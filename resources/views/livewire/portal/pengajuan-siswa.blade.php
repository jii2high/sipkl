@php
    use App\Support\MaskedContact;

    $pengajuan = $this->pengajuan();
    $status = $this->status();
    $period = $this->period();
    $selectedCompany = $this->selectedCompany();
    $history = $this->history();
@endphp

<section class="flex flex-col gap-4">
    <div>
        <h1 class="text-[1.125rem] font-semibold">{{ __('pkl.portal.pengajuan_judul') }}</h1>
        @if ($period !== null)
            <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">
                Periode {{ $period->tahun_ajaran }} batch {{ $period->batch }}
            </p>
        @endif
    </div>

    @if ($flash !== null)
        <p
            role="status"
            @class([
                'rounded-[var(--radius-md)] border p-3 text-[0.875rem]',
                'border-[var(--success)] bg-[var(--success-soft)] text-[var(--success)]' => $flashTone === 'success',
                'border-[var(--danger)] bg-[var(--danger-soft)] text-[var(--danger)]' => $flashTone === 'danger',
                'border-[var(--border)] bg-[var(--surface-muted)] text-[var(--text)]' => ! in_array($flashTone, ['success', 'danger'], true),
            ])
        >{{ $flash }}</p>
    @endif

    @if ($period === null)
        <div class="pkl-card">{{ __('pkl.pengajuan.no_active_period') }}</div>
    @endif

    @if ($pengajuan !== null && $status !== null)
        <div class="pkl-card flex flex-col gap-3">
            <h2 class="text-[1.125rem] font-semibold">Status pengajuan</h2>

            <div class="flex flex-wrap items-center gap-2">
                <x-status-badge :status="$status->value" />
                <span class="font-medium">{{ $pengajuan->company?->nama ?? '—' }}</span>
            </div>

            @if ($pengajuan->pengajuan_pkl_id !== null && $pengajuan->pengajuanPkl?->nomor_surat !== null)
                <a
                    class="text-[0.875rem] font-medium text-[var(--primary)] underline"
                    href="{{ route('documents.download', ['jenis' => 'surat_pengantar', 'id' => $pengajuan->pengajuan_pkl_id]) }}"
                >
                    Unduh surat pengantar {{ $pengajuan->pengajuanPkl->nomor_surat }}
                </a>
            @endif

            <dl class="grid gap-1 text-[0.875rem]">
                <div class="flex flex-wrap gap-1">
                    <dt class="text-[var(--text-muted)]">Periode</dt>
                    <dd>{{ $pengajuan->academicPeriod?->tahun_ajaran }} batch {{ $pengajuan->academicPeriod?->batch }}</dd>
                </div>
                <div class="flex flex-wrap gap-1">
                    <dt class="text-[var(--text-muted)]">Narahubung</dt>
                    <dd>
                        {{ $pengajuan->company?->cp_name ?? '—' }}
                        @if ($pengajuan->company?->cp_phone)
                            · {{ $this->contactUnlocked() ? $pengajuan->company->cp_phone : MaskedContact::phone($pengajuan->company->cp_phone) }}
                        @endif
                    </dd>
                </div>
            </dl>

            @if (! $this->contactUnlocked())
                <p class="text-[0.8125rem] text-[var(--text-muted)]">{{ __('pkl.pengajuan.contact_locked') }}</p>
            @endif

            @if ($this->isWaiting())
                <p class="text-[0.875rem] text-[var(--text-muted)]">
                    Pengajuan sedang diproses. Pantau status di halaman ini.
                </p>
            @endif

            @if ($this->canReopen())
                <button type="button" class="pkl-btn pkl-btn-primary w-full" wire:click="reopen" wire:loading.attr="disabled">
                    <x-heroicon-o-arrow-path class="h-5 w-5" aria-hidden="true" />
                    Perbaiki pengajuan
                </button>
            @endif

            @if ($this->isWithdrawn())
                <button type="button" class="pkl-btn pkl-btn-secondary w-full" wire:click="startNew" wire:loading.attr="disabled">
                    <x-heroicon-o-document-text class="h-5 w-5" aria-hidden="true" />
                    Buat pengajuan baru
                </button>
            @endif
        </div>
    @endif

    @if ($this->canUploadSuratBalasan())
        <div class="pkl-card flex flex-col gap-3">
            <div>
                <h2 class="text-[1.125rem] font-semibold">Surat balasan perusahaan</h2>
                <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">
                    Unggah surat balasan resmi dari perusahaan. Setelah itu Kaprog memutuskan penerimaan.
                </p>
            </div>

            <label class="flex flex-col gap-1 text-[0.8125rem]">
                <span class="font-medium">Berkas surat balasan *</span>
                <input
                    type="file"
                    wire:model="suratBalasan"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="pkl-input"
                    @error('suratBalasan') aria-invalid="true" @enderror
                >
                <span class="text-[var(--text-muted)]">PDF/JPG/PNG maksimal {{ config('pkl.surat_balasan.max_mb') }} MB.</span>
                @error('suratBalasan') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
            </label>

            <label class="flex flex-col gap-1 text-[0.8125rem]">
                <span class="font-medium">Hasil dari perusahaan *</span>
                <select class="pkl-input" wire:model="suratBalasanHasil">
                    <option value="">Pilih hasil</option>
                    <option value="diterima">Diterima</option>
                    <option value="ditolak">Ditolak</option>
                </select>
                @error('suratBalasanHasil') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
            </label>

            <button
                type="button"
                class="pkl-btn pkl-btn-primary w-full"
                wire:click="uploadSuratBalasan"
                wire:loading.attr="disabled"
                wire:target="uploadSuratBalasan, suratBalasan"
            >
                <span wire:loading.remove wire:target="uploadSuratBalasan, suratBalasan">Unggah surat balasan</span>
                <span wire:loading wire:target="uploadSuratBalasan, suratBalasan">Mengunggah...</span>
            </button>
        </div>
    @endif

    @if ($this->isSedangPkl())
        <div class="pkl-card flex flex-col gap-3">
            <h2 class="text-[1.125rem] font-semibold">Pelaksanaan PKL</h2>

            <dl class="grid gap-1 text-[0.875rem]">
                <div class="flex flex-wrap gap-1">
                    <dt class="text-[var(--text-muted)]">Tanggal PKL</dt>
                    <dd>
                        {{ $pengajuan->tanggal_mulai_pkl?->format('d M Y') ?? '—' }}
                        s.d. {{ $pengajuan->tanggal_selesai_pkl?->format('d M Y') ?? '—' }}
                    </dd>
                </div>
                <div class="flex flex-wrap gap-1">
                    <dt class="text-[var(--text-muted)]">Dokumen fisik</dt>
                    <dd>{{ $pengajuan->is_provisional ? 'Belum diterima Hubin (provisional)' : 'Sudah diterima Hubin' }}</dd>
                </div>
            </dl>

            @if ($this->hasPendingWithdrawal())
                <p class="text-[0.875rem] text-[var(--warning)]">{{ __('pkl.pengajuan.withdraw_pending') }}</p>
            @else
                <label class="flex flex-col gap-1 text-[0.8125rem]">
                    <span class="font-medium">Ajukan penarikan PKL</span>
                    <textarea
                        class="pkl-input"
                        rows="3"
                        wire:model="withdrawReason"
                        placeholder="Tulis alasan penarikan"
                    ></textarea>
                    @error('withdraw_reason') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>

                <button type="button" class="pkl-btn pkl-btn-danger w-full" wire:click="requestWithdrawal" wire:loading.attr="disabled">
                    <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                    Ajukan penarikan
                </button>
            @endif
        </div>
    @endif

    @if ($period !== null && ($pengajuan === null || $this->isDraft()))
        <div class="pkl-card flex flex-col gap-4">
            <div>
                <h2 class="text-[1.125rem] font-semibold">Data perusahaan</h2>
                <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">
                    Pilih perusahaan terdaftar atau isi perusahaan baru. Kontak narahubung tersamar sampai Kaprog menyetujui.
                </p>
            </div>

            @if ($selectedCompany !== null && ! $newCompany)
                <div class="flex flex-col gap-1">
                    <p class="font-medium">{{ $selectedCompany->nama }}</p>
                    <p class="text-[0.875rem] text-[var(--text-muted)]">{{ $selectedCompany->alamat }}</p>
                    <p class="text-[0.875rem] text-[var(--text-muted)]">
                        Narahubung: {{ $selectedCompany->cp_name ?? '—' }}
                        @if ($selectedCompany->cp_phone)
                            · {{ $this->contactUnlocked() ? $selectedCompany->cp_phone : MaskedContact::phone($selectedCompany->cp_phone) }}
                        @endif
                    </p>
                    <button type="button" class="pkl-btn pkl-btn-secondary self-start" wire:click="clearCompany" wire:loading.attr="disabled">
                        <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                        Ganti perusahaan
                    </button>
                </div>
            @else
                @livewire(\App\Livewire\Support\CompanyAutocomplete::class)

                <label class="flex min-h-11 items-center gap-2 text-[0.875rem]">
                    <input type="checkbox" wire:model.live="newCompany" class="h-5 w-5">
                    <span>Perusahaan belum ada di daftar, saya isi manual</span>
                </label>

                @error('companyId') <p class="text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p> @enderror
            @endif

            @if ($newCompany)
                <div class="grid gap-3">
                    <label class="flex flex-col gap-1 text-[0.8125rem]">
                        <span class="font-medium">Nama perusahaan *</span>
                        <input type="text" class="pkl-input" wire:model="company.nama" maxlength="200" @error('company.nama') aria-invalid="true" @enderror>
                        @error('company.nama') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex flex-col gap-1 text-[0.8125rem]">
                        <span class="font-medium">Alamat *</span>
                        <textarea class="pkl-input" rows="3" wire:model="company.alamat" @error('company.alamat') aria-invalid="true" @enderror></textarea>
                        @error('company.alamat') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex flex-col gap-1 text-[0.8125rem]">
                        <span class="font-medium">Telepon/faks</span>
                        <input type="text" class="pkl-input" wire:model="company.telp_fax" maxlength="30" @error('company.telp_fax') aria-invalid="true" @enderror>
                        @error('company.telp_fax') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex flex-col gap-1 text-[0.8125rem]">
                        <span class="font-medium">Email</span>
                        <input type="email" class="pkl-input" wire:model="company.email" maxlength="150" @error('company.email') aria-invalid="true" @enderror>
                        @error('company.email') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex flex-col gap-1 text-[0.8125rem]">
                        <span class="font-medium">Website</span>
                        <input type="text" class="pkl-input" wire:model="company.website" maxlength="200" @error('company.website') aria-invalid="true" @enderror>
                        @error('company.website') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex flex-col gap-1 text-[0.8125rem]">
                        <span class="font-medium">Nama narahubung</span>
                        <input type="text" class="pkl-input" wire:model="company.cp_name" maxlength="150" @error('company.cp_name') aria-invalid="true" @enderror>
                        @error('company.cp_name') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex flex-col gap-1 text-[0.8125rem]">
                        <span class="font-medium">Telepon narahubung</span>
                        <input type="tel" class="pkl-input" wire:model="company.cp_phone" maxlength="30" @error('company.cp_phone') aria-invalid="true" @enderror>
                        @error('company.cp_phone') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                    </label>
                </div>
            @endif

            @if (count($similarityWarnings) > 0 && ! $similarityConfirmed)
                <div class="flex flex-col gap-2 rounded-[var(--radius-md)] border border-[var(--warning)] bg-[var(--warning-soft)] p-3 text-[0.875rem]">
                    <p class="font-medium">Periksa kemiripan perusahaan</p>
                    <p>{{ __('pkl.pengajuan.similar_confirm') }}</p>
                    <ul class="list-disc pl-5">
                        @foreach ($similarityWarnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                    <p>Bila yakin perusahaan ini berbeda, lanjutkan menyimpan draf.</p>
                    <button type="button" class="pkl-btn pkl-btn-primary self-start" wire:click="confirmSimilarity" wire:loading.attr="disabled">
                        <x-heroicon-o-check class="h-5 w-5" aria-hidden="true" />
                        Tetap simpan
                    </button>
                </div>
            @endif

            <div class="flex flex-col gap-2">
                <button type="button" class="pkl-btn pkl-btn-secondary w-full" wire:click="saveDraft" wire:loading.attr="disabled">
                    <x-heroicon-o-document-text class="h-5 w-5" aria-hidden="true" />
                    <span wire:loading.remove wire:target="saveDraft">Simpan draf</span>
                    <span wire:loading wire:target="saveDraft">Menyimpan...</span>
                </button>

                @if ($this->isDraft())
                    <button type="button" class="pkl-btn pkl-btn-primary w-full" wire:click="submit" wire:loading.attr="disabled">
                        <x-heroicon-o-paper-airplane class="h-5 w-5" aria-hidden="true" />
                        <span wire:loading.remove wire:target="submit">Kirim pengajuan</span>
                        <span wire:loading wire:target="submit">Mengirim...</span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    @if ($history !== [])
        <div class="pkl-card flex flex-col gap-3">
            <h2 class="text-[1.125rem] font-semibold">Riwayat pengajuan</h2>

            <ol class="flex flex-col gap-3">
                @foreach ($history as $activity)
                    <li class="flex flex-col gap-1 border-b border-[var(--border)] pb-3 text-[0.875rem] last:border-b-0 last:pb-0">
                        <span class="font-medium">{{ $activity->description }}</span>
                        <span class="text-[var(--text-muted)]">
                            {{ $activity->created_at?->timezone(config('pkl.timezone.operational'))->format('d M Y H:i') }} WIB
                        </span>
                        @if (filled(data_get($activity->properties, 'catatan')))
                            <span class="text-[var(--danger)]">Catatan: {{ data_get($activity->properties, 'catatan') }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</section>
