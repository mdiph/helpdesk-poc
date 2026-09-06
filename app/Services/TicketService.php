<?php

namespace App\Services;

use App\Enums\SupportTier;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Single home for ticket write operations. Both the web controllers and the
 * API controllers call this class so lifecycle rules and the activity log
 * stay consistent regardless of entry point.
 */
class TicketService
{
    public function __construct(private readonly ActivityLogger $log)
    {
    }

    /**
     * @param  array{title: string, description: string, category_id?: int|null, priority: string}  $data
     */
    public function create(array $data, User $creator): Ticket
    {
        return DB::transaction(function () use ($data, $creator) {
            $ticket = Ticket::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'] ?? null,
                'priority' => $data['priority'],
                'status' => TicketStatus::Open,
                'support_tier' => SupportTier::L1,
                'created_by' => $creator->id,
                'assigned_to' => $data['assigned_to'] ?? null,
            ]);

            $this->log->created($ticket);

            if ($ticket->assigned_to) {
                $this->log->assigned($ticket, $ticket->assignee?->name);
            }

            return $ticket->refresh();
        });
    }

    /**
     * Update editable fields. Only keys present in $data are touched.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Ticket $ticket, array $data, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            $editable = ['title', 'description', 'category_id', 'priority', 'resolution'];
            $changes = [];

            foreach ($editable as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }

                $old = $this->stringify($ticket->getAttribute($field));
                $new = $this->stringify($data[$field]);

                if ($old !== $new) {
                    $changes[$field] = [$old, $new];
                    $ticket->setAttribute($field, $data[$field]);
                }
            }

            if ($changes) {
                $ticket->save();
                $this->log->updated($ticket, $changes);
            }

            // Status and assignee changes get their own dedicated log entries.
            if (array_key_exists('status', $data) && $data['status']) {
                $this->applyStatus($ticket, TicketStatus::from($data['status']), $actor, log: true);
                $ticket->save();
            }

            if (array_key_exists('assigned_to', $data)) {
                $this->applyAssignee($ticket, $data['assigned_to'], $actor, log: true);
            }

            $this->markFirstResponse($ticket, $actor);

            return $ticket->refresh();
        });
    }

    public function changeStatus(Ticket $ticket, TicketStatus $status, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $status, $actor) {
            $this->applyStatus($ticket, $status, $actor, log: true);
            $ticket->save();
            $this->markFirstResponse($ticket, $actor);

            return $ticket->refresh();
        });
    }

    public function assign(Ticket $ticket, ?User $assignee, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $assignee, $actor) {
            $this->applyAssignee($ticket, $assignee?->id, $actor, log: true);
            $ticket->save();
            $this->markFirstResponse($ticket, $actor);

            return $ticket->refresh();
        });
    }

    /**
     * Move a ticket from L1 to L2. Optionally assign an L2 agent and leave a note.
     */
    public function escalate(Ticket $ticket, User $actor, ?string $note = null, ?User $assignee = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor, $note, $assignee) {
            $ticket->support_tier = SupportTier::L2;
            $ticket->escalated_at = now();

            // Escalating a brand-new ticket also starts work on it.
            if ($ticket->status === TicketStatus::Open) {
                $this->applyStatus($ticket, TicketStatus::InProgress, $actor, log: true);
            }

            // The previous L1 owner no longer holds it unless a new one is named.
            // (applyAssignee no-ops and stays silent when nothing changes.)
            $this->applyAssignee($ticket, $assignee?->id, $actor, log: true);

            $ticket->save();
            $this->markFirstResponse($ticket, $actor);
            $this->log->escalated($ticket, $note);

            return $ticket->refresh();
        });
    }

    public function addComment(Ticket $ticket, User $author, string $body, bool $internal = false): TicketComment
    {
        return DB::transaction(function () use ($ticket, $author, $body, $internal) {
            $comment = $ticket->comments()->create([
                'user_id' => $author->id,
                'body' => $body,
                'is_internal' => $internal,
            ]);

            $ticket->touch();
            $this->markFirstResponse($ticket->refresh(), $author);
            $this->log->commented($ticket, $internal);

            return $comment;
        });
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function applyStatus(Ticket $ticket, TicketStatus $status, User $actor, bool $log): void
    {
        $from = $ticket->status;

        if ($from === $status) {
            return;
        }

        $ticket->status = $status;

        $ticket->resolved_at = match (true) {
            $status === TicketStatus::Resolved => $ticket->resolved_at ?? now(),
            $status->isOpen() => null, // reopened
            default => $ticket->resolved_at,
        };

        $ticket->closed_at = $status === TicketStatus::Closed ? ($ticket->closed_at ?? now()) : null;

        if ($log) {
            $this->log->statusChanged($ticket, $from->label(), $status->label());
        }
    }

    private function applyAssignee(Ticket $ticket, ?int $assigneeId, User $actor, bool $log): void
    {
        if ($ticket->assigned_to === $assigneeId) {
            return;
        }

        $ticket->assigned_to = $assigneeId;

        if ($log) {
            $ticket->save();
            $ticket->load('assignee'); // relation may be stale after the id change
            $this->log->assigned($ticket, $ticket->assignee?->name);
        }
    }

    private function markFirstResponse(Ticket $ticket, User $actor): void
    {
        if (! $ticket->first_responded_at && $actor->id !== $ticket->created_by && $actor->isAgent()) {
            $ticket->forceFill(['first_responded_at' => now()])->save();
        }
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }
}
