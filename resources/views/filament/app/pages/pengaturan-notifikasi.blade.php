<x-filament-panels::page>
    <div class="max-w-[560px] rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="text-sm font-medium text-gray-950 dark:text-white">
                {{ match ($pushOptIn) {
                    true => __('pkl.push.status_aktif'),
                    false => __('pkl.push.status_nonaktif'),
                    default => __('pkl.push.status_belum'),
                } }}
            </span>

            @if ($pushOptIn === true)
                <x-filament::button color="gray" wire:click="setPush(false)">
                    {{ __('pkl.push.matikan') }}
                </x-filament::button>
            @else
                <x-filament::button wire:click="setPush(true)">
                    {{ __('pkl.push.aktifkan') }}
                </x-filament::button>
            @endif
        </div>

        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('pkl.push.catatan') }}</p>
    </div>
</x-filament-panels::page>
