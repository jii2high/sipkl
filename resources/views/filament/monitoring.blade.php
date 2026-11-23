<x-filament-panels::page>
    @php
        $gates = $this->gates();
        $steps = $this->ttdSteps();
    @endphp

    <div class="grid gap-4 xl:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-950 dark:border-gray-700 dark:text-white">
                {{ __('pkl.monitor.gate') }}
            </h2>

            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($gates as $gate)
                    <li class="px-4 py-3">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">
                                {{ \App\Support\StatusChip::label($gate['status']) }}
                            </span>
                            <span class="text-sm tabular-nums text-gray-700 dark:text-gray-200">
                                {{ $gate['jumlah'] }} pengajuan
                            </span>
                        </div>

                        @if ($gate['jumlah'] > 0)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('pkl.monitor.ringkas', ['rata' => $gate['rata'], 'terlama' => $gate['terlama']]) }}
                            </p>
                        @endif

                        @foreach ($gate['kuota'] as $warning)
                            <span class="pkl-chip pkl-chip-{{ $warning['tone'] }} mt-2">
                                {{ $warning['tone'] === 'danger' ? __('pkl.monitor.kuota_penuh') : __('pkl.monitor.kuota_hampir') }}:
                                {{ $warning['nama'] }} ({{ $warning['used'] }}/{{ $warning['maks'] }})
                            </span>
                        @endforeach
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-950 dark:border-gray-700 dark:text-white">
                {{ __('pkl.monitor.ttd') }}
            </h2>

            @if (collect($steps)->sum('pending') === 0)
                <p class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">{{ __('pkl.monitor.kosong') }}</p>
            @else
                <ol class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($steps as $step)
                        <li class="px-4 py-3">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <span class="text-sm font-medium text-gray-950 dark:text-white">
                                    {{ $step['step'] }}. {{ $step['label'] }}
                                </span>
                                <span class="text-sm tabular-nums text-gray-700 dark:text-gray-200">
                                    {{ $step['pending'] }}
                                </span>
                            </div>

                            @if ($step['pending'] > 0)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('pkl.monitor.ringkas', ['rata' => $step['rata'], 'terlama' => $step['terlama']]) }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">{{ __('pkl.monitor.catatan') }}</p>
</x-filament-panels::page>
