<?php

return [
    'auth_key' => env('MSG91_AUTH_KEY'),
    'sender_id' => env('MSG91_SENDER_ID', 'GYMSTH'),
    'whatsapp_number' => env('MSG91_WHATSAPP_INTEGRATED_NUMBER'),
    'templates' => [
        'renewal_reminder' => env('MSG91_TEMPLATE_RENEWAL_REMINDER'),
        'welcome' => env('MSG91_TEMPLATE_WELCOME'),
        'payment_confirm' => env('MSG91_TEMPLATE_PAYMENT_CONFIRM'),
        'payment_failed' => env('MSG91_TEMPLATE_PAYMENT_FAILED'),
        'expiry_warning' => env('MSG91_TEMPLATE_EXPIRY_WARNING'),
        'membership_expired' => env('MSG91_TEMPLATE_MEMBERSHIP_EXPIRED'),
    ],
];
