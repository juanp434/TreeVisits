<?php

namespace App\Models;

use App\Domain\TreeReward;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Customer extends Model
{
    protected $fillable = [
        'external_id',
        'visits_count',
        'trees_planted',
        'last_visit_at',
    ];

    protected $casts = [
        'visits_count' => 'integer',
        'trees_planted' => 'integer',
        'last_visit_at' => 'datetime',
    ];

    // Start counters at zero in memory so a freshly created customer doesn't
    // carry null counts before the DB defaults are read back.
    protected $attributes = [
        'visits_count' => 0,
        'trees_planted' => 0,
    ];

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Record one visit and keep the denormalized counters consistent:
     * append the event, bump the count, recompute trees, stamp the last visit.
     * Returns whether this visit crossed an "X visits = 1 tree" boundary.
     */
    public function recordVisit(Carbon $occurredAt, TreeReward $reward): bool
    {
        $this->visits()->create(['occurred_at' => $occurredAt]);

        $before = $this->visits_count;
        $this->visits_count += 1;
        $this->trees_planted = $reward->treesFor($this->visits_count);
        $this->last_visit_at = $occurredAt;
        $this->save();

        return $reward->plantedBetween($before, $this->visits_count);
    }
}
