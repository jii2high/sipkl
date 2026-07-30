# Panduan M4 — Persuratan (revisi, sesuai TRD final v1.3)

Cakupan: generate PDF Surat Pengantar, bundling per perusahaan, preview + Cetak/Kirim
terpisah, lock upload balasan (BR20), auto Resmi PKL dengan validasi sistem + exception
reject Hubin (BR7).

> Revisi dari draft sebelumnya: `spatie/laravel-model-states`, `spatie/laravel-medialibrary`,
> dan `spatie/laravel-pdf` **tidak dipakai** (TRD §13). Status pengajuan cuma 5 nilai
> (`diajukan`, `disetujui`, `ditolak`, `resmi_pkl`, `selesai_pkl`) — M4 tidak menambah state
> baru. "Surat Pengantar sudah terbit" dan "Balasan sudah diupload" dicek lewat **keberadaan
> record** (relasi), bukan lewat state tambahan.

## 1. Data

- Tabel `surat_pengantar`: terikat ke perusahaan + periode, nomor surat unik (format BR18),
  tanggal terbit, siapa yang menerbitkan, path file PDF hasil generate.
- Tabel pivot antara `surat_pengantar` dan `pengajuan_pkl` (1 surat pengantar bisa nampung
  banyak pengajuan, maks 6 siswa per BR3 — soft rule, bukan hard block).
- Tabel `surat_balasan`: 1 baris per pengajuan, cuma path file. Dihapus/diganti kalau Hubin
  menolak balasan (lihat §5).
- Kolom `status` di `pengajuan_pkl` sudah ada dari M3, M4 cuma menggerakkannya dari
  `disetujui` ke `resmi_pkl` di titik BR7 — tidak menambah kolom status baru.
- "Sudah punya Surat Pengantar" dan "sudah upload Surat Balasan" dicek lewat relasi (misal
  `existsSuratPengantar`, `existsSuratBalasan`), bukan lewat nilai status.

## 2. Generate PDF

- Pakai library PDF Laravel generik (mis. `barryvdh/laravel-dompdf`), render dari Blade
  template — **bukan** `spatie/laravel-pdf`.
- dompdf tidak menjalankan JS, jadi Tailwind lewat CDN tidak akan ke-apply di template surat.
  Pakai CSS inline/plain untuk template ini, atau compile Tailwind ke CSS statis dulu.
- File hasil generate disimpan sebagai path biasa di storage lokal, bukan lewat media
  library.
- Kop surat, nomor surat, dan daftar siswa (maks 6) diambil dari relasi pivot surat pengantar.
- Nomor surat digenerate lewat satu helper terpusat (format BR18), bukan di-hardcode di view.

## 3. Halaman Hubin: Bundling & Cetak + Balasan Masuk

Satu halaman per perusahaan+periode, dua tab — bukan halaman approval terpisah:

- **Tab Kirim Surat Pengantar**: daftar pengajuan berstatus `disetujui` yang belum punya
  Surat Pengantar untuk perusahaan+periode itu. Tampilkan warning kalau jumlah siswa lewat
  kuota saran perusahaan (BR3), tapi tetap bisa lanjut cetak/kirim.
- **Tab Balasan Masuk**: daftar pengajuan berstatus `resmi_pkl` di perusahaan+periode itu,
  dengan link lihat file balasan dan tombol tolak (lihat §5).
- Tombol **Cetak** (download PDF, tidak mengubah data apa pun) dan tombol **Kirim ke Siswa**
  (baru di titik ini record Surat Pengantar dibuat + notifikasi terkirim) harus terpisah jelas
  — Hubin boleh preview/cetak berkali-kali sebelum benar-benar kirim.

## 4. BR20 — lock upload Surat Balasan

- Tombol/form upload balasan di sisi siswa disable/hidden sampai pengajuan itu punya Surat
  Pengantar.
- Validasi ini wajib juga ditegakkan di backend, bukan cuma disable tombol di UI — request
  upload balasan untuk pengajuan yang belum punya Surat Pengantar harus ditolak (403).

## 5. BR7 — upload balasan (auto) + reject pasca-fakta oleh Hubin

- Upload balasan wajib lolos validasi file dulu (mimetype PDF, ukuran wajar, file bisa
  dibuka/tidak corrupt) sebelum diproses.
- Begitu lolos validasi file, status **langsung** otomatis jadi `resmi_pkl` — tidak menunggu
  approval manual siapa pun di jalur normal. Ini beda dari asumsi lama yang mengira ada gate
  approval Hubin; yang benar, approval Hubin itu sifatnya pasca-fakta/exception, bukan gate
  blocking di jalur upload.
- File corrupt/tidak valid ditolak di level validasi, siswa dapat pesan error jelas dan
  diminta upload ulang — ini belum sampai status berubah.
- Hubin tetap punya wewenang menolak balasan **setelah** statusnya `resmi_pkl`, khusus untuk
  kasus dokumen ternyata bukan surat penerimaan yang valid (lolos validasi file tapi salah
  isi). Aksi ini pakai permission `surat-balasan.verifikasi`, wajib catatan minimal 15
  karakter (konsisten pola reject Kaprog BR6), menghapus record balasan, dan mengembalikan
  status ke `disetujui` supaya siswa bisa upload ulang.
- Setiap perubahan status di alur ini (upload → `resmi_pkl`, reject Hubin → `disetujui`)
  wajib tercatat di riwayat status (infra dari M3), termasuk notifikasi ke pihak terkait.

## Checkpoint akhir M4

- Tab Kirim Surat Pengantar nampilin daftar siswa yang benar per perusahaan+periode, warning
  muncul kalau lewat 6 siswa tapi tetap bisa lanjut cetak.
- Tombol Cetak menghasilkan PDF dengan nomor surat sesuai format BR18, tidak mengubah status
  atau membuat record Surat Pengantar.
- Tombol Kirim membuat record Surat Pengantar dan trigger notifikasi ke siswa terkait.
- Upload balasan ditolak backend (bukan cuma UI) kalau Surat Pengantar belum ada untuk
  pengajuan itu.
- Upload balasan yang lolos validasi file langsung mengubah status jadi `resmi_pkl` otomatis,
  tanpa approval tambahan di jalur normal.
- Upload balasan dengan file corrupt/tidak valid ditolak validasi, siswa dapat pesan error
  jelas, belum mengubah status.
- Hubin bisa lihat daftar balasan masuk per perusahaan+periode di tab Balasan Masuk, dan bisa
  menolak balasan yang ternyata salah/tidak valid (dengan catatan ≥15 karakter) — status balik
  ke `disetujui`, siswa upload ulang.
- Riwayat status mencatat semua transisi baru ini dengan benar, termasuk kasus reject-balasan
  oleh Hubin.
