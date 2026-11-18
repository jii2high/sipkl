<x-filament-panels::page>
    {{ $this->content }}

    @if ($hasil)
        <x-filament::section
            heading="Hasil impor {{ $hasil['jenis'] }}"
            description="{{ $hasil['ringkasan'] }}"
        >
            <div class="flex flex-wrap gap-2">
                <x-filament::badge color="success">{{ $hasil['total'] }} baris diproses</x-filament::badge>
                <x-filament::badge color="success">{{ count($hasil['berhasil']) }} berhasil</x-filament::badge>

                @if (count($hasil['gagal']) > 0)
                    <x-filament::badge color="danger">{{ count($hasil['gagal']) }} gagal</x-filament::badge>
                @endif

                @if (count($hasil['perlu_tinjau']) > 0)
                    <x-filament::badge color="warning">{{ count($hasil['perlu_tinjau']) }} perlu tinjau</x-filament::badge>
                @endif
            </div>

            @if (count($hasil['gagal']) > 0)
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Baris gagal</h3>
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                    <th class="py-2 pr-4">Baris</th>
                                    <th class="py-2">Pesan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hasil['gagal'] as $gagal)
                                    <tr class="border-t border-gray-200 dark:border-white/10">
                                        <td class="py-2 pr-4 font-mono">{{ $gagal['baris'] }}</td>
                                        <td class="py-2 text-danger-600 dark:text-danger-400">{{ $gagal['pesan'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (count($hasil['perlu_tinjau']) > 0)
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Perlu tinjau (kemungkinan duplikat)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Baris berikut tetap disimpan, tetapi mirip data yang sudah ada. Periksa dan gabungkan bila memang duplikat.
                    </p>
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                    <th class="py-2 pr-4">Baris</th>
                                    <th class="py-2 pr-4">Data</th>
                                    <th class="py-2">Mirip dengan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hasil['perlu_tinjau'] as $tinjau)
                                    <tr class="border-t border-gray-200 dark:border-white/10">
                                        <td class="py-2 pr-4 font-mono">{{ $tinjau['baris'] }}</td>
                                        <td class="py-2 pr-4">{{ $tinjau['label'] }}</td>
                                        <td class="py-2 text-warning-600 dark:text-warning-400">{{ $tinjau['saran'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (count($hasil['berhasil']) > 0)
                <details class="mt-6">
                    <summary class="cursor-pointer text-sm font-semibold text-gray-950 dark:text-white">
                        Lihat baris berhasil ({{ count($hasil['berhasil']) }})
                    </summary>
                    <div class="mt-2 max-h-64 overflow-y-auto overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                    <th class="py-2 pr-4">Baris</th>
                                    <th class="py-2">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hasil['berhasil'] as $berhasil)
                                    <tr class="border-t border-gray-200 dark:border-white/10">
                                        <td class="py-2 pr-4 font-mono">{{ $berhasil['baris'] }}</td>
                                        <td class="py-2">{{ $berhasil['label'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
