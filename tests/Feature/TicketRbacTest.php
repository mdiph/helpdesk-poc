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

    public function test_every_agent_can_view_any_ticket(): void
    {
        $l1 = User::factory()->l1()->create();
        $l2 = User::factory()->l2()->create();
        $viewer = User::factory()->viewer()->create();
        $ticket = Ticket::factory()->for($l1, 'creator')->create();      // L1 queue
        $escalated = Ticket::factory()->for($l1, 'creator')->escalated()->create();

        foreach ([$l1, $l2, $viewer] as $user) {
            $this->actingAs($user)->get(route('tickets.show', $ticket))->assertOk();
            $this->actingAs($user)->get(route('tickets.show', $escalated))->assertOk();
        }
    }

    public function test_l1_keeps_visibility_after_escalation_but_cannot_edit(): void
    {
        $l1 = User::factory()->l1()->create();
        $editor = User::factory()->l1()->create();
        $ticket = Ticket::factory()->for($l1, 'creator')->escalated()->create();

        // Another L1 (not the creator/assignee) can read it...
        $this->actingAs($editor)->get(route('tickets.show', $ticket))->assertOk();
        // ...but the L2 queue is not theirs to change.
        $this->assertFalse($editor->can('update', $ticket));
    }

    public function test_only_admin_can_delete_a_ticket(): void
    {
        $l1 = User::factory()->l1()->create();
        $l2 = User::factory()->l2()->create();
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->for($l1, 'creator')->create();

        $this->assertFalse($l1->can('delete', $ticket));
        $this->assertFalse($l2->can('delete', $ticket));
        $this->assertTrue($admin->can('delete', $ticket));
    }
}
