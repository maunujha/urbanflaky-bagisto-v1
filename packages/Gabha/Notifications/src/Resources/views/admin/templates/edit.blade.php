<x-admin::layouts>
    <x-slot:title>
        {{ $entry['label'] }} · @lang('notifications::app.admin.templates.title')
    </x-slot>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.notifications.templates.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-300">
                ← @lang('notifications::app.admin.templates.title')
            </a>

            <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ $entry['label'] }}</p>
        </div>
    </div>

    <div class="mt-4 flex gap-4 max-xl:flex-wrap">
        <div class="flex w-full flex-col gap-4 xl:w-[420px] xl:shrink-0">
            {{-- Subject --}}
            <form method="POST" action="{{ route('admin.notifications.templates.update', $key) }}" class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                @csrf
                @method('PUT')

                <label for="subject" class="text-sm font-semibold text-gray-800 dark:text-white">@lang('notifications::app.admin.templates.subject')</label>

                <input
                    id="subject"
                    name="subject"
                    type="text"
                    maxlength="200"
                    value="{{ old('subject', $custom) }}"
                    placeholder="{{ $rendered?->subject }}"
                    class="mt-2 w-full rounded-md border px-3 py-2 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                >

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">@lang('notifications::app.admin.templates.subject-help')</p>

                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach ($placeholders as $placeholder => $label)
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200" title="{{ $label }}">{{ $placeholder }}</code>
                    @endforeach
                </div>

                <button type="submit" class="primary-button mt-4">@lang('notifications::app.admin.templates.save')</button>
            </form>

            {{-- Test send --}}
            <form method="POST" action="{{ route('admin.notifications.templates.test', $key) }}" class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                @csrf

                <label for="test-email" class="text-sm font-semibold text-gray-800 dark:text-white">@lang('notifications::app.admin.templates.test')</label>

                <input
                    id="test-email"
                    name="email"
                    type="email"
                    required
                    value="{{ old('email', auth()->guard('admin')->user()?->email) }}"
                    class="mt-2 w-full rounded-md border px-3 py-2 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                >

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    @lang('notifications::app.admin.templates.test-help', ['left' => $testsLeft, 'limit' => \Gabha\Notifications\Http\Controllers\Admin\EmailTemplateController::TEST_LIMIT])
                </p>

                <button type="submit" class="secondary-button mt-4" @disabled(! $rendered || $testsLeft < 1)>
                    @lang('notifications::app.admin.templates.send-test')
                </button>
            </form>
        </div>

        {{-- Preview --}}
        <div class="box-shadow flex-1 rounded bg-white p-4 dark:bg-gray-900">
            @if ($rendered)
                <p class="text-xs text-gray-500 dark:text-gray-400">@lang('notifications::app.admin.templates.subject')</p>
                <p class="mt-1 text-base font-semibold text-gray-800 dark:text-white">{{ $rendered->subject }}</p>

                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">@lang('notifications::app.admin.templates.preview-note')</p>

                <iframe
                    src="{{ route('admin.notifications.templates.preview', $key) }}"
                    title="{{ $entry['label'] }}"
                    class="mt-3 h-[760px] w-full rounded border bg-black dark:border-gray-800"
                    sandbox
                ></iframe>

                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-gray-800 dark:text-white">@lang('notifications::app.admin.templates.plain-text')</summary>
                    <pre class="mt-2 max-h-96 overflow-auto whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $rendered->text }}</pre>
                </details>
            @else
                <p class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">@lang('notifications::app.admin.no-preview')</p>
            @endif
        </div>
    </div>
</x-admin::layouts>
