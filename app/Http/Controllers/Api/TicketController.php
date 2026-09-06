<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\TicketFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    /** GET /api/tickets */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = Ticket::query()
            ->visibleTo($request->user())
            ->filter(TicketFilters::fromRequest($request))
            ->with(['category', 'creator', 'assignee'])
            ->latest()
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return TicketResource::collection($tickets);
    }

    /** POST /api/tickets */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->tickets->create($request->validated(), $request->user());

        return (new TicketResource($ticket->load(['category', 'creator', 'assignee'])))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/tickets/{ticket} */
    public function show(Request $request, Ticket $ticket): TicketResource
    {
        $this->authorize('view', $ticket);

        return new TicketResource($ticket->load([
            'category', 'creator', 'assignee',
            'comments.author', 'activities.causer', 'attachments.uploader',
        ]));
    }

    /** PUT /api/tickets/{ticket} */
    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->tickets->update($ticket, $request->validated(), $request->user());

        return new TicketResource($ticket->load(['category', 'creator', 'assignee']));
    }

    /** DELETE /api/tickets/{ticket} */
    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(null, 204);
    }
}
