@php
    use App\Support\MaskedContact;
    use App\Support\StatusChip;

    $pengajuan = $this->pengajuan();
    $status = $this->status();
    $period = $this->period();
    $selectedCompany = $this->selectedCompany();
@endphp

<x-filament-panels::page>
    <div class="grid gap-6">
        @if ($period === null)
            <x-filament::section>
                <p>{{ __('pkl.pengajuan.no_active_period') }}</p>
            </x-filament::section>
        @endif

        @if ($pengajuan !== null && $status !== null)
            <x-filament::section heading="Status pengajuan">
                <div class="grid gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-filament::badge :color="StatusChip::color($status->value)">
                            {{ $status->label() }}
                        </x-filament::badge>
                        <span>{{ $pengajuan->company?->nama ?? '—' }}</span>
                    </div>

                    @if ($pengajuan->pengajuan_pkl_id !== null && $pengajuan->pengajuanPkl?->nomor_surat !== null)
                        <div>
                            <a
                                class="text-sm font-medium text-primary-600 underline dark:text-primary-400"
                                href="{{ route('documents.download', ['jenis' => 'surat_pengantar', 'id' => $pengajuan->pengajuan_pkl_id]) }}"
                            >
                                Unduh surat pengantar {{ $pengajuan->pengajuanPkl->nomor_surat }}
                            </a>
                        </div>
                    @endif

                    <dl class="grid gap-2 text-sm">
                        <div class="flex flex-wrap gap-2">
                            <dt class="font-medium">Periode</dt>
                            <dd>{{ $pengajuan->academicPeriod?->tahun_ajaran }} batch {{ $pengajuan->academicPeriod?->batch }}</dd>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <dt class="font-medium">Narahubung</dt>
                            <dd>
                                {{ $pengajuan->company?->cp_name ?? '—' }}
                                @if ($pengajuan->company?->cp_phone)
                                    · {{ $this->contactUnlocked() ? $pengajuan->company->cp_phone : MaskedContact::phone($pengajuan->company->cp_phone) }}
                                @endif
                            </dd>
                        </div>
                        @if (! $this->contactUnlocked())
                            <p class="text-sm text-gray-500">{{ __('pkl.pengajuan.contact_locked') }}</p>
                        @endif
                    </dl>

                    @if ($this->isWaiting())
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Pengajuan sedang diproses. Kamu dapat memantau status di halaman ini.
                        </p>
                    @endif

                    @if ($this->canReopen())
                        <div>
                            <x-filament::button wire:click="reopen" wire:loading.attr="disabled">
                                Perbaiki pengajuan
                            </x-filament::button>
                        </div>
                    @endif

                    @if ($this->isWithdrawn())
                        <div>
                            <x-filament::button wire:click="startNew" wire:loading.attr="disabled">
                                Buat pengajuan baru
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        @if ($this->canUploadSuratBalasan())
            <x-filament::section
                heading="Surat balasan perusahaan"
                description="Unggah surat balasan resmi dari perusahaan. Setelah itu Kaprog memutuskan penerimaan."
            >
                <div class="grid gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Berkas surat balasan *</span>
                        <x-filament::input.wrapper>
                            <x-filament::input type="file" wire:model="suratBalasan" accept=".pdf,.jpg,.jpeg,.png" />
                        </x-filament::input.wrapper>
                        <span class="text-xs text-gray-500">PDF/JPG/PNG maksimal {{ config('pkl.surat_balasan.max_mb') }} MB.</span>
                        @error('suratBalasan') <span class="text-danger-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Hasil dari perusahaan *</span>
                        <select class="fi-input" wire:model="suratBalasanHasil">
                            <option value="">Pilih hasil</option>
                            <option value="diterima">Diterima</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                        @error('suratBalasanHasil') <span class="text-danger-600">{{ $message }}</span> @enderror
                    </label>

                    <div>
                        <x-filament::button wire:click="uploadSuratBalasan" wire:loading.attr="disabled" wire:target="uploadSuratBalasan, suratBalasan">
                            Unggah surat balasan
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif

        @if ($this->isSedangPkl())
            <x-filament::section heading="Pelaksanaan PKL">
                <div class="grid gap-4 text-sm">
                    <dl class="grid gap-2">
                        <div class="flex flex-wrap gap-2">
                            <dt class="font-medium">Tanggal PKL</dt>
                            <dd>
                                {{ $pengajuan->tanggal_mulai_pkl?->format('d M Y') ?? '—' }}
                                s.d. {{ $pengajuan->tanggal_selesai_pkl?->format('d M Y') ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <dt class="font-medium">Dokumen fisik</dt>
                            <dd>
                                {{ $pengajuan->is_provisional ? 'Belum diterima Hubin (provisional)' : 'Sudah diterima Hubin' }}
                            </dd>
                        </div>
                    </dl>

                    @if ($this->hasPendingWithdrawal())
                        <p class="text-warning-600 dark:text-warning-400">
                            {{ __('pkl.pengajuan.withdraw_pending') }}
                        </p>
                    @else
                        <div class="grid gap-3">
                            <label class="grid gap-1">
                                <span class="font-medium">Ajukan penarikan PKL</span>
                                <textarea
                                    class="fi-input"
                                    rows="3"
                                    wire:model="withdrawReason"
                                    placeholder="Tulis alasan penarikan"
                                ></textarea>
                                @error('withdraw_reason') <span class="text-danger-600">{{ $message }}</span> @enderror
                            </label>
                            <div>
                                <x-filament::button color="danger" wire:click="requestWithdrawal" wire:loading.attr="disabled">
                                    Ajukan penarikan
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        @if ($period !== null && ($pengajuan === null || $this->isDraft()))
            <x-filament::section
                heading="Data perusahaan"
                description="Pilih perusahaan yang sudah terdaftar atau isi perusahaan baru. Kontak narahubung ditampilkan tersamar sampai Kaprog menyetujui."
            >
                <div class="grid gap-4">
                    @if ($selectedCompany !== null && ! $this->newCompany)
                        <div class="grid gap-1">
                            <p class="font-medium">{{ $selectedCompany->nama }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $selectedCompany->alamat }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Narahubung: {{ $selectedCompany->cp_name ?? '—' }}
                                @if ($selectedCompany->cp_phone)
                                    · {{ $this->contactUnlocked() ? $selectedCompany->cp_phone : MaskedContact::phone($selectedCompany->cp_phone) }}
                                @endif
                            </p>
                            <div>
                                <x-filament::button color="gray" size="sm" wire:click="clearCompany" wire:loading.attr="disabled">
                                    Ganti perusahaan
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        @livewire(\App\Livewire\Support\CompanyAutocomplete::class)

                        <label class="flex items-center gap-2 text-sm">
                            <x-filament::input.checkbox wire:model.live="newCompany" />
                            <span>Perusahaan belum ada di daftar, saya isi manual</span>
                        </label>

                        @error('companyId')
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    @endif

                    @if ($this->newCompany)
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span class="font-medium">Nama perusahaan *</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model="company.nama" maxlength="200" />
                                </x-filament::input.wrapper>
                                @error('company.nama') <span class="text-danger-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="font-medium">Telepon/faks</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model="company.telp_fax" maxlength="30" />
                                </x-filament::input.wrapper>
                                @error('company.telp_fax') <span class="text-danger-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="grid gap-1 text-sm sm:col-span-2">
                                <span class="font-medium">Alamat *</span>
                                <textarea class="fi-input" rows="3" wire:model="company.alamat"></textarea>
                                @error('company.alamat') <span class="text-danger-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="font-medium">Email</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="email" wire:model="company.email" maxlength="150" />
                                </x-filament::input.wrapper>
                                @error('company.email') <span class="text-danger-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="font-medium">Website</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model="company.website" maxlength="200" />
                                </x-filament::input.wrapper>
                                @error('company.website') <span class="text-danger-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="font-medium">Nama narahubung</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model="company.cp_name" maxlength="150" />
                                </x-filament::input.wrapper>
                                @error('company.cp_name') <span class="text-danger-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="font-medium">Telepon narahubung</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="tel" wire:model="company.cp_phone" maxlength="30" />
                                </x-filament::input.wrapper>
                                @error('company.cp_phone') <span class="text-danger-600">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <x-filament::button color="gray" wire:click="saveDraft" wire:loading.attr="disabled">
                            Simpan draf
                        </x-filament::button>

                        @if ($this->isDraft())
                            <x-filament::button wire:click="submit" wire:loading.attr="disabled">
                                Kirim pengajuan
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        @endif

        @if ($this->history() !== [])
            <x-filament::section heading="Riwayat pengajuan">
                <ol class="grid gap-3">
                    @foreach ($this->history() as $activity)
                        <li class="grid gap-1 border-b border-gray-200 pb-2 text-sm last:border-b-0 dark:border-gray-700">
                            <span class="font-medium">{{ $activity->description }}</span>
                            <span class="text-gray-500">
                                {{ $activity->created_at?->timezone(config('pkl.timezone.operational'))->format('d M Y H:i') }} WIB
                            </span>
                            @if (filled(data_get($activity->properties, 'catatan')))
                                <span class="text-danger-600">Catatan: {{ data_get($activity->properties, 'catatan') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-filament::section>
        @endif
    </div>

    <x-filament::modal id="konfirmasi-kemiripan" width="md">
        <x-slot name="heading">Periksa kemiripan perusahaan</x-slot>

        <div class="grid gap-3 text-sm">
            <p>{{ __('pkl.pengajuan.similar_confirm') }}</p>
            <ul class="list-disc pl-5">
                @foreach ($similarityWarnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <p>Bila yakin perusahaan ini berbeda, lanjutkan menyimpan draf.</p>
        </div>

        <x-slot name="footer">
            <x-filament::button wire:click="confirmSimilarity" wire:loading.attr="disabled">
                Tetap simpan
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
