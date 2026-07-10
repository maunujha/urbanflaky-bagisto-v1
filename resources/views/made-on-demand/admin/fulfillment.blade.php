{{--
    Fulfillment section for the product edit form (injected via the
    `bagisto.admin.catalog.product.edit.form.after` view-render event).

    Exposes the native `is_made_on_demand` product flag as a toggle. The leading
    hidden input guarantees a value of `0` is submitted when the switch is off, so
    the flag can be cleared as well as set.
--}}
@php $isMadeOnDemand = old('is_made_on_demand') !== null ? old('is_made_on_demand') : $product->is_made_on_demand; @endphp

<div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
    <div class="flex flex-1 flex-col gap-2">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                Fulfillment
            </p>

            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                Choose how this product is produced and fulfilled.
            </p>

            <x-admin::form.control-group class="!mb-0 flex items-center justify-between gap-4">
                <div class="grid gap-0.5">
                    <x-admin::form.control-group.label class="!mb-0 !text-sm">
                        Made on Demand
                    </x-admin::form.control-group.label>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Produced only after an order is placed (e.g. print on demand). Shoppers see a notice on the product, cart and checkout pages.
                    </p>
                </div>

                <input
                    type="hidden"
                    name="is_made_on_demand"
                    value="0"
                />

                <x-admin::form.control-group.control
                    type="switch"
                    name="is_made_on_demand"
                    value="1"
                    label="Made on Demand"
                    :checked="(bool) $isMadeOnDemand"
                />
            </x-admin::form.control-group>
        </div>
    </div>
</div>
