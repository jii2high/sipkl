# TASKS — Actionable Task Breakdown (Siap Dieksekusi AI Coding Agent)

Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi. Turunan dari `PRD.md` v2 dan `schema.sql` v2. Kode AC (`AC-xx`) merujuk TRD §11, dengan penyesuaian PRD Lampiran A (A3, A4, A15).

## Aturan Eksekusi (berlaku untuk semua task)

1. **Satu task = satu perubahan ter-review.** Jangan menggabungkan task. Jangan mulai milestone berikutnya sebelum Checkpoint lulus.
2. Sebelum coding: baca `PRD.md` (modul terkait + §1.5 Invarian), AC yang dirujuk, tabel terkait di `schema.sql`, lalu inspeksi kode aktual. Jangan menambah paket/abstraksi di luar daftar tanpa task.
3. Aturan bisnis (otorisasi, kuota, state, token, lock nilai) ditegakkan **di server** (Action/Policy/Service). UI hanya memanggil.
4. Setiap aksi tulis penting: transaksi DB, idempoten terhadap double-submit, dan audit log (`activity_log`) tanpa kredensial/OTP/PIN/token mentah.
5. Migration mengacu `schema.sql`; selaraskan dengan skema aktual. `schema.sql` bukan script yang dijalankan langsung.
6. Tiap task selesai dengan: file yang berubah, pemeriksaan yang benar-benar dijalankan (Pest/manual), dan hal yang belum dapat diverifikasi. Jangan klaim lulus bila tidak dijalankan.
7. Jika dokumen konflik atau keputusan belum ada: hentikan hanya task yang bergantung, catat ID (mis. `Q-E`), lanjutkan task independen.

**Konvensi struktur** (sesuaikan dengan repo aktual):

- `app/Models`, `app/Enums`, `app/Actions/<Modul>`, `app/Services`, `app/Policies`, `app/Jobs`
- Filament: `app/Filament/Hubin/*` (panel `hubin`), `app/Filament/App/*` (panel `app`)
- Publik/eksternal: `app/Livewire/Public/*`, `app/Livewire/External/*`, `resources/views/*`
- Test: `tests/Feature/<Modul>/*`, `tests/Unit/*`

---

# MILESTONE 0 — Fondasi Project

- [ ] **Task M0.1: Verifikasi/bootstrap proyek Laravel**
  - **Goal**: Proyek Laravel berjalan lokal.
  - **Target Files/Location**: `composer.json`, `.env.example`, `README.md`
  - **Instructions**: Cek apakah `composer.json` sudah ada. Bila belum, buat proyek Laravel versi stabil terbaru yang didukung. Catat versi PHP/Laravel aktual. Jalankan `php artisan about`.
  - **Acceptance Criteria**: `php artisan about` berhasil; versi tercatat di PR.

- [ ] **Task M0.2: Pasang paket inti**
  - **Goal**: Dependency sesuai PRD §1.4 terpasang.
  - **Target Files/Location**: `composer.json`, `composer.lock`
  - **Instructions**: `composer require` filament/filament, livewire/livewire (bila belum bawaan Filament), spatie/laravel-activitylog, barryvdh/laravel-dompdf, endroid/qr-code, maatwebsite/excel; dev: pestphp/pest, pest-plugin-laravel, pest-plugin-livewire. Inisialisasi Pest.
  - **Acceptance Criteria**: `composer install` bersih; `./vendor/bin/pest` berjalan (boleh 0 test).

- [ ] **Task M0.3: Konfigurasi environment dan zona waktu**
  - **Goal**: DB, mail, queue database, cache/session database, UTC di penyimpanan.
  - **Target Files/Location**: `.env.example`, `config/app.php`, `config/queue.php`
  - **Instructions**: Set `APP_TIMEZONE=UTC`; tambah `config('app.display_timezone')='Asia/Jakarta'` dari env. `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`. Buat migration tabel framework (`queue:table`, `cache:table`, `session:table`, `queue:failed-table`, `queue:batches-table`). Jangan commit kredensial.
  - **Acceptance Criteria**: `php artisan migrate` pada DB kosong berhasil; `config('app.timezone')==='UTC'`.

- [ ] **Task M0.4: Service waktu Jakarta**
  - **Goal**: Satu sumber aturan waktu (PD-05).
  - **Target Files/Location**: `app/Services/JakartaTime.php`, `tests/Unit/JakartaTimeTest.php`
  - **Instructions**: Method: `today()` (tanggal Jakarta), `endOfDay(Carbon|string $date)` → 23:59:59 Jakarta dalam UTC, `toDisplay($utc)`, `businessDaysBetween($a,$b)` (Senin–Jumat, libur tidak dikecualikan).
  - **Acceptance Criteria**: Unit test: date-only inklusif sampai 23:59:59 Jakarta; hitung hari kerja benar melintasi akhir pekan (AC-48).

- [ ] **Task M0.5: Migration master jurusan, kelas, academic_periods**
  - **Goal**: Tiga tabel master dasar.
  - **Target Files/Location**: `database/migrations/*_create_jurusan_table.php`, `*_create_kelas_table.php`, `*_create_academic_periods_table.php`, `app/Models/{Jurusan,Kelas,AcademicPeriod}.php`
  - **Instructions**: Ikuti `schema.sql` Bagian 1 termasuk CHECK dan UNIQUE. Tanpa `walas_id` di `kelas`. Tambah factory.
  - **Acceptance Criteria**: Migrate berhasil; insert `tahun_ajaran` format salah ditolak DB.

- [ ] **Task M0.6: Migration users, user_roles, otp_codes**
  - **Goal**: Akun, multi-role, OTP.
  - **Target Files/Location**: `database/migrations/*_create_users_table.php`, `*_create_user_roles_table.php`, `*_create_otp_codes_table.php`, `app/Models/{User,UserRole,OtpCode}.php`
  - **Instructions**: Ikuti `schema.sql` Bagian 2 (CHECK domain email, CHECK siswa wajib jurusan+kelas, generated `jurusan_key/kelas_key`). Relasi `User::roles()`. Tambah factory per tipe.
  - **Acceptance Criteria**: Insert role ganda (user,role,scope sama) ditolak DB; email non-domain sekolah ditolak DB.

- [ ] **Task M0.7: Migration activity_log dan notifikasi**
  - **Goal**: Audit dan notifikasi in-app.
  - **Target Files/Location**: `database/migrations/*_create_activity_log_table.php`, `*_create_notifications_table.php`, `*_create_notification_delivery_failures_table.php`, `app/Models/NotificationDeliveryFailure.php`
  - **Instructions**: Ikuti `schema.sql` Bagian 13. Konfigurasi activitylog memakai tabel ini.
  - **Acceptance Criteria**: Migrate berhasil; `activity()->log('x')` menulis baris.

- [ ] **Task M0.8: Panel Filament `hubin` dan `app` (kosong)**
  - **Goal**: Dua panel dengan auth session yang sama.
  - **Target Files/Location**: `app/Providers/Filament/HubinPanelProvider.php`, `AppPanelProvider.php`, `bootstrap/providers.php`
  - **Instructions**: Panel `hubin` path `/hubin`, `canAccessPanel` hanya bila `active_role==='hubin'`. Panel `app` path `/app`, akses untuk selain Hubin. Login memakai halaman kustom (M1), bukan login Filament bawaan; nonaktifkan registrasi/reset password bawaan.
  - **Acceptance Criteria**: Panel terbuka untuk user ber-role sesuai (dites lewat factory); user tanpa role mendapat 403.

- [ ] **Task M0.9: Middleware `EnsureActiveRoleMatches` dan helper active_role**
  - **Goal**: Otorisasi berbasis `session('active_role')`.
  - **Target Files/Location**: `app/Http/Middleware/EnsureActiveRoleMatches.php`, `app/Services/ActiveRole.php`, `bootstrap/app.php`
  - **Instructions**: Middleware menerima parameter role; cek role ada di `user_roles` DAN sama dengan `active_role`. Helper mengembalikan role + scope (jurusan_id/kelas_id) aktif. Tolak 403.
  - **Acceptance Criteria**: Test: user Kaprog+Guru dengan `active_role=guru_pembimbing` ke route Kaprog → 403 (AC-06).

- [ ] **Task M0.10: Layanan validasi upload dan unduhan private**
  - **Goal**: Satu jalur upload/unduh aman (PD-09).
  - **Target Files/Location**: `app/Services/UploadValidator.php`, `app/Services/PrivateFileStore.php`, `app/Http/Controllers/PrivateFileController.php`, `config/filesystems.php`
  - **Instructions**: Disk `private` di luar public. Profil: `laporan` (pdf/doc/docx, 10MB), `foto` (jpg/jpeg/png, 5MB), `balasan` (pdf/jpg/png, 10MB). Validasi ekstensi DAN MIME dari deteksi konten; tolak mismatch/kosong/oversize. Simpan di `{academic_period_id}/{jenis}/{uuid}.{ext}`. Controller unduh memanggil callback otorisasi per objek (Policy).
  - **Acceptance Criteria**: Test: `.pdf` berisi konten gambar ditolak; oversize ditolak; unduhan tanpa izin 403/404; tidak ada URL langsung (AC-49).

- [ ] **Task M0.11: Seeder data dev**
  - **Goal**: Data dummy untuk uji manual.
  - **Target Files/Location**: `database/seeders/DevSeeder.php`
  - **Instructions**: 2 jurusan, 4 kelas, 1 periode aktif, user per role (siswa ×6, guru multi-role, kaprog, walas, hubin, kepsek). Password awal = NIS/NIP ter-hash. Jangan data siswa nyata.
  - **Acceptance Criteria**: `migrate:fresh --seed` berhasil.

**Checkpoint M0**: migrate dari DB kosong, `pest` hijau, panel `/hubin` dan `/app` dapat diakses sesuai role dummy, test AC-06 lulus. Catat hasil.

---

# MILESTONE 1 — Autentikasi

- [ ] **Task M1.1: Layanan notifikasi (email + in-app + retry)**
  - **Goal**: Satu pintu pengiriman notifikasi (PD-06).
  - **Target Files/Location**: `app/Services/NotificationDispatcher.php`, `app/Notifications/BaseNotification.php`, `app/Jobs/SendEmailNotificationJob.php`
  - **Instructions**: Kirim in-app (tabel notifications) + email lewat queue. Retry transient maks 3x (backoff), kegagalan permanen → tulis `notification_delivery_failures`. Push tidak diimplementasikan (best-effort, ditunda). Tanpa menulis rahasia ke log.
  - **Acceptance Criteria**: Test: email gagal 3x menulis satu baris failure; kanal yang sukses tidak terkirim ulang saat retry (AC-18).

- [ ] **Task M1.2: Halaman login (email + password, validasi domain)**
  - **Goal**: Login dengan email sebagai identifier.
  - **Target Files/Location**: `app/Livewire/Public/Login.php`, `resources/views/livewire/public/login.blade.php`, `routes/web.php`
  - **Instructions**: Validasi domain `@smkn1kotabekasi.sch.id` saat submit sebelum OTP. NIS/NIP sebagai identifier ditolak. Pesan galat generik (tidak membocorkan akun). Rate limit login per akun + IP.
  - **Acceptance Criteria**: Email luar domain ditolak sebelum OTP; input NIS ditolak (AC-01). Responsif 360px.

- [ ] **Task M1.3: OtpService (terbit & verifikasi)**
  - **Goal**: OTP first-login aman.
  - **Target Files/Location**: `app/Services/OtpService.php`, `app/Mail/OtpMail.php`, `tests/Feature/Auth/OtpTest.php`
  - **Instructions**: OTP 6 digit acak kuat, simpan SHA-256, berlaku 1 menit elapsed. Menerbitkan OTP baru menginvalidasi yang lama (`invalidated_at`). Verifikasi: maks 3 percobaan; percobaan ke-4 menginvalidasi paksa (AC-04). Bandingkan hash secara aman.
  - **Acceptance Criteria**: Test: benar lolos; kedaluwarsa gagal; 3 salah lalu ke-4 → OTP invalid walau belum 1 menit.

- [ ] **Task M1.4: Rate limiter `otp-verify` dan `otp-resend` terpisah**
  - **Goal**: Batas resend 5x/30 menit dengan cooldown 30 menit.
  - **Target Files/Location**: `app/Providers/AppServiceProvider.php`, `app/Services/OtpService.php`
  - **Instructions**: Dua limiter independen (key berbeda). Resend ke-6 dalam 30 menit diblokir; tampilkan sisa cooldown. Hitung dari baris `otp_codes` per 30 menit terakhir.
  - **Acceptance Criteria**: Test: resend ke-6 diblokir (AC-05); verify dan resend tidak berbagi counter.

- [ ] **Task M1.5: Alur first-login: login → OTP → halaman verifikasi**
  - **Goal**: Urutan OTP dulu, baru ganti password (AC-02).
  - **Target Files/Location**: `app/Livewire/Public/VerifyOtp.php`, `app/Http/Middleware/EnsureFirstLoginCompleted.php`, `routes/web.php`
  - **Instructions**: Kredensial valid + `is_first_login=true` → kirim OTP, simpan state sesi "otp_pending", arahkan `/verify-otp`. Selama belum selesai, semua route terautentikasi diblokir middleware. OTP benar → set `email_verified_at`, arahkan `/force-password-reset`.
  - **Acceptance Criteria**: Test: first-login tidak bisa mengakses `/app` sebelum OTP dan ganti password; tidak langsung ke halaman ganti password (AC-02).

- [ ] **Task M1.6: Halaman ganti password wajib**
  - **Goal**: Validasi password baru dan set `is_first_login=false`.
  - **Target Files/Location**: `app/Livewire/Public/ForcePasswordReset.php`, `tests/Feature/Auth/PasswordResetTest.php`
  - **Instructions**: Min 8 karakter, tidak boleh sama dengan NIS/NIP, pesan validasi spesifik. Sukses: simpan hash, `is_first_login=false`, login penuh.
  - **Acceptance Criteria**: Test AC-03 dan AC-03a lulus; login berikutnya tidak memicu OTP.

- [ ] **Task M1.7: Role switcher + penyimpanan `active_role`**
  - **Goal**: Pindah peran tanpa logout.
  - **Target Files/Location**: `app/Livewire/App/RoleSwitcher.php`, integrasi ke topbar kedua panel
  - **Instructions**: Dropdown menampilkan role milik user. Server memvalidasi role dimiliki sebelum menyimpan ke sesi. Redirect ke dashboard role terkait. Satu peran → tanpa dropdown.
  - **Acceptance Criteria**: Test: set role yang tidak dimiliki → 403; berganti peran mengubah navigasi dan scope data.

- [ ] **Task M1.8: Policy dasar scope (kelas/jurusan/ownership)**
  - **Goal**: Fondasi scoping objek.
  - **Target Files/Location**: `app/Policies/Concerns/ScopesByActiveRole.php`, `tests/Unit/ScopeTest.php`
  - **Instructions**: Trait menyediakan `isKaprogOf($jurusanId)`, `isWalasOf($kelasId)`, `isGuruOf($pengajuanSiswa)` berdasarkan `ActiveRole`. Dipakai semua Policy berikutnya.
  - **Acceptance Criteria**: Unit test: Kaprog RPL tidak lolos untuk siswa TKR; Walas kelas A tidak lolos untuk kelas B.

**Checkpoint M1**: akun seed menyelesaikan first-login end-to-end; uji OTP salah/kedaluwarsa/attempt-limit, resend cooldown, password = NIS ditolak, role tidak aktif 403, layar 360px. Catat hasil per kasus.

---

# MILESTONE 2 — Master Data (Hubin)

- [ ] **Task M2.1: Migration `companies`**
  - **Goal**: Tabel perusahaan dengan signer NULLABLE.
  - **Target Files/Location**: `database/migrations/*_create_companies_table.php`, `app/Models/Company.php`
  - **Instructions**: Ikuti `schema.sql` Bagian 3 (UNIQUE email/website/telp_fax, CHECK signer, `merged_into_id`, `source`).
  - **Acceptance Criteria**: Duplikat email ditolak DB; `verified_at` tanpa nama/email ditolak DB.

- [ ] **Task M2.2: CompanyNormalizer dan aturan keunikan**
  - **Goal**: Normalisasi + validasi strict vs soft.
  - **Target Files/Location**: `app/Services/CompanyNormalizer.php`, `app/Rules/CompanyUniqueStrict.php`
  - **Instructions**: Lowercase+trim email/website (buang skema `https://`/`www.`/trailing slash), telp hanya digit. Rule strict menolak (422) bila email/website/telp sudah ada (abaikan record yang di-merge/dirinya sendiri).
  - **Acceptance Criteria**: Test AC-12: email sama → 422; nama/alamat mirip → tidak menolak.

- [ ] **Task M2.3: CompanyResolver (Levenshtein, soft-warning)**
  - **Goal**: Saran perusahaan mirip, dipakai form siswa dan impor.
  - **Target Files/Location**: `app/Services/CompanyResolver.php`, `tests/Unit/CompanyResolverTest.php`
  - **Instructions**: Method `suggest(string $nama, ?string $alamat): Collection` — kandidat via LIKE prefix/token lalu skor Levenshtein ternormalisasi di PHP; abaikan record `merged_into_id`. Ambang dapat dikonfigurasi. Hanya mengembalikan saran, tidak menolak.
  - **Acceptance Criteria**: "CV Ajikyo" menyarankan "CV Ajikiyo" (AC-11 unit).

- [ ] **Task M2.4: `CompanyResource` Filament (CRUD)**
  - **Goal**: Hubin mengelola perusahaan.
  - **Target Files/Location**: `app/Filament/Hubin/Resources/CompanyResource.php` (+ Pages)
  - **Instructions**: Form + tabel + filter. Validasi M2.2 dan soft-warning M2.3 saat simpan. Hanya `active_role=hubin`.
  - **Acceptance Criteria**: CRUD berjalan; role non-Hubin 403.

- [ ] **Task M2.5: Aksi merge perusahaan**
  - **Goal**: Gabung duplikat tanpa kehilangan histori.
  - **Target Files/Location**: `app/Actions/Company/MergeCompanyAction.php`, aksi di `CompanyResource`
  - **Instructions**: Dalam transaksi: set `merged_into_id` pada sumber, pindahkan referensi (pengajuan_pkl, pengajuan_siswa, plotting, dll. yang sudah ada saat ini) ke target, audit log. Tolak merge ke diri sendiri dan rantai melingkar. Snapshot JSON lama tidak diubah.
  - **Acceptance Criteria**: Test: referensi pindah; snapshot lama utuh; merge diri sendiri ditolak.

- [ ] **Task M2.6: `AcademicPeriodResource`**
  - **Goal**: CRUD tahun ajaran + batch.
  - **Target Files/Location**: `app/Filament/Hubin/Resources/AcademicPeriodResource.php`
  - **Instructions**: Field sesuai tabel; toggle `locked_for_new_submission` dan `is_active`; larang hapus periode yang sudah punya data terkait.
  - **Acceptance Criteria**: CRUD berjalan; duplikat (tahun, batch) ditolak; >1 periode aktif diizinkan.

- [ ] **Task M2.7: `UserResource` + penugasan role**
  - **Goal**: Kelola akun dan peran.
  - **Target Files/Location**: `app/Filament/Hubin/Resources/UserResource.php`, relation manager `UserRolesRelationManager`
  - **Instructions**: Filter Guru/Siswa. Relation manager menambah/menghapus baris `user_roles` dengan scope (jurusan untuk kaprog, kelas untuk walas). Aksi "Reset OTP/first-login" (set `is_first_login=true`, password = hash NIS/NIP) dengan audit.
  - **Acceptance Criteria**: Kaprog tanpa jurusan ditolak (CHECK + validasi form); reset memaksa alur first-login lagi.

- [ ] **Task M2.8: Kelola signer industri + verifikasi email**
  - **Goal**: Nama + email terverifikasi Pembimbing/Kepala Industri (AC-52).
  - **Target Files/Location**: `app/Actions/Company/SendSignerVerificationAction.php`, `app/Http/Controllers/SignerEmailVerificationController.php`, bagian form di `CompanyResource`
  - **Instructions**: Hubin mengisi nama+email signer lalu menekan "Kirim verifikasi" → email berisi signed URL berbatas waktu → klik mengisi `*_email_verified_at`. Mengubah nama/email mengosongkan `verified_at`. Tolak nama generik kosong.
  - **Acceptance Criteria**: Test: signer tanpa verifikasi ditandai "belum siap"; ubah email mereset verifikasi.

- [ ] **Task M2.9: Impor massal (perusahaan, akun, periode)**
  - **Goal**: Hubin mengimpor data memakai resolver yang sama.
  - **Target Files/Location**: `app/Filament/Hubin/Pages/BulkImport.php`, `app/Imports/{CompaniesImport,UsersImport,PeriodsImport}.php`
  - **Instructions**: Template xlsx. Setiap baris divalidasi dengan M2.2/M2.3; baris bermasalah dilaporkan, baris valid tetap masuk. Perusahaan mirip → ditandai "perlu tinjauan", tidak diblokir.
  - **Acceptance Criteria**: Impor 20 baris dengan 3 duplikat email: 17 masuk, 3 dilaporkan (AC-11/12 berlaku di impor).

**Checkpoint M2**: Hubin membuat/mengubah perusahaan, akun, periode; uji duplikat strict, warning nama/alamat, merge, impor, signer verifikasi, dan akses non-Hubin ditolak.

---

# MILESTONE 3 — Dokumen Dasar (nomor surat, TTD, PDF, QR)

> Dipindah sebelum pengajuan (PRD Lampiran A7): surat pengantar membutuhkan fondasi ini.

- [ ] **Task M3.1: Migration `signature_assets`, `nomor_surat_counters`, `document_qr_tokens`**
  - **Goal**: Tabel dokumen dasar.
  - **Target Files/Location**: tiga migration + model `SignatureAsset`, `NomorSuratCounter`, `DocumentQrToken`
  - **Instructions**: Ikuti `schema.sql` Bagian 3 (signature_assets), 6, 12.
  - **Acceptance Criteria**: Migrate berhasil; dua `signature_assets` aktif untuk owner sama ditolak DB.

- [ ] **Task M3.2: `NomorSuratService`**
  - **Goal**: Nomor atomik per (tahun_ajaran, jenis_surat).
  - **Target Files/Location**: `app/Services/NomorSuratService.php`
  - **Instructions**: `next(string $tahunAjaran, string $jenis): array{urut,string}` — dalam transaksi pemanggil: `firstOrCreate` baris counter lalu `SELECT … FOR UPDATE`, naikkan, kembalikan nomor + string format `[Urut/Klasifikasi]/[Klasifikasi/Urut]/SMKN.1-CADISDIK WIL 3` (klasifikasi dikonfigurasi). Dipanggil hanya di dalam transaksi pembuatan dokumen.
  - **Acceptance Criteria**: Test AC-46: counter tiap jenis terpisah; AC-47: tahun ajaran baru mulai dari 1.

- [ ] **Task M3.3: Uji konkurensi nomor surat**
  - **Goal**: Bukti tidak ada duplikat.
  - **Target Files/Location**: `tests/Feature/Documents/NomorSuratConcurrencyTest.php`
  - **Instructions**: Dua transaksi paralel (proses terpisah/`DB::transaction` dengan lock) menerbitkan nomor jenis sama; verifikasi unik dan berurutan. Verifikasi rollback tidak menghasilkan nomor ganda.
  - **Acceptance Criteria**: Tidak ada duplikat `(tahun_ajaran, nomor_urut)`; hasil didokumentasikan.

- [ ] **Task M3.4: Upload TTD Kepsek (Hubin)**
  - **Goal**: Scan TTD tersedia untuk surat otomatis.
  - **Target Files/Location**: `app/Filament/Hubin/Resources/SignatureAssetResource.php`
  - **Instructions**: Upload png/jpg (≤5MB) lewat `UploadValidator`; mengaktifkan aset baru menonaktifkan yang lama. Hanya Hubin; file tidak dapat diunduh umum.
  - **Acceptance Criteria**: Hanya satu aset aktif; non-Hubin 403.

- [ ] **Task M3.5: `QrTokenService` dan route `/verify/{token}`**
  - **Goal**: Verifikasi dokumen publik, payload minimal.
  - **Target Files/Location**: `app/Services/QrTokenService.php`, `app/Http/Controllers/VerifyDocumentController.php`, `resources/views/verify.blade.php`
  - **Instructions**: `issue($type,$id,$nomor)` membuat token acak kuat, simpan SHA-256, kembalikan URL. Halaman menampilkan hanya nomor surat, status (valid/dicabut), tanggal terbit. Token tidak dikenal → pesan netral tanpa detail. Tanpa bootstrap panel.
  - **Acceptance Criteria**: Token valid → 3 field; dicabut/tak dikenal → pesan netral (checkpoint M3).

- [ ] **Task M3.6: `LetterPdfRenderer` dasar**
  - **Goal**: Render PDF surat dengan kop, nomor, TTD, QR.
  - **Target Files/Location**: `app/Services/LetterPdfRenderer.php`, `resources/views/pdf/letter-base.blade.php`
  - **Instructions**: Terima DTO (judul, nomor, isi, signature_asset, qr_url). Simpan ke disk private lewat `PrivateFileStore`. Dijalankan di queue (`GenerateLetterPdfJob`) dan idempoten (tidak membuat nomor baru saat retry).
  - **Acceptance Criteria**: PDF berisi nomor, TTD, QR; retry job tidak mengubah nomor.

**Checkpoint M3**: terbitkan surat dummy dengan nomor unik; QR membuka `/verify/{token}` dengan field publik saja; token cabut/tak valid ditolak; uji dua penerbitan bersamaan.

---

# MILESTONE 4 — Pengajuan PKL (Individu → Company Group → Penerimaan)

- [ ] **Task M4.1: Migration `pengajuan_pkl`, `pengajuan_siswa`, VIEW kuota**
  - **Goal**: Tabel inti pengajuan.
  - **Target Files/Location**: tiga migration (`create_pengajuan_pkl`, `create_pengajuan_siswa`, `create_v_kuota_perusahaan_view`), model `PengajuanPkl`, `PengajuanSiswa`
  - **Instructions**: Ikuti `schema.sql` Bagian 4 persis: `company_id`, `pengajuan_pkl_id` NULLABLE, composite FK, generated `active_siswa_id`, semua CHECK. VIEW hanya baca.
  - **Acceptance Criteria**: Migrate dari kosong berhasil; CHECK provisional/snapshot/group ditolak DB bila dilanggar.

- [ ] **Task M4.2: Uji unik pengajuan aktif per siswa per periode**
  - **Goal**: Bukti PD-15 di DB.
  - **Target Files/Location**: `tests/Feature/Pengajuan/ActiveUniqueTest.php`
  - **Instructions**: Insert aktif ganda (dua transaksi bersamaan) → hanya satu lolos. Status `rejected`/`withdrawn` mengizinkan record baru.
  - **Acceptance Criteria**: AC-51 lulus.

- [ ] **Task M4.3: Enum status + transition table**
  - **Goal**: Transisi eksplisit, tolak yang tak dikenal.
  - **Target Files/Location**: `app/Enums/PengajuanStatus.php`, `app/Domain/Pengajuan/Transitions.php`
  - **Instructions**: Definisikan pasangan (dari → ke, aktor yang boleh) sesuai PRD F3. Method `assertCanTransition($from,$to,$actorRole)` melempar exception → 409. Tanpa paket state machine.
  - **Acceptance Criteria**: Unit test: semua transisi valid lolos; contoh tidak valid (`draft`→`sedang_pkl`) ditolak.

- [ ] **Task M4.4: Halaman buat draft pengajuan (siswa)**
  - **Goal**: Form pengajuan individu.
  - **Target Files/Location**: `app/Filament/App/Pages/PengajuanCreate.php` (atau Resource `PengajuanSiswaResource`)
  - **Instructions**: Field: perusahaan (select searchable memakai `CompanyResolver`, autofill alamat/email/telp), atau input perusahaan baru; periode (hanya yang `is_active` dan tidak locked). Kontak CP ditampilkan ter-mask sampai disetujui Kaprog (masking di server, bukan CSS). Tanpa guru pembimbing.
  - **Acceptance Criteria**: Draft tersimpan dengan `company_id`; periode terkunci tidak bisa dipilih; CP ter-mask di respons.

- [ ] **Task M4.5: Konfirmasi kemiripan + validasi strict saat submit**
  - **Goal**: Modal soft-warning sebelum simpan (AC-11, AC-12).
  - **Target Files/Location**: komponen di `PengajuanCreate`, `app/Actions/Pengajuan/CreateOrAttachCompanyAction.php`
  - **Instructions**: Perusahaan baru: panggil `suggest`; bila ada kandidat, tampilkan modal "pakai yang sudah ada / tetap buat baru" SEBELUM data tersimpan. Email/website/telp duplikat → 422. `source='siswa'`, signer kosong.
  - **Acceptance Criteria**: Test AC-11 dan AC-12 lulus.

- [ ] **Task M4.6: `SubmitPengajuanAction`**
  - **Goal**: Submit idempoten dengan snapshot immutable.
  - **Target Files/Location**: `app/Actions/Pengajuan/SubmitPengajuanAction.php`
  - **Instructions**: Dalam transaksi: cek milik sendiri, periode tidak locked, status `draft`; simpan `snapshot_company_json` (data perusahaan saat itu); status → `menunggu_verifikasi_kaprog`; audit. Double klik tidak menggandakan apa pun.
  - **Acceptance Criteria**: Test: status pertama `menunggu_verifikasi_kaprog` (AC-07); perubahan master data setelahnya tidak mengubah snapshot; submit ganda aman.

- [ ] **Task M4.7: Gate 1 Kaprog (antrean + approve/reject)**
  - **Goal**: Verifikasi relevansi.
  - **Target Files/Location**: `app/Filament/App/Resources/Kaprog/PengajuanVerifikasiResource.php`, `app/Actions/Pengajuan/ApproveKaprogAction.php`, `RejectKaprogAction.php`
  - **Instructions**: Antrean scoped jurusan aktif. Approve → `menunggu_ttd_walas` (tidak memakai kuota). Reject wajib catatan ≥10 karakter → `ditolak_kaprog`. Tampilkan kuota perusahaan (baca VIEW) sebagai peringatan, bukan penghalang.
  - **Acceptance Criteria**: Catatan kosong ditolak (AC-08); Kaprog jurusan lain tidak melihat/aksi (403/404).

- [ ] **Task M4.8: Revisi dan histori penolakan**
  - **Goal**: Siswa merevisi dan Kaprog/Walas melihat histori (AC-09).
  - **Target Files/Location**: `app/Actions/Pengajuan/ReviseToDraftAction.php`, komponen riwayat di halaman detail
  - **Instructions**: `ditolak_*` → `draft` pada record yang sama; catatan penolakan tetap ada di `activity_log` dan ditampilkan di timeline detail.
  - **Acceptance Criteria**: Test AC-09: catatan lama terbaca setelah resubmit.

- [ ] **Task M4.9: Gate 2 Walas (TTD/tolak)**
  - **Goal**: Gate kedua scoped kelas.
  - **Target Files/Location**: `app/Filament/App/Resources/Walas/PengajuanTtdResource.php`, `app/Actions/Pengajuan/SignWalasAction.php`, `RejectWalasAction.php`
  - **Instructions**: Hanya status `menunggu_ttd_walas`, hanya siswa kelas Walas aktif. TTD → `menunggu_surat_pengantar`. Reject wajib catatan.
  - **Acceptance Criteria**: Walas kelas lain 403; urutan gate Kaprog dulu terjaga (AC-07).

- [ ] **Task M4.10: Grouping otomatis ke company group**
  - **Goal**: Kelompokkan by company+periode.
  - **Target Files/Location**: `app/Actions/Pengajuan/AssignToCompanyGroupAction.php`, listener dipanggil dari `SignWalasAction`
  - **Instructions**: Dalam transaksi: cari group `terbentuk` (belum `surat_terbit`) untuk company+periode; bila tidak ada, buat. Set `pengajuan_pkl_id`. Siswa yang masuk setelah surat terbit membuat group baru. Idempoten.
  - **Acceptance Criteria**: Dua siswa, perusahaan sama → satu group (AC-10); susulan setelah surat terbit → group baru.

- [ ] **Task M4.11: Hubin menerbitkan surat pengantar**
  - **Goal**: Nomor, PDF, QR, kirim.
  - **Target Files/Location**: `app/Filament/Hubin/Resources/CompanyGroupResource.php`, `app/Actions/Pengajuan/IssueSuratPengantarAction.php`
  - **Instructions**: Transaksi: `NomorSuratService` (jenis `surat_pengantar`), isi kolom surat di `pengajuan_pkl`, `QrTokenService`, dispatch PDF job, status group → `surat_terbit`, semua anggota `menunggu_surat_pengantar` → `surat_pengantar_terbit` → `menunggu_balasan_perusahaan` setelah Hubin "tandai terkirim". Tahan bila scan TTD Kepsek belum ada.
  - **Acceptance Criteria**: Penerbitan ganda tidak membuat nomor/surat ganda; tanpa TTD aktif → ditolak dengan pesan jelas.

- [ ] **Task M4.12: Siswa upload surat balasan**
  - **Goal**: Unggah balasan perusahaan.
  - **Target Files/Location**: `app/Actions/Pengajuan/UploadSuratBalasanAction.php`, komponen di detail pengajuan
  - **Instructions**: Profil `balasan` (pdf/jpg/png ≤10MB). Hanya milik sendiri dan status `menunggu_balasan_perusahaan`. Siswa memilih hasil `diterima/ditolak` hanya sebagai informasi; keputusan resmi tetap di Kaprog.
  - **Acceptance Criteria**: Mismatch MIME ditolak; siswa lain 403.

- [ ] **Task M4.13: `AcceptCompanyAction` dengan lock kuota — TASK KRITIS**
  - **Goal**: Penerimaan placement dengan kuota 6 aman concurrency.
  - **Target Files/Location**: `app/Actions/Pengajuan/AcceptCompanyAction.php`
  - **Instructions**: Preconditions: status `menunggu_balasan_perusahaan`, balasan `diterima`, Kaprog berwenang. Dalam satu transaksi: `Company::lockForUpdate()` pada company_id; hitung ulang `COUNT(*) WHERE status='sedang_pkl' AND company_id AND academic_period_id` langsung dari tabel (bukan VIEW); jika ≥6 → lempar 409 tanpa efek samping; jika lolos: status `sedang_pkl`, `is_provisional` sesuai input Kaprog, `accepted_by/at`, `tanggal_*_pkl` default dari periode (dapat diubah), status group → `berjalan`, audit.
  - **Acceptance Criteria**: Kuota 5 + 6th masuk berhasil; ke-7 → 409 tanpa perubahan status/surat/notifikasi; provisional ikut dihitung (AC-13).

- [ ] **Task M4.14: Uji konkurensi kuota**
  - **Goal**: Bukti total tidak melebihi 6.
  - **Target Files/Location**: `tests/Feature/Pengajuan/QuotaConcurrencyTest.php`
  - **Instructions**: Seed 5 `sedang_pkl`; jalankan dua approval paralel (proses/koneksi terpisah). Verifikasi satu 200 dan satu 409, total 6.
  - **Acceptance Criteria**: AC-14 lulus; hasil dan cara menjalankan dicatat.

- [ ] **Task M4.15: Penolakan perusahaan (`rejected`)**
  - **Goal**: Tutup aplikasi satu siswa saja.
  - **Target Files/Location**: `app/Actions/Pengajuan/CompanyRejectAction.php`
  - **Instructions**: Dari `menunggu_balasan_perusahaan` → `rejected`; tidak menyentuh siswa lain; slot tidak terpakai. Jika semua anggota group tertutup → group `bubar`.
  - **Acceptance Criteria**: AC-15a lulus.

- [ ] **Task M4.16: Hubin konfirmasi dokumen fisik**
  - **Goal**: Clear `is_provisional` tanpa mengubah keputusan.
  - **Target Files/Location**: `app/Filament/Hubin/Pages/VerifikasiDokumenFisik.php`, `app/Actions/Pengajuan/ConfirmPhysicalDocumentAction.php`
  - **Instructions**: Hanya Hubin. Set `is_provisional=false`, `dokumen_fisik_diterima_at`, `dokumen_fisik_confirmed_by`. Tidak mengubah status/kuota/`accepted_by`.
  - **Acceptance Criteria**: Kaprog tidak dapat; status tetap `sedang_pkl`; audit tercatat (PD-03).

- [ ] **Task M4.17: `SendProvisionalReminderJob`**
  - **Goal**: Reminder harian pasif.
  - **Target Files/Location**: `app/Jobs/SendProvisionalReminderJob.php`, `routes/console.php`
  - **Instructions**: Dijadwalkan harian (Asia/Jakarta). Pilih `is_provisional=true AND (last_provisional_reminder_at IS NULL OR < today Jakarta)`; kirim via `NotificationDispatcher`; update tanggal. Tanpa eskalasi.
  - **Acceptance Criteria**: Test AC-18: dijalankan 2x sehari → 1 notifikasi; kegagalan push tidak memblokir.

- [ ] **Task M4.18: Update status non-placement di group campuran (Kaprog)**
  - **Goal**: Kaprog menyesuaikan status per siswa tanpa melewati gate.
  - **Target Files/Location**: `app/Filament/App/Resources/Kaprog/CompanyGroupResource.php`, `app/Actions/Pengajuan/UpdateNonPlacementStatusAction.php`
  - **Instructions**: Daftar group by jurusan Kaprog; aksi hanya untuk transisi yang diizinkan table M4.3 dan TIDAK untuk `sedang_pkl`/penerimaan (harus lewat M4.13).
  - **Acceptance Criteria**: Upaya set `sedang_pkl` lewat aksi ini ditolak.

- [ ] **Task M4.19: Skenario siswa susulan**
  - **Goal**: Proses penuh dari awal, mewarisi guru bila ada.
  - **Target Files/Location**: `tests/Feature/Pengajuan/SusulanTest.php`
  - **Instructions**: Test end-to-end: perusahaan sudah punya group + plotting; siswa baru mengajukan dari awal, melewati semua gate, mendapat group baru. (Pewarisan guru diverifikasi setelah M5.3.)
  - **Acceptance Criteria**: AC-15 lulus (tanpa shortcut gate).

- [ ] **Task M4.20: Withdrawal (ajukan + setujui)**
  - **Goal**: Mundur dengan alasan wajib.
  - **Target Files/Location**: `app/Actions/Pengajuan/RequestWithdrawalAction.php`, `ApproveWithdrawalAction.php`, halaman `/app` Kaprog "Withdrawal"
  - **Instructions**: Siswa/Kaprog mengajukan (alasan wajib) pada `sedang_pkl`; Kaprog menyetujui → `withdrawn`, `withdrawn_at`, bimbingan non-archived di-set `is_archived=true` + `archived_at` (tidak dihapus), slot lepas. Pengajuan ulang = record baru (`resubmitted_from_id`).
  - **Acceptance Criteria**: AC-19 lulus (3 bimbingan ter-archive, hitungan record baru = 0, slot lepas).

- [ ] **Task M4.21: Halaman detail & tracking siswa**
  - **Goal**: Siswa melihat status real-time dan histori.
  - **Target Files/Location**: `app/Filament/App/Pages/PengajuanDetail.php`
  - **Instructions**: Timeline status dari `activity_log` + status saat ini + aksi yang tersedia (upload balasan, withdraw). Hanya data milik sendiri. Responsif 360px.
  - **Acceptance Criteria**: Siswa lain tidak dapat membuka (403/404).

**Checkpoint M4**: 6 siswa `sedang_pkl` (termasuk provisional) dihitung; `rejected`/`withdrawn` tidak dihitung; dengan 5 placement dua approval paralel hanya satu berhasil; snapshot tidak berubah; surat pengantar bernomor unik. Catat data awal, aksi, hasil, bukti.

---

# MILESTONE 5 — Plotting, Surat Tugas Pembimbing, Visitasi

- [ ] **Task M5.1: Migration plotting + surat tugas pembimbing**
  - **Goal**: Tabel plotting dan surat tugas.
  - **Target Files/Location**: `*_create_guru_pembimbing_plotting_table.php`, `*_create_surat_tugas_pembimbing_table.php`, model terkait
  - **Instructions**: Ikuti `schema.sql` Bagian 5 dan 7 (UNIQUE company+periode; 1 surat per plotting).
  - **Acceptance Criteria**: Plotting ganda untuk company+periode ditolak DB.

- [ ] **Task M5.2: UI plotting Kaprog**
  - **Goal**: Plot guru by company+periode.
  - **Target Files/Location**: `app/Filament/App/Pages/Kaprog/Plotting.php`, `app/Actions/Plotting/PlotGuruAction.php`
  - **Instructions**: Daftar company yang punya placement `sedang_pkl` di jurusan Kaprog; pilih guru (role `guru_pembimbing`) dengan indikator beban (jumlah company/siswa). Soft-warning multi-wilayah (bukan hard block).
  - **Acceptance Criteria**: Guru bukan `guru_pembimbing` ditolak; warning muncul tapi tidak memblokir.

- [ ] **Task M5.3: Pewarisan guru untuk siswa susulan**
  - **Goal**: Guru otomatis terhubung (AC-16).
  - **Target Files/Location**: `app/Services/GuruResolver.php`, pemakaian di `AcceptCompanyAction`/query "siswa bimbingan"
  - **Instructions**: Guru siswa = plotting `(company_id, academic_period_id)` milik placement; tidak ada kolom guru per siswa. Semua query "siswa bimbingan" memakai resolver ini.
  - **Acceptance Criteria**: Test: siswa susulan langsung tampil di daftar bimbingan guru terplot tanpa plotting ulang.

- [ ] **Task M5.4: Auto-terbit Surat Tugas Pembimbing**
  - **Goal**: Surat otomatis saat plotting tersimpan (AC-20).
  - **Target Files/Location**: `app/Actions/Plotting/IssueSuratTugasPembimbingAction.php`
  - **Instructions**: Dalam transaksi plotting: `NomorSuratService` jenis `surat_tugas_pembimbing`, `signature_asset_id` aktif, `signed_at=now`, QR, dispatch PDF. Tanpa approval Hubin. Bila TTD Kepsek belum ada: simpan plotting, tahan surat, notifikasi Hubin. Ganti guru → surat baru + audit.
  - **Acceptance Criteria**: Satu plotting = satu surat bernomor baru; tidak ada approval Hubin; periode via relasi plotting.

- [ ] **Task M5.5: Migration visitasi**
  - **Goal**: Tabel surat tugas visitasi.
  - **Target Files/Location**: migration `surat_tugas_visitasi`, `surat_tugas_visitasi_companies`, `visitasi_dokumentasi` + model
  - **Instructions**: Ikuti `schema.sql` Bagian 7 (CHECK tanggal, reject note, nomor).
  - **Acceptance Criteria**: Migrate berhasil; `tanggal_selesai < tanggal_mulai` ditolak DB.

- [ ] **Task M5.6: Kaprog mengajukan visitasi**
  - **Goal**: Pengajuan multi-select atau general.
  - **Target Files/Location**: `app/Filament/App/Pages/Kaprog/VisitasiAjukan.php`, `app/Actions/Visitasi/SubmitVisitasiAction.php`
  - **Instructions**: Pilih guru (jurusannya), perusahaan (multi-select dari yang dipegang guru) atau `is_general`, tanggal (default +7 hari). Status `diajukan`.
  - **Acceptance Criteria**: Perusahaan yang bukan milik guru ditolak.

- [ ] **Task M5.7: Hubin approve/reject + generate manual**
  - **Goal**: Approval wajib + nomor surat.
  - **Target Files/Location**: `app/Filament/Hubin/Resources/SuratTugasVisitasiResource.php`, `app/Actions/Visitasi/ApproveVisitasiAction.php`, `RejectVisitasiAction.php`, `GenerateVisitasiManualAction.php`
  - **Instructions**: Approve → `NomorSuratService` jenis `surat_tugas_visitasi`, status `disetujui`. Reject wajib catatan; Kaprog dapat mengajukan ulang. Generate manual Hubin langsung `disetujui` (`diajukan_oleh='hubin'`).
  - **Acceptance Criteria**: AC-21 lulus; nomor tidak bertabrakan dengan jenis lain.

- [ ] **Task M5.8: Kaprog cetak mandiri**
  - **Goal**: PDF surat visitasi.
  - **Target Files/Location**: `app/Actions/Visitasi/PrintVisitasiAction.php`
  - **Instructions**: Hanya status `disetujui`/`dicetak`/`aktif`, scoped jurusan; render PDF (M3.6); status → `dicetak`.
  - **Acceptance Criteria**: Kaprog jurusan lain 403.

- [ ] **Task M5.9: `ExpireSuratTugasVisitasiJob`**
  - **Goal**: Kedaluwarsa otomatis (AC-22).
  - **Target Files/Location**: `app/Jobs/ExpireSuratTugasVisitasiJob.php`, `routes/console.php`
  - **Instructions**: Harian; set `kedaluwarsa` bila `JakartaTime::endOfDay(tanggal_selesai) < now`. Idempoten.
  - **Acceptance Criteria**: Pada tanggal selesai masih aktif sampai 23:59:59 Jakarta; berikutnya kedaluwarsa; job 2x aman.

- [ ] **Task M5.10: Guru memakai visitasi + dokumentasi**
  - **Goal**: Guru melihat surat aktif dan mengunggah foto.
  - **Target Files/Location**: `app/Filament/App/Pages/Guru/Visitasi.php`, `app/Actions/Visitasi/UploadDokumentasiAction.php`
  - **Instructions**: Hanya surat milik guru dan belum kedaluwarsa. Upload jpg/jpeg/png ≤5MB (profil `foto`) per perusahaan yang tercakup.
  - **Acceptance Criteria**: Upload ke surat kedaluwarsa ditolak; perusahaan di luar cakupan ditolak.

**Checkpoint M5**: plot guru pada satu periode → satu surat pembimbing terbit dan dapat dicetak; susulan mewarisi guru; visitasi: ajukan→approve→cetak→kedaluwarsa; QR surat valid.

---

# MILESTONE 6 — Magic Link & Portal Eksternal (infrastruktur)

- [ ] **Task M6.1: Migration `magic_links`, `external_sessions`**
  - **Goal**: Tabel akses eksternal.
  - **Target Files/Location**: dua migration + model `MagicLink`, `ExternalSession`
  - **Instructions**: Ikuti `schema.sql` Bagian 10 (unique `(type, active_reference_id)`).
  - **Acceptance Criteria**: Dua link aktif untuk (type, target) sama ditolak DB.

- [ ] **Task M6.2: `MagicLinkService` (terbit & konsumsi)**
  - **Goal**: Token acak, ter-hash, single-use.
  - **Target Files/Location**: `app/Services/MagicLinkService.php`
  - **Instructions**: `issue(type, referenceId, ttl, ?pin)` → `random_bytes(32)` base64url, simpan SHA-256, PIN 4 digit ter-hash bila perlu; kembalikan URL + PIN sekali tampil. `consume` atomik (`UPDATE … WHERE is_active AND used_at IS NULL`). Sebelum insert, nonaktifkan token target yang kedaluwarsa. TTL default sesuai PRD Q-D.
  - **Acceptance Criteria**: Test: token tidak pernah plaintext di DB/log; konsumsi ganda gagal.

- [ ] **Task M6.3: Revoke/reissue + otoritas per jenis**
  - **Goal**: Kaprog/Guru/Hubin sesuai PD-10 (AC-50).
  - **Target Files/Location**: `app/Actions/MagicLink/RevokeMagicLinkAction.php`, `ReissueMagicLinkAction.php`, `app/Policies/MagicLinkPolicy.php`
  - **Instructions**: Revoke: `is_active=false`, `revoked_at/by/reason`, set `revoked_at` semua `external_sessions` turunan. Reissue: dalam transaksi revoke lama + terbitkan baru (`reissued_from_id`). Policy per tabel PRD §5.8 (Kaprog: sidang + signature P/K.Industri jurusannya; Guru: penilaian industri siswa bimbingannya; Hubin: override lintas jurusan dengan alasan wajib).
  - **Acceptance Criteria**: Test AC-50 semua kombinasi aktor×jenis; override Hubin tanpa alasan ditolak.

- [ ] **Task M6.4: PIN service (attempt + device binding longgar)**
  - **Goal**: PIN sidang/revisi aman.
  - **Target Files/Location**: `app/Services/PinVerifier.php`
  - **Instructions**: Maks 3 salah per `magic_links.id`, lalu `is_active=false` dan tidak ada percobaan ke-4. Fingerprint = hash(User-Agent + hash IP) disimpan pada pemakaian pertama; mismatch → langkah verifikasi tambahan, bukan blok langsung.
  - **Acceptance Criteria**: AC-30 lulus; perubahan jaringan tidak memblokir langsung.

- [ ] **Task M6.5: Sesi OTP email eksternal**
  - **Goal**: OTP time-boxed untuk pengesahan/penilaian industri.
  - **Target Files/Location**: `app/Services/ExternalOtpService.php`
  - **Instructions**: Kirim OTP ke email signer dari `companies`; OTP 5 menit, maks 3 percobaan; verifikasi sukses → `external_sessions.verified_at`, `session_expires_at=+24 jam` elapsed. Resend dibatasi limiter.
  - **Acceptance Criteria**: Test: sesi kedaluwarsa setelah 24 jam; revoke menutup sesi.

- [ ] **Task M6.6: Middleware & layout portal eksternal**
  - **Goal**: Guard terpisah dari auth internal.
  - **Target Files/Location**: `app/Http/Middleware/ExternalAccess.php`, `resources/views/layouts/external.blade.php`, `routes/external.php`
  - **Instructions**: Middleware memvalidasi token (hash, aktif, belum kedaluwarsa/dicabut), tipe sesuai route, dan sesi OTP/PIN yang berlaku. Tidak memakai guard internal. Layout minimal tanpa asset panel. Pesan galat netral. Audit tindakan eksternal dengan `magic_link_id` (tanpa token).
  - **Acceptance Criteria**: Token salah/kedaluwarsa/dicabut → halaman netral + opsi minta link baru; tidak ada kebocoran data lintas scope.

- [ ] **Task M6.7: `ExpireMagicLinksJob`**
  - **Goal**: Link kedaluwarsa nonaktif.
  - **Target Files/Location**: `app/Jobs/ExpireMagicLinksJob.php`
  - **Instructions**: Tiap jam: set `is_active=false` untuk `expires_at < now`; sesi turunan ikut dicabut.
  - **Acceptance Criteria**: Job ulang aman; link baru untuk target sama dapat diterbitkan sesudahnya.

- [ ] **Task M6.8: Pengiriman link (email, wa.me, share)**
  - **Goal**: Distribusi link tanpa broadcast WA.
  - **Target Files/Location**: `app/Mail/MagicLinkMail.php`, `app/Services/ShareLinkBuilder.php`
  - **Instructions**: Email template; tautan `https://wa.me/<nomor>?text=...` untuk klik manual (nomor dari penguji); tombol share native. PIN dikirim terpisah dari link.
  - **Acceptance Criteria**: Link dan PIN tidak pernah dalam satu pesan yang sama; tidak ada integrasi WA API.

**Checkpoint M6**: terbitkan, pakai, revoke, reissue; satu token aktif per target; PIN salah 3x; sesi OTP 24 jam; semua rahasia ter-hash.

---

# MILESTONE 7 — Bimbingan & Pengesahan Laporan (5-TTD)

- [ ] **Task M7.1: Migration bimbingan, laporan_versions, document_signatures**
  - **Goal**: Tabel bimbingan dan rantai TTD.
  - **Target Files/Location**: tiga migration + model
  - **Instructions**: Ikuti `schema.sql` Bagian 8–9 (UNIQUE (versi, step), CHECK step↔signer, rejection ≥10 karakter).
  - **Acceptance Criteria**: Baris `rejected` tanpa catatan ≥10 karakter ditolak DB; step 3 dengan signer `kaprog` ditolak DB.

- [ ] **Task M7.2: Submit bimbingan + guard provisional — TASK KRITIS**
  - **Goal**: Guard di server (AC-17).
  - **Target Files/Location**: `app/Actions/Bimbingan/SubmitBimbinganAction.php`, halaman `/app` siswa "Bimbingan"
  - **Instructions**: Precondition di Action: milik sendiri, `status='sedang_pkl'`, `is_provisional=false`, record bukan archived. Upload profil `laporan`. Pesan jelas bila provisional. Tombol disabled hanya tambahan.
  - **Acceptance Criteria**: Request langsung saat provisional → ditolak, tidak ada baris tersimpan (AC-17).

- [ ] **Task M7.3: Feedback guru per submission**
  - **Goal**: Guru memberi revisi/diterima.
  - **Target Files/Location**: `app/Actions/Bimbingan/GiveFeedbackAction.php`, `app/Filament/App/Resources/Guru/BimbinganFeedbackResource.php`
  - **Instructions**: Hanya guru terplot siswa itu (via `GuruResolver`). Set status, catatan, `reviewed_by/at`. Submission immutable kecuali slot revisi baru.
  - **Acceptance Criteria**: Guru lain 403.

- [ ] **Task M7.4: Guard ≥4 interaksi sebelum "Bagian Akhir" — TASK KRITIS**
  - **Goal**: Validasi di server (AC-23).
  - **Target Files/Location**: `app/Actions/Bimbingan/MarkFinalAction.php`
  - **Instructions**: `COUNT(bimbingan_submissions WHERE pengajuan_siswa_id AND is_archived=false) >= 4` dihitung ulang di server; tandai `is_bagian_akhir`. Hanya satu bagian akhir aktif.
  - **Acceptance Criteria**: 3 interaksi ditolak; interaksi ke-4 diizinkan.

- [ ] **Task M7.5: `SignatureChainService` (mesin rantai)**
  - **Goal**: Aturan sequential di satu tempat.
  - **Target Files/Location**: `app/Domain/Pengesahan/SignatureChainService.php`
  - **Instructions**: Method `sign(step, actor)`, `reject(step, actor, note)`, `openNext(version, step)`. Aturan: step N hanya bila step N−1 `signed` (untuk versi atau versi sebelumnya sesuai partial-reject); baris `pending` dibuat saat step sebelumnya `signed`; baris berubah sekali ke `signed|rejected`; baris `signed` tidak pernah diubah. Semua dalam transaksi + audit.
  - **Acceptance Criteria**: Unit test: sign step 3 saat step 2 belum signed ditolak (AC-25).

- [ ] **Task M7.6: ACC Laporan Final = TTD 1 + trigger rantai**
  - **Goal**: Satu aksi (AC-24).
  - **Target Files/Location**: `app/Actions/Pengesahan/ApproveFinalReportAction.php`, halaman guru "Approve Laporan Final"
  - **Instructions**: Hanya pada submission `is_bagian_akhir`, guru terplot. Dalam satu transaksi: buat `laporan_versions` v1 (`source_bimbingan_submission_id`), step 1 `signed` (`signer_user_id`), buka step 2 `pending`, notifikasi Kaprog.
  - **Acceptance Criteria**: AC-24 lulus; klik ganda tidak membuat versi/step ganda.

- [ ] **Task M7.7: Step 2 Kaprog (sign/reject, bulk)**
  - **Goal**: TTD Kaprog dan bulk approve.
  - **Target Files/Location**: `app/Filament/App/Resources/Kaprog/PengesahanResource.php`, `app/Actions/Pengesahan/SignKaprogAction.php`
  - **Instructions**: Antrean step 2 scoped jurusan. Bulk "Approve Selected" memproses tiap laporan independen (satu gagal tidak membatalkan lainnya, hasil dilaporkan). Sign membuka step 3.
  - **Acceptance Criteria**: AC-28: 7 dari 10 dipilih → 7 maju, 3 tetap.

- [ ] **Task M7.8: Penerbitan link step 3/4 hanya setelah step sebelumnya signed**
  - **Goal**: Tidak ada pre-generate (AC-25).
  - **Target Files/Location**: `app/Actions/Pengesahan/IssueExternalSignatureLinkAction.php`
  - **Instructions**: Dipanggil dari `openNext` ketika membuka step 3/4: cek signer company (nama + `email_verified_at`). Bila belum siap → step tetap `pending`, tahan, notifikasi Hubin. Terbitkan `magic_links` tipe `pengesahan_p_industri|k_industri` ber-`reference_id = document_signatures.id`; kirim email. Simpan snapshot nama/kontak signer di baris signature.
  - **Acceptance Criteria**: Tidak ada link step 3 sebelum step 2 signed; signer belum terverifikasi menahan tanpa error.

- [ ] **Task M7.9: Portal pengesahan eksternal (OTP → sign/reject)**
  - **Goal**: P.Industri dan K.Industri menandatangani.
  - **Target Files/Location**: `app/Livewire/External/PengesahanSign.php`, view terkait, `routes/external.php`
  - **Instructions**: Alur: buka link → OTP email → tampilkan ringkasan laporan (unduh lewat controller berotorisasi token) → Sign atau Reject (catatan ≥10 karakter). Konsumsi link setelah aksi selesai. Idempoten.
  - **Acceptance Criteria**: Aksi hanya untuk signature `pending` target; ulang → pesan "sudah diproses".

- [ ] **Task M7.10: Kepsek approval gateway (step 5)**
  - **Goal**: Review-list dan Approve All.
  - **Target Files/Location**: `app/Filament/App/Pages/Kepsek/ApprovalGateway.php`, `app/Actions/Pengesahan/SignKepsekAction.php`, halaman riwayat
  - **Instructions**: Daftar laporan step 5, mobile-first. Sign tunggal atau bulk; reject dengan catatan. Kepsek hanya melihat data minimum untuk menandatangani. `/riwayat` menampilkan yang sudah ditandatangani.
  - **Acceptance Criteria**: Kepsek tidak mengakses detail siswa/perusahaan di luar kebutuhan; bulk memproses independen.

- [ ] **Task M7.11: Partial-reject + revisi + versi baru — TASK PALING KRITIS**
  - **Goal**: AC-26 dan AC-27.
  - **Target Files/Location**: `app/Actions/Pengesahan/UploadRevisionAction.php`, halaman siswa "Upload Revisi"
  - **Instructions**: Setelah reject di step k: siswa unggah file → buat `laporan_versions` v+1 → buat baris `pending` hanya untuk step k (dan terbitkan link bila k=3/4) pada versi baru. Step <k tetap `signed` mengacu versi lama; jangan mengubah/menyalin/menghapusnya. Laporan legit = step 1–5 masing-masing punya baris `signed` (versi mana pun).
  - **Acceptance Criteria**: Reject step 3 setelah step 1–2 signed → revisi kembali ke step 3; baris step 1–2 tetap `signed`, tidak `pending` (AC-26); versi bertambah (AC-27).

- [ ] **Task M7.12: Pool "Siap Sidang"**
  - **Goal**: Hanya laporan legit penuh (AC-29).
  - **Target Files/Location**: `app/Queries/SiapSidangQuery.php`, tampilan Kaprog
  - **Instructions**: Query: siswa `sedang_pkl` dengan step 1..5 semua punya baris `signed`; scoped jurusan; kecualikan yang sudah terjadwal.
  - **Acceptance Criteria**: Siswa di step 4 (Kepsek belum) tidak muncul; muncul setelah step 5.

- [ ] **Task M7.13: Halaman status pengesahan siswa**
  - **Goal**: Tracking 5-TTD.
  - **Target Files/Location**: `app/Filament/App/Pages/LaporanStatusPengesahan.php`
  - **Instructions**: Tampilkan step, status, catatan reject, aksi "Upload Revisi" bila perlu; hanya data milik sendiri (juga untuk guru bimbingan/Kaprog read-only).
  - **Acceptance Criteria**: Akses lintas scope ditolak.

- [ ] **Task M7.14: Uji kombinasi reject (end-to-end)**
  - **Goal**: Bukti partial-reject di semua step.
  - **Target Files/Location**: `tests/Feature/Pengesahan/PartialRejectMatrixTest.php`
  - **Instructions**: Untuk reject di step 2,3,4,5: verifikasi step sebelumnya tetap signed, revisi kembali ke step penolak, rantai selesai normal setelah revisi, versi bertambah, audit utuh.
  - **Acceptance Criteria**: Semua kombinasi lulus; hasil dicatat.

**Checkpoint M7**: simulasi reject di step 3 setelah step 1–2 signed → revisi kembali ke step 3 saja; sign di luar urutan ditolak; provisional memblokir bimbingan di endpoint; guard 4 interaksi.

---

# MILESTONE 8 — Form Builder, Sidang, Penilaian Industri

- [ ] **Task M8.1: Migration form tables**
  - **Goal**: Tabel form dinamis (JSON).
  - **Target Files/Location**: migration `form_definitions`, `form_sections`, `form_items`, `form_submissions`, `form_submission_siswa` + model
  - **Instructions**: Ikuti `schema.sql` Bagian 11 (generated `nilai_total`, CHECK bentuk submission, UNIQUE per jadwal dan per company/form/periode).
  - **Acceptance Criteria**: Submission industri dengan `pengajuan_siswa_id` terisi ditolak DB (PD-14).

- [ ] **Task M8.2: Form builder Kaprog (2 level)**
  - **Goal**: Section → Items (AC-40).
  - **Target Files/Location**: `app/Filament/App/Resources/Kaprog/FormDefinitionResource.php`
  - **Instructions**: Repeater section sejajar, tiap section punya repeater item (`has_alokasi_waktu` untuk industri). Tanpa nesting. Scoped jurusan + periode.
  - **Acceptance Criteria**: Tiga "kelompok" tersimpan sebagai 3 baris `form_sections` sejajar.

- [ ] **Task M8.3: Opsi Kehadiran & Catatan**
  - **Goal**: Checkbox opsional (AC-41).
  - **Target Files/Location**: field di `FormDefinitionResource`, renderer form submission
  - **Instructions**: `has_kehadiran` menambah 3 input Sakit/Izin/Tanpa Keterangan (free-input, tanpa validasi nilai); `has_catatan` menambah textarea. Disimpan di key `kehadiran`/`catatan` pada JSON.
  - **Acceptance Criteria**: Field muncul di form P.Industri; tidak divalidasi sistem.

- [ ] **Task M8.4: Publish dan kunci struktur**
  - **Goal**: Form tidak berubah setelah dipakai.
  - **Target Files/Location**: `app/Actions/Form/PublishFormAction.php`, guard di resource
  - **Instructions**: Publish menolak form tanpa section/item. Setelah ada `form_submissions`, ubah/hapus section/item ditolak (buat form baru).
  - **Acceptance Criteria**: Edit setelah submission → ditolak server.

- [ ] **Task M8.5: `NilaiCalculator`**
  - **Goal**: Hitung `nilai_total` di server (PRD Q-E).
  - **Target Files/Location**: `app/Services/NilaiCalculator.php`, `tests/Unit/NilaiCalculatorTest.php`
  - **Instructions**: Default: rata-rata item skala 0–100; validasi 0–100; tulis key `nilai_total` ke JSON (jangan JSON null). **Bila PO mengubah Q-E, hentikan dan konfirmasi.** Hitung juga `nilai_total_per_kategori` (per section).
  - **Acceptance Criteria**: Unit test angka tetap; nilai di luar 0–100 ditolak.

- [ ] **Task M8.6: Migration `external_examiners`, `jadwal_sidang`**
  - **Goal**: Penguji dan jadwal.
  - **Target Files/Location**: dua migration + model
  - **Instructions**: Ikuti `schema.sql` Bagian 11 (UNIQUE `pengajuan_siswa_id`).
  - **Acceptance Criteria**: Siswa dijadwalkan dua kali ditolak DB.

- [ ] **Task M8.7: Jadwal sidang + penguji terverifikasi (Kaprog)**
  - **Goal**: Jadwalkan dari pool Siap Sidang (AC-53).
  - **Target Files/Location**: `app/Filament/App/Pages/Kaprog/SidangJadwal.php`, `app/Actions/Sidang/ScheduleSidangAction.php`
  - **Instructions**: Pilih siswa dari `SiapSidangQuery`, tanggal, urutan; input/pilih penguji (nama + email atau WA + verifikasi kontak; NIK opsional). Tolak siswa belum legit.
  - **Acceptance Criteria**: Penguji tanpa kontak terverifikasi ditolak; siswa non-legit ditolak.

- [ ] **Task M8.8: Terbit link + PIN sidang**
  - **Goal**: Akses penguji.
  - **Target Files/Location**: `app/Actions/Sidang/IssueSidangLinkAction.php`
  - **Instructions**: `MagicLinkService::issue('sidang_penguji', jadwal_id, expiry=akhir hari sidang Jakarta, pin)`. Kaprog dapat melihat/menerbitkan ulang PIN. Kirim via M6.8 (link dan PIN terpisah).
  - **Acceptance Criteria**: Hanya Kaprog jurusannya yang dapat melihat/menerbitkan PIN.

- [ ] **Task M8.9: Portal sidang — PIN dan pengisian nilai**
  - **Goal**: Penguji mengisi nilai.
  - **Target Files/Location**: `app/Livewire/External/SidangNilai.php`
  - **Instructions**: Buka link → PIN (M6.4) → daftar antrean hari itu → form dari `form_definitions` → simpan draft per siswa. Hanya jadwal milik link ini.
  - **Acceptance Criteria**: Penguji tidak dapat melihat siswa di luar antreannya; PIN salah 3x memblokir (AC-30).

- [ ] **Task M8.10: Score lock-in — TASK KRITIS**
  - **Goal**: "Simpan & Selesaikan" mengunci nilai (AC-31).
  - **Target Files/Location**: `app/Actions/Sidang/SubmitAndLockScoreAction.php`, `app/Policies/FormSubmissionPolicy.php`
  - **Instructions**: Set `is_submitted=true`, `submitted_at`, hitung `nilai_total`; dispatch `SyncSidangResultsSummaryJob`. Policy `update` menolak SEMUA perubahan bila `is_submitted` (403), termasuk dari sesi link yang sama dan dari Kaprog. Cakup setiap jalur tulis.
  - **Acceptance Criteria**: Submit ulang/edit setelah lock → 403 di setiap endpoint; hanya jalur revisi resmi (M8.13) yang dapat mengubah.

- [ ] **Task M8.11: Smooth session switching**
  - **Goal**: Pindah siswa tanpa reload penuh (AC-32).
  - **Target Files/Location**: `app/Livewire/External/SidangNilai.php`
  - **Instructions**: Setelah lock, state komponen Livewire berpindah ke urutan berikutnya via pembaruan parsial (tanpa full-page navigation). Tampilkan "selesai" bila antrean habis.
  - **Acceptance Criteria**: Test Livewire: setelah submit siswa ke-3, komponen menampilkan siswa ke-4 tanpa redirect.

- [ ] **Task M8.12: Cascade shift reschedule**
  - **Goal**: Geser sisa antrean ke hari berikutnya (AC-33).
  - **Target Files/Location**: `app/Actions/Sidang/CascadeShiftAction.php`
  - **Instructions**: Dalam satu transaksi: ambil siswa yang belum selesai di tanggal T (urutan ≥ n), pindah ke T+1 sebagai urutan teratas, geser antrean T+1 yang sudah ada. Validasi konflik; gagal → rollback total dengan alasan jelas. Terbitkan ulang/perpanjang link sesuai tanggal baru.
  - **Acceptance Criteria**: Skenario AC-33 (4,5 → hari berikutnya urutan 1–2; antrean lama jadi 3–6); kegagalan menjaga jadwal awal.

- [ ] **Task M8.13: Migration + alur revisi nilai (Kaprog izin unlock)**
  - **Goal**: Revisi tanpa Kaprog mengedit nilai (AC-34).
  - **Target Files/Location**: migration `nilai_sidang_revisions`, `app/Actions/Sidang/ApproveRevisiNilaiAction.php`, `app/Filament/App/Resources/Kaprog/RevisiNilaiResource.php`
  - **Instructions**: Kaprog approve → terbitkan `magic_links` `revisi_nilai_sidang` (scoped 1 `form_submission_id`, TTL 24 jam elapsed, PIN baru) ke penguji asli (kontak di `external_examiners`); simpan `jawaban_lama`; status `menunggu_revisi`. Kaprog tidak punya UI/endpoint edit nilai.
  - **Acceptance Criteria**: Tidak ada jalur Kaprog mengubah angka; link revisi hanya untuk satu submission.

- [ ] **Task M8.14: Portal revisi nilai penguji**
  - **Goal**: Penguji submit ulang dalam TTL.
  - **Target Files/Location**: `app/Livewire/External/SidangRevisi.php`
  - **Instructions**: Isi `jawaban_baru`, hitung ulang, update submission dalam transaksi, `revision_count++`, status revisi `selesai`, link dikonsumsi, `SyncSidangResultsSummaryJob` ulang. Lock kembali.
  - **Acceptance Criteria**: Setelah selesai, edit lanjutan → 403.

- [ ] **Task M8.15: `AutoRelockNilaiSidangJob`**
  - **Goal**: Re-lock saat TTL habis (AC-35).
  - **Target Files/Location**: `app/Jobs/AutoRelockNilaiSidangJob.php`
  - **Instructions**: Tiap jam: revisi `menunggu_revisi` dengan `unlock_expires_at < now` → `auto_relocked`, nonaktifkan link, nilai lama tetap.
  - **Acceptance Criteria**: Link tidak bisa dipakai setelah TTL; nilai lama utuh.

- [ ] **Task M8.16: Window & trigger penilaian industri**
  - **Goal**: Tombol Generate pada window H-1 bulan (AC-36).
  - **Target Files/Location**: `app/Filament/App/Pages/Guru/PerusahaanDetail.php`, `app/Services/IndustriWindow.php`
  - **Instructions**: Window terbuka bila `tanggal_selesai_pkl` terdekat dari siswa terplot guru di perusahaan itu ≤ today+1 bulan (Jakarta). Server memeriksa ulang saat Generate. Independen dari status laporan.
  - **Acceptance Criteria**: Sisa >1 bulan → tombol tidak ada dan endpoint menolak.

- [ ] **Task M8.17: `GenerateIndustriLinkAction` (1 link, banyak siswa)**
  - **Goal**: Pivot multi-siswa (AC-38).
  - **Target Files/Location**: `app/Actions/Industri/GenerateIndustriLinkAction.php`
  - **Instructions**: Transaksi: ambil form published jurusan+periode; buat `form_submissions` (type industri, `company_id`, `academic_period_id`, `pengajuan_siswa_id` NULL); isi `form_submission_siswa` untuk semua siswa `sedang_pkl` bimbingan guru di perusahaan itu; terbitkan `magic_links` `penilaian_industri` ke email P.Industri terverifikasi.
  - **Acceptance Criteria**: 4 siswa → 1 link, 1 submission, 4 pivot, kolom langsung NULL.

- [ ] **Task M8.18: Reminder H-7 penilaian industri**
  - **Goal**: Notifikasi ke guru (AC-37).
  - **Target Files/Location**: `app/Jobs/SendPenilaianIndustriReminderJob.php`
  - **Instructions**: Harian: perusahaan dengan sisa PKL tepat 7 hari (Jakarta) dan belum ada submission industri → notifikasi ke guru terplot. Idempoten per hari.
  - **Acceptance Criteria**: Dijalankan 2x → satu notifikasi.

- [ ] **Task M8.19: Portal penilaian industri**
  - **Goal**: P.Industri mengisi nilai banyak siswa.
  - **Target Files/Location**: `app/Livewire/External/PenilaianIndustri.php`
  - **Instructions**: Link → OTP email → form per siswa dalam pivot (form items + kehadiran/catatan bila aktif) → submit final (`is_submitted=true`). Tanpa jalur revisi (AC-39).
  - **Acceptance Criteria**: Akses ulang setelah submit → ditolak; tidak ada endpoint unlock.

- [ ] **Task M8.20: UI revoke/reissue link**
  - **Goal**: Sesuai PD-10 (AC-50).
  - **Target Files/Location**: aksi di `SidangJadwal`, `PengesahanResource`, `PerusahaanDetail`, `app/Filament/Hubin/Pages/MagicLinkOverride.php`
  - **Instructions**: Kaprog: link sidang + signature P/K.Industri jurusannya; Guru: penilaian industri siswa bimbingannya; Hubin: semua jenis dengan alasan wajib. Memanggil `RevokeMagicLinkAction/Reissue` (M6.3).
  - **Acceptance Criteria**: Matriks aktor×jenis sesuai PRD §5.8; audit memuat alasan.

**Checkpoint M8**: penguji submit nilai 2x tanpa revisi resmi → submit ke-2 ditolak; revisi lewat izin Kaprog + link baru; auto re-lock; 1 link industri multi-siswa; penilaian industri tidak bisa direvisi.

---

# MILESTONE 9 — Ekspor Akreditasi

- [ ] **Task M9.1: Migration `sidang_results_summary`, `accreditation_exports`**
  - **Goal**: Tabel ringkasan + attempt ekspor.
  - **Target Files/Location**: dua migration + model
  - **Instructions**: Ikuti `schema.sql` Bagian 15 (CHECK `completed` wajib checksum/path/expiry).
  - **Acceptance Criteria**: `completed` tanpa checksum ditolak DB.

- [ ] **Task M9.2: `SyncSidangResultsSummaryJob`**
  - **Goal**: Ringkasan event-driven.
  - **Target Files/Location**: `app/Jobs/SyncSidangResultsSummaryJob.php`
  - **Instructions**: Upsert by `form_submission_id` dengan nama, NIS, jurusan, perusahaan, periode, `nilai_total_per_kategori`, status kelulusan (Q-I), penguji, tanggal. Dipicu saat lock dan saat revisi selesai. Tanpa telepon/alamat rumah/data orang tua.
  - **Acceptance Criteria**: Revisi nilai memperbarui baris ringkasan; job ulang idempoten.

- [ ] **Task M9.3: `ExportAkreditasiJob` (chunk 500)**
  - **Goal**: Ekspor tanpa timeout (AC-45).
  - **Target Files/Location**: `app/Jobs/ExportAkreditasiJob.php`, `app/Exports/AccreditationExport.php`
  - **Instructions**: Status `queued→running→completed`; tulis xlsx ke disk private per chunk 500 baris dari `sidang_results_summary`; validasi kelengkapan (jumlah baris = jumlah siswa lulus sidang periode) sebelum `completed`; simpan checksum SHA-256, `row_count`, `expires_at=+30 hari`; notifikasi Hubin dengan tautan unduh.
  - **Acceptance Criteria**: Header kolom persis field PD-08; file hanya `completed` setelah validasi.

- [ ] **Task M9.4: Halaman ekspor + unduh Hubin-only**
  - **Goal**: Trigger dan unduh aman.
  - **Target Files/Location**: `app/Filament/Hubin/Pages/AccreditationExport.php`, `app/Http/Controllers/AccreditationDownloadController.php`
  - **Instructions**: Tombol buat attempt baru; daftar attempt + status. Unduh via controller: hanya Hubin, hanya `completed`, belum kedaluwarsa, verifikasi checksum.
  - **Acceptance Criteria**: Non-Hubin 403; `failed`/parsial tidak dapat diunduh (AC-54).

- [ ] **Task M9.5: Penanganan gagal & retry**
  - **Goal**: Attempt gagal tercatat, retry = attempt baru.
  - **Target Files/Location**: `ExportAkreditasiJob::failed()`, aksi "Retry"
  - **Instructions**: Exception/validasi gagal → status `failed` + `failure_reason` (tanpa rahasia), bersihkan file parsial, log di channel terpisah. Retry membuat baris baru (`retry_of_id`), tidak menimpa.
  - **Acceptance Criteria**: Test AC-54.

- [ ] **Task M9.6: `DeleteExpiredAccreditationExportsJob`**
  - **Goal**: Retensi 30 hari.
  - **Target Files/Location**: `app/Jobs/DeleteExpiredAccreditationExportsJob.php`
  - **Instructions**: Harian (Jakarta): hapus file private + metadata yang `expires_at < now`. Urutan: hapus file dulu, baru baris; kegagalan hapus file tidak menghapus baris.
  - **Acceptance Criteria**: Permintaan setelah retensi membuat ekspor baru.

- [ ] **Task M9.7: Load test ekspor 1.752 siswa**
  - **Goal**: Verifikasi target < 5 menit (belum terbukti).
  - **Target Files/Location**: `database/seeders/LoadTestSeeder.php`, catatan hasil di PR
  - **Instructions**: Seed 1.752 baris summary dummy; jalankan ekspor via queue worker; catat durasi, memori, jumlah chunk.
  - **Acceptance Criteria**: Hasil terukur dicatat; bila > 5 menit, laporkan sebagai temuan (bukan menutup).

**Checkpoint M9**: ekspor sukses menghasilkan xlsx tervalidasi; gagal tidak dapat diunduh; retry membuat attempt baru; cleanup bekerja.

---

# MILESTONE 10 — Monitoring, FAQ, Landing

- [ ] **Task M10.1: Kanban read-only Walas**
  - **Goal**: Pantau kelas (5 kolom).
  - **Target Files/Location**: `app/Filament/App/Pages/Walas/Kanban.php`
  - **Instructions**: Kolom berdasarkan status pengajuan (kelompokkan menjadi 5 tahap, definisikan di enum). Scoped kelas Walas aktif; tanpa aksi tulis. Mobile-first.
  - **Acceptance Criteria**: Siswa kelas lain tidak tampil; tidak ada tombol aksi.

- [ ] **Task M10.2: Dashboard durasi tunggu per step**
  - **Goal**: Pantau TTD eksternal.
  - **Target Files/Location**: `app/Filament/App/Pages/WaitingTimeDashboard.php`
  - **Instructions**: Untuk tiap laporan aktif: lama menunggu per step (selisih `created_at` baris pending dengan now, hari kerja Senin–Jumat). Kaprog (jurusan) dan Hubin (semua).
  - **Acceptance Criteria**: Angka sesuai `JakartaTime::businessDaysBetween`.

- [ ] **Task M10.3: Migration FAQ**
  - **Goal**: `faq_categories`, `faqs` (tanpa FULLTEXT).
  - **Target Files/Location**: dua migration + model
  - **Instructions**: Ikuti `schema.sql` Bagian 14. Satu tabel kategori + satu tabel faq.
  - **Acceptance Criteria**: Migrate berhasil; tidak ada tabel FAQ lain.

- [ ] **Task M10.4: FAQ resource Hubin**
  - **Goal**: CRUD kategori dan FAQ.
  - **Target Files/Location**: `app/Filament/Hubin/Resources/{FaqCategoryResource,FaqResource}.php`
  - **Instructions**: Field sesuai tabel, `is_published`, urutan.
  - **Acceptance Criteria**: Hanya Hubin; FAQ draft tidak tampil publik.

- [ ] **Task M10.5: Halaman publik `/faq`**
  - **Goal**: Filter, cari, paginasi (AC-42..44).
  - **Target Files/Location**: `app/Livewire/Public/Faq.php`, view
  - **Instructions**: Hanya `is_published`; filter kategori; pencarian `LIKE` pada pertanyaan/jawaban (escape wildcard); paginasi 10/halaman. Tanpa login. Keadaan kosong/tanpa hasil.
  - **Acceptance Criteria**: 12 item kategori → 10 + 2; keyword "surat pengantar" lintas kategori.

- [ ] **Task M10.6: Landing page publik**
  - **Goal**: Halaman `/` dengan tautan login dan FAQ.
  - **Target Files/Location**: `resources/views/welcome.blade.php`, `app/Livewire/Public/Landing.php`
  - **Instructions**: Ringkasan alur PKL, tombol Login dan FAQ. **Minta wireframe bila belum ada** sebelum menata. Responsif 360px.
  - **Acceptance Criteria**: Dapat diakses tanpa login; tidak memuat aset panel.

**Checkpoint M10**: anonim hanya melihat FAQ terbit; uji kategori+pencarian+paginasi, halaman kosong, keyword tanpa hasil; kanban Walas hanya kelasnya.

---

# MILESTONE 11 — Hardening & Go-Live

- [ ] **Task M11.1: Audit precondition endpoint kritis**
  - **Goal**: Semua guard di server (TRD §4).
  - **Target Files/Location**: laporan `docs/audit-endpoints.md`
  - **Instructions**: Telusuri `AcceptCompanyAction`, `SubmitBimbinganAction`, `MarkFinalAction`, `SignatureChainService`, lock nilai, revoke/reissue, ekspor. Pastikan tiap precondition ada di Action, bukan hanya UI. Tambah test bila ada celah.
  - **Acceptance Criteria**: Daftar endpoint × precondition × test; tidak ada celah terbuka.

- [ ] **Task M11.2: Test matriks permission lintas role**
  - **Goal**: Tidak ada kebocoran scope (TRD §5).
  - **Target Files/Location**: `tests/Feature/Security/PermissionMatrixTest.php`
  - **Instructions**: Dataset aksi×role×scope (halaman, aksi, pencarian, ekspor, unduhan). Sertakan kasus multi-role dengan `active_role` salah.
  - **Acceptance Criteria**: Semua sel matriks sesuai; 403/404 konsisten.

- [ ] **Task M11.3: Audit rahasia plaintext**
  - **Goal**: Tidak ada token/OTP/PIN mentah (invarian 6).
  - **Target Files/Location**: `docs/audit-secrets.md`, test `tests/Feature/Security/NoPlaintextSecretsTest.php`
  - **Instructions**: Periksa DB, activity_log, log aplikasi, queue payload, email log. Pastikan `properties` tidak memuat rahasia.
  - **Acceptance Criteria**: Tidak ada plaintext ditemukan; bukti pencarian dicatat.

- [ ] **Task M11.4: Audit upload dan unduhan**
  - **Goal**: PD-09 / AC-49.
  - **Target Files/Location**: `tests/Feature/Security/UploadTest.php`
  - **Instructions**: Uji tiga profil (laporan/foto/balasan): ekstensi×MIME mismatch, ukuran, file kosong, path traversal pada nama; unduhan lewat controller dengan otorisasi objek; antivirus hanya bila dikonfigurasi (jangan klaim aktif).
  - **Acceptance Criteria**: Semua kasus ditolak/diizinkan sesuai aturan.

- [ ] **Task M11.5: Audit waktu dan job terjadwal**
  - **Goal**: AC-48.
  - **Target Files/Location**: `routes/console.php`, `tests/Feature/Time/*`
  - **Instructions**: Semua job harian dijadwalkan `timezone('Asia/Jakarta')`, sekali per tanggal; TTL elapsed; date-only inklusif; semua kolom waktu UTC. Semua job idempoten.
  - **Acceptance Criteria**: Test batas 23:59:59 dan 00:00:00 Jakarta; job ulang tidak menggandakan efek.

- [ ] **Task M11.6: Audit notifikasi**
  - **Goal**: PD-06.
  - **Target Files/Location**: `tests/Feature/Notifications/*`
  - **Instructions**: Verifikasi email + in-app wajib, retry maks 3x, kegagalan permanen terlihat Hubin (halaman daftar `notification_delivery_failures`), tidak ada re-prompt push.
  - **Acceptance Criteria**: Hubin dapat melihat dan menandai selesai kegagalan permanen.

- [ ] **Task M11.7: Backup dan konfigurasi produksi**
  - **Goal**: Backup DB nightly + file incremental.
  - **Target Files/Location**: `docs/ops-runbook.md`, konfigurasi scheduler/cron/supervisor queue
  - **Instructions**: Dokumentasikan backup DB harian, backup file incremental harian, supervisor untuk `queue:work`, `schedule:run`, HTTPS, cookie `Secure/HttpOnly/SameSite`, `APP_DEBUG=false`. Tanggung jawab: sysadmin.
  - **Acceptance Criteria**: Runbook lengkap; konfigurasi diverifikasi di lingkungan staging bila ada.

- [ ] **Task M11.8: Restore test**
  - **Goal**: Pemulihan backup terbukti.
  - **Target Files/Location**: `docs/restore-test.md`
  - **Instructions**: Pulihkan backup ke DB kosong + file; jalankan smoke test login dan unduh file. Catat durasi dan hasil.
  - **Acceptance Criteria**: Pemulihan berhasil dan terdokumentasi.

- [ ] **Task M11.9: Eksekusi seluruh AC dan laporan**
  - **Goal**: Definition of Done proyek.
  - **Target Files/Location**: `docs/ac-report.md`
  - **Instructions**: Tabel AC-01..AC-54 (disesuaikan Lampiran A): metode, bukti, status (Not run/Pass/Fail/Blocked). Selaraskan teks AC yang berubah (AC-15/16, AC-33, AC-46/47) dengan keputusan Lampiran A dan B.
  - **Acceptance Criteria**: Semua AC Pass dengan bukti yang dapat diulang, atau pengecualian disetujui eksplisit.

- [ ] **Task M11.10: Migrate fresh dan smoke test akhir**
  - **Goal**: Pemasangan dari DB kosong.
  - **Target Files/Location**: `docs/release-checklist.md`
  - **Instructions**: `migrate:fresh` pada DB kosong, seed minimal produksi (jurusan, periode, akun Hubin), jalankan skenario end-to-end satu siswa: pengajuan → penerimaan → bimbingan → 5-TTD → sidang → ekspor.
  - **Acceptance Criteria**: Skenario selesai tanpa intervensi manual di DB.

---

## Catatan Pemakaian

- Task bertanda **KRITIS** (M4.13, M7.2, M7.4, M7.11, M8.10) adalah sumber bug tersembunyi; wajib test otomatis + uji manual, jangan hanya review kode.
- Dependensi: M3 sebelum M4; M6 sebelum M7 (link eksternal) dan M8; M4.13 sebelum M5.2; M7.12 sebelum M8.7; M8.10 sebelum M9.2.
- Wireframe diperlukan hanya untuk halaman Blade kustom (landing M10.6, portal eksternal M6.6/M7.9/M8.9/M8.19, kanban M10.1). Bila belum ada, minta sebelum menata UI.
- Pertanyaan terbuka yang masih memengaruhi task: **Q-A** (M3.2), **Q-B** (M5.2, M8.17), **Q-E** (M8.5), **Q-I** (M9.2). Bila PO mengubah default, perbarui `schema.sql`/task terkait sebelum mengerjakan task yang bergantung.
