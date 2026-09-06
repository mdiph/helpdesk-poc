<?php

namespace App\Http\Controllers\Web;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\ChangeStatusRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;

class TicketStatusController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    public function update(ChangeStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        $status = TicketStatus::from($request->validated('status'));

        // Allow a resolution note to be supplied together with the status change.
        if ($request->filled('resolution')) {
            $this->tickets->update($ticket, ['resolution' => $request->validated('resolution')], $request->user());
        }

        $this->tickets->changeStatus($ticket, $status, $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Status changed to {$status->label()}.");
    }
}
