<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visit_event_creates_the_customer_and_counts_one_visit(): void
    {
        $response = $this->postJson('/api/visits', ['customer_id' => 'cust-1']);

        $response->assertCreated()
            ->assertJsonPath('data.customer_id', 'cust-1')
            ->assertJsonPath('data.visits_count', 1)
            ->assertJsonPath('data.trees_planted', 0)
            ->assertJsonPath('tree_planted', false);

        $customer = Customer::firstWhere('external_id', 'cust-1');
        $this->assertNotNull($customer->last_visit_at);
        $this->assertSame(1, $customer->visits()->count());
    }

    public function test_a_tree_is_planted_after_the_configured_number_of_visits(): void
    {
        config(['trees.visits_per_tree' => 3]);

        // Visits 1 and 2 plant nothing.
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/visits', ['customer_id' => 'cust-1'])
                ->assertJsonPath('tree_planted', false);
        }

        // The 3rd visit plants the first tree.
        $this->postJson('/api/visits', ['customer_id' => 'cust-1'])
            ->assertJsonPath('data.visits_count', 3)
            ->assertJsonPath('data.trees_planted', 1)
            ->assertJsonPath('tree_planted', true);
    }

    public function test_visits_are_tracked_independently_per_customer(): void
    {
        $this->postJson('/api/visits', ['customer_id' => 'a']);
        $this->postJson('/api/visits', ['customer_id' => 'a']);
        $this->postJson('/api/visits', ['customer_id' => 'b']);

        $this->getJson('/api/customers/a')->assertJsonPath('data.visits_count', 2);
        $this->getJson('/api/customers/b')->assertJsonPath('data.visits_count', 1);
    }

    public function test_it_accepts_a_custom_occurred_at_timestamp(): void
    {
        $this->postJson('/api/visits', [
            'customer_id' => 'cust-1',
            'occurred_at' => '2026-01-01T09:30:00Z',
        ])->assertCreated();

        $this->assertDatabaseHas('visits', [
            'occurred_at' => '2026-01-01 09:30:00',
        ]);
    }

    public function test_it_validates_the_request(): void
    {
        $this->postJson('/api/visits', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_id');

        $this->postJson('/api/visits', ['customer_id' => 'x', 'occurred_at' => 'not-a-date'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('occurred_at');
    }

    public function test_hourly_endpoint_aggregates_visits_per_hour(): void
    {
        foreach (['2026-01-01T09:05:00Z', '2026-01-01T09:45:00Z', '2026-01-01T11:00:00Z'] as $ts) {
            $this->postJson('/api/visits', ['customer_id' => 'cust-1', 'occurred_at' => $ts]);
        }

        $this->getJson('/api/visits/hourly')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['hour' => '2026-01-01T09:00', 'visits' => 2],
                ['hour' => '2026-01-01T11:00', 'visits' => 1],
            ]]);
    }

    public function test_showing_an_unknown_customer_returns_404(): void
    {
        $this->getJson('/api/customers/nope')->assertNotFound();
    }
}
