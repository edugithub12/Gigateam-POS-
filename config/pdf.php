<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company Information
    | Auto-injected into every PDF by PdfService. Never pass manually.
    |--------------------------------------------------------------------------
    */
    'company' => [
        'name'     => 'GIGATEAM SOLUTIONS LTD',
        'tagline'  => 'Secured & Connected',
        'address1' => 'White Angle House, 1st Floor – Suite 62',
        'po_box'   => 'P O Box 47271-00100, Nairobi',
        'phone1'   => '+254 111292948',
        'phone2'   => '718811661',
        'email1'   => 'sales@gigateamltd.com',
        'email2'   => 'gigateamsolutions@gmail.com',
        'website'  => 'www.gigateamsolutions.co.ke',
        'kra_pin'  => 'P051892936Q',
        'vat_no'   => 'P051892936Q',
        'footer'   => 'Gigateam Solutions Ltd | sales@gigateamltd.com | +254 111292948 | www.gigateamsolutions.co.ke',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo – absolute local path. DomPDF cannot load remote URLs.
    | Logo is at: public/images/gigateam-logo.png
    |--------------------------------------------------------------------------
    */
    'logo_path' => public_path('images/gigateam-logo.png'),

    /*
    |--------------------------------------------------------------------------
    | Paper
    |--------------------------------------------------------------------------
    */
    'paper'       => 'a4',
    'orientation' => 'portrait',

    /*
    |--------------------------------------------------------------------------
    | VAT – Kenya standard rate 16%
    |--------------------------------------------------------------------------
    */
    'vat_rate' => 0.16,

    /*
    |--------------------------------------------------------------------------
    | Payment Methods (receipts)
    |--------------------------------------------------------------------------
    */
    'payment_methods' => [
        'mpesa'  => 'M-Pesa',
        'cash'   => 'Cash',
        'card'   => 'Card / POS',
        'bank'   => 'Bank Transfer',
        'cheque' => 'Cheque',
    ],

];