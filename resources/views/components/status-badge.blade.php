@php
    use App\Support\StatusChip;

    $tone = StatusChip::tone($status);
    $label = $label ?? StatusChip::label($status);
@endphp

<span {{ $attributes->class(['pkl-chip', 'pkl-chip-'.$tone]) }}>{{ $label }}</span>
