# GO-LIVE.md — Kesiapan Rilis SIM PKL

Dokumen operasional T10.2. Isinya: perintah rilis, konfigurasi server, cadangan/pemulihan,
checklist UAT manusia, pemetaan invarian PRD 1.5 ke tes, dan keputusan butir Q.

Status per dokumen ini: T10.1 selesai dan teruji; T10.2 kode/uji selesai. **Checklist UAT §5
sudah dicentang lewat checkpoint visual R6.3** (bukti di `D:\cv\shots`, temuan di §8 sudah
diperbaiki); **cadangan §4 dan keputusan PO §6 masih harus dijalankan manusia di server/peramban.**

---

## 1. Perintah rilis

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan db:seed --force            # jurusan, periode contoh, akun, FAQ
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

`public/build` tidak masuk repo; `npm run build` wajib sebelum halaman publik/auth diuji.
Disk berkas adalah `local` (privat) di `storage/app/private`; tidak ada `storage:link`.

## 2. Proses latar (wajib)

Email/OTP, PDF, dan ekspor akreditasi berjalan lewat queue `database`.

**Supervisor** — `/etc/supervisor/conf.d/simpkl-worker.conf`:

```ini
[program:simpkl-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/simpkl/artisan queue:work database --sleep=1 --tries=3 --timeout=900
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/simpkl/storage/logs/worker.log
stopwaitsecs=3600
```

**Cron** — `crontab -e`:

```cron
* * * * * cd /var/www/simpkl && php artisan schedule:run >> /dev/null 2>&1
```

Jadwal terdaftar (cek `php artisan schedule:list`): pengingat provisional 07:00, pengingat
H-7 industri 07:05, kedaluwarsa visitasi 01:00, kedaluwarsa magic link tiap jam, kunci ulang
nilai sidang tiap jam, hapus ekspor akreditasi 02:00 (semua Asia/Jakarta, kecuali tiap jam).

Pemantauan: `failed_jobs`, `notification_delivery_failures`, dan status ekspor tampil di
panel Hubin (`/hubin`), sesuai TRD.md A18.

## 3. Header dan cookie keamanan

- `SecurityHeaders` (grup `web`, termasuk `/eksternal/*`): `X-Content-Type-Options`,
  `X-Frame-Options: DENY`, `Referrer-Policy` (`no-referrer` di rute eksternal), CSP yang
  kompatibel Livewire/Alpine/Filament, dan HSTS saat HTTPS.
- Produksi **wajib** HTTPS dan setel di `.env`:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

- Cookie sesi: `HttpOnly` (default) + `SameSite=Lax` (default `config/session.php`).

## 4. Cadangan dan pemulihan (uji sebelum go-live)

DB dan berkas privat dicadangkan terjadwal. Simpan di luar host aplikasi.

```bash
STAMP=$(date +%F)
mysqldump --single-transaction --routines --triggers -u root -p sistem_pkl \
  | gzip > /backup/sistem_pkl-$STAMP.sql.gz
tar czf /backup/simpkl-private-$STAMP.tar.gz -C /var/www/simpkl/storage/app private
```

**Uji pemulihan** (di server uji, jangan di produksi):

```bash
gunzip -c /backup/sistem_pkl-$STAMP.sql.gz | mysql -u root -p sistem_pkl_restore
tar xzf /backup/simpkl-private-$STAMP.tar.gz -C /var/www/simpkl-restore/storage/app
# cek: jumlah pengajuan, file laporan terbaca lewat aplikasi, nomor surat unik
```

Checklist: [ ] dump berhasil, [ ] restore ke DB bersih berhasil, [ ] berkas privat terbaca
lewat unduhan berotorisasi, [ ] catat durasi restore.

## 5. Checklist UAT manusia (tidak bisa diotomatiskan)

Otomatis: `tests/Feature/Uat/RoleSmokeTest.php` membuktikan tiap peran membuka halaman
intinya (200) dan ditolak di halaman peran lain (403). Yang tetap manual: lebar 360px dan
1280px, mode terang dan gelap, kontras, dan target sentuh.

| Peran | Halaman inti | 360px | 1280px | Terang | Gelap |
|---|---|---|---|---|---|
| Siswa | `/siswa`, `/siswa/bimbingan`, `/siswa/jadwal-sidang` | [x] | [x] | [x] | [x] |
| Walas | `/walas`, `/walas/kanban` | [x] | [x] | [x] | [x] |
| Guru | `/guru/bimbingan-siswa`, `/guru/penilaian-industri`, `/guru/visitasi` | [x] | [x] | [x] | [x] |
| Kaprog | `/kaprog/antrean-pengajuan`, `/kaprog/plotting-guru`, `/kaprog/form-builder`, `/kaprog/jadwal-sidang`, `/kaprog/pengesahan-laporan`, `/kaprog/penilaian-sidang`, `/kaprog/monitoring` | [x] | [x] | [x] | [x] |
| Kepsek | `/kepsek` (bulk TTD di ponsel) | [x] | [x] | [x] | [x] |
| Semua peran berakun | `/notifikasi` (preferensi notifikasi) | [x] | [x] | [x] | [x] |
| Hubin | `/hubin/companies`, `/hubin/users`, `/hubin/signature-assets`, `/hubin/faqs`, `/hubin/ekspor-akreditasi`, `/hubin/monitoring` | [x] | [x] | [x] | [x] |
| Penguji | `/eksternal/penguji/{token}` (PIN) | [x] | [x] | [x] | [x] |
| Industri | `/eksternal/industri/{token}` (OTP) | [x] | [x] | [x] | [x] |
| Publik | `/`, `/faq`, `/verify/{token}`, `/login` | [x] | [x] | [x] | [x] |

Per halaman, cek juga: status selalu chip bertulisan, tidak ada tombol utama kedua,
hubungan kartu/tabel nyaman di 360px.

Bukti R6.3: 354 PNG di `D:\cv\shots` (di luar repo), 9 grup × 360×800 + 1280×900 ×
terang + gelap, diambil REA `capture_browser_scenario` (Chromium 157 headless) terhadap
`http://127.0.0.1:8000` dengan basis data uji terpisah `sipkl_cv`. Semua langkah
`completed`, tanpa galat halaman. Status di seluruh halaman selalu chip bertulisan
(bukan warna saja), tiap halaman satu tombol utama, navigasi bawah portal 360px
memakai target sentuh ≥ 44px, dan mode gelap benar di keempat permukaan. Temuan
kerapian/kontras dicatat di §8 (sudah diperbaiki).

## 6. Pemetaan invarian PRD 1.5 → tes

| # | Invarian | Bukti otomatis |
|---|---|---|
| 1 | Kuota 6 (lock + hitung ulang) | `tests/Feature/Pengajuan/PengajuanSuratQuotaTest.php`, `tests/Feature/Pengajuan/PengajuanFlowTest.php` |
| 2 | Provisional mengunci bimbingan | `tests/Feature/Bimbingan/BimbinganFlowTest.php` |
| 3 | 5-TTD sequential + partial-reject | `tests/Feature/Pengesahan/PengesahanFlowTest.php` |
| 4 | Nilai terkunci + unlock Kaprog | `tests/Feature/Form/ScoreServiceTest.php`, `tests/Feature/Sidang/SidangFlowTest.php` |
| 5 | `active_role` + scope objek | `tests/Feature/Auth/ActiveRoleTest.php`, `tests/Feature/Master/AcademicPeriodPolicyTest.php`, `tests/Feature/Pengajuan/PengajuanFlowTest.php` |
| 6 | Rahasia eksternal ter-hash | `tests/Feature/Auth/OtpTest.php`, `tests/Feature/Eksternal/MagicLinkTest.php`, `tests/Unit/AuditRedactionTest.php` |
| 7 | Form JSON dua tingkat | `tests/Feature/Form/FormBuilderTest.php`, `tests/Feature/SchemaConstraintTest.php` |
| 8 | Idempoten + atomik | `tests/Feature/ActionIdempotencyTest.php`, `tests/Feature/DailyJobIdempotencyTest.php` |
| 9 | Waktu UTC/Jakarta + hari kerja | `tests/Unit/JakartaTimeTest.php` |

**Celah yang diketahui:** invarian 1 diuji berurutan, belum dua proses bersamaan; penguncian
`companies` + hitung ulang dalam transaksi sudah ada, tetapi bukti dua proses nyata belum
dijalankan. Jalankan saat uji beban di server nyata.

## 7. Keputusan butir Q (tertulis)

| Q | Keputusan yang dipakai | Catatan |
|---|---|---|
| Q-A | Nomor surat per **tahun ajaran** (bukan per periode) | Menyimpang dari PD-13; mengubahnya = ganti kunci counter dan UNIQUE nomor surat |
| Q-E | `nilai_total` = rata-rata item, skala 0–100, 2 desimal, dihitung server (`ScoreService`) | Tidak ada kolom `max`; batas 100 konstanta kelas |
| Q-F | Stack Filament/Livewire/MySQL | Aplikasi sudah dibangun di atas stack ini; kembali ke TRD v1 berarti bangun ulang |
| Q-K | 5 kolom kanban: (1) pengajuan & verifikasi, (2) surat & balasan, (3) PKL & bimbingan, (4) pengesahan laporan, (5) sidang & selesai | Dipakai di `/walas/kanban` |
| Q-L | Masa berlaku visitasi dari **tanggal terbit** surat | Sudah keputusan PO |
| Q-H | Perusahaan tanpa signer boleh jalan sampai step 3; Hubin diberi tahu | Default dipakai |

Status: keempat butir (A, E, F, K) memakai default di atas dan dicatat sebagai keputusan
yang berjalan. Bila PO mengubah, perubahan masuk versi berikutnya dengan catatan migrasi.

## 8. Batasan dan risiko

- Lingkungan pengembangan saat ini MariaDB (XAMPP); produksi wajib MySQL 8.0.16+. Migrasi
  `2026_06_02_000001` memakai `DROP CONSTRAINT` yang portabel di MySQL 8.0.19+ dan MariaDB.
- Uji beban ekspor hanya mengukur penulisan CSV dari `sidang_results_summary` (±1 detik untuk
  1.752 baris), bukan waktu sinkronisasi nilai sidang ke tabel ringkasan.
- Kolom ekspor mengikuti kolom `sidang_results_summary`; daftar resmi PD-08 belum ada di repo.
- Push (T10.3) best-effort: tanpa `PKL_PUSH_ENDPOINT`, kanal push tidak dipakai; email dan
  in-app tetap jalan.

### Temuan CV R6.3 (UI, sudah diperbaiki)

Checkpoint visual R6.3 memotret semua 29 baris checklist §5. Yang lolos: terna terang/gelap,
chip status bertulisan, satu tombol utama per halaman, target sentuh portal 360px. Temuan
kerapian berikut sudah diperbaiki pada tugas lanjutan (tanpa mengubah Action/Policy/Service):

1. **Kolom tabel terpotong di 1280** — aksi tabel kini `iconButton()` + `tooltip()`, jadi
   label panjang tidak lagi memaksa lebar kolom: `/guru/visitasi` ("Unggah"/"Unduh"),
   `/guru/penilaian-industri` dan `/hubin/penilaian-industri` ("Terbitkan"/"Cabut"/"Terbitkan
   ulang"). Kolom teks dirapikan (`Telepon` pada kolom tabel, `alamat` `limit(30)`,
   `Peran` `limitList(2)`). Bila 1280 masih terpotong, cadangan
   `->toggleable(isToggledHiddenByDefault: true)` pada kolom `Email` tersedia (belum diukur ulang).
2. **Baris tabel terlalu tinggi** — kolom panjang dibungkus `lineClamp(2)`:
   `/guru/visitasi` (`perusahaan`), `/guru/penilaian-industri` (`company`, `students`),
   `/hubin/penilaian-industri` (`company.nama`, `students`), `/kaprog/plotting-guru` (`nama`).
3. **File input bawaan tak diterjemahkan** — dibiarkan apa adanya (F3): teks `Choose File /
   No file chosen` adalah perilaku bawaan peramban yang tidak dapat diganti tanpa widget
   JS kustom.
4. **Header publik di 360** — wordmark diberi `truncate`, tautan FAQ/Masuk jadi ikon+teks
   ringkas, dan pemilih tema jadi tiga tombol ikon `h-11 w-11` ber-`aria-label`; header tidak
   lagi patah multi-baris.
5. **Label paginasi hilang di 360** — override CSS di `resources/css/app.css` menampilkan
   kembali teks "per halaman" pada pemilih jumlah baris (bergantung nama kelas Filament
   5.11.4, dicatat di komentar).
6. **Kata ganti tidak konsisten** — semua "Kamu"/"-mu" pada portal siswa diganti "Anda"
   (`resources/lang/id/pkl.php`, `antrean-walas`, `bimbingan-siswa`, `company-autocomplete`).

Tambahan tugas yang sama: ikon navigasi (`navigationIcon`) untuk 10 halaman panel dan ikon
pada menu portal, header publik, tombol aksi, serta chip/keadaan kosong di seluruh permukaan
(panel Filament + portal Blade).

Catatan proses: satu jalur 360px terang pernah gagal `page.goto: net::ERR_ABORTED` pada
`/siswa` (balapan dengan reload `wire:navigate` Livewire); diulang bersih tanpa galat.
Sesi peramban REA dipakai ulang dalam satu proses, jadi tema diset eksplisit lewat dua kunci
localStorage (`filament:theme:mode=light` + `sim-pkl-theme-applied=1`) agar tidak ditimpa
`theme_preference` akun.
