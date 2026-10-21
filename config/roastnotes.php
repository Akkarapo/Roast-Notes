<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route after registration
    |--------------------------------------------------------------------------
    |
    | Named route a new member is sent to right after signing up.
    | The taste-profile module changes this to the Taste Quiz.
    |
    */

    'after_register_route' => 'home',

    /*
    |--------------------------------------------------------------------------
    | Shipping (baht)
    |--------------------------------------------------------------------------
    */

    'shipping_fee' => 50,

    'free_shipping_threshold' => 1000, // after discounts

    /*
    |--------------------------------------------------------------------------
    | Discount rules
    |--------------------------------------------------------------------------
    |
    | App\Support\Pricing\DiscountRule classes, applied in this order:
    | buy X get Y -> coupon -> member tier. Filled in by promotions and loyalty.
    |
    */

    'discount_rules' => [],

];
