@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    @php
        $cards = $data['cards'];
        $tiles = [
            ['label' => 'Total tickets', 'value' => $cards['total'], 'href' => route('tickets.index')],
            ['label' => 'Open', 'value' => $cards['open'], 'href' => route('tickets.index', ['status' => 'open'])],
            ['label' => 'In progress', 'value' => $cards['in_progress'], 'href' => route('tickets.index', ['status' => 'in_progress'])],
            ['label' => 'Pending', 'value' => $cards['pending'], 'href' => route('tickets.index', ['status' => 'pending'])],
            ['label' => 'Resolved', 'value' => $cards['resolved'], 'href' => route('tickets.index', ['status' => 'resolved'])],
            ['label' => 'Closed', 'value' => $cards['closed'], 'href' => route('tickets.index', ['status' => 'closed'])],
            ['label' => 'Assigned to me', 'value' => $cards['assigned_to_me'], 'href' => route('tickets.index', ['assigned_to' => auth()->id()])],
            ['label' => 'Waiting for L2', 'value' => $cards['waiting_for_l2'], 'href' => route('tickets.index', ['support_tier' => 'l2'])],
        ];
    @endphp

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ($tiles as $tile)
            <a href="{{ $tile['href'] }}" class="card p-4 hover:border-brand-300">
                <p class="text-sm text-gray-500">{{ $tile['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($tile['value']) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card p-4 lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-gray-700">Tickets created (last 14 days)</h2>
            <div class="h-64">
                <canvas data-chart="line" data-chart-data="{{ json_encode([
                    'labels' => collect($data['charts']['created_last_14_days'])->pluck('date'),
                    'datasets' => [['label' => 'Created', 'data' => collect($data['charts']['created_last_14_days'])->pluck('count')]],
                ]) }}"></canvas>
            </div>
        </div>
        <div class="card p-4">
            <h2 class="mb-3 text-sm font-semibold text-gray-700">By status</h2>
            <div class="h-64">
                <canvas data-chart="doughnut" data-chart-data="{{ json_encode([
                    'labels' => collect($data['charts']['by_status'])->pluck('label'),
                    'data' => collect($data['charts']['by_status'])->pluck('count'),
                ]) }}"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card p-4">
            <h2 class="mb-3 text-sm font-semibold text-gray-700">Open tickets by priority</h2>
            <div class="h-56">
                <canvas data-chart="bar" data-chart-data="{{ json_encode([
                    'labels' => collect($data['charts']['by_priority_open'])->pluck('label'),
                    'data' => collect($data['charts']['by_priority_open'])->pluck('count'),
                ]) }}"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="border-b border-gray-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">My open tickets</h2>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($data['my_open'] as $ticket)
                    <li class="flex items-center gap-3 px-4 py-3 text-sm">
                        <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-brand-700">{{ $ticket->reference }}</a>
                        <span class="flex-1 truncate text-gray-600">{{ $ticket->title }}</span>
                        <x-priority-badge :priority="$ticket->priority" />
                        <x-status-badge :status="$ticket->status" />
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-gray-400">Nothing assigned to you.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="card mt-6">
        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-700">Recent tickets</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Ref</th>
                        <th class="table-th">Title</th>
                        <th class="table-th">Category</th>
                        <th class="table-th">Priority</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Assignee</th>
                        <th class="table-th">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($data['recent'] as $ticket)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td"><a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-brand-700">{{ $ticket->reference }}</a></td>
                            <td class="table-td max-w-xs truncate">{{ $ticket->title }}</td>
                            <td class="table-td">{{ $ticket->category?->name ?? '—' }}</td>
                            <td class="table-td"><x-priority-badge :priority="$ticket->priority" /></td>
                            <td class="table-td"><x-status-badge :status="$ticket->status" /></td>
                            <td class="table-td">{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
                            <td class="table-td whitespace-nowrap text-gray-500">{{ $ticket->updated_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
