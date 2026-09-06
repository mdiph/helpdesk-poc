@extends('layouts.app')
@section('title', 'Reports')
@section('heading', 'Reports')

@section('content')
    @php $r = $report; @endphp

    <form method="GET" action="{{ route('reports.index') }}" class="card mb-6 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="label">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? $r['range']['from'] }}" class="input">
            </div>
            <div>
                <label class="label">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? $r['range']['to'] }}" class="input">
            </div>
            <div>
                <label class="label">Tier</label>
                <select name="support_tier" class="input">
                    <option value="">L1 &amp; L2</option>
                    <option value="l1" @selected(($filters['support_tier'] ?? '') === 'l1')>L1 only</option>
                    <option value="l2" @selected(($filters['support_tier'] ?? '') === 'l2')>L2 only</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="btn-primary">Generate</button>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500">Export:</span>
            <a href="{{ route('reports.index', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn-secondary">CSV</a>
            <a href="{{ route('reports.index', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn-secondary">Excel</a>
            <a href="{{ route('reports.index', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn-secondary">PDF</a>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="card p-4"><p class="text-sm text-gray-500">Total tickets</p><p class="mt-1 text-2xl font-semibold">{{ number_format($r['total']) }}</p></div>
        <div class="card p-4"><p class="text-sm text-gray-500">Resolved in range</p><p class="mt-1 text-2xl font-semibold">{{ number_format($r['resolution']['resolved_count']) }}</p></div>
        <div class="card p-4"><p class="text-sm text-gray-500">Avg resolution</p><p class="mt-1 text-2xl font-semibold">{{ $r['resolution']['avg_hours'] ?? '—' }}<span class="text-base font-normal text-gray-400"> h</span></p></div>
        <div class="card p-4"><p class="text-sm text-gray-500">Median resolution</p><p class="mt-1 text-2xl font-semibold">{{ $r['resolution']['median_hours'] ?? '—' }}<span class="text-base font-normal text-gray-400"> h</span></p></div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">Created vs resolved</h3>
            <div class="h-64">
                <canvas data-chart="line" data-chart-data="{{ json_encode([
                    'labels' => collect($r['created_vs_resolved'])->pluck('date'),
                    'datasets' => [
                        ['label' => 'Created', 'data' => collect($r['created_vs_resolved'])->pluck('created')],
                        ['label' => 'Resolved', 'data' => collect($r['created_vs_resolved'])->pluck('resolved')],
                    ],
                ]) }}"></canvas>
            </div>
        </div>
        <div class="card p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">By status</h3>
            <div class="h-64">
                <canvas data-chart="doughnut" data-chart-data="{{ json_encode([
                    'labels' => collect($r['by_status'])->pluck('label'),
                    'data' => collect($r['by_status'])->pluck('count'),
                ]) }}"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        @foreach ([
            'By priority' => $r['by_priority'],
            'By category' => $r['by_category'],
            'By tier (L1 vs L2)' => $r['by_tier'],
        ] as $title => $rows)
            <div class="card">
                <div class="border-b border-gray-200 px-4 py-3"><h3 class="text-sm font-semibold text-gray-700">{{ $title }}</h3></div>
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="table-td">{{ $row['label'] }}</td>
                                <td class="table-td text-right font-medium">{{ number_format($row['count']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <div class="card mt-6">
        <div class="border-b border-gray-200 px-4 py-3"><h3 class="text-sm font-semibold text-gray-700">By assigned user / team</h3></div>
        <table class="min-w-full divide-y divide-gray-100">
            <tbody>
                @foreach ($r['by_assignee'] as $row)
                    <tr>
                        <td class="table-td">{{ $row['label'] }}</td>
                        <td class="table-td text-right font-medium">{{ number_format($row['count']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
