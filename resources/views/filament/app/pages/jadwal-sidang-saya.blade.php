<x-filament-panels::page>
    <div class="space-y-3">
        @forelse ($this->schedules() as $jadwal)
            <article wire:key="jadwal-{{ $jadwal->getKey() }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="font-semibold text-gray-950 dark:text-white">{{ $jadwal->tanggal->format('d/m/Y') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Penguji: {{ $jadwal->externalExaminer?->nama }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Status:
                    {{ match ($jadwal->status) {
                        'terjadwal' => 'Terjadwal',
                        'sesi_aktif' => 'Sedang berlangsung',
                        'selesai' => 'Selesai',
                        default => $jadwal->status,
                    } }}
                </p>
            </article>
        @empty
            <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                Belum ada jadwal sidang. Jadwal muncul setelah laporan sah dan Kaprog menjadwalkan sidang.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>