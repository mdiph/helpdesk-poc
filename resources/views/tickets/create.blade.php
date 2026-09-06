@extends('layouts.app')
@section('title', 'New ticket')
@section('heading', 'New ticket')

@section('content')
    <form method="POST" action="{{ route('tickets.store') }}" class="card mx-auto max-w-2xl space-y-4 p-6">
        @csrf

        <div>
            <label class="label" for="title">Title</label>
            <input id="title" name="title" type="text" required maxlength="180" value="{{ old('title') }}" class="input">
        </div>

        <div>
            <label class="label" for="description">Description</label>
            <textarea id="description" name="description" rows="6" required class="input">{{ old('description') }}</textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="category_id">Category</label>
                <select id="category_id" name="category_id" class="input">
                    <option value="">— None —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="priority">Priority</label>
                <select id="priority" name="priority" required class="input">
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="label" for="assigned_to">Assign to (optional)</label>
            <select id="assigned_to" name="assigned_to" class="input">
                <option value="">— Unassigned —</option>
                @foreach ($assignees as $assignee)
                    <option value="{{ $assignee->id }}" @selected(old('assigned_to') == $assignee->id)>
                        {{ $assignee->name }} ({{ $assignee->roleName()?->label() }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('tickets.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Create ticket</button>
        </div>
    </form>
@endsection
