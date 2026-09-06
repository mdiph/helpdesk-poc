<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\AssignTicketRequest;
use App\Http\Requests\Tickets\ChangeStatusRequest;
use App\Http\Requests\Tickets\EscalateTicketRequest;
use App\Http\Requests\Tickets\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;

/**
 * Workflow endpoints for a single ticket: comment, escalate, assign, status.
 * Each mirrors the equivalent web controller and shares TicketService.
 */
class TicketActionController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    /** POST /api/tickets/{ticket}/comments */
    public function comment(StoreCommentRequest $request, Ticket $ticket): JsonResponse
    {
        $internal = $request->boolean('is_internal') && $request->user()->isAgent();

        $comment = $this->tickets->addComment(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $internal,
        );

        return (new CommentResource($comment->load('author')))->response()->setStatusCode(201);
    }

    /** POST /api/tickets/{ticket}/escalate */
    public function escalate(EscalateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->tickets->escalate(
            $ticket,
            $request->user(),
            $request->validated('note'),
            $request->resolvedAssignee(),
        );

        return new TicketResource($ticket->load(['category', 'creator', 'assignee']));
    }

    /** POST /api/tickets/{ticket}/assign */
    public function assign(AssignTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->tickets->assign($ticket, $request->resolvedAssignee(), $request->user());

        return new TicketResource($ticket->load(['category', 'creator', 'assignee']));
    }

    /** POST /api/tickets/{ticket}/status */
    public function status(ChangeStatusRequest $request, Ticket $ticket): TicketResource
    {
        if ($request->filled('resolution')) {
            $this->tickets->update($ticket, ['resolution' => $request->validated('resolution')], $request->user());
        }

        $ticket = $this->tickets->changeStatus(
            $ticket,
            TicketStatus::from($request->validated('status')),
            $request->user(),
        );

        return new TicketResource($ticket->load(['category', 'creator', 'assignee']));
    }
}
