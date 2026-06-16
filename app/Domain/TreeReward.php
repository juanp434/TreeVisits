<?php

namespace App\Domain;

use InvalidArgumentException;

/**
 * The "X visits = 1 tree" rule, as a framework-free value object.
 *
 * Pure domain logic: no Eloquent, no config, no database. It only knows how
 * many trees a visit count earns, so it can be unit-tested in isolation.
 */
final class TreeReward
{
    public function __construct(private readonly int $visitsPerTree)
    {
        if ($visitsPerTree < 1) {
            throw new InvalidArgumentException('visitsPerTree must be >= 1.');
        }
    }

    /**
     * How many trees a customer with this many visits has earned.
     */
    public function treesFor(int $visits): int
    {
        return intdiv($visits, $this->visitsPerTree);
    }

    /**
     * Whether crossing from $before to $after visits earned a new tree
     * (i.e. this visit is the one that planted it).
     */
    public function plantedBetween(int $before, int $after): bool
    {
        return $this->treesFor($after) > $this->treesFor($before);
    }
}
