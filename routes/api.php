<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

// Visit events from devices.
Route::post('/visits', [VisitController::class, 'store']);
Route::get('/visits/hourly', [VisitController::class, 'hourly']);

// Customer state.
Route::get('/customers', [CustomerController::class, 'index']);
Route::get('/customers/{externalId}', [CustomerController::class, 'show']);
