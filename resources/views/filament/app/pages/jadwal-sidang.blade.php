<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Periode akademik
                    <select wire:model.live="periodId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Pilih periode</option>
                        @foreach ($this->periods() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Tetapkan penguji</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Penguji menerima tautan dan PIN 4 digit; tanpa akun panel.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama penguji
                    <input type="text" wire:model="nama" maxlength="150" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                    @error('nama') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    NIK (opsional)
                    <input type="text" wire:model="nik" maxlength="30" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                    @error('nik') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Jenis kontak
                    <select wire:model.live="contactType" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="email">Email</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $contactType === 'email' ? 'Email' : 'Nomor WhatsApp' }}
                    <input type="text" wire:model="contactValue" maxlength="150" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                    @error('contactValue') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-4 flex justify-end">
                <x-filament::button class="min-h-11" wire:click="saveExaminer" wire:loading.attr="disabled">Simpan penguji</x-filament::button>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Jadwalkan sidang</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Hanya siswa dengan laporan sah yang muncul di pool Siap Sidang.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Siswa
                    <select wire:model="pengajuanId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Pilih siswa</option>
                        @foreach ($this->pool() as $pengajuan)
                            <option value="{{ $pengajuan->getKey() }}">{{ $pengajuan->siswa?->name }} — {{ $pengajuan->company?->nama }}</option>
                        @endforeach
                    </select>
                    @error('pengajuanId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Penguji
                    <select wire:model="examinerId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Pilih penguji</option>
                        @foreach ($this->examiners() as $examiner)
                            <option value="{{ $examiner->getKey() }}">{{ $examiner->nama }}</option>
                        @endforeach
                    </select>
                    @error('examinerId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tanggal sidang
                    <input type="date" wire:model="tanggal" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                    @error('tanggal') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-4 flex justify-end">
                <x-filament::button class="min-h-11" wire:click="schedule" wire:loading.attr="disabled">Jadwalkan</x-filament::button>
            </div>
            @if ($this->pool()->isEmpty())
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada siswa di pool Siap Sidang untuk periode ini.</p>
            @endif
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Jadwal pada periode ini</h2>

            @forelse ($this->schedules() as $jadwal)
                <article wire:key="jadwal-{{ $jadwal->getKey() }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-950 dark:text-white">{{ $jadwal->pengajuanSiswa?->siswa?->name }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $jadwal->tanggal->format('d/m/Y') }} · urutan {{ $jadwal->urutan }} · {{ $jadwal->externalExaminer?->nama }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Status: {{ $jadwal->status }}
                                @if ($jadwal->submission?->is_submitted)
                                    · nilai terkunci ({{ $jadwal->submission->nilai_total }})
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-filament::button size="sm" class="min-h-11" color="gray" wire:click="issueLink({{ $jadwal->getKey() }})" wire:loading.attr="disabled">
                                Terbitkan tautan
                            </x-filament::button>
                            <x-filament::button size="sm" class="min-h-11" color="gray" wire:click="startRevoke({{ $jadwal->getKey() }})">
                                Cabut tautan
                            </x-filament::button>
                            <x-filament::button size="sm" class="min-h-11" color="gray" wire:click="startShift({{ $jadwal->getKey() }})">
                                Pindah
                            </x-filament::button>
                        </div>
                    </div>

                    @if ($revokeId === $jadwal->getKey())
                        <div class="mt-4 grid gap-3 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 md:grid-cols-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 md:col-span-2">
                                Alasan pencabutan (minimal 10 karakter)
                                <input type="text" wire:model="revokeReason" maxlength="500" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                                @error('revokeReason') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <div class="flex items-end">
                                <x-filament::button size="sm" class="min-h-11" color="danger" wire:click="revokeLink" wire:loading.attr="disabled">
                                    Cabut tautan
                                </x-filament::button>
                            </div>
                        </div>
                    @endif

                    @if ($shiftId === $jadwal->getKey())
                        <div class="mt-4 grid gap-3 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 md:grid-cols-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tanggal baru
                                <input type="date" wire:model="shiftTanggal" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                                @error('shiftTanggal') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <div class="flex items-end gap-2 md:col-span-2">
                                <x-filament::button size="sm" class="min-h-11" wire:click="saveShift" wire:loading.attr="disabled">Simpan perpindahan</x-filament::button>
                                <x-filament::button
                                    size="sm"
                                    class="min-h-11"
                                    color="danger"
                                    wire:click="cancelShift"
                                    wire:confirm="Batalkan jadwal sidang ini? Sisa antrean pada tanggal tersebut akan bergeser."
                                >
                                    Batalkan jadwal
                                </x-filament::button>
                            </div>
                            @error('jadwal') <p class="text-sm text-danger-600 md:col-span-3">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    Belum ada jadwal sidang pada periode ini.
                </div>
            @endforelse
        </section>

        @if ($waUrl)
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Kirim tautan lewat WhatsApp</h2>
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-11 items-center text-sm font-medium text-primary-600 underline">
                    Buka WhatsApp dengan pesan siap kirim
                </a>
            </section>
        @endif
    </div>
</x-filament-panels::page>