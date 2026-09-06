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
        table.kv { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.kv td { padding: 3px 4px; vertical-align: top; }
        table.kv td.k { color: #6b7280; width: 130px; }
        .box { border: 1px solid #d1d5db; padding: 6px 8px; margin-top: 4px; white-space: pre-line; }
        .entry { border-bottom: 1px solid #e5e7eb; padding: 4px 0; }
        .muted { color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $ticket->reference }} — {{ $ticket->title }}</h1>
    <div class="meta">Generated {{ $generatedAt->format('Y-m-d H:i') }}</div>

    <table class="kv">
        <tr><td class="k">Status</td><td>{{ $ticket->status->label() }}</td><td class="k">Priority</td><td>{{ $ticket->priority->label() }}</td></tr>
        <tr><td class="k">Support tier</td><td>{{ $ticket->support_tier->label() }}</td><td class="k">Category</td><td>{{ $ticket->category?->name ?? '—' }}</td></tr>
        <tr><td class="k">Created by</td><td>{{ $ticket->creator?->name }}</td><td class="k">Assigned to</td><td>{{ $ticket->assignee?->name ?? 'Unassigned' }}</td></tr>
        <tr><td class="k">Created</td><td>{{ $ticket->created_at->format('Y-m-d H:i') }}</td><td class="k">Updated</td><td>{{ $ticket->updated_at->format('Y-m-d H:i') }}</td></tr>
        <tr><td class="k">Escalated</td><td>{{ $ticket->escalated_at?->format('Y-m-d H:i') ?? '—' }}</td><td class="k">Resolved</td><td>{{ $ticket->resolved_at?->format('Y-m-d H:i') ?? '—' }}</td></tr>
    </table>

    <h2>Description</h2>
    <div class="box">{{ $ticket->description }}</div>

    @if ($ticket->resolution)
        <h2>Resolution</h2>
        <div class="box">{{ $ticket->resolution }}</div>
    @endif

    <h2>Comments &amp; notes</h2>
    @forelse ($ticket->comments as $comment)
        <div class="entry">
            <span class="muted">{{ $comment->author?->name }} · {{ $comment->created_at->format('Y-m-d H:i') }} {{ $comment->is_internal ? '· INTERNAL' : '' }}</span><br>
            {{ $comment->body }}
        </div>
    @empty
        <div class="muted">No comments.</div>
    @endforelse

    <h2>Activity history</h2>
    @foreach ($ticket->activities as $activity)
        <div class="entry">
            <span class="muted">{{ $activity->created_at->format('Y-m-d H:i') }} · {{ $activity->causer?->name ?? 'System' }}</span><br>
            {{ $activity->description }}
        </div>
    @endforeach

    @if ($ticket->attachments->isNotEmpty())
        <h2>Attachments</h2>
        @foreach ($ticket->attachments as $attachment)
            <div class="entry">{{ $attachment->original_name }} <span class="muted">({{ $attachment->humanSize() }}, {{ $attachment->uploader?->name }})</span></div>
        @endforeach
    @endif
</body>
</html>
