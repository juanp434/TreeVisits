<?php

namespace App\Domain;

use App\Models\Customer;

/**
 * Result of registering a visit: the updated customer plus whether this
 * visit crossed an "X visits = 1 tree" boundary.
 */
final readonly class VisitOutcome
{
    public function __construct(
        public Customer $customer,
        public bool $treePlanted,
    ) {
    }
}
