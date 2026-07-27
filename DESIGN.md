# DESIGN.md — SI-PKL (Sistem Informasi PKL)

**SMK Negeri 1 Kota Bekasi**

Versi: **1.0**
Tanggal: 21 Juli 2026
Status: *Final — acuan tunggal UI sebelum M0 mulai; UI dibangun sesuai dokumen ini sejak M0, paralel sama backend tiap milestone (bukan overhaul di akhir).*

> Dokumen ini bagian dari set dokumentasi SI-PKL (PRD, TRD, DESIGN.md) — dijaga tetap sinkron. Referensi teknis lain (tech stack, RBAC) ada di TRD.

---

## Daftar Isi

1. Color Palette
2. Typography
3. Komponen shadcn/ui
4. Icon
5. Implementasi (Tailwind v4 Tokens)
6. Prinsip Umum

---

## 1. Color Palette

### 1.1 Brand

| Token | Nama | Hex | Kegunaan |
|---|---|---|---|
| `--color-primary` | Royal Blue | `#4169E1` | Tombol utama, link aktif, active nav item, focus ring, header/sidebar accent |
| `--color-accent` | Bright Orange | `#FFA500` | CTA sekunder/highlight non-status (mis. tombol "Cetak" vs "Kirim" biar beda tegas), badge penting non-semantik |
| `--color-background` | Bright Snow | `#FAFAFA` | Base background halaman (light mode) |

### 1.2 Semantic State Badge

Satu warna = satu makna, **tidak** di-hardcode ke nama state tertentu — supaya reusable buat badge apa pun (status `pengajuan_pkl`, `formulir`, `penugasan_pembimbing`, dll). Mapping state → semantic ada di tabel kedua di bawah, tapi tokennya sendiri generik.

| Semantic | Hex (text/bg) | Kegunaan Umum |
|---|---|---|
| **Success** | `#166534` / `#DCFCE7` | Selesai, disetujui, aktif, submitted |
| **Warning** | `#854D0E` / `#FEF9C3` | Menunggu tindakan lanjutan, reminder |
| **Danger** | `#991B1B` / `#FEE2E2` | Ditolak, nonaktif, error validasi |
| **Info** | `#1E40AF` / `#DBEAFE` | Sedang diproses/menunggu verifikasi |
| **Neutral** | `#374151` / `#F3F4F6` | Draft, belum ada data/belum ditentukan |

**Contoh mapping ke state existing** (referensi, bukan bagian dari token):

| Entity | State | Semantic |
|---|---|---|
| `pengajuan_pkl` | Draft | Neutral |
| `pengajuan_pkl` | DiajukanKaprog | Info |
| `pengajuan_pkl` | DitolakKaprog | Danger |
| `pengajuan_pkl` | SiapTerbit / SuratPengantarTerbit / ResmiPkl / SelesaiPkl | Success |
| `formulir` | Draft | Neutral |
| `formulir` | Submitted | Success |
| `penugasan_pembimbing` | Aktif | Success |
| `penugasan_pembimbing` | Nonaktif | Neutral |

Format badge: pill, `bg` soft + `text` warna pekat (kontras AA), **tanpa border tambahan dan tanpa shadow** — konsisten sama prinsip elevasi global (lihat Section 6).

### 1.3 Neutral Scale (teks, border, surface)

| Token | Hex | Kegunaan |
|---|---|---|
| `--color-fg` | `#111827` | Teks utama |
| `--color-fg-muted` | `#6B7280` | Teks sekunder/caption |
| `--color-border` | `#E5E7EB` | Border kartu, input, divider |
| `--color-surface` | `#FFFFFF` | Kartu/panel di atas background `#FAFAFA` |

### 1.4 Dark Mode

Token di atas disiapkan lewat CSS variable (bukan hardcoded class) sejak awal, supaya varian dark mode tinggal override value-nya tanpa refactor komponen — implementasi dark mode sendiri di luar cakupan v1, tapi struktur token sudah antisipatif.

---

## 2. Typography

| Role | Font | Weight yang Dipakai |
|---|---|---|
| Heading (`h1`–`h6`, judul kartu, judul modal) | **Plus Jakarta Sans** | 600 (Semibold) untuk h2–h6, 700 (Bold) untuk h1/hero |
| Body (paragraf, label, tabel, form) | **Lato** | 400 (Regular) default, 700 (Bold) untuk emphasis/label penting |

### Type Scale

| Level | Size / Line-height | Font | Weight |
|---|---|---|---|
| H1 | 32px / 40px | Plus Jakarta Sans | 700 |
| H2 | 24px / 32px | Plus Jakarta Sans | 600 |
| H3 | 20px / 28px | Plus Jakarta Sans | 600 |
| H4 (judul kartu/section) | 16px / 24px | Plus Jakarta Sans | 600 |
| Body | 14px / 20px | Lato | 400 |
| Body Emphasis | 14px / 20px | Lato | 700 |
| Caption/Meta | 12px / 16px | Lato | 400 |

---

## 3. Komponen shadcn/ui

Daftar komponen yang dipakai project ini beserta aturan pemakaian spesifik — di luar daftar ini, tidak menambah komponen shadcn baru tanpa diskusi tim.

| Komponen | Aturan Pemakaian |
|---|---|
| **Alert Dialog** (`size=sm`) | Wajib dipakai untuk **semua** aksi konfirmasi kritikal — approve/tolak pengajuan, cetak/kirim surat, nonaktifkan Kaprog, submit Formulir Penilaian. Tidak pakai `window.confirm()` native. |
| **Attachment** | Upload & preview file — Surat Balasan Perusahaan, Laporan Akhir PKL, dokumentasi kunjungan monitoring (multi-file). |
| **Avatar Dropdown** | Disederhanakan — **cuma 2 item**: "Settings" (1 halaman gabungan: ganti password + set foto profil, bukan tab-tab terpisah) dan "Log out". |
| **Button** | 6 varian: **Primary** (aksi utama per halaman, 1 per section), **Secondary** (aksi pendamping), **Outline** (aksi netral/cancel), **Ghost** (aksi ringan di dalam tabel/list, mis. "Lihat detail →"), **Destructive** (tolak/hapus/nonaktifkan), **Link** (navigasi inline). |
| **Checkbox** | Filter multi-pilih, bulk-select di Data Table (kalau ada aksi massal). |
| **Combobox** | Autocomplete perusahaan (data persisten lintas periode), dropdown assign Guru Pembimbing per baris. |
| **Data Table** | Semua halaman kelola data & rekap — terintegrasi dengan Search & Filter Bar global (BR21), sorting bawaan shadcn. |
| **Date Picker** | Input tanggal (waktu pelaksanaan PKL, tanggal kunjungan monitoring, tanggal penilaian). |
| **Skeleton** | Loading state — dipakai konsisten ganti spinner generik, terutama Data Table & dashboard cards. |
| **Sonner** | Toast notification untuk semua feedback aksi (berhasil/gagal), pasangan dari notifikasi in-app di database. |

---

## 4. Icon

**Lucide** (`lucide-react`) — dipakai eksklusif di project ini. *Catatan: beda dari project lain (SIME dkk) yang pakai Tabler Icons — khusus SI-PKL pakai Lucide.*

---

## 5. Implementasi (Tailwind v4 Tokens)

```css
/* resources/css/tokens.css — di-import sebelum tailwind base */
:root {
  /* Brand */
  --color-primary: #4169E1;
  --color-accent: #FFA500;
  --color-background: #FAFAFA;

  /* Semantic — text/bg pair */
  --color-success-text: #166534;
  --color-success-bg: #DCFCE7;
  --color-warning-text: #854D0E;
  --color-warning-bg: #FEF9C3;
  --color-danger-text: #991B1B;
  --color-danger-bg: #FEE2E2;
  --color-info-text: #1E40AF;
  --color-info-bg: #DBEAFE;
  --color-neutral-text: #374151;
  --color-neutral-bg: #F3F4F6;

  /* Neutral scale */
  --color-fg: #111827;
  --color-fg-muted: #6B7280;
  --color-border: #E5E7EB;
  --color-surface: #FFFFFF;

  /* Font */
  --font-heading: "Plus Jakarta Sans", sans-serif;
  --font-body: "Lato", sans-serif;
}
```

```js
// tailwind.config (v4 — @theme inline atau lewat CSS var reference)
// referensikan var di atas, jangan hardcode hex kedua kalinya di config —
// satu sumber kebenaran ada di tokens.css
```

Komponen `Badge` (semantic state) selalu ambil pasangan `--color-{semantic}-text` + `--color-{semantic}-bg`, tidak pernah hardcode hex per pemakaian.

---

## 6. Prinsip Umum

- **Tidak ada glow effect atau shadow dekoratif** — elevasi kartu/panel dari `--color-border` + `--color-surface` saja, konsisten sama konvensi desain lintas project.
- Navigasi: **Siswa** = mobile-first (bottom nav + hamburger sheet + FAB kontekstual); **staf** (Kaprog/Guru Pembimbing/Hubin) = sidebar desktop admin.
- Semua komponen shadcn di atas dipasang lewat CLI resmi (`npx shadcn add ...`), tidak ditulis manual dari nol.
- Style ditulis dengan Tailwind utility class langsung di komponen — tidak ada file CSS terpisah per komponen kecuali `tokens.css` di atas.
