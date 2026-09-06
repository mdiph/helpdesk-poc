@extends('layouts.app')
@section('title', 'New category')
@section('heading', 'New category')

@section('content')
    <form method="POST" action="{{ route('categories.store') }}" class="card mx-auto max-w-md space-y-4 p-6">
        @csrf

        <div>
            <label class="label" for="name">Name</label>
            <input id="name" name="name" type="text" required maxlength="80" value="{{ old('name') }}" class="input">
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            Active (selectable on new tickets)
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('categories.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary">Create</button>
        </div>
    </form>
@endsection
