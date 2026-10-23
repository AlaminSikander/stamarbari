<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Online payment
    |--------------------------------------------------------------------------
    |
    | When enabled, website bookings hold the rooms and send the guest to the
    | payment gateway for the advance. When disabled, a website booking is saved
    | as a "pending confirmation" request: no payment is taken, nothing is held
    | or confirmed automatically, and staff confirm it from the admin panel.
    |
    */

    'online_payment_enabled' => (bool) env('BOOKING_ONLINE_PAYMENT_ENABLED', false),

];
