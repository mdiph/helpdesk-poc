<?php

namespace App\Enums;

/**
 * Which support team currently owns a ticket. Tickets start at L1 and are
 * moved to L2 by the escalation workflow.
 */
enum SupportTier: string
{
    case L1 = 'l1';
    case L2 = 'l2';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
