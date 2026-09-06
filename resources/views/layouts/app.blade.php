<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Helpdesk') &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="min-h-full" x-data="{ sidebar: false }">
    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebar" x-cloak class="fixed inset-0 z-40 bg-gray-900/40 lg:hidden" @click="sidebar = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full transform border-r border-gray-200 bg-white transition lg:translate-x-0"
           :class="sidebar && 'translate-x-0'">
        @include('partials.sidebar')
    </aside>

    <div class="lg:pl-64">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6">
            <button class="lg:hidden" @click="sidebar = true" aria-label="Open menu">
                <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-900">@yield('heading', 'Helpdesk')</h1>

            <div class="ml-auto flex items-center gap-4" x-data="{ open: false }">
                @yield('actions')
                <div class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 text-sm hover:bg-gray-100">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                            {{ Str::of(auth()->user()->name)->explode(' ')->map(fn ($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}
                        </span>
                        <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 mt-2 w-48 rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        <div class="px-3 py-2 text-xs text-gray-500">
                            {{ auth()->user()->roleName()?->label() }}
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile &amp; password</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
