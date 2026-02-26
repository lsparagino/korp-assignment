<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Approval Thresholds
    |--------------------------------------------------------------------------
    |
    | Transactions above these amounts (per currency) require manager approval
    | when initiated by a Member. Admins and Managers bypass these thresholds.
    |
    */

    'approval_thresholds' => [
        'USD' => 10000,
        'EUR' => 10000,
    ],

];
