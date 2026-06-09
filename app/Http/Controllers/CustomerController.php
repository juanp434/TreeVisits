<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * List all customers with their counters.
     */
    public function index()
    {
        return CustomerResource::collection(
            Customer::orderByDesc('last_visit_at')->get()
        );
    }

    /**
     * Show a single customer by its device-provided external id.
     */
    public function show(string $externalId): CustomerResource
    {
        $customer = Customer::where('external_id', $externalId)->firstOrFail();

        return CustomerResource::make($customer);
    }
}
