@php
    $user = auth()->user();
    $nav = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard', 'show' => true],
        ['label' => 'Tickets', 'route' => 'tickets.index', 'pattern' => 'tickets*', 'show' => true],
        ['label' => 'New ticket', 'route' => 'tickets.create', 'pattern' => 'tickets.create', 'show' => $user->can('create', \App\Models\Ticket::class)],
        ['label' => 'Reports', 'route' => 'reports.index', 'pattern' => 'reports*', 'show' => true],
        ['label' => 'Categories', 'route' => 'categories.index', 'pattern' => 'categories*', 'show' => $user->isAdmin()],
        ['label' => 'Users', 'route' => 'users.index', 'pattern' => 'users*', 'show' => $user->isAdmin()],
    ];
@endphp

<div class="flex h-16 items-center gap-2 border-b border-gray-200 px-5">
    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-brand-600 text-white font-bold">H</span>
    <span class="text-lg font-semibold text-gray-900">{{ config('app.name') }}</span>
</div>

<nav class="space-y-1 p-3">
    @foreach ($nav as $item)
        @continue(! $item['show'])
        <a href="{{ route($item['route']) }}"
           class="nav-link {{ request()->routeIs($item['pattern']) ? 'nav-link-active' : '' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>

<div class="absolute inset-x-0 bottom-0 border-t border-gray-200 p-4 text-xs text-gray-400">
    Signed in as<br>
    <span class="font-medium text-gray-600">{{ $user->email }}</span>
</div>
