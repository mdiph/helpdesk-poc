<?php

namespace App\Models;

use App\Enums\SupportTier;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'title',
        'description',
        'category_id',
        'priority',
        'status',
        'support_tier',
        'created_by',
        'assigned_to',
        'resolution',
        'escalated_at',
        'first_responded_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'support_tier' => SupportTier::class,
            'escalated_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Assign a stable, human-readable reference once the row has an id.
        static::created(function (Ticket $ticket): void {
            if (! $ticket->reference) {
                $prefix = config('helpdesk.ticket_reference_prefix', 'TKT');
                $ticket->reference = sprintf('%s-%06d', $prefix, $ticket->id);
                $ticket->saveQuietly();
            }
        });

        // Remove attachment files before the DB cascade drops their rows
        // (model events do not fire for database-level ON DELETE CASCADE).
        static::deleting(function (Ticket $ticket): void {
            $ticket->attachments->each->delete();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    // ---------------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->latest();
    }

    // ---------------------------------------------------------------------
    // Derived state
    // ---------------------------------------------------------------------

    public function isEscalated(): bool
    {
        return $this->support_tier === SupportTier::L2;
    }

    public function isOpenState(): bool
    {
        return $this->status->isOpen();
    }

    /** Resolution time in hours, or null if not resolved yet. */
    public function resolutionHours(): ?float
    {
        if (! $this->resolved_at) {
            return null;
        }

        return round($this->created_at->diffInMinutes($this->resolved_at) / 60, 2);
    }

    // ---------------------------------------------------------------------
    // Query scopes - shared by the web list, the API and reports
    // ---------------------------------------------------------------------

    public function scopeStatus(Builder $query, string|TicketStatus $status): Builder
    {
        return $query->where('status', $status instanceof TicketStatus ? $status->value : $status);
    }

    public function scopeOpenStates(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (TicketStatus $s) => $s->value,
            TicketStatus::openStates()
        ));
    }

    public function scopeWaitingForL2(Builder $query): Builder
    {
        return $query->where('support_tier', SupportTier::L2->value)->openStates();
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Apply the search / filter parameters used by the ticket list, the API
     * index endpoint and the report/export builders. Unknown keys are ignored.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.str_replace('%', '\\%', $term).'%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('reference', 'ilike', $like)
                        ->orWhere('title', 'ilike', $like)
                        ->orWhere('description', 'ilike', $like);
                });
            })
            ->when($filters['reference'] ?? null, fn (Builder $q, string $ref) => $q->where('reference', 'ilike', '%'.$ref.'%'))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->whereIn('status', (array) $v))
            ->when($filters['priority'] ?? null, fn (Builder $q, $v) => $q->whereIn('priority', (array) $v))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $v) => $q->whereIn('category_id', (array) $v))
            ->when($filters['support_tier'] ?? null, fn (Builder $q, $v) => $q->where('support_tier', $v))
            ->when(($filters['assigned_to'] ?? null) === 'unassigned', fn (Builder $q) => $q->whereNull('assigned_to'))
            ->when(
                ($filters['assigned_to'] ?? null) && $filters['assigned_to'] !== 'unassigned',
                fn (Builder $q) => $q->whereIn('assigned_to', (array) $filters['assigned_to'])
            )
            ->when($filters['created_by'] ?? null, fn (Builder $q, $v) => $q->whereIn('created_by', (array) $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v));
    }

    /**
     * Restrict the query to tickets the given user is allowed to see.
     * Enforced in addition to the TicketPolicy so list endpoints never leak.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isViewer()) {
            return $query; // full read access
        }

        if ($user->isL2()) {
            // L2 sees escalated tickets plus anything assigned to them.
            return $query->where(function (Builder $q) use ($user) {
                $q->where('support_tier', SupportTier::L2->value)
                    ->orWhere('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        // L1 sees the L1 queue plus tickets they created or own.
        return $query->where(function (Builder $q) use ($user) {
            $q->where('support_tier', SupportTier::L1->value)
                ->orWhere('assigned_to', $user->id)
                ->orWhere('created_by', $user->id);
        });
    }
}
