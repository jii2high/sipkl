<x-filament-panels::page>
    <div class="space-y-6">
        @if (session('active_role') === 'kaprog')
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Ajukan surat tugas visitasi</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Hubin akan meninjau pengajuan. Masa berlaku default tujuh hari kalender.</p>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Guru pembimbing
                        <select wire:model.live="guruId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                            <option value="">Pilih guru</option>
                            @foreach ($this->teachers() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                        @error('guruId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Perusahaan
                        <select wire:model="companyIds" multiple size="5" @disabled($isGeneral) class="mt-1 block w-full rounded-lg border-gray-300 text-sm disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950">
                            @foreach ($this->companies() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                        @error('companyIds') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                        @error('companyIds.*') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                </div>
                <label class="mt-4 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="isGeneral" class="rounded border-gray-300 text-primary-600">
                    Berlaku untuk seluruh perusahaan bimbingan guru
                </label>
                <div class="mt-5 flex justify-end">
                    <x-filament::button class="min-h-11" wire:click="submitRequest" wire:loading.attr="disabled">Kirim pengajuan</x-filament::button>
                </div>
            </section>
        @endif

        @if (session('active_role') === 'guru_pembimbing' && $selectedVisitasiId)
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div><h2 class="text-base font-semibold text-gray-950 dark:text-white">Unggah dokumentasi kunjungan</h2><p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Gunakan foto JPG atau PNG, maksimal 5 MB.</p></div>
                    <x-filament::button class="min-h-11" color="gray" wire:click="$set('selectedVisitasiId', null)">Tutup</x-filament::button>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Perusahaan
                        <select wire:model="uploadCompanyId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                            <option value="">Pilih perusahaan</option>
                            @foreach ($this->uploadCompanies() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                        @error('uploadCompanyId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal kunjungan
                        <input type="date" wire:model="visitDate" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        @error('visitDate') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Foto dokumentasi
                        <input type="file" wire:model="photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="mt-1 block w-full text-sm">
                        @error('photo') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan
                        <textarea wire:model="note" rows="2" maxlength="2000" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950"></textarea>
                        @error('note') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                </div>
                <div class="mt-5 flex justify-end"><x-filament::button class="min-h-11" wire:click="uploadDocumentation" wire:loading.attr="disabled">Simpan dokumentasi</x-filament::button></div>
            </section>
        @endif

        <section class="space-y-3">
            @forelse ($this->visitations() as $visitasi)
                <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-gray-950 dark:text-white">{{ $visitasi->guru?->name ?? 'Guru' }}</h2>
                                <x-status-badge :status="$visitasi->status" />
                            </div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $visitasi->is_general ? 'Seluruh perusahaan bimbingan' : $visitasi->companies->pluck('company.nama')->filter()->join(', ') }}</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Berlaku {{ $visitasi->tanggal_mulai->format('d/m/Y') }}–{{ $visitasi->tanggal_selesai->format('d/m/Y') }}</p>
                            @if ($visitasi->nomor_surat)
                                <a class="mt-2 inline-block text-sm text-primary-600 underline" href="{{ route('documents.download', ['jenis' => 'surat_tugas_visitasi', 'id' => $visitasi->id]) }}">Unduh {{ $visitasi->nomor_surat }}</a>
                            @endif
                            @if ($visitasi->rejection_note)<p class="mt-2 text-sm text-danger-700">Alasan: {{ $visitasi->rejection_note }}</p>@endif
                        </div>
                        @if (session('active_role') === 'guru_pembimbing' && in_array($visitasi->status, ['disetujui', 'dicetak', 'aktif'], true) && $visitasi->tanggal_selesai->toDateString() >= now()->timezone('Asia/Jakarta')->toDateString())
                            <x-filament::button class="min-h-11" color="gray" wire:click="selectUpload({{ $visitasi->id }})">Unggah dokumentasi</x-filament::button>
                        @endif
                    </div>
                    @if ($visitasi->dokumentasi->isNotEmpty())
                        <div class="mt-4 border-t border-gray-200 pt-3 dark:border-gray-800">
                            <h3 class="text-sm font-medium">Dokumentasi tersimpan</h3>
                            <ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                @foreach ($visitasi->dokumentasi as $item)
                                    <li><a class="text-primary-600 underline" href="{{ route('visitasi.documentation.download', ['id' => $item->id]) }}">{{ $item->company?->nama }} · {{ $item->tanggal_kunjungan->format('d/m/Y') }}</a>{{ $item->keterangan ? ' · '.$item->keterangan : '' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">Belum ada surat tugas visitasi.</div>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
