<x-filament-panels::page>
    <div class="space-y-6">
        @if ($issuedUrl)
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Tautan siap dibagikan</h2>
                <p class="mt-2 break-all text-sm text-primary-600">{{ $issuedUrl }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tautan juga dikirim ke email pembimbing industri.</p>
            </section>
        @endif

        <section class="space-y-3">
            @forelse ($this->submissions() as $submission)
                @php($state = $submission->industryLinkState())
                <article wire:key="industri-hubin-{{ $submission->getKey() }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-950 dark:text-white">{{ $submission->company?->nama }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $submission->definition?->nama }} · {{ $submission->academicPeriod?->tahun_ajaran }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Status tautan: {{ $this->stateLabels()[$state] ?? $state }}
                                @if ($submission->nilai_total !== null)
                                    · nilai {{ $submission->nilai_total }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($state === 'active')
                                <x-filament::button size="sm" class="min-h-11" color="gray" wire:click="startRevoke({{ $submission->getKey() }})">
                                    Cabut / terbitkan ulang
                                </x-filament::button>
                            @elseif ($state === 'none' || $state === 'expired')
                                <x-filament::button size="sm" class="min-h-11" wire:click="issue({{ $submission->getKey() }})" wire:loading.attr="disabled">
                                    Terbitkan tautan
                                </x-filament::button>
                            @endif
                        </div>
                    </div>

                    <ul class="mt-3 flex flex-wrap gap-2 text-sm text-gray-600 dark:text-gray-400">
                        @foreach ($submission->students as $row)
                            <li class="rounded-full bg-gray-100 px-3 py-1 dark:bg-white/5">{{ $row->pengajuanSiswa?->siswa?->name }}</li>
                        @endforeach
                    </ul>

                    @if ($actionId === $submission->getKey())
                        <div class="mt-4 grid gap-3 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 md:grid-cols-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 md:col-span-2">
                                Alasan override Hubin (minimal 10 karakter)
                                <input type="text" wire:model="reason" maxlength="500" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                                @error('reason') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                                @error('alasan') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                                @error('tautan') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <div class="flex items-end gap-2">
                                <x-filament::button size="sm" class="min-h-11" color="danger" wire:click="revoke" wire:loading.attr="disabled">
                                    Cabut
                                </x-filament::button>
                                <x-filament::button size="sm" class="min-h-11" color="gray" wire:click="reissue" wire:loading.attr="disabled">
                                    Terbitkan ulang
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    Belum ada tautan penilaian industri yang diterbitkan.
                </div>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>