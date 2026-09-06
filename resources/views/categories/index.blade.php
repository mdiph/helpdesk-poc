@extends('layouts.app')
@section('title', 'Categories')
@section('heading', 'Categories')

@section('actions')
    <a href="{{ route('categories.create') }}" class="btn-primary">New category</a>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Name</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Tickets</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-medium text-gray-900">{{ $category->name }}</td>
                            <td class="table-td">
                                @if ($category->is_active)
                                    <span class="badge bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="badge bg-gray-200 text-gray-600">Hidden</span>
                                @endif
                            </td>
                            <td class="table-td">{{ $category->tickets_count }}</td>
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('categories.edit', $category) }}" class="text-brand-700 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                          onsubmit="return confirm('Delete this category? Existing tickets keep their history but lose this category label.')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-4 py-3">{{ $categories->links() }}</div>
    </div>

    <p class="mt-4 text-xs text-gray-400">
        Hidden categories stay on existing tickets but can't be chosen for new ones.
        Roles are fixed by design and can't be edited here.
    </p>
@endsection
