<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; }
        h1 { font-size: 17px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 16px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        .meta { color: #6b7280; margin-bottom: 10px; }
        table { width: 60%; border-collapse: collapse; }
        td, th { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; }
        .cards td { border: none; padding: 2px 8px 2px 0; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Report</h1>
    <div class="meta">
        Range {{ $report['range']['from'] }} to {{ $report['range']['to'] }} · Generated {{ $generatedAt->format('Y-m-d H:i') }}
    </div>

    <table class="cards">
        <tr><td>Total tickets</td><td><strong>{{ $report['total'] }}</strong></td></tr>
        <tr><td>Resolved in range</td><td><strong>{{ $report['resolution']['resolved_count'] }}</strong></td></tr>
        <tr><td>Average resolution (hours)</td><td><strong>{{ $report['resolution']['avg_hours'] ?? 'n/a' }}</strong></td></tr>
        <tr><td>Median resolution (hours)</td><td><strong>{{ $report['resolution']['median_hours'] ?? 'n/a' }}</strong></td></tr>
    </table>

    @foreach ([
        'By status' => $report['by_status'],
        'By priority' => $report['by_priority'],
        'By tier (L1 vs L2)' => $report['by_tier'],
        'By category' => $report['by_category'],
        'By assigned user / team' => $report['by_assignee'],
    ] as $title => $rows)
        <h2>{{ $title }}</h2>
        <table>
            <thead><tr><th>Label</th><th>Count</th></tr></thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <h2>Created vs resolved (daily)</h2>
    <table>
        <thead><tr><th>Date</th><th>Created</th><th>Resolved</th></tr></thead>
        <tbody>
            @foreach ($report['created_vs_resolved'] as $row)
                <tr><td>{{ $row['date'] }}</td><td>{{ $row['created'] }}</td><td>{{ $row['resolved'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
