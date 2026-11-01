<x-shop::layouts
    robots="noindex, nofollow"
    :has-feature="false"
>
    <x-slot:title>
        @lang('notifications::app.feedback.title')
    </x-slot>

    <div class="container flex justify-center px-4 py-16 max-md:py-10">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 p-8 max-md:p-6">
            <h1 class="font-dmserif text-2xl text-black">
                @lang('notifications::app.feedback.title')
            </h1>

            <p class="mt-2 text-sm text-zinc-500">
                @lang('notifications::app.emails.order-ref', ['order_id' => $order->increment_id])
            </p>

            @if ($done)
                <p class="mt-6 text-base text-zinc-700" role="status">
                    @lang('notifications::app.feedback.thanks')
                </p>
            @else
                {{-- Posts to this same signed URL. --}}
                <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6">
                    @csrf

                    <fieldset>
                        <legend class="text-sm font-medium text-black">@lang('notifications::app.feedback.question')</legend>

                        <div class="mt-3 flex gap-2">
                            @foreach (range(1, 5) as $value)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $value }}" class="peer sr-only" @checked($value === $rating) required>
                                    <span class="flex h-11 w-11 items-center justify-center rounded-lg border border-zinc-300 text-base font-semibold text-black transition peer-checked:border-black peer-checked:bg-black peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-black peer-focus-visible:ring-offset-2">
                                        {{ $value }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <p class="mt-2 text-xs text-zinc-500">@lang('notifications::app.emails.feedback.scale')</p>
                    </fieldset>

                    <label for="feedback-comment" class="mt-6 block text-sm font-medium text-black">
                        @lang('notifications::app.feedback.comment')
                    </label>

                    <textarea
                        id="feedback-comment"
                        name="comment"
                        rows="4"
                        maxlength="2000"
                        class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-black focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                    >{{ $feedback?->comment }}</textarea>

                    <button
                        type="submit"
                        class="mt-6 w-full rounded-lg bg-black px-6 py-3 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2"
                    >
                        @lang('notifications::app.feedback.send')
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-shop::layouts>
