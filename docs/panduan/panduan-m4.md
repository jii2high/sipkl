# Panduan Implementasi — M4: Persuratan

**Referensi:** `PRD.md` §5 (poin 3–4), §8 (BR7, BR16–BR20) | `TRD.md` §3.2, §4.2, §6, §7, §8 | `milestone.md` M4

---

## 0. Prasyarat

- [ ] Checklist M3 sudah lulus semua. Status `disetujui` pada `pengajuan_pkl` sudah bisa dicapai lewat alur verifikasi Kaprog. Infra `pengajuan_pkl_histories` dan notifikasi (Laravel DB Notification + Sonner) dari M3 sudah siap dipakai ulang.

## 1. Tujuan Milestone

Membangun seluruh alur surat: dari Surat Pengantar (setelah pengajuan disetujui) sampai status Resmi PKL.

## 2. Urutan Eksekusi Modul/Fitur

### 2.1 Generasi PDF Surat Pengantar (dengan Penomoran Surat)
- **BR16**: Surat Pengantar hanya bisa diterbitkan Hubin setelah pengajuan berstatus `disetujui` (guard dari BR9 di M3).
- Tabel `surat_pengantar` (`TRD.md` §3.2): `nomor_surat` (unique), `pengajuan_pkl_id`, `diterbitkan_oleh`, `file_pdf`.
- **BR17**: nomor surat unik, digenerate **sequential per jenis surat per periode/tahun ajaran** — bukan random.
- **Penomoran (`TRD.md` §6)**: nomor digenerate saat surat **diterbitkan** (bukan saat draft), dibungkus **DB transaction** dengan `lockForUpdate` pada counter per jenis+periode untuk menghindari nomor bentrok saat concurrent create.
- **Generasi PDF (`TRD.md` §7)**: alur = template Blade/HTML → render PDF (pakai library PDF Laravel, mis. `barryvdh/laravel-dompdf`) → simpan ke `storage/app/surat/...` → link download/preview di UI.
- **BR18**: dokumen surat diarsipkan di storage, bisa diunduh ulang kapan saja oleh pihak berwenang.
- Permission: `surat.terbitkan` (hubin).
- Controller: `Hubin/SuratController.php`.
- Catat penerbitan surat ini juga sebagai perubahan status di `pengajuan_pkl_histories` (BR6, infra dari M3).

Smoke test:
- [ ] PDF Surat Pengantar tergenerate dengan nomor surat unik dan format sesuai template.
- [ ] Terbitkan 2 surat secara bersamaan (concurrent) untuk jenis+periode yang sama → tidak ada nomor bentrok (verifikasi lock bekerja).

### 2.2 Upload Surat Balasan (Siswa) — Conditional Lock
- Tabel `surat_balasan`.
- **BR20**: upload Surat Balasan **terkunci** sampai Surat Pengantar untuk pengajuan tsb resmi terbit. Gate ini di frontend (tombol disabled/hidden, `TRD.md` §4.2) **dan** backend (validasi ulang di Form Request/Policy — jangan andalkan frontend saja).
- Permission: `surat-balasan.upload` (siswa).
- Controller: `Siswa/SuratBalasanController.php`.

Smoke test:
- [ ] Upload Surat Balasan benar-benar terkunci sebelum Surat Pengantar terbit (coba akses langsung via request manual, bukan hanya cek UI).
- [ ] Setelah Surat Pengantar terbit, tombol upload aktif dan upload berhasil.

### 2.3 Verifikasi Surat Balasan → Auto Status Resmi PKL (BR7)
- **BR7**: status **Resmi PKL** aktif otomatis pada siswa setelah Surat Balasan yang diupload diverifikasi Hubin — **tidak ada** aksi manual tambahan untuk mengubah status ini.
- Update `pengajuan_pkl.status` → `resmi_pkl` sebagai efek samping otomatis dari aksi verifikasi (bukan tombol status terpisah).
- Catat perubahan ini di `pengajuan_pkl_histories`.
- Trigger notifikasi: Surat Balasan diverifikasi → Resmi PKL (ke Siswa).
- Permission: `surat-balasan.verifikasi` (hubin).

Smoke test:
- [ ] Status siswa otomatis Resmi PKL setelah verifikasi Surat Balasan (tanpa aksi manual lain).
- [ ] Perubahan tercatat di `pengajuan_pkl_histories`.

### 2.4 Fitur Bundling & Cetak (Hubin)
- **BR19**: preview dokumen sebelum aksi final; tombol **Cetak** dan **Kirim** **terpisah** — preview tidak otomatis memicu pengiriman.
- Controller: `Hubin/BundlingController.php`.
- Permission: `persuratan.bundling` (hubin).

Smoke test:
- [ ] Tombol Cetak dan Kirim di Bundling berfungsi independen — buka preview tidak otomatis mengirim apapun.

### 2.5 Notifikasi Terkait Persuratan
Tambahkan trigger baru ke infra notifikasi M3 (**jangan** bangun infra baru):
- Surat Pengantar terbit → ke Siswa.
- Surat Balasan diupload (opsional, sesuai kebutuhan internal) / diverifikasi → Resmi PKL → ke Siswa.

Smoke test:
- [ ] Notifikasi terkait persuratan (surat terbit, balasan diupload, dst.) muncul sesuai event yang relevan.

## 3. Checklist Testing Akhir Sesi M4

- [ ] Upload Surat Balasan benar-benar terkunci sebelum Surat Pengantar terbit.
- [ ] Status siswa otomatis Resmi PKL setelah verifikasi Surat Balasan.
- [ ] PDF Surat Pengantar tergenerate dengan nomor surat unik dan format sesuai template.
- [ ] Tombol Cetak dan Kirim di Bundling berfungsi independen (preview tidak auto-kirim).

**Semua item di atas harus lulus sebelum M5 dimulai.**
