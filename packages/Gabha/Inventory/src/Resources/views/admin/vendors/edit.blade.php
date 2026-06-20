<x-admin::layouts>
    <x-slot:title>
        @lang('inventory::app.admin.vendors.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.inventory.vendors.update', $vendor->id)"
        method="PUT"
    >
        @include('inventory::admin.vendors.form', ['vendor' => $vendor])
    </x-admin::form>
</x-admin::layouts>
