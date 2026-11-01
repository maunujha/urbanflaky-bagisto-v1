<x-shop::layouts
    robots="noindex, nofollow"
    :has-feature="false"
>
    <x-slot:title>
        @lang('notifications::app.preferences.title')
    </x-slot>

    <div class="container flex justify-center px-4 py-16 max-md:py-10">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 p-8 max-md:p-6">
            <h1 class="font-dmserif text-2xl text-black">
                @lang('notifications::app.preferences.title')
            </h1>

            @if ($done)
                <p class="mt-4 text-base text-zinc-600" role="status">
                    @lang('notifications::app.preferences.done')
                </p>
            @else
                <p class="mt-4 text-base text-black">
                    @lang('notifications::app.preferences.confirm', ['email' => $email])
                </p>

                <p class="mt-2 text-sm text-zinc-500">
                    @lang('notifications::app.preferences.scopes.'.$scope).
                    @lang('notifications::app.preferences.note')
                </p>

                {{-- Posts back to this same signed URL. --}}
                <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6">
                    @csrf

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-black px-6 py-3 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2"
                    >
                        @lang('notifications::app.preferences.button')
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-shop::layouts>
