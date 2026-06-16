<?php

namespace App\Services;

use App\Domain\TreeReward;
use App\Domain\VisitOutcome;
use App\Models\Customer;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisitService
{
    public function __construct(private readonly TreeReward $reward)
    {
    }

    /**
     * Register a visit event for a customer.
     *
     * Creates the customer on first sight, records the visit event, updates
     * the denormalized counters and recomputes how many trees they've earned
     * (X visits = 1 tree). Wrapped in a transaction so counters and the visit
     * row never drift apart.
     *
     */
    public function registerVisit(string $externalId, ?Carbon $occurredAt = null): VisitOutcome
    {
        $occurredAt ??= now();

        return DB::transaction(function () use ($externalId, $occurredAt) {
            /** @var Customer $customer */
            $customer = Customer::lockForUpdate()->firstOrCreate(['external_id' => $externalId]);

            return new VisitOutcome(
                customer: $customer,
                treePlanted: $customer->recordVisit($occurredAt, $this->reward),
            );
        });
    }

    /**
     * Visits aggregated per hour, oldest first.
     *
     * @return array<int, array{hour: string, visits: int}>
     */
    public function visitsPerHour(?string $externalId = null): array
    {
        return Visit::hourlyTotals($externalId);
    }
}
