{{-- Return request received: what was asked for, and what happens next. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.rma.mail.customer-rma-create.preheader', ['id' => $rma->id])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $rma->order->increment_id])"
        :title="trans('shop::app.rma.mail.customer-rma-create.heading')"
        :name="$rma->order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.rma.mail.customer-rma-create.next', ['id' => $rma->id])
    </x-shop::email.text>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
        @foreach ($rma->items as $item)
            <tr>
                <td valign="top" style="padding:14px 0; border-top:1px solid #262626; font-size:15px; line-height:21px; color:#f5f5f5;">
                    <strong>{{ $item->orderItem?->name }}</strong><br />
                    <span style="font-size:13px; color:#a3a3a3;">
                        @lang('shop::app.emails.qty', ['qty' => $item->quantity])
                        @if ($item->reason?->title)
                            · {{ $item->reason->title }}
                        @endif
                    </span>
                </td>
            </tr>
        @endforeach
    </table>

    @if ($rma->information)
        <x-shop::email.panel>{!! nl2br(e(strip_tags($rma->information))) !!}</x-shop::email.panel>
    @endif

    <x-shop::email.button :url="route('shop.customers.account.rma.view', $rma->id)">
        @lang('shop::app.rma.mail.customer-rma-create.cta')
    </x-shop::email.button>
@endcomponent
