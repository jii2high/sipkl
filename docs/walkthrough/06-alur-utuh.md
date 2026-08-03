# Walkthrough SI-PKL — 06. Alur Utuh (Satu Skenario Lengkap)

Dokumen ini menelusuri **satu pengajuan PKL dari awal sampai status Resmi PKL**,
lengkap dengan file:baris yang terlibat. Ini adalah "cerita" yang menghubungkan
semua modul 01–05 menjadi satu.

---

## 6.1 State Machine Status Pengajuan

```
                       siswa submit            kaprog approve           hubin terbit surat
  draft ───────────▶ diajukan ─────────────▶ disetujui ─────────────────────────────┐
     ▲                  │                        │  ▲                                │
     │                  │ kaprog reject (≥15)    │  │ hubin tolak-balasan (≥15)      │
     │                  ▼                        │  └────────────────────────────────┤
     │               ditolak ────────────────────┘                                   │
     │         (siswa ubah & submit ulang)                                           ▼
     └────────────────────────────────────────────────────────────────────     siswa upload balasan
                                                                                  status → resmi_pkl
                                                                                    (otomatis, BR7)
                                                                                          │
                                                                                          ▼
                                                                              (M5-M7: laporan + penilaian)
                                                                                     selesai_pkl (BR13, otomatis)
```

Transisi yang sah di kode saat ini (M1–M4):

| Dari | Ke | Pemicu | Di mana di-enforce |
|---|---|---|---|
| `draft` | `diajukan` | siswa submit | `PengajuanPklPolicy::submit` (04) |
| `ditolak` | `diajukan` | siswa submit ulang | `PengajuanPklPolicy::submit` |
| `diajukan` | `disetujui` | kaprog approve | `PengajuanPklPolicy::approve` (04) |
| `diajukan` | `ditolak` | kaprog reject + catatan ≥15 | `PengajuanPklPolicy::reject` |
| `disetujui` | `resmi_pkl` | siswa upload surat balasan | `SuratBalasanController@store` BR7 (05) |
| `resmi_pkl` | `disetujui` | hubin tolak-balasan + catatan ≥15 | `PengajuanPklPolicy::tolakBalasan` (05) |

Setiap transisi otomatis tercatat ke `pengajuan_pkl_histories` via
`PengajuanPklObserver` (BR6) — kecuali tolak-balasan yang menulis history manual.

---

## 6.2 Skenario: "Fajri PKL di PT Maju Jaya"

### Langkah 0 — Setup

- Hubin sudah meng-import akun Fajri (NIS → password awal, `must_change_password=true`).
- Hubin sudah membuat **Periode PKL aktif**, **Perusahaan** PT Maju Jaya,
  dan ada **Kaprog jurusan RPL** (Kompetensi Keahlian RPL).

### Langkah 1 — Login & ganti password pertama

```
POST /login
   └─ LoginResponse → redirect ke siswa.dashboard           (02: LoginResponse.php:11)
GET /siswa/dashboard
   └─ CheckFirstLogin → must_change_password=true → redirect ke password.force-change
POST /auth/force-change-password
   └─ FirstLoginController@store → password baru ≠ NIS, flag=false
```

### Langkah 2 — Buat pengajuan (draft)

```
GET /siswa/pengajuan
   └─ PengajuanController@index → periode aktif + pengajuan aktif (null, belum ada)
POST /siswa/pengajuan  { perusahaan_id }
   └─ PengajuanController@store
       ├─ authorize('create') → PengajuanPklPolicy::create → BR1: belum ada pengajuan aktif ✔
       ├─ PengajuanPkl::create(status='draft')
       └─ PengajuanPklObserver::created → history: - → draft (BR6)
```

### Langkah 3 — Submit ke Kaprog

```
POST /siswa/pengajuan/{id}/submit
   └─ PengajuanController@submit
       ├─ authorize('submit') → pemilik + status draft ✔
       ├─ status = 'diajukan'; save()
       │   └─ Observer::updated → history: draft → diajukan
       └─ User::role('kaprog') sejurusan RPL → notify PengajuanMasukAntrian
```

### Langkah 4 — Kaprog verifikasi

```
GET /kaprog/verifikasi
   └─ VerifikasiController@index → pengajuan 'diajukan' sejurusan RPL
GET /kaprog/verifikasi/{id}
   └─ VerifikasiController@show → authorize('view') → detail + histories
POST /kaprog/verifikasi/{id}/approve
   └─ VerifikasiController@approve
       ├─ authorize('approve') → kaprog sejurusan + status diajukan ✔ (BR4)
       ├─ kaprog_id = user.id; verified_kaprog_at = now(); status='disetujui'
       │   └─ Observer → history: diajukan → disetujui
       └─ notify PengajuanDiverifikasi('disetujui') ke Fajri
```

### Langkah 5 — Hubin bundling & terbit Surat Pengantar

```
GET /hubin/surat-pengantar
   └─ SuratPengantarController@index → perusahaan punya pengajuan 'disetujui' di periode aktif
GET /hubin/surat-pengantar/{ptmaju}/{periode}
   └─ preview → tab "Kirim Surat" (Fajri dsb.) + "Balasan Masuk"
GET /hubin/surat-pengantar/{...}/cetak
   └─ cetak → PDF preview tanpa record (nomor placeholder)
POST /hubin/surat-pengantar/{...}/kirim
   └─ kirim → DB::transaction
       ├─ PenomoranSurat::suratPengantar → "1/421.5/SMKN 1-Cadisdik.Wil.II/{romawi}/{tahun}" (BR17/BR18)
       ├─ SuratPengantar::create + attach pivot (Fajri ↔ surat)
       ├─ PDF dompdf → storage/app/private/surat-pengantar/...
       └─ notify SuratPengantarTerbit ke Fajri
```

### Langkah 6 — Siswa upload Surat Balasan → Resmi PKL

```
GET /siswa/pengajuan
   └─ PengajuanController@index
       └─ pengajuan.status='disetujui' & exists_surat_pengantar=true
          → frontend menampilkan blok upload (08: siswa/pengajuan/index.tsx:196)
POST /siswa/pengajuan/{id}/surat-balasan  (FormData: file PDF ≤5MB)
   └─ SuratBalasanController@store
       ├─ authorize('uploadBalasan') → BR20: disetujui + surat pengantar ada ✔
       ├─ abort_unless suratPengantars()->exists (lapis 2 BR20)
       ├─ validate PDF mimes:pdf, max 5120
       ├─ Storage::store('surat-balasan') + SuratBalasan::create
       ├─ status = 'resmi_pkl' (BR7 — otomatis, tanpa tombol manual)
       │   └─ Observer → history: disetujui → resmi_pkl
       └─ notify SuratBalasanDiupload
```

### Hasil akhir

- Fajri melihat badge **Resmi PKL** + tombol **Download Surat Balasan**
  (`siswa/pengajuan/index.tsx:230`).
- Hubin melihat Fajri masuk tab **"Balasan Masuk"** (`resmi_pkl`).
- Riwayat lengkap di `pengajuan_pkl_histories`:
  `- → draft → diajukan → disetujui → resmi_pkl` (4 baris).

---

## 6.3 Skenario Cabang: Ditolak

### Reject oleh Kaprog

```
POST /kaprog/verifikasi/{id}/reject  { catatan_kaprog: "..." }  (min 15)
   └─ VerifikasiController@reject
       ├─ authorize('reject')
       ├─ catatan_kaprog tersimpan; status='ditolak' (history tercatat)
       └─ notify PengajuanDiverifikasi('ditolak')
Siswa melihat catatan di halaman pengajuan (index.tsx:265)
Siswa submit ulang (status ditolak → diajukan) — policy submit mengizinkan 'ditolak'
```

### Tolak-balasan oleh Hubin (setelah resmi)

```
POST /hubin/surat-pengantar/{pengajuan}/tolak-balasan  { catatan ≥15 }
   └─ SuratPengantarController@tolakBalasan
       ├─ authorize('tolakBalasan') → hubin + resmi_pkl ✔
       ├─ DB::transaction: hapus SuratBalasan, status='disetujui',
       │                   history MANUAL: resmi_pkl → disetujui ('[Hubin] ...')
       └─ notify SuratBalasanDitolak
Siswa harus upload ulang balasan (BR20 gate lagi)
```

---

## 6.4 Alur Pembuatan Akun (Sisi Hubin)

Pendukung alur di atas — akun dibuat via import Excel, bukan register:

```
GET /hubin/kelola-akun/template-siswa → download template
Hubin isi Excel (nama, nis, email, kelas, kompetensi_keahlian, alamat, no_telp)
POST /hubin/kelola-akun/import-siswa
   └─ AkunController@importSiswa → SiswaImport
       ├─ tiap baris: validasi → User::create(password=NIS, must_change_password=true)
       │                 → assignRole('siswa') → SiswaProfile::create
       └─ error per baris → flash importErrors
```

---

## 6.5 Peta "Siapa Melakukan Apa" per Status

| Status | Yang bisa bertindak | Aksi |
|---|---|---|
| `draft` | Siswa | submit |
| `diajukan` | Kaprog sejurusan | approve / reject |
| `disetujui` | Hubin | bundling & terbit surat |
| `disetujui` + surat ada | Siswa | upload balasan (BR20) |
| `resmi_pkl` | Hubin | tolak-balasan (→ disetujui) |
| `ditolak` | Siswa | submit ulang (membuatnya `diajukan` lagi) |

> Setiap aksi otorisasi di-*enforce* dua lapis: middleware `permission:` (boleh
> lakukan kategori aksi ini?) + policy `$this->authorize()` (berhak atas objek
> ini / status ini?). Detail di `07-rbac-dan-policy`.
