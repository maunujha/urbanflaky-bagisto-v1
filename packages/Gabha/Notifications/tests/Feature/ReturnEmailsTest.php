<?php

use Webkul\RMA\Models\RMA;
use Webkul\RMA\Models\RMAStatus;
use Webkul\Shop\Mail\Customer\RMA\CustomerRMARequestNotification;
use Webkul\Shop\Mail\Customer\RMA\CustomerRMAStatusNotification;

require_once __DIR__.'/Helpers.php';

/*
 * Return emails go to the address given at checkout, not the account email:
 * phone-only accounts hold a placeholder address, and guests have no account.
 */

function returnFor($order, string $status): RMA
{
    $rma = (new RMA)->forceFill(['id' => 4242, 'order_id' => $order->id]);

    $rma->setRelation('order', $order);
    $rma->setRelation('status', (new RMAStatus)->forceFill(['title' => $status]));

    return $rma;
}

it('sends return emails to the checkout email of a phone-only customer', function () {
    $customer = Webkul\Customer\Models\Customer::factory()->create(['email' => '9876543210@noreply.urbanflaky.in']);
    $order = notificationsOrder(['customer_id' => $customer->id, 'customer_email' => 'real@example.com']);

    foreach ([new CustomerRMARequestNotification(returnFor($order, 'Pending')), new CustomerRMAStatusNotification(returnFor($order, 'Accepted'))] as $mail) {
        expect($mail->envelope()->to[0]->address)->toBe('real@example.com');
    }
});

it('reaches guests, who have no account at all', function () {
    $order = notificationsOrder(['customer_id' => null, 'customer_email' => 'guest@example.com', 'is_guest' => 1]);

    $mail = new CustomerRMAStatusNotification(returnFor($order, 'Declined'));

    expect($mail->envelope()->to[0]->address)->toBe('guest@example.com');
    expect($mail->render())->toContain('Declined');
});

it('names the outcome in the subject', function (string $status) {
    $mail = new CustomerRMAStatusNotification(returnFor(notificationsOrder(), $status));

    expect($mail->envelope()->subject)->toBe("Return request #4242: {$status}");
})->with(['Accepted', 'Declined']);
