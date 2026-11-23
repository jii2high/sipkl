<x-filament-panels::page>
    @php
        $signatures = $this->signatures();
    @endphp
    <div class="space-y-4 pb-24">
        @if ($bulkResult !== null)
            <div class="rounded-xl bg-white p-4 text-sm shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="font-medium text-gray-950 dark:text-white">
                    {{ count($bulkResult['approved']) }} laporan disetujui, {{ count($bulkResult['failed']) }} gagal.
                </p>
                @if ($bulkResult['failed'] !== [])
                    <ul class="mt-2 space-y-1 text-danger-700 dark:text-danger-400">
                        @foreach ($bulkResult['failed'] as $failure)
                            <li>#{{ $failure['id'] }}: {{ $failure['alasan'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @forelse ($signatures as $signature)
            @php
                $isInternalStep = in_array((int) $signature->step_order, [2, 5], true);
                $linkState = $this->linkState($signature);
            @endphp
            <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <label class="flex items-start gap-3">
                        @if ($isInternalStep)
                            <input
                                type="checkbox"
                                value="{{ $signature->getKey() }}"
                                wire:model="bulkIds"
                                class="mt-1 h-5 w-5 rounded border-gray-300"
                                aria-label="Pilih laporan {{ $signature->pengajuanSiswa?->siswa?->name }}"
                            >
                        @endif
                        <span>
                            <span class="block text-base font-semibold text-gray-950 dark:text-white">
                                {{ $signature->pengajuanSiswa?->siswa?->name }}
                            </span>
                            <span class="block text-sm text-gray-600 dark:text-gray-400">
                                {{ $signature->pengajuanSiswa?->company?->nama }} · Step {{ $signature->step_order }} — {{ $this->stepLabel($signature) }}
                            </span>
                        </span>
                    </label>
                    <x-status-badge :status="$signature->status" />
                </div>

                <div class="mt-4">
                    <x-signature-stepper :steps="$this->stepper($signature)" />
                </div>

                @if ($signature->laporanVersion)
                    <a
                        href="{{ route('laporan.download', ['version' => $signature->laporanVersion->getKey()]) }}"
                        class="mt-4 inline-flex min-h-11 items-center text-sm text-primary-600 underline"
                    >
                        Unduh berkas laporan versi {{ $signature->laporanVersion->version_number }}
                    </a>
                @endif

                @if ($isInternalStep)
                    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Catatan penolakan (wajib bila menolak, minimal 10 karakter)
                            <textarea
                                wire:model="rejectNotes.{{ $signature->getKey() }}"
                                rows="2"
                                maxlength="2000"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950"
                            ></textarea>
                        </label>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-filament::button class="min-h-11" wire:click="approve({{ $signature->getKey() }})" wire:loading.attr="disabled">
                                Setujui
                            </x-filament::button>
                            <x-filament::button class="min-h-11" color="danger" wire:click="reject({{ $signature->getKey() }})" wire:loading.attr="disabled">
                                Tolak
                            </x-filament::button>
                        </div>
                    </div>
                @else
                    <div class="mt-4 space-y-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @if ($linkState === 'issued')
                                {{ __('pkl.pengesahan.link_issued') }}
                            @elseif ($linkState === 'expired')
                                {{ __('pkl.pengesahan.link_expired') }}
                            @else
                                {{ __('pkl.pengesahan.link_none') }}
                            @endif
                        </p>

                        @if ($linkState === 'issued')
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Alasan terbitkan ulang (minimal 10 karakter)
                                <textarea
                                    wire:model="reasons.{{ $signature->getKey() }}"
                                    rows="2"
                                    maxlength="2000"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950"
                                ></textarea>
                            </label>
                            <x-filament::button class="min-h-11" color="gray" wire:click="reissue({{ $signature->getKey() }})" wire:loading.attr="disabled">
                                Terbitkan ulang tautan
                            </x-filament::button>
                        @else
                            <x-filament::button class="min-h-11" wire:click="issue({{ $signature->getKey() }})" wire:loading.attr="disabled">
                                Terbitkan tautan pengesahan
                            </x-filament::button>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10">
                Tidak ada laporan yang menunggu tanda tangan Anda.
            </div>
        @endforelse
    </div>

    @if ($signatures !== [])
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-gray-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ count($bulkIds) }} laporan dipilih
                </p>
                <x-filament::button
                    class="min-h-11"
                    wire:click="bulkApprove"
                    wire:loading.attr="disabled"
                    :disabled="count($bulkIds) === 0"
                >
                    Setujui ({{ count($bulkIds) }})
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>