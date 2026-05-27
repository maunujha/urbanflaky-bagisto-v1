{!! view_render_event('bagisto.shop.layout.features.before') !!}

<!--
    The ThemeCustomizationRepository repository is injected directly here because there is no way
    to retrieve it from the view composer, as this is an anonymous component.
-->
@inject('themeCustomizationRepository', 'Webkul\Theme\Repositories\ThemeCustomizationRepository')

@php
    $channel = core()->getCurrentChannel();

    $customization = $themeCustomizationRepository->findOneWhere([
        'type'       => 'services_content',
        'status'     => 1,
        'theme_code' => $channel->theme,
        'channel_id' => $channel->id,
    ]); 
@endphp

<!-- Features / Trust Badges -->
@if ($customization)
    <section class="w-full px-5 py-16 md:px-10 md:py-24 xl:px-14 xl:py-28" v-pre>
        <div class="relative mx-auto max-w-[1360px]">
            <div class="grid grid-cols-2 gap-3.5 md:grid-cols-4 md:gap-5 xl:gap-6">
                @foreach ($customization->options['services'] as $service)
                    <div class="group relative isolate flex flex-col items-center gap-[18px] overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] px-[18px] pb-7 pt-8 text-center backdrop-blur-sm transition-all duration-300 lg:hover:-translate-y-1 lg:hover:border-uf-accent/40 lg:hover:bg-white/[0.06] lg:hover:shadow-[0_18px_36px_rgba(0,0,0,0.45)]">
                        <span
                            class="{{ $service['service_icon'] }} inline-flex h-[60px] w-[60px] flex-shrink-0 items-center justify-center rounded-full border-[1.5px] border-uf-accent/30 bg-uf-accent/[0.08] text-[28px] leading-none text-uf-accent transition-all duration-300 md:h-16 md:w-16 md:text-[30px] lg:group-hover:scale-105 lg:group-hover:border-uf-accent lg:group-hover:bg-uf-accent lg:group-hover:text-black lg:group-hover:shadow-[0_6px_18px_rgba(199,235,49,0.35)]"
                            role="presentation"
                        ></span>

                        <div class="flex flex-col items-center gap-1.5">
                            <p class="m-0 font-poppins text-[13px] font-bold uppercase leading-tight tracking-[1.5px] text-white md:text-sm md:tracking-[2px]">
                                {{ $service['title'] }}
                            </p>

                            <p class="m-0 max-w-[220px] font-poppins text-xs font-normal leading-relaxed text-neutral-400 md:text-[13px]">
                                {{ $service['description'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{!! view_render_event('bagisto.shop.layout.features.after') !!}