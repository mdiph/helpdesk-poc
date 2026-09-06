<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TicketActivity
 */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'description' => $this->description,
            'properties' => $this->properties,
            'causer' => $this->whenLoaded('causer', fn () => $this->causer?->only('id', 'name')),
            'created_at' => $this->created_at,
        ];
    }
}
