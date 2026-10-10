@php
    $look = $look ?? null;
    $taggedProducts = $taggedProducts ?? [];
@endphp

<div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
    <!-- Left: Media + Tagged products -->
    <div class="flex w-full flex-col gap-2">
        <!-- Media -->
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                @lang('lookbook::app.admin.form.media')
            </p>

            <!-- Content Type -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('lookbook::app.admin.form.type')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="type"
                    rules="required"
                    :value="old('type', $look?->type ?? 'image')"
                    :label="trans('lookbook::app.admin.form.type')"
                >
                    <option value="image">@lang('lookbook::app.admin.form.type-image')</option>
                    <option value="reel">@lang('lookbook::app.admin.form.type-reel')</option>
                </x-admin::form.control-group.control>

                <x-admin::form.control-group.error control-name="type" />
            </x-admin::form.control-group>

            <!-- Thumbnail / Image -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label :class="! $look ? 'required' : ''">
                    @lang('lookbook::app.admin.form.image')
                </x-admin::form.control-group.label>

                @if ($look?->image_url)
                    <img
                        src="{{ $look->image_url }}"
                        class="mb-2 h-40 w-32 rounded-md object-cover"
                        alt="{{ $look->title }}"
                    />
                @endif

                <input
                    type="file"
                    name="image"
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    class="flex w-full cursor-pointer rounded-md border bg-white px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('lookbook::app.admin.form.image-info')
                </p>

                <x-admin::form.control-group.error control-name="image" />
            </x-admin::form.control-group>

            <!-- Video upload -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('lookbook::app.admin.form.video')
                </x-admin::form.control-group.label>

                @if ($look?->video)
                    <video
                        src="{{ \Illuminate\Support\Facades\Storage::url($look->video) }}"
                        class="mb-2 h-44 w-full rounded-md bg-black object-contain"
                        controls
                        muted
                    ></video>
                @endif

                <input
                    type="file"
                    name="video"
                    accept="video/mp4,video/webm,video/quicktime"
                    class="flex w-full cursor-pointer rounded-md border bg-white px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('lookbook::app.admin.form.video-info')
                </p>

                <x-admin::form.control-group.error control-name="video" />
            </x-admin::form.control-group>

            <!-- Video URL (external, optional) -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('lookbook::app.admin.form.video-url')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="video_url"
                    rules="url"
                    :value="old('video_url', $look?->video_url)"
                    :label="trans('lookbook::app.admin.form.video-url')"
                    placeholder="https://…/reel.mp4"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('lookbook::app.admin.form.video-url-info')
                </p>

                <x-admin::form.control-group.error control-name="video_url" />
            </x-admin::form.control-group>

            <!-- Instagram reel / post link (CTA redirect) -->
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('lookbook::app.admin.form.permalink')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="permalink"
                    rules="url"
                    :value="old('permalink', $look?->permalink)"
                    :label="trans('lookbook::app.admin.form.permalink')"
                    placeholder="https://www.instagram.com/reel/…"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('lookbook::app.admin.form.permalink-info')
                </p>

                <x-admin::form.control-group.error control-name="permalink" />
            </x-admin::form.control-group>
        </div>

        <!-- Tagged products -->
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('lookbook::app.admin.form.tagged-products')
            </p>

            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                @lang('lookbook::app.admin.form.tagged-info')
            </p>

            <v-lookbook-tagging :initial='@json($taggedProducts)'></v-lookbook-tagging>
        </div>
    </div>

    <!-- Right: General settings -->
    <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                @lang('lookbook::app.admin.form.general')
            </p>

            <!-- Title -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('lookbook::app.admin.form.title')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="title"
                    :value="old('title', $look?->title)"
                    :label="trans('lookbook::app.admin.form.title')"
                    :placeholder="trans('lookbook::app.admin.form.title')"
                />

                <x-admin::form.control-group.error control-name="title" />
            </x-admin::form.control-group>

            <!-- Collection label -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('lookbook::app.admin.form.collection-name')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="collection_name"
                    :value="old('collection_name', $look?->collection_name)"
                    :label="trans('lookbook::app.admin.form.collection-name')"
                    placeholder="Summer Drop"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('lookbook::app.admin.form.collection-info')
                </p>
            </x-admin::form.control-group>

            <!-- Caption -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('lookbook::app.admin.form.caption')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="textarea"
                    name="caption"
                    :value="old('caption', $look?->caption)"
                    :label="trans('lookbook::app.admin.form.caption')"
                />
            </x-admin::form.control-group>

            <!-- Display order -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('lookbook::app.admin.form.display-order')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="display_order"
                    rules="numeric"
                    :value="old('display_order', $look?->display_order ?? 0)"
                    :label="trans('lookbook::app.admin.form.display-order')"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('lookbook::app.admin.form.display-order-info')
                </p>
            </x-admin::form.control-group>

            <!-- Featured -->
            <x-admin::form.control-group>
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_featured"
                        value="1"
                        class="h-4 w-4 rounded border-gray-300"
                        {{ old('is_featured', $look?->is_featured) ? 'checked' : '' }}
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        @lang('lookbook::app.admin.form.featured')
                    </span>
                </label>
            </x-admin::form.control-group>

            <!-- Status -->
            <x-admin::form.control-group class="!mb-0">
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        name="status"
                        value="1"
                        class="h-4 w-4 rounded border-gray-300"
                        {{ old('status', $look?->status ?? true) ? 'checked' : '' }}
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        @lang('lookbook::app.admin.form.status')
                    </span>
                </label>
            </x-admin::form.control-group>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-lookbook-tagging-template"
    >
        <div>
            <!-- Hidden inputs submitted with the form -->
            <input
                v-for="product in selected"
                :key="'pid-' + product.id"
                type="hidden"
                name="product_ids[]"
                :value="product.id"
            />

            <!-- Search box -->
            <div class="relative">
                <input
                    type="text"
                    v-model="query"
                    @input="search"
                    class="flex w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    placeholder="@lang('lookbook::app.admin.form.search-products')"
                />

                <!-- Results dropdown -->
                <div
                    v-if="results.length"
                    class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        v-for="product in results"
                        :key="'res-' + product.id"
                        class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800"
                        @click="add(product)"
                    >
                        <img v-if="product.image" :src="product.image" class="h-8 w-8 rounded object-cover" />
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-200" v-text="product.name"></p>
                            <p class="text-xs text-gray-400" v-text="product.sku"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected products -->
            <div class="mt-3 flex flex-wrap gap-2">
                <p v-if="! selected.length" class="text-xs text-gray-400">
                    @lang('lookbook::app.admin.form.no-products')
                </p>

                <div
                    v-for="product in selected"
                    :key="'sel-' + product.id"
                    class="flex items-center gap-2 rounded-full border bg-gray-50 py-1 pl-1 pr-2 dark:border-gray-800 dark:bg-gray-800"
                >
                    <img v-if="product.image" :src="product.image" class="h-7 w-7 rounded-full object-cover" />
                    <span class="text-xs text-gray-700 dark:text-gray-200" v-text="product.name"></span>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-red-500"
                        @click="remove(product.id)"
                        aria-label="@lang('lookbook::app.admin.form.remove')"
                    >&times;</button>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lookbook-tagging', {
            template: '#v-lookbook-tagging-template',

            props: {
                initial: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    query: '',
                    results: [],
                    selected: [...this.initial],
                    timer: null,
                };
            },

            methods: {
                search() {
                    clearTimeout(this.timer);

                    if (this.query.trim().length < 2) {
                        this.results = [];
                        return;
                    }

                    this.timer = setTimeout(() => {
                        this.$axios.get("{{ route('admin.lookbook.search_products') }}", {
                            params: { query: this.query },
                        }).then(response => {
                            const taggedIds = this.selected.map(p => p.id);
                            this.results = response.data.data.filter(p => ! taggedIds.includes(p.id));
                        }).catch(() => {
                            this.results = [];
                        });
                    }, 300);
                },

                add(product) {
                    if (! this.selected.some(p => p.id === product.id)) {
                        this.selected.push(product);
                    }
                    this.query = '';
                    this.results = [];
                },

                remove(id) {
                    this.selected = this.selected.filter(p => p.id !== id);
                },
            },
        });
    </script>
@endPushOnce
