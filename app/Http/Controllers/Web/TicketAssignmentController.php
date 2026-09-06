<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\AssignTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;

class TicketAssignmentController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    public function update(AssignTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $assignee = $request->resolvedAssignee();

        $this->tickets->assign($ticket, $assignee, $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $assignee ? "Assigned to {$assignee->name}." : 'Ticket unassigned.');
    }
}
