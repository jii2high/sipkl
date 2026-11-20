<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Antrean permintaan revisi</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Persetujuan menerbitkan satu tautan + PIN baru untuk penguji asli, berlaku 24 jam.</p>

            <div class="mt-5 space-y-3">
                @forelse ($this->revisions() as $revision)
                    <article wire:key="revisi-{{ $revision->getKey() }}" class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-medium text-gray-950 dark:text-white">
                                    {{ $revision->submission?->pengajuanSiswa?->siswa?->name }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Penguji {{ $revision->submission?->jadwalSidang?->externalExaminer?->nama }} · diajukan {{ $revision->created_at?->format('d/m/Y H:i') }}
                                </p>
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Alasan: {{ $revision->reason }}</p>
                            </div>
                            <x-filament::button size="sm" class="min-h-11" wire:click="approve({{ $revision->getKey() }})" wire:loading.attr="disabled">
                                Setujui revisi
                            </x-filament::button>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada permintaan revisi yang menunggu.</p>
                @endforelse
            </div>

            @if ($waUrl)
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex min-h-11 items-center text-sm font-medium text-primary-600 underline">
                    Buka WhatsApp dengan tautan revisi
                </a>
            @endif
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Kelulusan sidang</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Status kelulusan diisi manual oleh Kaprog setelah nilai dikunci penguji.</p>

            @forelse ($this->submissions() as $submission)
                @php($summary = $this->summary($submission))
                <article wire:key="nilai-{{ $submission->getKey() }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-950 dark:text-white">{{ $submission->pengajuanSiswa?->siswa?->name }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Nilai total {{ $submission->nilai_total }} · penguji {{ $submission->jadwalSidang?->externalExaminer?->nama }}
                                · {{ $submission->jadwalSidang?->tanggal?->format('d/m/Y') }}
                            </p>
                            <p class="mt-1 text-sm">
                                Kelulusan:
                                <span class="font-medium">{{ $summary?->status_kelulusan ?? 'belum ditetapkan' }}</span>
                            </p>
                        </div>
                        <x-filament::button size="sm" class="min-h-11" color="gray" wire:click="openKelulusan({{ $submission->getKey() }})">
                            Tetapkan kelulusan
                        </x-filament::button>
                    </div>

                    @if ($kelulusanId === $submission->getKey())
                        <div class="mt-4 grid gap-3 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 md:grid-cols-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Status kelulusan
                                <select wire:model="kelulusanStatus" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                                    <option value="">Pilih status</option>
                                    <option value="Lulus">Lulus</option>
                                    <option value="Tidak Lulus">Tidak Lulus</option>
                                </select>
                                @error('kelulusanStatus') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 md:col-span-2">
                                Catatan (opsional)
                                <textarea wire:model="kelulusanCatatan" rows="2" maxlength="2000" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950"></textarea>
                                @error('kelulusanCatatan') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <div class="md:col-span-3">
                                <x-filament::button size="sm" class="min-h-11" wire:click="saveKelulusan" wire:loading.attr="disabled">
                                    Simpan kelulusan
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    Belum ada nilai sidang yang dikunci.
                </div>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>