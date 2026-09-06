<?php

namespace App\Exports;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exports a filtered list of tickets to CSV / XLSX / PDF, and a single
 * ticket (with its activity history) to PDF.
 */
class TicketExporter
{
    public const HEADINGS = [
        'Reference', 'Title', 'Category', 'Priority', 'Status', 'Tier',
        'Created By', 'Assigned To', 'Created At', 'Updated At',
        'Escalated At', 'Resolved At', 'Closed At', 'Resolution Hours', 'Resolution',
    ];

    public function __construct(private readonly SpreadsheetExporter $spreadsheet)
    {
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    public function list(Builder $query, string $format): Response
    {
        $query->with(['category', 'creator', 'assignee'])->latest();
        $stamp = now()->format('Ymd_His');

        return match ($format) {
            'csv' => $this->spreadsheet->csv("tickets_{$stamp}.csv", self::HEADINGS, $this->rows($query)),
            'xlsx' => $this->spreadsheet->xlsx("tickets_{$stamp}.xlsx", self::HEADINGS, $this->rows($query)),
            'pdf' => Pdf::loadView('exports.tickets', [
                'tickets' => $query->get(),
                'generatedAt' => now(),
            ])->setPaper('a4', 'landscape')->download("tickets_{$stamp}.pdf"),
            default => abort(400, 'Unsupported export format.'),
        };
    }

    public function single(Ticket $ticket, string $format = 'pdf'): Response
    {
        $ticket->load(['category', 'creator', 'assignee', 'comments.author', 'activities.causer', 'attachments.uploader']);
        $name = str_replace('-', '_', $ticket->reference ?? 'ticket_'.$ticket->id);

        if ($format !== 'pdf') {
            abort(400, 'A single ticket can only be exported as PDF.');
        }

        return Pdf::loadView('exports.ticket', ['ticket' => $ticket, 'generatedAt' => now()])
            ->setPaper('a4')
            ->download("{$name}.pdf");
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return \Generator<int, array<int, string|int|float|null>>
     */
    private function rows(Builder $query): \Generator
    {
        foreach ($query->lazy(500) as $ticket) {
            /** @var Ticket $ticket */
            yield [
                $ticket->reference,
                $ticket->title,
                $ticket->category?->name,
                $ticket->priority->label(),
                $ticket->status->label(),
                $ticket->support_tier->label(),
                $ticket->creator?->name,
                $ticket->assignee?->name ?? 'Unassigned',
                $ticket->created_at?->toDateTimeString(),
                $ticket->updated_at?->toDateTimeString(),
                $ticket->escalated_at?->toDateTimeString(),
                $ticket->resolved_at?->toDateTimeString(),
                $ticket->closed_at?->toDateTimeString(),
                $ticket->resolutionHours(),
                $ticket->resolution,
            ];
        }
    }

    public static function formatFromRequest(Request $request): string
    {
        return strtolower((string) $request->query('format', 'csv'));
    }
}
