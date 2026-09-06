<?php

namespace Tests\Feature;

use App\Enums\SupportTier;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_viewer_cannot_create_tickets(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->get(route('tickets.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('tickets.store'), [
            'title' => 'x', 'description' => 'y', 'priority' => 'low',
        ])->assertForbidden();
    }

    public function test_l1_can_create_ticket_and_it_starts_in_l1_queue(): void
    {
        $l1 = User::factory()->l1()->create();

        $response = $this->actingAs($l1)->post(route('tickets.store'), [
            'title' => 'Printer broken',
            'description' => 'It is on fire',
            'priority' => 'high',
        ]);

        $ticket = Ticket::first();
        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(SupportTier::L1, $ticket->support_tier);
        $this->assertNotNull($ticket->reference);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'event' => 'created']);
    }

    public function test_l2_cannot_see_unescalated_l1_ticket(): void
    {
        $l1 = User::factory()->l1()->create();
        $l2 = User::factory()->l2()->create();
        $ticket = Ticket::factory()->for($l1, 'creator')->create();

        $this->actingAs($l2)->get(route('tickets.show', $ticket))->assertForbidden();
    }

    public function test_l2_can_see_escalated_ticket(): void
    {
        $l1 = User::factory()->l1()->create();
        $l2 = User::factory()->l2()->create();
        $ticket = Ticket::factory()->for($l1, 'creator')->escalated()->create();

        $this->actingAs($l2)->get(route('tickets.show', $ticket))->assertOk();
    }
}
