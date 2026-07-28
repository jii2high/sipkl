# Panduan Implementasi — M5: Pembimbingan & Kunjungan Monitoring

**Referensi:** `PRD.md` §5 (poin 5–7), §6.2–6.3, §8 (BR10–BR12, BR22–BR23, BR30) | `TRD.md` §3.2, §4.4, §4.5, §5, §5.1 | `milestone.md` M5

---

## 0. Prasyarat

- [ ] Checklist M4 sudah lulus semua. Siswa dengan status `resmi_pkl` sudah tersedia sebagai basis untuk assignment pembimbing.

## 1. Tujuan Milestone

Membangun assignment Guru Pembimbing dan alur monitoring kunjungan yang terikat pada Surat Tugas resmi.

## 2. Urutan Eksekusi Modul/Fitur

### 2.1 Assign Guru Pembimbing (Kaprog)
- Tabel `pembimbingan` (`TRD.md` §3.2): `siswa_id`, `guru_pembimbing_id`, `assigned_by`, `status` (enum `aktif`/`diarsipkan`), `is_self_assign`.
- UI: tabel per baris (Siswa, Perusahaan, dropdown Guru Pembimbing, tombol Simpan/Ubah).
- **BR10**: wewenang **eksklusif** Kaprog jurusan siswa ybs — **tidak ada** hak override dari Hubin. Scoping via Policy (`PembimbinganPolicy`), bukan role check inline.
- **BR11**: satu siswa hanya boleh punya **satu** Guru Pembimbing aktif per periode PKL. Penggantian pembimbing → assignment lama diubah jadi `status=diarsipkan` (**bukan** dihapus), assignment baru dibuat `status=aktif` (state machine di `TRD.md` §4.5).
- Permission: `pembimbing.assign` (kaprog, scoped).
- Controller: `Kaprog/PembimbinganController.php`.
- Trigger notifikasi: Guru Pembimbing di-assign → ke Siswa & Guru Pembimbing.

Smoke test:
- [ ] Hanya Kaprog jurusan terkait yang bisa assign Guru Pembimbing untuk siswanya (coba dari Kaprog jurusan lain → ditolak).
- [ ] Ganti pembimbing untuk siswa yang sudah punya pembimbing aktif → assignment lama berstatus `diarsipkan`, tidak hilang dari histori.

### 2.2 Self-Assign Kaprog sebagai Pembimbing
- **BR10** (lanjutan): Kaprog boleh self-assign dirinya sendiri sebagai Guru Pembimbing untuk siswa jurusannya.
- **Penting (`TRD.md` §5, PRD §3 & §7)**: Kaprog **tetap satu role Spatie** (`kaprog`) — **tidak** ada role gabungan. Yang terjadi adalah penambahan permission `kunjungan.*` (dan nanti `penilaian.*` di M6) di atas role `kaprog` yang sudah ada.
- Akses ditampilkan lewat section terpisah **"Bimbingan Saya"** di dashboard Kaprog — bukan menggabungkan UI Guru Pembimbing ke dashboard Kaprog.
- `pembimbingan.is_self_assign = true` untuk assignment jenis ini.

Smoke test:
- [ ] Kaprog self-assign muncul benar di "Bimbingan Saya" tanpa mengubah role Spatie-nya (masih `kaprog`, cek via tinker/`hasRole()`).
- [ ] Kaprog yang self-assign bisa mengakses fitur Laporan Kunjungan (2.4) untuk siswa yang di-self-assign-nya, lewat permission tambahan — bukan lewat role baru.

### 2.3 Surat Tugas Monitoring — Global & Scoped (BR22, BR23)
- Tabel `surat_tugas_monitoring` (`TRD.md` §3.2): `nomor_surat` (unique, sequential — sama seperti `surat_pengantar`, pola dari M4 §6), `lingkup` (enum `global`/`scoped`), `kompetensi_keahlian_id` (nullable, null jika global), `diterbitkan_oleh`, `file_pdf`.
- **BR23**: dua lingkup —
  - **Global**: berlaku seluruh jurusan, diinisiasi langsung oleh Hubin.
  - **Scoped**: berlaku satu jurusan tertentu, diterbitkan Hubin **atas request eksplisit** dari Kaprog jurusan tsb.
- Permission: `surat-tugas.terbitkan-global` (hubin), `surat-tugas.request-scoped` (kaprog, scoped), `surat-tugas.terbitkan-scoped` (hubin).
- Controller: `Kaprog/SuratTugasRequestController.php` (request scoped), `Hubin/SuratController.php` (terbitkan global & scoped — bisa reuse controller Surat dari M4, tambahkan action baru).
- Generasi PDF & penomoran ikuti pola yang sama seperti Surat Pengantar (M4 §6–§7).

Smoke test:
- [ ] Surat Tugas Scoped hanya berlaku untuk jurusan yang di-request — tidak "bocor" ke jurusan lain (verifikasi lewat gate di 2.4).

### 2.4 Laporan Kunjungan Monitoring (Guru Pembimbing)
- Tabel `laporan_kunjungan`, terikat ke `surat_tugas_monitoring`.
- **BR22**: kunjungan hanya sah dan dapat dicatat jika ada Surat Tugas Monitoring yang **diterbitkan Hubin** dan **berstatus aktif** — gate ini wajib dicek di backend (Policy `KunjunganPolicy`), bukan hanya UI.
- Gate detail (`TRD.md` §4.4): Surat Tugas belum terbit → tidak bisa isi; Surat Tugas terbit (Global **atau** Scoped, dan lingkupnya mencakup siswa ybs) → Laporan Kunjungan bisa diisi.
- Diisi **berkala/dicicil** — bukan sekali submit seperti Formulir Penilaian nanti di M6.
- **BR12**: Guru Pembimbing hanya bisa akses data siswa yang eksplisit di-assign kepadanya.
- Permission: `kunjungan.isi` (guru_pembimbing untuk siswa assigned; +kaprog via self-assign), `kunjungan.lihat` (guru_pembimbing, kaprog scoped).
- Controller: `GuruPembimbing/KunjunganController.php`.

Smoke test:
- [ ] Laporan Kunjungan tidak bisa diisi tanpa Surat Tugas Monitoring aktif (Global atau Scoped) untuk siswa/jurusan tsb.
- [ ] Guru Pembimbing bisa isi Laporan Kunjungan berkala (lebih dari satu entri per siswa, tidak dibatasi sekali submit).

### 2.5 Notifikasi Assignment & Kunjungan
Tambahkan trigger baru ke infra notifikasi M3:
- Guru Pembimbing di-assign → ke Siswa & Guru Pembimbing (sudah disebut di 2.1).
- Surat Tugas Monitoring terbit → ke Guru Pembimbing terkait.

## 3. Catatan Terkait BR30 (untuk Diketahui, Bukan Fitur M5)

**BR30**: Penggantian Guru Pembimbing (2.1) hanya untuk kasus **permanen** (resign/pindah tugas). Ketidakhadiran pembimbing di hari sidang penilaian (nanti relevan di M6) **tidak** memicu penggantian pembimbing lewat modul ini — itu diselesaikan lewat penjadwalan ulang sidang secara manual di luar sistem. Pastikan tidak membangun fitur "reschedule sidang" di M5.

## 4. Checklist Testing Akhir Sesi M5

- [ ] Hanya Kaprog jurusan terkait yang bisa assign Guru Pembimbing untuk siswanya.
- [ ] Kaprog self-assign muncul benar di "Bimbingan Saya" tanpa mengubah role Spatie-nya.
- [ ] Laporan Kunjungan tidak bisa diisi tanpa Surat Tugas Monitoring aktif (Global atau Scoped).
- [ ] Surat Tugas Scoped hanya berlaku untuk jurusan yang di-request, tidak bocor ke jurusan lain.

**Semua item di atas harus lulus sebelum M6 dimulai.**
