<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AttachmentService
{
    public function __construct(private readonly ActivityLogger $log)
    {
    }

    /**
     * Store an uploaded file for a ticket. The stored filename is randomised;
     * the original name is kept in the database for display and download.
     */
    public function store(Ticket $ticket, UploadedFile $file, User $uploader): Attachment
    {
        $disk = 'attachments';
        $directory = 'tickets/'.$ticket->id;
        $storedName = Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin');

        $path = $file->storeAs($directory, $storedName, ['disk' => $disk]);

        $attachment = $ticket->attachments()->create([
            'uploaded_by' => $uploader->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'created_at' => now(),
        ]);

        $ticket->touch();
        $this->log->attachmentAdded($ticket, $attachment->original_name);

        return $attachment;
    }
}
