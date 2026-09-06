<?php

namespace Tests\Unit;

use App\Enums\TicketStatus;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function test_open_states_do_not_include_closed_or_resolved(): void
    {
        $open = TicketStatus::openStates();

        $this->assertContains(TicketStatus::Open, $open);
        $this->assertContains(TicketStatus::InProgress, $open);
        $this->assertContains(TicketStatus::Pending, $open);
        $this->assertNotContains(TicketStatus::Resolved, $open);
        $this->assertNotContains(TicketStatus::Closed, $open);
    }

    public function test_is_open_helper(): void
    {
        $this->assertTrue(TicketStatus::Open->isOpen());
        $this->assertFalse(TicketStatus::Closed->isOpen());
    }
}
