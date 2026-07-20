<?php

return [
    'company_name' => env('INVOICE_COMPANY_NAME', 'Your Company Name'),
    'tagline' => env('INVOICE_TAGLINE', 'Quality products and professional service'),
    'address' => env('INVOICE_ADDRESS', 'Company address'),
    'city' => env('INVOICE_CITY', ''),
    'state' => env('INVOICE_STATE', ''),
    'postcode' => env('INVOICE_POSTCODE', ''),
    'gst_number' => env('INVOICE_GST_NUMBER', ''),
    'pan_number' => env('INVOICE_PAN_NUMBER', ''),
    'email' => env('INVOICE_EMAIL', ''),
    'phone' => env('INVOICE_PHONE', ''),

    'bank_name' => env('INVOICE_BANK_NAME', ''),
    'account_no' => env('INVOICE_ACCOUNT_NO', ''),
    'ifsc' => env('INVOICE_IFSC', ''),
    'branch' => env('INVOICE_BRANCH', ''),
];
