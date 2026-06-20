<x-admin::layouts>
    <x-slot:title>
        @lang('inventory::app.admin.vendors.create.title')
    </x-slot>

    <x-admin::form :action="route('admin.inventory.vendors.store')">
        @include('inventory::admin.vendors.form', ['vendor' => null])
    </x-admin::form>
</x-admin::layouts>
