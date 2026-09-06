<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #6b7280; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Ticket export</h1>
    <div class="meta">Generated {{ $generatedAt->format('Y-m-d H:i') }} · {{ $tickets->count() }} tickets</div>

    <table>
        <thead>
            <tr>
                <th>Ref</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th>
                <th>Tier</th><th>Created by</th><th>Assignee</th><th>Created</th><th>Resolved</th><th>Res. hrs</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->reference }}</td>
                    <td>{{ $ticket->title }}</td>
                    <td>{{ $ticket->category?->name ?? '—' }}</td>
                    <td>{{ $ticket->priority->label() }}</td>
                    <td>{{ $ticket->status->label() }}</td>
                    <td>{{ $ticket->support_tier->label() }}</td>
                    <td>{{ $ticket->creator?->name }}</td>
                    <td>{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
                    <td>{{ $ticket->created_at->format('Y-m-d') }}</td>
                    <td>{{ $ticket->resolved_at?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $ticket->resolutionHours() ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
