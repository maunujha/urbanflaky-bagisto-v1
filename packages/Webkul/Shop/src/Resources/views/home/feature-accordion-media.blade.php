{{--
    Single feature-accordion media cell (image OR on-brand placeholder).
    Fills its sized parent (h-full). Used by both the desktop sticky stage
    and the in-panel mobile image so the markup stays in one place.
    Props: $item (array with image/alt/title), $i (zero-based index).
--}}
@if (! empty($item['image']))
    <img
        src="{{ $item['image'] }}"
        alt="{{ $item['alt'] }}"
        class="h-full w-full object-cover"
        loading="lazy"
        decoding="async"
    >
@else
    {{-- On-brand placeholder until a real image path is set in the array --}}
    <div
        class="grid h-full grid-rows-[1fr_auto] p-6 md:p-9"
        style="background: radial-gradient(120% 90% at 85% 12%, rgba(199,235,49,0.12) 0%, rgba(199,235,49,0) 55%), linear-gradient(160deg, #1c1c1c 0%, #0a0a0a 100%);"
    >
        <span class="justify-self-end self-start font-poppins text-6xl font-extrabold leading-none tabular-nums text-white/[0.06] md:text-8xl">{{ sprintf('%02d', $i + 1) }}</span>
        <div>
            <div class="font-poppins text-xl font-bold tracking-tight text-uf-text md:text-2xl">{{ $item['title'] }}</div>
            <div class="mt-2 font-poppins text-[0.7rem] font-semibold uppercase tracking-[2px] text-uf-accent">Urbanflaky</div>
        </div>
    </div>
@endif
