<x-filament-panels::page>
    @php($tersembunyi = $this->hiddenCount())

    <div x-data="{ aktif: 1 }">
        <p class="text-xs text-gray-500 dark:text-gray-400 sm:hidden">
            {{ __('pkl.monitor.kanban_geser') }}
        </p>

        <div
            class="-mx-4 mt-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0"
            @scroll.debounce.100ms="aktif = Math.min(5, Math.max(1, Math.round($el.scrollLeft / 296) + 1))"
            tabindex="0"
            role="region"
            aria-label="{{ __('pkl.monitor.kanban_judul') }}"
        >
            <div class="flex min-w-max snap-x snap-mandatory gap-4">
                @foreach ($this->board() as $kolom)
                    <section class="w-[280px] shrink-0 snap-start overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                        <header class="flex items-center justify-between gap-2 border-b border-gray-200 px-3 py-2 dark:border-gray-700">
                            <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $kolom['judul'] }}</h2>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ $kolom['items']->count() }}
                            </span>
                        </header>

                        <ul class="space-y-2 p-2">
                            @forelse ($kolom['items'] as $row)
                                <li class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $row->siswa?->name }}</p>
                                    <p class="mt-0.5 truncate text-xs text-gray-600 dark:text-gray-300">{{ $row->company?->nama }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <x-status-badge :status="$row->status" />
                                        <span class="text-xs text-gray-600 dark:text-gray-300">
                                            {{ __('pkl.monitor.menunggu', ['hari' => $this->waitingDays($row)]) }}
                                        </span>
                                    </div>
                                </li>
                            @empty
                                <li class="rounded-lg bg-gray-50 px-3 py-6 text-center text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    {{ __('pkl.monitor.kanban_kosong') }}
                                </li>
                            @endforelse
                        </ul>
                    </section>
                @endforeach
            </div>
        </div>

        <div class="mt-2 flex items-center justify-center gap-1.5 sm:hidden" aria-hidden="true">
            @for ($dot = 1; $dot <= 5; $dot++)
                <span
                    class="h-1.5 rounded-full transition-all"
                    :class="aktif === {{ $dot }} ? 'w-4 bg-primary-600 dark:bg-primary-400' : 'w-1.5 bg-gray-300 dark:bg-gray-600'"
                ></span>
            @endfor
        </div>

        @if ($tersembunyi > 0)
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                {{ __('pkl.monitor.kanban_tersembunyi', ['total' => $tersembunyi]) }}
            </p>
        @endif
    </div>
</x-filament-panels::page>
