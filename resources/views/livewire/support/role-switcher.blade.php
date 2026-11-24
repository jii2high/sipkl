{{-- Pemilih peran aktif di header portal mobile. Root selalu ada
     agar Livewire punya tag akar; isi hanya tampil untuk pengguna multi-peran. --}}
<div class="flex items-center gap-2">
    @if (count($options) > 1)
        <label for="active-role" class="sr-only">Peran aktif</label>
        <select
            id="active-role"
            wire:change="switchTo($event.target.value)"
            class="min-h-[44px] rounded-lg border border-gray-300 bg-white px-3 text-[0.8125rem] font-medium text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
        >
            @foreach ($options as $value => $label)
                <option value="{{ $value }}" @selected($value === $active)>{{ $label }}</option>
            @endforeach
        </select>
    @endif
</div>
