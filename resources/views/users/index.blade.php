@extends('layouts.app')
@section('title', 'Users')
@section('heading', 'User management')

@section('actions')
    <a href="{{ route('users.create') }}" class="btn-primary">New user</a>
@endsection

@section('content')
    <form method="GET" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div>
            <label class="label">Search</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name or email" class="input">
        </div>
        <div>
            <label class="label">Role</label>
            <select name="role" class="input">
                <option value="">Any</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary">Filter</button>
        <a href="{{ route('users.index') }}" class="btn-secondary">Reset</a>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Name</th>
                        <th class="table-th">Email</th>
                        <th class="table-th">Role</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="table-td">{{ $user->email }}</td>
                            <td class="table-td">{{ $user->roleName()?->label() }}</td>
                            <td class="table-td">
                                @if ($user->is_active)
                                    <span class="badge bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="badge bg-gray-200 text-gray-600">Disabled</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('users.edit', $user) }}" class="text-brand-700 hover:underline">Edit</a>

                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                            @csrf @method('PUT')
                                            <button class="text-gray-600 hover:underline">{{ $user->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                    @endif

                                    <details class="relative">
                                        <summary class="cursor-pointer list-none text-gray-600 hover:underline">Reset password</summary>
                                        <form method="POST" action="{{ route('users.reset-password', $user) }}"
                                              class="absolute z-10 mt-2 w-64 space-y-2 rounded-md border border-gray-200 bg-white p-3 shadow-lg">
                                            @csrf @method('PUT')
                                            <input type="password" name="password" placeholder="New password" required class="input">
                                            <input type="password" name="password_confirmation" placeholder="Confirm password" required class="input">
                                            <button class="btn-primary w-full">Set password</button>
                                        </form>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-4 py-3">{{ $users->links() }}</div>
    </div>
@endsection
