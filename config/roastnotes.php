<?php

use App\Support\Pricing\Rules\BuyXGetYRule;
use App\Support\Pricing\Rules\CouponRule;
use App\Support\Pricing\Rules\LoyaltyTierRule;
use App\Support\Reports\Discounts;
use App\Support\Reports\FlavorNotes;
use App\Support\Reports\RoastShare;
use App\Support\Reports\SalesOverTime;
use App\Support\Reports\Summary;
use App\Support\Reports\TasteVsSales;
use App\Support\Reports\TopProducts;

return [

    /*
    |--------------------------------------------------------------------------
    | Route after registration
    |--------------------------------------------------------------------------
    |
    | Named route a new member is sent to right after signing up: the Taste Quiz.
    |
    */

    'after_register_route' => 'taste-quiz.edit',

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

    'discount_rules' => [
        BuyXGetYRule::class,
        CouponRule::class,
        LoyaltyTierRule::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sales report blocks (App\Support\Reports\Report), in page order
    |--------------------------------------------------------------------------
    */

    'reports' => [
        Summary::class,
        SalesOverTime::class,
        TopProducts::class,
        RoastShare::class,
        FlavorNotes::class,
        Discounts::class,
        TasteVsSales::class,
    ],

];
