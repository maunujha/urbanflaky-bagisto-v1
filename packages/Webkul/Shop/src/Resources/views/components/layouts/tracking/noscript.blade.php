{{--
    Google Tag Manager <noscript> fallback — must sit immediately after <body>.
    Gated by the same container id as the head snippet.
--}}
@php $gtmId = config('services.gtm.container_id'); @endphp

{{-- When the consent layer is ON, a no-JS visitor cannot make a choice, so the
     iframe is withheld (default = denied). It loads normally when consent is OFF. --}}
@if ($gtmId && ! \App\Support\CookieConsent::enabled())
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
@endif
