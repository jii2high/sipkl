<x-layouts.eksternal :title="'Tautan tidak berlaku'">
    <div class="space-y-6">
        <header>
            <h1 class="text-[1.5rem] font-semibold leading-tight">Tautan tidak berlaku</h1>
            <p class="mt-1 text-[var(--text-muted)]">{{ __('pkl.eksternal.invalid_link') }}</p>
        </header>

        <a href="{{ route('eksternal.link') }}" class="pkl-btn pkl-btn-primary min-h-11 w-full text-center">
            <x-heroicon-o-arrow-left class="h-5 w-5" aria-hidden="true" />
            Minta tautan baru
        </a>
    </div>
</x-layouts.eksternal>