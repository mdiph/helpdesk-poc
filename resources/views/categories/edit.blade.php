@extends('layouts.app')
@section('title', 'Edit category')
@section('heading', 'Edit '.$category->name)

@section('content')
    <form method="POST" action="{{ route('categories.update', $category) }}" class="card mx-auto max-w-md space-y-4 p-6">
        @csrf
        @method('PUT')

        <div>
            <label class="label" for="name">Name</label>
            <input id="name" name="name" type="text" required maxlength="80" value="{{ old('name', $category->name) }}" class="input">
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))
                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            Active (selectable on new tickets)
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('categories.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary">Save</button>
        </div>
    </form>
@endsection
