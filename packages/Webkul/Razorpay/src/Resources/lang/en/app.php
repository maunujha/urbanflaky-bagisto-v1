<?php

return [
    'drop-in-ui' => [
        'title' => 'Razorpay',
    ],

    'response' => [
        'payment' => [
            'cancelled' => 'Razorpay payment has been cancelled.',
        ],

        'something-went-wrong' => 'Something went wrong.',
        'supported-currency-error' => 'The currency :currency is not supported. Supported Currencies: :supportedCurrencies.',

        'refund' => [
            'failed' => 'The refund could not be processed through Razorpay, so no refund was recorded. Check the logs and try again, or refund the payment from the Razorpay dashboard.',
            'missing-payment' => 'No Razorpay payment reference was found for this order. Refund it manually from the Razorpay dashboard.',
        ],
    ],
];
