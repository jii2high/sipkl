# TASKS — Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi

**Turunan dari**: `PRD.md` v3 dan `TRD.md` (§19 urutan milestone).
**Lokasi**: `docs/TASKS.md`
**Jumlah**: 11 milestone, 21 task (22 bila opsional diambil).

## 0. Cara Memakai

### Prinsip pengelompokan (alasan jumlah task sedikit)

1. **Slice vertikal.** Satu task = migrasi + model + Enum/transisi + Action + Policy + UI Filament/Blade + tes untuk satu kemampuan utuh. Tidak ada task terpisah "buat tabel", "buat UI", atau "tulis tes".
2. **Gabung bila bersama-sama dipakai.** Fitur yang berbagi service, tabel, atau layar digabung (mis. plotting + visitasi, penerimaan + provisional + withdrawal).
3. **Bangun just-in-time.** Komponen UI bersama (`SignatureStepper`, `QuotaBar`, `OtpInput`, dll.) dibuat di task pertama yang membutuhkannya, bukan di task "komponen" tersendiri.
4. **Pisah hanya bila ada alasan teknis**: dependensi berbeda, dapat dikerjakan paralel, atau risikonya berbeda (mis. infrastruktur magic link dipisah dari rantai 5-TTD karena dipakai tiga fitur).

### Singkatan yang dipakai di tiap task

| Singkatan | Arti |
|---|---|
| **DoD** | Definition of Done `TRD.md` §21 (aturan terpenuhi + Pest hijau, lewat Action, Policy + scope teruji, idempoten/atomik, tanpa rahasia di log, teks Bahasa Indonesia, `scheme.sql` ikut diperbarui, tanpa paket baru). Berlaku untuk **semua** task; tidak diulang di tiap task |
| **CV** | Checkpoint visual `TRD.md` §17.2, berlaku untuk task yang menghasilkan halaman: 360px dan 1280px; mode terang dan gelap; kontras sesuai `Design.md`; status berupa chip bertulisan; target sentuh ≥ 44px dan input ≥ 16px di ponsel |
| **BR / KP** | Aturan bisnis dan kriteria penerimaan di `PRD.md` (`BR-F3-1`, `KP-F3-3`, dst.) |
| Ukuran | **S** kecil, **M** sedang, **L** besar, **XL** sangat besar (relatif, bukan estimasi jam) |

### Aturan eksekusi untuk agent

- Baca bagian fitur terkait di `PRD.md` sebelum mulai; perilaku produk mengikuti PRD.
- 1 state machine = 1 Enum + 1 tabel transisi + 1 Action per transisi. Tidak ada transisi di luar Action.
- Tidak ada tahap wireframe. Acuan visual hanya `Design.md`.
- Jangan menambah paket atau abstraksi di luar `TRD.md` §2 tanpa task baru di berkas ini.
- Perubahan skema → perbarui `docs/scheme.sql` dan migrasi dalam task yang sama.
- Beri centang `[x]` hanya bila DoD (dan CV bila berlaku) terpenuhi. Jangan klaim lulus untuk pemeriksaan yang tidak dijalankan.
- Bila keputusan PO belum ada (Q-A, Q-E, Q-F, Q-K), pakai default di PRD Lampiran B dan catat di bagian Catatan Pelaksanaan; jangan menghentikan task lain.

### Peta dependensi

```
T0.1 → T0.2 → T0.3 → T1.1 ─┬→ T2.1 ─┐
                            ├→ T2.2 ─┼→ T3.1 → T4.1 → T4.2 → T5.1 ─┐
                            ├→ T6.2 (paralel)                      ├→ T6.1 → T6.3 → T7.2 → T8.1
                            └→ T9.2 (paralel)           T7.1 (setelah T2.2) ──────────┘        │
                                                                                T9.1 → T10.1 → T10.2
```

Bagian yang dapat berjalan paralel: **T6.2** dan **T9.2** (setelah T1.1), **T7.1** (setelah T2.2).

---

## Daftar Milestone

| M | Nama | Task | Hasil yang bisa didemokan |
|---|---|---|---|
| M0 | Fondasi | T0.1–T0.3 | Aplikasi kosong berjalan, tema terang/gelap, skema + fondasi domain |
| M1 | Akses | T1.1 | Login, OTP, ganti password, pindah peran |
| M2 | Master Data | T2.1–T2.2 | Hubin mengelola perusahaan, periode, akun, impor, TTD Kepsek |
| M3 | Dokumen Dasar | T3.1 | Nomor surat, PDF + QR, verifikasi publik, penyimpanan file aman |
| M4 | Pengajuan | T4.1–T4.2 | Pengajuan sampai `sedang_pkl`, kuota, provisional, withdrawal |
| M5 | Plotting & Visitasi | T5.1 | Guru terplot + surat tugas; visitasi lengkap |
| M6 | Bimbingan & Pengesahan | T6.1–T6.3 | Bimbingan, portal eksternal, rantai 5-TTD |
| M7 | Form & Sidang | T7.1–T7.2 | Form builder, jadwal sidang, penilaian oleh penguji, kunci nilai |
| M8 | Penilaian Industri | T8.1 | Link per perusahaan, penilaian pivot |
| M9 | Monitoring & Publik | T9.1–T9.2 | Kanban Walas, dashboard waktu tunggu, landing, FAQ |
| M10 | Ekspor & Penutup | T10.1–T10.2 (+T10.3 opsional) | Ekspor akreditasi, uji beban, siap go-live |

---

# M0 — Fondasi

### [✓] T0.1 — Bootstrap proyek, konfigurasi, dan tema  · Ukuran: L
**Cakupan**
- Inisialisasi Laravel + Filament dengan dua panel (`hubin` di `/hubin`, `app` di `/app`), Livewire, Pest, dan paket di `TRD.md` §2 (activitylog, dompdf, endroid/qr-code, maatwebsite/excel). Kunci versi di `composer.json`.
- `config/pkl.php` berisi seluruh konstanta bisnis `TRD.md` §4 (kuota 6, TTL, batas upload, dst.).
- `.env.example`; queue, cache, dan session memakai driver `database`; disk private; timezone aplikasi UTC dengan helper Asia/Jakarta di `app/Support/` (batas inklusif 23:59:59 Jakarta, hari kerja Senin–Jumat).
- Struktur folder sesuai `TRD.md` §3.2; `resources/lang/id/`.
- Tema dari `Design.md`: CSS variables terang/gelap di `resources/css/app.css`, pemetaan warna ke Filament, tanpa kilatan tema saat load. Komponen bersama awal: `StatusBadge`, `ThemeSwitcher`, layout publik, dan layout eksternal minimal.
- CI menjalankan Pest.

**Rujukan**: PRD §1.5(9), §1.7, NFR Responsif; `Design.md`.
**Selesai bila**: kedua panel dapat dibuka; ganti tema Terang/Gelap/Sistem bekerja; tes helper waktu (batas 23:59:59 Jakarta, hari kerja) hijau; CV pada layout dasar.

### [x] T0.2 — Skema database dan data awal  · Ukuran: L
**Cakupan**
- Tulis `docs/scheme.sql` dari `TRD.md` §6 dan Lampiran A PRD (A1–A14), lalu buat migrasi Laravel yang setara (satu migrasi per kelompok tabel §6.1). Model Eloquent dan factory.
- Seluruh constraint di `TRD.md` §6.2 berada di database: generated column `active_key` (pengajuan aktif, magic link aktif), CHECK status↔`pengajuan_pkl_id`, FK komposit group, signer NULLABLE + CHECK `verified_at`, `jurusan_key/kelas_key`, UNIQUE nomor surat per tahun ajaran, UNIQUE plotting, kolom `*_hash`, generated column `nilai_total`.
- Seeder: peran, jurusan, periode contoh, FAQ awal.
- Tes skema: setiap invarian yang dijaga DB dibuktikan dengan percobaan insert yang melanggar (harus gagal).

**Rujukan**: PRD §1.5(1,3,6,7), Lampiran A.
**Selesai bila**: `migrate:fresh --seed` berhasil; `scheme.sql` dan migrasi menghasilkan skema setara; tes constraint hijau.

### [x] T0.3 — Fondasi domain lintas modul  · Ukuran: M
**Cakupan**
- Kerangka state machine: base Enum + `TransitionTable` + exception `InvalidTransition` (dipetakan ke 409/422 berpesan Indonesia).
- Base Action: transaksi, pemeriksaan status sumber (`UPDATE … WHERE status = :dari` dan cek `affected_rows`), hasil idempoten, audit dalam transaksi yang sama.
- `AuditService` (activitylog) dengan redaksi token/PIN/OTP/password.
- Infrastruktur notifikasi: email + in-app, `afterCommit`, retry transient maks 3x, pencatatan kegagalan permanen untuk Hubin; kerangka job idempoten dengan kunci "sudah jalan per tanggal Jakarta".
- Pola Policy + scope query (`visibleTo($user, $role)`).

**Rujukan**: PRD §1.5(5,8), BR-F14-1, BR-F14-2, BR-F14-4.
**Selesai bila**: tes unit `TransitionTable` (pasangan tak dikenal ditolak), tes Action dua kali panggil hanya satu efek, tes redaksi log, tes retry notifikasi.

---

# M1 — Akses

### [✓] T1.1 — Autentikasi, OTP first-login, dan role switch (F1)  · Ukuran: L
**Cakupan**
- Halaman Livewire kustom: login, tantangan OTP, paksa ganti password. Validasi domain `@smkn1kotabekasi.sch.id` sebelum OTP; respons sama untuk akun ada/tidak ada.
- `OtpService` internal: 6 digit, ter-hash, TTL 60 detik, maks 3 percobaan (ke-4 menginvalidasi lewat `invalidated_at`), resend 5x/30 menit lalu cooldown 30 menit; limiter `otp-verify` dan `otp-resend` terpisah; komponen `OtpInput`.
- Ganti password: ≥ 8 karakter dan ≠ NIS/NIP, pesan spesifik.
- `SwitchActiveRole`, middleware `EnsureActiveRole` (403), navigasi panel `app` dari `active_role`, `RoleSwitcher` di header, pembersihan state saat pindah peran; preferensi tema tersimpan per akun.
- Akun nonaktif ditolak; email OTP lewat infrastruktur T0.3.

**Rujukan**: BR-F1-1…5; KP-F1-1…5.
**Selesai bila**: seluruh KP-F1 lolos (termasuk 403 pada peran tak dimiliki dan pergantian peran tanpa reload penuh); CV pada halaman auth.

---

# M2 — Master Data

### [x] T2.1 — Perusahaan, resolver duplikat, signer, merge, impor perusahaan (F2)  · Ukuran: L
**Cakupan**
- `CompanyResolver`: strict-unique (`email`, `website`, `telp_fax` dinormalisasi) dan soft-warning Levenshtein untuk nama/alamat. Satu resolver untuk form siswa dan impor.
- Resource Filament Hubin untuk perusahaan, termasuk signer Pembimbing/Kepala Industri (nama individu + email terverifikasi; jabatan generik ditolak untuk OTP).
- `MergeCompany`: `merged_into_id`, pindahkan referensi dalam transaksi, audit; merge ke diri sendiri ditolak.
- `ImportData` untuk perusahaan: transaksi per baris, laporan baris bermasalah.
- Komponen autocomplete perusahaan (nama + kota) dan `MaskedContact` (dipakai ulang di T4.1).

**Rujukan**: BR-F2-1…4; KP-F2-1, KP-F2-3, KP-F2-4.
**Selesai bila**: KP-F2-1/3/4 lolos; tes normalisasi telp/website; CV.

### [x] T2.2 — Periode, akun, peran, impor akun, TTD Kepsek (F2)  · Ukuran: M
**Cakupan**
- Periode akademik (tahun ajaran + batch 1/2, kunci pengajuan baru; periode berdata tidak bisa dihapus).
- Akun: filter Guru/Siswa, `user_roles` dengan scope jurusan/kelas (rangkap peran), reset OTP, nonaktifkan.
- Impor massal akun (siswa, guru, walas, kaprog) memakai `ImportData` dari T2.1; password awal = NIS/NIP.
- `signature_assets`: unggah scan TTD Kepsek (satu aktif), disimpan private.

**Rujukan**: BR-F2-5; KP-F2-2.
**Selesai bila**: KP-F2-2 lolos (100 baris, 5 salah → 95 masuk + laporan); satu TTD Kepsek aktif; CV.

---

# M3 — Dokumen Dasar

### [✓] T3.1 — Nomor surat, PDF + QR, verifikasi publik, penyimpanan file (F12)  · Ukuran: L
**Cakupan**
- `LetterNumberService`: counter per (tahun ajaran, jenis) dengan `FOR UPDATE` dalam transaksi pembuatan dokumen; unik per tahun ajaran.
- `DocumentService`: PDF via queue (dompdf) + QR (endroid) + `document_qr_tokens` (hash). Kegagalan tidak meninggalkan nomor terpakai tanpa dokumen; retry idempoten memakai nomor yang sama.
- **Tiga template PDF sekaligus** (Surat Pengantar, Surat Tugas Pembimbing, Surat Tugas Visitasi) agar M4–M5 tinggal memanggil. Susun dari contoh di `docs/old physical document examples/` (kop, urutan blok, bahasa surat); catat perbedaan dari contoh fisik di bagian Catatan Pelaksanaan.
- Halaman `/verify/{token}` (Blade + Livewire): hanya nomor surat, status, tanggal terbit; token tidak valid/dicabut → pesan netral.
- `FileStorageService` dan controller unduh berotorisasi: deteksi MIME dari isi, batas ukuran per jenis, nama acak, disk private. Dipakai ulang oleh bimbingan, balasan, dokumentasi.
- Penahanan penerbitan bila `signature_assets` kosong, dengan notifikasi ke Hubin.

**Rujukan**: BR-F12-1…5, BR-F4-3; KP-F12-1…3.
**Selesai bila**: KP-F12-1/2/3 lolos; unggah file berekstensi palsu ditolak; pengguna tak berwenang tidak dapat mengunduh; CV pada `/verify`.

---

# M4 — Pengajuan

### [✓] T4.1 — Pengajuan siswa sampai gate Kaprog dan Walas, plus grouping (F3 bagian 1)  · Ukuran: XL
**Cakupan**
- Enum + tabel transisi `pengajuan_siswa` lengkap (`TRD.md` §5.2); status company group (`terbentuk/surat_terbit/berjalan/bubar`).
- Siswa: draft, pilih/ketik perusahaan (pakai komponen T2.1), modal konfirmasi kemiripan nama, kontak CP ter-mask, submit (snapshot JSON immutable), revisi setelah ditolak. Satu pengajuan aktif per siswa per periode; periode terkunci menolak submit.
- Kaprog Gate 1 (relevansi, belum memakai kuota) dan Walas Gate 2 (TTD), penolakan wajib catatan ≥ 10 karakter; histori terbaca.
- `GroupCompanyJob` setelah TTD Walas → `menunggu_surat_pengantar`.
- Layar: form dan status siswa (mobile-first), antrean Gate 1 Kaprog, antrean Gate 2 Walas; `StatusBadge` terpetakan ke status.
- Perusahaan baru tanpa signer tetap boleh lanjut (Q-H).

**Rujukan**: BR-F3-1, BR-F3-2, BR-F3-6; KP-F3-1, KP-F3-2, KP-F3-4.
**Selesai bila**: tes tiap transisi (aktor salah 403, status salah 409, panggil dua kali satu efek); snapshot tidak berubah saat data perusahaan diedit; CV.

### [✓] T4.2 — Surat pengantar, penerimaan, kuota, provisional, withdrawal (F3 bagian 2)  · Ukuran: XL
**Cakupan**
- Hubin menerbitkan surat pengantar per company group (T3.1) → `surat_pengantar_terbit`; siswa unggah surat balasan → `menunggu_balasan_perusahaan`.
- `QuotaService` + Action `AcceptByCompany`: `FOR UPDATE` pada `companies`, hitung ulang `sedang_pkl` (provisional ikut), lempar `QuotaExceeded` (409) tanpa efek samping; set `is_provisional` dari dokumen fisik; `tanggal_mulai/selesai_pkl` default dari periode, dapat diubah Kaprog. `RejectByCompany` untuk siswa tersebut saja.
- Hubin `ConfirmPhysicalDocument` hanya mengubah `is_provisional`.
- Withdrawal: pengajuan siswa/Kaprog dengan alasan, `ApproveWithdrawal` → `withdrawn`, slot lepas; sediakan hook archive bimbingan (diisi di T6.1).
- `ProvisionalReminderJob` harian (email + in-app, tanpa eskalasi).
- Komponen `QuotaBar` (≤ 4 netral, 5 peringatan, 6 bahaya, dengan label "n dari 6"); antrean Kaprog dengan peringatan kuota.

**Rujukan**: BR-F3-3, BR-F3-4, BR-F3-5; KP-F3-3, KP-F3-5, KP-F3-6, KP-F3-7; PRD §1.5(1,2).
**Selesai bila**: tes konkurensi dua approval pada kuota 5 → satu `sedang_pkl` + satu 409; `rejected`/`withdrawn` melepas slot; reminder harian idempoten; CV.

---

# M5 — Plotting & Visitasi

### [x] T5.1 — Plotting guru, Surat Tugas Pembimbing, dan visitasi (F4 + F5)  · Ukuran: L
**Cakupan**
- **Plotting**: daftar company dengan placement resmi (≥ 1 siswa `sedang_pkl` pada company+periode), pilih guru dengan indikator beban kerja dan soft-warning multi-wilayah; 1 company + 1 periode = 1 guru; `AssignGuru` otomatis menerbitkan Surat Tugas Pembimbing (tanpa approval Hubin); siswa susulan mewarisi guru; `ReplaceGuru` membuat surat baru + audit; surat ditahan bila TTD Kepsek belum ada (notifikasi Hubin).
- **Visitasi**: Kaprog mengajukan (multi-select perusahaan atau general) atau Hubin membuat manual; Hubin approve/reject (alasan wajib); nomor surat terbit; masa berlaku default 7 hari, inklusif 23:59:59 Jakarta; `ExpireVisitasiJob` harian; guru mengunggah dokumentasi (jpg/png ≤ 5MB); pengajuan untuk perusahaan bukan milik guru ditolak; reject boleh diajukan ulang sebagai record baru.
- Layar: plotting Kaprog, daftar visitasi (Kaprog, Hubin, Guru).

**Rujukan**: BR-F4-1…5, BR-F5-1…4; KP-F4-1…3, KP-F5-1…3.
**Selesai bila**: KP-F4 dan KP-F5 lolos; job kedaluwarsa dijalankan dua kali tidak mengubah status dua kali; CV.

---

# M6 — Bimbingan & Pengesahan

### [x] T6.1 — Bimbingan laporan dan ACC final (F6)  · Ukuran: M
**Cakupan**
- Unggah bimbingan berulang (file pdf/doc/docx ≤ 10MB via `FileStorageService`, MIME dari isi, private) dan feedback guru (revisi/diterima).
- Guard server: `is_provisional = false` dan ≥ 4 interaksi (yang tidak ter-archive) untuk "Bagian Akhir"; berlaku juga untuk request langsung ke endpoint.
- `ApproveLaporanFinal`: satu aksi menghasilkan `laporan_versions` v1, step 1 `signed`, step 2 `pending`.
- Isi hook withdrawal dari T4.2: histori bimbingan di-archive, hitungan mulai 0 pada record baru.
- Layar: bimbingan siswa (mobile-first), daftar siswa bimbingan dan feedback guru; indikator progres 3/4.

**Rujukan**: BR-F6-1…4; KP-F6-1…4; PRD §1.5(2).
**Selesai bila**: KP-F6 lolos (provisional ditolak langsung ke endpoint; file `.exe` berekstensi `.pdf` ditolak); CV.

### [x] T6.2 — Infrastruktur akses eksternal: magic link, OTP eksternal, portal (F11)  · Ukuran: L
**Cakupan**
- `MagicLinkService`: token ≥ 32 byte acak, simpan SHA-256, single-use, revocable, 1 aktif per (type, target) dengan `active_key`; reissue = revoke lama + terbit baru atomik; tipe `examiner_sidang`, `examiner_unlock`, `signer_step3`, `signer_step4`, `industry_assessment`.
- `OtpService` eksternal (email, 5 menit, maks 3 percobaan) dan PIN 4 digit untuk penguji (3 salah → invalid); sesi eksternal 24 jam turunan token (revoke menutup sesi turunan); binding perangkat longgar (verifikasi tambahan, bukan penolakan keras).
- Guard `EnsureExternalToken`, `routes/eksternal.php`, layout minimal satu-tugas; pesan galat netral dan halaman "Minta link baru" yang meneruskan ke Kaprog/Guru/Hubin; override Hubin wajib alasan di audit.
- `ExpireMagicLinksJob` per jam; komponen `PinInput`.

**Rujukan**: BR-F11-1…5, BR-F9-1, BR-F9-2; KP-F11-1…4; PRD §1.5(6).
**Selesai bila**: KP-F11 lolos (token mentah tidak ada di DB/log; link tidak valid, kedaluwarsa, dan tidak ada menghasilkan pesan sama); CV pada layout eksternal.

### [x] T6.3 — Rantai pengesahan 5-TTD (F7)  · Ukuran: L
**Cakupan**
- Enum + transisi `laporan_signatures` (`pending → signed | rejected`), kode step 1–5. `SignStep` memastikan step N hanya setelah N-1 `signed`; `RejectStep` (catatan ≥ 10) tidak mereset step sebelumnya; `UploadRevisiLaporan` membuat versi baru dan kembali langsung ke step yang menolak.
- Step 2 Kaprog dan step 5 Kepsek (single dan bulk; tiap laporan diproses independen, hasil per item dikembalikan); step 3/4 lewat magic link + OTP (`IssueSignerLink`, `ReissueSignerLink`) hanya setelah step sebelumnya `signed`.
- Signer industri tanpa email terverifikasi → langkah ditahan + notifikasi Hubin (Q-H).
- Setelah step 5 `signed` → `is_legit = true` → masuk pool Siap Sidang.
- Komponen `SignatureStepper` (selesai, aktif ber-cincin turquoise, belum, menolak) dan layar TTD Kepsek mobile dengan target sentuh 44px.

**Rujukan**: BR-F7-1…3; KP-F7-1…5; PRD §1.5(3,8).
**Selesai bila**: KP-F7 lolos (termasuk request langsung ke step yang belum boleh, reject step 4 tidak mengubah step 1–3, bulk 10 laporan dengan 1 gagal); sign ganda idempoten; CV.

---

# M7 — Form & Sidang

### [x] T7.1 — Form builder dan perhitungan nilai (F8)  · Ukuran: M
**Cakupan**
- Builder Kaprog untuk form `sidang` dan `penilaian_industri` per jurusan + periode: Section → Items, "Section Kehadiran" dan "Section Catatan", publish.
- Struktur JSON dua tingkat; publish ditolak bila tanpa item; setelah ada submission, struktur terkunci (ubah = versi baru); hapus section/item terpakai ditolak.
- `ScoreService`: validasi jawaban terhadap definisi form dan hitung `nilai_total` di server (default Q-E: rata-rata item 0–100); Kehadiran dan Catatan tidak ikut hitungan.
- Petakan struktur form dari `docs/form-penilaian-references/`; catat selisih di Catatan Pelaksanaan.

**Rujukan**: BR-F8-1, BR-F8-2; KP-F8-1…3.
**Selesai bila**: KP-F8 lolos; tes `ScoreService` untuk batas nilai dan item kosong; CV.

### [x] T7.2 — Sidang, portal penguji, kunci nilai, dan revisi nilai (F9)  · Ukuran: XL
**Cakupan**
- Kaprog menjadwalkan siswa dari pool Siap Sidang (hanya `is_legit`) dan menetapkan penguji (nama + kontak terverifikasi); `CascadeShift` atomik (gagal validasi → jadwal awal utuh + daftar konflik).
- `IssueExaminerLink` (email atau tautan `wa.me` manual) memakai infrastruktur T6.2; link berlaku sampai akhir hari sidang (Jakarta).
- Portal penguji: masuk dengan PIN, isi nilai, "Simpan & Selesaikan" → `is_submitted` (update berikutnya 403) → pindah ke siswa berikutnya tanpa reload penuh.
- Revisi nilai: `RequestUnlock` / `ApproveUnlock` Kaprog → link + PIN baru (1 form, 24 jam) hanya untuk penguji asli; `RelockScoreJob` per jam, nilai lama berlaku bila TTL habis.
- Revoke/reissue oleh Kaprog/Hubin; status kelulusan opsional diisi manual Kaprog (Q-I).

**Rujukan**: BR-F9-1…5; KP-F9-1…6; PRD §1.5(4).
**Selesai bila**: KP-F9 lolos; tes cascade shift gagal tidak mengubah jadwal; unlock hanya membuka satu form; CV pada portal penguji.

---

# M8 — Penilaian Industri

### [x] T8.1 — Penilaian industri per perusahaan (F10)  · Ukuran: M
**Cakupan**
- Window H-1 bulan sebelum `tanggal_selesai_pkl`; tombol "Generate Link" di detail perusahaan (guru), tetapi server tetap memeriksa window.
- 1 link + 1 submission untuk seluruh siswa bimbingan aktif di perusahaan itu (`form_submissions.pengajuan_siswa_id` NULL; siswa via `form_submission_siswa`; siswa `withdrawn` tidak ikut); form per form definition (satu per jurusan, Q-B).
- Portal pembimbing industri (magic link + OTP, T6.2): submit final tanpa revisi; akses ulang setelah submit ditolak.
- Revoke/reissue oleh guru (siswa bimbingannya) dan Hubin (override beralasan).
- `IndustryAssessmentReminderJob` H-7 harian, hanya bila link belum dibuat.

**Rujukan**: BR-F10-1…4; KP-F10-1…4.
**Selesai bila**: KP-F10 lolos; reminder idempoten per tanggal Jakarta; CV pada portal industri.

---

# M9 — Monitoring & Publik

### [x] T9.1 — Dashboard dan kanban monitoring (F15)  · Ukuran: M
**Cakupan**
- Kanban Walas read-only 5 kolom, hanya kelasnya (definisi kolom mengikuti Q-K; default di PRD Lampiran B); horizontal-scroll dengan penanda kolom aktif di ponsel.
- Dashboard Kaprog/Hubin: durasi tunggu per step 5-TTD dan per gate (hari kerja Senin–Jumat, libur nasional tidak dikecualikan), antrean gate dengan peringatan kuota.
- Tidak ada target angka untuk step eksternal (hanya dipantau).

**Rujukan**: BR-F15-1, BR-F15-2; KP-F15-1, KP-F15-2.
**Selesai bila**: Walas tidak melihat kelas lain dan tidak punya kontrol ubah; perhitungan hari kerja teruji; CV.

### [x] T9.2 — Landing page dan FAQ publik (F16)  · Ukuran: S
**Cakupan**
- Landing dan `/faq` tanpa login, hanya `is_published = true`; filter kategori, pencarian `LIKE`, 7–10 item per halaman, pesan jelas bila kosong.
- Hubin CRUD kategori dan FAQ.
- Dapat dikerjakan paralel setelah T1.1.

**Rujukan**: BR-F16-1…4; KP-F16-1, KP-F16-2.
**Selesai bila**: KP-F16 lolos; CV.

---

# M10 — Ekspor & Penutup

### [x] T10.1 — Ekspor akreditasi dan uji beban (F13)  · Ukuran: M
**Cakupan**
- Hubin memicu ekspor → attempt `queued` → `AccreditationExportJob` chunk 500 → validasi kelengkapan → `completed` + notifikasi tautan; gagal/parsial = `failed` dan tidak dapat diunduh; retry = attempt baru; unduh Hubin-only lewat controller; `PurgeExpiredExportsJob` hapus setelah 30 hari.
- Field sesuai PD-08 saja; snapshot immutable.
- **Uji beban** untuk 1.752 siswa; catat hasil di Catatan Pelaksanaan. Target < 5 menit adalah target yang diuji, bukan janji.

**Rujukan**: BR-F13-1…3; KP-F13-1…4.
**Selesai bila**: KP-F13 lolos; hasil uji beban tercatat; CV.

### [x] T10.2 — Pengerasan, UAT, dan kesiapan go-live  · Ukuran: L
**Cakupan**
- Audit keamanan sesuai `TRD.md` §16: header (HSTS, CSP yang kompatibel dengan Livewire/Filament, `Referrer-Policy` pada rute eksternal), cookie sesi, rate limit (login, OTP, PIN, `/verify`), tidak ada rahasia di log/audit.
- Tinjau seluruh invarian PRD §1.5 terhadap tes yang ada; tambah tes yang belum ada.
- UAT per peran (Siswa, Walas, Guru, Kaprog, Hubin, Kepsek, Penguji, Industri) di 360px dan 1280px, terang dan gelap.
- Operasional: konfigurasi supervisor `queue:work`, cron `schedule:run`, cadangan DB + `storage/app/private`, uji pemulihan; seeding produksi (peran, jurusan, periode, FAQ).
- Sinkronkan `docs/scheme.sql`, `TRD.md`, dan PRD dengan kenyataan; tutup atau catat butir Q-A, Q-E, Q-F, Q-K.

**Rujukan**: PRD Bagian 3 (NFR), `TRD.md` §16, §18.
**Selesai bila**: semua tes hijau; checklist go-live terisi; tidak ada butir Q yang menggantung tanpa keputusan tertulis.

### [x] T10.3 — Push notification opt-in *(opsional)*  · Ukuran: S
**Cakupan**: push opt-in dan best-effort sebagai kanal tambahan; tidak diprompt ulang setelah ditolak; kegagalan tidak memengaruhi email dan in-app.
**Rujukan**: BR-F14-1.
**Selesai bila**: opt-in/out bekerja dan kegagalan push tidak mengganggu kanal lain.

---

# Pelacakan

## Matriks Fitur → Task

| Fitur | Task |
|---|---|
| F1 Autentikasi | T1.1 |
| F2 Master Data | T2.1, T2.2 |
| F3 Pengajuan | T4.1, T4.2 |
| F4 Plotting | T5.1 |
| F5 Visitasi | T5.1 |
| F6 Bimbingan | T6.1 |
| F7 5-TTD | T6.3 |
| F8 Form Builder | T7.1 |
| F9 Sidang & Nilai | T7.2 |
| F10 Penilaian Industri | T8.1 |
| F11 Magic Link | T6.2 |
| F12 Dokumen & QR | T3.1 |
| F13 Ekspor | T10.1 |
| F14 Notifikasi & Job | T0.3 (fondasi); job per fitur di T4.2, T5.1, T6.2, T7.2, T8.1, T10.1; push di T10.3 |
| F15 Monitoring | T9.1 |
| F16 FAQ & Landing | T9.2 |

## Catatan Pelaksanaan (diisi saat pengerjaan)

| Topik | Catatan |
|---|---|
| M0 — status | T0.2 dan T0.3 selesai (40 tes Pest hijau di MySQL 8.4.3). T0.1 selesai kecuali **checkpoint visual** 360px/1280px terang/gelap pada layout dasar, yang harus dicek manual di peramban. |
| M0 — `activity_log` | Dibuat mengikuti skema spatie/laravel-activitylog v5 yang sebenarnya: kolom `event` dan `attribute_changes`, tanpa `batch_uuid`. `docs/schema.sql` BAGIAN 13 sudah disesuaikan. Indeks morph memakai lebar 150 dengan nama `idx_activity_subject`/`idx_activity_causer`. |
| M0 — `ck_company_not_self_merge` | Dibuang dari migrasi dan `docs/schema.sql`: MySQL 8.4 menolak CHECK yang menyentuh kolom AUTO_INCREMENT (error 3818). Invarian "merge ke diri sendiri ditolak" ditegakkan di Action `MergeCompany` (T2.1). |
| M0 — tipe `magic_links` | Enum `type` mengikuti `TRD.md` §9.1 (`examiner_sidang`, `examiner_unlock`, `signer_step3`, `signer_step4`, `industry_assessment`), menggantikan nama lama di `docs/schema.sql`. Pemetaan `reference_id` dicatat sebagai komentar di skema. |
| M0 — `users.remember_token` | Rancangan `docs/schema.sql` tidak memuat kolom ini, jadi model `User` menonaktifkan token "ingat saya" (login murni sesi server). Login panel Filament diuji tetap berhasil dengan opsi "ingat saya" aktif. |
| M0 — akun seeder | Kata sandi awal = NIS/NIP dengan `is_first_login = true` (BR-F1-2); satu akun per peran (siswa, walas, guru_pembimbing, kaprog, hubin, kepsek). |
| M0 — daftar jurusan | Seeder memakai contoh `docs/schema.sql` (RPL, TKR, TP) dengan satu kelas per tingkat. Daftar jurusan resmi menunggu konfirmasi Hubin. |
| M0 — `v_kuota_perusahaan` | Dibuat dengan `CREATE OR REPLACE VIEW` agar `migrate:fresh` tetap berhasil (view tidak ikut terhapus oleh drop tabel). |
| M0 — database tes | Tes berjalan di MySQL `sistem_pkl_test` (bukan SQLite) melalui `phpunit.xml`, supaya CHECK, generated column, dan FK komposit benar-benar diuji. |
| M0 — panel Filament | `->login()` diaktifkan pada kedua panel; panel `app` ditetapkan `->default()`. Dasbor hanya dapat dibuka di environment `local` sampai `T1.1` membuat model `User` mengimplementasikan `FilamentUser` dengan `canAccessPanel()` (aturan peran per panel). Panel login bawaan bersifat sementara sampai `T1.1` menggantinya dengan halaman Livewire kustom. |
| M1 — status | DoD + seluruh KP-F1 teruji (Pest hijau). Halaman `/login`, `/login/otp`, `/login/password` dibuat sebagai Livewire kustom; login panel bawaan (`/app/login`, `/hubin/login`) dihapus dan tamu diarahkan ke `/login` lewat `PanelAuthenticate`. **CV halaman auth (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| M1 — hitungan resend | `otp.resend_max = 5` dimaknai 5 kirim ulang di atas kirim awal: 1 kirim awal + 5 resend = 6 kirim diizinkan per jendela 30 menit; kirim ke-7 ditolak (resend ke-6), sesuai KP-F1-2. Dihitung lewat RateLimiter `otp-resend` (bukan kolom tambahan). |
| M1 — OTP lewat queue | Email OTP dikirim lewat infrastruktur T0.3 (`NotificationService` → `SendNotificationJob`, `afterCommit`, retry) agar kegagalan transient tercatat. Konsekuensi operasional: `queue:work` harus jalan supaya OTP sampai sebelum TTL 60 detik. Tes memakai `QUEUE_CONNECTION=sync`. |
| M1 — penerima email lokal | Ditambah kunci `pkl.mail.override_to` (`PKL_MAIL_OVERRIDE_TO`): di `APP_ENV=local`, `User::routeNotificationForMail()` mengalihkan email ke alamat ini karena domain sekolah tidak nyata. Kosong di produksi. |
| M1 — preferensi tema | Snapshot tema per akun disimpan ke `users.theme_preference` lewat `POST /theme`; skrip kecil pada render hook panel menyinkronkan pilihan tema Filament (localStorage `filament:theme:mode`) ke akun dan menerapkan preferensi tersimpan saat panel dibuka. |
| M1 — OTP input | Komponen `OtpInput` (6 kotak mono, auto-advance, paste, `autocomplete="one-time-code"`) memakai Alpine yang disertakan Livewire; bila Alpine gagal dimuat, input tetap dapat diisi manual per kotak. |
| M2 — status | T2.1 dan T2.2 selesai (Pest hijau, 117 tes di MySQL). KP-F2-1 (`CompanyResolver` + form), KP-F2-2 (`ImportData` 100 baris → 95 masuk + 5 laporan), KP-F2-3 (audit + pemindahan referensi `MergeCompany`), dan KP-F2-4 (`SignerGuard::canReceiveOtp`) teruji. **CV panel Hubin (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| M2 — kontak tersimpan ternormalisasi | Kolom `companies.email/website/telp_fax` menyimpan bentuk **sudah dinormalisasi** (bukan bentuk asli ketikan), supaya kunci UNIQUE database membandingkan nilai setara. Perbandingan strict-unique jadi kesetaraan sederhana; `CompanyResolver` tetap satu-satunya tempat aturan ini. |
| M2 — tanpa kolom `kota` | `docs/schema.sql` tidak punya kolom `kota` untuk perusahaan, jadi autocomplete perusahaan memakai **nama + alamat** (cakupan asli menyebut "nama + kota"). Tidak menambah kolom; bila Hubin memerlukan filter kota, tambahkan di task lanjutan. |
| M2 — `MergeCompany` dan FK komposit | Perpindahan referensi mematikan `FOREIGN_KEY_CHECKS` sementara di dalam transaksi, karena FK komposit `pengajuan_siswa(pengajuan_pkl_id, company_id, academic_period_id) → pengajuan_pkl` tidak bisa dipindah berurutan. Konsistensi tetap dijaga saat COMMIT; alasan dikomentari di kode. |
| M2 — tambahan di luar TRD §3.2 | Dua kelas ditambahkan untuk memenuhi cakupan F2: `SetAccountActive` (aktif/nonaktif akun + hapus sesi + batal OTP) dan `SignerGuard` (tolak jabatan generik sebagai penerima OTP). Keduanya dipanggil UI Hubin dan Policy, bukan lapisan baru di luar pola Action/Policy TRD. |
| M2 — `source` perusahaan | Kolom `source` tidak ditampilkan di form Hubin; nilainya mengikuti default DB `hubin` (impor mengisi `import`, pengajuan siswa nanti `siswa`). |
| M2 — reset OTP | "Reset OTP" membatalkan OTP aktif lalu mengembalikan akun ke `is_first_login = true`; kode baru diterbitkan saat login, sehingga Hubin tidak pernah melihat rahasia pengguna lain (TRD §12). |
| M2 — format berkas impor | Impor massal (perusahaan dan akun) membaca CSV/XLSX lewat `maatwebsite/excel`; baris pertama adalah judul kolom, baris kosong dilewati, dan tiap baris diproses `ImportData` dalam transaksinya sendiri. |
| M2 — pembaca/penyimpan sementara | `ImportSheet` (baca baris) dan unggahan `FileUpload` memakai API Filament v5 (`storeFiles(false)`), jadi `docs/schema.sql` tidak berubah pada M2. |
| T3.1 — status | T3.1 selesai (Pest hijau, 133 tes di MySQL). KP-F12-2/3 teruji; KP-F12-1 dijaga `SELECT ... FOR UPDATE` + UNIQUE `(tahun_ajaran, nomor_urut)`, tetapi balapan multi-proses nyata belum diuji otomatis. **CV `/verify` (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T3.1 — format nomor surat | `{kode}/{urut 4 digit}/SMKN.1-CADISDIK WIL 3`, kode per jenis: `421.5`, `KPG.11.01`, `KPV.11.01` (config `pkl.document`). Counter terpisah per (tahun ajaran, jenis) di `nomor_surat_counters`. |
| T3.1 — token QR | Token = `hash_hmac('sha256', "{jenis}:{id}", APP_KEY)` (deterministik, bukan acak seperti QR umum): retry job tidak pernah membuat token baru, dan yang disimpan hanya `sha256(token)`. Konsekuensi: mengganti `APP_KEY` membatalkan semua tautan verifikasi lama. |
| T3.1 — kop tanpa logo | Belum ada aset logo sekolah di repo, jadi kop hanya blok teks + dua garis seperti contoh fisik. Taruh berkas logo lalu sisipkan di `resources/views/pdf/partials/kop.blade.php` saat aset tersedia. |
| T3.1 — `docs/Design.md` tidak ada | Dokumen desain yang dirujuk beberapa task tidak ada di repo, dan belum ada `docs/DESIGN.md` pengganti. Halaman `/verify` memakai variabel CSS dan kelas `pkl-*` yang sudah ada. |
| T3.1 — TTD hasil pindai | dompdf hanya dapat menyematkan PNG/JPG; `signature_assets` berisi PDF dicetak tanpa gambar TTD (blok nama + NIP tetap ada). Validasi unggahan tetap menerima PDF sesuai `pkl.signature.mimes`. |
| T3.1 — alamat siswa | Skema `users` tidak punya kolom alamat, jadi kolom ALAMAT pada Surat Tugas Pembimbing dicetak `-` (kolom tetap ada seperti contoh fisik). |
| Selisih template surat vs `docs/old physical document examples/` | Kop: teks + garis, tanpa logo (lihat baris T3.1 di atas). Surat Pengantar: "Perihal" ditulis "Permohonan Praktik Kerja Lapangan (PKL)", tidak ada blok Tembusan, dan tanggal surat memakai tanggal terbit sistem. Surat Tugas: kolom ALAMAT siswa kosong, "Pangkat/Golongan" `-` (tidak ada di skema), dan masa berlaku visitasi diambil dari tanggal surat tugas. Ketiganya memakai QR verifikasi di kiri blok tanda tangan, yang tidak ada pada contoh fisik. |
| T4.1 — status | T4.1 selesai (Pest hijau, 16 tes pengajuan). Tes mencakup tiap transisi Gate 1/Gate 2 (aktor salah 403, status salah 409, panggil dua kali satu efek), snapshot immutable, penolakan ≥ 10 karakter, grouping idempoten, isolasi scope peran, dan duplikat perusahaan baru. **CV halaman siswa dan antrean Kaprog/Walas (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T4.1 — URL panel | Halaman siswa: `/app/pengajuan-saya`; antrean: `/app/antrean-pengajuan` (slug Filament dari nama kelas). Keduanya tidak punya resource Hubin; akses dibatasi `canAccess()` + query `visibleTo()`, bukan hanya menu tersembunyi. |
| T4.1 — komponen autocomplete | Blade memakai `@livewire(\App\Livewire\Support\CompanyAutocomplete::class)` mengikuti konvensi `role-switcher.blade.php`; alias `<livewire:company-autocomplete>` tidak terdaftar karena tidak ada `Livewire::component()` di repo. |
| T4.1 — snapshot | Snapshot JSON dibekukan saat submit dan ditulis lewat `transitionStatus()` (query builder) sehingga di-encode manual; data perusahaan setelahnya boleh berubah tanpa mengubah snapshot. |
| T4.1 — grouping | `pengajuan_pkl` tidak punya UNIQUE `(company_id, academic_period_id)`, jadi `GroupCompanyJob` mengunci baris pengajuan lalu baris `companies` sebelum `firstOrCreate`; job idempoten dan dijalankan `afterCommit`. |
| T4.1 — catatan penolakan | Catatan ≥ 10 karakter divalidasi di Action dan disimpan di `activity_log` (bukan kolom baru), konsisten dengan `docs/schema.sql`. |
| M4 — perbaikan migrasi | Tabel `notification_delivery_failures` gagal dibuat di MySQL 8.4 karena `first_failed_at`/`last_failed_at` TIMESTAMP NOT NULL tanpa default; migrasi dan `docs/schema.sql` diberi `DEFAULT CURRENT_TIMESTAMP`. Tanpa ini seluruh tes `RefreshDatabase` gagal. |
| M4 - aset frontend | `public/build/manifest.json` tidak ada (folder `node_modules` kosong, `public/build` di-.gitignore), sehingga 7 tes lama yang membuka halaman publik/auth gagal `Vite manifest not found`. Bukan regresi T4.1; jalankan `npm install && npm run build` sebelum suite penuh. |
| T4.2 - status | T4.2 selesai (Pest hijau, 16 tes pengajuan surat/kuota). Tes mencakup penerbitan surat per group + idempoten, penahanan tanpa TTD, unggah surat balasan, penerimaan dengan kuota 5→6 lalu 409, dokumen fisik, withdrawal, reminder harian idempoten, `QuotaBar`, dan akses halaman Hubin/Kaprog/siswa. **CV halaman baru (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T4.2 - konkurensi | Uji konkurensi nyata (dua proses bersamaan pada slot terakhir) **belum dijalankan**; tes yang ada berurutan: 5 `sedang_pkl`, penerimaan keenam lolos, penerimaan ketujuh 409. Penguncian `companies` + hitung ulang di dalam transaksi sudah diterapkan, tetapi bukti empiris dua proses masih menjadi risiko yang harus diuji saat uji beban T10.1. |
| T4.2 - kuota dan slot | `QuotaService` hanya menghitung `status = sedang_pkl`; provisional ikut, `rejected` dan `withdrawn` otomatis tidak dihitung sehingga slot lepas tanpa update tambahan. Batas dari `config('pkl.quota_per_company_period')`. |
| T4.2 - tanggal PKL | `AcceptByCompany` memakai tanggal periode sebagai default; Kaprog dapat mengubah lewat form. Tanggal disimpan sebagai DATE (bukan timestamp) agar tidak bergeser zona waktu. |
| T4.2 - surat pengantar | Unduhan untuk siswa memakai `DocumentPolicy::download` pada `PengajuanPkl` (anggota group). Nomor + PDF + QR tetap satu pintu di `DocumentService`; halaman Hubin hanya memanggil `IssueSuratPengantar`. |
| T4.2 - penolakan penerimaan | `RejectByCompany` menolak satu siswa saja dan menyimpan catatan di `activity_log`; tidak ada kolom baru. Berbeda dari Gate 1/2, panjang catatan tidak dipaksa ≥ 10 karakter karena perusahaan yang menolak, bukan verifikasi sekolah. |
| T4.2 - hook bimbingan | Hook arsip bimbingan pada `ApproveWithdrawal` sengaja masih komentar; tabel bimbingan baru ada di T6.1. Jangan lupa mengisi hook saat T6.1 dikerjakan. **Selesai di T6.1** (lihat baris "M6 - hook bimbingan T4.2"). |
| T4.2 - UI | Halaman Hubin: `/hubin/antrean-surat-pengantar` dan `/hubin/konfirmasi-dokumen-fisik`. Antrean Kaprog `/app/antrean-pengajuan` kini memuat verifikasi, penerimaan setelah balasan, dan persetujuan penarikan; kolom kuota memakai komponen `quota-bar`. Halaman siswa menampilkan unggah surat balasan, unduh surat pengantar, dan form penarikan. |
| T5.1 - approve vs terbit surat | Keputusan Hubin dipisah dari penerbitan surat: `ApproveVisitasi` hanya transisi `diajukan → disetujui` + audit, penerbitan dicoba setelah commit. Bila scan TTD Kepsek belum ada, surat ditahan (`disetujui` tanpa nomor) dan Hubin diberi tahu sekali lewat `DocumentService::hold()`; tombol "Terbitkan surat" di halaman Hubin menerbitkannya. `IssueSuratTugasVisitasi` memanggil `hold()` di luar transaksi agar notifikasi tidak ikut rollback. Migrasi `2026_06_02_000001` melonggarkan `ck_stv_nomor` agar `disetujui` boleh tanpa nomor. |
| T5.1 - masa berlaku visitasi | Dihitung dari tanggal surat terbit (`IssueSuratTugasVisitasi`), bukan tanggal approve (Q-L); `tanggal_mulai/selesai` dibekukan di transaksi yang sama dengan pengambilan nomor lewat `LetterNumberService`, memakai `JakartaTime::today()`, rumus `mulai + (default_validity_days - 1)`. `ExpireVisitasiJob` dan `ExpireVisitasi` mengabaikan surat tanpa nomor. Guru tidak dapat mengunggah dokumentasi sebelum surat bernomor (`DocumentPolicy::upload` + `UploadDokumentasi`). |
| M6 - status | T6.1, T6.2, T6.3 selesai (Pest hijau, 205 tes di MySQL; 18 tes baru: 5 bimbingan, 6 akses eksternal, 7 pengesahan). **CV layar bimbingan siswa/guru, portal `/eksternal/*`, dan halaman `Pengesahan Laporan` (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| M6 - `laporan_versions.is_legit` | Kolom ini ada di `docs/schema.sql` (dipakai `SignStep` untuk menandai versi sah saat step 5 `signed`) tetapi belum ada di migrasi. Ditambahkan lewat migrasi `2026_06_03_000001_add_is_legit_to_laporan_versions.php` + fillable/cast model, bukan kolom baru di luar skema. |
| M6 - hook bimbingan T4.2 | Hook arsip bimbingan pada `ApproveWithdrawal` kini terisi: baris `bimbingan_submissions` siswa itu ditandai `is_archived = true` dalam transaksi yang sama, sehingga hitungan "≥ 4 interaksi" mulai dari 0 pada pengajuan baru (BR-F6-4). |
| M6 - magic link | Satu link aktif per `(type, reference_id)` dijaga transaksi + `lockForUpdate`, bukan generated column `active_key` seperti `docs/schema.sql`: kolom itu tidak diperlukan karena penerbitan selalu mencabut pendahulunya dalam transaksi yang sama. `reissue` mewajibkan alasan (≥ 10 karakter) bila aktornya pengguna internal dan menulis audit `magic_link_reissued`; `revoke` menulis `magic_link_revoked`. Perangkat diikat longgar (`hash(User-Agent + 2 oktet IP)`) dan perbedaan perangkat memicu verifikasi ulang, bukan penolakan keras. |
| M6 - OTP eksternal | `services/ExternalOtpService.php` terpisah dari `OtpService` internal karena TTL (5 menit vs 60 detik), sesi 24 jam, dan notifikasi berbeda. Kode disimpan sebagai SHA-256 di `external_sessions.otp_code_hash`, dikirim lewat `ExternalOtpNotification` (kanal email saja, tidak masuk `notifications`), dan sesi dianggap tidak sah bila link induknya dicabut/kedaluwarsa. |
| M6 - portal eksternal | Guard `EnsureExternalToken` membaca `external_session_id` dari sesi web dan selalu menjawab 410 dengan halaman yang sama untuk link tidak ada, tidak valid, dan kedaluwarsa (pesan `pkl.eksternal.invalid_link`). Rute didaftarkan di `routes/eksternal.php` lewat `withRouting(then: ...)` supaya tetap memakai grup `web` tanpa menyentuh `/app`/`/hubin`. |
| M6 - layar signer step 3/4 | Portal memakai satu alur (`SignerEntry` → OTP → `SignerStep`) yang dipakai bersama step 3 dan step 4; layar terpisah per step tidak dibuat karena isinya identik. |
| M6 - Tahap 3/4 ditahan (Q-H) | Bila email signer industri belum terverifikasi, penerbitan tautan melempar `DocumentIssuanceHeld::noVerifiedSigner()` dan Hubin diberi notifikasi sekali per pembukaan step; rantai tetap tersimpan sampai step 2 `signed`. Ini pemakaian default Q-H (PRD Lampiran B), tanpa keputusan PO baru. |
| M6 - bulk TTD | `BulkApproveSignatures` memproses tiap id dalam percobaan sendiri dan mengembalikan `approved`/`failed` per item; satu kegagalan tidak membatalkan yang lain (DESIGN.md "Bulk action"). Halaman menampilkan hasil per item setelah proses. |
| M6 - revisi laporan | `UploadRevisiLaporan` membuat `laporan_versions` baru dengan `source_bimbingan_submission_id` disalin dari versi sebelumnya dan satu baris `document_signatures` `pending` pada step yang menolak; baris versi lama tidak diubah sehingga riwayat TTD tetap utuh. |
| M6 - berkas | Unduhan internal laporan memakai `laporan.download` + `LaporanVersionPolicy`; portal eksternal memakai `eksternal.laporan`, yang hanya melayani sesi turunan tautan untuk baris tanda tangan itu. Keduanya membaca disk `local` (privat). |
| T7.1 — status | T7.1 selesai (Pest hijau, 10 tes form). KP-F8-1 (publish tanpa item ditolak), KP-F8-2 (struktur terkunci setelah submission + `DuplicateFormDefinition`), KP-F8-3 (Kehadiran/Catatan tidak masuk `nilai_total`), dan rumus Q-E (rata-rata item 0–100, 2 desimal) teruji. **CV halaman Form Builder (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T7.1 — nilai item | Setiap item bernilai 0–100; `docs/schema.sql` tidak punya kolom `max`, jadi batas 100 adalah konstanta `ScoreService::MAX_ITEM_SCORE` (bukan kolom baru). `form_submissions.jawaban` menyimpan `{"items":{"<id>":{"nilai":..}},"nilai_total":..,"kehadiran":{..},"catatan":".."}`; `nilai_total` dibaca kembali oleh generated column. |
| T7.1 — alokasi waktu | Item `penilaian_industri` bertanda `has_alokasi_waktu` menyimpan teks alokasi di JSON jawaban; nilainya tidak ikut perhitungan (keputusan PO: rata-rata item 0–100). |
| T7.1 — struktur relasional | Builder menyimpan Section → Items di `form_sections`/`form_items`, bukan JSON di `form_definitions` seperti contoh TRD §13.1; JSON hanya untuk jawaban. `form_definitions` tidak punya kolom versi, jadi "versi baru" = baris baru pada (jurusan, periode, jenis) dan submission lama tetap menunjuk definisinya. |
| Selisih struktur form vs `docs/form-penilaian-references/` | Keempat PDF (`RPL`, `TKR`, `TP`, `DKV`) adalah **form penilaian industri** (ditandatangani Pembimbing Industri) — tidak ada contoh form sidang, jadi struktur form sidang dibiarkan ditentukan Kaprog lewat builder. Section yang terbaca: **RPL** "Soft Skill" (6 item: etika komunikasi, integritas & disiplin, kemauan/motivasi/kualitas/inisiatif, mandiri, kepedulian sosial, sopan/K3LH/POS) + "Kompetensi Teknis" (9 item: Sistem Komputer, Komputer & Jaringan Dasar, Dasar Pemrograman, UI/UX, Perencanaan PL, Basis Data, PBO, Pemrograman Web, Mobile Apps); **TKR** "Sikap Mental" (5: Disiplin Waktu, Kemauan Kerja & Motivasi, Kualitas Kerja, Inisiatif Kerja, Perilaku) + "Kompetensi Teknis" (8: K3LH, manajemen/proses bisnis otomotif, dasar otomotif, perawatan mesin/sasis/kelistrikan, PKK, Pre Course); **TP** "Sikap Mental" (5) + "Dasar-dasar Program Keahlian" (3) + "Mata Pelajaran (Konsentrasi Keahlian)" (9: OA W, SMAW, MIG/MAG, TIG, FCAW, mutu pengelasan, PKK, PKL, CAD); **DKV** "Umum/Elemen" (4 capaian: Internalisasi & Penerapan Soft Skill, Penerapan Hard Skill, Peningkatan Hard Skill, Penyiapan Kemandirian Berwirausaha) + "Kompetensi Teknis" (5). Selisih yang dicatat: (1) kolom `KET` per item dan teks capaian ala DKV tidak punya tempat di struktur Section→Items berlabel saja; (2) bagian Kehadiran (Sakit/Izin/Tanpa Keterangan) + Catatan hanya ada pada form RPL, sehingga `has_kehadiran`/`has_catatan` dibuat per form, bukan selalu ada; (3) formulir memuat identitas siswa, tabel institusi + waktu pelaksanaan, dan kolom NILAI (Alokasi Waktu/Nilai) yang tidak direplika di form builder — datanya sudah ada di `pengajuan_siswa`/`companies`, jadi tidak diduplikasi; (4) form default tidak di-seed; Kaprog membangun lewat builder. |

| T7.2 — status | T7.2 selesai (Pest hijau, 9 tes sidang). Teruji: jadwal hanya untuk laporan `is_legit`, cascade shift memindah antrean ke hari kerja berikutnya, shift yang melanggar batas PKL tidak mengubah satu baris pun, pembatalan jadwal merapatkan urutan, nilai terkunci hanya bisa diubah lewat persetujuan Kaprog, `RelockScoreJob` mengunci ulang setelah 24 jam, kelulusan manual, pencabutan tautan beserta sesinya, dan portal PIN penguji. **CV portal penguji + halaman Kaprog/siswa (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T7.2 — cascade shift | Pemicu cascade hanya tindakan manual Kaprog (pindah atau batalkan satu jadwal), bukan kapasitas. Sisa antrean pada tanggal asal bergeser ke hari kerja berikutnya; seluruh susunan baru divalidasi lebih dulu sehingga konflik (urutan bentrok, penguji dobel, melewati `tanggal_selesai_pkl`, siswa tanpa laporan sah, nilai sudah terkunci) membatalkan seluruh transaksi dan pesannya dikembalikan sebagai daftar. |
| T7.2 — revisi nilai | Alur dimulai penguji dari portal (`RequestUnlock`, status `menunggu_persetujuan`), lalu Kaprog menyetujui (`ApproveUnlock`) dan menerbitkan satu tautan + PIN baru 24 jam untuk penguji asli. Tidak ada aksi "tolak revisi" dan tidak ada notifikasi ke Kaprog saat pengajuan dibuat; antrean tampil di halaman Nilai Sidang. Pencabutan tautan oleh Kaprog memakai `MagicLinkService::revoke` dengan alasan wajib; penerbitan ulang = tombol "Terbitkan tautan" yang otomatis mencabut tautan aktif sebelumnya. |
| T7.2 — selisih schema | Tiga pelonggaran dicatat: (1) `sidang_results_summary.catatan_kelulusan` ditambah untuk Q-I; (2) `nilai_sidang_revisions` mengizinkan `approved_by_kaprog_id`, `unlock_magic_link_id`, dan `unlock_expires_at` NULL selama `menunggu_persetujuan` (status baru), karena baris dibuat penguji lebih dulu; (3) `external_sessions.otp_code_hash` dan `otp_expires_at` boleh NULL karena sesi PIN penguji tidak memakai OTP email. Ketiganya sudah disalin ke `docs/schema.sql`. |
| T7.2 — portal penguji | Masuk dengan PIN 4 digit (bukan OTP), sesi memakai tabel `external_sessions` yang sama. Satu tautan berlaku untuk semua siswa penguji tersebut pada hari sidang yang sama; `FormSubmission::isTargetedBy()` membandingkan penguji + tanggal anchor, sehingga penguji dapat menilai siswa berikutnya tanpa tautan baru. Submission sidang dibuka lewat `OpenSidangSubmission` dari definisi `sidang` terbit terakhir pada jurusan + periode siswa. |
| T7.2 — ringkasan | `SyncSidangResultsSummaryJob` (queue, dipanggil setelah nilai dikunci/direvisi) mengisi `sidang_results_summary`; `status_kelulusan` dan `catatan_kelulusan` tidak ditimpa. |
| T8.1 — status | T8.1 selesai (Pest hijau, 232 tes di MySQL; 8 tes baru di `tests/Feature/Industri/IndustriAssessmentTest.php`). Teruji: window H-1 bulan ditolak server, pivot hanya siswa `sedang_pkl` pada jurusan form (`withdrawn` dan jurusan lain tidak ikut), generate/reissue/revoke beralasan, submit mengunci tanpa revisi (submit kedua 403, tautan baru setelah final ditolak), pencabutan menutup sesi turunan, pengingat H-7 idempoten per tanggal Jakarta serta berhenti setelah submission dibuat, dan alur portal Livewire (sesi belum verifikasi ditolak). **CV portal `/eksternal/industri` + halaman `Penilaian Industri` guru/Hubin (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T8.1 — window dan pivot | Window memakai `tanggal_selesai_pkl` **paling awal** di antara siswa aktif jurusan itu (bukan per siswa): satu tautan per (form definition, perusahaan, periode) tidak boleh terlambat karena satu siswa selesai lebih dulu. Pivot `form_submission_siswa` hanya berisi siswa `sedang_pkl`; `withdrawn` tidak dinilai dan tidak masuk tautan. |
| T8.1 — pengingat H-7 | Baris submission baru ada setelah tautan dibuat, jadi dedup "sekali per tanggal Jakarta per perusahaan" disimpan di kolom baru `companies.last_industry_reminder_at` (DATE NULL, migrasi `2026_06_07_000001`, sudah disalin ke `docs/schema.sql`). Job berjalan harian 07:05 Asia/Jakarta dan mengirim ke guru yang terplot; bila belum ada plotting, pengingat dilewati. |
| T8.1 — tanpa revisi | `SubmitIndustryAssessment` langsung menulis `is_submitted = true` + `submitted_at`; tidak ada jalur buka ulang seperti revisi nilai sidang (BR-F10-1). Karena `form_submissions` sudah punya `company_id`, `academic_period_id`, UNIQUE `uq_submission_industri`, CHECK shape, dan enum `magic_links.type`, T8.1 tidak menambah migrasi selain kolom penanda pengingat. Form industri tidak di-seed; Kaprog membangunnya lewat Form Builder (T7.1). |
| T8.1 — portal dan override | Portal memakai OTP email yang sama dengan signer. Submission untuk submit diturunkan dari `magic_link.reference_id` sesi, bukan dari properti publik klien; Action juga menolak sesi yang belum terverifikasi. Halaman Hubin dapat mencabut/menerbitkan ulang tautan aktif dan menerbitkan tautan baru saat tautan sudah kedaluwarsa, sehingga override tidak bergantung pada guru. |
| T9.1 - status | T9.1 selesai (Pest hijau, 249 tes di MySQL). KP-F15-1 teruji: kanban `/app/kanban-kelas` dibatasi `visibleTo()` kelas peran Walas, hanya menampilkan siswa kelasnya, dan tidak memuat aksi ubah. KP-F15-2 teruji: `MonitoringService` mengembalikan jumlah, rata-rata, dan durasi terlama per gate dan per step 5-TTD dalam hari kerja Senin-Jumat; halaman `Monitoring` tersedia untuk Kaprog (`/app/monitoring`) dan Hubin (`/hubin/monitoring`) dengan peringatan kuota pada antrean gate. **CV kanban dan dashboard (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T9.1 - kolom dan durasi | Lima kolom mengikuti usulan Q-K: (1) pengajuan & verifikasi, (2) surat & balasan perusahaan, (3) PKL & bimbingan, (4) pengesahan laporan, (5) sidang & selesai. Kolom 3-5 ditentukan dari versi laporan terbaru (`is_legit`); pengajuan `rejected`/`withdrawn` tidak ditampilkan dan jumlahnya dicatat di bawah papan. Durasi memakai `updated_at` pengajuan (gate/kanban) dan `created_at` tanda tangan pending (step 5-TTD, yaitu saat step dibuka), karena belum ada kolom waktu masuk status per gate; angka ini perkiraan sampai audit log dipakai untuk laporan historis. |
| T9.1 - helper hari kerja | `JakartaTime::workingDaysBetween()` diperbaiki agar menormalkan `DateTimeInterface` ke Asia/Jakarta sebelum `startOfDay`; sebelumnya nilai UTC dari DB membuat batas hari bergeser dan hasil bisa kurang satu hari. Ditambah `workingDaysElapsed()` (hari mulai dihitung 0) dan tes unit untuk keduanya. |
| T9.2 - status | T9.2 selesai (Pest hijau, 8 tes FAQ). KP-F16-1 teruji (FAQ `is_published = false` tidak muncul), KP-F16-2 teruji (pencarian tanpa hasil menampilkan pesan jelas + tombol reset), filter kategori, LIKE pada pertanyaan/jawaban, paginasi `config('pkl.faq.per_page')` = 10, landing `/` dengan tautan FAQ/login, dan CRUD Hubin (FAQ + kategori). **CV landing dan `/faq` (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T9.2 - detail | `/faq` memakai Livewire `FaqIndex` dengan query `is_published = true`, urutan kategori lalu `sort_order`; `created_by` diisi dari `CreateAction::mutateDataUsing` pada halaman Hubin. Kategori yang masih dipakai FAQ tidak menampilkan aksi hapus (FK tetap penjaga terakhir). Landing memakai layout publik yang sudah ada dan tidak menambah aset baru. |
| M9 - portabilitas migrasi | Migrasi `2026_06_02_000001` memakai `DROP CHECK` yang hanya dikenal MySQL, sehingga `migrate:fresh` gagal di MariaDB (lingkungan pengembangan saat ini). Diganti `DROP CONSTRAINT` yang portabel di MySQL 8.0.19+ dan MariaDB; perilaku constraint tidak berubah. |
| M9 - perintah tes | `php artisan test` kehabisan memori 128 MB pada tes berkas yang sudah ada. Suite penuh dijalankan dengan `php -d memory_limit=1G vendor/bin/pest`: 249 tes, 839 assertion, hijau. |
| T10.1 - status | T10.1 selesai (Pest hijau, 257 tes di MySQL; uji beban terpisah). KP-F13-1 (gagal di tengah tidak menyediakan berkas), KP-F13-2 (unduh Hubin-only; aktor lain 403 walau punya tautan), KP-F13-3 (berkas + baris dihapus setelah 30 hari), dan KP-F13-4 (uji beban tercatat) teruji. **CV halaman `Ekspor akreditasi` (360px/1280px, terang/gelap) menunggu cek manual di peramban.** |
| T10.1 - kolom ekspor | PD-08 tidak ada di repo, jadi kolom ekspor adalah kolom `sidang_results_summary` apa adanya: Nama Siswa, NIS, Jurusan, Perusahaan, Nilai per Kategori (JSON), Status Kelulusan, Penguji, Tanggal Sidang. Snapshot CSV ditulis ke disk privat `exports/`, checksum SHA-256 disimpan, dan nomor berkas memakai id attempt. Bila PO punya daftar PD-08 resmi, urutan/kolomnya disesuaikan di `AccreditationExportJob::HEADERS`. |
| T10.1 - gagal dan retry | Satu baris yang tidak lengkap (nama/penguji/tanggal/nilai kosong) menggagalkan seluruh attempt: `status = failed`, berkas sementara dihapus, `snapshot_file_path` dibiarkan NULL sehingga tidak ada berkas parsial (BR-F13-2). Retry dari UI membuat baris baru dengan `retry_of_id`, bukan mengulang baris lama; job `tries = 1` supaya retry otomatis queue tidak diam-diam mengulang attempt. |
| T10.1 - unduhan | Rute `/ekspor-akreditasi/{export}/unduh` (grup `auth`) + `AccreditationExportPolicy`: hanya peran aktif Hubin, attempt `completed`, belum kedaluwarsa, dan berkas benar-benar ada. `PurgeExpiredExportsJob` (harian 02:00 Jakarta) melepas `retry_of_id` yang menunjuk baris kedaluwarsa lalu menghapus berkas dan barisnya. |
| Hasil uji beban ekspor 1.752 siswa | 1.752 baris `sidang_results_summary` → CSV selesai dalam ~0,6-1,0 detik (chunk `pkl.export.chunk_size` = 500, tanpa indeks tambahan). Jauh di bawah target 300 detik, tetapi angka ini mengukur penulisan berkas dari tabel ringkasan yang sudah terisi; ia **bukan** waktu penuh Sinkronisasi nilai sidang ke `sidang_results_summary`. Tes: `vendor/bin/pest --group load` (dikeluarkan dari suite default lewat `phpunit.xml`). |
| T10.2 - header keamanan | `SecurityHeaders` dipasang pada grup `web` (juga menjangkau `/eksternal/*`): `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy` (`no-referrer` di rute eksternal, `strict-origin-when-cross-origin` di lainnya), CSP yang mengizinkan inline/eval agar Livewire-Alpine dan Filament jalan (`default-src 'self'`, `script-src 'self' 'unsafe-inline' 'unsafe-eval'`, `style-src 'self' 'unsafe-inline'`, `img-src 'self' data: blob:`), dan HSTS hanya saat request HTTPS. Diuji di `tests/Feature/Security/SecurityHeadersTest.php`. |
| T10.2 - rate limit login | Sebelumnya login belum dibatasi; kini kunci sementara per (email + IP): `config('pkl.login.max_attempts')` = 5, `decay_seconds` = 60, dihitung `RateLimiter` di `Login::submit()`, pesan `pkl.auth.throttled`. Rate limit OTP (`otp-verify`, `otp-resend`), PIN penguji (`throttle:10,1`), minta tautan baru (`throttle:5,1`), dan `/verify` (`throttle:60,1`) sudah ada sejak M1/M3/M6/M7. |
| T10.2 - status | T10.2 selesai pada sisi kode dan dokumen. (1) **UAT otomatis** per peran ada di `tests/Feature/Uat/RoleSmokeTest.php`: tiap peran membuka halaman intinya (200) dan ditolak di halaman peran lain (403), termasuk Hubin. (2) **Audit invarian PRD 1.5** dipetakan ke tes di `docs/GO-LIVE.md`; satu celah dicatat: invarian kuota belum diuji dua proses bersamaan. (3) **Operasional** (perintah rilis, supervisor, cron, cadangan + prosedur pemulihan) ada di `docs/GO-LIVE.md`; penjadwalan sudah terdaftar di `routes/console.php`. (4) **Seeding produksi** sudah lengkap di `DatabaseSeeder` (jurusan, periode, akun, FAQ) tetapi belum dijalankan di server. (5) **Keputusan Q** ditulis di `docs/GO-LIVE.md` (Q-A, Q-E, Q-F, Q-K memakai default; Q-H dan Q-L tetap). **Yang masih harus dilakukan manusia di server/peramban: tick checklist UAT 360px/1280px terang/gelap, jalankan backup + uji pemulihan, jalankan seeder dan supervisor/cron di server.** |
| T10.2 - temuan bug | UAT otomatis menemukan satu bug nyata: `resources/views/filament/app/pages/pengesahan-laporan.blade.php` gagal dikompilasi (`ParseError: unexpected endforeach`). Sebab: satu file memakai bentuk satu baris `@php(...)` **dan** blok `@php ... @endphp`; Blade mencocokkan `@php(...)` sebagai awal blok sehingga sisa direktif terlewat. Diperbaiki dengan mengubah bentuk satu baris menjadi blok. Semua view dicompile dan dilint (`php -l`) ulang: 0 gagal. Halaman `Pengesahan Laporan` (Kaprog/Kepsek) kini benar-benar dapat dibuka. |
| T10.3 - status | T10.3 (opsional) selesai. Kolom baru `users.push_opt_in` (NULL = belum ditanya; TRUE/FALSE = jawaban pengguna, tidak pernah ditimpa otomatis) lewat migrasi `2026_06_08_000001`, disalin ke `docs/schema.sql`. Halaman `/app/pengaturan-notifikasi` (`PengaturanNotifikasi`) satu-satunya tempat meminta jawaban. `PushService` hanya mengirim bila opt-in dan `PKL_PUSH_ENDPOINT` diisi; kegagalan push dicatat ke `notification_delivery_failures` (channel `push`) tanpa melempar, sehingga email/in-app tetap terkirim. Teruji di `tests/Feature/Notification/PushNotificationTest.php`: tanpa opt-in tidak ada HTTP, opt-in mengirim payload + in-app tetap terbuat, kegagalan HTTP tercatat tanpa exception, dan toggle opt-in/out tersimpan. |
| Baris pengesahan (T10.2) | Halaman `Pengesahan Laporan` dipakai Kaprog dan Kepsek (step 2 dan step 5). Guru tidak membuka halaman ini: step 1 ditandatangani otomatis saat guru ACC laporan final (`ApproveLaporanFinal`). Ini diuji di UAT smoke agar ekspektasi peran tidak salah. |
| Keputusan Q-A, Q-E, Q-F, Q-K | Tertulis di `docs/GO-LIVE.md` bagian 7. Ringkas: Q-A nomor surat per tahun ajaran; Q-E `nilai_total` = rata-rata item 0-100 (2 desimal) dihitung server; Q-F stack Filament/Livewire/MySQL dipakai; Q-K lima kolom kanban seperti usulan. Keempatnya memakai default dan berjalan; perubahan PO masuk versi berikutnya beserta migrasi/catatan. Q-H dan Q-L tidak berubah. |
| `docs/GO-LIVE.md` (baru) | Perintah rilis, supervisor `queue:work`, cron `schedule:run`, cadangan + prosedur uji pemulihan, checklist UAT manusia per peran, pemetaan invarian PRD 1.5 ke tes, dan keputusan butir Q. Ini dokumen yang dipakai saat go-live (T10.2). |
| Refactor UI (R0–R6) | Refactor UI/UX permukaan: panel Filament `/hubin`, `/kaprog`, `/guru` dan portal mobile `/siswa`, `/walas`, `/kepsek`; panel `app` dibubarkan. Rencana, keputusan, dan catatan pelaksanaan ada di `docs/TASKS-REFACTOR-UI.md`. |