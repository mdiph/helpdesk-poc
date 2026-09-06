@extends('layouts.app')
@section('title', 'Edit '.$ticket->reference)
@section('heading', 'Edit '.$ticket->reference)

@section('content')
    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="card mx-auto max-w-2xl space-y-4 p-6">
        @csrf
        @method('PUT')

        <div>
            <label class="label" for="title">Title</label>
            <input id="title" name="title" type="text" required maxlength="180" value="{{ old('title', $ticket->title) }}" class="input">
        </div>

        <div>
            <label class="label" for="description">Description</label>
            <textarea id="description" name="description" rows="6" required class="input">{{ old('description', $ticket->description) }}</textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label" for="category_id">Category</label>
                <select id="category_id" name="category_id" class="input">
                    <option value="">— None —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $ticket->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="priority">Priority</label>
                <select id="priority" name="priority" class="input">
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $ticket->priority->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="input">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $ticket->status->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="label" for="assigned_to">Assignee</label>
            <select id="assigned_to" name="assigned_to" class="input">
                <option value="">— Unassigned —</option>
                @foreach ($assignees as $assignee)
                    <option value="{{ $assignee->id }}" @selected(old('assigned_to', $ticket->assigned_to) == $assignee->id)>
                        {{ $assignee->name }} ({{ $assignee->roleName()?->label() }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="label" for="resolution">Resolution note</label>
            <textarea id="resolution" name="resolution" rows="3" class="input">{{ old('resolution', $ticket->resolution) }}</textarea>
            <p class="mt-1 text-xs text-gray-400">Required before a ticket can be marked resolved.</p>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('tickets.show', $ticket) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save changes</button>
        </div>
    </form>
@endsection
