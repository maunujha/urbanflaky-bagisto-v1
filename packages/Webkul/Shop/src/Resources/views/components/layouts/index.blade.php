@props([
    'hasHeader'       => true,
    'hasFeature'      => true,
    'hasFooter'       => true,
    'hasCustomSeo'    => false,
    'metaDescription' => "Shop men's polo t-shirts, slim fit tshirts and casual wear at Urbanflaky. Mid-range fashion Rs 299–799. Fast delivery pan India including Rajasthan. – Gabha Enterprise",
    'metaKeywords'    => 'polo tshirt online india, slim fit tshirt men, casual wear men women, buy tshirt under 500, urbanflaky, gabha enterprise, mens fashion jaipur, tshirt rajasthan',
    'robots'          => 'index, follow',
    'ogImage'         => null,
    'ogType'          => 'website',
    'canonical'       => null,
])

<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}"
    dir="{{ core()->getCurrentLocale()->direction }}"
>
    <head>

        {{-- Tag Manager + Clarity + data layer — kept as high in <head> as possible --}}
        <x-shop::layouts.tracking.head />

        {!! view_render_event('bagisto.shop.layout.head.before') !!}

        <title>{{ $title ?? "Urbanflaky — Men's Polo T-Shirts, Slim Fit & Casual Wear Online | Gabha Enterprise" }}</title>

        {{-- Page-specific meta pushed by individual views (product, category, etc.) --}}
        {{-- Must come BEFORE layout defaults so page-specific values take precedence --}}
        @stack('meta')

        {{-- Generic fallback meta — skipped when a page defines its own SEO block --}}
        @unless($hasCustomSeo)
            <meta name="description" content="{{ $metaDescription }}">
            <meta name="keywords" content="{{ $metaKeywords }}">
            <meta name="robots" content="{{ $robots }}">

            <meta property="og:title" content="{{ $title ?? "Urbanflaky — Men's Fashion Online | Gabha Enterprise" }}">
            <meta property="og:description" content="{{ $metaDescription }}">
            <meta property="og:image" content="{{ $ogImage ?? asset('images/og-image.jpg') }}">
            <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
            <meta property="og:type" content="{{ $ogType }}">

            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $title ?? "Urbanflaky — Men's Fashion Online | Gabha Enterprise" }}">
            <meta name="twitter:description" content="{{ $metaDescription }}">
            <meta name="twitter:image" content="{{ $ogImage ?? asset('images/og-image.jpg') }}">

            <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
        @endunless

        {{-- Always present — not page-specific --}}
        <meta property="og:site_name" content="Urbanflaky">
        <meta property="og:locale" content="en_IN">

        <meta charset="UTF-8">

        <meta
            http-equiv="X-UA-Compatible"
            content="IE=edge"
        >
        <meta
            http-equiv="content-language"
            content="{{ app()->getLocale() }}"
        >

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="base-url"
            content="{{ url()->to('/') }}"
        >
        <meta
            name="currency"
            content="{{ core()->getCurrentCurrency()->toJson() }}"
        >

        <link
            rel="icon"
            sizes="16x16"
            href="{{ core()->getCurrentChannel()->favicon_url ?? bagisto_asset('images/favicon.ico') }}"
        />

        @bagistoVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/css/urbanflaky.css', 'src/Resources/assets/js/app.js'])

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
            crossorigin
        />

        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        />

        {{-- Load fonts without blocking render: fetch as a preload, then flip
             to a stylesheet on load. Browsers without JS still get them via the
             <noscript> fallback. --}}
        <link
            rel="preload" as="style"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;800&family=DM+Serif+Display&display=swap"
            onload="this.onload=null;this.rel='stylesheet'"
        />

        <noscript>
            <link
                rel="stylesheet"
                href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;800&family=DM+Serif+Display&display=swap"
            />
        </noscript>

        @stack('styles')

        <style>
            {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
        </style>

        @if(core()->getConfigData('general.content.speculation_rules.enabled'))
            <script type="speculationrules">
                @json(core()->getSpeculationRules(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            </script>
        @endif

        {!! view_render_event('bagisto.shop.layout.head.after') !!}

        @stack('structured_data')

    </head>

    <body>
        <!-- Google Tag Manager (noscript) — must be first child of <body> -->
        <x-shop::layouts.tracking.noscript />

        <!-- Premium "UF" site preloader — sibling before #app so Vue never manages it -->
        <x-shop::layouts.preloader />

        <!-- GDPR cookie consent — sibling before #app so Vue never manages it -->
        @if (\App\Support\CookieConsent::enabled())
            <x-shop::layouts.cookie />
        @endif

        {!! view_render_event('bagisto.shop.layout.body.before') !!}

        <a
            href="#main"
            class="skip-to-main-content-link"
        >
            Skip to main content
        </a>

        <!-- Built With Bagisto -->
        <div id="app">
            <!-- Flash Message Blade Component -->
            <x-shop::flash-group />

            <!-- Confirm Modal Blade Component -->
            <x-shop::modal.confirm />

            <!-- Exit Intent Welcome Discount Popup -->
            @if (\App\Support\ExitIntentPopup::enabled())
                <x-shop::layouts.exit-intent-popup />
            @endif

            <!-- Page Header Blade Component -->
            @if ($hasHeader)
                <x-shop::layouts.header />
            @endif

            {!! view_render_event('bagisto.shop.layout.content.before') !!}

            <!-- Page Content Blade Component -->
            <main id="main" class="uf-main">
                {{ $slot }}
            </main>

            {!! view_render_event('bagisto.shop.layout.content.after') !!}


            <!-- Page Services Blade Component -->
            @if ($hasFeature)
                <x-shop::layouts.services />
            @endif

            <!-- Page Footer Blade Component -->
            @if ($hasFooter)
                <x-shop::layouts.footer />
            @endif
        </div>

        {!! view_render_event('bagisto.shop.layout.body.after') !!}

        @stack('scripts')

        <script src="{{ asset('js/urbanflaky.js') }}" defer></script>

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.before') !!}
        <script>
            /**
             * Load event, the purpose of using the event is to mount the application
             * after all of our `Vue` components which is present in blade file have
             * been registered in the app. No matter what `app.mount()` should be
             * called in the last.
             */
            window.addEventListener("load", function (event) {
                app.mount("#app");
            });
        </script>

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.after') !!}

        <script type="text/javascript">
            {!! core()->getConfigData('general.content.custom_scripts.custom_javascript') !!}
        </script>
    </body>
</html>
