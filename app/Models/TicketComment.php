<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A note added to a ticket. Internal notes are visible to agents only
 * (not to the Viewer role or, in the API, to non-agent callers).
 */
class TicketComment extends Model
{
    use HasFactory;

    protected $fillable = ['ticket_id', 'user_id', 'body', 'is_internal'];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
