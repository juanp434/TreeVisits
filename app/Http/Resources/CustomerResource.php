<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer_id' => $this->external_id,
            'visits_count' => $this->visits_count,
            'trees_planted' => $this->trees_planted,
            'last_visit_at' => $this->last_visit_at?->toIso8601String(),
        ];
    }
}
