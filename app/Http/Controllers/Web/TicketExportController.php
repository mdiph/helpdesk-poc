<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Exports\TicketExporter;
use App\Support\TicketFilters;
use Illuminate\Http\Request;

class TicketExportController extends Controller
{
    public function __construct(private readonly TicketExporter $exporter)
    {
    }

    /** Export the currently filtered ticket list (csv | xlsx | pdf). */
    public function index(Request $request)
    {
        $this->authorize('export', Ticket::class);

        $request->validate(['format' => ['nullable', 'in:csv,xlsx,pdf']]);

        $query = Ticket::query()
            ->visibleTo($request->user())
            ->filter(TicketFilters::fromRequest($request));

        return $this->exporter->list($query, TicketExporter::formatFromRequest($request));
    }

    /** Export a single ticket, including its activity history, as PDF. */
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        return $this->exporter->single($ticket, 'pdf');
    }
}
