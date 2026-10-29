# PRD — Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi (v2, terkonsolidasi)

**Sumber**: `PRD.md`, `TRD.md`, `MILESTONES.md`, `schema.sql` versi awal. Dokumen ini mengonsolidasikan keempatnya, menyelesaikan konflik antar-dokumen (Lampiran A), dan menjadi dasar `schema.sql` v2 serta `TASKS.md` (tahap 2).
**Status**: Draf untuk persetujuan Product Owner. Keputusan PD-01 s/d PD-16 dipertahankan kecuali yang ditandai di Lampiran A/B.
**Aturan konflik**: perilaku produk = dokumen ini; implementasi = TRD §10–11 selama tidak bertentangan dengan Lampiran A.

---

# BAGIAN 1 — RINGKASAN PRD (HIGH-LEVEL PLAN)

## 1.1 Problem Statement & Vision

**Masalah**: administrasi PKL masih manual berbasis kertas. Akibatnya:

- Siswa tidak tahu posisi pengajuannya; panduan alur hanya cetakan di mading.
- Kaprog sulit melacak verifikasi massal dan plotting guru (Excel); visitasi bergantung inisiatif Hubin.
- Hubin menangani dokumen fisik yang rawan hilang dan membuat surat satu per satu.
- Kepsek jarang di sekolah sehingga TTD menahan laporan berhari-hari.
- Penguji eksternal dan pembimbing industri menilai di kertas, lalu data diketik ulang ke Excel.

**Vision**: satu sistem yang mendigitalkan alur pengajuan → persuratan → bimbingan → pengesahan laporan (5-TTD) → sidang → penilaian, dengan status real-time per peran, serta mekanisme anti-manipulasi (kunci nilai, token ter-hash, snapshot immutable, audit log). Dokumen fisik tetap dipakai hanya di titik yang memang wajib (surat balasan asli, surat ber-TTD).

## 1.2 Target User

| Peran | Akses | Fungsi utama |
|---|---|---|
| Siswa | Akun | Mengajukan PKL, upload balasan, bimbingan, memantau pengesahan |
| Wali Kelas | Akun (bisa rangkap) | Gate TTD pengajuan, monitoring kelas (read-only) |
| Guru Pembimbing | Akun (bisa rangkap) | Bimbingan, ACC laporan final (TTD 1), visitasi, memicu penilaian industri |
| Kaprog | Akun (bisa rangkap) | Verifikasi, penerimaan, plotting, TTD 2, jadwal sidang, form builder, revisi nilai |
| Hubin | Akun, panel admin | Master data, surat pengantar, dokumen fisik, FAQ, ekspor akreditasi |
| Kepala Sekolah | Akun, mobile | TTD 5 (bulk/single) |
| Penguji Eksternal | Magic link + PIN (tanpa akun) | Mengisi nilai sidang |
| Pembimbing & Kepala Industri | Magic link + OTP email (tanpa akun) | TTD 3/4 dan penilaian industri |

Skala: ±1.752 siswa aktif. Pengguna siswa dan Kepsek dominan di ponsel.

## 1.3 Core Value & Scope

**In-Scope (MVP)**

1. Autentikasi email sekolah + OTP first-login + role switch per sesi (`active_role`).
2. Master data (perusahaan + resolver duplikat, periode, akun, signer industri, TTD Kepsek), impor massal.
3. Pengajuan individu → company group → penerimaan (kuota 6, provisional) → withdrawal.
4. Plotting guru by company+periode, Surat Tugas Pembimbing (otomatis), Surat Tugas Visitasi (approval Hubin, kedaluwarsa).
5. Bimbingan laporan paralel dengan PKL; guard provisional dan guard ≥4 interaksi.
6. Pengesahan 5-TTD sequential dengan partial-reject.
7. Form builder (Section→Items), sidang oleh penguji eksternal, kunci nilai, revisi nilai via izin Kaprog, penilaian industri (1 link per perusahaan).
8. Nomor surat per tahun ajaran, PDF surat + QR verifikasi publik.
9. Ekspor akreditasi (queued, chunked, snapshot immutable, Hubin-only).
10. Notifikasi email + in-app (push opsional), audit log, FAQ publik, landing page, dashboard waktu tunggu.

**Non-Goals (TIDAK dikerjakan)**

- Jurnal harian, absensi harian, kas.
- WhatsApp Business API (hanya tautan `wa.me` manual).
- Deadline/SLA otomatis provisional (hanya reminder harian pasif).
- Revisi nilai penilaian industri; rollback/reset rantai TTD.
- Pengecualian libur nasional pada hitungan SLA hari kerja.
- Integrasi antivirus/CDR sebagai syarat (opsional per deployment).
- Design system/wireframe final; Redis; pencarian FAQ full-text.
- NIK wajib untuk penguji.

## 1.4 Tech Stack Strategy (hasil evaluasi ulang: efisien token)

**Rekomendasi**: satu bahasa (PHP) dan satu framework UI untuk seluruh peran.

| Layer | Pilihan | Alasan efisiensi |
|---|---|---|
| Backend | Laravel (versi stabil terbaru yang didukung saat implementasi; cek `composer.json`) | Satu pola controller/Action/Policy untuk semua modul |
| UI internal | **Filament multi-panel + Livewire**: panel `hubin` (desktop) dan panel `app` (Siswa, Walas, Guru, Kaprog, Kepsek; navigasi mengikuti `active_role`) | Tabel, form, filter, bulk action, notifikasi tersedia bawaan; tidak ada lapisan API/JSON terpisah |
| UI publik & eksternal | Blade + Livewire ringan (landing, FAQ, login/OTP, `/verify/{token}`, `/eksternal/*`) | Layout minimal, tanpa bundle SPA |
| Styling | Tema Filament + Tailwind bawaan | Tanpa shadcn/ui |
| Auth | Session auth bawaan Laravel + halaman Livewire kustom untuk OTP; tanpa Fortify/Sanctum | Tidak ada SPA/token API yang perlu diamankan |
| Database | MySQL 8.0.16+ (JSON, CHECK, generated column) | Sesuai skema |
| Queue/Cache | Driver `database` | Skala MVP, tanpa Redis |
| Storage | Disk lokal private, akses via controller berotorisasi | Sesuai PD-09 |
| Paket pendukung | `spatie/laravel-activitylog`, PDF (dompdf), QR (endroid/qr-code), ekspor xlsx chunked (maatwebsite/excel queue) | Satu paket per kebutuhan |
| Test | Pest + Livewire testing | Satu runner untuk scope, state machine, kuota |

**Dibuang dari TRD v1**: Inertia, React, shadcn/ui, Sanctum, Fortify, aturan wajib wireframe Stitch per halaman React. Wireframe tetap diperlukan hanya untuk halaman Blade kustom (landing, portal eksternal, kanban Walas).

**Trade-off yang harus disetujui PO**: kebebasan tata letak lebih kecil dibanding React kustom; kebutuhan "smooth session switching" (AC-32) dipenuhi lewat pembaruan parsial Livewire. Responsif 360px diverifikasi di tiap checkpoint, bukan diasumsikan. **Cadangan**: jika satu halaman gagal memenuhi AC, boleh memakai komponen Alpine/JS lokal untuk halaman itu saja, tanpa memindahkan stack.

**Konvensi untuk AI Agent (hemat token)**

- 1 state machine = 1 Enum + 1 transition table + 1 Action class per transisi (menolak transisi tak dikenal).
- Semua aturan bisnis di Action/Policy/Service; UI hanya memanggil. Tombol disabled bukan otorisasi.
- Nama file dan class konsisten dengan `TASKS.md`; jangan menambah paket/abstraksi di luar daftar tanpa task.

## 1.5 Invarian Sistem (tidak boleh dilanggar)

1. **Kuota 6**: hitung ulang `sedang_pkl` per company+periode dalam transaksi setelah `SELECT … FOR UPDATE` pada baris `companies`; provisional ikut dihitung; VIEW hanya untuk baca.
2. **Provisional mengunci bimbingan** di server; Kaprog memutuskan penerimaan, Hubin hanya mengonfirmasi dokumen fisik.
3. **5-TTD sequential, partial-reject**: step N hanya setelah N-1 `signed`; reject tidak mereset step sebelumnya.
4. **Nilai terkunci** setelah `is_submitted`; revisi hanya via izin Kaprog dan link+PIN baru untuk penguji asli.
5. **`active_role` + scope objek** pada setiap otorisasi (kelas/jurusan/ownership/token).
6. **Rahasia eksternal ter-hash** (SHA-256 dari secret acak kuat); tidak ada plaintext di DB/log.
7. **Form dinamis** = MySQL JSON dua tingkat; tanpa JSONB, nested, atau EAV.
8. **Idempoten + atomik**: submit/approval ganda tidak membuat baris/surat/notifikasi/nomor ganda; perubahan status dan audit event satu transaksi.
9. **Waktu**: DB UTC; operasional Asia/Jakarta; date-only inklusif 23:59:59 Jakarta; TTL = durasi elapsed; job harian sekali per tanggal Jakarta; SLA Senin–Jumat.

---

# BAGIAN 2 — SPESIFIKASI FITUR & LOGIKA BISNIS

Rute: publik `/`, `/faq`, `/login`, `/verify/{token}`; panel Hubin `/hubin`; panel `app` `/app`; eksternal `/eksternal/*`.

## F1 — Autentikasi, OTP First-Login, Role Switch

**User Flow**
1. User membuka `/login`, mengisi email + password (awal = NIS/NIP).
2. Server memvalidasi domain `@smkn1kotabekasi.sch.id` saat submit, sebelum OTP. NIS/NIP bukan identifier.
3. Jika `is_first_login = true`: kirim OTP 6 digit (berlaku 1 menit) → verifikasi → wajib ganti password → `is_first_login = false` → dashboard.
4. User multi-role memilih peran lewat dropdown; server menyimpan `active_role` di sesi dan memvalidasi bahwa user memilikinya.

**Rule & Validasi**
- OTP: 6 digit, ter-hash, maks 3 percobaan verify (percobaan ke-4 menginvalidasi OTP); resend maks 5x/30 menit lalu cooldown 30 menit; limiter `otp-verify` dan `otp-resend` terpisah.
- Password baru ≥ 8 karakter dan ≠ NIS/NIP; pesan validasi spesifik.
- Setiap aksi memeriksa `active_role` + scope; role tidak aktif → 403.

**Edge Cases**
- Domain salah → ditolak saat submit; tanpa pengungkapan apakah akun ada.
- OTP kedaluwarsa/habis percobaan → minta OTP baru; email gagal terkirim → retry transient (maks 3x) dan catat kegagalan permanen.
- Akun nonaktif → ditolak. Pergantian peran tidak membocorkan data peran sebelumnya.

## F2 — Master Data (Hubin)

**User Flow**: Hubin mengelola perusahaan, periode akademik (tahun ajaran + batch 1/2, kunci pengajuan baru), akun (filter Guru/Siswa, reset OTP), signer industri, unggah scan TTD Kepsek, merge perusahaan, impor massal.

**Rule & Validasi**
- Strict-unique: `email`, `website`, `telp_fax` (dinormalisasi). Nama/alamat: soft-warning (Levenshtein) — resolver yang sama untuk form siswa dan impor.
- Signer Pembimbing/Kepala Industri: nama individu + email aktif terverifikasi; jabatan generik ditolak untuk dipakai OTP (AC-52).
- Merge perusahaan: arahkan `merged_into_id`, pindahkan referensi dalam transaksi, simpan jejak audit.
- Hanya satu scan TTD Kepsek aktif.

**Edge Cases**: impor sebagian gagal → laporan baris bermasalah, baris valid tetap masuk (dalam transaksi per baris); merge ke diri sendiri ditolak; periode dengan data terkait tidak boleh dihapus.

## F3 — Pengajuan PKL (Individu → Company Group → Penerimaan)

**State**: `draft → menunggu_verifikasi_kaprog → (ditolak_kaprog→draft) → menunggu_ttd_walas → (ditolak_walas→draft) → menunggu_surat_pengantar → surat_pengantar_terbit → menunggu_balasan_perusahaan → sedang_pkl | rejected`; `sedang_pkl → withdrawn` (approval Kaprog). Transisi hanya oleh aktor dan status sumber yang ditentukan.

**User Flow**
1. Siswa memilih/mengetik perusahaan (autocomplete + autofill; kemiripan nama → modal konfirmasi sebelum simpan). Kontak CP di-mask sampai disetujui Kaprog.
2. Submit → snapshot JSON perusahaan disimpan immutable → `menunggu_verifikasi_kaprog`.
3. **Gate 1** Kaprog (jurusannya) menyetujui relevansi (belum memakai kuota) → **Gate 2** Walas (kelasnya) TTD.
4. Setelah Walas: job grouping memasukkan siswa ke company group (`company_id + academic_period_id`) → status `menunggu_surat_pengantar`.
5. Hubin menerbitkan surat pengantar per group (nomor + PDF + QR) → `surat_pengantar_terbit`; siswa konfirmasi ke perusahaan → upload surat balasan → `menunggu_balasan_perusahaan`.
6. Kaprog memutuskan: perusahaan menerima → `sedang_pkl` (set `is_provisional` sesuai dokumen fisik, tanggal PKL default dari periode, kuota dihitung ulang dalam lock) atau menolak → `rejected` (hanya siswa itu).
7. Hubin mencatat dokumen fisik diterima → `is_provisional = false` (tidak mengubah keputusan Kaprog).
8. Kaprog plotting guru by company+periode (F4). Siswa susulan: proses penuh dari awal; mewarisi guru yang sudah diplot.
9. Withdrawal: siswa/Kaprog mengajukan dengan alasan; Kaprog menyetujui → `withdrawn`, slot lepas, bimbingan di-archive.

**Rule & Validasi**
- Maks 1 pengajuan aktif per siswa per periode (unique DB); aktif = selain `rejected`/`withdrawn`.
- Penolakan (Kaprog/Walas) wajib catatan ≥ 10 karakter; histori tetap terbaca (audit log).
- Kuota 6 per company+periode, lintas semua company group; `rejected` dan `withdrawn` melepas slot.
- Reminder harian provisional ke siswa (email + in-app), tanpa eskalasi.

**Edge Cases**
- Approval bersamaan saat kuota 5 → satu berhasil, lainnya 409 tanpa efek samping.
- Double submit/double klik → idempoten (tidak ada baris/surat/notifikasi ganda).
- Perusahaan baru dari siswa belum punya signer → boleh lanjut sampai TTD 3; Hubin diberi tahu bila belum lengkap saat rantai mencapai step 3.
- Periode terkunci → submit baru ditolak dengan pesan jelas. Pengajuan ulang setelah `withdrawn` = record baru.

## F4 — Plotting Guru & Surat Tugas Pembimbing

**User Flow**: Kaprog membuka daftar company yang sudah punya placement resmi → memilih guru (indikator beban kerja; soft-warning multi-wilayah) → simpan → sistem otomatis menerbitkan Surat Tugas Pembimbing (nomor baru, TTD Kepsek otomatis, tanpa approval Hubin).

**Rule**: 1 company + 1 periode = 1 guru; 1 plotting = 1 surat; siswa susulan otomatis terhubung ke guru yang ada.
**Edge Cases**: scan TTD Kepsek belum ada → plotting boleh tersimpan tetapi penerbitan surat ditahan dan Hubin diberi tahu; penggantian guru membuat surat baru dengan jejak audit (usulan; lihat Lampiran B Q-J).

## F5 — Visitasi & Surat Tugas Visitasi

**User Flow**: Kaprog mengajukan (multi-select perusahaan atau general) atau Hubin membuat manual → Hubin approve/reject (alasan wajib bila reject) → nomor surat terbit → Kaprog mencetak → guru memakai selama masa berlaku dan mengunggah dokumentasi (jpg/png ≤ 5MB).

**Rule**: default masa berlaku 7 hari; `tanggal_selesai` inklusif 23:59:59 Jakarta; job harian menandai `kedaluwarsa`; guru tidak bisa memakai surat kedaluwarsa. Reject → Kaprog boleh mengajukan ulang.
**Edge Cases**: pengajuan melampaui perusahaan yang bukan milik guru ditolak; job berjalan ulang tidak mengubah status dua kali.

## F6 — Bimbingan Laporan

**User Flow**: siswa `sedang_pkl` dan tidak provisional mengunggah bimbingan (file + keterangan) berulang → guru memberi feedback (revisi/diterima) → setelah ≥ 4 interaksi (tidak ter-archive) siswa menandai "Bagian Akhir" → guru "Approve Laporan Final" = TTD 1 + memicu rantai (1 aksi).

**Rule & Validasi**: guard server `is_provisional = false`; guard server ≥ 4 interaksi; file pdf/doc/docx ≤ 10MB, MIME dari deteksi konten asli, disimpan private.
**Edge Cases**: provisional → ditolak walau request langsung ke endpoint; withdrawal → histori di-archive, hitungan mulai 0 pada record baru; file ganda/salah tipe → 422.

## F7 — Pengesahan Laporan (5-TTD)

**Urutan**: G.Pembimbing → Kaprog → P.Industri (magic link + OTP) → K.Industri (magic link + OTP) → Kepsek.

**User Flow**: ACC guru membuat `laporan_versions` v1 dan baris step 1 `signed`, lalu membuka step 2 (`pending`). Tiap sign membuka step berikutnya; link eksternal diterbitkan hanya setelah step sebelumnya `signed`. Reject di step mana pun (catatan ≥ 10 karakter) → siswa unggah revisi → versi baru → kembali langsung ke step yang menolak; step sebelumnya tetap `signed`. Setelah TTD 5 → laporan legit → masuk pool Siap Sidang.

**Rule**: bulk approve Kaprog/Kepsek memproses tiap laporan secara independen; dashboard durasi tunggu per step (KPI dipantau, bukan ditarget untuk step eksternal).
**Edge Cases**: signer industri tanpa email terverifikasi → langkah ditahan dan Hubin diberi tahu; link kedaluwarsa → Kaprog/Hubin menerbitkan ulang; sign ganda → idempoten.

## F8 — Form Builder (JSON Hybrid)

**User Flow**: Kaprog membuat form (sidang/penilaian_industri) per jurusan+periode: Section sejajar → Items; opsi "Section Kehadiran" (Sakit/Izin/Tanpa Keterangan) dan "Section Catatan" (free-input) → publish.
**Rule**: struktur 2 level; setelah ada submission, struktur terkunci (ubah = versi/form baru); jawaban di kolom JSON dengan generated column `nilai_total`.
**Edge Cases**: form tanpa item tidak dapat dipublish; hapus section/item bila sudah dipakai ditolak.

## F9 — Sidang & Nilai

**User Flow**: Kaprog menjadwalkan siswa dari pool Siap Sidang dan menetapkan penguji (nama + kontak terverifikasi) → sistem menerbitkan magic link + PIN 4 digit (dikirim lewat email/`wa.me`) → penguji masuk, mengisi nilai, "Simpan & Selesaikan" → nilai terkunci → tampilan pindah ke siswa berikutnya tanpa reload penuh. Cascade shift menggeser sisa antrean ke hari berikutnya (atomik).
**Revisi nilai**: Kaprog menyetujui unlock → link+PIN baru (1 form, 24 jam) ke penguji asli → penguji submit ulang; TTL habis → auto re-lock, nilai lama berlaku.
**Rule**: PIN salah 3x → invalid, Kaprog menerbitkan ulang; binding perangkat longgar (langkah verifikasi tambahan); Kaprog/Hubin dapat revoke/reissue sesuai PD-10.
**Edge Cases**: update setelah lock → 403; cascade shift gagal validasi → jadwal awal utuh + alasan konflik; siswa belum legit tidak bisa dijadwalkan.

## F10 — Penilaian Industri

**User Flow**: pada window H-1 bulan sebelum akhir PKL (dihitung dari `tanggal_selesai_pkl`), Guru Pembimbing membuka halaman detail perusahaan → "Generate Link" → 1 link + 1 submission untuk seluruh siswa bimbingannya di perusahaan itu (pivot) → P.Industri mengisi (OTP email) → submit final. Reminder H-7 harian bila link belum dibuat.
**Rule**: tanpa revisi; `form_submissions.pengajuan_siswa_id` NULL, siswa via `form_submission_siswa`; Guru (siswa bimbingannya) dan Hubin (override beralasan) dapat revoke/reissue.
**Edge Cases**: akses ulang setelah submit → ditolak; siswa withdrawn tidak ikut pivot; tombol hanya muncul di window, tetapi server tetap memeriksa.

## F11 — Magic Link & Portal Eksternal (lintas fitur)

- Token acak ≥ 32 byte, simpan SHA-256, single-use, revocable, 1 token aktif per (type, target); reissue = revoke lama + terbit baru secara atomik.
- Guard terpisah dari sesi internal; layout minimal; respons hanya data untuk satu tugas.
- Revoke membatalkan seluruh sesi turunan. Override Hubin wajib alasan di audit.
- Pesan galat tidak membocorkan keberadaan target; ada alur mudah "minta link baru" lewat Kaprog/Guru/Hubin.

## F12 — Dokumen, Nomor Surat, QR

- Counter per (tahun ajaran, jenis surat) diambil dalam transaksi (`FOR UPDATE`); unique nomor per tahun ajaran; nomor yang rollback tidak menghasilkan duplikat.
- PDF surat: kop, nomor, TTD Kepsek (scan), QR → `/verify/{token}` menampilkan hanya nomor surat, status, tanggal terbit; token tidak valid/dicabut → pesan netral.
- Generate PDF lewat queue; kegagalan tidak membuat nomor "terpakai tanpa dokumen" (nomor dan dokumen satu transaksi logis, retry idempoten).

## F13 — Ekspor Akreditasi

**User Flow**: Hubin klik ekspor → attempt `queued` → job chunk 500 → validasi kelengkapan → `completed` + notifikasi tautan → unduh (Hubin-only) hingga 30 hari lalu dihapus otomatis.
**Rule**: field sesuai PD-08 saja; gagal/parsial = `failed`, tidak dapat diunduh; retry = attempt baru; target < 5 menit untuk 1.752 siswa harus diuji beban (bukan janji).

## F14 — Notifikasi & Job Terjadwal

- Email + in-app wajib; push opt-in/best-effort, tidak diprompt ulang setelah ditolak; email retry transient maks 3x; kegagalan permanen tercatat untuk Hubin.
- Job harian (sekali per tanggal Jakarta): reminder provisional, reminder H-7 penilaian industri, kedaluwarsa visitasi, hapus ekspor kedaluwarsa. Job per jam: kedaluwarsa magic link, auto re-lock nilai. Semua job idempoten dan mencatat kegagalan tanpa rahasia.

## F15 — Monitoring

- Walas: kanban read-only 5 kolom, hanya kelasnya.
- Kaprog/Hubin: dashboard durasi tunggu per step 5-TTD; antrean gate dengan peringatan kuota.

## F16 — FAQ & Landing (publik)

- Landing + `/faq` tanpa login; hanya `is_published = true`; filter kategori, pencarian `LIKE`, 7–10 item per halaman.
- Hubin CRUD kategori dan FAQ. Kasus kosong/tanpa hasil ditampilkan dengan pesan jelas.

---

# LAMPIRAN A — HASIL AUDIT & KEPUTUSAN PERBAIKAN

| ID | Temuan | Perbaikan di v2 |
|---|---|---|
| A1 | `pengajuan_siswa.pengajuan_pkl_id` NOT NULL, padahal grouping terjadi setelah Walas; `company_id` pilihan siswa tidak punya kolom | Tambah `company_id`; `pengajuan_pkl_id` NULLABLE + CHECK wajib terisi sejak `surat_pengantar_terbit`; composite FK (group, company, periode) |
| A2 | Kolom signer `companies` NOT NULL, bertabrakan dengan perusahaan baru buatan siswa | NULLABLE + CHECK nama/email wajib jika `verified_at` terisi; guard di step 3/4 |
| A3 | `guru_terplot` dipakai AC-15 tetapi tidak ada di enum; status company group tidak didefinisikan | Status group: `terbentuk/surat_terbit/berjalan/bubar`; `guru_terplot` = ada baris plotting. **AC-15/AC-16 perlu diselaraskan** |
| A4 | PRD: nomor surat reset per tahun ajaran; PD-13/schema: per academic_period; `nomor_surat` UNIQUE global | Counter per `tahun_ajaran`; UNIQUE (tahun_ajaran, nomor_urut/nomor_surat). **Menyimpang dari PD-13/AC-47 → butuh persetujuan PO (Q-A)** |
| A5 | Tidak ada tanggal PKL per siswa untuk window H-1 bulan / H-7 | `tanggal_mulai_pkl`, `tanggal_selesai_pkl`, default dari periode saat penerimaan |
| A6 | Tidak ada penyimpanan TTD Kepsek untuk surat otomatis (AC-20) | Tabel `signature_assets` |
| A7 | Urutan milestone: task 3.8 (PDF surat pengantar) bergantung 7.1/7.2 | Di `TASKS.md`, counter + PDF dasar dipindah sebelum penerbitan surat |
| A8 | `FULLTEXT` di `faqs` tidak dipakai (keputusan `LIKE`) | Dihapus |
| A9 | Tech stack: Inertia+React+shadcn+Sanctum+Fortify menambah lapisan | Diganti Filament/Livewire (§1.4) — butuh persetujuan PO |
| A10 | Surat balasan, keputusan penerimaan, approval withdrawal tidak punya kolom | Kolom ditambahkan di `pengajuan_siswa` |
| A11 | UNIQUE `user_roles` tidak efektif saat scope NULL; `kelas.walas_id` duplikat sumber kebenaran | Generated key `jurusan_key/kelas_key`; `walas_id` dihapus |
| A12 | Tidak ada tabel dokumentasi visitasi dan token verifikasi QR | `visitasi_dokumentasi`, `document_qr_tokens` |
| A13 | Invarian belum dijaga DB (provisional, kode signature per step, 1 submission per jadwal/perusahaan, ekspor completed) | CHECK/UNIQUE ditambahkan |
| A14 | `otp_codes.resend_count/cooldown_until` ambigu (per baris OTP) | Dihitung dari baris per 30 menit + RateLimiter; `invalidated_at` |
| A15 | AC-33 memakai tanggal 20/21 Okt dan AC-46/47 memakai jargon yang bergantung A4 | Perbarui AC bersamaan dengan keputusan Q-A |

# LAMPIRAN B — PERTANYAAN TERBUKA (DEFAULT YANG SAYA PAKAI)

| ID | Pertanyaan | Default |
|---|---|---|
| Q-A | Counter nomor surat per **tahun ajaran** (batch 1 & 2 berbagi) atau per **periode** (PD-13)? | Per tahun ajaran, agar tidak ada dua surat bernomor sama dalam satu tahun |
| Q-B | Perusahaan yang menerima siswa dari >1 jurusan: plotting dan form industri per jurusan atau satu per perusahaan? | Plotting tetap 1 guru per company+periode (sesuai PRD); form/link industri per form definition (satu per jurusan) |
| Q-C | Tanggal PKL per siswa: sumber? | Disalin dari periode saat Kaprog menerima; Kaprog dapat mengubah |
| Q-D | TTL magic link pengesahan/penilaian industri dan OTP eksternal? | Link 7 hari; OTP 5 menit, maks 3 percobaan; sesi 24 jam; link sidang berlaku sampai akhir hari sidang (Jakarta) |
| Q-E | Rumus `nilai_total` dan skala nilai per item? | Rata-rata item, skala 0–100, dihitung server saat submit (belum ada di dokumen sumber) |
| Q-F | Setujui penggantian stack (A9)? | Ya; bila tidak, pakai TRD v1 dan wireframe Stitch wajib |
| Q-G | Siapa mengunggah scan TTD Kepsek? | Hubin |
| Q-H | Perusahaan baru dari siswa tanpa signer: boleh jalan sampai step 3? | Ya, Hubin diberi tahu saat rantai mencapai step 3 |
| Q-I | Status kelulusan sidang: ditentukan siapa/rumus? | Opsional, diisi manual oleh Kaprog; kosong bila tidak ada |
| Q-J | Penggantian guru pembimbing setelah plotting: perlu surat baru? | Ya, surat baru dengan jejak audit |
