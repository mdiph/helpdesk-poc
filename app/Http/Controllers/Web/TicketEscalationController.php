<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\EscalateTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;

class TicketEscalationController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    public function store(EscalateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->tickets->escalate(
            $ticket,
            $request->user(),
            $request->validated('note'),
            $request->resolvedAssignee(),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Ticket {$ticket->reference} escalated to L2.");
    }
}
