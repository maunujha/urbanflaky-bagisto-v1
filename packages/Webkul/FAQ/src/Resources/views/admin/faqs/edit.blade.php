<x-admin::layouts>
    <x-slot:title>
        @lang('faq::app.admin.faqs.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.faqs.update', $faq->id)"
        method="PUT"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('faq::app.admin.faqs.edit.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.faqs.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('faq::app.admin.faqs.create.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('faq::app.admin.faqs.edit.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <!-- Left -->
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <!-- Question -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('faq::app.admin.faqs.create.question')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="question"
                            rules="required"
                            :value="old('question', $faq->question)"
                            :label="trans('faq::app.admin.faqs.create.question')"
                            :placeholder="trans('faq::app.admin.faqs.create.question')"
                        />

                        <x-admin::form.control-group.error control-name="question" />
                    </x-admin::form.control-group>

                    <!-- Answer -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('faq::app.admin.faqs.create.answer')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            id="answer"
                            name="answer"
                            rules="required"
                            :value="old('answer', $faq->answer)"
                            :label="trans('faq::app.admin.faqs.create.answer')"
                            :placeholder="trans('faq::app.admin.faqs.create.answer')"
                            :tinymce="true"
                        />

                        <x-admin::form.control-group.error control-name="answer" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <!-- Right -->
            <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('faq::app.admin.faqs.create.general')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <!-- Category -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('faq::app.admin.faqs.create.category')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="faq_category_id"
                                rules="required"
                                class="cursor-pointer"
                                :value="old('faq_category_id', $faq->faq_category_id)"
                                :label="trans('faq::app.admin.faqs.create.category')"
                            >
                                <option value="">@lang('faq::app.admin.faqs.create.select-category')</option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="faq_category_id" />
                        </x-admin::form.control-group>

                        <!-- Sort Order -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('faq::app.admin.faqs.create.sort-order')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="sort_order"
                                rules="integer|min:0"
                                :value="old('sort_order', $faq->sort_order)"
                                :label="trans('faq::app.admin.faqs.create.sort-order')"
                                :placeholder="trans('faq::app.admin.faqs.create.sort-order')"
                            />

                            <x-admin::form.control-group.error control-name="sort_order" />
                        </x-admin::form.control-group>

                        <!-- Status -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('faq::app.admin.faqs.create.status')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="switch"
                                name="status"
                                class="cursor-pointer"
                                value="1"
                                :checked="(bool) old('status', $faq->status)"
                                :label="trans('faq::app.admin.faqs.create.status')"
                            />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
