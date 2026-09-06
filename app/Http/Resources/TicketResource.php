<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Ticket
 */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAgent = $request->user()?->isAgent() ?? false;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => $this->category?->only('id', 'name')),
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'support_tier' => $this->support_tier->value,
            'is_escalated' => $this->isEscalated(),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->only('id', 'name')),
            'assigned_to' => $this->whenLoaded('assignee', fn () => $this->assignee?->only('id', 'name')),
            'resolution' => $this->resolution,
            'resolution_hours' => $this->resolutionHours(),
            'escalated_at' => $this->escalated_at,
            'first_responded_at' => $this->first_responded_at,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'comments' => $this->whenLoaded('comments', fn () => CommentResource::collection(
                $isAgent ? $this->comments : $this->comments->where('is_internal', false)->values()
            )),
            'activities' => $this->whenLoaded('activities', fn () => ActivityResource::collection($this->activities)),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
        ];
    }
}
