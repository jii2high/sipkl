# Walkthrough SI-PKL — 08. Frontend Deep-Dive

Semua UI ditulis React + TypeScript + Tailwind v4 + shadcn/ui (new-york).
Dokumen ini membedah bagaimana frontend terstruktur dan bagaimana satu halaman
"berbicara" dengan backend.

---

## 8.1 Struktur `resources/js/`

```
resources/js/
├── app.tsx                  # Entry Inertia (lihat 01)
├── pages/                   # Komponen halaman (Inertia "pages")
│   ├── welcome.tsx          # Landing publik (stock starter)
│   ├── dashboard.tsx        # Dashboard generik (tidak dipakai)
│   ├── auth/*               # login, first-login, forgot/reset/confirm-password
│   ├── siswa/*              # dashboard, pengajuan, riwayat
│   ├── kaprog/*             # dashboard, verifikasi (index, show)
│   ├── guru-pembimbing/*    # dashboard (placeholder)
│   ├── hubin/*              # dashboard, perusahaan, periode-pkl,
│   │                        #   kompetensi-keahlian, indikator-penilaian,
│   │                        #   surat-pengantar (index, preview), kelola-akun
│   └── settings/*           # profile, security, appearance
├── components/              # Komponen UI aplikasi (bukan halaman)
│   ├── ui/                  # Primitif shadcn (31 komponen)
│   └── (app-logo, app-sidebar, nav-*, search-filter-bar, ...)
├── layouts/                 # Layout Inertia (lihat 01)
├── hooks/                   # use-appearance, use-flash-toast, use-mobile, dll.
├── lib/utils.ts             # cn() + toUrl() helper
├── types/                   # Tipe bersama (auth, navigation, ui, global)
├── routes/                  # Stub route Wayfinder (typed)
├── actions/                 # Stub controller action Wayfinder (typed)
└── wayfinder/index.ts       # Runtime Wayfinder (queryParams, helpers)
```

---

## 8.2 Wayfinder — route & action bertipe

Wayfinder (`@laravel/vite-plugin-wayfinder`) otomatis membuat **fungsi TypeScript**
dari route Laravel. Setiap kali route PHP berubah, dev server meregenerate
`resources/js/routes/` dan `resources/js/actions/`.

Dua bentuk import:

```tsx
// (a) Named route → fungsi URL
import { index as pengajuanIndex, store as pengajuanStore } from '@/routes/siswa/pengajuan';
post(pengajuanStore().url);                       // POST /siswa/pengajuan

// (b) Controller action → objek RouteDefinition + .url/.form/.post
import { submit as pengajuanSubmit } from '@/routes/siswa/pengajuan';
router.post(pengajuanSubmit({ pengajuan: pengajuan.id }).url);

// Route dengan parameter model binding:
import { store as suratBalasanStore } from '@/routes/siswa/surat-balasan';
suratBalasanStore({ pengajuan: pengajuan.id }).url;   // menerima id / object / array
```

Keuntungan: typo URL langsung ketahuan `tsc`, refactor route PHP otomatis ikut.

> **Catatan**: beberapa halaman masih memakai string URL hardcoded
> (`router.put(\`/hubin/perusahaan/${edit.id}\`)`) — tercatat di `10-status`.

---

## 8.3 Bedah Halaman: `siswa/pengajuan/index.tsx`

Halaman paling "kaya" dari sisi state UI — kondisi render bergantung status.

### Bagian 1 — Setup & import

```tsx
import { Head, useForm, router } from '@inertiajs/react';
import { index as pengajuanIndex, store as pengajuanStore, submit as pengajuanSubmit } from '@/routes/siswa/pengajuan';
import { store as suratBalasanStore, download as suratBalasanDownload } from '@/routes/siswa/surat-balasan';
```

`useForm` = hook Inertia untuk form (state + errors + post). `router` = navigasi
imperatif (untuk upload file & submit di luar `<form>`).

### Bagian 2 — Peta badge status

```tsx
const statusBadge: Record<string, string> = {
    draft:    'bg-muted text-muted-foreground',
    diajukan: 'bg-yellow-100 text-yellow-800',
    disetujui: 'bg-green-100 text-green-800',
    ditolak:  'bg-red-100 text-red-800',
    resmi_pkl: 'bg-blue-100 text-blue-800',
};
```

Pemetaan kelas Tailwind manual per status (bukan pakai token semantik
`--color-success-*` dari DESIGN.md).

### Bagian 3 — Tiga state utama

```tsx
// Form draft
{periodeAktif && !pengajuan && (
    // Select perusahaan + tombol "Simpan Draft"
)}
// Card pengajuan aktif
{pengajuan && (
    // badge status, info perusahaan/periode
    // tombol "Ajukan ke Kaprog"  (jika status draft/ditolak)
    // blok "Upload Surat Balasan" (jika disetujui + exists_surat_pengantar)  ← BR20
    // blok "Resmi PKL" + tombol Download (jika resmi_pkl)
    // catatan kaprog (jika ada)
    // timeline riwayat status
)}
```

Tiga blok kunci:

1. **Ajukan ke Kaprog** (baris 186–194):
   ```tsx
   {(pengajuan.status === 'draft' || pengajuan.status === 'ditolak') && (
       <Button onClick={handleSubmitToKaprog}>Ajukan ke Kaprog</Button>
   )}
   ```
   `handleSubmitToKaprog` memakai `confirm()` lalu `router.post(...)`.

2. **Upload Surat Balasan** (baris 196–228) — muncul **hanya** jika
   `status === 'disetujui' && pengajuan.exists_surat_pengantar`:
   ```tsx
   const formData = new FormData();
   formData.append('file', file);
   router.post(suratBalasanStore({ pengajuan: pengajuan.id }).url, formData, {
       preserveScroll: true,
       onFinish: () => { setUploading(false); ... },
   });
   ```
   Validasi klien-side duplikat (type PDF + ukuran ≤5MB) sebelum dikirim —
   sama dengan validasi server (sangat disarankan, jangan hapus).

3. **Status resmi + download** (baris 230–254):
   ```tsx
   <a href={suratBalasanDownload({ pengajuan: pengajuan.id }).url} ...>
   ```
   Link langsung ke route download PDF.

4. **Timeline riwayat** (baris 276–323):
   ```tsx
   {pengajuan.histories.map((h: any) => (
       <span>{h.status_dari ?? '-'} → {h.status_ke}</span>  // audit trail
       // nama actor + tanggal (toLocaleDateString('id-ID'))
       // catatan (hijau/kuning)
   ))}
   ```

### Bagian 4 — Layout deklaratif

```tsx
Pengajuan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: siswaDashboard() },
        { title: 'Pengajuan PKL', href: pengajuanIndex() },
    ],
};
```

Halaman bisa **mendeklarasikan layout sendiri** lewat properti statis `layout`.
`app.tsx` resolver memilih layout dari nama komponen; properti ini menyediakan
breadcrumb yang dirender `AppSidebarHeader`.

---

## 8.4 Bedah Halaman CRUD: `hubin/perusahaan/index.tsx`

Pola CRUD standar frontend:

- **Satu Dialog** untuk create & edit. State `edit` menentukan mode:
  ```tsx
  const [edit, setEdit] = useState<PerusahaanItem | null>(null);
  function openCreate() { setEdit(null); setForm(empty); setOpen(true); }
  function openEdit(p) { setEdit(p); setForm(p); setOpen(true); }
  function submit() {
      if (edit) router.put(`/hubin/perusahaan/${edit.id}`, form);
      else      router.post('/hubin/perusahaan', form);
      setOpen(false);
  }
  ```
- Tabel memakai `<table>` langsung (bukan komponen DataTable — belum ada).
- Hapus memakai `confirm()` (deviasi dari DESIGN.md, lihat `10-status`).
- Tipe props eksplisit:
  ```tsx
  interface Props { perusahaan: { data: PerusahaanItem[]; meta: any } }
  ```
  `meta` berisi pagination Inertia (links, current_page, total, ...).

---

## 8.5 Hooks & Komponen Penting

| Hook/Komponen | File | Fungsi |
|---|---|---|
| `useAppearance` | `hooks/use-appearance.tsx` | State tema (light/dark/system) via cookie+localStorage; `initializeTheme()` di `app.tsx` |
| `useFlashToast` | `hooks/use-flash-toast.ts` | Dengar event `flash` Inertia → tampilkan toast sonner (flash `sukses`/`error`) |
| `useMobile` | `hooks/use-mobile.tsx` | Deteksi breakpoint 768px (untuk sidebar responsif) |
| `useCurrentUrl` | `hooks/use-current-url.ts` | Cek URL aktif untuk highlight nav |
| `AlertError` | `components/alert-error.tsx` | Daftar error validasi global |
| `SearchFilterBar` | `components/search-filter-bar.tsx` | Input search + filter yang sinkron dengan query string |
| `PlaceholderPattern` | `components/ui/placeholder-pattern.tsx` | Skeleton pattern (custom) |
| `Sonner` | `components/ui/sonner.tsx` | Pembungkus `sonner` + toast flash |

---

## 8.6 Desain System — `resources/css/app.css`

Tailwind v4. Token warna didefinisikan sebagai **CSS variable** di `:root`
(light) dan `.dark` (dark mode, out of scope v1 tapi siap).

Warna kunci (DESIGN.md):

| Token | Nilai | Pemakaian |
|---|---|---|
| `--primary` | `#4169e1` (royal blue) | tombol utama, nav aktif, ring |
| `--accent` | `#ffa500` (orange) | CTA sekunder ("Cetak"), badge penting |
| `--background` | `#fafafa` | latar halaman |
| `--border` | `#e5e7eb` | batas kartu (elevasi TANPA box-shadow) |

Semantik status (badge) — text/bg pair:

```
--color-success-text/-bg   #166534 / #dcfce7   (hijau lembut)
--color-warning-text/-bg   #854d0e / #fef9c3   (kuning lembut)
--color-danger-text/-bg    #991b1b / #fee2e2   (merah lembut)
--color-info-text/-bg      #1e40af / #dbeafe   (biru lembut)
--color-neutral-text/-bg   #374151 / #f3f4f6   (abu lembut)
```

Font: `Lato` (body) + `Plus Jakarta Sans` (heading) via Google Fonts di
`app.blade.php`.

Aturan DESain penting:

- **TANPA `box-shadow`** — elevasi kartu hanya dari `--color-border` +
  `--color-surface` (terlihat di `bg-surface rounded-lg border` pada tabel
  perusahaan).
- **Ikon hanya `lucide-react`** — tidak boleh pakai Tabler.
- **Tidak ada tag `<form>` HTML** — semua lewat `useForm` / `router.*`.
- Navigasi: **Siswa = mobile-first** (bottom nav + FAB — layout `siswa-layout.tsx`
  sudah dibuat, belum diaktifkan); **staf = sidebar desktop**.

---

## 8.7 Komponen shadcn/ui yang Ada

31 primitif di `resources/js/components/ui/`: alert, avatar, badge, breadcrumb,
button, card, checkbox, collapsible, dialog, dropdown-menu, icon (custom Lucide),
input, label, navigation-menu, placeholder-pattern, select, separator, sheet,
sidebar, skeleton, sonner, spinner, table, textarea, toggle, toggle-group, tooltip.

**Belum ada**: `form`, `tabs`, `radio-group`, `switch`, `popover`, `command`,
`calendar`, `carousel`, `chart` — form-heavy page masih hand-roll
(`Label` + `Input` + `Select`).

---

## 8.8 Ringkasan: "Bagaimana Frontend dan Backend Bicara"

```
Halaman React                      Backend
─────────────────────────          ─────────────────────────
GET request via <Link> / router ─▶ route → controller → Inertia::render
                                    │ props = data
render dengan props  ◀─────────────┘ (serialisasi model otomatis)
   │
POST form (useForm) ─────────────▶ controller → validasi → DB
   │                                redirect back + flash('sukses')
props baru / toast ◀─────────────── (flash dibaca useFlashToast → toast)
```

Semua navigasi & submit adalah **request HTTP biasa** ke route Laravel;
Inertia menangani render-render berikutnya tanpa reload.
