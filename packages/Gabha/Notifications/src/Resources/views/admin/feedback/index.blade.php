<x-admin::layouts>
    <x-slot:title>
        @lang('notifications::app.admin.feedback.title')
    </x-slot>

    <p class="text-xl font-bold text-gray-800 dark:text-white">
        @lang('notifications::app.admin.feedback.title')
    </p>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
        @lang('notifications::app.admin.feedback.info')
    </p>

    <div class="mt-4 flex flex-wrap gap-4">
        <div class="box-shadow min-w-[200px] flex-1 rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">@lang('notifications::app.admin.feedback.average')</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $count ? $average.' / 5' : '—' }}</p>
        </div>

        <div class="box-shadow min-w-[200px] flex-1 rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">@lang('notifications::app.admin.feedback.count')</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $count }}</p>
        </div>
    </div>

    <div class="box-shadow mt-4 overflow-x-auto rounded bg-white dark:bg-gray-900">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <tr>
                    @foreach (['date', 'order', 'customer', 'rating', 'comment'] as $column)
                        <th class="px-4 py-3 font-semibold">@lang('notifications::app.admin.feedback.columns.'.$column)</th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="text-gray-700 dark:text-gray-300">
                @forelse ($feedback as $row)
                    <tr class="border-b align-top dark:border-gray-800">
                        <td class="whitespace-nowrap px-4 py-3">{{ core()->formatDate($row->updated_at, 'd M Y') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($row->order)
                                <a href="{{ route('admin.sales.orders.view', $row->order_id) }}" class="text-blue-600 hover:underline">#{{ $row->order->increment_id }}</a>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $row->email }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $row->rating }} / 5</td>
                        <td class="px-4 py-3">{{ $row->comment }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">@lang('notifications::app.admin.feedback.empty')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $feedback->links() }}
    </div>
</x-admin::layouts>
