<?php

use Illuminate\Support\Facades\Route;

use App\Models\Customer;

Route::get('/', function () {
    return view('dashboard', [
        'visitsPerTree' => max(1, (int) config('trees.visits_per_tree')),
        'totalVisits' => Customer::sum('visits_count'),
        'totalTrees' => Customer::sum('trees_planted'),
    ]);
});
