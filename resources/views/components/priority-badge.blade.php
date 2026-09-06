@props(['priority'])
@php
    $value = $priority instanceof \App\Enums\TicketPriority ? $priority : \App\Enums\TicketPriority::from($priority);
    $classes = match ($value) {
        \App\Enums\TicketPriority::Urgent => 'bg-red-100 text-red-800',
        \App\Enums\TicketPriority::High => 'bg-orange-100 text-orange-800',
        \App\Enums\TicketPriority::Medium => 'bg-yellow-100 text-yellow-800',
        \App\Enums\TicketPriority::Low => 'bg-gray-100 text-gray-700',
    };
@endphp
<span class="badge {{ $classes }}">{{ $value->label() }}</span>
