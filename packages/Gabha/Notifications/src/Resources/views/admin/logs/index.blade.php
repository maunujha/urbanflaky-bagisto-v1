<x-admin::layouts>
    <x-slot:title>
        @lang('notifications::app.admin.title')
    </x-slot>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('notifications::app.admin.title')
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                @lang('notifications::app.admin.info')
            </p>
        </div>
    </div>

    <!-- Stats -->
    <div class="mt-4 flex flex-wrap gap-4">
        @foreach ($stats as $label => $value)
            <div class="box-shadow min-w-[200px] flex-1 rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-300">@lang('notifications::app.admin.stats.'.$label)</p>
                <p class="mt-2 text-2xl font-bold {{ $label === 'failed-week' && $value > 0 ? 'text-red-600' : 'text-gray-800 dark:text-white' }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <!-- Templates -->
    <div class="box-shadow mt-4 flex flex-wrap items-center justify-between gap-3 rounded bg-white p-4 dark:bg-gray-900">
        <p class="text-sm text-gray-600 dark:text-gray-300">@lang('notifications::app.admin.templates-hint')</p>

        <a href="{{ route('admin.notifications.templates.index') }}" class="secondary-button">
            @lang('notifications::app.admin.templates.menu')
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" class="mt-4 flex flex-wrap items-center gap-2">
        <select name="status" class="rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <option value="">@lang('notifications::app.admin.filters.all-status')</option>
            @foreach (\Gabha\Notifications\Models\EmailLog::STATUSES as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>@lang('notifications::app.admin.statuses.'.$status)</option>
            @endforeach
        </select>

        <select name="type" class="rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <option value="">@lang('notifications::app.admin.filters.all-types')</option>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
            @endforeach
        </select>

        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="@lang('notifications::app.admin.filters.search')"
            class="rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
        >

        <button type="submit" class="primary-button">@lang('notifications::app.admin.filters.apply')</button>
    </form>

    @if ($consent)
        <!-- Marketing consent for the searched address -->
        <div class="box-shadow mt-4 rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('notifications::app.admin.consent.title') · {{ $consent['email'] }}
            </p>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                @lang('notifications::app.admin.consent.'.($consent['subscribed'] ? 'subscribed' : 'unsubscribed'))

                @if ($consent['suppressions'])
                    · @lang('notifications::app.admin.consent.suppressed', ['scopes' => implode(', ', $consent['suppressions'])])
                @endif
            </p>

            <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-300">@lang('notifications::app.admin.consent.history')</p>

            <ul class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                @forelse ($consent['history'] as $entry)
                    <li>{{ core()->formatDate($entry->created_at, 'd M Y, H:i') }} · {{ str_replace('_', ' ', $entry->action) }} · {{ $entry->source }}</li>
                @empty
                    <li>@lang('notifications::app.admin.consent.none')</li>
                @endforelse
            </ul>
        </div>
    @endif

    <!-- Log -->
    <div class="box-shadow mt-4 overflow-x-auto rounded bg-white dark:bg-gray-900">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <tr>
                    @foreach (['date', 'type', 'recipient', 'subject', 'status'] as $column)
                        <th class="px-4 py-3 font-semibold">@lang('notifications::app.admin.columns.'.$column)</th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="text-gray-700 dark:text-gray-300">
                @forelse ($logs as $log)
                    <tr class="border-b align-top dark:border-gray-800">
                        <td class="whitespace-nowrap px-4 py-3">{{ core()->formatDate($log->created_at, 'd M Y, H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $log->type }}</td>
                        <td class="px-4 py-3">{{ $log->recipient }}</td>
                        <td class="px-4 py-3">{{ $log->subject }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'label-active'     => $log->status === 'sent',
                                'label-pending'    => $log->status === 'queued',
                                'label-canceled'   => $log->status === 'failed',
                                'label-closed'     => $log->status === 'suppressed',
                            ])>@lang('notifications::app.admin.statuses.'.$log->status)</span>

                            @if ($log->error)
                                <p @class([
                                    'mt-1 max-w-xs break-words text-xs',
                                    'text-red-600' => $log->status === 'failed',
                                    'text-gray-500' => $log->status !== 'failed',
                                ])>{{ \Illuminate\Support\Str::limit($log->error, 160) }}</p>
                            @endif

                            @if ($log->canRetry() && bouncer()->hasPermission('marketing.communications.email_log.retry'))
                                <form method="POST" action="{{ route('admin.notifications.logs.retry', $log->id) }}" class="mt-2">
                                    @csrf
                                    <button type="submit" class="secondary-button text-xs">@lang('notifications::app.admin.retry.button')</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">@lang('notifications::app.admin.empty')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</x-admin::layouts>
