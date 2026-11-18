---
name: Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi
description: Sistem desain bersih, terstruktur, dan hemat aksen untuk alur PKL (pengajuan → 5-TTD → sidang), mendukung mode terang dan gelap.
colors:
  # ---- Mode terang ----
  bg: "#F8FAFC"
  surface: "#FFFFFF"
  surface-muted: "#F1F5F9"
  surface-raised: "#F1F5F9"
  border: "#E2E8F0"
  text: "#0F172A"
  text-muted: "#475569"
  primary: "#1E40AF"
  primary-hover: "#1E3A8A"
  primary-soft: "#DBEAFE"
  accent: "#06B6D4"
  accent-strong: "#0891B2"
  accent-soft: "#CFFAFE"
  on-accent: "#0F172A"
  focus-ring: "#06B6D4"
  success: "#166534"
  success-soft: "#DCFCE7"
  warning: "#92400E"
  warning-soft: "#FEF3C7"
  danger: "#991B1B"
  danger-soft: "#FEE2E2"
  # ---- Mode gelap ----
  bg-dark: "#0B1120"
  surface-dark: "#0F172A"
  surface-raised-dark: "#1E293B"
  border-dark: "#334155"
  text-dark: "#F1F5F9"
  text-muted-dark: "#94A3B8"
  primary-fill-dark: "#2563EB"
  primary-text-dark: "#93C5FD"
  primary-soft-dark: "#1E3A8A66"
  accent-dark: "#06B6D4"
  accent-strong-dark: "#22D3EE"
  accent-soft-dark: "#06B6D426"
  success-dark: "#4ADE80"
  success-soft-dark: "#4ADE8026"
  warning-dark: "#FBBF24"
  warning-soft-dark: "#FBBF2426"
  danger-dark: "#F87171"
  danger-soft-dark: "#F8717126"
typography:
  display:
    fontFamily: "Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
    fontSize: "2rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0em"
  body:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "0em"
  label:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: "0.01em"
  mono:
    fontFamily: "'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: 1.5
    letterSpacing: "0em"
rounded:
  sm: "6px"
  md: "8px"
  lg: "12px"
  full: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
    height: "44px"
  button-primary-hover:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.text}"
  button-primary-dark:
    backgroundColor: "{colors.primary-fill-dark}"
    textColor: "{colors.surface}"
  button-primary-dark-hover:
    backgroundColor: "{colors.accent-dark}"
    textColor: "{colors.bg-dark}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
    height: "44px"
  button-danger:
    backgroundColor: "{colors.danger-soft}"
    textColor: "{colors.danger}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
    height: "44px"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.lg}"
    padding: "20px"
  card-dark:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.text-dark}"
    rounded: "{rounded.lg}"
    padding: "20px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "10px 12px"
    height: "44px"
  input-dark:
    backgroundColor: "{colors.surface-raised-dark}"
    textColor: "{colors.text-dark}"
    rounded: "{rounded.md}"
    padding: "10px 12px"
    height: "44px"
  badge-notification:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    size: "20px"
  chip-status-success:
    backgroundColor: "{colors.success-soft}"
    textColor: "{colors.success}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: "2px 10px"
  chip-status-warning:
    backgroundColor: "{colors.warning-soft}"
    textColor: "{colors.warning}"
    rounded: "{rounded.full}"
    padding: "2px 10px"
  chip-status-danger:
    backgroundColor: "{colors.danger-soft}"
    textColor: "{colors.danger}"
    rounded: "{rounded.full}"
    padding: "2px 10px"
  chip-status-info:
    backgroundColor: "{colors.primary-soft}"
    textColor: "{colors.primary}"
    rounded: "{rounded.full}"
    padding: "2px 10px"
  nav-item-active:
    backgroundColor: "{colors.primary-soft}"
    textColor: "{colors.primary}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    height: "44px"
---

## Overview

**Creative North Star: "Kertas Bersih, Alur Jelas."**

Aplikasi ini menggantikan administrasi PKL berbasis kertas. Antarmukanya harus terasa seperti map arsip yang rapi: lapang, terbaca, dan selalu memberi tahu *di mana posisi saya sekarang dan apa langkah berikutnya*. Ini adalah antarmuka **Operate** (pengguna menyelesaikan tugas), jadi keterbacaan, konsistensi, dan kecepatan memindai mengalahkan ekspresi visual. Identitas brand hadir lewat detail presisi (warna, ritme, status), bukan dekorasi.

**Key Characteristics:**
- **Aturan 60-30-10**: 60% off-white/white (ruang napas), 30% royal blue + deep navy (struktur dan hierarki), 10% turquoise (perhatian dan interaksi).
- **Status adalah bahasa utama.** Hampir setiap layar menjawab "status apa, di step mana, siapa berikutnya". Status selalu berupa chip (warna + teks), tidak pernah warna saja.
- **Mobile-first untuk Siswa dan Kepsek**, desktop-first untuk panel Hubin. Semua layar valid di lebar 360px.
- **Dua tema setara.** Mode terang dan gelap sama-sama kelas satu, bukan sekadar inversi.
- **Netral untuk pengguna eksternal.** Portal penguji dan pembimbing industri (magic link) memakai layout minimal satu-tugas tanpa navigasi.

Implementasi mengikuti stack PRD: Filament multi-panel + Livewire untuk panel `hubin` dan `app`, serta Blade + Livewire ringan untuk landing, FAQ, login/OTP, `/verify/{token}`, dan `/eksternal/*`. Semua token di bawah dipetakan ke CSS variables sehingga Filament dan halaman Blade berbagi satu sumber warna.

## Colors

Frontmatter adalah sumber nilai yang normatif. Bagian ini menjelaskan *peran* tiap warna.

### Dominan, 60%: Off-White & White
- **Background** (`#F8FAFC`) untuk kanvas aplikasi, area di antara card, dan latar halaman publik.
- **Surface** (`#FFFFFF`) untuk card, panel, table body, modal, sidebar minimalis, dan dropdown.
- **Surface muted** (`#F1F5F9`) untuk header tabel, baris zebra tipis, dan area input yang di-disable.
- **Border** (`#E2E8F0`) untuk garis pembatas halus.

### Struktural, 30%: Royal Blue & Deep Navy
- **Royal Blue** (`#1E40AF`) untuk header utama, ikon navigasi, link, tombol CTA pada kondisi diam, dan item nav aktif. Putih di atas biru ini lolos kontras AA dengan lega.
- **Deep Navy** (`#0F172A`) untuk judul, teks isi, dan ikon utama.
- **Text muted** (`#475569`) untuk keterangan, placeholder, dan metadata. Tetap ≥ 4.5:1 di atas putih.
- **Primary soft** (`#DBEAFE`) untuk latar item nav aktif, chip informasi, dan baris terpilih.

### Aksen, 10%: Turquoise
- **Turquoise** (`#06B6D4`) hanya untuk: tombol utama saat **hover/active**, progress bar, badge angka notifikasi, toggle yang menyala, cincin fokus, dan penanda fitur baru.
- **Accent strong** (`#0891B2`) untuk varian yang butuh lebih tegas (garis progress di atas surface putih, ikon aktif kecil).
- **Accent soft** (`#CFFAFE`) untuk highlight latar yang sangat ringan.

**The Turquoise Budget Rule.** Satu layar boleh punya sedikit titik turquoise. Jika aksen mulai terlihat di lebih dari sekitar 10% area, ada yang salah: kembalikan elemen itu ke biru atau netral.

**The Turquoise Contrast Rule.** Turquoise di atas putih hanya sekitar 2.4:1, jadi **jangan pernah memakainya sebagai warna teks atau ikon kecil di mode terang**. Teks di atas isian turquoise selalu Deep Navy (`#0F172A`), tidak pernah putih.

### Semantik (status alur PKL)
Warna semantik dipakai hemat dan selalu berpasangan dengan label teks.

| Makna | Terang (teks / latar) | Gelap (teks / latar) | Contoh status |
|---|---|---|---|
| Sukses | `#166534` / `#DCFCE7` | `#4ADE80` / `#4ADE8026` | `signed`, `sedang_pkl`, `completed` |
| Peringatan | `#92400E` / `#FEF3C7` | `#FBBF24` / `#FBBF2426` | `provisional`, `pending`, kuota hampir penuh |
| Bahaya | `#991B1B` / `#FEE2E2` | `#F87171` / `#F8717126` | `rejected`, `ditolak_*`, `failed`, kedaluwarsa |
| Info | `#1E40AF` / `#DBEAFE` | `#93C5FD` / `#1E3A8A66` | `menunggu_*`, `draft`, informasi netral |

### Mode Gelap
Mode gelap memakai **Deep Navy sebagai surface**, bukan hitam murni, supaya identitas brand tetap terasa.

| Peran | Terang | Gelap |
|---|---|---|
| Background | `#F8FAFC` | `#0B1120` |
| Surface (card, tabel, modal) | `#FFFFFF` | `#0F172A` |
| Surface raised (dropdown, input, hover baris) | `#F1F5F9` | `#1E293B` |
| Border | `#E2E8F0` | `#334155` |
| Teks utama | `#0F172A` | `#F1F5F9` |
| Teks muted | `#475569` | `#94A3B8` |
| Link / ikon biru | `#1E40AF` | `#93C5FD` |
| Isian tombol utama | `#1E40AF` | `#2563EB` |
| Aksen | `#06B6D4` | `#06B6D4` |

**The Dark Blue Rule.** `#1E40AF` terlalu gelap untuk teks dan ikon di atas surface gelap. Di mode gelap, biru sebagai *teks/ikon* memakai `#93C5FD`, dan biru sebagai *isian tombol* memakai `#2563EB` dengan teks putih.

**The Turquoise Bonus Rule.** Turquoise justru kontras tinggi di atas surface gelap, jadi di mode gelap boleh dipakai sebagai warna teks/ikon aksen kecil, tetap dalam anggaran 10%.

### Implementasi token (CSS variables)

```css
:root {
  --bg: #F8FAFC;        --surface: #FFFFFF;     --surface-muted: #F1F5F9;
  --surface-raised: #F1F5F9;
  --border: #E2E8F0;    --text: #0F172A;        --text-muted: #475569;
  --primary: #1E40AF;   --primary-soft: #DBEAFE;
  --accent: #06B6D4;    --accent-strong: #0891B2;
  --on-accent: #0F172A;
  --focus-ring: #06B6D4;
}
:root.dark,
:root[data-theme="dark"] {
  --bg: #0B1120;        --surface: #0F172A;     --surface-muted: #1E293B;
  --border: #334155;    --text: #F1F5F9;        --text-muted: #94A3B8;
  --primary: #2563EB;   --primary-soft: #1E3A8A66;
  --accent: #06B6D4;    --accent-strong: #22D3EE;
  --on-accent: #0B1120;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]):not(.light) { /* nilai sama dengan blok gelap di atas */ }
}
```

### Perilaku tema
- Tiga pilihan: **Terang / Gelap / Ikuti sistem**. Default = ikuti sistem.
- Pilihan disimpan per akun dan tidak terpengaruh pergantian `active_role`.
- Halaman publik dan portal eksternal (tanpa akun) mengikuti `prefers-color-scheme` dan boleh menyimpan pilihan di perangkat.
- Tidak ada flash tema salah saat load: terapkan class/atribut tema sebelum render pertama.

## Typography

Satu keluarga sans-serif (Inter, fallback sistem) untuk seluruh peran. Bahasa Indonesia berteks panjang (catatan penolakan, alasan withdrawal) membutuhkan line-height lega.

| Peran | Ukuran | Berat | Dipakai untuk |
|---|---|---|---|
| Display (2rem) | 700 | Judul landing, hero halaman publik |
| Headline (1.5rem) | 600 | Judul halaman panel |
| Title (1.125rem) | 600 | Judul card, section, modal |
| Body (0.9375rem) | 400 | Teks isi, sel tabel, formulir |
| Label (0.8125rem) | 500 | Label field, tombol, chip, header tabel |
| Mono (0.8125rem) | 500 | Nomor surat, PIN, OTP, ID dokumen |

**The Mono For Identifiers Rule.** Nomor surat, kode OTP, PIN, dan token verifikasi selalu memakai font mono agar karakter mirip (0/O, 1/l) tidak ambigu dan mudah disalin.

**The 16px Input Rule.** Ukuran teks input di ponsel minimal 16px agar iOS tidak memperbesar halaman saat fokus. Jika skala body 15px dipakai pada input mobile, naikkan ke 16px khusus field.

Panjang baris teks naratif dibatasi ±70 karakter. Angka di tabel memakai `font-variant-numeric: tabular-nums`.

## Layout

- **Grid dasar 4px**, skala ruang: 4 / 8 / 16 / 24 / 32.
- **Breakpoint**: 360px (minimum yang wajib valid), 640px, 1024px (sidebar tetap muncul), 1280px.
- **Panel `hubin` (desktop)**: sidebar kiri 256px (bisa diciutkan ke ikon), konten maks ±1280px, tabel padat dengan filter di atas dan bulk action di bar melayang.
- **Panel `app` (mobile-first)**: bar atas + navigasi bawah/drawer; konten satu kolom. Dropdown **pemilih peran** (`active_role`) berada di header, selalu terlihat, dan menampilkan peran aktif sebagai teks, bukan hanya ikon.
- **Tabel di ponsel** berubah menjadi daftar card (judul + chip status + 2–3 metadata + aksi), bukan tabel yang digulir ke samping, kecuali data benar-benar tabular (antrean sidang).
- **Target sentuh minimal 44×44px**, jarak antar target ≥ 8px. Penting untuk bulk TTD Kepsek di ponsel.
- **Portal eksternal (`/eksternal/*`)**: satu kolom, lebar maks 640px, tanpa sidebar, satu tugas per layar, tombol utama selalu terlihat di bawah.
- **Halaman publik** (landing, FAQ, verifikasi QR): konten tengah lebar maks 960px, hero ringkas, FAQ dengan filter kategori dan 7–10 item per halaman.

**The Whitespace First Rule.** Pemisah antar kelompok memakai ruang kosong (24–32px) lebih dulu, baru garis. Garis border hanya untuk memisahkan baris data dan container.

**The One Primary Action Rule.** Satu layar/modal punya paling banyak satu tombol utama. Aksi sekunder memakai tombol outline atau teks.

## Elevation & Depth

Sistem ini **datar dan tonal**. Kedalaman dibuat lewat perbedaan surface dan border, bukan bayangan besar.

- **Mode terang**: card = surface putih + border 1px `#E2E8F0` + bayangan sangat halus (`0 1px 2px rgba(15,23,42,0.06)`). Dropdown dan modal memakai `0 8px 24px rgba(15,23,42,0.12)`.
- **Mode gelap**: **tanpa bayangan**. Lapisan dibedakan oleh surface (`#0B1120` → `#0F172A` → `#1E293B`) dan border `#334155`. Modal diberi overlay gelap 60%.
- **Overlay modal** menggunakan Deep Navy semi-transparan, bukan hitam murni, di kedua mode.

**The Tonal Layering Rule.** Elemen yang "lebih tinggi" selalu lebih terang di mode gelap dan lebih putih/berborder di mode terang. Jangan menumpuk lebih dari tiga tingkat surface.

## Shapes

- Sudut lembut dan konsisten: **6px** (chip kecil, checkbox), **8px** (tombol, input, item nav), **12px** (card, modal), **pill** (chip status, badge).
- Ikon bergaya garis (stroke 1.5–2px), satu keluarga ikon (Heroicons, bawaan Filament) agar konsisten.
- Tidak memakai bentuk dekoratif, gradien, atau ilustrasi berat pada layar operasional. Ilustrasi ringan hanya untuk empty state dan landing.

## Components

### Tombol
- **Primary**: isian Royal Blue, teks putih, tinggi 44px, radius 8px. **Hover/active/pressed berubah menjadi Turquoise dengan teks Deep Navy.** Mode gelap: isian `#2563EB`, hover tetap Turquoise dengan teks `#0B1120`.
- **Secondary**: latar surface, border biru, teks biru (mode gelap: teks `#93C5FD`).
- **Danger/Tolak**: latar `danger-soft`, teks `danger`; aksi destruktif membuka konfirmasi dengan kolom catatan (≥ 10 karakter untuk penolakan sesuai PRD).
- **Disabled**: opasitas 50%, kursor `not-allowed`, disertai alasan singkat bila bukan jelas (mis. "Menunggu ≥ 4 bimbingan"). Disabled hanyalah petunjuk, otorisasi tetap di server.
- **Loading**: spinner di dalam tombol, lebar tetap, mencegah klik ganda (selaras aturan idempoten).

### Input & Formulir
- Tinggi 44px, border `#E2E8F0`, fokus: cincin Turquoise 2px dengan offset 2px.
- Label selalu di atas field. Pesan galat di bawah field, warna `danger`, spesifik (mis. "Password baru tidak boleh sama dengan NIS").
- Field OTP: 6 kotak mono, auto-advance, mendukung paste dan `autocomplete="one-time-code"`. Sertakan penghitung mundur masa berlaku dan tombol kirim ulang dengan status cooldown.
- Field PIN penguji: 4 kotak mono, tampilan mask opsional.
- Autocomplete perusahaan: daftar hasil dengan nama + kota; kemiripan nama memunculkan **modal konfirmasi** sebelum simpan. Kontak CP yang di-mask ditampilkan sebagai `••••` sampai disetujui Kaprog.
- Toggle: biru/netral saat mati, **Turquoise saat menyala**.

### Card
Surface putih/navy, radius 12px, padding 20px (16px di ponsel). Header card memakai Title dan aksi opsional di kanan. Tidak ada card di dalam card; gunakan pemisah tipis.

### Tabel & Daftar
- Header tabel di surface-muted, teks Label, huruf kecil biasa (tanpa all-caps).
- Baris 48–56px, hover memakai surface-muted, baris terpilih memakai primary-soft.
- Kolom status selalu chip. Kolom aksi memakai menu "⋯" di ponsel.
- Bulk action: bar melayang di bawah dengan jumlah terpilih, tombol primary "Setujui (n)" dan hasil per item ditampilkan setelah proses (ingat: tiap laporan diproses independen).

### Chip Status
Pill dengan titik/ikon kecil + teks. Pemetaan:

| Status | Chip |
|---|---|
| `draft`, `menunggu_*` | Info (biru) |
| `pending` (step TTD aktif), `provisional` | Peringatan |
| `signed`, `sedang_pkl`, `surat_pengantar_terbit`, `completed` | Sukses |
| `ditolak_*`, `rejected`, `failed`, `kedaluwarsa` | Bahaya |
| `withdrawn`, `bubar`, `archived` | Netral (surface-muted + teks muted) |

**The Never Color Alone Rule.** Status tidak boleh hanya dibedakan oleh warna. Selalu sertakan teks (dan ikon bila ruang cukup).

### Stepper 5-TTD (komponen khas)
Stepper horizontal di desktop dan vertikal di ponsel: **Guru Pembimbing → Kaprog → Pembimbing Industri → Kepala Industri → Kepsek**.
- Step selesai: lingkaran biru terisi + centang, garis penghubung biru.
- Step aktif: cincin **Turquoise**, label tebal, keterangan "Menunggu [peran]" + lama menunggu.
- Step belum: lingkaran outline netral.
- Step menolak: lingkaran `danger` + ikon silang; catatan penolakan terbuka di bawahnya. Step sebelumnya tetap tampil selesai (partial-reject).
- Step eksternal menampilkan status link (diterbitkan / kedaluwarsa / dicabut) dan tombol "Terbitkan ulang" untuk Kaprog/Hubin.

### Progress & Notifikasi
- Progress bar: trek surface-muted, isian **Turquoise** (`accent-strong` di mode terang bila tampil di atas putih), tinggi 8px, radius pill. Dipakai untuk interaksi bimbingan (mis. 3/4), kuota perusahaan (mis. 5/6), dan ekspor akreditasi.
- Kuota: ≤ 4 netral, 5 peringatan, 6 bahaya, dengan label teks "5 dari 6".
- Badge notifikasi: lingkaran/pill Turquoise, teks Deep Navy, angka maks "99+".
- Toast di kanan atas (desktop) atau atas layar (ponsel); sukses/galat memakai warna semantik, otomatis hilang 5 detik kecuali galat.

### Navigasi
- Item nav: ikon + teks, tinggi 44px. Aktif = latar primary-soft + teks/ikon biru (gelap: `#93C5FD`).
- Header atas: judul halaman, pemilih peran, lonceng notifikasi (badge Turquoise), menu akun dengan pilihan tema.
- Walas melihat kanban read-only 5 kolom: kolom bergulir horizontal di ponsel dengan penanda kolom aktif.

### Modal & Konfirmasi
Radius 12px, lebar maks 480px (ponsel: bottom sheet). Judul jelas, ringkasan dampak, tombol destruktif terpisah dari tombol utama. Modal kemiripan perusahaan menampilkan kandidat berdampingan dengan input siswa.

### Status Kosong, Galat, Memuat
- Empty state: ikon garis + satu kalimat + satu aksi ("Belum ada bimbingan. Unggah bimbingan pertama").
- Galat: pesan manusiawi, apa yang terjadi, apa yang bisa dilakukan (mis. 409 kuota: "Kuota perusahaan sudah penuh. Muat ulang untuk melihat status terbaru.").
- Memuat: skeleton surface-muted, bukan spinner layar penuh. Kecuali tindakan satu klik, tidak ada loading yang memblokir layar.
- Pesan galat portal eksternal netral dan tidak membocorkan keberadaan target, dengan tautan "Minta link baru".

### Halaman Verifikasi QR (`/verify/{token}`)
Card tengah sederhana: ikon status, nomor surat (mono), status (chip), tanggal terbit. Tidak ada data lain. Token tidak valid/dicabut menampilkan pesan netral yang sama gayanya.

## Do's and Don'ts

**Do**
- Do jaga rasio 60-30-10; biarkan whitespace bekerja.
- Do pakai Deep Navy (`#0F172A`) untuk teks utama di mode terang, dan `#F1F5F9` di mode gelap.
- Do tampilkan status sebagai chip bertulisan, dengan posisi step dan "menunggu siapa" yang eksplisit.
- Do uji tiap layar di 360px, di mode terang dan gelap, sebelum dianggap selesai.
- Do beri cincin fokus Turquoise yang terlihat pada semua elemen interaktif.
- Do dukung `prefers-reduced-motion`: transisi ≤ 150ms, tanpa animasi dekoratif; transisi pergantian siswa (sidang) cukup fade/slide singkat.
- Do pakai teks Deep Navy di atas isian Turquoise.
- Do tampilkan alasan ketika tombol dinonaktifkan atau aksi ditolak server.

**Don't**
- Don't pakai Turquoise sebagai warna teks atau ikon kecil di mode terang.
- Don't pakai teks putih di atas Turquoise.
- Don't pakai biru `#1E40AF` sebagai teks/ikon di atas surface gelap; gunakan `#93C5FD`.
- Don't pakai hitam murni `#000000` atau putih murni sebagai teks di mode gelap; gunakan nilai token.
- Don't mengandalkan warna saja untuk status, kuota, atau validasi.
- Don't membuat tombol utama kedua dalam satu layar, atau memakai Turquoise untuk dekorasi.
- Don't memberi bayangan besar, gradien, atau efek kaca pada layar operasional.
- Don't menampilkan data sensitif (kontak CP ter-mask, token, PIN, OTP) di log UI, toast, atau URL.
- Don't membuat tabel yang hanya bisa digulir horizontal di ponsel untuk data yang bisa disajikan sebagai card.
- Don't menambah komponen, ikon set, atau font baru di luar dokumen ini tanpa memperbarui Design.md.
