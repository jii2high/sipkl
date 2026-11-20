<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Pilih periode</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Form berlaku per program keahlian Anda dan per periode akademik.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Periode akademik
                    <select wire:model.live="periodId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Pilih periode</option>
                        @foreach ($this->periods() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('periodId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex items-end">
                    <x-filament::button wire:click="newDefinition" wire:loading.attr="disabled" class="w-full min-h-11">
                        Form baru
                    </x-filament::button>
                </div>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Form pada periode ini</h2>
            @forelse ($this->definitions() as $definition)
                <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-950 dark:text-white">
                                {{ $definition->nama }}
                                @if ($definitionId === $definition->getKey())
                                    <span class="ml-1 text-xs font-normal text-primary-600">sedang diedit</span>
                                @endif
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $definition->form_type }} · {{ $definition->items_count }} item
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($definition->is_published)
                                <span class="rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-700 dark:bg-success-400/10 dark:text-success-300">Dipublikasikan</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">Draf</span>
                            @endif
                            <x-filament::button size="sm" class="min-h-11" color="gray" wire:click="editDefinition({{ $definition->getKey() }})">
                                Buka
                            </x-filament::button>
                        </div>
                    </div>

                    @if ($definition->isLocked())
                        <p class="mt-3 rounded-lg bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-400/10 dark:text-warning-200" role="status">
                            Sudah ada pengisian. Buat versi baru untuk mengubah struktur.
                        </p>
                        <div class="mt-3">
                            <x-filament::button size="sm" class="min-h-11" wire:click="editDefinition({{ $definition->getKey() }})" wire:loading.attr="disabled">
                                Lihat struktur
                            </x-filament::button>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    Belum ada form untuk periode ini. Klik "Form baru" untuk mulai.
                </div>
            @endforelse
        </section>

        @if ($sections !== [])
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Struktur form</h2>

                @if ($notice)
                    <p class="mt-3 rounded-lg bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-400/10 dark:text-warning-200" role="status">{{ $notice }}</p>
                @endif
                @error('sections') <p class="mt-3 text-sm text-danger-600">{{ $message }}</p> @enderror

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nama form
                        <input type="text" wire:model="nama" maxlength="150" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Jenis form
                        <select wire:model="formType" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                            <option value="sidang">Sidang</option>
                            <option value="penilaian_industri">Penilaian industri</option>
                        </select>
                    </label>
                </div>

                <div class="mt-4 flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="hasKehadiran" class="rounded border-gray-300 dark:border-gray-700">
                        Sertakan Section Kehadiran
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="hasCatatan" class="rounded border-gray-300 dark:border-gray-700">
                        Sertakan Section Catatan
                    </label>
                </div>

                <div class="mt-5 space-y-4">
                    @foreach ($sections as $index => $section)
                        <div wire:key="section-{{ $index }}" class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <div class="flex items-end gap-3">
                                <label class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Judul section
                                    <input type="text" wire:model="sections.{{ $index }}.label" maxlength="150" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                                </label>
                                <x-filament::button size="sm" color="danger" class="min-h-11" wire:click="removeSection({{ $index }})">
                                    Hapus section
                                </x-filament::button>
                            </div>

                            <div class="mt-3 space-y-2">
                                @foreach ($section['items'] as $itemIndex => $item)
                                    <div wire:key="item-{{ $index }}-{{ $itemIndex }}" class="flex flex-wrap items-end gap-3">
                                        <label class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                                            Item {{ $itemIndex + 1 }}
                                            <input type="text" wire:model="sections.{{ $index }}.items.{{ $itemIndex }}.label" maxlength="255" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950">
                                        </label>
                                        <label class="flex items-center gap-2 pb-2 text-sm text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" wire:model="sections.{{ $index }}.items.{{ $itemIndex }}.has_alokasi_waktu" class="rounded border-gray-300 dark:border-gray-700">
                                            Alokasi waktu
                                        </label>
                                        <x-filament::button size="sm" color="gray" class="min-h-11" wire:click="removeItem({{ $index }}, {{ $itemIndex }})">
                                            Hapus
                                        </x-filament::button>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                <x-filament::button size="sm" color="gray" class="min-h-11" wire:click="addItem({{ $index }})">
                                    Tambah item
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <x-filament::button color="gray" class="min-h-11" wire:click="addSection" wire:loading.attr="disabled">
                        Tambah section
                    </x-filament::button>
                    <x-filament::button class="min-h-11" wire:click="save" wire:loading.attr="disabled">
                        Simpan struktur
                    </x-filament::button>
                    <x-filament::button class="min-h-11" wire:click="saveAndPublish" wire:loading.attr="disabled">
                        Simpan &amp; publikasikan
                    </x-filament::button>
                    @if ($definitionId !== null)
                        <x-filament::button color="gray" class="min-h-11" wire:click="createVersion" wire:loading.attr="disabled">
                            Buat versi baru
                        </x-filament::button>
                    @endif
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
