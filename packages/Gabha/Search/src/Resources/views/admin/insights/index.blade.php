<x-admin::layouts>
    <x-slot:title>
        Search Insights
    </x-slot>

    @php
        $total = (int) $stats['total'];
        $pct   = fn ($n) => $total ? round($n / $total * 100, 1) : 0;
    @endphp

    {{-- Header + window selector --------------------------------------------------- --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Search Insights
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Natural-language search analytics · last {{ $range }} days
            </p>
        </div>

        <div class="flex items-center gap-1 rounded-lg border bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
            @foreach ($ranges as $option)
                <a
                    href="{{ route('admin.search.insights.index', ['range' => $option]) }}"
                    @class([
                        'rounded-md px-3 py-1.5 text-sm font-semibold transition',
                        'bg-violet-600 text-white' => $range === $option,
                        'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => $range !== $option,
                    ])
                >
                    {{ $option }}d
                </a>
            @endforeach
        </div>
    </div>

    @if (! $total)
        <div class="mt-8 rounded-lg border border-dashed bg-white p-10 text-center dark:border-gray-800 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No searches recorded in this window yet.</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Storefront searches appear here once shoppers start searching (analytics driver must be <code>database</code>).
            </p>
        </div>
    @else
        {{-- KPI cards ----------------------------------------------------------------- --}}
        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Total searches', 'value' => $total, 'sub' => 'in the last '.$range.' days', 'tone' => 'text-gray-800 dark:text-white'],
                    ['label' => 'Zero-result', 'value' => $stats['zero'], 'sub' => $pct($stats['zero']).'% of searches', 'tone' => 'text-red-600 dark:text-red-400'],
                    ['label' => 'Expressed intent', 'value' => $stats['with_intent'], 'sub' => $pct($stats['with_intent']).'% structured', 'tone' => 'text-violet-600 dark:text-violet-400'],
                    ['label' => 'Relaxed', 'value' => $stats['relaxed'], 'sub' => $pct($stats['relaxed']).'% needed fallback', 'tone' => 'text-amber-600 dark:text-amber-400'],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="rounded-lg border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold {{ $card['tone'] }}">{{ $card['value'] }}</p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $card['sub'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Top + zero-result terms --------------------------------------------------- --}}
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-lg border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-3 font-semibold text-gray-800 dark:text-white">Top searches</p>

                <div class="divide-y dark:divide-gray-800">
                    @forelse ($topTerms as $row)
                        <div class="flex items-center justify-between py-2">
                            <span class="truncate pr-3 text-sm text-gray-700 dark:text-gray-200">{{ $row->term }}</span>
                            <span class="flex shrink-0 items-center gap-2">
                                <span class="text-xs text-gray-400">~{{ (int) $row->avg_results }} results</span>
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $row->hits }}</span>
                            </span>
                        </div>
                    @empty
                        <p class="py-2 text-sm text-gray-400">No data.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-3 flex items-center justify-between">
                    <p class="font-semibold text-gray-800 dark:text-white">Zero-result searches</p>
                    <span class="text-xs text-gray-400">catalog / synonym gaps</span>
                </div>

                <div class="divide-y dark:divide-gray-800">
                    @forelse ($zeroTerms as $row)
                        <div class="flex items-center justify-between py-2">
                            <span class="truncate pr-3 text-sm text-gray-700 dark:text-gray-200">{{ $row->term }}</span>
                            <span class="shrink-0 rounded bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600 dark:bg-red-900/30 dark:text-red-400">{{ $row->hits }}</span>
                        </div>
                    @empty
                        <p class="py-2 text-sm text-emerald-600 dark:text-emerald-400">None — every search returned a product.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Facet usage + distributions ----------------------------------------------- --}}
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-lg border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-3 font-semibold text-gray-800 dark:text-white">Facet usage</p>

                @php
                    $facetRows = [
                        'Colour'       => $facets['color'],
                        'Price'        => $facets['price'],
                        'Gender'       => $facets['gender'],
                        'Product type' => $facets['product_type'],
                        'Section'      => $facets['category'],
                    ];
                @endphp

                <div class="space-y-3">
                    @foreach ($facetRows as $label => $count)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                                <span class="text-gray-400">{{ $count }} · {{ $pct($count) }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-violet-500" style="width: {{ $pct($count) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-3 font-semibold text-gray-800 dark:text-white">Demand breakdown</p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @php
                        $dists = ['Colour' => $colors, 'Gender' => $genders, 'Type' => $types];
                    @endphp

                    @foreach ($dists as $label => $rows)
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</p>
                            <div class="space-y-1.5">
                                @forelse ($rows as $row)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="truncate pr-2 capitalize text-gray-600 dark:text-gray-300">{{ $row->label }}</span>
                                        <span class="text-gray-400">{{ $row->hits }}</span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400">—</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Full search log ----------------------------------------------------------- --}}
        <div class="mt-6 flex items-center justify-between">
            <p class="text-base font-semibold text-gray-800 dark:text-white">Search log</p>
            <x-admin::datagrid.export :src="route('admin.search.insights.index')" />
        </div>

        <div class="mt-2">
            <x-admin::datagrid :src="route('admin.search.insights.index')" />
        </div>
    @endif
</x-admin::layouts>
