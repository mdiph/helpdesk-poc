<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Attachment
 */
class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->humanSize(),
            'uploaded_by' => $this->whenLoaded('uploader', fn () => $this->uploader?->only('id', 'name')),
            'download_url' => route('tickets.attachments.show', [$this->ticket_id, $this->id]),
            'created_at' => $this->created_at,
        ];
    }
}
