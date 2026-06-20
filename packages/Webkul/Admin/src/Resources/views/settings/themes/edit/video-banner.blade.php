@php
    $options = $theme->translate($currentLocale->code)->options ?? [];

    /* Flat list of selectable categories (root excluded) for the category picker. */
    $vbCategories = app(\Webkul\Category\Repositories\CategoryRepository::class)
        ->getModel()
        ->whereNotNull('parent_id')
        ->orderBy('position')
        ->get()
        ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])
        ->values();

    /* Pre-selected product (when this banner already links to one) for display. */
    $vbProduct = null;

    if (($options['link_type'] ?? '') === 'product' && ! empty($options['link_id'])) {
        $vbProduct = app(\Webkul\Product\Repositories\ProductRepository::class)->find($options['link_id']);
    }
@endphp

<v-video-banner :errors="errors"></v-video-banner>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-video-banner-template"
    >
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="mb-2.5 flex flex-col gap-1">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.themes.edit.video-banner.title')
                    </p>

                    <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                        @lang('admin::app.settings.themes.edit.video-banner.description')
                    </p>
                </div>

                <!-- Persisted media paths — carried across saves when no new file is chosen. -->
                <input type="hidden" name="{{ $currentLocale->code }}[options][video]" :value="options.video" />
                <input type="hidden" name="{{ $currentLocale->code }}[options][mobile_video]" :value="options.mobile_video" />
                <input type="hidden" name="{{ $currentLocale->code }}[options][logo]" :value="options.logo" />
                <input type="hidden" name="{{ $currentLocale->code }}[options][poster]" :value="options.poster" />

                <!-- Desktop Video -->
                <x-admin::form.control-group class="mb-2.5 pt-4">
                    <x-admin::form.control-group.label class="required">
                        @lang('admin::app.settings.themes.edit.video-banner.video')
                    </x-admin::form.control-group.label>

                    <div class="flex flex-wrap items-center gap-4">
                        <video
                            v-if="previews.video"
                            :src="previews.video"
                            class="h-24 w-40 rounded border border-gray-200 object-cover dark:border-gray-800"
                            muted
                            playsinline
                        ></video>

                        <div>
                            <label class="secondary-button" for="vb_video_file">
                                @lang('admin::app.settings.themes.edit.video-banner.choose-video')
                            </label>

                            <input
                                type="file"
                                id="vb_video_file"
                                name="video_file"
                                class="hidden"
                                accept="video/mp4,video/webm"
                                @change="onFile($event, 'video')"
                            />
                        </div>
                    </div>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                        @lang('admin::app.settings.themes.edit.video-banner.video-hint')
                    </p>

                    <x-admin::form.control-group.error control-name="video_file" />
                </x-admin::form.control-group>

                <!-- Mobile Video (optional) -->
                <x-admin::form.control-group class="mb-2.5">
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.themes.edit.video-banner.mobile-video')
                    </x-admin::form.control-group.label>

                    <div class="flex flex-wrap items-center gap-4">
                        <video
                            v-if="previews.mobile_video"
                            :src="previews.mobile_video"
                            class="h-24 w-40 rounded border border-gray-200 object-cover dark:border-gray-800"
                            muted
                            playsinline
                        ></video>

                        <div>
                            <label class="secondary-button" for="vb_mobile_video_file">
                                @lang('admin::app.settings.themes.edit.video-banner.choose-video')
                            </label>

                            <input
                                type="file"
                                id="vb_mobile_video_file"
                                name="mobile_video_file"
                                class="hidden"
                                accept="video/mp4,video/webm"
                                @change="onFile($event, 'mobile_video')"
                            />
                        </div>
                    </div>

                    <x-admin::form.control-group.error control-name="mobile_video_file" />
                </x-admin::form.control-group>

                <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                    <!-- Logo -->
                    <x-admin::form.control-group class="mb-2.5">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.themes.edit.video-banner.logo')
                        </x-admin::form.control-group.label>

                        <div class="flex flex-wrap items-center gap-4">
                            <img
                                v-if="previews.logo"
                                :src="previews.logo"
                                class="h-16 w-16 rounded border border-gray-200 object-contain p-1 dark:border-gray-800"
                                alt="@lang('admin::app.settings.themes.edit.video-banner.logo')"
                            />

                            <div>
                                <label class="secondary-button" for="vb_logo_file">
                                    @lang('admin::app.settings.themes.edit.video-banner.choose-image')
                                </label>

                                <input
                                    type="file"
                                    id="vb_logo_file"
                                    name="logo_file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="onFile($event, 'logo')"
                                />
                            </div>
                        </div>

                        <x-admin::form.control-group.error control-name="logo_file" />
                    </x-admin::form.control-group>

                    <!-- Poster (optional) -->
                    <x-admin::form.control-group class="mb-2.5">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.themes.edit.video-banner.poster')
                        </x-admin::form.control-group.label>

                        <div class="flex flex-wrap items-center gap-4">
                            <img
                                v-if="previews.poster"
                                :src="previews.poster"
                                class="h-16 w-24 rounded border border-gray-200 object-cover dark:border-gray-800"
                                alt="@lang('admin::app.settings.themes.edit.video-banner.poster')"
                            />

                            <div>
                                <label class="secondary-button" for="vb_poster_file">
                                    @lang('admin::app.settings.themes.edit.video-banner.choose-image')
                                </label>

                                <input
                                    type="file"
                                    id="vb_poster_file"
                                    name="poster_file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="onFile($event, 'poster')"
                                />
                            </div>
                        </div>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                            @lang('admin::app.settings.themes.edit.video-banner.poster-hint')
                        </p>

                        <x-admin::form.control-group.error control-name="poster_file" />
                    </x-admin::form.control-group>
                </div>

                <span class="mb-4 mt-2 block w-full border-b dark:border-gray-800"></span>

                <!-- Title (multiline) -->
                <x-admin::form.control-group class="mb-2.5">
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.themes.edit.video-banner.banner-title')
                    </x-admin::form.control-group.label>

                    <textarea
                        name="{{ $currentLocale->code }}[options][title]"
                        rows="2"
                        v-model="options.title"
                        class="flex w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        placeholder="@lang('admin::app.settings.themes.edit.video-banner.banner-title')"
                    ></textarea>
                </x-admin::form.control-group>

                <!-- Description (multiline) -->
                <x-admin::form.control-group class="mb-2.5">
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.themes.edit.video-banner.banner-description')
                    </x-admin::form.control-group.label>

                    <textarea
                        name="{{ $currentLocale->code }}[options][description]"
                        rows="3"
                        v-model="options.description"
                        class="flex w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        placeholder="@lang('admin::app.settings.themes.edit.video-banner.banner-description')"
                    ></textarea>
                </x-admin::form.control-group>

                <!-- Show Product Price -->
                <x-admin::form.control-group class="mb-2.5 flex items-center gap-2.5">
                    <input type="hidden" name="{{ $currentLocale->code }}[options][show_price]" :value="options.show_price ? 1 : 0" />

                    <label class="relative inline-flex cursor-pointer items-center">
                        <input
                            type="checkbox"
                            class="peer sr-only"
                            v-model="options.show_price"
                        />
                        <span class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:top-0.5 after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full dark:bg-gray-800 ltr:after:left-0.5 rtl:after:right-0.5"></span>
                    </label>

                    <x-admin::form.control-group.label class="!mb-0">
                        @lang('admin::app.settings.themes.edit.video-banner.show-price')
                    </x-admin::form.control-group.label>
                </x-admin::form.control-group>

                <span class="mb-4 mt-2 block w-full border-b dark:border-gray-800"></span>

                <!-- Link Type -->
                <x-admin::form.control-group class="mb-2.5">
                    <x-admin::form.control-group.label class="required">
                        @lang('admin::app.settings.themes.edit.video-banner.link-type')
                    </x-admin::form.control-group.label>

                    <select
                        name="{{ $currentLocale->code }}[options][link_type]"
                        v-model="options.link_type"
                        class="custom-select flex min-h-[39px] w-full rounded-md border bg-white px-3 py-1.5 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >
                        <option value="product">@lang('admin::app.settings.themes.edit.video-banner.product')</option>
                        <option value="category">@lang('admin::app.settings.themes.edit.video-banner.category')</option>
                    </select>
                </x-admin::form.control-group>

                <!-- Selected entity id -->
                <input type="hidden" name="{{ $currentLocale->code }}[options][link_id]" :value="options.link_id" />

                <!-- Product selector -->
                <x-admin::form.control-group
                    class="mb-2.5"
                    v-if="options.link_type === 'product'"
                >
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.themes.edit.video-banner.select-product')
                    </x-admin::form.control-group.label>

                    <div
                        class="flex items-center justify-between gap-2.5 rounded border border-slate-300 p-4 dark:border-gray-800"
                        v-if="selectedProduct"
                    >
                        <div class="flex items-center gap-2.5">
                            <img
                                v-if="selectedProduct.images && selectedProduct.images.length"
                                :src="selectedProduct.images[0].url"
                                class="h-12 w-12 rounded object-cover"
                            />
                            <div class="grid gap-1">
                                <p class="font-semibold text-gray-800 dark:text-white">@{{ selectedProduct.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-300">@{{ selectedProduct.sku }}</p>
                            </div>
                        </div>

                        <p
                            class="cursor-pointer text-red-600 transition-all hover:underline"
                            @click="clearProduct"
                        >
                            @lang('admin::app.settings.themes.edit.delete')
                        </p>
                    </div>

                    <div
                        class="secondary-button mt-2.5 w-fit"
                        @click="$refs.productSearch.openDrawer()"
                    >
                        @lang('admin::app.settings.themes.edit.video-banner.select-product')
                    </div>

                    <x-admin::products.search
                        ref="productSearch"
                        ::added-product-ids="selectedProduct ? [selectedProduct.id] : []"
                        @onProductAdded="onProductAdded($event)"
                    />
                </x-admin::form.control-group>

                <!-- Category selector -->
                <x-admin::form.control-group
                    class="mb-2.5"
                    v-if="options.link_type === 'category'"
                >
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.themes.edit.video-banner.select-category')
                    </x-admin::form.control-group.label>

                    <select
                        v-model="options.link_id"
                        class="custom-select flex min-h-[39px] w-full rounded-md border bg-white px-3 py-1.5 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >
                        <option value="">@lang('admin::app.settings.themes.edit.select')</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >@{{ category.name }}</option>
                    </select>
                </x-admin::form.control-group>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-video-banner', {
            template: '#v-video-banner-template',

            props: ['errors'],

            data() {
                return {
                    options: Object.assign({
                        title: '',
                        description: '',
                        show_price: false,
                        link_type: 'product',
                        link_id: '',
                        video: '',
                        mobile_video: '',
                        logo: '',
                        poster: '',
                    }, @json($options)),

                    categories: @json($vbCategories),

                    selectedProduct: @json($vbProduct ? ['id' => $vbProduct->id, 'name' => $vbProduct->name, 'sku' => $vbProduct->sku, 'images' => $vbProduct->images->map(fn ($i) => ['url' => $i->url])] : null),

                    previews: {
                        video: @json(! empty($options['video']) ? asset($options['video']) : null),
                        mobile_video: @json(! empty($options['mobile_video']) ? asset($options['mobile_video']) : null),
                        logo: @json(! empty($options['logo']) ? asset($options['logo']) : null),
                        poster: @json(! empty($options['poster']) ? asset($options['poster']) : null),
                    },
                };
            },

            created() {
                /* Normalise the persisted checkbox (stored as 0/1/"true") to a boolean. */
                this.options.show_price = this.options.show_price === true
                    || this.options.show_price === 1
                    || this.options.show_price === '1';
            },

            watch: {
                /* Switching the destination must not keep a stale id from the other type. */
                'options.link_type'() {
                    this.options.link_id = '';
                    this.selectedProduct = null;
                },
            },

            methods: {
                onFile(event, key) {
                    const file = event.target.files[0];

                    if (! file) {
                        return;
                    }

                    this.previews[key] = URL.createObjectURL(file);
                },

                onProductAdded(products) {
                    if (! products.length) {
                        return;
                    }

                    const product = products[products.length - 1];

                    this.selectedProduct = product;
                    this.options.link_id = product.id;
                },

                clearProduct() {
                    this.selectedProduct = null;
                    this.options.link_id = '';
                },
            },
        });
    </script>
@endPushOnce
