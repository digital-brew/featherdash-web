<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Freemius Checkout
    |--------------------------------------------------------------------------
    |
    | The Featherdash product in Freemius, used by the pricing block's buttons
    | to open the checkout. Both values are public (they ship inside the
    | plugin too); the plan, licences and coupon are set on the block.
    |
    */

    'product_id' => env('FREEMIUS_PRODUCT_ID', '42160'),

    'public_key' => env('FREEMIUS_PUBLIC_KEY', 'pk_76113026c5e682104fb14d697f06f'),

];
