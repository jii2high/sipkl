<section class="flex flex-col gap-4">
    <h1 class="text-[1.125rem] font-semibold">Jadwal sidang saya</h1>

    @forelse ($this->schedules() as $jadwal)
        <article wire:key="jadwal-{{ $jadwal->getKey() }}" class="pkl-card flex flex-col gap-2">
            <h2 class="text-[1rem] font-semibold">{{ $jadwal->tanggal->format('d M Y') }}</h2>

            <dl class="grid gap-1 text-[0.875rem]">
                <div class="flex flex-wrap gap-1">
                    <dt class="text-[var(--text-muted)]">Penguji</dt>
                    <dd>{{ $jadwal->externalExaminer?->nama ?? '—' }}</dd>
                </div>
                <div class="flex flex-wrap gap-1">
                    <dt class="text-[var(--text-muted)]">Status</dt>
                    <dd>{{ $this->statusLabel($jadwal->status) }}</dd>
                </div>
            </dl>
        </article>
    @empty
        <div class="pkl-card flex flex-col items-center gap-3 text-center">
            <x-heroicon-o-calendar-days class="h-8 w-8 text-[var(--text-muted)]" aria-hidden="true" />
            <p class="text-[0.875rem] text-[var(--text-muted)]">
                Belum ada jadwal sidang. Jadwal muncul setelah laporan sah dan Kaprog menjadwalkan sidang.
            </p>
            <a href="{{ route('portal.siswa') }}" class="pkl-btn pkl-btn-secondary w-full">
                <x-heroicon-o-arrow-left class="h-5 w-5" aria-hidden="true" />
                Kembali ke pengajuan
            </a>
        </div>
    @endforelse
</section>
