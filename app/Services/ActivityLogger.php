<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Writes entries to a ticket's append-only activity log. Every method
 * returns the created TicketActivity so callers can chain if needed.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(Ticket $ticket, string $event, string $description, array $properties = [], ?User $causer = null): TicketActivity
    {
        return $ticket->activities()->create([
            'user_id' => ($causer ?? Auth::user())?->id,
            'event' => $event,
            'description' => $description,
            'properties' => $properties ?: null,
            'created_at' => now(),
        ]);
    }

    public function created(Ticket $ticket): TicketActivity
    {
        return $this->log($ticket, 'created', 'Ticket created');
    }

    public function statusChanged(Ticket $ticket, string $from, string $to): TicketActivity
    {
        return $this->log($ticket, 'status_changed', "Status changed from {$from} to {$to}", compact('from', 'to'));
    }

    public function assigned(Ticket $ticket, ?string $assigneeName): TicketActivity
    {
        $text = $assigneeName ? "Assigned to {$assigneeName}" : 'Unassigned';

        return $this->log($ticket, 'assigned', $text, ['assignee' => $assigneeName]);
    }

    public function escalated(Ticket $ticket, ?string $note): TicketActivity
    {
        return $this->log($ticket, 'escalated', 'Escalated to L2'.($note ? ': '.$note : ''), ['note' => $note]);
    }

    public function commented(Ticket $ticket, bool $internal): TicketActivity
    {
        return $this->log($ticket, 'commented', $internal ? 'Internal note added' : 'Comment added', ['internal' => $internal]);
    }

    public function attachmentAdded(Ticket $ticket, string $filename): TicketActivity
    {
        return $this->log($ticket, 'attachment_added', "Attachment added: {$filename}", ['filename' => $filename]);
    }

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  field => [old, new]
     */
    public function updated(Ticket $ticket, array $changes): TicketActivity
    {
        $fields = implode(', ', array_keys($changes));

        return $this->log($ticket, 'updated', "Updated {$fields}", ['changes' => $changes]);
    }
}
