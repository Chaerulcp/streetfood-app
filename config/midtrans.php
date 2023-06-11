<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration of Midtrans.
    | Make sure to provide the correct values for the server key and client key.
    |
    */

    'serverKey' => env('MIDTRANS_SERVER_KEY'),
    'clientKey' => env('MIDTRANS_CLIENT_KEY'),

    'isProduction' => env('MIDTRANS_IS_PRODUCTION', false),
    'is3ds' => env('MIDTRANS_IS_3DS', false),

    // Other configuration options...

];
