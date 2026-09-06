@props(['tier'])
@php
    $value = $tier instanceof \App\Enums\SupportTier ? $tier : \App\Enums\SupportTier::from($tier);
    $classes = $value === \App\Enums\SupportTier::L2 ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700';
@endphp
<span class="badge {{ $classes }}">{{ $value->label() }}</span>
