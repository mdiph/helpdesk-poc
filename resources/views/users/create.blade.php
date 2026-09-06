@extends('layouts.app')
@section('title', 'New user')
@section('heading', 'New user')

@section('content')
    <form method="POST" action="{{ route('users.store') }}" class="card mx-auto max-w-lg space-y-4 p-6">
        @csrf

        <div>
            <label class="label" for="name">Name</label>
            <input id="name" name="name" type="text" required value="{{ old('name') }}" class="input">
        </div>
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" name="email" type="email" required value="{{ old('email') }}" class="input">
        </div>
        <div>
            <label class="label" for="role">Role</label>
            <select id="role" name="role" required class="input">
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" name="password" type="password" required class="input">
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="input">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            Account enabled
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary">Create user</button>
        </div>
    </form>
@endsection
