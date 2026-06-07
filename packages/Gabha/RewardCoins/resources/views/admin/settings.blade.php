<x-admin::layouts>
    <x-slot:title>
        @lang('reward-coins::reward_coins.admin.settings.title')
    </x-slot>

    <x-admin::form :action="route('admin.reward_coins.settings.update')">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('reward-coins::reward_coins.admin.settings.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.reward_coins.index') }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                    @lang('reward-coins::reward_coins.admin.dashboard.title')
                </a>

                <button type="submit" class="primary-button">
                    @lang('reward-coins::reward_coins.admin.settings.save')
                </button>
            </div>
        </div>

        <div class="mt-3.5 box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <div class="grid grid-cols-1 gap-x-6 md:grid-cols-2">
                {{-- Earning rate --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('reward-coins::reward_coins.admin.settings.earning_rate')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="earning_rate"
                        rules="required"
                        :value="old('earning_rate', $settings->earning_rate)"
                    />

                    <x-admin::form.control-group.error control-name="earning_rate" />
                </x-admin::form.control-group>

                {{-- Coins per unit --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('reward-coins::reward_coins.admin.settings.coins_per_unit')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="coins_per_unit"
                        rules="required|integer"
                        :value="old('coins_per_unit', $settings->coins_per_unit)"
                    />

                    <x-admin::form.control-group.error control-name="coins_per_unit" />
                </x-admin::form.control-group>

                {{-- Minimum order amount --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('reward-coins::reward_coins.admin.settings.min_order_amount')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="min_order_amount"
                        rules="required"
                        :value="old('min_order_amount', $settings->min_order_amount)"
                    />

                    <x-admin::form.control-group.error control-name="min_order_amount" />
                </x-admin::form.control-group>

                {{-- Max redemption per order --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('reward-coins::reward_coins.admin.settings.max_redemption_per_order')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="max_redemption_per_order"
                        rules="required"
                        :value="old('max_redemption_per_order', $settings->max_redemption_per_order)"
                    />

                    <x-admin::form.control-group.error control-name="max_redemption_per_order" />
                </x-admin::form.control-group>

                {{-- Max redemption percent --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('reward-coins::reward_coins.admin.settings.max_redemption_percent')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="max_redemption_percent"
                        rules="required|integer"
                        :value="old('max_redemption_percent', $settings->max_redemption_percent)"
                    />

                    <x-admin::form.control-group.error control-name="max_redemption_percent" />
                </x-admin::form.control-group>

                {{-- Expiry days --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('reward-coins::reward_coins.admin.settings.expiry_days')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="expiry_days"
                        rules="required|integer"
                        :value="old('expiry_days', $settings->expiry_days)"
                    />

                    <x-admin::form.control-group.error control-name="expiry_days" />
                </x-admin::form.control-group>

                {{-- Pending confirmation days --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('reward-coins::reward_coins.admin.settings.pending_confirmation_days')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="pending_confirmation_days"
                        rules="required|integer"
                        :value="old('pending_confirmation_days', $settings->pending_confirmation_days)"
                    />

                    <x-admin::form.control-group.error control-name="pending_confirmation_days" />
                </x-admin::form.control-group>
            </div>

            {{-- Toggles --}}
            <div class="mt-2 flex flex-col gap-3">
                <label class="flex items-center gap-2 text-gray-700 dark:text-white">
                    <input type="checkbox" name="exclude_discounted_items" value="1" @checked(old('exclude_discounted_items', $settings->exclude_discounted_items)) />
                    @lang('reward-coins::reward_coins.admin.settings.exclude_discounted_items')
                </label>

                <label class="flex items-center gap-2 text-gray-700 dark:text-white">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $settings->is_active)) />
                    @lang('reward-coins::reward_coins.admin.settings.is_active')
                </label>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
