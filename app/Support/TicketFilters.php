<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Normalises the search / filter query parameters shared by the ticket list,
 * the exports and the API index endpoint into the array shape expected by
 * Ticket::scopeFilter().
 */
class TicketFilters
{
    /**
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request): array
    {
        return array_filter([
            'q' => trim((string) $request->query('q', '')) ?: null,
            'reference' => trim((string) $request->query('reference', '')) ?: null,
            'status' => self::arr($request->query('status')),
            'priority' => self::arr($request->query('priority')),
            'category_id' => self::arr($request->query('category_id')),
            'support_tier' => $request->query('support_tier') ?: null,
            'assigned_to' => $request->query('assigned_to') ?: null,
            'created_by' => self::arr($request->query('created_by')),
            'date_from' => $request->query('date_from') ?: null,
            'date_to' => $request->query('date_to') ?: null,
        ], fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /** Accept both `status=open` and `status[]=open&status[]=pending`. */
    private static function arr(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return array_values(array_filter((array) $value, fn ($v) => $v !== '' && $v !== null));
    }
}
