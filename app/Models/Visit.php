<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $fillable = [
        'customer_id',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Limit the query to a single customer by its device-provided external id. */
    public function scopeForCustomer(Builder $query, ?string $externalId): Builder
    {
        return $query->when(
            $externalId,
            fn (Builder $q) => $q->whereRelation('customer', 'external_id', $externalId)
        );
    }

    /**
     * Visit counts bucketed per hour, oldest first.
     *
     * @return array<int, array{hour: string, visits: int}>
     */
    public static function hourlyTotals(?string $externalId = null): array
    {
        return static::query()
            ->forCustomer($externalId)
            ->selectRaw("DATE_FORMAT(occurred_at, '%Y-%m-%dT%H:00') as hour, count(*) as visits")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row) => [
                'hour' => $row->hour,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }
}
