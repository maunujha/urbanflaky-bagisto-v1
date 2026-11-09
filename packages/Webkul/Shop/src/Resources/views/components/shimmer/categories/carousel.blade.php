@props(['count' => 4, 'navigationLink' => false])

{{-- Mirrors the sizing in components/categories/carousel so the section does not shift on load. --}}
<section class="uf-cat-section w-full px-3 py-5 md:px-10 md:py-10 xl:py-14" {{ $attributes }}>
    <div class="mx-auto max-w-[1120px] overflow-hidden [container-type:inline-size]">
        <div class="flex w-max min-w-full gap-2 lg:gap-4">
            @for ($i = 0; $i < $count; $i++)
                <div
                    class="shimmer h-[150px] w-[calc((100cqw-24px)/3.25)] shrink-0 rounded-md sm:h-[200px] md:h-[260px] lg:aspect-[4/5] lg:h-auto lg:w-[calc((100cqw-48px)/4)]"
                    aria-hidden="true"
                ></div>
            @endfor
        </div>
    </div>
</section>
