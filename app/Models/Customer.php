<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
