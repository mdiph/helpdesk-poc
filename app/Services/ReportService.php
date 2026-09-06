<?php

namespace App\Services;

use App\Enums\SupportTier;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the aggregate figures shown on the Reports page and included in
 * report exports. All counts respect the caller's visibility (scopeVisibleTo)
 * and the selected date range (by ticket creation date).
 */
class ReportService
{
    /**
     * @param  array<string, mixed>  $filters  optional extra ticket filters
     * @return array<string, mixed>
     */
    public function build(User $viewer, Carbon $from, Carbon $to, array $filters = []): array
    {
        $base = fn () => Ticket::query()
            ->visibleTo($viewer)
            ->filter($filters)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $total = $base()->count();

        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'total' => $total,
            'by_status' => $this->countBy($base(), 'status', TicketStatus::options()),
            'by_priority' => $this->countBy($base(), 'priority', TicketPriority::options()),
            'by_category' => $this->countByCategory($base()),
            'by_tier' => $this->countBy($base(), 'support_tier', SupportTier::options()),
            'by_assignee' => $this->countByAssignee($base()),
            'created_vs_resolved' => $this->createdVsResolved($viewer, $from, $to, $filters),
            'resolution' => $this->resolutionStats($base()),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Ticket>  $query
     * @param  array<string, string>  $labels
     * @return array<int, array{key: string, label: string, count: int}>
     */
    private function countBy($query, string $column, array $labels): array
    {
        $counts = $query->groupBy($column)
            ->selectRaw("{$column} as k, count(*) as c")
            ->pluck('c', 'k');

        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = ['key' => $key, 'label' => $label, 'count' => (int) ($counts[$key] ?? 0)];
        }

        return $rows;
    }

    private function countByCategory($query): array
    {
        $counts = $query->groupBy('category_id')
            ->selectRaw('category_id, count(*) as c')
            ->pluck('c', 'category_id');

        $names = \App\Models\Category::whereIn('id', $counts->keys()->filter())->pluck('name', 'id');

        return $counts
            ->map(fn ($c, $id) => [
                'label' => $id ? ($names[$id] ?? 'Category #'.$id) : 'Uncategorised',
                'count' => (int) $c,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    private function countByAssignee($query): array
    {
        $counts = $query->groupBy('assigned_to')
            ->selectRaw('assigned_to, count(*) as c')
            ->pluck('c', 'assigned_to');

        $names = User::whereIn('id', $counts->keys()->filter())->pluck('name', 'id');

        return $counts
            ->map(fn ($c, $id) => [
                'label' => $id ? ($names[$id] ?? 'User #'.$id) : 'Unassigned',
                'count' => (int) $c,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * Daily counts of tickets created vs. tickets resolved within the range.
     *
     * @return array<int, array{date: string, created: int, resolved: int}>
     */
    private function createdVsResolved(User $viewer, Carbon $from, Carbon $to, array $filters): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $created = Ticket::query()->visibleTo($viewer)->filter($filters)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('CAST(created_at AS DATE) as d, count(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $resolved = Ticket::query()->visibleTo($viewer)->filter($filters)
            ->whereBetween('resolved_at', [$start, $end])
            ->selectRaw('CAST(resolved_at AS DATE) as d, count(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $series = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $series[] = [
                'date' => $key,
                'created' => (int) ($created[$key] ?? 0),
                'resolved' => (int) ($resolved[$key] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return array{resolved_count: int, avg_hours: float|null, median_hours: float|null}
     */
    private function resolutionStats($query): array
    {
        $durations = (clone $query)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at'])
            ->map(fn (Ticket $t) => $t->created_at->diffInMinutes($t->resolved_at) / 60)
            ->filter(fn ($h) => $h >= 0)
            ->sort()
            ->values();

        return [
            'resolved_count' => $durations->count(),
            'avg_hours' => $durations->isEmpty() ? null : round($durations->avg(), 1),
            'median_hours' => $durations->isEmpty() ? null : round($this->median($durations), 1),
        ];
    }

    private function median(Collection $sorted): float
    {
        $count = $sorted->count();
        $mid = intdiv($count, 2);

        return $count % 2
            ? (float) $sorted[$mid]
            : (($sorted[$mid - 1] + $sorted[$mid]) / 2);
    }
}
