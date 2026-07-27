# Technical Requirements Document (TRD)
## Sistem Informasi PKL (SI-PKL) — SMKN 1 Kota Bekasi

**Versi:** 1.3 (lengkap)
**Tanggal:** 27 Juli 2026
**Status:** Final — acuan implementasi per modul/fitur
**Dokumen terkait:** `PRD.md`, `docs/milestone.md`, `DESIGN.md`

---

## 1. Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13 |
| Bridge | Inertia.js |
| Frontend | React (TSX) |
| Styling | Tailwind CSS v4 |
| Komponen UI | shadcn/ui |
| Database | MySQL (keputusan tim, migrasi dari rencana awal PostgreSQL) |
| Otorisasi | Spatie `laravel-permission` |
| Ikon | Lucide (khusus proyek ini — bukan Tabler) |
| Notifikasi | Laravel Database Notification + Sonner (toast) |
| Excel Import/Export | `maatwebsite/excel` (Laravel Excel) |
| PDF Generation | Library PDF Laravel (mis. `barryvdh/laravel-dompdf` atau setara), render dari Blade template |

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

| Tabel | Keterangan |
|---|---|
| `users` | Akun login (siswa & staf) |
| `guru_profiles` | Profil staf; kolom `jabatan` menjadi sumber derivasi role via Observer |
| `siswa_profiles` | Profil siswa, relasi ke `kompetensi_keahlian` (jurusan) |
| `kompetensi_keahlian` | Master jurusan (dipakai ulang sebagai referensi "Jurusan", bukan tabel baru) |
| `perusahaan` | Master data perusahaan mitra PKL |
| `periode_pkl` | Master periode PKL |
| `pengajuan_pkl` | Data pengajuan PKL siswa |
| `pengajuan_pkl_histories` | Audit trail perubahan status pengajuan |
| `surat_pengantar` | Surat Pengantar, bernomor |
| `surat_balasan` | Surat Balasan dari perusahaan (upload siswa) |
| `surat_tugas_monitoring` | Surat Tugas Monitoring, bernomor, lingkup Global/Scoped |
| `pembimbingan` | Assignment Guru Pembimbing ↔ Siswa (termasuk histori penggantian, BR11) |
| `laporan_kunjungan` | Laporan kunjungan monitoring, terikat `surat_tugas_monitoring` |
| `laporan_akhir_pkl` | Dokumen Laporan Akhir PKL yang diupload siswa |
| `formulir_penilaian` | Formulir induk penilaian akhir PKL (1 per siswa per periode) |
| `indikator_penilaian` | Indikator penilaian; kategori Umum (`kompetensi_keahlian_id = NULL`) atau Teknis Kejuruan (scoped) |
| `section_penilaian` | Sub-indikator, child dari `indikator_penilaian`, **nullable** |
| `nilai` | Nilai per leaf indikator/section |
| `notifications` | Tabel bawaan Laravel DB Notification |

### 3.2 Skema Detail Tabel Inti

**`pengajuan_pkl`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `siswa_id` | FK → users | |
| `perusahaan_id` | FK → perusahaan | |
| `periode_pkl_id` | FK → periode_pkl | |
| `status` | enum | `diajukan`, `disetujui`, `ditolak`, `resmi_pkl`, `selesai_pkl` |
| `catatan_verifikasi` | text nullable | wajib diisi saat reject (BR6) |
| `dokumen_pendukung` | string nullable | path file (BR8) |
| `created_at`, `updated_at` | timestamp | |

**`pengajuan_pkl_histories`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `pengajuan_pkl_id` | FK | |
| `status_lama` | string nullable | |
| `status_baru` | string | |
| `aktor_id` | FK → users | |
| `catatan` | text nullable | |
| `created_at` | timestamp | |

**`surat_pengantar`** / **`surat_tugas_monitoring`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `nomor_surat` | string unique | digenerate sequential per jenis+periode (BR17) |
| `pengajuan_pkl_id` (khusus surat_pengantar) | FK | |
| `lingkup` (khusus surat_tugas_monitoring) | enum | `global`, `scoped` |
| `kompetensi_keahlian_id` (khusus surat_tugas_monitoring, nullable) | FK | null jika lingkup global |
| `diterbitkan_oleh` | FK → users (Hubin) | |
| `file_pdf` | string | path arsip PDF |
| `created_at` | timestamp | |

**`pembimbingan`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `siswa_id` | FK → users | |
| `guru_pembimbing_id` | FK → users | |
| `assigned_by` | FK → users (Kaprog) | |
| `status` | enum | `aktif`, `diarsipkan` (BR11) |
| `is_self_assign` | boolean | true jika Kaprog self-assign |
| `created_at`, `updated_at` | timestamp | |

**`indikator_penilaian`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `nama` | string | |
| `kategori` | enum | `umum`, `teknis_kejuruan` (BR24) |
| `kompetensi_keahlian_id` | FK nullable | NULL jika kategori umum |
| `urutan` | integer | |

**`section_penilaian`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `indikator_penilaian_id` | FK | nullable relasi — section opsional per indikator (BR26) |
| `nama` | string | |
| `urutan` | integer | |

**`formulir_penilaian`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `siswa_id` | FK → users | |
| `periode_pkl_id` | FK | |
| `guru_pembimbing_id` | FK → users | |
| `sakit` | integer | jumlah hari |
| `izin` | integer | jumlah hari |
| `alpa` | integer | jumlah hari |
| `catatan_guru_pembimbing` | text nullable | |
| `nama_pembimbing_industri` | string | |
| `nilai_akhir` | decimal nullable | hasil kalkulasi BR28, diisi saat submit |
| `submitted_at` | timestamp nullable | menandai formulir sudah final (sekali isi, BR32) |

**`nilai`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `formulir_penilaian_id` | FK | |
| `indikator_penilaian_id` | FK nullable | diisi jika leaf di level indikator |
| `section_penilaian_id` | FK nullable | diisi jika leaf di level section |
| `nilai` | decimal | 0–100 |
| `predikat` | enum (generated/computed) | K/C/B/SB, dihitung dari `nilai` (BR29) |

### 3.3 Relasi Kunci
- `guru_profiles.jabatan` → Observer → `model_has_roles` (Spatie), otomatis (tidak diset manual).
- `pengajuan_pkl` → banyak `pengajuan_pkl_histories` (audit trail tiap perubahan status).
- `formulir_penilaian` → banyak `nilai`, masing-masing menunjuk ke `indikator_penilaian` **atau** `section_penilaian` (mutually exclusive, sesuai leaf BR28).
- `pembimbingan` menyimpan histori assignment (bukan overwrite) agar BR11 (arsip assignment lama) terpenuhi.

## 4. State Machine

### 4.1 Status Pengajuan PKL
```
diajukan → (Kaprog approve) disetujui → (Hubin terbit Surat Pengantar) → (siswa upload Surat Balasan, BR20)
        → (Hubin verifikasi Surat Balasan) → resmi_pkl → ... → selesai_pkl (BR13)
diajukan → (Kaprog reject) ditolak → (siswa ajukan ulang, BR5) → diajukan
```
Setiap transisi dicatat di `pengajuan_pkl_histories` (BR6).

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

### 4.5 Assignment Guru Pembimbing (BR10, BR11)
```
Belum ada pembimbingan aktif → Kaprog assign (create pembimbingan.status=aktif)
Ada pembimbingan aktif → Kaprog ubah → pembimbingan lama.status=diarsipkan, pembimbingan baru.status=aktif
```

## 5. RBAC — Implementasi

- **Role**: `siswa`, `kaprog`, `guru_pembimbing`, `hubin` — model Spatie `HasRoles`.
- **Derivasi otomatis**: Observer pada `guru_profiles` (event `saving`/`saved`) membaca kolom `jabatan` dan men-sync role Spatie yang bersangkutan. Role **tidak pernah** diset manual lewat UI.
- **Kaprog non-aktif → Guru Pembimbing (BR31)**: perubahan `jabatan` (via aksi "Nonaktifkan sebagai Kaprog") memicu Observer yang sama — konsisten dengan mekanisme derivasi role di atas.
- **Kaprog self-assign sebagai pembimbing (BR10)**: Kaprog tetap satu role Spatie (`kaprog`), namun diberi permission tambahan `kunjungan.*` dan `penilaian.*` agar bisa mengakses fitur Laporan Kunjungan/Formulir Penilaian untuk siswa yang dia self-assign. Akses ini ditampilkan lewat section terpisah "Bimbingan Saya" di dashboard Kaprog — bukan lewat role gabungan.
- **Data scoping** (mis. Kaprog hanya melihat siswa jurusannya, Guru Pembimbing hanya siswanya) selalu lewat Policy (`PengajuanPolicy`, `PembimbinganPolicy`, `KunjunganPolicy`, `PenilaianPolicy`, dst.), bukan `if ($role === ...)`.

### 5.1 Daftar Permission Lengkap

| Permission | Role Pemilik Default |
|---|---|
| `pengajuan.ajukan` | siswa |
| `pengajuan.lihat-sendiri` | siswa |
| `pengajuan.lihat` | kaprog (scoped), hubin (semua) |
| `pengajuan.setujui` | kaprog (scoped) |
| `pengajuan.tolak` | kaprog (scoped) |
| `surat.terbitkan` | hubin |
| `surat-balasan.upload` | siswa |
| `surat-balasan.verifikasi` | hubin |
| `pembimbing.assign` | kaprog (scoped) |
| `kunjungan.isi` | guru_pembimbing; +kaprog via self-assign |
| `kunjungan.lihat` | guru_pembimbing, kaprog (scoped) |
| `surat-tugas.terbitkan-global` | hubin |
| `surat-tugas.request-scoped` | kaprog (scoped) |
| `surat-tugas.terbitkan-scoped` | hubin |
| `laporan-akhir.upload` | siswa |
| `penilaian.isi` | guru_pembimbing; +kaprog via self-assign |
| `penilaian.lihat` | guru_pembimbing, kaprog (scoped), siswa (sendiri, read-only) |
| `master-penilaian.kelola` | hubin |
| `master-data.kelola` | hubin |
| `akun.kelola` | hubin |
| `persuratan.bundling` | hubin |
| `riwayat.lihat-sendiri` | siswa |
| `dashboard.global` | hubin |
| `dashboard.jurusan` | kaprog |

## 6. Penomoran Surat

Format penomoran surat mengikuti pola sekolah, disimpan sebagai kolom `nomor_surat` unik per entitas surat (`surat_pengantar`, `surat_tugas_monitoring`). Nomor digenerate saat surat diterbitkan (bukan saat draft), sequential per jenis surat per periode/tahun ajaran, untuk menghindari nomor bentrok saat concurrent create — digenerate dalam DB transaction dengan lock (`lockForUpdate`) pada counter per jenis+periode.

## 7. Generasi PDF

- Surat (Pengantar, Tugas Monitoring) dan dokumen Bundling dirender ke PDF di server.
- Alur: template Blade/HTML → render PDF → simpan ke storage (`storage/app/surat/...`) → link download/preview di UI.
- Fitur **Bundling & Cetak** (Hubin) menyediakan preview dokumen sebelum aksi **Cetak** dan **Kirim** dipisah menjadi dua tombol berbeda (preview tidak otomatis mengirim, BR19).

## 8. Notifikasi & Audit Trail

- **Audit trail**: dimulai dari `pengajuan_pkl_histories`, mencatat setiap perubahan status pengajuan (aktor, waktu, status lama → baru, BR6).
- **Notifikasi**: Laravel Database Notification sebagai storage, ditampilkan real-time dengan toast **Sonner** di UI untuk event penting:
  - Pengajuan disetujui/ditolak (ke Siswa)
  - Surat Pengantar terbit (ke Siswa)
  - Surat Balasan diverifikasi → Resmi PKL (ke Siswa)
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
- Contoh `indikator_penilaian` (Umum & Teknis Kejuruan) + `section_penilaian` untuk minimal satu jurusan yang memang punya sub-indikator, dan minimal satu jurusan tanpa sub-indikator (untuk menguji BR28 di dua skenario).

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
- **Concurrency**: penomoran surat (BR17) dan kalkulasi Nilai Akhir (BR28) dibungkus DB transaction untuk menghindari race condition.
- **Testing**: setiap milestone menutup dengan smoke test checkpoint (lihat `docs/milestone.md`) sebelum lanjut milestone berikutnya.
