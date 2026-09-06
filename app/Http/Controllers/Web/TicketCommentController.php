<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreCommentRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;

class TicketCommentController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    public function store(StoreCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        // Only agents may flag a note as internal.
        $internal = $request->boolean('is_internal') && $request->user()->isAgent();

        $this->tickets->addComment(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $internal,
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $internal ? 'Internal note added.' : 'Comment added.');
    }
}
