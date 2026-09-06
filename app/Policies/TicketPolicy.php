<?php

namespace App\Policies;

use App\Enums\SupportTier;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

/**
 * Authorization rules for tickets. Every controller (web and API) calls
 * these; the matching query filter is Ticket::scopeVisibleTo().
 *
 * Summary:
 *   Admin   - everything
 *   L1      - owns the L1 queue: create, view, update, assign, escalate
 *   L2      - handles the L2 queue: view, update, assign, resolve
 *   Viewer  - read only
 */
class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // list results are constrained by scopeVisibleTo()
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin() || $user->isViewer()) {
            return true;
        }

        if ($this->isParticipant($user, $ticket)) {
            return true;
        }

        return $user->isL2()
            ? $ticket->support_tier === SupportTier::L2
            : $ticket->support_tier === SupportTier::L1;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isL1();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Closed tickets are frozen for non-admins.
        if ($ticket->status === TicketStatus::Closed) {
            return false;
        }

        return $this->ownsQueue($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $this->ownsQueue($user, $ticket);
    }

    public function escalate(User $user, Ticket $ticket): bool
    {
        if ($ticket->support_tier === SupportTier::L2) {
            return false; // already at L2
        }

        return $user->isAdmin() || $user->isL1();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() && $this->view($user, $ticket);
    }

    public function manageAttachments(User $user, Ticket $ticket): bool
    {
        return $this->update($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function export(User $user): bool
    {
        return true; // any authenticated user, results still filtered per role
    }

    // ------------------------------------------------------------------

    private function isParticipant(User $user, Ticket $ticket): bool
    {
        return $ticket->created_by === $user->id || $ticket->assigned_to === $user->id;
    }

    /** True when the ticket sits in the queue this agent is responsible for. */
    private function ownsQueue(User $user, Ticket $ticket): bool
    {
        if ($user->isL1()) {
            return $ticket->support_tier === SupportTier::L1 || $this->isParticipant($user, $ticket);
        }

        if ($user->isL2()) {
            return $ticket->support_tier === SupportTier::L2 || $this->isParticipant($user, $ticket);
        }

        return false;
    }
}
