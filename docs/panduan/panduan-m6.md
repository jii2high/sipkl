# Panduan Implementasi — M6: Penilaian Akhir & Selesai PKL

**Referensi:** `PRD.md` §5 (poin 8–10), §6.1, §6.3, §8 (BR13, BR24–BR29, BR32) | `TRD.md` §3.2, §4.3, §5.1, §9, §12 | `milestone.md` M6

---

## 0. Prasyarat

- [ ] Checklist M5 sudah lulus semua.
- [ ] Seeder `indikator_penilaian` + `section_penilaian` dari M2/M9 mencakup **minimal satu jurusan dengan sub-indikator** dan **minimal satu jurusan tanpa sub-indikator** — dibutuhkan untuk menguji BR28 di dua skenario di milestone ini.

## 1. Tujuan Milestone

Membangun alur penilaian akhir sampai penutupan status PKL siswa.

## 2. Urutan Eksekusi Modul/Fitur

### 2.1 Upload Laporan Akhir PKL (Siswa)
- Tabel `laporan_akhir_pkl`.
- Ini jadi **gate** pembuka Formulir Penilaian (2.2) — pastikan status upload tercatat dengan jelas (mis. timestamp upload) karena akan dicek di Policy modul berikutnya.
- Permission: `laporan-akhir.upload` (siswa).
- Controller: `Siswa/LaporanAkhirController.php`.

Smoke test: siswa upload Laporan Akhir, file tersimpan dan tercatat.

### 2.2 Formulir Penilaian PKL (Guru Pembimbing) — Gated oleh Laporan Akhir (BR32)
- Tabel `formulir_penilaian` (`TRD.md` §3.2, 1 per siswa per periode): `siswa_id`, `periode_pkl_id`, `guru_pembimbing_id`, `sakit`, `izin`, `alpa` (jumlah hari), `catatan_guru_pembimbing`, `nama_pembimbing_industri`, `nilai_akhir` (nullable, diisi saat submit), `submitted_at` (nullable).
- **BR25**: field wajib di formulir induk — sakit/izin/alpa, catatan Guru Pembimbing, nama Pembimbing Industri, selain nilai per indikator.
- **BR32**: formulir baru terbuka & bisa diakses Guru Pembimbing **setelah** Laporan Akhir diupload siswa (gate eksplisit, cek lewat Policy `PenilaianPolicy`). Ini **berbeda** dari Laporan Kunjungan (M5) yang bisa diisi berkala sepanjang periode — Formulir Penilaian **hanya diisi sekali** menjelang sidang.
- State machine (`TRD.md` §4.3):
  ```
  Laporan Akhir belum diupload → Formulir tidak dapat diakses
  Laporan Akhir sudah diupload → Formulir terbuka (submitted_at masih NULL)
  Formulir sudah submit (submitted_at terisi) → read-only
  ```
- **BR12** (dari M5): Guru Pembimbing hanya bisa isi untuk siswa yang eksplisit di-assign kepadanya.
- Permission: `penilaian.isi` (guru_pembimbing untuk siswa assigned; +kaprog via self-assign), `penilaian.lihat` (guru_pembimbing, kaprog scoped, **siswa** — read-only untuk nilai sendiri).
- Controller: `GuruPembimbing/PenilaianController.php`.

Smoke test: Formulir tidak bisa diakses sebelum Laporan Akhir diupload; setelah diupload, formulir terbuka untuk diisi Guru Pembimbing yang assigned.

### 2.3 Input Nilai per Indikator/Section
- Tabel `nilai` (`TRD.md` §3.2): `formulir_penilaian_id`, `indikator_penilaian_id` (nullable), `section_penilaian_id` (nullable) — **mutually exclusive**: satu baris nilai menunjuk ke **salah satu** (leaf di level Indikator **atau** leaf di level Section, sesuai BR28), `nilai` (0–100), `predikat` (computed).
- Kategori Umum & Teknis Kejuruan (dari M2) sama-sama muncul di form penilaian ini.
- **BR29**: skala predikat **fixed**, sama untuk seluruh jurusan, **tidak bisa dikustomisasi**: K (<70), C (70–80), B (81–90), SB (91–100). Hitung `predikat` sebagai computed field dari `nilai`.

Smoke test: input nilai tersimpan benar per leaf (Indikator atau Section, tidak dua-duanya), predikat terhitung sesuai skala fixed.

### 2.4 Kalkulasi Nilai Akhir Otomatis (BR28)
- **BR28**: Nilai Akhir = rata-rata seluruh nilai **leaf** yang terisi. Leaf = Section (jika Indikator tsb punya Section), atau Indikator itu sendiri (jika Indikator tsb **tidak** punya Section).
- **Wajib** diuji di dua skenario (sesuai seeder di prasyarat §0):
  1. Jurusan dengan sub-indikator — leaf yang dihitung adalah level Section.
  2. Jurusan tanpa sub-indikator — leaf yang dihitung adalah level Indikator langsung.
- **Concurrency (`TRD.md` §12)**: kalkulasi ini dibungkus DB transaction untuk menghindari race condition (mis. submit ganda).

Smoke test:
- [ ] Nilai Akhir terhitung benar untuk kasus jurusan dengan sub-indikator.
- [ ] Nilai Akhir terhitung benar untuk kasus jurusan tanpa sub-indikator.

### 2.5 Auto Status Selesai PKL (BR13)
- **BR13**: status **Selesai PKL** aktif otomatis pada siswa setelah Nilai Akhir terisi (hasil submit Formulir Penilaian) — **tidak ada** aksi manual tambahan.
- Saat submit: set `submitted_at`, hitung & simpan `nilai_akhir` (2.4), lalu update `pengajuan_pkl.status` → `selesai_pkl` sebagai efek samping otomatis dalam transaksi yang sama.
- **Formulir hanya bisa disubmit sekali** — setelah `submitted_at` terisi, formulir jadi read-only, tidak bisa diisi ulang bebas.
- Catat perubahan status ini juga di `pengajuan_pkl_histories` (infra dari M3).
- Trigger notifikasi: Formulir Penilaian terbuka (ke Guru Pembimbing, saat Laporan Akhir diupload) dan Selesai PKL (ke Siswa, saat submit).

Smoke test:
- [ ] Formulir hanya bisa disubmit sekali (tidak bisa diisi ulang bebas setelah `submitted_at` terisi).
- [ ] Status siswa otomatis berubah jadi Selesai PKL setelah submit penilaian, tanpa aksi manual tambahan.

### 2.6 Notifikasi Terkait Penilaian
Tambahkan trigger baru ke infra notifikasi M3:
- Laporan Akhir diupload → Formulir Penilaian terbuka (ke Guru Pembimbing).
- Formulir Penilaian submit → Selesai PKL (ke Siswa).
- Siswa juga bisa lihat Nilai Akhir setelah Selesai PKL (permission `penilaian.lihat`, read-only untuk siswa).

## 3. Checklist Testing Akhir Sesi M6

- [ ] Formulir Penilaian benar-benar terkunci sebelum Laporan Akhir diupload siswa.
- [ ] Formulir hanya bisa disubmit sekali (tidak bisa diisi ulang bebas).
- [ ] Nilai Akhir terhitung benar untuk kasus jurusan dengan sub-indikator maupun tanpa sub-indikator.
- [ ] Status siswa otomatis berubah jadi Selesai PKL setelah submit penilaian.

**Semua item di atas harus lulus sebelum M7 dimulai. Ini juga milestone fitur terakhir — tidak ada fitur baru lagi setelah M6, M7 murni deployment & UAT.**
