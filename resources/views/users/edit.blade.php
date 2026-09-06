@extends('layouts.app')
@section('title', 'Edit user')
@section('heading', 'Edit '.$user->name)

@section('content')
    <form method="POST" action="{{ route('users.update', $user) }}" class="card mx-auto max-w-lg space-y-4 p-6">
        @csrf
        @method('PUT')

        <div>
            <label class="label" for="name">Name</label>
            <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}" class="input">
        </div>
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" name="email" type="email" required value="{{ old('email', $user->email) }}" class="input">
        </div>
        <div>
            <label class="label" for="role">Role</label>
            @if ($user->id === auth()->id())
                <input type="hidden" name="role" value="{{ $user->roleName()?->value }}">
                <input type="text" class="input" value="{{ $user->roleName()?->label() }}" disabled>
                <p class="mt-1 text-xs text-gray-400">You cannot change your own role.</p>
            @else
                <select id="role" name="role" required class="input">
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $user->roleName()?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary">Save changes</button>
        </div>
    </form>

    <div class="card mx-auto mt-6 max-w-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700">Reset password</h3>
        <form method="POST" action="{{ route('users.reset-password', $user) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
            @csrf @method('PUT')
            <input type="password" name="password" placeholder="New password" required class="input">
            <input type="password" name="password_confirmation" placeholder="Confirm password" required class="input">
            <div class="sm:col-span-2">
                <button class="btn-secondary">Set new password</button>
            </div>
        </form>
    </div>
@endsection
