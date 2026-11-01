<x-admin::layouts>
    <x-slot:title>
        @lang('notifications::app.admin.templates.title')
    </x-slot>

    <p class="text-xl font-bold text-gray-800 dark:text-white">
        @lang('notifications::app.admin.templates.title')
    </p>

    <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-300">
        @lang('notifications::app.admin.templates.info')
    </p>

    @foreach ($groups as $group => $entries)
        <div class="box-shadow mt-4 overflow-x-auto rounded bg-white dark:bg-gray-900">
            <p class="border-b px-4 py-3 text-base font-semibold text-gray-800 dark:border-gray-800 dark:text-white">{{ $group }}</p>

            <table class="w-full text-left text-sm">
                <tbody class="text-gray-700 dark:text-gray-300">
                    @foreach ($entries as $key => $entry)
                        <tr class="border-b last:border-0 dark:border-gray-800">
                            <td class="w-1/3 px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $entry['label'] }}</td>
                            <td class="px-4 py-3">
                                @if (! empty($subjects[$key]))
                                    {{ $subjects[$key] }}
                                    <span class="ml-2 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">@lang('notifications::app.admin.templates.custom')</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">@lang('notifications::app.admin.templates.default-subject')</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.notifications.templates.edit', $key) }}" class="secondary-button">
                                    @lang('notifications::app.admin.templates.open')
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</x-admin::layouts>
