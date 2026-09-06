<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full items-center justify-center px-4">
<div class="w-full max-w-sm">
    <div class="mb-6 flex items-center justify-center gap-2">
        <span class="flex h-10 w-10 items-center justify-center rounded-md bg-brand-600 text-white text-lg font-bold">H</span>
        <span class="text-xl font-semibold text-gray-900">{{ config('app.name') }}</span>
    </div>

    <div class="card p-6">
        <h1 class="text-lg font-semibold text-gray-900">Sign in</h1>
        <p class="mt-1 text-sm text-gray-500">Access the IT helpdesk.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" name="email" type="email" required autofocus
                       value="{{ old('email') }}" class="input" autocomplete="username">
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" name="password" type="password" required
                       class="input" autocomplete="current-password">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                Remember me
            </label>
            <button type="submit" class="btn-primary w-full">Sign in</button>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-gray-400">
        {{ config('helpdesk.credit') }} &middot; &copy; {{ date('Y') }}
    </p>
</div>
</body>
</html>
