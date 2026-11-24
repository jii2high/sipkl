@php($tersembunyi = $this->hiddenCount())

<section class="flex flex-col gap-2">
    <div>
        <h1 class="text-[1.125rem] font-semibold">{{ __('pkl.monitor.kanban_judul') }}</h1>
        <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">{{ __('pkl.monitor.kanban_petunjuk') }}</p>
    </div>

    <div x-data="{ aktif: 1 }">
        <p class="text-[0.75rem] text-[var(--text-muted)] sm:hidden">
            {{ __('pkl.monitor.kanban_geser') }}
        </p>

        <div
            class="-mx-4 mt-2 overflow-x-auto px-4 pb-2"
            @scroll.debounce.100ms="aktif = Math.min(5, Math.max(1, Math.round($el.scrollLeft / 296) + 1))"
            tabindex="0"
            role="region"
            aria-label="{{ __('pkl.monitor.kanban_judul') }}"
        >
            <div class="flex min-w-max snap-x snap-mandatory gap-4">
                @foreach ($this->board() as $kolom)
                    <section class="w-[280px] shrink-0 snap-start overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)]">
                        <header class="flex items-center justify-between gap-2 border-b border-[var(--border)] px-3 py-2">
                            <h2 class="text-[0.8125rem] font-semibold">{{ $kolom['judul'] }}</h2>
                            <span class="rounded-full bg-[var(--surface-muted)] px-2 py-0.5 text-[0.75rem] font-medium tabular-nums text-[var(--text-muted)]">
                                {{ $kolom['items']->count() }}
                            </span>
                        </header>

                        <ul class="space-y-2 p-2">
                            @forelse ($kolom['items'] as $row)
                                <li wire:key="kanban-{{ $row->getKey() }}" class="rounded-[var(--radius-md)] border border-[var(--border)] p-3">
                                    <p class="text-[0.8125rem] font-medium">{{ $row->siswa?->name }}</p>
                                    <p class="mt-0.5 truncate text-[0.75rem] text-[var(--text-muted)]">{{ $row->company?->nama }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <x-status-badge :status="$row->status" />
                                        <span class="text-[0.75rem] text-[var(--text-muted)]">
                                            {{ __('pkl.monitor.menunggu', ['hari' => $this->waitingDays($row)]) }}
                                        </span>
                                    </div>
                                </li>
                            @empty
                                <li class="flex flex-col items-center gap-1 rounded-[var(--radius-md)] bg-[var(--surface-muted)] px-3 py-6 text-center text-[0.75rem] text-[var(--text-muted)]">
                                    <x-heroicon-o-view-columns class="h-6 w-6" aria-hidden="true" />
                                    <span>{{ __('pkl.monitor.kanban_kosong') }}</span>
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
                    :class="aktif === {{ $dot }} ? 'w-4 bg-[var(--primary)]' : 'w-1.5 bg-[var(--border)]'"
                ></span>
            @endfor
        </div>

        @if ($tersembunyi > 0)
            <p class="mt-4 text-[0.75rem] text-[var(--text-muted)]">
                {{ __('pkl.monitor.kanban_tersembunyi', ['total' => $tersembunyi]) }}
            </p>
        @endif
    </div>
</section>