@props(['value' => null, 'type' => 'phone'])

@php
    $masked = $type === 'email'
        ? \App\Support\MaskedContact::email($value)
        : \App\Support\MaskedContact::phone($value);
@endphp

<span {{ $attributes }}>{{ $masked !== null ? $masked : '—' }}</span>
