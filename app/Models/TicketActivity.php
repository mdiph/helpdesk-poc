<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail for a ticket. Written by ActivityLogger; never
 * updated or deleted. `properties` holds structured before/after values.
 */
class TicketActivity extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // rows are immutable

    protected $fillable = ['ticket_id', 'user_id', 'event', 'description', 'properties'];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
