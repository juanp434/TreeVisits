<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitRequest;
use App\Http\Resources\CustomerResource;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function __construct(private readonly VisitService $visits)
    {
    }

    /**
     * Receive a visit event from a device.
     */
    public function store(StoreVisitRequest $request): JsonResponse
    {
        $occurredAt = $request->date('occurred_at');

        $outcome = $this->visits->registerVisit($request->string('customer_id'), $occurredAt);

        return CustomerResource::make($outcome->customer)
            ->additional(['tree_planted' => $outcome->treePlanted])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Visits aggregated per hour (used by the dashboard).
     */
    public function hourly(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->visits->visitsPerHour($request->query('customer_id')),
        ]);
    }
}
