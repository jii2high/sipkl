<div class="mx-auto max-w-[560px]">
    <div class="pkl-card">
        <h1 class="text-[1.5rem] font-bold leading-tight tracking-[-0.01em]">
            {{ __('pkl.document.verify.judul') }}
        </h1>
        <p class="mt-2 text-[0.875rem] text-[var(--text-muted)]">
            {{ __('pkl.document.verify.petunjuk') }}
        </p>

        @if ($hasil !== null)
            <dl class="mt-6 divide-y divide-[var(--border)] border-y border-[var(--border)]">
                <div class="flex flex-wrap items-baseline justify-between gap-2 py-3">
                    <dt class="text-[0.875rem] text-[var(--text-muted)]">{{ __('pkl.document.verify.nomor') }}</dt>
                    <dd class="font-mono text-[0.9375rem] font-semibold">{{ $hasil['nomor_surat'] }}</dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 py-3">
                    <dt class="text-[0.875rem] text-[var(--text-muted)]">{{ __('pkl.document.verify.status') }}</dt>
                    <dd><span class="pkl-chip pkl-chip-info"><x-heroicon-o-check-badge class="h-4 w-4" aria-hidden="true" />{{ $hasil['status'] }}</span></dd>
                </div>
                <div class="flex flex-wrap items-baseline justify-between gap-2 py-3">
                    <dt class="text-[0.875rem] text-[var(--text-muted)]">{{ __('pkl.document.verify.tanggal') }}</dt>
                    <dd class="text-[0.9375rem]">{{ $hasil['tanggal_terbit'] }}</dd>
                </div>
            </dl>
        @else
            <p class="mt-6 flex items-center gap-2 rounded-[var(--radius-md)] bg-[var(--surface-muted)] px-4 py-3 text-[0.9375rem]">
                <x-heroicon-o-x-circle class="h-5 w-5 shrink-0" aria-hidden="true" />
                {{ __('pkl.document.verify.tidak_ditemukan') }}
            </p>
        @endif

        <a href="{{ url('/') }}" class="pkl-btn pkl-btn-secondary mt-6">
            <x-heroicon-o-arrow-left class="h-5 w-5" aria-hidden="true" />
            {{ __('pkl.document.verify.kembali') }}
        </a>
    </div>
</div>