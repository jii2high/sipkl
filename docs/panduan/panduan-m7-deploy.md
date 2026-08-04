# Panduan M7 — Deployment & UAT

Cakupan: murni deployment & User Acceptance Testing. **Tidak ada fitur atau komponen UI baru di milestone ini** — kalau nemu gap fitur pas UAT, itu tandanya balik ke milestone terkait, bukan ditambal di M7.

## 1. Persiapan build production

```bash
composer install --no-dev --optimize-autoloader
npm run build          # atau npm run build:ssr kalau butuh Inertia SSR
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Jangan `config:cache` sebelum semua `.env` production kefix — cache nyimpen value env saat itu, salah urutan bikin config lama nyangkut.

## 2. Environment production

`.env` production, poin yang beda dari lokal:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<domain-sekolah>

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=si_pkl
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database   # atau redis kalau tersedia
SESSION_DRIVER=database
```

- `APP_DEBUG=false` wajib — kalau nyala di production, stack trace error bisa kebuka ke publik.
- Generate `APP_KEY` baru khusus production (`php artisan key:generate`), jangan pakai punya lokal.

## 3. Migrasi & seeding data produksi

```bash
php artisan migrate --force
```

- **Jangan** jalanin `DatabaseSeeder` versi development (isinya akun dummy) di production.
- Bikin `ProductionSeeder` terpisah — isinya cuma: role & permission (`RoleSeeder`), master `kompetensi_keahlian` sesuai jurusan riil sekolah, dan 1 akun Hubin pertama (buat mulai import data guru/siswa asli lewat fitur M2).
- Data guru & siswa asli masuk lewat fitur **Import Excel** yang udah dibangun di M2 — bukan seeder manual, biar konsisten sama validasi yang udah ada.

## 4. Scheduler & queue worker

Command `pkl:tandai-selesai` (M6) butuh scheduler jalan terus — kalau hosting bukan yang nyediain Forge/managed cron, pasang cron job server manual:

```
* * * * * cd /path/ke/si-pkl && php artisan schedule:run >> /dev/null 2>&1
```

Queue worker (buat notifikasi, generate PDF berat, dst) jalan sebagai proses persisten (supervisor/systemd), bukan `php artisan queue:work` yang mati kalau SSH ditutup:

```
php artisan queue:work --tries=3 --timeout=60
```

## 5. Storage & file upload

```bash
php artisan storage:link
```

Pastiin folder `storage/app/public` (dan koleksi medialibrary — surat, dokumentasi kunjungan, laporan akhir) writable oleh web server user, dan **di-backup** sama seriusnya kayak database — dokumen-dokumen ini bagian dari arsip resmi sekolah.

## 6. Checklist UAT per role

Jalanin dengan akun & data mendekati kondisi asli (bukan dummy 1-1), minimal 1 siklus penuh per role:

**Siswa**

- [ ] Login pertama pakai NIS dipaksa ganti password (M1)
- [ ] Isi & submit pengajuan, lihat status berubah real-time di dashboard (M3)
- [ ] Terima notifikasi approve/reject (M3)
- [ ] Download Surat Pengantar setelah terbit (M4)
- [ ] Upload Surat Balasan cuma bisa setelah surat pengantar terbit (M4, BR20)
- [ ] Upload Laporan Akhir, lihat status Resmi PKL → Selesai (M6)
- [ ] Riwayat PKL nampilin data periode lama kalau ada (M3)

**Kaprog**

- [ ] Antrian verifikasi cuma nampilin siswa jurusannya sendiri (M3, BR2)
- [ ] Approve/reject dengan popup konfirmasi & validasi catatan (M3)
- [ ] Assign Guru Pembimbing termasuk self-assign, muncul di "Bimbingan Saya" (M5)
- [ ] Request Surat Tugas Scoped ke Hubin (M5)

**Guru Pembimbing**

- [ ] Cuma lihat siswa bimbingannya (M5)
- [ ] Gak bisa isi Laporan Kunjungan sebelum Surat Tugas terbit (M5, BR22)
- [ ] Formulir Penilaian gated sampai Laporan Akhir siswa masuk (M6, BR32)
- [ ] Nilai Akhir keitung otomatis dan predikat sesuai skala (M6, BR25/BR28)

**Hubin**

- [ ] Kirim/Cetak Surat Pengantar terpisah, bundling per perusahaan benar (M4)
- [ ] Terbitkan Surat Tugas Global & Scoped (M5)
- [ ] Nonaktifkan Kaprog otomatis ubah role tanpa langkah manual tambahan (M2)
- [ ] Import Excel guru/siswa, baris invalid dilaporkan per baris (M2, BR16)
- [ ] Export template & data akun (M2)
- [ ] Kelola master Indikator Penilaian & Section, kategori Umum/Teknis Kejuruan kebedain jelas (M2)

## 7. Monitoring & error tracking pasca-launch

- Aktifin Laravel log ke file harian (`config/logging.php`, channel `daily`) minimal, biar gampang ditelusur kalau ada laporan bug dari user.
- Kalau ada budget/akses, pasang error tracking ringan (mis. Sentry gratis tier) — bukan wajib buat scope sekolah, tapi bantu banget pas minggu-minggu awal launch.

## Checkpoint akhir M7 (definisi selesai)

- Semua item checklist UAT di atas lolos dengan akun & data mendekati riil.
- `APP_DEBUG=false`, `.env` production terisah dari lokal, `APP_KEY` unik.
- Scheduler & queue worker jalan sebagai proses persisten, bukan proses manual yang harus di-trigger orang.
- Backup storage (dokumen) & database berjalan terjadwal, bukan cuma sekali di awal.
- Gak ada fitur baru yang "kepaksa" ditambahin di milestone ini — kalau ada, itu artinya scope-nya harusnya balik ke M2–M6.
