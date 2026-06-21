<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Allow Negative Account Balances
    |--------------------------------------------------------------------------
    |
    | When disabled, expense and transfer operations that would drive an
    | account below zero will be rejected before any ledger mutation occurs.
    |
    */

    'allow_negative_balances' => env('LEDGER_ALLOW_NEGATIVE_BALANCES', false),

];
