<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_login_returns_a_token(): void
    {
        $user = User::factory()->l1()->create();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);
    }

    public function test_tickets_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
    }

    public function test_agent_can_create_ticket_via_api(): void
    {
        Sanctum::actingAs(User::factory()->l1()->create());

        $this->postJson('/api/tickets', [
            'title' => 'API ticket',
            'description' => 'created through the API',
            'priority' => 'medium',
        ])->assertCreated()->assertJsonPath('data.title', 'API ticket');
    }

    public function test_viewer_cannot_create_ticket_via_api(): void
    {
        Sanctum::actingAs(User::factory()->viewer()->create());

        $this->postJson('/api/tickets', [
            'title' => 'nope', 'description' => 'nope', 'priority' => 'low',
        ])->assertForbidden();
    }

    public function test_non_admin_cannot_list_users_via_api(): void
    {
        Sanctum::actingAs(User::factory()->l2()->create());

        $this->getJson('/api/users')->assertForbidden();
    }
}
