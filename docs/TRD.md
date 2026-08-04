# Technical Requirements Document (TRD)

## Sistem Informasi PKL (SI-PKL) — SMKN 1 Kota Bekasi

**Versi:** 1.3 (lengkap)
**Tanggal:** 27 Juli 2026
**Status:** Final — acuan implementasi per modul/fitur
**Dokumen terkait:** `PRD.md`, `docs/milestone.md`, `DESIGN.md`

---

## 1. Tech Stack

| Layer               | Teknologi                                                                                    |
| ------------------- | -------------------------------------------------------------------------------------------- |
| Backend             | Laravel 13                                                                                   |
| Bridge              | Inertia.js                                                                                   |
| Frontend            | React (TSX)                                                                                  |
| Styling             | Tailwind CSS v4                                                                              |
| Komponen UI         | shadcn/ui                                                                                    |
| Database            | MySQL (keputusan tim, migrasi dari rencana awal PostgreSQL)                                  |
| Otorisasi           | Spatie `laravel-permission`                                                                  |
| Ikon                | Lucide (khusus proyek ini — bukan Tabler)                                                    |
| Notifikasi          | Laravel Database Notification + Sonner (toast)                                               |
| Excel Import/Export | `maatwebsite/excel` (Laravel Excel)                                                          |
| PDF Generation      | Library PDF Laravel (mis. `barryvdh/laravel-dompdf` atau setara), render dari Blade template |

Font: heading **Plus Jakarta Sans**, body **Lato**. Warna primary **Royal Blue #4169E1**, accent **Bright Orange #FFA500**, background **Bright Snow #FAFAFA**. Detail lengkap di `DESIGN.md`.

## 2. Arsitektur Sistem

- Monolith Laravel + Inertia (server-driven routing, tanpa REST API terpisah untuk frontend utama).
- Controller dinamespace per role: `Siswa/`, `Kaprog/`, `GuruPembimbing/`, `Hubin/`.
- `Inertia::share` untuk data global (user, notifikasi, permission) **wajib** menggunakan closure `fn()` agar selalu fresh per-request.
- Ownership/data-scoping (mis. Kaprog hanya lihat siswa jurusannya) diimplementasikan lewat Laravel **Policy** (`$this->authorize(...)`), bukan `abort_unless` inline maupun percabangan berdasarkan role.
- Tidak ada `<form>` tag di React — submit lewat handler (`onClick`) yang memanggil Inertia `router.post/put/patch`.
- Seluruh kode dan komentar ditulis dalam Bahasa Indonesia.
- Navigasi: Siswa pakai pola mobile (Bottom Nav + Hamburger Sheet + FAB kontekstual — model banking/task-intent, bukan overflow ala media sosial); staf (Kaprog/Guru Pembimbing/Hubin) pakai sidebar desktop admin.

## 3. Data Model

### 3.1 Daftar Entitas

| Tabel                     | Keterangan                                                                                         |
| ------------------------- | -------------------------------------------------------------------------------------------------- |
| `users`                   | Akun login (siswa & staf)                                                                          |
| `guru_profiles`           | Profil staf; kolom `jabatan` menjadi sumber derivasi role via Observer                             |
| `siswa_profiles`          | Profil siswa, relasi ke `kompetensi_keahlian` (jurusan)                                            |
| `kompetensi_keahlian`     | Master jurusan (dipakai ulang sebagai referensi "Jurusan", bukan tabel baru)                       |
| `perusahaan`              | Master data perusahaan mitra PKL                                                                   |
| `periode_pkl`             | Master periode PKL                                                                                 |
| `pengajuan_pkl`           | Data pengajuan PKL siswa                                                                           |
| `pengajuan_pkl_histories` | Audit trail perubahan status pengajuan                                                             |
| `surat_pengantar`         | Surat Pengantar, bernomor                                                                          |
| `surat_balasan`           | Surat Balasan dari perusahaan (upload siswa)                                                       |
| `surat_tugas_monitoring`  | Surat Tugas Monitoring, bernomor, lingkup Global/Scoped                                            |
| `pembimbingan`            | Assignment Guru Pembimbing ↔ Siswa (termasuk histori penggantian saat guru diganti, BR30)          |
| `laporan_kunjungan`       | Laporan kunjungan monitoring, terikat `surat_tugas_monitoring`                                     |
| `laporan_akhir_pkl`       | Dokumen Laporan Akhir PKL yang diupload siswa                                                      |
| `formulir_penilaian`      | Formulir induk penilaian akhir PKL (1 per siswa per periode)                                       |
| `indikator_penilaian`     | Indikator penilaian; kategori Umum (`kompetensi_keahlian_id = NULL`) atau Teknis Kejuruan (scoped) |
| `section_penilaian`       | Sub-indikator, child dari `indikator_penilaian`, **nullable**                                      |
| `nilai`                   | Nilai per leaf indikator/section                                                                   |
| `notifications`           | Tabel bawaan Laravel DB Notification                                                               |

### 3.2 Skema Detail Tabel Inti

**`pengajuan_pkl`**

| Kolom                      | Tipe             | Keterangan                                                     |
| -------------------------- | ---------------- | -------------------------------------------------------------- |
| `id`                       | bigint PK        |                                                                |
| `siswa_id`                 | FK → users       |                                                                |
| `perusahaan_id`            | FK → perusahaan  |                                                                |
| `periode_pkl_id`           | FK → periode_pkl |                                                                |
| `status`                   | enum             | `diajukan`, `disetujui`, `ditolak`, `resmi_pkl`, `selesai_pkl` |
| `catatan_verifikasi`       | text nullable    | wajib diisi saat reject (BR6)                                  |
| `dokumen_pendukung`        | string nullable  | path file, lampiran opsional pengajuan (BR17)                  |
| `created_at`, `updated_at` | timestamp        |                                                                |

**`pengajuan_pkl_histories`**

| Kolom              | Tipe            | Keterangan |
| ------------------ | --------------- | ---------- |
| `id`               | bigint PK       |            |
| `pengajuan_pkl_id` | FK              |            |
| `status_lama`      | string nullable |            |
| `status_baru`      | string          |            |
| `aktor_id`         | FK → users      |            |
| `catatan`          | text nullable   |            |
| `created_at`       | timestamp       |            |

**`surat_pengantar`** / **`surat_tugas_monitoring`**

| Kolom                                                              | Tipe               | Keterangan                                      |
| ------------------------------------------------------------------ | ------------------ | ----------------------------------------------- |
| `id`                                                               | bigint PK          |                                                 |
| `nomor_surat`                                                      | string unique      | digenerate sequential per jenis+periode (BR18)  |
| `perusahaan_id` (khusus surat_pengantar)                           | FK                 | 1 surat pengantar dibundel per perusahaan (BR8) |
| `periode_pkl_id` (khusus surat_pengantar)                          | FK                 |                                                 |
| `lingkup` (khusus surat_tugas_monitoring)                          | enum               | `global`, `scoped`                              |
| `kompetensi_keahlian_id` (khusus surat_tugas_monitoring, nullable) | FK                 | null jika lingkup global                        |
| `diterbitkan_oleh`                                                 | FK → users (Hubin) |                                                 |
| `file_pdf`                                                         | string             | path arsip PDF                                  |
| `created_at`                                                       | timestamp          |                                                 |

**`surat_pengantar_pengajuan`** (pivot, khusus surat_pengantar — 1 surat memuat hingga 6 pengajuan/siswa dalam perusahaan+periode yang sama, BR3/BR8)

| Kolom                | Tipe      | Keterangan |
| -------------------- | --------- | ---------- |
| `id`                 | bigint PK |            |
| `surat_pengantar_id` | FK        |            |
| `pengajuan_pkl_id`   | FK        |            |

**`surat_balasan`** (1 baris per pengajuan, dihapus/diganti bila Hubin menolak pasca-fakta — BR7)

| Kolom                      | Tipe       | Keterangan       |
| -------------------------- | ---------- | ---------------- |
| `id`                       | bigint PK  |                  |
| `pengajuan_pkl_id`         | FK, unique |                  |
| `file_path`                | string     | kolom path biasa |
| `created_at`, `updated_at` | timestamp  |                  |

**`pembimbingan`**

| Kolom                      | Tipe                | Keterangan                   |
| -------------------------- | ------------------- | ---------------------------- |
| `id`                       | bigint PK           |                              |
| `siswa_id`                 | FK → users          |                              |
| `guru_pembimbing_id`       | FK → users          |                              |
| `assigned_by`              | FK → users (Kaprog) |                              |
| `status`                   | enum                | `aktif`, `diarsipkan` (BR30) |
| `is_self_assign`           | boolean             | true jika Kaprog self-assign |
| `created_at`, `updated_at` | timestamp           |                              |

**`indikator_penilaian`**

| Kolom                    | Tipe        | Keterangan                       |
| ------------------------ | ----------- | -------------------------------- |
| `id`                     | bigint PK   |                                  |
| `nama`                   | string      |                                  |
| `kategori`               | enum        | `umum`, `teknis_kejuruan` (BR25) |
| `kompetensi_keahlian_id` | FK nullable | NULL jika kategori umum          |
| `urutan`                 | integer     |                                  |

**`section_penilaian`**

| Kolom                    | Tipe      | Keterangan                                              |
| ------------------------ | --------- | ------------------------------------------------------- |
| `id`                     | bigint PK |                                                         |
| `indikator_penilaian_id` | FK        | nullable relasi — section opsional per indikator (BR25) |
| `nama`                   | string    |                                                         |
| `urutan`                 | integer   |                                                         |

**`formulir_penilaian`**

| Kolom                      | Tipe               | Keterangan                                       |
| -------------------------- | ------------------ | ------------------------------------------------ |
| `id`                       | bigint PK          |                                                  |
| `siswa_id`                 | FK → users         |                                                  |
| `periode_pkl_id`           | FK                 |                                                  |
| `guru_pembimbing_id`       | FK → users         |                                                  |
| `sakit`                    | integer            | jumlah hari                                      |
| `izin`                     | integer            | jumlah hari                                      |
| `alpa`                     | integer            | jumlah hari                                      |
| `catatan_guru_pembimbing`  | text nullable      |                                                  |
| `nama_pembimbing_industri` | string             |                                                  |
| `nilai_akhir`              | decimal nullable   | hasil kalkulasi BR28, diisi saat submit          |
| `submitted_at`             | timestamp nullable | menandai formulir sudah final (sekali isi, BR32) |

**`nilai`**

| Kolom                    | Tipe                      | Keterangan                             |
| ------------------------ | ------------------------- | -------------------------------------- |
| `id`                     | bigint PK                 |                                        |
| `formulir_penilaian_id`  | FK                        |                                        |
| `indikator_penilaian_id` | FK nullable               | diisi jika leaf di level indikator     |
| `section_penilaian_id`   | FK nullable               | diisi jika leaf di level section       |
| `nilai`                  | decimal                   | 0–100                                  |
| `predikat`               | enum (generated/computed) | K/C/B/SB, dihitung dari `nilai` (BR28) |

### 3.3 Relasi Kunci

- `guru_profiles.jabatan` → Observer → `model_has_roles` (Spatie), otomatis (tidak diset manual).
- `pengajuan_pkl` → banyak `pengajuan_pkl_histories` (audit trail tiap perubahan status).
- `formulir_penilaian` → banyak `nilai`, masing-masing menunjuk ke `indikator_penilaian` **atau** `section_penilaian` (mutually exclusive, sesuai leaf BR26).
- `pembimbingan` menyimpan histori assignment (bukan overwrite) agar BR30 (arsip assignment lama saat ganti Guru Pembimbing) terpenuhi.

### 3.4 Contoh Implementasi: Predikat & Nilai Akhir (BR28)

> **Catatan: kode di bawah ini hanya contoh ilustratif** untuk menjelaskan logika, bukan kode final yang wajib dipakai apa adanya — penamaan class/method final ditentukan saat implementasi dan boleh menyesuaikan konvensi tim.

Skala predikat bersifat **fixed/statis** (bukan tabel konfigurasi Hubin) — cukup sebagai konstanta/helper, contoh:

```php
// Contoh — app/Enums/PredikatPenilaian.php (ilustratif, bukan final)
match (true) {
    $skor >= 91 => 'SB',   // Sangat Baik
    $skor >= 81 => 'B',    // Baik
    $skor >= 70 => 'C',    // Cukup
    default     => 'K',    // Kurang
};
```

Nilai Akhir **tidak** dihitung `AVG(nilai)` langsung dari seluruh baris leaf (pendekatan ini membuat jurusan dengan leaf Teknis Kejuruan lebih banyak mendominasi skor). Dihitung 2 tahap sesuai BR28 — contoh ilustrasi logikanya:

```php
// Contoh — accessor pada model FormulirPenilaian (ilustratif, bukan final)
public function getNilaiAkhirAttribute(): ?float
{
    $rataPerKategori = $this->nilai
        ->loadMissing('indikatorPenilaian')
        ->groupBy(fn ($n) => $n->indikatorPenilaian->kategori) // 'umum' | 'teknis_kejuruan'
        ->map(fn ($grup) => $grup->avg('nilai'));

    if ($rataPerKategori->isEmpty()) {
        return null; // belum ada leaf terisi sama sekali
    }

    return round($rataPerKategori->avg(), 1);
}
```

Langkah kalkulasi (BR28):

1. Rata-rata dulu leaf yang terisi **per kategori indikator** (`umum` / `teknis_kejuruan`) — kategori yang tidak punya leaf terisi sama sekali dikecualikan dari tahap ini.
2. Rata-ratakan hasil tiap kategori tsb jadi Nilai Akhir, dibulatkan 1 desimal.

_Catatan penyesuaian: PRD_v1_2.md/TRD_v1_2.md mengelompokkan tahap 1 berdasarkan `kelompok_tujuan` (4 kelompok granular: No. 1, 2, 3, 4). Skema final di dokumen ini hanya punya kolom `kategori` (2 nilai: `umum`/`teknis_kejuruan`, Section 3.2) — jadi pengelompokan di atas disederhanakan ke level kategori. Tolong dikonfirmasi apakah penyederhanaan ini yang dimaksud, atau kolom `kelompok_tujuan` granular perlu ditambahkan kembali ke `indikator_penilaian`._

Dihitung on-the-fly (accessor), bukan dipersistensi sebagai kolom terpisah, supaya selalu konsisten kalau ada revisi nilai sebelum submit. Kolom `nilai_akhir` pada `formulir_penilaian` (Section 3.2) hanya menyimpan **snapshot hasil kalkulasi saat submit**, bukan sumber kebenaran yang dipakai sebelum submit.

Contoh constraint SQL untuk menjaga `nilai` tetap mutually exclusive di level leaf (BR26) — **ilustratif**, sintaks final tergantung dukungan versi MySQL yang dipakai:

```sql
-- Contoh (ilustratif) — pastikan tepat salah satu dari dua FK yang terisi
ALTER TABLE nilai
  ADD CONSTRAINT chk_nilai_leaf_exclusive
  CHECK (
    (indikator_penilaian_id IS NOT NULL AND section_penilaian_id IS NULL)
    OR
    (indikator_penilaian_id IS NULL AND section_penilaian_id IS NOT NULL)
  );
```

## 4. State Machine

### 4.1 Status Pengajuan PKL

```
diajukan → (Kaprog approve) disetujui → (Hubin terbit Surat Pengantar) → (siswa upload Surat Balasan, BR20)
        → (lolos validasi sistem: file valid & terbaca) → resmi_pkl (BR7) → ... → selesai_pkl
           (periode berakhir + laporan akhir + Formulir Penilaian submitted, BR13)
resmi_pkl → (Hubin tolak balasan pasca-fakta, `surat-balasan.verifikasi`, wajib catatan ≥15 karakter) → disetujui → (siswa upload ulang)
diajukan → (Kaprog reject, wajib catatan ≥15 karakter) ditolak → (siswa ajukan ulang, BR6) → diajukan
```

Setiap transisi dicatat di `pengajuan_pkl_histories` (BR17).

### 4.2 Gate Upload Surat Balasan (BR20)

```
Surat Pengantar belum terbit → tombol upload Surat Balasan disabled/hidden
Surat Pengantar terbit        → tombol upload Surat Balasan aktif
```

### 4.3 Gate Formulir Penilaian (BR32)

```
Laporan Akhir belum diupload siswa → Formulir Penilaian tidak dapat diakses Guru Pembimbing
Laporan Akhir sudah diupload       → Formulir Penilaian terbuka (submitted_at masih NULL, diisi sekali)
Formulir sudah submit (submitted_at terisi) → read-only, memicu kalkulasi Nilai Akhir (BR28) + auto selesai_pkl (BR13)
```

### 4.4 Kunjungan Monitoring (BR22, BR23)

```
Surat Tugas Monitoring belum terbit → Guru Pembimbing tidak bisa isi Laporan Kunjungan
Surat Tugas Monitoring terbit (Global atau Scoped, dan lingkup mencakup siswa ybs) → Laporan Kunjungan dapat diisi berkala
```

### 4.5 Assignment Guru Pembimbing (BR10, BR30)

```
Belum ada pembimbingan aktif → Kaprog assign (create pembimbingan.status=aktif)
Ada pembimbingan aktif → Kaprog ubah → pembimbingan lama.status=diarsipkan, pembimbingan baru.status=aktif
```

## 5. RBAC — Implementasi

- **Role**: `siswa`, `kaprog`, `guru_pembimbing`, `hubin` — model Spatie `HasRoles`.
- **Derivasi otomatis**: Observer pada `guru_profiles` (event `saving`/`saved`) membaca kolom `jabatan` dan men-sync role Spatie yang bersangkutan. Role **tidak pernah** diset manual lewat UI.
- **Kaprog non-aktif → Guru Pembimbing (BR27)**: perubahan `jabatan` (via aksi "Nonaktifkan sebagai Kaprog") memicu Observer yang sama — konsisten dengan mekanisme derivasi role di atas.
- **Kaprog self-assign sebagai pembimbing (BR10)**: Kaprog tetap satu role Spatie (`kaprog`), namun diberi permission tambahan `kunjungan.*` dan `penilaian.*` agar bisa mengakses fitur Laporan Kunjungan/Formulir Penilaian untuk siswa yang dia self-assign. Akses ini ditampilkan lewat section terpisah "Bimbingan Saya" di dashboard Kaprog — bukan lewat role gabungan.
- **Data scoping** (mis. Kaprog hanya melihat siswa jurusannya, Guru Pembimbing hanya siswanya) selalu lewat Policy (`PengajuanPolicy`, `PembimbinganPolicy`, `KunjunganPolicy`, `PenilaianPolicy`, dst.), bukan `if ($role === ...)`.

### 5.1 Daftar Permission Lengkap

| Permission                     | Role Pemilik Default                                                      |
| ------------------------------ | ------------------------------------------------------------------------- |
| `pengajuan.ajukan`             | siswa                                                                     |
| `pengajuan.lihat-sendiri`      | siswa                                                                     |
| `pengajuan.lihat`              | kaprog (scoped), hubin (semua)                                            |
| `pengajuan.setujui`            | kaprog (scoped)                                                           |
| `pengajuan.tolak`              | kaprog (scoped)                                                           |
| `surat.terbitkan`              | hubin                                                                     |
| `surat-balasan.upload`         | siswa                                                                     |
| `surat-balasan.verifikasi`     | hubin (tolak balasan pasca-fakta, bukan gate approval — lihat §4.1 & BR7) |
| `pembimbing.assign`            | kaprog (scoped)                                                           |
| `kunjungan.isi`                | guru_pembimbing; +kaprog via self-assign                                  |
| `kunjungan.lihat`              | guru_pembimbing, kaprog (scoped)                                          |
| `surat-tugas.terbitkan-global` | hubin                                                                     |
| `surat-tugas.request-scoped`   | kaprog (scoped)                                                           |
| `surat-tugas.terbitkan-scoped` | hubin                                                                     |
| `laporan-akhir.upload`         | siswa                                                                     |
| `penilaian.isi`                | guru_pembimbing; +kaprog via self-assign                                  |
| `penilaian.lihat`              | guru_pembimbing, kaprog (scoped), siswa (sendiri, read-only)              |
| `master-penilaian.kelola`      | hubin                                                                     |
| `master-data.kelola`           | hubin                                                                     |
| `akun.kelola`                  | hubin                                                                     |
| `persuratan.bundling`          | hubin                                                                     |
| `riwayat.lihat-sendiri`        | siswa                                                                     |
| `dashboard.global`             | hubin                                                                     |
| `dashboard.jurusan`            | kaprog                                                                    |

### 5.2 Studi Kasus Policy (Langkah demi Langkah)

Permission (Section 5.1) hanya gate kasar ("boleh melakukan jenis aksi ini sama sekali"). Policy adalah gate halus per-record, dicek lewat ownership. Dua contoh non-trivial:

**`KunjunganPolicy@create` (BR22) — validasi kelayakan isi Laporan Kunjungan:**

1. Ambil `siswa_id` dan `kompetensi_keahlian_id` dari `pembimbingan` yang direferensikan.
2. Ambil `periode_pkl_id` dari `pengajuan_pkl` terkait siswa tsb.
3. Cek: ada `surat_tugas_monitoring` WHERE periode cocok DAN (`lingkup = 'global'` ATAU `kompetensi_keahlian_id` = jurusan siswa tsb) DAN surat berstatus aktif/berlaku.
4. Kalau tidak ada surat yang cocok → deny (403) — jangan hanya andalkan disable tombol di frontend.
5. Kalau ada lebih dari satu surat yang cocok → pakai yang paling baru diterbitkan sebagai acuan.
6. Kalau lolos → izinkan create Laporan Kunjungan untuk `pembimbingan` tsb.

**`PenilaianPolicy@update` (isi/ubah nilai pada Formulir Penilaian, BR24/BR30/BR31/BR32) — precondition berlapis:**

1. Cek paling awal: `pengajuan_pkl` terkait sudah punya `laporan_akhir_pkl` ter-upload (BR32) — kalau belum, Formulir dianggap belum ter-generate → deny (403), dicek sebelum langkah 2–5 di bawah.
2. Ambil `pembimbingan` dari `formulir_penilaian` yang direferensikan.
3. Cek: `auth()->id() === pembimbingan.guru_pembimbing_id`.
4. Cek: `pembimbingan.status === 'aktif'` (BR30) — tolak akses Guru Pembimbing lama kalau sudah di-reassign.
5. Cek: `formulir_penilaian.submitted_at IS NULL` (BR31) — submitted bersifat final, tidak ada revisi setelah submit.
6. Kalau salah satu gagal → deny (403). Kalau semua lolos → izinkan create/update baris `nilai` untuk formulir tsb — indikator/section yang di-load tetap harus divalidasi scoped ke `kompetensi_keahlian_id` siswa di level Form Request, bukan cuma dipercaya dari frontend; validasi minimal 1 leaf terisi per kategori applicable (BR31) juga dilakukan di Form Request saat submit.

## 6. Penomoran Surat

Format penomoran surat mengikuti pola sekolah, disimpan sebagai kolom `nomor_surat` unik per entitas surat (`surat_pengantar`, `surat_tugas_monitoring`). Nomor digenerate saat surat diterbitkan (bukan saat draft), sequential per jenis surat per periode/tahun ajaran, untuk menghindari nomor bentrok saat concurrent create — digenerate dalam DB transaction dengan lock (`lockForUpdate`) pada counter per jenis+periode.

## 7. Generasi PDF

- Surat (Pengantar, Tugas Monitoring) dan dokumen Bundling dirender ke PDF di server.
- Alur: template Blade/HTML → render PDF → simpan ke storage (`storage/app/surat/...`) → link download/preview di UI.
- Fitur **Bundling & Cetak** (Hubin) menyediakan preview dokumen sebelum aksi **Cetak** dan **Kirim** dipisah menjadi dua tombol berbeda (preview tidak otomatis mengirim, BR5).

## 8. Notifikasi & Audit Trail

- **Audit trail**: dimulai dari `pengajuan_pkl_histories`, mencatat setiap perubahan status pengajuan (aktor, waktu, status lama → baru, BR17).
- **Notifikasi**: Laravel Database Notification sebagai storage, ditampilkan real-time dengan toast **Sonner** di UI untuk event penting:
    - Pengajuan disetujui/ditolak (ke Siswa)
    - Surat Pengantar terbit (ke Siswa)
    - Surat Balasan lolos validasi sistem → Resmi PKL (ke Siswa)
    - Surat Balasan ditolak Hubin pasca-fakta (BR7) → status kembali Disetujui, wajib upload ulang (ke Siswa)
    - Guru Pembimbing di-assign (ke Siswa & Guru Pembimbing)
    - Surat Tugas Monitoring terbit (ke Guru Pembimbing terkait)
    - Laporan Akhir diupload → Formulir Penilaian terbuka (ke Guru Pembimbing)
    - Formulir Penilaian submit → Selesai PKL (ke Siswa)
- Infra notifikasi dan audit trail dibangun sekali di M3, lalu trigger baru ditambahkan inkremental di tiap milestone berikutnya.

## 9. Seeder

Seeder disiapkan untuk data master minimum agar sistem bisa langsung diuji tiap milestone:

- Akun per role (siswa, kaprog, guru pembimbing, hubin) dengan `jabatan` sesuai agar Observer men-derive role dengan benar.
- Master `kompetensi_keahlian` (jurusan) sesuai jurusan riil sekolah.
- Contoh `perusahaan`, `periode_pkl`.
- Contoh `indikator_penilaian` (Umum & Teknis Kejuruan) + `section_penilaian` untuk minimal satu jurusan yang memang punya sub-indikator, dan minimal satu jurusan tanpa sub-indikator (untuk menguji resolusi leaf BR26 dan kalkulasi Nilai Akhir BR28 di dua skenario).

## 10. Struktur Folder Controller

```
app/Http/Controllers/
  Siswa/
    PengajuanController.php
    SuratBalasanController.php
    RiwayatController.php
    LaporanAkhirController.php
  Kaprog/
    PengajuanController.php
    PembimbinganController.php
    SuratTugasRequestController.php
    DashboardController.php
  GuruPembimbing/
    KunjunganController.php
    PenilaianController.php
  Hubin/
    MasterDataController.php
    IndikatorPenilaianController.php
    AkunController.php
    SuratController.php
    BundlingController.php
    DashboardController.php
app/Models/
app/Policies/
  PengajuanPolicy.php
  PembimbinganPolicy.php
  KunjunganPolicy.php
  PenilaianPolicy.php
app/Observers/
  GuruProfileObserver.php   # derivasi role dari jabatan
app/Notifications/
```

## 11. Konvensi Route

- Route digroup per role dengan middleware `role:` Spatie, prefix sesuai namespace controller (mis. `/kaprog/...`, `/hubin/...`).
- Nama route: `<role>.<fitur>.<aksi>` (mis. `kaprog.pengajuan.setujui`, `hubin.surat.terbitkan`).

## 12. Pertimbangan Non-Fungsional

- **Validasi**: Form Request class per aksi tulis, pesan validasi Bahasa Indonesia.
- **Error handling**: gunakan Inertia error bag standar; tidak ada custom JSON API error format karena bukan API terpisah.
- **Search & Filter Bar (BR21)**: dibangun sebagai komponen React reusable di M2, dipakai ulang di seluruh modul list/table sepanjang proyek.
- **Concurrency**: penomoran surat (BR18) dan kalkulasi Nilai Akhir (BR28) dibungkus DB transaction untuk menghindari race condition.
- **Testing**: setiap milestone menutup dengan smoke test checkpoint (lihat `docs/coding_plan.md`) sebelum lanjut milestone berikutnya.

## 13. Dependencies & Package List Lengkap

| Package                                                  | Kegunaan                               |
| -------------------------------------------------------- | -------------------------------------- |
| `laravel/framework` (v13)                                | Backend framework                      |
| `inertiajs/inertia-laravel`                              | Bridge Laravel ↔ React                 |
| `spatie/laravel-permission`                              | RBAC (role & permission)               |
| `maatwebsite/excel`                                      | Import/export Excel                    |
| PDF Laravel (mis. `barryvdh/laravel-dompdf` atau setara) | Generate PDF surat dari Blade template |
| `tailwindcss` v4                                         | Styling                                |
| `shadcn/ui`                                              | Komponen UI React                      |
| `lucide-react`                                           | Ikon                                   |
| `sonner`                                                 | Toast notifikasi in-app                |
| `mysql` (driver bawaan Laravel)                          | Database                               |

_Catatan: dibanding `TRD_v1_2.md`, package berikut sudah tidak dipakai sebagai keputusan tim final (dikonfirmasi ulang 30 Juli 2026): `spatie/laravel-model-states` (diganti kolom `enum` biasa + validasi di Policy/Form Request), `spatie/laravel-medialibrary` (upload file memakai kolom path biasa), dan `spatie/laravel-pdf` (diganti PDF Laravel generik, `barryvdh/laravel-dompdf` atau setara — dipilih karena layout surat cukup sederhana untuk CSS dompdf, dan menghindari dependency Chromium/headless-browser di server produksi). Database juga berpindah dari rencana awal PostgreSQL ke MySQL._

_Catatan migrasi: M3 (Pengajuan & Verifikasi Awal) sempat terlanjur diimplementasikan memakai `spatie/laravel-model-states` dan `spatie/laravel-medialibrary` sebelum keputusan final ini dikonfirmasi. Tim memutuskan untuk **rework M3** ke pola final (enum + kolom path) sebelum M4 dimulai, bukan mempertahankan package lama — supaya seluruh milestone konsisten memakai satu pola yang sama._

## 14. Import/Export Excel — Implementasi Teknis

- Package: `maatwebsite/excel` (Laravel Excel).
- 2 Import class terpisah: `ImportGuruExcel`, `ImportSiswaExcel` — masing-masing implement `ToModel`, `WithHeadingRow`, `WithValidation`, `SkipsOnError` (kolektor error per baris, tidak membatalkan seluruh proses — BR16).
- Password default (NIS/NIP) di-hash saat baris berhasil divalidasi (BR15).
- Export class: `ExportAkunExcel` (data existing), `ExportTemplateExcel` (template kosong, header saja), serta export data operasional (rekap pengajuan, rekap nilai) untuk kebutuhan pelaporan Hubin.
- Validasi saat import: NIS/NIP unik, `kompetensi_keahlian` harus match master data.
- Struktur kolom template mengikuti draft business requirement di `PRD.md` Section 9.

## 15. Dokumen Terkait

| Dokumen                       | Path                    | Kaitan                                             |
| ----------------------------- | ----------------------- | -------------------------------------------------- |
| Product Requirements Document | `docs/PRD.md`           | Business Rules (BR1–BR32), functional requirements |
| Coding Plan                   | `docs/coding_plan.md`   | Urutan implementasi per section TRD ini            |
| Design System                 | `docs/DESIGN.md`        | Warna, tipografi, komponen UI                      |
| PDM (Physical Data Model)     | `docs/diagrams/PDM.xml` | Diagram ERD/skema database — acuan Section 3       |
| Sitemap & Flow Diagram        | `docs/diagrams/`        | Alur navigasi & business flow per role             |
| Mockup Interaktif             | `docs/mockups/`         | Mockup HTML interaktif untuk 4 role                |
