<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments)
    {
    }

    public function store(StoreAttachmentRequest $request, Ticket $ticket): RedirectResponse
    {
        foreach ($request->file('files', []) as $file) {
            $this->attachments->store($ticket, $file, $request->user());
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Attachment(s) uploaded.');
    }

    public function show(Request $request, Ticket $ticket, Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, Ticket $ticket, Attachment $attachment): RedirectResponse
    {
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        // Uploader or anyone who can manage the ticket's attachments.
        abort_unless(
            $attachment->uploaded_by === $request->user()->id
                || $request->user()->can('manageAttachments', $ticket),
            403
        );

        $attachment->delete();

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Attachment removed.');
    }
}
