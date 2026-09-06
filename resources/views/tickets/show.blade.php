@extends('layouts.app')
@section('title', $ticket->reference)
@section('heading', $ticket->reference)

@section('actions')
    <a href="{{ route('tickets.export.show', $ticket) }}" class="btn-secondary">Export PDF</a>
    @can('update', $ticket)
        <a href="{{ route('tickets.edit', $ticket) }}" class="btn-secondary">Edit</a>
    @endcan
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main column --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-5">
                <div class="flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$ticket->status" />
                    <x-priority-badge :priority="$ticket->priority" />
                    <x-tier-badge :tier="$ticket->support_tier" />
                    @if ($ticket->isEscalated())
                        <span class="badge bg-indigo-50 text-indigo-700">Escalated {{ $ticket->escalated_at?->diffForHumans() }}</span>
                    @endif
                </div>
                <h2 class="mt-3 text-xl font-semibold text-gray-900">{{ $ticket->title }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $ticket->description }}</p>

                @if ($ticket->resolution)
                    <div class="mt-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-900">
                        <p class="font-medium">Resolution</p>
                        <p class="mt-1 whitespace-pre-line">{{ $ticket->resolution }}</p>
                    </div>
                @endif
            </div>

            {{-- Attachments --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-700">Attachments</h3>
                <ul class="mt-3 divide-y divide-gray-100">
                    @forelse ($ticket->attachments as $attachment)
                        <li class="flex items-center gap-3 py-2 text-sm">
                            <a href="{{ route('tickets.attachments.show', [$ticket, $attachment]) }}" class="font-medium text-brand-700">
                                {{ $attachment->original_name }}
                            </a>
                            <span class="text-gray-400">{{ $attachment->humanSize() }}</span>
                            <span class="text-gray-400">· {{ $attachment->uploader?->name }}</span>
                            @if ($attachment->uploaded_by === auth()->id() || auth()->user()->can('manageAttachments', $ticket))
                                <form method="POST" action="{{ route('tickets.attachments.destroy', [$ticket, $attachment]) }}" class="ml-auto"
                                      onsubmit="return confirm('Remove this attachment?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Remove</button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-400">No attachments.</li>
                    @endforelse
                </ul>

                @can('manageAttachments', $ticket)
                    <form method="POST" action="{{ route('tickets.attachments.store', $ticket) }}" enctype="multipart/form-data"
                          class="mt-3 flex items-center gap-2">
                        @csrf
                        <input type="file" name="files[]" multiple required class="text-sm">
                        <button class="btn-secondary">Upload</button>
                    </form>
                @endcan
            </div>

            {{-- Comments --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-700">Comments &amp; notes</h3>
                <ul class="mt-4 space-y-4">
                    @forelse ($comments as $comment)
                        <li class="rounded-md border {{ $comment->is_internal ? 'border-amber-200 bg-amber-50' : 'border-gray-200' }} p-3">
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <span class="font-medium text-gray-700">{{ $comment->author?->name ?? 'Unknown' }}</span>
                                <span>· {{ $comment->created_at->diffForHumans() }}</span>
                                @if ($comment->is_internal)
                                    <span class="badge bg-amber-100 text-amber-800">Internal</span>
                                @endif
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $comment->body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-gray-400">No comments yet.</li>
                    @endforelse
                </ul>

                @can('comment', $ticket)
                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="mt-4 space-y-2">
                        @csrf
                        <textarea name="body" rows="3" required class="input" placeholder="Add a comment…">{{ old('body') }}</textarea>
                        <div class="flex items-center justify-between">
                            @if (auth()->user()->isAgent())
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="is_internal" value="1" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    Internal note (agents only)
                                </label>
                            @else
                                <span></span>
                            @endif
                            <button class="btn-primary">Post comment</button>
                        </div>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Side column --}}
        <div class="space-y-6">
            <div class="card p-5 text-sm">
                <dl class="space-y-3">
                    <div class="flex justify-between"><dt class="text-gray-500">Reference</dt><dd class="font-medium">{{ $ticket->reference }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Category</dt><dd>{{ $ticket->category?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Created by</dt><dd>{{ $ticket->creator?->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Assigned to</dt><dd>{{ $ticket->assignee?->name ?? 'Unassigned' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Created</dt><dd>{{ $ticket->created_at->format('Y-m-d H:i') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Updated</dt><dd>{{ $ticket->updated_at->diffForHumans() }}</dd></div>
                    @if ($ticket->resolved_at)
                        <div class="flex justify-between"><dt class="text-gray-500">Resolved</dt><dd>{{ $ticket->resolved_at->format('Y-m-d H:i') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Resolution time</dt><dd>{{ $ticket->resolutionHours() }} h</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Status change --}}
            @can('update', $ticket)
                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-gray-700">Change status</h3>
                    <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="mt-3 space-y-2">
                        @csrf @method('PUT')
                        <select name="status" class="input">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($ticket->status->value === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="resolution" rows="2" class="input" placeholder="Resolution note (required to resolve)">{{ $ticket->resolution }}</textarea>
                        <button class="btn-primary w-full">Update status</button>
                    </form>
                </div>
            @endcan

            {{-- Assignment --}}
            @can('assign', $ticket)
                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-gray-700">Assign</h3>
                    <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="mt-3 space-y-2">
                        @csrf @method('PUT')
                        <select name="assigned_to" class="input">
                            <option value="">— Unassigned —</option>
                            @foreach ($assignees as $assignee)
                                <option value="{{ $assignee->id }}" @selected($ticket->assigned_to === $assignee->id)>
                                    {{ $assignee->name }} ({{ $assignee->roleName()?->label() }})
                                </option>
                            @endforeach
                        </select>
                        <button class="btn-secondary w-full">Update assignee</button>
                    </form>
                </div>
            @endcan

            {{-- Escalation --}}
            @can('escalate', $ticket)
                <div class="card border-indigo-200 p-5">
                    <h3 class="text-sm font-semibold text-indigo-700">Escalate to L2</h3>
                    <p class="mt-1 text-xs text-gray-500">Moves this ticket to the L2 queue.</p>
                    <form method="POST" action="{{ route('tickets.escalate', $ticket) }}" class="mt-3 space-y-2">
                        @csrf
                        <select name="assigned_to" class="input">
                            <option value="">Assign later</option>
                            @foreach ($assignees->filter(fn ($a) => $a->isL2() || $a->isAdmin()) as $assignee)
                                <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                        <textarea name="note" rows="2" class="input" placeholder="Why are you escalating?"></textarea>
                        <button class="btn-primary w-full bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500">Escalate</button>
                    </form>
                </div>
            @endcan

            @can('delete', $ticket)
                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" onsubmit="return confirm('Delete this ticket permanently?')">
                    @csrf @method('DELETE')
                    <button class="btn-danger w-full">Delete ticket</button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Activity log --}}
    <div class="card mt-6 p-5">
        <h3 class="text-sm font-semibold text-gray-700">Activity history</h3>
        <ol class="mt-4 space-y-3">
            @foreach ($ticket->activities as $activity)
                <li class="flex gap-3 text-sm">
                    <span class="mt-1 h-2 w-2 flex-none rounded-full bg-brand-400"></span>
                    <div>
                        <p class="text-gray-700">{{ $activity->description }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $activity->causer?->name ?? 'System' }} · {{ $activity->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endsection
