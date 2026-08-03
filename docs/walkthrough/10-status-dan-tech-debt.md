# Walkthrough SI-PKL — 10. Status Milestone & Tech Debt

Dokumen penutup: di mana posisi proyek, dan catatan hal-hal yang perlu
diperbaiki/dilanjutkan.

---

## 10.1 Status Milestone

| Milestone | Status | Isi |
|---|---|---|
| **M1 — Foundation & Auth** | ✅ selesai | Fortify auth, `CheckFirstLogin`, layout, RBAC seeding, observer |
| **M2 — Master Data & User Mgmt** | ✅ selesai | CRUD perusahaan/periode/jurusan/indikator, kelola akun, Excel import/export |
| **M3 — Pengajuan & Verifikasi** | ✅ selesai | Pengajuan siswa, verifikasi Kaprog (approve/reject), audit trail, notifikasi, riwayat |
| **M4 — Persuratan** | ✅ selesai | Surat Pengantar (PDF, nomor sequential), Surat Balasan (BR20/BR7), tolak-balasan |
| **M5 — Pembimbingan & Kunjungan** | ❌ belum mulai | Tidak ada `pembimbingan`, `laporan_kunjungan`, `surat_tugas_monitoring` |
| **M6 — Penilaian & Selesai** | ❌ belum mulai | Tidak ada `formulir_penilaian`, `laporan_akhir_pkl`, `nilai` |
| **M7 — Deployment & UAT** | ❌ belum mulai | — |

Kondisi nyata: **repo ada di akhir M4**. Guru Pembimbing hanya punya dashboard
placeholder (`guru-pembimbing/dashboard.tsx`).

### Tabel yang sudah ada (migration)

`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`,
`job_batches`, `failed_jobs`, `permissions`, `roles`, `model_has_permissions`,
`model_has_roles`, `role_has_permissions`, `kompetensi_keahlian`, `guru_profiles`,
`siswa_profiles`, `perusahaans`, `periode_pkls`, `indikator_penilaians`,
`section_penilaians`, `pengajuan_pkl`, `pengajuan_pkl_histories`, `notifications`,
`surat_pengantar`, `surat_pengantar_pengajuan`, `surat_balasan`.

### Tabel yang BELUM ada (untuk M5–M6, sesuai TRD)

`surat_tugas_monitoring`, `pembimbingan`, `laporan_kunjungan`, `laporan_akhir_pkl`,
`formulir_penilaian`, `nilai`.

---

## 10.2 Tech Debt & Catatan

### Dokumentasi stale

| Masalah | Detail |
|---|---|
| `AGENTS.md` "M1, hanya scaffold" | Repo sebenarnya **akhir M4** |
| `AGENTS.md` "3 package belum terpasang" | `spatie/laravel-permission`, `barryvdh/laravel-dompdf`, `maatwebsite/excel` **sudah** di `composer.json` |

### Bug / risiko otorisasi

| Masalah | Lokasi | Dampak |
|---|---|---|
| `PengajuanPklPolicy::view` jalur Hubin butuh `pengajuan.lihat` — padahal role hubin tidak punya permission itu | `app/Policies/PengajuanPklPolicy.php:28` | Hubin kemungkinan **tidak bisa** membuka detail pengajuan (403) |
| Policy `uploadBalasan`/`tolakBalasan` diduplikasi di `SuratPengantarPolicy` | `SuratPengantarPolicy.php` | Dua sumber kebenaran; risiko inkonsistensi |

### Deviasi konvensi

| Masalah | Lokasi | Aturan yang dilanggar |
|---|---|---|
| `window.confirm()` untuk hapus/konfirmasi kritis | `hubin/perusahaan/index.tsx:65`, `siswa/pengajuan/index.tsx:49`, dll. | DESIGN.md wajib **AlertDialog** (`size=sm`) |
| URL string hardcoded (`router.post('/hubin/perusahaan')`) | `hubin/perusahaan/index.tsx:56-66`, dst. | Sebaiknya pakai Wayfinder (`@/routes`) |
| Badge status pakai kelas Tailwind manual | `siswa/pengajuan/index.tsx:26-32` | DESIGN.md punya token semantik `--color-{success,warning,danger,info,neutral}-*` |
| `siswa-layout.tsx` (mobile bottom-nav) **tidak diaktifkan** | `resources/js/layouts/siswa-layout.tsx` | Menunggu spec designer (komentar `ponytail:`) |
| Validasi inline `$request->validate()` (bukan Form Request) | semua controller master data | Konvensi Settings sudah Form Request; belum konsisten |
| Halaman `dashboard.tsx` & `siswa/kaprog/.../dashboard.tsx` masih placeholder | `pages/*/dashboard.tsx` | Belum ada konten dashboard |

### Kualitas kecil

| Masalah | Detail |
|---|---|
| `PengajuanPklHistoryFactory` **kosong** | Test yang butuh history harus buat manual |
| Prop `<any>` di banyak halaman | Tipe belum ketat (`pengajuan: any`, `histories: any`) |
| `welcome.tsx` masih starter Laravel | Landing publik belum dibuat khusus |
| Penomoran surat count-based | Aman dengan `lockForUpdate` di MySQL; kalau throughput naik, upgrade ke kolom counter per periode |
| `balasanMasuk()` controller ada tapi route tidak eksplisit | Data disuplai dari `preview()`; method ini seperti mati (dead code) |

---

## 10.3 Rekomendasi Lanjutan (urutan masuk akal)

1. **Perbaiki `PengajuanPklPolicy::view`** — pastikan Hubin bisa lihat detail
   (tambah `hasRole('hubin')` tanpa syarat permission, atau beri hubin permission
   `pengajuan.lihat`).
2. **Migrasi URL string → Wayfinder** di halaman hubin (perusahaan, periode, dst.).
3. **Ganti `confirm()` → AlertDialog** sesuai DESIGN.md.
4. **Perbarui `AGENTS.md`** — status M4 + daftar package terpasang.
5. **Isi `PengajuanPklHistoryFactory`** + hapus `balasanMasuk()` dead code.
6. Mulai **M5** mengikuti `docs/panduan/panduan-m5.md` & TRD (tabel
   `pembimbingan`, `surat_tugas_monitoring`, `laporan_kunjungan`).

---

## 10.4 Ringkasan Satu Halaman

```
SI-PKL — Sistem Informasi PKL SMKN 1 Kota Bekasi
Stack : Laravel 13 + Inertia v3 + React 19 + Tailwind v4 + shadcn/ui
Status: M1–M4 selesai (auth, master data, pengajuan/verifikasi, persuratan)
RBAC  : 4 role (siswa/kaprog/guru_pembimbing/hubin), Spatie, policy-based
Alur  : draft → diajukan → disetujui → resmi_pkl (→ selesai_pkl, M6)
BR kunci: BR1 satu pengajuan, BR4 approve di detail, BR6 audit trail,
         BR7 auto resmi, BR17 nomor surat sequential, BR20 gate balasan, BR31 demosi kaprog
Terbuka: M5–M6 (pembimbingan & penilaian), M7 (deploy), + tech debt §10.2
```
