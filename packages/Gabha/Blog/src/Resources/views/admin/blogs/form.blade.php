@php
    $isEdit = ! empty($blog);
@endphp

<div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
    <p class="text-xl font-bold text-gray-800 dark:text-white">
        {{ $isEdit ? trans('blog::app.admin.blogs.edit.title') : trans('blog::app.admin.blogs.create.title') }}
    </p>

    <div class="flex items-center gap-x-2.5">
        <a
            href="{{ route('admin.blogs.index') }}"
            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        >
            @lang('blog::app.admin.blogs.create.back-btn')
        </a>

        <button type="submit" class="primary-button">
            {{ $isEdit ? trans('blog::app.admin.blogs.edit.save-btn') : trans('blog::app.admin.blogs.create.save-btn') }}
        </button>
    </div>
</div>

<div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
    <!-- Left -->
    <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                @lang('blog::app.admin.blogs.create.general')
            </p>

            <!-- Title -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('blog::app.admin.blogs.create.post-title')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="title"
                    rules="required"
                    :value="old('title', $isEdit ? $blog->title : '')"
                    :label="trans('blog::app.admin.blogs.create.post-title')"
                    :placeholder="trans('blog::app.admin.blogs.create.post-title-placeholder')"
                />

                <x-admin::form.control-group.error control-name="title" />
            </x-admin::form.control-group>

            <!-- Slug -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('blog::app.admin.blogs.create.slug')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="slug"
                    :value="old('slug', $isEdit ? $blog->slug : '')"
                    :label="trans('blog::app.admin.blogs.create.slug')"
                    :placeholder="trans('blog::app.admin.blogs.create.slug-placeholder')"
                />

                <x-admin::form.control-group.error control-name="slug" />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                    @lang('blog::app.admin.blogs.create.slug-info')
                </p>
            </x-admin::form.control-group>

            <!-- Short Description -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('blog::app.admin.blogs.create.short-description')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="textarea"
                    name="short_description"
                    :value="old('short_description', $isEdit ? $blog->short_description : '')"
                    :label="trans('blog::app.admin.blogs.create.short-description')"
                />

                <x-admin::form.control-group.error control-name="short_description" />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                    @lang('blog::app.admin.blogs.create.short-description-info')
                </p>
            </x-admin::form.control-group>

            <!-- Content -->
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('blog::app.admin.blogs.create.content')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="textarea"
                    id="content"
                    name="content"
                    :value="old('content', $isEdit ? $blog->content : '')"
                    :label="trans('blog::app.admin.blogs.create.content')"
                    :tinymce="true"
                />

                <x-admin::form.control-group.error control-name="content" />
            </x-admin::form.control-group>
        </div>

        <!-- Featured Image -->
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                @lang('blog::app.admin.blogs.create.image')
            </p>

            <p class="mb-3 text-xs text-gray-500 dark:text-gray-300">
                @lang('blog::app.admin.blogs.create.image-info')
            </p>

            <x-admin::media.images
                name="image"
                :uploaded-images="$isEdit && $blog->image ? [['id' => 'image', 'url' => $blog->image_url]] : []"
            />
        </div>
    </div>

    <!-- Right -->
    <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
        <!-- Publish -->
        <x-admin::accordion>
            <x-slot:header>
                <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('blog::app.admin.blogs.create.publish')
                </p>
            </x-slot>

            <x-slot:content>
                <!-- Status -->
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('blog::app.admin.blogs.create.status')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="switch"
                        name="status"
                        class="cursor-pointer"
                        value="1"
                        :checked="(bool) old('status', $isEdit ? $blog->status : true)"
                        :label="trans('blog::app.admin.blogs.create.status')"
                    />

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                        @lang('blog::app.admin.blogs.create.status-info')
                    </p>
                </x-admin::form.control-group>

                <!-- Author -->
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('blog::app.admin.blogs.create.author')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="author"
                        :value="old('author', $isEdit ? $blog->author : '')"
                        :label="trans('blog::app.admin.blogs.create.author')"
                    />

                    <x-admin::form.control-group.error control-name="author" />
                </x-admin::form.control-group>

                <!-- Publish Date -->
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('blog::app.admin.blogs.create.published-at')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="date"
                        name="published_at"
                        :value="old('published_at', $isEdit && $blog->published_at ? $blog->published_at->format('Y-m-d') : now()->format('Y-m-d'))"
                        :label="trans('blog::app.admin.blogs.create.published-at')"
                    />

                    <x-admin::form.control-group.error control-name="published_at" />
                </x-admin::form.control-group>
            </x-slot>
        </x-admin::accordion>

        <!-- SEO -->
        <x-admin::accordion>
            <x-slot:header>
                <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('blog::app.admin.blogs.create.seo')
                </p>
            </x-slot>

            <x-slot:content>
                <!-- Meta Title -->
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('blog::app.admin.blogs.create.meta-title')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="meta_title"
                        :value="old('meta_title', $isEdit ? $blog->meta_title : '')"
                        :label="trans('blog::app.admin.blogs.create.meta-title')"
                    />

                    <x-admin::form.control-group.error control-name="meta_title" />

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                        @lang('blog::app.admin.blogs.create.meta-title-info')
                    </p>
                </x-admin::form.control-group>

                <!-- Meta Description -->
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('blog::app.admin.blogs.create.meta-description')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        name="meta_description"
                        :value="old('meta_description', $isEdit ? $blog->meta_description : '')"
                        :label="trans('blog::app.admin.blogs.create.meta-description')"
                    />

                    <x-admin::form.control-group.error control-name="meta_description" />

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                        @lang('blog::app.admin.blogs.create.meta-description-info')
                    </p>
                </x-admin::form.control-group>

                <!-- Meta Keywords -->
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('blog::app.admin.blogs.create.meta-keywords')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="meta_keywords"
                        :value="old('meta_keywords', $isEdit ? $blog->meta_keywords : '')"
                        :label="trans('blog::app.admin.blogs.create.meta-keywords')"
                        :placeholder="trans('blog::app.admin.blogs.create.meta-keywords-placeholder')"
                    />

                    <x-admin::form.control-group.error control-name="meta_keywords" />
                </x-admin::form.control-group>
            </x-slot>
        </x-admin::accordion>
    </div>
</div>
