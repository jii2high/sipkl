<x-layouts.public :title="'Beranda — SIM PKL ' . config('pkl.school.name')">
    <h1 class="text-[2rem] font-bold leading-tight tracking-[-0.02em]">
        Sistem Informasi Manajemen PKL
    </h1>
    <p class="mt-4 max-w-[70ch] text-[var(--text-muted)]">
        {{ config('pkl.school.name') }}. Satu tempat untuk alur pengajuan, persuratan,
        bimbingan, pengesahan lima tanda tangan, sampai sidang dan penilaian.
    </p>

    {{--
        Landing page lengkap (hero, alur, tautan FAQ) dikerjakan pada T9.2.
        Hanya konten yang sudah ada yang ditampilkan di sini.
    --}}
    <p class="mt-6 text-[0.8125rem] text-[var(--text-muted)]">
        Halaman informasi publik sedang disiapkan.
    </p>
</x-layouts.public>
