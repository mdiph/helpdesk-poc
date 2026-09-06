<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Assembles the figures and chart series shown on the dashboard. Everything
 * is scoped to what the given user is allowed to see.
 */
class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $visible = fn () => Ticket::query()->visibleTo($user);

        $statusCounts = $visible()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $priorityCounts = $visible()
            ->openStates()
            ->selectRaw('priority, count(*) as c')
            ->groupBy('priority')
            ->pluck('c', 'priority');

        return [
            'cards' => [
                'total' => (int) $statusCounts->sum(),
                'open' => (int) ($statusCounts[TicketStatus::Open->value] ?? 0),
                'in_progress' => (int) ($statusCounts[TicketStatus::InProgress->value] ?? 0),
                'pending' => (int) ($statusCounts[TicketStatus::Pending->value] ?? 0),
                'resolved' => (int) ($statusCounts[TicketStatus::Resolved->value] ?? 0),
                'closed' => (int) ($statusCounts[TicketStatus::Closed->value] ?? 0),
                'assigned_to_me' => $visible()->assignedTo($user->id)->openStates()->count(),
                'waiting_for_l2' => $visible()->waitingForL2()->count(),
            ],
            'charts' => [
                'by_status' => collect(TicketStatus::cases())->map(fn (TicketStatus $s) => [
                    'label' => $s->label(),
                    'count' => (int) ($statusCounts[$s->value] ?? 0),
                ])->all(),
                'by_priority_open' => collect(TicketPriority::cases())->map(fn (TicketPriority $p) => [
                    'label' => $p->label(),
                    'count' => (int) ($priorityCounts[$p->value] ?? 0),
                ])->all(),
                'created_last_14_days' => $this->createdSeries($user, 14),
            ],
            'recent' => $visible()
                ->with(['category', 'assignee'])
                ->latest()
                ->limit(8)
                ->get(),
            'my_open' => $visible()
                ->assignedTo($user->id)
                ->openStates()
                ->with('category')
                ->orderByRaw($this->priorityOrderSql())
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @return array<int, array{date: string, count: int}>
     */
    private function createdSeries(User $user, int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $counts = Ticket::query()->visibleTo($user)
            ->where('created_at', '>=', $start)
            ->selectRaw('CAST(created_at AS DATE) as d, count(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $series = [];
        for ($day = $start->copy(); $day->lte(Carbon::today()); $day->addDay()) {
            $key = $day->toDateString();
            $series[] = ['date' => $key, 'count' => (int) ($counts[$key] ?? 0)];
        }

        return $series;
    }

    private function priorityOrderSql(): string
    {
        return "case priority
            when 'urgent' then 1
            when 'high' then 2
            when 'medium' then 3
            when 'low' then 4
            else 5 end";
    }
}
