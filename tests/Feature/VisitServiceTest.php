<?php

namespace Tests\Feature;

use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VisitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trees_accrue_one_per_x_visits(): void
    {
        config(['trees.visits_per_tree' => 2]);
        $service = app(VisitService::class);

        $expectedTrees = [0, 1, 1, 2, 2, 3]; // floor(n / 2) for n = 1..6
        foreach ($expectedTrees as $i => $expected) {
            $outcome = $service->registerVisit('cust-1');
            $this->assertSame($expected, $outcome->customer->trees_planted, "after visit ".($i + 1));
        }
    }

    public function test_register_visit_defaults_occurred_at_to_now(): void
    {
        Carbon::setTestNow('2026-06-09 12:00:00');
        $service = app(VisitService::class);

        $outcome = $service->registerVisit('cust-1');

        $this->assertEquals('2026-06-09 12:00:00', $outcome->customer->last_visit_at->toDateTimeString());
        Carbon::setTestNow();
    }
}
