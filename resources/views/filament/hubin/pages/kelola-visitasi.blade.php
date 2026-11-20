<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Buat surat tugas manual</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Surat manual masuk ke antrean persetujuan yang sama.</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Guru pembimbing
                    <select wire:model.live="guruId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Pilih guru</option>
                        @foreach ($this->teachers() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                    @error('guruId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Perusahaan
                    <select wire:model="companyIds" multiple size="5" @disabled($isGeneral) class="mt-1 block w-full rounded-lg border-gray-300 text-sm disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950">
                        @foreach ($this->companies() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                    @error('companyIds') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" wire:model.live="isGeneral" class="rounded border-gray-300 text-primary-600">Seluruh perusahaan bimbingan guru</label>
            <div class="mt-5 flex justify-end"><x-filament::button class="min-h-11" wire:click="createManual" wire:loading.attr="disabled">Buat pengajuan</x-filament::button></div>
        </section>

        <section class="space-y-3">
            @forelse ($this->visitations() as $visitasi)
                <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-gray-950 dark:text-white">{{ $visitasi->guru?->name }}</h2><x-status-badge :status="$visitasi->status" /></div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pengaju: {{ $visitasi->kaprog?->name ?? 'Hubin' }} · {{ $visitasi->is_general ? 'Seluruh perusahaan bimbingan' : $visitasi->companies->pluck('company.nama')->filter()->join(', ') }}</p>
                            @if ($visitasi->nomor_surat)
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Berlaku {{ $visitasi->tanggal_mulai->format('d/m/Y') }}–{{ $visitasi->tanggal_selesai->format('d/m/Y') }}</p>
                                <a class="mt-2 inline-block text-sm text-primary-600 underline" href="{{ route('documents.download', ['jenis' => 'surat_tugas_visitasi', 'id' => $visitasi->id]) }}">Unduh {{ $visitasi->nomor_surat }}</a>
                            @elseif ($visitasi->status === 'disetujui')
                                <p class="mt-2 text-sm text-warning-700">Nomor surat menunggu scan TTD Kepsek.</p>
                            @else
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Masa berlaku dihitung saat surat terbit.</p>
                            @endif
                            @if ($visitasi->rejection_note)<p class="mt-2 text-sm text-danger-700">Alasan: {{ $visitasi->rejection_note }}</p>@endif
                        </div>
                        @if ($visitasi->status === 'diajukan')
                            <div class="flex gap-2"><x-filament::button class="min-h-11" wire:click="approve({{ $visitasi->id }})">Setujui</x-filament::button><x-filament::button class="min-h-11" color="danger" wire:click="startReject({{ $visitasi->id }})">Tolak</x-filament::button></div>
                        @elseif ($visitasi->status === 'disetujui' && ! $visitasi->nomor_surat)
                            <x-filament::button class="min-h-11" color="gray" wire:click="issueLetter({{ $visitasi->id }})">Terbitkan surat</x-filament::button>
                        @endif
                    </div>
                    @if ($rejectingId === $visitasi->id)
                        <div class="mt-4 rounded-lg bg-danger-50 p-4 dark:bg-danger-400/10">
                            <label class="block text-sm font-medium">Alasan penolakan
                                <textarea wire:model="rejectionReason" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950"></textarea>
                            </label>
                            @error('rejectionReason') <span class="mt-1 block text-xs text-danger-700">{{ $message }}</span> @enderror
                            <div class="mt-3 flex justify-end gap-2"><x-filament::button class="min-h-11" color="gray" wire:click="$set('rejectingId', null)">Batal</x-filament::button><x-filament::button class="min-h-11" color="danger" wire:click="reject">Tolak pengajuan</x-filament::button></div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">Belum ada pengajuan visitasi.</div>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
