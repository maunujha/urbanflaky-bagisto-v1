<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.account.orders.title')
    </x-slot>

    <!-- Breadcrumbs -->
    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="orders" />
        @endSection
    @endif

    <div class="mx-4">
        <x-shop::layouts.account.navigation />
    </div>

    <span class="mb-5 mt-2 w-full border-t border-white/10"></span>

    <!--Customers logout-->
    @auth('customer')
        <div class="mx-4">
            <div class="mx-auto w-[400px] rounded-lg border border-white/20 text-center transition hover:border-uf-accent max-sm:w-full">
                <x-shop::form
                    method="DELETE"
                    action="{{ route('shop.customer.session.destroy') }}"
                    id="customerLogout"
                />

                <a
                    class="flex min-h-12 items-center justify-center gap-1.5 text-base hover:bg-white/5 hover:text-uf-accent"
                    href="{{ route('shop.customer.session.destroy') }}"
                    onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                >
                    @lang('shop::app.components.layouts.header.desktop.bottom.logout')
                </a>
            </div>
        </div>
    @endauth

</x-shop::layouts.account>