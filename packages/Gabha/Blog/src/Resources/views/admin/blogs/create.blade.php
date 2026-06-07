<x-admin::layouts>
    <x-slot:title>
        @lang('blog::app.admin.blogs.create.title')
    </x-slot>

    <x-admin::form :action="route('admin.blogs.store')" enctype="multipart/form-data">
        @include('blog::admin.blogs.form', ['blog' => null])
    </x-admin::form>
</x-admin::layouts>
