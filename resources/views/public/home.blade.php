<x-layouts.public :title="__('pkl.home.judul').' — '.config('pkl.school.name')">
    <section class="py-4 sm:py-10">
        <p class="text-[0.8125rem] font-medium uppercase tracking-[0.08em] text-[var(--primary)]">
            {{ config('pkl.school.name') }}
        </p>
        <h1 class="mt-2 max-w-[20ch] text-[2rem] font-bold leading-tight tracking-[-0.02em]">
            {{ __('pkl.home.judul') }}
        </h1>
        <p class="mt-4 max-w-[70ch] text-[var(--text-muted)]">
            {{ __('pkl.home.ringkas', ['school' => config('pkl.school.name')]) }}
        </p>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('login') }}" class="pkl-btn pkl-btn-primary">
                <x-heroicon-o-arrow-right-end-on-rectangle class="h-5 w-5" aria-hidden="true" />
                {{ __('pkl.home.masuk') }}
            </a>
            <a href="{{ route('faq') }}" class="pkl-btn pkl-btn-secondary">
                <x-heroicon-o-question-mark-circle class="h-5 w-5" aria-hidden="true" />
                {{ __('pkl.home.faq') }}
            </a>
        </div>
    </section>

    <section aria-labelledby="alur-pkl" class="mt-4">
        <h2 id="alur-pkl" class="text-[1.5rem] font-semibold leading-tight tracking-[-0.01em]">
            {{ __('pkl.home.alur_judul') }}
        </h2>

        <ol class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach (__('pkl.home.alur') as $index => $langkah)
                <li class="pkl-card flex gap-4">
                    <span class="font-mono text-[0.9375rem] font-semibold text-[var(--primary)]" aria-hidden="true">
                        {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <p class="text-[0.9375rem]">{{ $langkah }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <p class="mt-6 max-w-[70ch] text-[0.8125rem] text-[var(--text-muted)]">
        Surat pengantar dan surat tugas memakai QR verifikasi. Pindai QR pada surat untuk membuka halaman
        verifikasi resmi sekolah.
    </p>
</x-layouts.public>
