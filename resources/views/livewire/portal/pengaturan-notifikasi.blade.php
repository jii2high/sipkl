<section class="flex flex-col gap-4">
    <div>
        <h1 class="text-[1.125rem] font-semibold">{{ __('pkl.push.judul') }}</h1>
        <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">{{ __('pkl.push.subjudul') }}</p>
    </div>

    @if ($flash !== null)
        <p
            role="status"
            class="rounded-[var(--radius-md)] border border-[var(--success)] bg-[var(--success-soft)] p-3 text-[0.875rem] text-[var(--success)]"
        >{{ $flash }}</p>
    @endif

    <div class="pkl-card flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span @class([
                'pkl-chip',
                'pkl-chip-success' => $pushOptIn === true,
                'pkl-chip-neutral' => $pushOptIn !== true,
            ])>
                {{ match ($pushOptIn) {
                    true => __('pkl.push.status_aktif'),
                    false => __('pkl.push.status_nonaktif'),
                    default => __('pkl.push.status_belum'),
                } }}
            </span>

            @if ($pushOptIn === true)
                <button type="button" class="pkl-btn pkl-btn-secondary" wire:click="setPush(false)">
                    <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                    {{ __('pkl.push.matikan') }}
                </button>
            @else
                <button type="button" class="pkl-btn pkl-btn-primary" wire:click="setPush(true)">
                    <x-heroicon-o-check class="h-5 w-5" aria-hidden="true" />
                    {{ __('pkl.push.aktifkan') }}
                </button>
            @endif
        </div>

        <p class="text-[0.75rem] text-[var(--text-muted)]">{{ __('pkl.push.catatan') }}</p>
    </div>
</section>
