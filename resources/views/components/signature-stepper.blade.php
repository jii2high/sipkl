{{--
    Stepper 5-TTD (DESIGN.md "Stepper 5-TTD"). Horisontal di desktop, vertikal di
    ponsel. Status selalu disertai teks, tidak pernah warna saja.
--}}
@props(['steps'])

<ol class="flex flex-col gap-4 md:flex-row md:items-start md:gap-2">
    @foreach ($steps as $index => $step)
        @php
            $status = $step['status'] ?? 'empty';
            $circle = match ($status) {
                'signed' => 'bg-[var(--accent-strong)] text-white',
                'pending' => 'border-2 border-[var(--accent)] text-[var(--accent-strong)]',
                'rejected' => 'bg-[var(--danger)] text-white',
                default => 'border border-[var(--border)] text-[var(--text-muted)]',
            };
        @endphp
        <li class="flex flex-1 items-start gap-3 md:flex-col md:items-center md:text-center">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[0.8125rem] font-semibold {{ $circle }}" aria-hidden="true">
                @if ($status === 'signed') ✓
                @elseif ($status === 'rejected') ✕
                @else {{ $index + 1 }}
                @endif
            </span>
            <span class="min-w-0">
                <span class="block text-[0.8125rem] font-medium {{ $status === 'pending' ? 'text-[var(--text)]' : '' }}">{{ $step['label'] }}</span>
                <span class="block text-[0.75rem] text-[var(--text-muted)]">
                    @if ($status === 'signed') Selesai
                    @elseif ($status === 'pending') {{ $step['waiting'] ?? 'Menunggu' }}
                    @elseif ($status === 'rejected') Ditolak
                    @else Belum
                    @endif
                </span>
                @if (! empty($step['note']))
                    <span class="mt-1 block text-[0.75rem] text-[var(--danger)]">{{ $step['note'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>