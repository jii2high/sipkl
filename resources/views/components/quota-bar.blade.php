@props(['used' => 0, 'max' => null])

@php
    use App\Services\QuotaService;

    $quota = app(QuotaService::class);
    $used = (int) $used;
    $max = $max === null ? $quota->maximum() : (int) $max;
    $tone = $quota->tone($used);
    $fill = match ($tone) {
        'danger' => 'bg-danger-500',
        'warning' => 'bg-warning-500',
        default => 'bg-primary-500',
    };
    $text = match ($tone) {
        'danger' => 'text-danger-600 dark:text-danger-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        default => 'text-gray-600 dark:text-gray-300',
    };
@endphp

<div {{ $attributes->class(['flex flex-wrap items-center gap-2']) }}>
    <div
        class="flex gap-1"
        role="progressbar"
        aria-valuenow="{{ $used }}"
        aria-valuemin="0"
        aria-valuemax="{{ $max }}"
        aria-label="{{ __('pkl.quota.label', ['used' => $used, 'maks' => $max]) }}"
    >
        @for ($slot = 1; $slot <= $max; $slot++)
            <span @class([
                'h-2 w-4 rounded-sm',
                $fill => $slot <= $used,
                'bg-gray-200 dark:bg-gray-700' => $slot > $used,
            ])></span>
        @endfor
    </div>

    <span class="text-xs font-medium {{ $text }}">
        {{ __('pkl.quota.label', ['used' => $used, 'maks' => $max]) }}
    </span>
</div>
