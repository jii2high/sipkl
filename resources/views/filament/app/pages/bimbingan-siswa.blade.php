<x-filament-panels::page>
    <div class="space-y-6">
        @if ($errors->any())
            <div class="rounded-xl bg-danger-50 p-4 text-sm text-danger-800 dark:bg-danger-500/10 dark:text-danger-300">
                {{ $errors->first() }}
            </div>
        @endif

        @forelse ($this->students() as $pengajuan)
            @php
                $subs = $pengajuan->bimbinganSubmissions->where('is_archived', false)->sortByDesc('id');
                $final = $subs->firstWhere('is_bagian_akhir', true);
                $canApprove = $final !== null && $final->status === 'diterima' && $pengajuan->laporanVersions->isEmpty();
            @endphp
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $pengajuan->siswa?->name }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pengajuan->company?->nama }}</p>
                    </div>
                    <x-status-badge :status="$pengajuan->status" />
                </div>

                @if ($subs->isEmpty())
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Belum ada bimbingan dari siswa ini.</p>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($subs as $submission)
                            <li class="rounded-lg bg-gray-50 p-4 dark:bg-gray-950">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-status-badge :status="$submission->status" />
                                        @if ($submission->is_bagian_akhir)
                                            <x-status-badge status="bagian_akhir" :label="'Bagian akhir'" />
                                        @endif
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $submission->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <a class="text-sm text-primary-600 underline" href="{{ route('bimbingan.download', ['id' => $submission->id]) }}">Unduh berkas</a>
                                </div>
                                @if ($submission->keterangan)
                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $submission->keterangan }}</p>
                                @endif
                                @if ($submission->feedback_note)
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Feedback: {{ $submission->feedback_note }}</p>
                                @endif

                                @if ($submission->status === 'menunggu_feedback')
                                    <div class="mt-3 space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Catatan feedback (opsional)
                                            <textarea wire:model="note" rows="2" maxlength="2000" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"></textarea>
                                        </label>
                                        @error('note') <span class="block text-xs text-danger-600">{{ $message }}</span> @enderror
                                        <div class="flex flex-wrap gap-2">
                                            <x-filament::button class="min-h-11" wire:click="giveFeedback({{ $submission->id }}, 'diterima')" wire:loading.attr="disabled">Terima</x-filament::button>
                                            <x-filament::button class="min-h-11" color="warning" wire:click="giveFeedback({{ $submission->id }}, 'revisi')" wire:loading.attr="disabled">Minta revisi</x-filament::button>
                                        </div>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($canApprove)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Bagian akhir sudah diterima. ACC final membuka rantai pengesahan 5-TTD.</p>
                        <x-filament::button class="min-h-11" wire:click="approveFinal({{ $pengajuan->id }}, {{ $final->id }})" wire:loading.attr="disabled">
                            Approve Laporan Final
                        </x-filament::button>
                    </div>
                @endif
            </section>
        @empty
            <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                Belum ada siswa bimbingan aktif.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
