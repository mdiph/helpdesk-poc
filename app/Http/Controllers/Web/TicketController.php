<?php

namespace App\Http\Controllers\Web;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use App\Support\TicketFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $filters = TicketFilters::fromRequest($request);

        $tickets = Ticket::query()
            ->visibleTo($request->user())
            ->filter($filters)
            ->with(['category', 'creator', 'assignee'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $request->query(),
            'options' => $this->filterOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Ticket::class);

        return view('tickets.create', [
            'categories' => Category::active()->orderBy('name')->get(),
            'priorities' => TicketPriority::options(),
            'assignees' => User::assignable()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->tickets->create($request->validated(), $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Ticket {$ticket->reference} created.");
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'category', 'creator', 'assignee',
            'comments.author',
            'activities.causer',
            'attachments.uploader',
        ]);

        $comments = $request->user()->isViewer()
            ? $ticket->comments->where('is_internal', false)
            : $ticket->comments;

        return view('tickets.show', [
            'ticket' => $ticket,
            'comments' => $comments,
            'statuses' => TicketStatus::options(),
            'assignees' => User::assignable()->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Ticket $ticket): View
    {
        $this->authorize('update', $ticket);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'categories' => Category::orderBy('name')->get(),
            'priorities' => TicketPriority::options(),
            'statuses' => TicketStatus::options(),
            'assignees' => User::assignable()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->tickets->update($ticket, $request->validated(), $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket updated.');
    }

    public function destroy(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $reference = $ticket->reference;
        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('status', "Ticket {$reference} deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'assignees' => User::assignable()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
