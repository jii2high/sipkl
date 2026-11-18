{{-- Autocomplete perusahaan (T2.1): dipakai form pengajuan siswa di T4.1. --}}
<div class="space-y-2">
    <label for="company-search" class="block text-sm font-medium">Cari perusahaan</label>

    <input
        id="company-search"
        type="text"
        wire:model.live.debounce.300ms="search"
        placeholder="Nama atau alamat perusahaan"
        autocomplete="off"
        class="block w-full min-h-11 rounded-md border border-neutral-300 px-3 text-base dark:border-neutral-600 dark:bg-neutral-800"
    >

    @if (mb_strlen(trim($search)) >= 2 && $selectedId === null)
        @if (count($companies) === 0)
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                Tidak ada perusahaan yang cocok. Kamu boleh mengetik perusahaan baru.
            </p>
        @else
            <ul class="divide-y divide-neutral-200 rounded-md border border-neutral-200 dark:divide-neutral-700 dark:border-neutral-700">
                @foreach ($companies as $company)
                    <li>
                        <button
                            type="button"
                            wire:click="select({{ $company['id'] }})"
                            class="flex min-h-11 w-full flex-col items-start px-3 py-2 text-left hover:bg-neutral-100 dark:hover:bg-neutral-700"
                        >
                            <span class="font-medium">{{ $company['nama'] }}</span>
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ $company['alamat'] }}</span>
                            @if ($company['telepon'] !== null)
                                <span class="text-sm text-neutral-500 dark:text-neutral-500">Telp: {{ $company['telepon'] }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

    @if ($selectedId !== null)
        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
            Perusahaan dipilih: {{ $selectedName }}
        </p>
    @endif
</div>
