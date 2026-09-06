@props(['status'])
@php
    $value = $status instanceof \App\Enums\TicketStatus ? $status : \App\Enums\TicketStatus::from($status);
    $classes = match ($value) {
        \App\Enums\TicketStatus::Open => 'bg-blue-100 text-blue-800',
        \App\Enums\TicketStatus::InProgress => 'bg-amber-100 text-amber-800',
        \App\Enums\TicketStatus::Pending => 'bg-purple-100 text-purple-800',
        \App\Enums\TicketStatus::Resolved => 'bg-green-100 text-green-800',
        \App\Enums\TicketStatus::Closed => 'bg-gray-200 text-gray-700',
    };
@endphp
<span class="badge {{ $classes }}">{{ $value->label() }}</span>
