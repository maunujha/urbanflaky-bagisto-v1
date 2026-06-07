<x-admin::layouts>
    <x-slot:title>
        @lang('blog::app.admin.blogs.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.blogs.update', $blog->id)"
        method="PUT"
        enctype="multipart/form-data"
    >
        @include('blog::admin.blogs.form', ['blog' => $blog])
    </x-admin::form>
</x-admin::layouts>
