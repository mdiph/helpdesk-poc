<?php

namespace Tests\Feature;

use App\Enums\SupportTier;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscalationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_l1_can_escalate_a_ticket_to_l2(): void
    {
        $l1 = User::factory()->l1()->create();
        $l2 = User::factory()->l2()->create();
        $ticket = Ticket::factory()->for($l1, 'creator')->create();

        $this->actingAs($l1)
            ->post(route('tickets.escalate', $ticket), ['note' => 'Needs server access', 'assigned_to' => $l2->id])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame(SupportTier::L2, $ticket->support_tier);
        $this->assertNotNull($ticket->escalated_at);
        $this->assertSame($l2->id, $ticket->assigned_to);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'event' => 'escalated']);
    }

    public function test_escalated_ticket_cannot_be_escalated_again(): void
    {
        $l1 = User::factory()->l1()->create();
        $ticket = Ticket::factory()->for($l1, 'creator')->escalated()->create();

        $this->actingAs($l1)
            ->post(route('tickets.escalate', $ticket))
            ->assertForbidden();
    }

    public function test_resolution_time_is_recorded(): void
    {
        $l1 = User::factory()->l1()->create();
        $agent = User::factory()->l1()->create();
        $ticket = Ticket::factory()->for($l1, 'creator')->create(['created_at' => now()->subHours(5)]);

        app(TicketService::class)->changeStatus($ticket, \App\Enums\TicketStatus::Resolved, $agent);

        $this->assertNotNull($ticket->fresh()->resolved_at);
        $this->assertGreaterThanOrEqual(4, $ticket->fresh()->resolutionHours());
    }
}
