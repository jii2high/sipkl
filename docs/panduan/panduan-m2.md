# Panduan Implementasi — M2: Master Data & User Management (Hubin)

**Referensi:** `PRD.md` §6.4, §7, §8 (BR21, BR31) | `TRD.md` §3, §5, §5.1, §12 | `milestone.md` M2

---

## 0. Prasyarat

- [ ] Checklist M1 sudah lulus semua.
- [ ] Mulai M2, rotasi slot kembali normal: 1 orang per slot (Backend Core, Frontend/UI, Integrasi&QA), rotasi tiap milestone.

## 1. Tujuan Milestone

Membangun seluruh master data yang menjadi dependency modul-modul berikutnya (M3–M6), plus komponen **Search & Filter Bar** reusable yang dipakai ulang di seluruh proyek.

## 2. Urutan Eksekusi Modul/Fitur

### 2.1 CRUD Perusahaan
- Tabel `perusahaan` (master data mitra PKL).
- Validasi pakai Form Request class, pesan Bahasa Indonesia.
- Smoke test: create/read/update/delete berjalan dengan validasi benar.

### 2.2 CRUD Periode PKL
- Tabel `periode_pkl` — ini yang nanti jadi sumber pilihan periode di form Pengajuan PKL (BR2: **tidak ada** input periode bebas, harus pilih dari master).
- Smoke test: CRUD berjalan.

### 2.3 CRUD Kompetensi Keahlian (Jurusan)
- Tabel `kompetensi_keahlian` sudah ada sejak M1 (dipakai `siswa_profiles`) — di sini tambahkan CRUD-nya: kolom nama, acronym.
- Smoke test: CRUD berjalan, tidak merusak relasi yang sudah dipakai `siswa_profiles`/`guru_profiles`.

### 2.4 CRUD Indikator Penilaian + Section Penilaian
Baca dulu struktur di `TRD.md` §3.2 dan BR24–BR27:
- `indikator_penilaian`: kolom `kategori` enum (`umum`/`teknis_kejuruan`, BR24). Jika `umum`, `kompetensi_keahlian_id` = NULL (berlaku semua jurusan); jika `teknis_kejuruan`, scoped ke satu jurusan.
- `section_penilaian`: child dari `indikator_penilaian`, **nullable** (BR26) — Hubin **tidak wajib** membuat Section untuk tiap Indikator.
- Struktur maksimal 2 level: Indikator → Section, **tidak ada** sub-sub-indikator (BR27).
- Permission terkait: `master-penilaian.kelola` (hubin).

Smoke test:
- [ ] Bisa buat Indikator kategori Umum (tanpa jurusan) dan Teknis Kejuruan (scoped ke satu jurusan).
- [ ] Bisa buat Indikator dengan Section maupun tanpa Section — keduanya valid.

### 2.5 Kelola Akun (Hubin)
- Tabel Kelola Akun Kaprog dengan **satu** aksi: **"Nonaktifkan sebagai Kaprog"**.
- **Penting (BR31, Keputusan Desain §9 PRD):** ini **bukan** dropdown "Ubah Jabatan" bebas — hanya satu aksi spesifik ini yang tersedia di UI.
- Aksi ini mengubah `jabatan` di `guru_profiles`, yang akan memicu `GuruProfileObserver` (dibangun di M1) sehingga role Spatie otomatis berubah jadi `guru_pembimbing`.
- Permission: `akun.kelola` (hubin).

Smoke test:
- [ ] Klik "Nonaktifkan sebagai Kaprog" → role Spatie user tsb berubah jadi Guru Pembimbing (via Observer yang sama dari M1, **tidak** ada logic baru untuk assign role di modul ini).
- [ ] Tidak ada opsi ubah jabatan lain selain aksi ini di UI.

### 2.6 Import/Export Excel Data Master
- Pakai `maatwebsite/excel` (Laravel Excel).
- Terapkan untuk data master yang relevan (mis. Perusahaan, Kompetensi Keahlian — sesuaikan kebutuhan riil).
- Smoke test: export lalu import ulang file yang sama → data tidak korup, round-trip aman.

### 2.7 Search & Filter Bar (BR21)
- **Wajib** komponen React global reusable, dibangun **sekali** di M2, dipasang di seluruh modul list/table proyek ini dan seterusnya.
- Pasang minimal di 2 modul yang sudah ada di M2 (mis. list Perusahaan, list Periode PKL) sebagai bukti reusability sebelum lanjut milestone berikutnya.

Smoke test:
- [ ] Search & Filter Bar berfungsi (search + filter) di minimal 2 modul berbeda dengan komponen yang sama (bukan duplikat kode per modul).

## 3. Checklist Testing Akhir Sesi M2

- [ ] CRUD tiap master data berjalan dengan validasi Bahasa Indonesia.
- [ ] Aksi "Nonaktifkan sebagai Kaprog" memicu Observer M1 dan mengubah role jadi Guru Pembimbing.
- [ ] Import/Export Excel berhasil round-trip tanpa korupsi data.
- [ ] Search & Filter Bar berfungsi di minimal 2 modul berbeda.

**Semua item di atas harus lulus sebelum M3 dimulai.**

## 4. Catatan untuk Milestone Berikutnya

Master data yang selesai di sini (`perusahaan`, `periode_pkl`, `kompetensi_keahlian`, `indikator_penilaian`, `section_penilaian`) menjadi dependency langsung untuk:
- M3 (Pengajuan PKL butuh `perusahaan` + `periode_pkl`).
- M6 (Formulir Penilaian butuh `indikator_penilaian` + `section_penilaian`).
