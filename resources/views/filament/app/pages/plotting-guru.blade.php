<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Tetapkan guru pembimbing</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Perusahaan muncul setelah memiliki siswa berstatus sedang PKL pada periode terpilih.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Periode akademik
                    <select wire:model.live="periodId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Pilih periode</option>
                        @foreach ($this->periods() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Perusahaan
                    <select wire:model.live="companyId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Pilih perusahaan</option>
                        @foreach ($this->companies() as $company)
                            <option value="{{ $company->id }}">{{ $company->nama }} ({{ $company->jumlah_siswa }} siswa)</option>
                        @endforeach
                    </select>
                    @error('companyId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Guru pembimbing
                    <select wire:model="guruId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Pilih guru aktif</option>
                        @foreach ($this->teachers() as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }} · {{ $teacher->jumlah_bimbingan }} perusahaan</option>
                        @endforeach
                    </select>
                    @error('guruId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex items-end">
                    <x-filament::button wire:click="savePlotting" wire:loading.attr="disabled" class="w-full min-h-11">
                        Simpan plotting
                    </x-filament::button>
                </div>
            </div>

            @php($selectedTeacher = $this->teachers()->firstWhere('id', $guruId))
            @if ($selectedTeacher && $selectedTeacher->jumlah_wilayah > 1)
                <div class="mt-4 rounded-lg bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-400/10 dark:text-warning-200" role="status">
                    Guru sudah membimbing di alamat perusahaan yang berbeda. Periksa jarak perjalanan sebelum menetapkan penugasan baru.
                </div>
            @endif
            @php($selectedCompany = $this->companies()->firstWhere('id', $companyId))
            @if ($selectedCompany && $selectedCompany->jumlah_jurusan > 1)
                <div class="mt-3 rounded-lg bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-400/10 dark:text-warning-200" role="status">
                    Perusahaan menerima siswa dari beberapa program keahlian. Plotting tetap berlaku untuk perusahaan dan periode ini.
                </div>
            @endif
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Perusahaan siap plotting</h2>
            @forelse ($this->companies() as $company)
                @php($plotting = $company->plottings->first())
                <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-950 dark:text-white">{{ $company->nama }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $company->jumlah_siswa }} siswa PKL aktif</p>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $plotting?->guru?->name ?? 'Belum diplot' }}</p>
                    </div>
                    <div class="mt-3 border-t border-gray-200 pt-3 text-sm dark:border-gray-800">
                        @if ($plotting?->suratTugas)
                            <a class="text-primary-600 underline" href="{{ route('documents.download', ['jenis' => 'surat_tugas_pembimbing', 'id' => $plotting->suratTugas->id]) }}">{{ $plotting->suratTugas->nomor_surat }}</a>
                        @elseif ($plotting)
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-warning-700 dark:text-warning-300">Menunggu scan TTD Kepsek</span>
                                <x-filament::button size="sm" class="min-h-11" wire:click="issueLetter({{ $plotting->getKey() }})" wire:loading.attr="disabled">
                                    Coba terbitkan ulang
                                </x-filament::button>
                            </div>
                        @else
                            <span class="text-gray-500">Belum terbit</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">Belum ada siswa PKL aktif pada periode ini.</div>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
