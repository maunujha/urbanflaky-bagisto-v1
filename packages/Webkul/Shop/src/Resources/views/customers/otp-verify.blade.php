<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="@lang('shop::app.customers.otp.verify-page-title')"/>
    <meta name="keywords" content="@lang('shop::app.customers.otp.verify-page-title')"/>
@endPush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.otp.verify-page-title')
    </x-slot>

    <div class="container mt-20 max-1180:px-5 max-md:mt-12">
        <!-- Company Logo -->
        <div class="flex items-center gap-x-14 max-[1180px]:gap-x-9">
            <a
                href="{{ route('shop.home.index') }}"
                class="m-[0_auto_20px_auto]"
                aria-label="{{ config('app.name') }}"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                    width="131"
                    height="29"
                >
            </a>
        </div>

        <!-- Form Container -->
        <div class="m-auto w-full max-w-[870px] rounded-xl border border-zinc-200 p-16 px-[90px] max-md:px-8 max-md:py-8 max-sm:border-none max-sm:p-0">
            <h1 class="font-dmserif text-4xl max-md:text-3xl max-sm:text-xl">
                @lang('shop::app.customers.otp.verify-page-title')
            </h1>

            <p class="mt-4 text-xl text-zinc-500 max-sm:mt-0 max-sm:text-sm">
                @lang('shop::app.customers.otp.verify-page-subtitle')
            </p>

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="mt-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-14 rounded max-sm:mt-8">
                <x-shop::form :action="route('shop.customer.otp.verify.store')">

                    <!-- OTP Input -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.otp.enter-otp')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            class="px-6 py-4 text-center text-2xl tracking-[1rem] max-md:py-3 max-sm:py-2"
                            name="otp"
                            rules="required|digits:4"
                            value=""
                            maxlength="4"
                            :label="trans('shop::app.customers.otp.enter-otp')"
                            :placeholder="trans('shop::app.customers.otp.otp-placeholder')"
                            aria-required="true"
                            autocomplete="one-time-code"
                            inputmode="numeric"
                        />

                        <x-shop::form.control-group.error control-name="otp" />
                    </x-shop::form.control-group>

                    <!-- Submit -->
                    <div class="mt-8 flex flex-wrap items-center gap-9 max-sm:justify-center max-sm:gap-5 max-sm:text-center">
                        <button
                            class="primary-button m-0 mx-auto block w-full max-w-[374px] rounded-2xl px-11 py-4 text-center text-base max-md:max-w-full max-md:rounded-lg max-md:py-3 max-sm:py-1.5 ltr:ml-0 rtl:mr-0"
                            type="submit"
                        >
                            @lang('shop::app.customers.otp.verify-button')
                        </button>
                    </div>
                </x-shop::form>
            </div>

            <!-- Resend OTP -->
            <div class="mt-6 text-center">
                <p class="text-sm text-zinc-500">
                    @lang('shop::app.customers.otp.didnt-receive')
                </p>

                <form action="{{ route('shop.customer.otp.resend') }}" method="POST" class="inline">
                    @csrf
                    <button
                        type="submit"
                        class="mt-1 cursor-pointer text-sm font-medium text-navyBlue hover:underline"
                    >
                        @lang('shop::app.customers.otp.resend-otp')
                    </button>
                </form>
            </div>

            <!-- Back to login -->
            <div class="mt-4 text-center">
                <a
                    href="{{ route('shop.customer.session.index') }}"
                    class="text-sm text-zinc-500 hover:underline"
                >
                    @lang('shop::app.customers.otp.back-to-login')
                </a>
            </div>
        </div>

        <p class="mb-4 mt-8 text-center text-xs text-zinc-500">
            @lang('shop::app.customers.login-form.footer', ['current_year' => date('Y')])
        </p>
    </div>
</x-shop::layouts>
