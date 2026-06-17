{{--
    Google Tag Manager <noscript> fallback — must sit immediately after <body>.
    Gated by the same container id as the head snippet.
--}}
@php $gtmId = config('services.gtm.container_id'); @endphp

@if ($gtmId)
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
@endif
