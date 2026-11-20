@php
    $pengajuan = $this->pengajuan();
    $progress = $this->progress();
    $rejected = $this->rejectedSignature();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        @if ($pengajuan === null)
            <div class="rounded-xl bg-white p-6 text-sm text-gray-600 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10">
                Bimbingan tersedia setelah kamu berstatus Sedang PKL.
            </div>
        @else
            @if ($pengajuan->is_provisional)
                <div class="rounded-xl bg-warning-50 p-4 text-sm text-warning-800 ring-1 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-300">
                    Bimbingan terkunci: dokumen fisik belum dikonfirmasi Hubin.
                </div>
            @endif

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Progres bimbingan</h2>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('pkl.bimbingan.progress', $progress) }}</span>
                </div>
                @if ($progress['count'] >= $progress['min'])
                    <p class="mt-2 text-sm text-success-700 dark:text-success-400">Kamu bisa menandai bagian akhir laporan.</p>
                @else
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Unggah bimbingan sampai minimal {{ $progress['min'] }} interaksi sebelum menandai bagian akhir.</p>
                @endif
            </section>

            @if ($rejected !== null)
                <section class="rounded-xl bg-danger-50 p-5 ring-1 ring-danger-600/20 dark:bg-danger-500/10">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Revisi laporan</h2>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        Step {{ $rejected->step_order }} ({{ $rejected->signer_type }}) ditolak dengan catatan:
                        “{{ $rejected->rejection_note }}”
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Unggah revisi. Rantai pengesahan dilanjutkan langsung dari step {{ $rejected->step_order }}.
                    </p>
                    <div class="mt-4 space-y-3">
                        <input type="file" wire:model="revisionFile" accept=".pdf,.doc,.docx" class="block w-full text-sm">
                        @error('revisionFile') <span class="block text-xs text-danger-600">{{ $message }}</span> @enderror
                        <x-filament::button class="min-h-11" wire:click="uploadRevision" wire:loading.attr="disabled">
                            Unggah revisi laporan
                        </x-filament::button>
                    </div>
                </section>
            @endif

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Unggah bimbingan</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Berkas PDF/DOC/DOCX maksimal 10 MB. Berkas disimpan privat.</p>
                <div class="mt-4 space-y-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Berkas bimbingan
                        <input type="file" wire:model="file" accept=".pdf,.doc,.docx" class="mt-1 block w-full text-sm" @disabled($pengajuan->is_provisional)>
                        @error('file') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Keterangan
                        <textarea wire:model="keterangan" rows="2" maxlength="2000" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950" @disabled($pengajuan->is_provisional)></textarea>
                        @error('keterangan') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <div class="flex justify-end">
                        <x-filament::button class="min-h-11" wire:click="submit" wire:loading.attr="disabled" :disabled="$pengajuan->is_provisional">
                            Unggah bimbingan
                        </x-filament::button>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                @forelse ($this->submissions() as $submission)
                    <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-status-badge :status="$submission->status" />
                                @if ($submission->is_bagian_akhir)
                                    <x-status-badge status="bagian_akhir" :label="'Bagian akhir'" />
                                @endif
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $submission->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($submission->keterangan)
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $submission->keterangan }}</p>
                        @endif
                        @if ($submission->feedback_note)
                            <p class="mt-2 rounded-lg bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-950 dark:text-gray-300">Feedback guru: {{ $submission->feedback_note }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <a class="text-sm text-primary-600 underline" href="{{ route('bimbingan.download', ['id' => $submission->id]) }}">Unduh berkas</a>
                            @if (! $submission->is_bagian_akhir && $progress['count'] >= $progress['min'])
                                <x-filament::button class="min-h-11" color="gray" wire:click="markFinal({{ $submission->id }})">
                                    Tandai bagian akhir
                                </x-filament::button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        Belum ada bimbingan yang diunggah.
                    </div>
                @endforelse
            </section>
        @endif
    </div>
</x-filament-panels::page>
