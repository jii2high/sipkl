<div class="mx-auto max-w-[720px]">
    <h1 class="text-[2rem] font-bold leading-tight tracking-[-0.02em]">{{ __('pkl.faq.judul') }}</h1>
    <p class="mt-3 max-w-[70ch] text-[var(--text-muted)]">{{ __('pkl.faq.petunjuk') }}</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-[1fr_220px]">
        <div>
            <label for="faq-search" class="block text-[0.8125rem] font-medium">{{ __('pkl.faq.cari') }}</label>
            <input
                id="faq-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('pkl.faq.cari_placeholder') }}"
                class="mt-1 h-11 w-full rounded-[var(--radius-md)] border border-[var(--border)] bg-[var(--surface)] px-3 text-[16px] text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-[var(--focus-ring)] focus:ring-offset-2 focus:ring-offset-[var(--bg)]"
            >
        </div>
        <div>
            <label for="faq-category" class="block text-[0.8125rem] font-medium">{{ __('pkl.faq.kategori') }}</label>
            <select
                id="faq-category"
                wire:model.live="category"
                class="mt-1 h-11 w-full rounded-[var(--radius-md)] border border-[var(--border)] bg-[var(--surface)] px-3 text-[16px] text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-[var(--focus-ring)] focus:ring-offset-2 focus:ring-offset-[var(--bg)]"
            >
                <option value="">{{ __('pkl.faq.semua_kategori') }}</option>
                @foreach ($categories as $item)
                    <option value="{{ $item->id }}">{{ $item->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <p class="mt-4 text-[0.8125rem] text-[var(--text-muted)]" aria-live="polite">
        {{ __('pkl.faq.hasil', ['total' => $faqs->total()]) }}
    </p>

    @if ($faqs->isEmpty())
        <div class="pkl-card mt-4" role="status">
            <p class="font-semibold">
                {{ $adaFilter ? __('pkl.faq.kosong_filter') : __('pkl.faq.kosong') }}
            </p>
            @if ($adaFilter)
                <button type="button" wire:click="resetFilter" class="pkl-btn pkl-btn-secondary mt-4">
                    <x-heroicon-o-arrow-path class="h-5 w-5" aria-hidden="true" />
                    {{ __('pkl.faq.reset') }}
                </button>
            @endif
        </div>
    @else
        <ul class="mt-4 space-y-3">
            @foreach ($faqs as $faq)
                <li class="pkl-card">
                    <details>
                        <summary class="cursor-pointer list-none text-[1.0625rem] font-semibold">
                            {{ $faq->pertanyaan }}
                        </summary>
                        <div class="mt-3 space-y-2">
                            <span class="pkl-chip pkl-chip-info">{{ $faq->category?->nama }}</span>
                            <p class="max-w-[70ch] whitespace-pre-line text-[var(--text-muted)]">{{ $faq->jawaban }}</p>
                        </div>
                    </details>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $faqs->links() }}
        </div>
    @endif

    <a href="{{ url('/') }}" class="pkl-btn pkl-btn-secondary mt-6">
        <x-heroicon-o-arrow-left class="h-5 w-5" aria-hidden="true" />
        {{ __('pkl.faq.kembali') }}
    </a>
</div>
