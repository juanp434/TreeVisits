<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Visits per tree
    |--------------------------------------------------------------------------
    |
    | How many visits a customer must accumulate before one tree is planted
    | for them. "X visits = 1 tree". Configurable via the VISITS_PER_TREE
    | environment variable.
    |
    */

    'visits_per_tree' => (int) env('VISITS_PER_TREE', 5),

];
