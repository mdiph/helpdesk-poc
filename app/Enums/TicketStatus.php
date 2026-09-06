<?php

namespace App\Enums;

/**
 * Ticket lifecycle: Open -> In Progress -> Pending -> Resolved -> Closed.
 * Transitions are not strictly linear (a ticket can be reopened), but the
 * TicketService records every change in the activity log.
 */
enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Pending => 'Pending',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    /** Statuses that count as "still needs work". */
    public static function openStates(): array
    {
        return [self::Open, self::InProgress, self::Pending];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::openStates(), true);
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
