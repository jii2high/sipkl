# TRD — Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi

**Turunan dari**: `PRD.md` v3 (terkonsolidasi). **Pendamping**: `DESIGN.md` (visual), `schema.sql` (skema DB), `TASKS.md` (pekerjaan M0–M10), `TASKS-REFACTOR-UI.md` (refactor UI/UX).
**Status**: Draf untuk implementasi. **Audiens utama**: developer dan AI agent pelaksana.

## 0. Cara Memakai Dokumen Ini

**Urutan otoritas bila ada konflik**

1. Perilaku produk, aturan bisnis, dan acceptance criteria → `PRD.md`.
2. Implementasi teknis → dokumen ini, selama tidak bertentangan dengan PRD (termasuk Lampiran A).
3. Bentuk data → `schema.sql` (turunan §6 dokumen ini).
4. Tampilan → `DESIGN.md`.
5. Pekerjaan dan urutan M0–M10 → `TASKS.md`. Refactor UI/UX (panel tiga + portal mobile) → `TASKS-REFACTOR-UI.md`.

Konflik yang tidak bisa diselesaikan dengan urutan di atas dicatat di §20 (Risiko & Keputusan Terbuka), bukan ditebak.

**Keputusan proses (berlaku untuk seluruh repo)**

- **Tidak ada tahap wireframe.** Implementasi langsung. Acuan visual hanyalah `DESIGN.md` + komponen bawaan Filament/Tailwind (lihat §14). Ketentuan PRD v2 §1.4 yang masih mewajibkan wireframe untuk halaman Blade kustom (landing, portal eksternal, kanban Walas) **dicabut** dan tercatat di PRD v3 (§1.7, Lampiran A16); verifikasi visual dilakukan lewat checkpoint di §17.
- Satu bahasa (PHP), satu framework UI per permukaan: panel memakai Filament, portal mobile memakai Blade + Livewire tanpa Filament (§14); tanpa lapisan API/JSON terpisah.
- Semua aturan bisnis hidup di Action/Policy/Service. UI hanya memanggil, tidak memutuskan.

## 1. Ringkasan Arsitektur

```
Browser (Hubin/Kaprog/Guru)               Browser (Siswa/Walas/Kepsek)         Browser (Penguji, Pembimbing/Kepala Industri)
  │  /hubin /kaprog /guru                   │  /siswa /walas /kepsek              │  /eksternal/*  (Blade + Livewire, guard token)
  │  (Filament 3 panel, native)             │  (Blade + Livewire, 360px)          │
  └───────────────┬─────────────────────────┴──────────────────────┬──────────────┘
                  ▼                                                ▼
        Filament Resources / Pages / Widgets              Controller tipis + Livewire
                  │                                                │
                  └───────────────► Actions (1 transisi = 1 class) ◄─┘
                                     │  memakai
                   Policies ─ Services ─ Enums/TransitionTable ─ Eloquent Models
                                     │
                        MySQL 8.0.16+  ·  Disk private  ·  Queue (database)
                                     │
                        Scheduler (cron per menit) → Jobs idempoten
```

**Prinsip**

- **Satu pintu tulis.** Perubahan status hanya lewat Action. Action memeriksa Policy, status sumber, dan scope; menulis perubahan + audit event dalam satu transaksi.
- **Server = otoritas.** Tombol disabled hanyalah petunjuk UI.
- **Idempoten.** Tiap Action aman dijalankan ulang (lihat §8).
- **Tanpa Redis.** Queue dan cache memakai driver `database`.

## 2. Stack Teknis

| Layer | Pilihan | Catatan implementasi |
|---|---|---|
| Runtime | PHP + Laravel (versi stabil terbaru yang didukung saat implementasi) | Kunci versi di `composer.json`; Filament dan paket lain harus kompatibel |
| UI internal (panel) | Filament multi-panel + Livewire | Tiga panel: `hubin`, `kaprog`, `guru` |
| UI portal mobile | Blade + Livewire (tanpa Filament) | `/siswa`, `/walas`, `/kepsek`; satu layout bersama, 360px |
| UI publik/eksternal | Blade + Livewire ringan | Landing, `/faq`, `/login`, OTP, `/verify/{token}`, `/eksternal/*` |
| Styling | Tailwind + tema Filament | Token dari `DESIGN.md` → CSS variables (§14) |
| Auth | Session auth bawaan Laravel + halaman Livewire kustom untuk OTP | Tanpa Fortify/Sanctum |
| DB | MySQL 8.0.16+ | JSON, CHECK, generated column wajib |
| Queue/Cache/Session | Driver `database` | Skala MVP |
| Storage | Disk lokal **private** | Diakses hanya lewat controller berotorisasi |
| Audit | `spatie/laravel-activitylog` | Plus tabel event domain bila perlu (§12) |
| PDF | dompdf | Lewat queue |
| QR | endroid/qr-code | Menunjuk `/verify/{token}` |
| Ekspor | maatwebsite/excel (queue, chunk 500) | Hanya Hubin |
| Test | Pest + Livewire testing | Satu runner |

**Aturan paket**: tidak boleh menambah paket atau abstraksi di luar tabel ini tanpa task di `TASKS.md`.

## 3. Struktur Folder Akhir yang Diharapkan

### 3.1 Dokumen pengembangan

Semua dokumen pengembangan berada di `docs/`. Dua folder referensi sudah ada dan **dipertahankan apa adanya** (tidak diubah oleh agent).

```
docs/
├── form-penilaian-references/          # referensi form penilaian (sidang, industri); sumber bagi form builder
├── old physical dokument examples/     # contoh dokumen fisik lama (surat pengantar, surat tugas, dll.); sumber bagi template PDF
├── PRD.md                              # sumber kebenaran perilaku produk
├── TRD.md                              # dokumen ini
├── TASKS.md                            # daftar pekerjaan bertahap M0–M10 (turunan PRD + TRD)
├── TASKS-REFACTOR-UI.md                # rencana refactor UI/UX (panel tiga + portal mobile)
└── schema.sql                          # skema MySQL (turunan §6)
```

`DESIGN.md` berada di root repo, bukan di `docs/`.

> Nama folder referensi dipakai persis seperti yang ada (termasuk spasi dan ejaan pada `old physical dokument examples`). Gunakan tanda kutip pada perintah shell.

### 3.2 Kode aplikasi (Laravel)

```
/                                       # root repo
├── docs/                               # lihat 3.1
├── app/
│   ├── Actions/                        # 1 transisi/operasi = 1 class (invokable)
│   │   ├── Auth/                       # VerifyOtp, ChangePassword, SwitchActiveRole
│   │   ├── Pengajuan/                  # SubmitPengajuan, ApproveKaprog, RejectKaprog, SignWalas, RejectWalas,
│   │   │                               #   GroupCompany, IssueSuratPengantar, UploadSuratBalasan,
│   │   │                               #   AcceptByCompany, RejectByCompany, ConfirmPhysicalDocument,
│   │   │                               #   RequestWithdrawal, ApproveWithdrawal
│   │   ├── Plotting/                   # AssignGuru, ReplaceGuru, IssueSuratTugasPembimbing
│   │   ├── Visitasi/                   # RequestVisitasi, ApproveVisitasi, RejectVisitasi, UploadDokumentasi
│   │   ├── Bimbingan/                  # SubmitBimbingan, GiveFeedback, MarkBagianAkhir, ApproveLaporanFinal
│   │   ├── Pengesahan/                 # SignStep, RejectStep, UploadRevisiLaporan, IssueSignerLink, ReissueSignerLink
│   │   ├── Form/                       # CreateForm, PublishForm, SubmitNilai, RequestUnlock, ApproveUnlock, RelockExpired
│   │   ├── Sidang/                     # ScheduleSidang, CascadeShift, IssueExaminerLink, RevokeExaminerLink
│   │   ├── PenilaianIndustri/          # GenerateIndustryLink, SubmitIndustryAssessment, RevokeIndustryLink
│   │   ├── Master/                     # MergeCompany, ImportData, UploadKepsekSignature, ResetOtp
│   │   └── Export/                     # StartAccreditationExport
│   ├── Enums/                          # 1 state machine = 1 Enum (+ TransitionTable bersebelahan)
│   │   └── Transitions/                # tabel transisi per Enum
│   ├── Models/
│   ├── Policies/                       # 1 policy per model; selalu cek active_role + scope
│   ├── Services/
│   │   ├── QuotaService.php            # hitung ulang kuota dalam lock
│   │   ├── LetterNumberService.php     # counter nomor surat per (tahun ajaran, jenis)
│   │   ├── DocumentService.php         # PDF + QR + token verifikasi
│   │   ├── MagicLinkService.php        # terbit/hash/verifikasi/revoke/reissue
│   │   ├── OtpService.php              # OTP internal dan eksternal
│   │   ├── CompanyResolver.php         # strict-unique + soft-warning (dipakai form siswa dan impor)
│   │   ├── FileStorageService.php      # deteksi MIME asli, simpan private
│   │   ├── ScoreService.php            # hitung nilai_total di server
│   │   └── AuditService.php
│   ├── Jobs/                           # lihat §10
│   ├── Notifications/                  # email + in-app (+ push opsional)
│   ├── Filament/
│   │   ├── Hubin/                      # Resources (Users, Companies, AcademicPeriods, Faqs, FaqCategories,
│   │   │                               #   SignatureAssets), Pages, Widgets panel hubin
│   │   ├── Kaprog/                     # Pages (dan Widgets) panel kaprog
│   │   ├── Guru/                       # Pages (dan Widgets) panel guru
│   │   └── Support/                    # cadangan komponen panel; saat ini kosong (lihat §14.3)
│   ├── Livewire/
│   │   ├── Public/                     # Landing, Faq, Verify
│   │   ├── Auth/                       # Login, OtpChallenge, ForceChangePassword
│   │   ├── Portal/                     # PengajuanSiswa, BimbinganSiswa, JadwalSidang, AntreanWalas,
│   │   │                               #   KanbanKelas, PengesahanKepsek, PengaturanNotifikasi
│   │   └── Eksternal/                  # ExaminerForm, IndustrySignOff, IndustryAssessment, RequestNewLink
│   ├── Http/
│   │   ├── Controllers/                # download file, PDF, QR image (berotorisasi)
│   │   └── Middleware/                 # EnsureActiveRole, EnsureExternalToken, SetTimezoneContext
│   ├── Support/                        # helper waktu (Jakarta/UTC), StatusChip, nilai, normalisasi (telp, website)
│   └── Providers/
├── bootstrap/
├── config/
│   └── pkl.php                         # konstanta bisnis (kuota=6, TTL, batas upload, ambang, dll.)
├── database/
│   ├── migrations/                     # dibuat dari docs/schema.sql
│   ├── factories/
│   └── seeders/                        # peran, jurusan, periode contoh, FAQ awal
├── resources/
│   ├── css/app.css                     # token dari DESIGN.md sebagai CSS variables (terang/gelap)
│   ├── views/
│   │   ├── public/                     # Blade landing/FAQ/verify
│   │   ├── eksternal/                  # layout minimal satu-tugas
│   │   ├── portal/                     # view portal mobile (layout bersama)
│   │   ├── pdf/                        # template surat (kop, nomor, TTD, QR)
│   │   └── emails/
│   └── lang/id/                        # semua teks UI dan pesan validasi dalam Bahasa Indonesia
├── routes/
│   ├── web.php                         # publik + login + verify + unduh file
│   ├── portal.php                      # /siswa, /walas, /kepsek (Blade + Livewire, penjaga peran portal)
│   ├── eksternal.php                   # /eksternal/* (guard token)
│   └── console.php                     # jadwal job
├── storage/app/private/                # lampiran, laporan, surat, ekspor (tidak pernah publik)
├── tests/
│   ├── Feature/                        # per Action dan per invarian
│   ├── Unit/                           # Service, TransitionTable, normalisasi
│   └── Browser or Livewire/            # alur utama per peran
├── .env.example
└── composer.json
```

## 4. Konfigurasi Bisnis (`config/pkl.php`)

Semua angka bisnis dari PRD dipusatkan di satu file agar tidak tersebar sebagai "magic number".

| Kunci | Nilai default | Sumber |
|---|---|---|
| `quota_per_company_period` | 6 | PRD §1.5 (1) |
| `email_domain` | `smkn1kotabekasi.sch.id` | F1 |
| `otp.internal.length` / `ttl_seconds` | 6 / 60 | F1 |
| `otp.internal.max_verify_attempts` | 3 | F1 |
| `otp.internal.resend_max` / `window_min` / `cooldown_min` | 5 / 30 / 30 | F1 |
| `password.min_length` | 8 | F1 |
| `rejection_note.min_length` | 10 | F3, F7 |
| `bimbingan.min_interactions` | 4 | F6 |
| `upload.bimbingan.max_mb` / `mimes` | 10 / pdf, doc, docx | F6 |
| `upload.visitasi.max_mb` / `mimes` | 5 / jpg, png | F5 |
| `visitasi.default_validity_days` | 7 | F5 |
| `magic_link.min_bytes` | 32 | F11 |
| `magic_link.ttl_days` (pengesahan, penilaian industri) | 7 | Q-D (default) |
| `external_otp.ttl_minutes` / `max_attempts` / `session_hours` | 5 / 3 / 24 | Q-D (default) |
| `examiner.pin_digits` / `max_attempts` | 4 / 3 | F9 |
| `score_unlock.ttl_hours` | 24 | F9 |
| `industry_assessment.window_months_before_end` | 1 | F10 |
| `industry_assessment.reminder_days_before_end` | 7 | F10 |
| `export.chunk_size` / `retention_days` / `target_seconds` | 500 / 30 / 300 | F13 |
| `faq.per_page` | 10 | F16 |
| `mail.retry_max` | 3 | F14 |
| `login.max_attempts` / `decay_seconds` | 5 / 60 | F1, 16 |
| `push.endpoint` / `timeout_seconds` | kosong / 5 | F14 (T10.3) |

## 5. Domain dan State Machine

### 5.1 Aturan umum

- Satu Enum per state machine, satu **tabel transisi** (`dari → ke`, aktor yang diizinkan), dan satu Action per transisi.
- Transisi yang tidak ada di tabel → exception domain (`InvalidTransition`) → dipetakan ke 409/422 dengan pesan Bahasa Indonesia.
- Action memeriksa **status sumber saat transaksi** (`WHERE status = :dari`) sehingga dua request bersamaan tidak sama-sama lolos.
- Setiap transisi menulis audit event dalam transaksi yang sama.

### 5.2 `pengajuan_siswa.status`

| Dari | Ke | Aktor | Syarat |
|---|---|---|---|
| `draft` | `menunggu_verifikasi_kaprog` | Siswa | Periode tidak terkunci; belum ada pengajuan aktif lain; snapshot perusahaan tersimpan |
| `menunggu_verifikasi_kaprog` | `menunggu_ttd_walas` | Kaprog (jurusan siswa) | Tidak memakai kuota |
| `menunggu_verifikasi_kaprog` | `ditolak_kaprog` | Kaprog | Catatan ≥ 10 karakter |
| `ditolak_kaprog` | `draft` | Siswa | Memperbaiki pengajuan |
| `menunggu_ttd_walas` | `menunggu_surat_pengantar` | Walas (kelas siswa) | Job grouping jalan setelahnya |
| `menunggu_ttd_walas` | `ditolak_walas` | Walas | Catatan ≥ 10 karakter |
| `ditolak_walas` | `draft` | Siswa | — |
| `menunggu_surat_pengantar` | `surat_pengantar_terbit` | Hubin | Surat per company group; `pengajuan_pkl_id` terisi |
| `surat_pengantar_terbit` | `menunggu_balasan_perusahaan` | Siswa | Surat balasan terunggah |
| `menunggu_balasan_perusahaan` | `sedang_pkl` | Kaprog | Kuota dihitung ulang dalam lock; set `is_provisional` dari dokumen fisik; tanggal PKL default dari periode |
| `menunggu_balasan_perusahaan` | `rejected` | Kaprog | Hanya siswa itu |
| `sedang_pkl` | `withdrawn` | Kaprog | Atas pengajuan siswa/Kaprog dengan alasan; slot dilepas; bimbingan di-archive |

Catatan: konfirmasi dokumen fisik oleh Hubin **bukan transisi status**, hanya mengubah `is_provisional = false`.

### 5.3 `pengajuan_pkl.status` (company group)

`terbentuk → surat_terbit → berjalan → bubar`. `guru_terplot` bukan status; ia diturunkan dari ada atau tidaknya baris plotting (Lampiran A3).

### 5.4 `document_signatures.status` (per `step_order` 1..5)

`pending → signed | rejected`. Aturan:

- Step N hanya boleh `pending` bila step N-1 `signed`.
- `rejected` tidak mengubah step < N.
- Setelah revisi: versi laporan baru, kembali ke step yang menolak (bukan ke step 1).
- Setelah step 5 `signed` → `laporan_versions.is_legit = true` → masuk pool Siap Sidang.

Kode step: 1 Guru Pembimbing, 2 Kaprog, 3 Pembimbing Industri, 4 Kepala Industri, 5 Kepsek (CHECK di DB).

### 5.5 Status lain

| Entitas | Status |
|---|---|
| `visitasi` (`surat_tugas_visitasi`) | `diajukan → disetujui → dicetak → aktif → kedaluwarsa`, atau `diajukan → ditolak` (Kaprog boleh mengajukan ulang sebagai record baru) |
| `magic_links` | `active → used`, `active → revoked`, `active → expired` (token tunggal aktif per `(type, target)`) |
| `form_definitions` | `is_published = false → true`; terkunci setelah ada submission |
| `form_submissions` | `is_submitted` (terkunci) → `unlock_approved` (24 jam) → `submitted_again` atau auto re-lock |
| `accreditation_exports` | `queued → running → completed` atau `queued/running → failed` (retry = baris baru via `retry_of_id`) |

## 6. Model Data (turunan untuk `schema.sql`)

`schema.sql` adalah bentuk data final. Nama tabel di bawah mengikuti `schema.sql`.

### 6.1 Tabel inti

| Kelompok | Tabel |
|---|---|
| Identitas & akses | `users`, `user_roles`, `jurusan`, `kelas`, `otp_codes`, `sessions` |
| Master | `companies`, `academic_periods`, `signature_assets` (TTD Kepsek) |
| Pengajuan | `pengajuan_siswa`, `pengajuan_pkl` (company group), `guru_pembimbing_plotting` |
| Surat & dokumen | `nomor_surat_counters`, `surat_tugas_pembimbing`, `surat_tugas_visitasi`, `surat_tugas_visitasi_companies`, `visitasi_dokumentasi`, `document_qr_tokens` |
| Bimbingan & pengesahan | `bimbingan_submissions`, `laporan_versions`, `document_signatures` |
| Form & penilaian | `form_definitions`, `form_sections`, `form_items`, `form_submissions`, `form_submission_siswa`, `jadwal_sidang`, `sidang_results_summary`, `nilai_sidang_revisions` |
| Akses eksternal | `magic_links`, `external_sessions`, `external_examiners` |
| Lain-lain | `faq_categories`, `faqs`, `accreditation_exports`, `notifications`, `notification_delivery_failures`, `activity_log`, `cache`, `jobs`, `failed_jobs` |

### 6.2 Constraint yang wajib dijaga database (bukan hanya aplikasi)

| Invarian | Mekanisme |
|---|---|
| Maks 1 pengajuan aktif per siswa per periode | Generated column `active_key` (NULL bila `rejected`/`withdrawn`) + UNIQUE `(siswa_id, periode_id, active_key)` |
| `pengajuan_pkl_id` wajib terisi sejak `surat_pengantar_terbit` | CHECK berdasarkan status |
| Komposit group konsisten | FK komposit `(pengajuan_pkl_id, company_id, academic_period_id)` |
| Signer perusahaan boleh kosong, tetapi bila `verified_at` terisi maka nama dan email wajib | Kolom NULLABLE + CHECK |
| Kode step TTD hanya 1..5 dan unik per `(laporan_version_id, step_order)` | CHECK + UNIQUE |
| `user_roles` unik walau scope NULL | Generated key `jurusan_key`, `kelas_key` + UNIQUE |
| `kelas.walas_id` tidak ada | Satu sumber kebenaran: `user_roles` |
| Nomor surat unik per tahun ajaran | `nomor_surat_counters` UNIQUE `(tahun_ajaran, jenis_surat)`; tiap tabel surat UNIQUE `(tahun_ajaran, nomor_urut)` dan `(tahun_ajaran, nomor_surat)` |
| 1 plotting per `(company_id, academic_period_id)` | UNIQUE |
| 1 submission penilaian industri per `(form_definition_id, company_id, periode)` | UNIQUE |
| 1 magic link aktif per `(type, target_id)` | Generated column `active_key` + UNIQUE |
| `accreditation_exports.status = completed` hanya jika file dan checksum terisi | CHECK |
| Token/PIN/OTP hanya disimpan sebagai hash | Kolom `*_hash` (CHAR(64)); tidak ada kolom plaintext |
| `nilai_total` dihitung dari JSON | Generated column (lihat §13) |

### 6.3 Snapshot immutable

`pengajuan_siswa.company_snapshot` (JSON) disimpan saat submit dan tidak pernah diubah. Perubahan data perusahaan sesudahnya tidak mengubah snapshot.

### 6.4 Strategi migrasi

`schema.sql` adalah acuan. Migrasi Laravel dibuat dari sana (satu migrasi per kelompok di §6.1) dan harus menghasilkan skema yang setara. Perubahan skema sesudahnya dilakukan lewat migrasi baru **dan** diperbarui di `docs/schema.sql`.

## 7. Autentikasi dan Otorisasi

### 7.1 Login dan OTP (F1)

1. `POST /login` memvalidasi domain email **sebelum** pemeriksaan password/OTP. Respons galat tidak membedakan "akun tidak ada" dan "password salah".
2. `is_first_login = true` → `OtpService::issue()` (6 digit acak kriptografis, simpan hash, TTL 60 detik) → halaman verifikasi → wajib ganti password → `is_first_login = false`.
3. Limiter terpisah: `otp-verify` (maks 3; percobaan ke-4 menginvalidasi OTP via `invalidated_at`) dan `otp-resend` (5 per 30 menit lalu cooldown 30 menit; dihitung dari baris OTP + RateLimiter, tanpa kolom ambigu).
4. Password baru: ≥ 8 karakter dan ≠ NIS/NIP, dengan pesan validasi spesifik.

### 7.2 `active_role`

- Disimpan di sesi. `SwitchActiveRole` memvalidasi bahwa user memiliki peran itu.
- `EnsureActiveRole` (middleware) menolak 403 bila peran tidak aktif/tidak dimiliki.
- Ganti peran membersihkan state peran sebelumnya (filter tabel, cache query per peran, notifikasi terbuka) agar tidak ada kebocoran data.
- Navigasi dibangun dari `active_role`: panel `hubin`, `kaprog`, `guru` (Filament) atau portal mobile `siswa`, `walas`, `kepsek` (Blade). Satu pengguna hanya melihat permukaan peran aktifnya.

### 7.3 Policy dan scope

Setiap Policy memeriksa tiga hal: **peran aktif**, **scope objek**, **status objek**.

| Peran | Scope |
|---|---|
| Siswa | Hanya pengajuan/bimbingan miliknya |
| Walas | Hanya kelas yang ia wali; read-only monitoring |
| Guru | Hanya company+periode hasil plotting dan siswa bimbingannya |
| Kaprog | Hanya jurusannya |
| Hubin | Seluruh master data dan dokumen; ekspor |
| Kepsek | Hanya step 5 |
| Eksternal | Hanya satu target per token |

Query Eloquent untuk daftar **harus** dibatasi oleh scope (global scope atau `->visibleTo($user, $role)`), bukan hanya disembunyikan di UI.

### 7.4 Akses file

File tidak pernah di folder publik. `Route → Controller → Policy → stream`. URL file tidak menyertakan path asli; memakai ID dokumen dan otorisasi per request.

## 8. Konkurensi, Atomisitas, Idempotensi

| Kasus | Teknik |
|---|---|
| **Kuota 6** | Dalam transaksi: `SELECT … FOR UPDATE` pada baris `companies` → hitung ulang `sedang_pkl` (termasuk provisional) per company+periode → bila ≥ 6, throw `QuotaExceeded` (409) tanpa efek samping. VIEW hanya untuk baca/dashboard. |
| **Nomor surat** | `LetterNumberService` mengambil counter `(tahun_ajaran, jenis)` dengan `FOR UPDATE` di transaksi yang sama dengan pembuatan dokumen. Rollback = tidak ada nomor terpakai. |
| **Double submit / double klik** | `UPDATE … WHERE id = ? AND status = :dari` dan periksa `affected_rows`; 0 baris = sudah diproses → kembalikan hasil yang sama (idempoten), tidak membuat baris/surat/notifikasi/nomor ganda. Tombol UI memakai `wire:loading.attr="disabled"` hanya sebagai lapisan tambahan. |
| **Sign ganda step TTD** | UNIQUE `(laporan_version_id, step)` + kondisi `status = 'pending'`. |
| **Reissue magic link** | Satu transaksi: revoke token lama + terbit token baru; unique aktif per `(type, target)` mencegah dua token aktif. |
| **Cascade shift sidang** | Satu transaksi; validasi seluruh jadwal baru dulu, baru tulis. Gagal → jadwal awal utuh + daftar konflik. |
| **Bulk approve (Kaprog/Kepsek)** | Tiap laporan diproses independen dalam transaksinya sendiri; hasil per item dikembalikan (sukses/gagal + alasan). |
| **Job terjadwal** | Setiap job idempoten (kondisi berbasis status/tanggal) dan, untuk job harian, memakai kunci "sudah jalan untuk tanggal Jakarta X". |

Perubahan status dan penulisan audit event **selalu satu transaksi**. Notifikasi dikirim setelah commit (`afterCommit`).

## 9. Magic Link, OTP Eksternal, dan Portal `/eksternal/*`

### 9.1 Token

- 32 byte acak kriptografis (`random_bytes`), dikodekan URL-safe. Yang disimpan hanya `SHA-256(token)`. Token mentah muncul sekali (saat dikirim) dan tidak masuk log.
- Single-use, dapat dicabut, satu aktif per `(type, target)`.
- Tipe: `examiner_sidang`, `examiner_unlock`, `signer_step3`, `signer_step4`, `industry_assessment`.

### 9.2 Pembeda tiap tipe

| Tipe | Verifikasi tambahan | TTL default |
|---|---|---|
| `examiner_*` | PIN 4 digit (hash; 3 salah → invalid → Kaprog menerbitkan ulang) | Sampai akhir hari sidang (Jakarta); unlock 24 jam |
| `signer_step3/4` | OTP email (6 digit, 5 menit, maks 3 percobaan) | 7 hari |
| `industry_assessment` | OTP email | 7 hari |

Sesi eksternal berlaku 24 jam dan **turun dari token**: revoke token membatalkan seluruh sesi turunannya.

### 9.3 Guard dan respons

- Guard terpisah dari sesi internal (`EnsureExternalToken`); tidak memberi akses ke rute panel (`/hubin`, `/kaprog`, `/guru`) atau portal mobile (`/siswa`, `/walas`, `/kepsek`).
- Layout minimal; respons hanya berisi data untuk satu tugas.
- Pesan galat netral (tidak membocorkan apakah target ada). Selalu tampilkan jalur "Minta link baru" (diteruskan ke Kaprog/Guru/Hubin).
- Override Hubin (revoke/reissue) wajib alasan dan masuk audit.
- Signer industri harus punya nama individu + email terverifikasi; jabatan generik ditolak sebagai penerima OTP.

### 9.4 Binding perangkat (longgar)

Simpan sidik perangkat ringan (mis. hash UA + IP prefix) pada sesi. Perubahan memicu langkah verifikasi tambahan (OTP/PIN ulang), bukan penolakan keras.

## 10. Job dan Penjadwalan

Scheduler dijalankan cron setiap menit (`schedule:run`). Worker: `queue:work` (database) di bawah supervisor.

| Job | Jadwal | Fungsi | Idempotensi |
|---|---|---|---|
| `GroupCompanyJob` | Event (setelah TTD Walas) | Masukkan siswa ke company group `(company, periode)` | Cek keanggotaan sebelum insert |
| `GenerateDocumentPdfJob` | Event | Buat PDF + QR | Kunci per dokumen; retry aman |
| `SendNotificationJob` | Event | Email + in-app; retry transient maks 3x | Kunci per notifikasi |
| `ProvisionalReminderJob` | Harian (Jakarta) | Reminder provisional ke siswa (email + in-app), tanpa eskalasi | Sekali per tanggal |
| `IndustryAssessmentReminderJob` | Harian | Reminder H-7 bila link belum dibuat | Sekali per tanggal per perusahaan |
| `ExpireVisitasiJob` | Harian | Tandai `kedaluwarsa` (inklusif 23:59:59 Jakarta) | Hanya ubah yang belum kedaluwarsa |
| `PurgeExpiredExportsJob` | Harian | Hapus ekspor > 30 hari | Hanya yang melewati batas |
| `ExpireMagicLinksJob` | Per jam | Tandai link kedaluwarsa | Berbasis status |
| `RelockScoreJob` | Per jam | Re-lock nilai yang unlock-nya habis; nilai lama berlaku | Berbasis status |
| `AccreditationExportJob` | Event (queued) | Ekspor chunk 500, validasi kelengkapan | Attempt baru bila retry |

Semua job mencatat kegagalan tanpa rahasia (token/PIN/OTP) dan memakai `failed_jobs`. Kegagalan email permanen dicatat dan terlihat oleh Hubin.

## 11. Dokumen, Nomor Surat, PDF, QR

- **Template** PDF di `resources/views/pdf/`, mengacu pada contoh di `docs/old physical dokument examples/` (kop, urutan blok, bahasa surat). Perbedaan terhadap contoh fisik dicatat di `TASKS.md` per template.
- **Jenis surat**: Surat Pengantar (per company group), Surat Tugas Pembimbing (otomatis saat plotting), Surat Tugas Visitasi (setelah approval Hubin).
- **Nomor**: `LetterNumberService` per `(tahun_ajaran, jenis)`, bukan per periode (default Q-A; menunggu persetujuan PO).
- **TTD Kepsek**: dari `signature_assets` (satu aktif). Bila belum ada, penerbitan surat otomatis **ditahan**, data tetap tersimpan, Hubin diberi tahu.
- **Pembuatan**: nomor + baris dokumen dalam satu transaksi; PDF dibuat via queue; kegagalan tidak meninggalkan "nomor terpakai tanpa dokumen" (retry idempoten, memakai nomor yang sama).
- **QR** → `/verify/{token}`; tabel `document_qr_tokens` menyimpan hash token. Halaman verifikasi **hanya** menampilkan nomor surat, status, dan tanggal terbit. Token tidak valid/dicabut → pesan netral.

## 12. Audit, Logging, dan Notifikasi

- **Audit**: `activity_log` mencatat aktor, `active_role`, objek, status lama→baru, alasan (bila ada), IP. Penulisan satu transaksi dengan perubahan status. Penolakan tetap terbaca historinya.
- **Redaksi log**: token, PIN, OTP, password tidak pernah ke log/audit/exception. Gunakan penyamaran (`last4`) bila perlu referensi.
- **Notifikasi**: email + in-app wajib; push opt-in dan best-effort (tidak diprompt ulang setelah ditolak). Retry transient maks 3x; kegagalan permanen tercatat dan tampil ke Hubin.

## 13. Form Builder, Sidang, dan Nilai

### 13.1 Struktur form dua tingkat (relasional)

```
form_definitions        # per jurusan + periode; form_type = sidang | penilaian_industri
  └── form_sections     # label + sort_order
        └── form_items  # label + has_alokasi_waktu + sort_order
```

- Tepat dua tingkat konten (Section → Items) di tabel relasional. Tanpa tingkat lebih dalam, EAV, atau tabel jawaban per item.
- Blok Kehadiran dan Catatan adalah flag di `form_definitions` (`has_kehadiran`, `has_catatan`), bukan item bernilai.
- Form tanpa item tidak dapat dipublish (`is_published`). Setelah ada submission, struktur terkunci; perubahan = form/versi baru.
- Struktur form mengacu pada contoh di `docs/form-penilaian-references/`.

### 13.2 Jawaban dan `nilai_total`

- Jawaban disimpan di `form_submissions.jawaban` (JSON) per submission, divalidasi terhadap definisi form: setiap `form_item_id` harus ada dan nilainya dalam rentang.
- `nilai_total` dihitung **server saat submit** (default Q-E: rata-rata item, skala 0–100) dan diekspos lewat generated column agar bisa diurutkan/diekspor. Rumus final menunggu konfirmasi PO.
- Section Kehadiran dan Catatan tidak ikut perhitungan.

### 13.3 Alur nilai

1. Kaprog menjadwalkan siswa dari pool Siap Sidang (hanya yang `is_legit`) dan menetapkan penguji (nama + kontak terverifikasi).
2. `IssueExaminerLink` membuat magic link + PIN; dikirim via email atau tautan `wa.me` manual.
3. Penguji mengisi nilai → "Simpan & Selesaikan" → `is_submitted = true` → semua update berikutnya 403.
4. Revisi: Kaprog menyetujui unlock → link + PIN baru (1 form, 24 jam) ke penguji asli → submit ulang; TTL habis → `RelockScoreJob`, nilai lama berlaku.
5. Penilaian industri: tanpa revisi; 1 link + 1 submission per perusahaan, siswa dihubungkan via `form_submission_siswa`; siswa `withdrawn` tidak ikut.

## 14. Implementasi UI (Tanpa Wireframe)

**Sumber kebenaran visual**: `DESIGN.md`. Tidak ada wireframe; halaman dirancang langsung dengan komponen Filament (panel) atau Blade + Livewire (portal) berdasarkan pola di `DESIGN.md`.

### 14.1 Token dan tema

- `resources/css/app.css` memuat CSS variables dari `DESIGN.md` (blok `:root` terang dan blok `.dark`/`[data-theme="dark"]`).
- Filament diatur agar warna primer/bahaya/sukses/peringatan mengikuti token; mode gelap memakai mekanisme dark mode Filament.
- Pilihan tema: Terang/Gelap/Ikuti sistem, disimpan per akun; halaman publik dan eksternal mengikuti `prefers-color-scheme`. Terapkan class tema sebelum render pertama untuk menghindari kilatan.

### 14.2 Pemetaan peran → permukaan dan layar

| Peran | Permukaan | Layar utama |
|---|---|---|
| Hubin | Panel `/hubin` (Filament) | Master data, impor, surat pengantar, konfirmasi dokumen fisik, approval visitasi, FAQ, scan TTD Kepsek, ekspor akreditasi |
| Kaprog | Panel `/kaprog` (Filament) | Antrean Gate 1, keputusan penerimaan + kuota, plotting, pengajuan visitasi, bulk TTD 2, form builder, jadwal sidang, approval unlock/withdrawal, dashboard durasi tunggu |
| Guru Pembimbing | Panel `/guru` (Filament) | Daftar bimbingan siswa, feedback, Approve Laporan Final, visitasi, detail perusahaan + Generate Link penilaian |
| Siswa | Portal `/siswa` | Pengajuan (form + status), unggah balasan, bimbingan (daftar + unggah), status pengesahan (stepper), jadwal sidang |
| Walas | Portal `/walas` | Gate TTD pengajuan, kanban 5 kolom read-only |
| Kepsek | Portal `/kepsek` | Daftar TTD 5 (bulk/single) dengan target sentuh 44px |
| Eksternal | `/eksternal/*` | Satu layar per tugas: nilai sidang, TTD 3/4, penilaian industri |
| Publik | `/`, `/faq`, `/login*`, `/verify/{token}` | Landing, FAQ, verifikasi QR, login/OTP |

### 14.3 Komponen bersama

Sumber status tunggal: `app/Support/StatusChip.php` (label, warna, ikon). Enum status mengekspos kontrak Filament (`HasLabel`/`HasColor`/`HasIcon`) yang mendelegasikan ke `StatusChip`, sehingga badge panel (`TextColumn->badge()`) dan chip portal membaca peta yang sama.

Komponen Blade bersama di `resources/views/components/`: `status-badge`, `quota-bar`, `signature-stepper`, `masked-contact`, `otp-input`, `pin-input`, `theme-switcher`, `theme-script`. Layout: `layouts/public`, `layouts/eksternal`, `layouts/auth`, dan `layouts/portal` (portal mobile).

`app/Filament/Support/` ada tetapi kosong; komponen panel memakai fitur bawaan Filament lebih dulu (§14.4).

### 14.4 Aturan UI

- UI hanya memanggil Action; tidak memuat aturan bisnis.
- Tombol disabled disertai alasan, otorisasi tetap di server.
- Panel: pakai fitur bawaan Filament lebih dulu, urutannya Resource → Page + table/schema → Widget → Action/BulkAction → komponen bawaan. Blade kustom di `resources/views/filament/` hanya dengan alasan satu baris.
- Portal mobile (`/siswa`, `/walas`, `/kepsek`) tidak memakai Filament: satu layout bersama, 360px, target sentuh ≥ 44px, token `DESIGN.md`.
- Tabel di ponsel menjadi daftar card; target sentuh ≥ 44px; input ≥ 16px di ponsel.
- Semua teks UI dan pesan galat dalam Bahasa Indonesia di `resources/lang/id/`.
- Responsif 360px diverifikasi di tiap checkpoint (§17), bukan diasumsikan. **Cadangan**: bila satu halaman panel tidak bisa memenuhi AC dengan Filament, boleh memakai komponen Alpine/JS lokal untuk halaman itu saja, tanpa memindahkan stack.

## 15. Waktu dan Zona

- DB menyimpan UTC; aplikasi beroperasi di Asia/Jakarta (`config('app.timezone')` = UTC, konversi di lapisan tampil/aturan).
- Tanggal tanpa jam bersifat inklusif sampai 23:59:59 Jakarta (visitasi, periode, link sidang).
- TTL = durasi elapsed (bukan kalender).
- Job harian sekali per tanggal Jakarta.
- SLA dihitung Senin–Jumat (libur nasional tidak dikecualikan, sesuai non-goal).
- Semua perhitungan tanggal lewat helper di `app/Support/` agar seragam dan mudah diuji.

## 16. Keamanan

| Area | Kontrol |
|---|---|
| Kredensial | Password di-hash bawaan Laravel; OTP/PIN/token di-hash; tidak ada plaintext di DB/log |
| Enumerasi | Pesan galat netral di login, magic link, dan `/verify` |
| Rate limit | `otp-verify`, `otp-resend`, login, PIN penguji, `/verify` per IP |
| Upload | Deteksi MIME dari **isi** file; batas ukuran per jenis; nama file acak; disk private; tanpa eksekusi |
| Sesi | Regenerasi ID saat login/ganti peran; cookie `HttpOnly`, `Secure`, `SameSite=Lax` (aktifkan `SESSION_SECURE_COOKIE`/`SESSION_ENCRYPT` di produksi) |
| CSRF | Bawaan Laravel/Livewire |
| Header | HSTS, `X-Content-Type-Options`, CSP yang kompatibel dengan Livewire/Filament, `Referrer-Policy` ketat pada rute eksternal. Diimplementasikan middleware `SecurityHeaders` (grup `web`) |
| Data pribadi | Kontak CP perusahaan di-mask sampai disetujui Kaprog; tidak ada NIK penguji (non-goal) |
| Antivirus/CDR | Opsional per deployment, bukan syarat |
| Akses ekspor | Hubin-only, unduh via controller berotorisasi, tautan kedaluwarsa 30 hari |

## 17. Strategi Pengujian dan Checkpoint

### 17.1 Pest

- **Feature test per Action**: jalur sukses, aktor salah (403), status sumber salah (409/422), idempotensi (panggil dua kali → satu efek).
- **Test per invarian PRD §1.5**: kuota 6 dengan dua request konkuren di kuota 5; 5-TTD sequential + partial-reject; nilai terkunci; scope per peran; rahasia ter-hash; nomor surat tanpa duplikat; atomisitas status+audit.
- **Unit test**: `TransitionTable` (semua pasangan yang tidak diizinkan ditolak), normalisasi (telp/website), `ScoreService`, helper waktu (batas 23:59:59 Jakarta, hari kerja).
- **Livewire test**: alur utama per peran (login+OTP, submit pengajuan, bulk TTD, penguji mengisi nilai).
- **Test akses file**: pengguna tak berwenang tidak dapat mengunduh lampiran.

### 17.2 Checkpoint visual (pengganti wireframe)

Di akhir tiap milestone, per halaman baru:

1. Lebar 360px dan 1280px.
2. Mode terang dan gelap.
3. Kontras sesuai aturan `DESIGN.md` (aksen turquoise, biru di mode gelap).
4. Status memakai chip bertulisan, bukan hanya warna.
5. Target sentuh dan ukuran input di ponsel.

### 17.3 Uji beban

Ekspor akreditasi untuk 1.752 siswa diuji beban; target < 5 menit adalah **target yang diuji**, bukan janji. Hasil dicatat di `TASKS.md`.

## 18. Operasional dan Lingkungan

- **Env**: `.env.example` memuat konfigurasi DB, mail, queue (`database`), timezone, domain email sekolah, dan kunci aplikasi. Rahasia tidak masuk repo.
- **Proses**: web server + PHP-FPM, `queue:work` (supervisor), cron `schedule:run` per menit.
- **Backup**: DB dan `storage/app/private` dicadangkan terjadwal; uji pemulihan sebelum go-live.
- **Pemantauan**: `failed_jobs`, kegagalan email permanen, dan kegagalan ekspor tampil di panel Hubin.
- **Seed**: peran, jurusan, periode contoh, FAQ awal. Data akun diimpor lewat fitur impor Hubin.
- **Rilis dan operasional**: perintah rilis, supervisor, cron, cadangan/pemulihan, checklist UAT, dan pemetaan invarian ada di `docs/GO-LIVE.md`.

## 19. Urutan Implementasi (Langsung, Tanpa Wireframe)

Rincian per task ada di `TASKS.md`. Urutan ini memastikan dependensi terpenuhi (A7: counter + PDF dasar sebelum penerbitan surat).

| Milestone | Isi |
|---|---|
| M0 Fondasi | Repo, paket, `config/pkl.php`, migrasi dari `schema.sql`, token tema + layout dasar, `AuditService`, helper waktu |
| M1 Akses | Login, OTP first-login, ganti password, `active_role`, Policy dasar + scope, ThemeSwitcher |
| M2 Master Data | Perusahaan + `CompanyResolver`, periode, akun, signer, impor, merge, scan TTD Kepsek |
| M3 Dokumen Dasar | `LetterNumberService`, `DocumentService` (PDF + QR), `/verify` |
| M4 Pengajuan | Alur pengajuan sampai `sedang_pkl`, kuota, grouping, surat pengantar, provisional, withdrawal |
| M5 Plotting & Visitasi | Plotting + Surat Tugas, visitasi, job kedaluwarsa |
| M6 Bimbingan & Pengesahan | Bimbingan, guard provisional dan ≥4, 5-TTD, magic link + OTP eksternal, bulk approve |
| M7 Form & Sidang | Form builder, jadwal + cascade shift, portal penguji, kunci nilai, unlock |
| M8 Penilaian Industri | Window H-1 bulan, link per perusahaan, pivot siswa, reminder H-7 |
| M9 Monitoring & Publik | Kanban Walas, dashboard durasi tunggu, landing, FAQ |
| M10 Ekspor & Penutup | Ekspor akreditasi, uji beban, audit keamanan, backup, go-live checklist |

Setelah M10 ada pekerjaan **refactor UI/UX**: panel Filament dibagi tiga (`hubin`, `kaprog`, `guru`) dan Siswa/Walas/Kepsek pindah ke portal mobile Blade. Urutan task R0–R6 ada di `TASKS-REFACTOR-UI.md`; dokumen ini sudah memakai bentuk akhirnya.

## 20. Risiko dan Keputusan Terbuka

| ID | Hal | Default yang dipakai | Dampak bila berubah |
|---|---|---|---|
| Q-A | Counter nomor surat per **tahun ajaran** (bukan periode) | Per tahun ajaran | Mengubah kunci `nomor_surat_counters` dan UNIQUE nomor; menyimpang dari PD-13/AC-47 |
| Q-B | Perusahaan lintas >1 jurusan | 1 guru per company+periode; form/link industri per form definition | Skema form/pivot |
| Q-C | Tanggal PKL per siswa | Disalin dari periode saat penerimaan; Kaprog dapat mengubah | Window penilaian industri |
| Q-D | TTL link/OTP eksternal | Link 7 hari; OTP 5 menit (maks 3); sesi 24 jam | Nilai di `config/pkl.php` saja |
| Q-E | Rumus `nilai_total` | Rata-rata item, skala 0–100 | `ScoreService` + generated column |
| Q-F | Penggantian stack (A9) | Filament/Livewire | Bila ditolak: kembali ke TRD v1 dan wireframe |
| Q-J | Ganti guru setelah plotting | Surat baru + audit | `ReplaceGuru` |
| R1 | Livewire gagal memenuhi AC tertentu (AC-32 session switching, 360px) | Alpine/JS lokal per halaman | Terbatas pada halaman itu |
| R2 | Tanpa wireframe, risiko inkonsistensi tata letak | `DESIGN.md` + komponen bersama §14.3 + checkpoint §17.2 | Perlu review visual di tiap milestone |
| R3 | Waktu ekspor melebihi 5 menit | Chunk 500, queue; uji beban dini | Penyetelan chunk/indeks |
| R4 | Referensi di `docs/form-penilaian-references/` dan `docs/old physical dokument examples/` belum dipetakan ke template/form | Dipetakan di M3 (surat) dan M7 (form) | Perbedaan isi/format surat dan form |

## 21. Definition of Done (per Task)

1. Aturan PRD yang dirujuk terpenuhi dan teruji (Pest hijau).
2. Perubahan status hanya lewat Action; tabel transisi diperbarui bila ada status baru.
3. Policy + scope teruji untuk peran terkait; akses lintas peran ditolak.
4. Idempotensi dan atomisitas teruji (panggilan ganda, kegagalan di tengah).
5. Tidak ada rahasia di log/audit/respons.
6. Halaman baru lolos checkpoint visual §17.2.
7. Teks UI Bahasa Indonesia di `resources/lang/id/`.
8. Skema berubah → `docs/schema.sql` ikut diperbarui.
9. Tidak ada paket/abstraksi baru di luar §2 tanpa task.
10. Portal mobile: rute di `routes/portal.php` di luar middleware panel; target sentuh ≥ 44px di 360px; teks Bahasa Indonesia.
