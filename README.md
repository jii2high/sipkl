# SIPKL — Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi

Aplikasi pengelolaan Praktik Kerja Lapangan: pengajuan siswa, plotting pembimbing,
surat menyurat bertanda QR, bimbingan, penilaian, sampai rekap. Dibangun dengan
Laravel + Filament (panel `app` dan `hubin`), Livewire/Blade, Tailwind, dan MySQL.

Status: M1–M3 selesai (login OTP, master data, nomor surat + PDF + verifikasi
publik). Rencana dan aturan main ada di `docs/TASKS.md`; perilaku produk di
`docs/PRD.md`; keputusan teknis di `docs/TRD.md`.

Panduan ini hanya untuk **lingkungan lokal**.

## Prasyarat

| Kebutuhan | Versi |
|---|---|
| PHP | 8.4, ekstensi `mbstring`, `pdo_mysql`, `gd`, `zip`, `fileinfo` |
| Composer | 2.x |
| Node.js | 22.x (dipakai Vite 8) |
| MySQL | 8.0.16 atau lebih baru (`JSON`, `CHECK`, generated column) |

Ekstensi `gd` wajib: dipakai membuat QR surat dan menyematkan tanda tangan ke PDF.

## Instalasi

```bash
composer install
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
php artisan key:generate
```

Buat dua database (satu untuk aplikasi, satu untuk tes):

```bash
mysql -u root -e "CREATE DATABASE sistem_pkl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE DATABASE sistem_pkl_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Sesuaikan `.env`, lalu jalankan migrasi beserta data awal:

```bash
php artisan migrate --seed
```

Pasang dan bangun aset frontend:

```bash
npm install
npm run build
```

`composer run setup` tersedia sebagai jalan pintas langkah di atas, tetapi **tidak**
menjalankan seeder. Jalankan `php artisan migrate --seed` terpisah.

### Variabel `.env` yang perlu diperiksa

| Variabel | Keterangan |
|---|---|
| `APP_URL` | Alamat aplikasi, mis. `http://localhost:8000`. Dipakai saat membentuk tautan QR verifikasi surat. |
| `DB_*` | Koneksi MySQL. Default `sistem_pkl`, user `root`, tanpa sandi. |
| `APP_TIMEZONE` | Biarkan `UTC`. Tanggal kalender operasional dihitung di `Asia/Jakarta` oleh kode. |
| `PKL_EMAIL_DOMAIN` | Domain email yang boleh login (default `smkn1kotabekasi.sch.id`). |
| `PKL_MAIL_OVERRIDE_TO` | Isi dengan email nyata Anda. Seluruh email lokal dialihkan ke alamat ini agar OTP bisa dibaca. Kosongkan untuk mematikan. |
| `MAIL_MAILER` | Default `log`. Isi email masuk ke `storage/logs/laravel.log`. |
| `QUEUE_CONNECTION` | Default `database`. Jangan diganti ke `sync` saat menguji PDF dan notifikasi. |

### Akun demo

`php artisan migrate --seed` mengisi jurusan, kelas, periode akademik, FAQ, dan
satu akun per peran. Daftar akun ada di `database/seeders/AccountSeeder.php`.

Kata sandi awal setiap akun = NIS/NIP-nya, dan `is_first_login = true`, jadi
aplikasi langsung meminta ganti kata sandi pada login pertama. Jangan menjalankan
`--seed` di lingkungan selain lokal.

## Menjalankan

Empat proses terpisah, masing-masing di terminalnya sendiri:

```bash
php artisan serve        # aplikasi di http://127.0.0.1:8000
npm run dev              # Vite (Tailwind/Livewire) saat mengembangkan tampilan
php artisan queue:work   # worker antrean
php artisan schedule:work # scheduler lokal, termasuk kedaluwarsa surat visitasi
```

Worker antrean wajib hidup: penerbitan PDF surat, QR, dan pengiriman notifikasi
berjalan lewat queue `database`. Tanpa worker, surat terbit di database tetapi
berkas PDF tidak pernah dibuat.

### Alamat penting

| Alamat | Fungsi |
|---|---|
| `/` | Beranda publik |
| `/login` | Satu-satunya pintu masuk semua peran; setelahnya OTP dan/atau ganti sandi |
| `/app` | Panel peran non-Hubin (siswa, walas, kaprog, guru pembimbing, kepsek) |
| `/hubin` | Panel Hubin (desktop) |
| `/verify/{token}` | Verifikasi surat publik hasil pindai QR |
| `/documents/{jenis}/{id}` | Unduh PDF surat; wajib login dan berwenang |

## Berkas privat

Semua unggahan (scan tanda tangan, lampiran bimbingan, surat balasan, dokumentasi
visitasi) disimpan di `storage/app/private` lewat disk `local`. Tidak ada
`php artisan storage:link` yang diperlukan, dan berkas tidak dapat diakses langsung
dari URL. Unduhan selalu melewati controller berotorisasi.

Pastikan direktori `storage/` dan `bootstrap/cache/` dapat ditulis proses PHP.

## Tes

```bash
php artisan test
```

Tes berjalan di database `sistem_pkl_test` (lihat `phpunit.xml`), memakai
`QUEUE_CONNECTION=sync` dan `MAIL_MAILER=array`, jadi tidak mengganggu data lokal.
`APP_KEY` harus terisi karena token QR verifikasi dihitung dari kunci itu.

CI di `.github/workflows/ci.yml` menjalankan langkah yang sama di MySQL 8.4.

## Catatan penting

- **Jangan mengganti `APP_KEY` setelah surat terbit.** Token QR adalah
  `HMAC-SHA256(APP_KEY, "jenis:id")`; kunci baru membuat semua tautan verifikasi
  lama menunjuk ke pesan "dokumen tidak ditemukan".
- **Penerbitan surat ditahan tanpa scan tanda tangan Kepala Sekolah.** Unggah
  lewat panel Hubin, menu **scan TTD Kepsek**, sebelum menerbitkan surat; tanpa
  itu permintaan ditolak dengan status 409.
- Aset Filament terkompilasi ikut di repo (`public/js/filament`,
  `public/fonts/filament`). Bila hilang atau setelah menaikkan versi Filament,
  jalankan `php artisan filament:assets`.
- Tanda tangan hasil pindai berformat PDF tidak dapat disematkan ke PDF surat
  (dompdf hanya menerima gambar). Gunakan PNG atau JPG bila ingin tanda tangan
  tampil.
- Nomor surat berurutan per tahun ajaran dan jenis surat, disimpan di tabel
  `nomor_surat_counters`. Mengosongkan tabel itu akan mengulang nomor dari awal.

## Troubleshooting

| Gejala | Penyebab dan tindakan |
|---|---|
| QR atau PDF gagal dibuat | Ekstensi `gd` belum aktif. Periksa `php -m`. |
| Surat tertahan 409 "menunggu scan TTD Kepala Sekolah" | Belum ada `signature_assets` aktif. Unggah lewat panel Hubin. |
| Surat tidak muncul berkasnya | Worker `queue:work` tidak jalan, atau `QUEUE_CONNECTION` bukan `database`. |
| Email OTP tidak diterima | `MAIL_MAILER=log`; baca `storage/logs/laravel.log`, atau isi `PKL_MAIL_OVERRIDE_TO`. |
| Perubahan `.env` tidak terbaca | `php artisan config:clear` lalu ulangi. |
| Migrasi gagal soal `JSON`/`CHECK` | MySQL terlalu lama. Pakai 8.0.16 atau lebih baru. |
| Panel tampil tanpa gaya | `npm run build` belum dijalankan, atau aset Filament hilang. |
