@extends('layouts.app')
@section('title', 'Profile')
@section('heading', 'Profile & password')

@section('content')
    <div class="card mx-auto max-w-lg p-6">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Name</dt><dd class="font-medium">{{ $user->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>{{ $user->email }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Role</dt><dd>{{ $user->roleName()?->label() }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-gray-400">Ask an administrator to change your name, email or role.</p>
    </div>

    <div class="card mx-auto mt-6 max-w-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700">Change password</h3>
        <form method="POST" action="{{ route('profile.password') }}" class="mt-3 space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label class="label" for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" required class="input" autocomplete="current-password">
            </div>
            <div>
                <label class="label" for="password">New password</label>
                <input id="password" name="password" type="password" required class="input" autocomplete="new-password">
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="input" autocomplete="new-password">
            </div>
            <button class="btn-primary">Update password</button>
        </form>
    </div>
@endsection
