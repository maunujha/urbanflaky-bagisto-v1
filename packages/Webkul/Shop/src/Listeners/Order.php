<?php

namespace Webkul\Shop\Listeners;

use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Contracts\OrderComment;
use Webkul\Shop\Mail\Order\CanceledNotification;
use Webkul\Shop\Mail\Order\CommentedNotification;

class Order extends Base
{
    /**
     * Send cancel order mail.
     *
     * @param  OrderContract  $order
     * @return void
     */
    public function afterCanceled($order)
    {
        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.cancel_order')) {
                return;
            }

            $this->prepareMail($order, new CanceledNotification($order));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Send order comment mail.
     *
     * @param  OrderComment  $comment
     * @return void
     */
    public function afterCommented($comment)
    {
        if (! $comment->customer_notified) {
            return;
        }

        try {
            /**
             * Email to customer.
             */
            $this->prepareMail($comment, new CommentedNotification($comment));
        } catch (\Exception $e) {
            report($e);
        }
    }
}
