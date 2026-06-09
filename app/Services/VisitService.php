<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisitService
{
    /**
     * Register a visit event for a customer.
     *
     * Creates the customer on first sight, records the visit event, updates
     * the denormalized counters and recomputes how many trees they've earned
     * (X visits = 1 tree). Wrapped in a transaction so counters and the visit
     * row never drift apart.
     *
     * @return array{customer: Customer, tree_planted: bool}
     */
    public function registerVisit(string $externalId, ?Carbon $occurredAt = null): array
    {
        $occurredAt ??= now();

        return DB::transaction(function () use ($externalId, $occurredAt) {
            /** @var Customer $customer */
            $customer = Customer::lockForUpdate()->firstOrCreate(
                ['external_id' => $externalId]
            );

            $customer->visits()->create(['occurred_at' => $occurredAt]);

            $treesBefore = $customer->trees_planted;

            $customer->visits_count += 1;
            $customer->trees_planted = intdiv($customer->visits_count, $this->visitsPerTree());
            $customer->last_visit_at = $occurredAt;
            $customer->save();

            return [
                'customer' => $customer,
                'tree_planted' => $customer->trees_planted > $treesBefore,
            ];
        });
    }

    /**
     * Visits aggregated per hour, oldest first.
     *
     * @return array<int, array{hour: string, visits: int}>
     */
    public function visitsPerHour(?string $externalId = null): array
    {
        return Visit::query()
            ->when($externalId, fn ($q) => $q->whereRelation('customer', 'external_id', $externalId))
            ->selectRaw("strftime('%Y-%m-%dT%H:00', occurred_at) as hour, count(*) as visits")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row) => [
                'hour' => $row->hour,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    public function visitsPerTree(): int
    {
        return max(1, (int) config('trees.visits_per_tree'));
    }
}
