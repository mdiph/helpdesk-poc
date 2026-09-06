@extends('layouts.app')
@section('title', 'Tickets')
@section('heading', 'Tickets')

@section('actions')
    @can('create', \App\Models\Ticket::class)
        <a href="{{ route('tickets.create') }}" class="btn-primary">New ticket</a>
    @endcan
@endsection

@section('content')
    @php $f = $filters; @endphp

    <form method="GET" action="{{ route('tickets.index') }}" class="card mb-4 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="label">Search</label>
                <input type="text" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Reference, title or text" class="input">
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <option value="">Any</option>
                    @foreach ($options['statuses'] as $value => $label)
                        <option value="{{ $value }}" @selected(($f['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Priority</label>
                <select name="priority" class="input">
                    <option value="">Any</option>
                    @foreach ($options['priorities'] as $value => $label)
                        <option value="{{ $value }}" @selected(($f['priority'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Category</label>
                <select name="category_id" class="input">
                    <option value="">Any</option>
                    @foreach ($options['categories'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($f['category_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Assignee</label>
                <select name="assigned_to" class="input">
                    <option value="">Any</option>
                    <option value="unassigned" @selected(($f['assigned_to'] ?? '') === 'unassigned')>Unassigned</option>
                    @foreach ($options['assignees'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($f['assigned_to'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Team / tier</label>
                <select name="support_tier" class="input">
                    <option value="">Any</option>
                    <option value="l1" @selected(($f['support_tier'] ?? '') === 'l1')>L1</option>
                    <option value="l2" @selected(($f['support_tier'] ?? '') === 'l2')>L2</option>
                </select>
            </div>
            <div>
                <label class="label">Created from</label>
                <input type="date" name="date_from" value="{{ $f['date_from'] ?? '' }}" class="input">
            </div>
            <div>
                <label class="label">Created to</label>
                <input type="date" name="date_to" value="{{ $f['date_to'] ?? '' }}" class="input">
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="submit" class="btn-primary">Apply filters</button>
            <a href="{{ route('tickets.index') }}" class="btn-secondary">Reset</a>
            <span class="mx-2 h-5 w-px bg-gray-200"></span>
            <span class="text-sm text-gray-500">Export:</span>
            <a href="{{ route('tickets.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn-secondary">CSV</a>
            <a href="{{ route('tickets.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn-secondary">Excel</a>
            <a href="{{ route('tickets.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn-secondary">PDF</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Ref</th>
                        <th class="table-th">Title</th>
                        <th class="table-th">Category</th>
                        <th class="table-th">Priority</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Tier</th>
                        <th class="table-th">Assignee</th>
                        <th class="table-th">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tickets as $ticket)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td whitespace-nowrap">
                                <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-brand-700">{{ $ticket->reference }}</a>
                            </td>
                            <td class="table-td max-w-sm truncate">{{ $ticket->title }}</td>
                            <td class="table-td whitespace-nowrap">{{ $ticket->category?->name ?? '—' }}</td>
                            <td class="table-td"><x-priority-badge :priority="$ticket->priority" /></td>
                            <td class="table-td"><x-status-badge :status="$ticket->status" /></td>
                            <td class="table-td"><x-tier-badge :tier="$ticket->support_tier" /></td>
                            <td class="table-td whitespace-nowrap">{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
                            <td class="table-td whitespace-nowrap text-gray-500">{{ $ticket->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">No tickets match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-4 py-3">
            {{ $tickets->links() }}
        </div>
    </div>
@endsection
