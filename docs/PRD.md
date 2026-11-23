# PRD — Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi

**Versi**: 3.0 (revisi dari v2 terkonsolidasi)
**Status**: Draf untuk persetujuan Product Owner (PO)
**Dokumen pendamping**: `TRD.md` (implementasi teknis), `DESIGN.md` (sistem desain), `TASKS.md` (pekerjaan), `schema.sql` (skema DB), `TASKS-REFACTOR-UI.md` (rencana refactor UI/UX)
**Lokasi**: `docs/PRD.md`

## Tentang Revisi Ini

PRD ini dirapikan dari v2 agar benar-benar menjadi dokumen **produk**: apa yang dibangun, untuk siapa, dengan aturan apa, dan bagaimana kita tahu itu selesai.

| Perubahan | Keterangan |
|---|---|
| Tech stack dipindah | Bagian "Tech Stack Strategy" v2 §1.4 dipindahkan ke `TRD.md` §2. PRD hanya memuat batasan yang memengaruhi produk (§1.6). |
| Wireframe ditiadakan | Implementasi langsung, acuan visual = `DESIGN.md` (§1.7, Lampiran A16). Ketentuan v2 yang mewajibkan wireframe untuk halaman Blade kustom dicabut. |
| Struktur baku | Ditambah tujuan terukur, user story per peran, hak akses, kriteria penerimaan per fitur, persyaratan non-fungsional, glosarium. |
| Aturan diberi ID | Aturan bisnis diberi ID `BR-F{n}-{m}` dan kriteria penerimaan `KP-F{n}-{m}` supaya dapat ditelusuri dari `TASKS.md` dan tes. |
| Pertanyaan terbuka | Default tetap dipakai, tetapi kini punya status dan pemilik keputusan (Lampiran B). Ditambah satu pertanyaan baru (Q-K). |
| Isi tidak berkurang | Semua aturan bisnis, state, alur, dan edge case v2 dipertahankan. Nomor fitur F1–F16, invarian §1.5, dan ID Lampiran A/B **tidak berubah** agar rujukan TRD tetap valid. |

**Catatan rujukan lama.** v2 merujuk ke keputusan `PD-01…PD-16` dan kriteria `AC-xx` yang berasal dari dokumen sumber lain dan **tidak ada dalam berkas ini**. Rujukan itu tetap ditulis apa adanya sebagai "Ref lama". Kriteria penerimaan baru (`KP-…`) ditulis dari aturan di dokumen ini dan tidak menggantikan daftar AC asli sampai keduanya disandingkan.

---

# BAGIAN 1 — RINGKASAN PRODUK

## 1.1 Masalah dan Visi

**Masalah.** Administrasi PKL masih manual berbasis kertas:

- Siswa tidak tahu posisi pengajuannya. Panduan alur hanya cetakan di mading.
- Kaprog sulit melacak verifikasi massal dan plotting guru (Excel). Visitasi bergantung inisiatif Hubin.
- Hubin menangani dokumen fisik yang rawan hilang dan membuat surat satu per satu.
- Kepsek jarang di sekolah sehingga TTD menahan laporan berhari-hari.
- Penguji eksternal dan pembimbing industri menilai di kertas, lalu data diketik ulang ke Excel.

**Visi.** Satu sistem yang mendigitalkan alur **pengajuan → persuratan → bimbingan → pengesahan laporan (5-TTD) → sidang → penilaian**, dengan status real-time per peran, serta mekanisme anti-manipulasi: kunci nilai, token ter-hash, snapshot immutable, dan audit log. Dokumen fisik tetap dipakai hanya di titik yang memang wajib (surat balasan asli, surat ber-TTD).

## 1.2 Tujuan dan Ukuran Keberhasilan

| ID | Tujuan | Indikator | Target |
|---|---|---|---|
| G1 | Transparansi status | Setiap siswa dapat melihat posisi pengajuan, step 5-TTD, dan pihak yang ditunggu | Terpenuhi untuk seluruh status di F3 dan F7 |
| G2 | Mengurangi waktu tunggu | Durasi tunggu per step 5-TTD dan per gate pengajuan | Dipantau di dashboard; **angka target ditetapkan PO setelah baseline**. Step eksternal dipantau, tidak ditargetkan |
| G3 | Menghapus pengetikan ulang nilai | Nilai sidang dan penilaian industri diinput langsung oleh penguji/pembimbing industri | Ekspor tanpa input manual ulang |
| G4 | Integritas data | Nilai terkunci, nomor surat tanpa duplikat, rahasia ter-hash, setiap perubahan status tercatat | 0 nomor surat ganda; 0 perubahan nilai tanpa jejak |
| G5 | Kesiapan akreditasi | Ekspor akreditasi seluruh siswa aktif | < 5 menit untuk ±1.752 siswa (**target yang wajib diuji beban**, bukan janji) |
| G6 | Dapat dipakai di ponsel | Alur Siswa dan Kepsek tuntas di layar 360px | Diverifikasi di tiap checkpoint visual (`TRD.md` §17.2) |

## 1.3 Pengguna dan Hak Akses

Skala: ±1.752 siswa aktif. Pengguna Siswa dan Kepsek dominan memakai ponsel.

| Peran | Akses | Permukaan | Fungsi utama |
|---|---|---|---|
| Siswa | Akun | Portal `/siswa` | Mengajukan PKL, upload balasan, bimbingan, memantau pengesahan |
| Wali Kelas (Walas) | Akun (bisa rangkap) | Portal `/walas` | Gate TTD pengajuan, monitoring kelas (read-only) |
| Guru Pembimbing | Akun (bisa rangkap) | Panel `/guru` | Bimbingan, ACC laporan final (TTD 1), visitasi, memicu penilaian industri |
| Kaprog | Akun (bisa rangkap) | Panel `/kaprog` | Verifikasi, penerimaan, plotting, TTD 2, jadwal sidang, form builder, revisi nilai |
| Hubin | Akun | Panel `/hubin` | Master data, surat pengantar, dokumen fisik, FAQ, ekspor akreditasi |
| Kepala Sekolah (Kepsek) | Akun | Portal `/kepsek` | TTD 5 (bulk/single) |
| Penguji Eksternal | Magic link + PIN (tanpa akun) | `/eksternal/*` | Mengisi nilai sidang |
| Pembimbing & Kepala Industri | Magic link + OTP email (tanpa akun) | `/eksternal/*` | TTD 3/4 dan penilaian industri |

Panel Filament hanya untuk Hubin, Kaprog, dan Guru Pembimbing. Siswa, Walas, dan Kepsek memakai portal mobile (`/siswa`, `/walas`, `/kepsek`) dengan satu layout bersama. Satu `/login` untuk semua peran.

### Matriks Aksi Utama per Peran

| Aksi | Siswa | Walas | Guru | Kaprog | Hubin | Kepsek | Penguji | Industri |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| Mengajukan PKL / upload balasan | ✔ | | | | | | | |
| Gate 1 (relevansi) | | | | ✔ | | | | |
| Gate 2 (TTD pengajuan) | | ✔ | | | | | | |
| Terbit surat pengantar | | | | | ✔ | | | |
| Putuskan penerimaan (terima/tolak) | | | | ✔ | | | | |
| Konfirmasi dokumen fisik | | | | | ✔ | | | |
| Plotting guru | | | | ✔ | | | | |
| Ajukan visitasi | | | | ✔ | ✔ (manual) | | | |
| Approve visitasi | | | | | ✔ | | | |
| Bimbingan (unggah / feedback) | ✔ | | ✔ | | | | | |
| TTD 1 (ACC final) | | | ✔ | | | | | |
| TTD 2 | | | | ✔ | | | | |
| TTD 3 / TTD 4 | | | | | | | | ✔ |
| TTD 5 | | | | | | ✔ | | |
| Form builder | | | | ✔ | | | | |
| Jadwal sidang + tetapkan penguji | | | | ✔ | | | | |
| Isi nilai sidang | | | | | | | ✔ | |
| Setujui revisi nilai | | | | ✔ | | | | |
| Generate link penilaian industri | | | ✔ | | | | | |
| Isi penilaian industri | | | | | | | | ✔ |
| Master data, FAQ, ekspor | | | | | ✔ | | | |
| Monitoring kelas | | ✔ | | | | | | |
| Revoke/reissue link | | | ✔ (siswa bimbingannya) | ✔ | ✔ (override) | | | |

### User Story Inti

| Peran | Cerita |
|---|---|
| Siswa | Sebagai siswa, saya ingin tahu pengajuan saya sedang di tahap mana dan ditunggu siapa, agar saya tidak perlu bertanya berulang ke guru. |
| Siswa | Sebagai siswa, saya ingin mengajukan perusahaan lewat ponsel dan diberi tahu bila datanya mirip dengan perusahaan yang sudah ada, agar tidak terjadi duplikat. |
| Walas | Sebagai wali kelas, saya ingin memberi TTD pengajuan dan memantau posisi seluruh siswa kelas saya tanpa bisa mengubah data. |
| Guru | Sebagai guru pembimbing, saya ingin menerima bimbingan dan menyetujui laporan final dengan satu aksi yang langsung memulai rantai pengesahan. |
| Kaprog | Sebagai Kaprog, saya ingin memverifikasi, menerima, dan memplot dalam satu tempat dengan peringatan kuota, agar tidak ada perusahaan yang kelebihan siswa. |
| Hubin | Sebagai Hubin, saya ingin menerbitkan surat per perusahaan (bukan per siswa) dan mengekspor data akreditasi tanpa merakit Excel manual. |
| Kepsek | Sebagai Kepsek, saya ingin menandatangani banyak laporan sekaligus dari ponsel agar tidak menahan proses. |
| Penguji | Sebagai penguji eksternal, saya ingin masuk dengan link dan PIN tanpa akun lalu mengisi nilai siswa satu per satu. |
| Industri | Sebagai pembimbing industri, saya ingin mengisi satu penilaian untuk seluruh siswa di perusahaan saya, bukan satu per siswa. |

## 1.4 Cakupan

### In-Scope (MVP)

1. Autentikasi email sekolah + OTP first-login + role switch per sesi (`active_role`).
2. Master data (perusahaan + resolver duplikat, periode, akun, signer industri, TTD Kepsek), impor massal.
3. Pengajuan individu → company group → penerimaan (kuota 6, provisional) → withdrawal.
4. Plotting guru by company+periode, Surat Tugas Pembimbing (otomatis), Surat Tugas Visitasi (approval Hubin, kedaluwarsa).
5. Bimbingan laporan paralel dengan PKL; guard provisional dan guard ≥ 4 interaksi.
6. Pengesahan 5-TTD sequential dengan partial-reject.
7. Form builder (Section → Items), sidang oleh penguji eksternal, kunci nilai, revisi nilai via izin Kaprog, penilaian industri (1 link per perusahaan).
8. Nomor surat per tahun ajaran, PDF surat + QR verifikasi publik.
9. Ekspor akreditasi (queued, chunked, snapshot immutable, Hubin-only).
10. Notifikasi email + in-app (push opsional), audit log, FAQ publik, landing page, dashboard waktu tunggu.

### Non-Goals (tidak dikerjakan)

- Jurnal harian, absensi harian, kas.
- WhatsApp Business API (hanya tautan `wa.me` manual).
- Deadline/SLA otomatis provisional (hanya reminder harian pasif).
- Revisi nilai penilaian industri; rollback/reset rantai TTD.
- Pengecualian libur nasional pada hitungan SLA hari kerja.
- Integrasi antivirus/CDR sebagai syarat (opsional per deployment).
- Wireframe dan desain visual terpisah (diganti `DESIGN.md`); Redis; pencarian FAQ full-text.
- NIK wajib untuk penguji.

## 1.5 Invarian Sistem (tidak boleh dilanggar)

1. **Kuota 6**: hitung ulang `sedang_pkl` per company+periode dalam transaksi setelah `SELECT … FOR UPDATE` pada baris `companies`; provisional ikut dihitung; VIEW hanya untuk baca.
2. **Provisional mengunci bimbingan** di server; Kaprog memutuskan penerimaan, Hubin hanya mengonfirmasi dokumen fisik.
3. **5-TTD sequential, partial-reject**: step N hanya setelah N-1 `signed`; reject tidak mereset step sebelumnya.
4. **Nilai terkunci** setelah `is_submitted`; revisi hanya via izin Kaprog dan link + PIN baru untuk penguji asli.
5. **`active_role` + scope objek** pada setiap otorisasi (kelas/jurusan/ownership/token).
6. **Rahasia eksternal ter-hash** (SHA-256 dari secret acak kuat); tidak ada plaintext di DB/log.
7. **Form dinamis** = dua tingkat konten di tabel relasional (`form_definitions` → `form_sections` → `form_items`); jawaban per submission di kolom JSON. Tanpa tingkat lebih dalam, EAV, atau tabel jawaban per item.
8. **Idempoten + atomik**: submit/approval ganda tidak membuat baris/surat/notifikasi/nomor ganda; perubahan status dan audit event satu transaksi.
9. **Waktu**: DB UTC; operasional Asia/Jakarta; date-only inklusif 23:59:59 Jakarta; TTL = durasi elapsed; job harian sekali per tanggal Jakarta; SLA Senin–Jumat.

## 1.6 Asumsi dan Batasan

| Hal | Ketentuan |
|---|---|
| Satu sekolah | Sistem melayani SMKN 1 Kota Bekasi; tidak multi-tenant |
| Identitas | Email domain `@smkn1kotabekasi.sch.id`; NIS/NIP hanya password awal, bukan identifier |
| Bahasa | Seluruh antarmuka dan pesan dalam Bahasa Indonesia |
| Dokumen fisik | Surat balasan asli dan surat ber-TTD tetap fisik di titik yang wajib; sistem mencatat konfirmasinya |
| Komunikasi eksternal | Link/PIN dikirim lewat email atau tautan `wa.me` manual; tanpa WhatsApp API |
| Hari kerja | SLA Senin–Jumat; libur nasional tidak dikecualikan |
| Perangkat | Siswa dan Kepsek dominan ponsel; layar minimum 360px |
| Teknologi | Satu bahasa (PHP). Panel Filament untuk Hubin, Kaprog, dan Guru Pembimbing; portal mobile Blade/Livewire untuk Siswa, Walas, dan Kepsek dengan satu layout bersama. Detail dan trade-off di `TRD.md` §2; persetujuan PO atas penggantian stack tercatat di Lampiran B (Q-F) |

## 1.7 Prinsip Proses dan Desain

- **Tanpa wireframe, langsung implementasi.** Acuan visual adalah `DESIGN.md` (palet 60/30/10, mode terang dan gelap, komponen bersama). Konsistensi tata letak dijaga lewat komponen bersama dan checkpoint visual di akhir tiap milestone (`TRD.md` §14, §17.2).
- **Perilaku produk = dokumen ini.** Konflik antar dokumen: PRD → TRD → `schema.sql` → `DESIGN.md` → `TASKS.md`.
- **Server adalah otoritas.** Tombol disabled hanyalah petunjuk; setiap aturan di dokumen ini wajib ditegakkan di server.
- **Dokumen fisik tetap hanya di titik wajib**; selebihnya digital.

---

# BAGIAN 2 — SPESIFIKASI FITUR

Rute: publik `/`, `/faq`, `/login`, `/verify/{token}`; panel Filament `/hubin`, `/kaprog`, `/guru`; portal mobile `/siswa`, `/walas`, `/kepsek`; eksternal `/eksternal/*`.

Format tiap fitur: **Aktor → Alur → Aturan bisnis (BR) → Edge case → Kriteria penerimaan (KP)**.

---

## F1 — Autentikasi, OTP First-Login, Role Switch

**Aktor**: semua pengguna berakun.

**Alur**
1. User membuka `/login`, mengisi email + password (awal = NIS/NIP).
2. Server memvalidasi domain `@smkn1kotabekasi.sch.id` saat submit, sebelum OTP.
3. Jika `is_first_login = true`: kirim OTP 6 digit (berlaku 1 menit) → verifikasi → wajib ganti password → `is_first_login = false` → dashboard.
4. User multi-role memilih peran lewat dropdown; server menyimpan `active_role` di sesi dan memvalidasi bahwa user memilikinya.

**Aturan bisnis**
- BR-F1-1: NIS/NIP bukan identifier.
- BR-F1-2: OTP 6 digit, ter-hash, maks 3 percobaan verify (percobaan ke-4 menginvalidasi OTP).
- BR-F1-3: Resend maks 5x per 30 menit lalu cooldown 30 menit; limiter `otp-verify` dan `otp-resend` terpisah.
- BR-F1-4: Password baru ≥ 8 karakter dan ≠ NIS/NIP, dengan pesan validasi spesifik.
- BR-F1-5: Setiap aksi memeriksa `active_role` + scope; peran tidak aktif → 403.

**Edge case**
- Domain salah → ditolak saat submit, tanpa mengungkap apakah akun ada.
- OTP kedaluwarsa/habis percobaan → minta OTP baru.
- Email gagal terkirim → retry transient (maks 3x), kegagalan permanen dicatat.
- Akun nonaktif → ditolak. Pergantian peran tidak membocorkan data peran sebelumnya.

**Kriteria penerimaan**
- KP-F1-1: Email berdomain lain ditolak sebelum OTP, dengan respons yang sama untuk akun ada/tidak ada.
- KP-F1-2: OTP ke-4 yang salah menginvalidasi OTP; resend ke-6 dalam 30 menit ditolak sampai cooldown selesai.
- KP-F1-3: Password sama dengan NIS/NIP atau kurang dari 8 karakter ditolak dengan pesan spesifik.
- KP-F1-4: User tanpa peran X yang memaksa `active_role = X` mendapat 403.
- KP-F1-5: Pergantian peran memperbarui layar tanpa reload penuh dan tanpa menampilkan data peran sebelumnya. (Ref lama: AC-32)

---

## F2 — Master Data (Hubin)

**Aktor**: Hubin.

**Alur**: Hubin mengelola perusahaan, periode akademik (tahun ajaran + batch 1/2, kunci pengajuan baru), akun (filter Guru/Siswa, reset OTP), signer industri, unggah scan TTD Kepsek, merge perusahaan, impor massal.

**Aturan bisnis**
- BR-F2-1: Strict-unique (dinormalisasi): `email`, `website`, `telp_fax`.
- BR-F2-2: Nama/alamat memakai soft-warning (Levenshtein). Resolver yang sama untuk form siswa dan impor.
- BR-F2-3: Signer Pembimbing/Kepala Industri harus nama individu + email aktif terverifikasi; jabatan generik ditolak sebagai penerima OTP. (Ref lama: AC-52)
- BR-F2-4: Merge perusahaan: set `merged_into_id`, pindahkan referensi dalam transaksi, simpan jejak audit.
- BR-F2-5: Hanya satu scan TTD Kepsek aktif (diunggah Hubin, Q-G).

**Edge case**
- Impor sebagian gagal → laporan baris bermasalah; baris valid tetap masuk (transaksi per baris).
- Merge ke diri sendiri ditolak.
- Periode yang punya data terkait tidak boleh dihapus.

**Kriteria penerimaan**
- KP-F2-1: Perusahaan dengan email/website/telp yang sama (setelah normalisasi) ditolak; nama/alamat mirip hanya memunculkan peringatan.
- KP-F2-2: Impor 100 baris dengan 5 baris salah memasukkan 95 baris dan menghasilkan laporan 5 baris bermasalah.
- KP-F2-3: Setelah merge, seluruh referensi menunjuk perusahaan tujuan dan ada entri audit.
- KP-F2-4: Signer dengan jabatan generik tidak dapat dipakai untuk mengirim OTP.

---

## F3 — Pengajuan PKL (Individu → Company Group → Penerimaan)

**Aktor**: Siswa, Kaprog, Walas, Hubin.

**State**: `draft → menunggu_verifikasi_kaprog → (ditolak_kaprog → draft) → menunggu_ttd_walas → (ditolak_walas → draft) → menunggu_surat_pengantar → surat_pengantar_terbit → menunggu_balasan_perusahaan → sedang_pkl | rejected`; `sedang_pkl → withdrawn` (approval Kaprog). Transisi hanya oleh aktor dan status sumber yang ditentukan.

**Alur**
1. Siswa memilih/mengetik perusahaan (autocomplete + autofill; kemiripan nama → modal konfirmasi sebelum simpan). Kontak CP di-mask sampai disetujui Kaprog.
2. Submit → snapshot JSON perusahaan disimpan immutable → `menunggu_verifikasi_kaprog`.
3. **Gate 1**: Kaprog (jurusannya) menyetujui relevansi (belum memakai kuota). **Gate 2**: Walas (kelasnya) TTD.
4. Setelah Walas: job grouping memasukkan siswa ke company group (`company_id + academic_period_id`) → `menunggu_surat_pengantar`.
5. Hubin menerbitkan surat pengantar per group (nomor + PDF + QR) → `surat_pengantar_terbit`; siswa konfirmasi ke perusahaan → upload surat balasan → `menunggu_balasan_perusahaan`.
6. Kaprog memutuskan: perusahaan menerima → `sedang_pkl` (set `is_provisional` sesuai dokumen fisik, tanggal PKL default dari periode, kuota dihitung ulang dalam lock) atau menolak → `rejected` (hanya siswa itu).
7. Hubin mencatat dokumen fisik diterima → `is_provisional = false` (tidak mengubah keputusan Kaprog).
8. Kaprog plotting guru (F4). Siswa susulan: proses penuh dari awal; mewarisi guru yang sudah diplot.
9. Withdrawal: siswa/Kaprog mengajukan dengan alasan; Kaprog menyetujui → `withdrawn`, slot lepas, bimbingan di-archive.

**Aturan bisnis**
- BR-F3-1: Maks 1 pengajuan aktif per siswa per periode (unique DB); aktif = selain `rejected`/`withdrawn`.
- BR-F3-2: Penolakan (Kaprog/Walas) wajib catatan ≥ 10 karakter; histori tetap terbaca.
- BR-F3-3: Kuota 6 per company+periode, lintas semua company group; `rejected` dan `withdrawn` melepas slot.
- BR-F3-4: Reminder harian provisional ke siswa (email + in-app), tanpa eskalasi.
- BR-F3-5: Tanggal PKL per siswa disalin dari periode saat Kaprog menerima; Kaprog dapat mengubah (Q-C).
- BR-F3-6: Pengajuan ulang setelah `withdrawn` = record baru.

**Edge case**
- Approval bersamaan saat kuota 5 → satu berhasil, lainnya 409 tanpa efek samping.
- Double submit/double klik → idempoten.
- Perusahaan baru dari siswa belum punya signer → boleh lanjut sampai TTD 3; Hubin diberi tahu bila belum lengkap saat rantai mencapai step 3 (Q-H).
- Periode terkunci → submit baru ditolak dengan pesan jelas.

**Kriteria penerimaan**
- KP-F3-1: Siswa dengan pengajuan aktif tidak dapat membuat pengajuan kedua di periode yang sama (ditolak di server, bukan hanya UI).
- KP-F3-2: Penolakan dengan catatan < 10 karakter ditolak.
- KP-F3-3: Dua approval bersamaan saat kuota 5 menghasilkan tepat satu `sedang_pkl` dan satu 409.
- KP-F3-4: Perubahan data perusahaan setelah submit tidak mengubah snapshot pengajuan.
- KP-F3-5: Siswa `rejected`/`withdrawn` melepas slot; kuota langsung bertambah di dashboard.
- KP-F3-6: Satu surat pengantar mencakup seluruh siswa dalam satu company group.
- KP-F3-7: Konfirmasi dokumen fisik oleh Hubin tidak mengubah status, hanya `is_provisional`.

---

## F4 — Plotting Guru dan Surat Tugas Pembimbing

**Aktor**: Kaprog (plotting), sistem (surat), Hubin (menerima notifikasi bila TTD Kepsek belum ada).

**Alur**: Kaprog membuka daftar company yang sudah punya placement resmi (minimal satu siswa `sedang_pkl` pada company+periode itu) → memilih guru (indikator beban kerja; soft-warning multi-wilayah) → simpan → sistem otomatis menerbitkan Surat Tugas Pembimbing (nomor baru, TTD Kepsek otomatis, tanpa approval Hubin).

**Aturan bisnis**
- BR-F4-1: 1 company + 1 periode = 1 guru; 1 plotting = 1 surat.
- BR-F4-2: Siswa susulan otomatis terhubung ke guru yang sudah ada.
- BR-F4-3: Scan TTD Kepsek belum ada → plotting boleh tersimpan tetapi penerbitan surat ditahan dan Hubin diberi tahu.
- BR-F4-4: Penggantian guru membuat surat baru dengan jejak audit (Q-J).
- BR-F4-5: Status `guru_terplot` diturunkan dari ada tidaknya baris plotting (Lampiran A3). (Ref lama: AC-15, AC-16, AC-20)

**Kriteria penerimaan**
- KP-F4-1: Plotting kedua pada company+periode yang sama ditolak (atau diperlakukan sebagai penggantian dengan surat baru).
- KP-F4-2: Tanpa scan TTD Kepsek, plotting tersimpan, surat tidak terbit, dan Hubin menerima notifikasi.
- KP-F4-3: Siswa susulan langsung muncul di daftar bimbingan guru yang sudah diplot.

---

## F5 — Visitasi dan Surat Tugas Visitasi

**Aktor**: Kaprog, Hubin, Guru.

**Alur**: Kaprog mengajukan (multi-select perusahaan atau general) atau Hubin membuat manual → Hubin approve/reject (alasan wajib bila reject; keputusan tidak menunggu TTD) → surat terbit (nomor + PDF) bila scan TTD Kepsek tersedia, bila belum surat ditahan tanpa nomor sampai TTD diunggah → Kaprog mencetak → guru memakai selama masa berlaku dan mengunggah dokumentasi (jpg/png ≤ 5MB).

**Aturan bisnis**
- BR-F5-1: Default masa berlaku 7 hari dihitung dari tanggal surat terbit; `tanggal_selesai` inklusif 23:59:59 Jakarta (Q-L).
- BR-F5-2: Job harian menandai `kedaluwarsa`; guru tidak bisa memakai surat kedaluwarsa.
- BR-F5-3: Reject → Kaprog boleh mengajukan ulang.
- BR-F5-4: Pengajuan untuk perusahaan yang bukan milik guru ditolak.

**Edge case**: job berjalan ulang tidak mengubah status dua kali.

**Kriteria penerimaan**
- KP-F5-1: Reject tanpa alasan ditolak.
- KP-F5-2: Surat dengan `tanggal_selesai` hari ini masih berlaku sampai 23:59:59 Jakarta.
- KP-F5-3: Unggah dokumentasi di luar jpg/png atau > 5MB ditolak (422).

---

## F6 — Bimbingan Laporan

**Aktor**: Siswa, Guru.

**Alur**: Siswa `sedang_pkl` dan tidak provisional mengunggah bimbingan (file + keterangan) berulang → guru memberi feedback (revisi/diterima) → setelah ≥ 4 interaksi (tidak ter-archive) siswa menandai "Bagian Akhir" → guru "Approve Laporan Final" = TTD 1 + memicu rantai (1 aksi).

**Aturan bisnis**
- BR-F6-1: Guard server `is_provisional = false`.
- BR-F6-2: Guard server ≥ 4 interaksi.
- BR-F6-3: File pdf/doc/docx ≤ 10MB; MIME dari deteksi konten asli; disimpan private.
- BR-F6-4: Withdrawal → histori di-archive; hitungan mulai 0 pada record baru.

**Edge case**: provisional → ditolak walau request langsung ke endpoint; file ganda/salah tipe → 422.

**Kriteria penerimaan**
- KP-F6-1: Siswa provisional yang memanggil endpoint unggah langsung mendapat penolakan.
- KP-F6-2: "Bagian Akhir" dengan 3 interaksi ditolak; dengan 4 interaksi diterima.
- KP-F6-3: File `.exe` yang diganti ekstensi `.pdf` ditolak berdasarkan isi.
- KP-F6-4: "Approve Laporan Final" menghasilkan `laporan_versions` v1, step 1 `signed`, dan step 2 `pending` dalam satu aksi.

---

## F7 — Pengesahan Laporan (5-TTD)

**Urutan**: Guru Pembimbing → Kaprog → Pembimbing Industri (magic link + OTP) → Kepala Industri (magic link + OTP) → Kepsek.

**Alur**
1. ACC guru membuat `laporan_versions` v1 dan baris step 1 `signed`, lalu membuka step 2 (`pending`).
2. Tiap sign membuka step berikutnya; link eksternal diterbitkan hanya setelah step sebelumnya `signed`.
3. Reject di step mana pun (catatan ≥ 10 karakter) → siswa unggah revisi → versi baru → kembali langsung ke step yang menolak; step sebelumnya tetap `signed`.
4. Setelah TTD 5 → laporan legit → masuk pool Siap Sidang.

**Aturan bisnis**
- BR-F7-1: Bulk approve Kaprog/Kepsek memproses tiap laporan secara independen.
- BR-F7-2: Dashboard durasi tunggu per step (KPI dipantau, bukan ditarget untuk step eksternal).
- BR-F7-3: Tidak ada rollback/reset rantai.

**Edge case**
- Signer industri tanpa email terverifikasi → langkah ditahan dan Hubin diberi tahu.
- Link kedaluwarsa → Kaprog/Hubin menerbitkan ulang.
- Sign ganda → idempoten.

**Kriteria penerimaan**
- KP-F7-1: Step 3 tidak dapat ditandatangani sebelum step 2 `signed` (juga lewat request langsung).
- KP-F7-2: Reject di step 4 mengembalikan laporan ke step 4 setelah revisi, tanpa mengubah step 1–3.
- KP-F7-3: Bulk approve 10 laporan dengan 1 gagal memproses 9 laporan dan melaporkan 1 kegagalan beserta alasan.
- KP-F7-4: Link eksternal step 4 tidak ada sebelum step 3 `signed`.
- KP-F7-5: Setelah step 5 `signed`, laporan muncul di pool Siap Sidang.

---

## F8 — Form Builder (Section → Items)

**Aktor**: Kaprog.

**Alur**: Kaprog membuat form (tipe `sidang` atau `penilaian_industri`) per jurusan + periode: Section sejajar → Items; opsi "Section Kehadiran" (Sakit/Izin/Tanpa Keterangan) dan "Section Catatan" (free-input) → publish.

**Aturan bisnis**
- BR-F8-1: Struktur dua tingkat relasional (`form_sections` → `form_items`); setelah ada submission, struktur terkunci (ubah = versi/form baru).
- BR-F8-2: Jawaban di kolom JSON dengan generated column `nilai_total` (rumus: Q-E).

**Edge case**: form tanpa item tidak dapat dipublish; hapus section/item yang sudah dipakai ditolak.

**Kriteria penerimaan**
- KP-F8-1: Publish form tanpa item ditolak.
- KP-F8-2: Mengubah struktur form yang sudah punya submission ditolak; menawarkan pembuatan versi baru.
- KP-F8-3: Section Kehadiran dan Catatan tidak memengaruhi `nilai_total`.

---

## F9 — Sidang dan Nilai

**Aktor**: Kaprog, Penguji Eksternal, Hubin (revoke/reissue).

**Alur**
1. Kaprog menjadwalkan siswa dari pool Siap Sidang dan menetapkan penguji (nama + kontak terverifikasi).
2. Sistem menerbitkan magic link + PIN 4 digit (dikirim lewat email/`wa.me`).
3. Penguji masuk, mengisi nilai, "Simpan & Selesaikan" → nilai terkunci → tampilan pindah ke siswa berikutnya tanpa reload penuh.
4. Cascade shift menggeser sisa antrean ke hari berikutnya (atomik).
5. **Revisi nilai**: Kaprog menyetujui unlock → link + PIN baru (1 form, 24 jam) ke penguji asli → penguji submit ulang; TTL habis → auto re-lock, nilai lama berlaku.

**Aturan bisnis**
- BR-F9-1: PIN salah 3x → invalid; Kaprog menerbitkan ulang.
- BR-F9-2: Binding perangkat longgar (langkah verifikasi tambahan, bukan penolakan keras).
- BR-F9-3: Kaprog/Hubin dapat revoke/reissue. (Ref lama: PD-10)
- BR-F9-4: Link sidang berlaku sampai akhir hari sidang (Jakarta) (Q-D).
- BR-F9-5: Status kelulusan opsional, diisi manual oleh Kaprog (Q-I).

**Edge case**
- Update setelah lock → 403.
- Cascade shift gagal validasi → jadwal awal utuh + alasan konflik.
- Siswa belum legit tidak bisa dijadwalkan.

**Kriteria penerimaan**
- KP-F9-1: Setelah "Simpan & Selesaikan", request update nilai yang sama mendapat 403.
- KP-F9-2: Cascade shift yang gagal tidak mengubah satu pun jadwal dan menampilkan konflik.
- KP-F9-3: Unlock menghasilkan link + PIN baru yang hanya bisa membuka satu form dan hanya untuk penguji asli.
- KP-F9-4: Setelah 24 jam tanpa submit ulang, nilai kembali terkunci dengan nilai lama.
- KP-F9-5: Siswa tanpa laporan legit tidak muncul pada pilihan penjadwalan.
- KP-F9-6: PIN ke-4 yang salah ditolak sampai Kaprog menerbitkan ulang.

---

## F10 — Penilaian Industri

**Aktor**: Guru, Pembimbing Industri, Hubin.

**Alur**: pada window H-1 bulan sebelum akhir PKL (dari `tanggal_selesai_pkl`), Guru Pembimbing membuka halaman detail perusahaan → "Generate Link" → 1 link + 1 submission untuk seluruh siswa bimbingannya di perusahaan itu (pivot) → Pembimbing Industri mengisi (OTP email) → submit final. Reminder H-7 harian bila link belum dibuat.

**Aturan bisnis**
- BR-F10-1: Tanpa revisi.
- BR-F10-2: `form_submissions.pengajuan_siswa_id` NULL; siswa dihubungkan via `form_submission_siswa`.
- BR-F10-3: Guru (siswa bimbingannya) dan Hubin (override beralasan) dapat revoke/reissue.
- BR-F10-4: Form/link industri per form definition, yaitu satu per jurusan (Q-B).

**Edge case**: akses ulang setelah submit ditolak; siswa `withdrawn` tidak ikut pivot; tombol hanya muncul di window, tetapi server tetap memeriksa.

**Kriteria penerimaan**
- KP-F10-1: "Generate Link" sebelum window ditolak di server.
- KP-F10-2: Satu submission mencakup semua siswa bimbingan aktif di perusahaan itu dan tidak mencakup siswa `withdrawn`.
- KP-F10-3: Membuka link setelah submit final ditolak.
- KP-F10-4: Reminder H-7 terkirim sekali per hari hanya bila link belum dibuat.

---

## F11 — Magic Link dan Portal Eksternal (lintas fitur)

**Aturan bisnis**
- BR-F11-1: Token acak ≥ 32 byte; simpan SHA-256; single-use; revocable; 1 token aktif per (type, target); reissue = revoke lama + terbit baru secara atomik.
- BR-F11-2: Guard terpisah dari sesi internal; layout minimal; respons hanya data untuk satu tugas.
- BR-F11-3: Revoke membatalkan seluruh sesi turunan. Override Hubin wajib alasan di audit.
- BR-F11-4: Pesan galat tidak membocorkan keberadaan target; ada alur mudah "minta link baru" lewat Kaprog/Guru/Hubin.
- BR-F11-5: OTP eksternal 5 menit, maks 3 percobaan, sesi 24 jam; link pengesahan/penilaian industri 7 hari (Q-D).

**Kriteria penerimaan**
- KP-F11-1: Menerbitkan link kedua untuk (type, target) yang sama otomatis mencabut link pertama.
- KP-F11-2: Link yang dicabut menutup sesi aktif yang berasal darinya.
- KP-F11-3: Token mentah tidak muncul di basis data maupun log.
- KP-F11-4: Link tidak valid, kedaluwarsa, dan tidak ada menghasilkan pesan yang sama.

---

## F12 — Dokumen, Nomor Surat, QR

**Aturan bisnis**
- BR-F12-1: Counter per (tahun ajaran, jenis surat) diambil dalam transaksi (`FOR UPDATE`); nomor unik per tahun ajaran; nomor yang rollback tidak menghasilkan duplikat (Q-A).
- BR-F12-2: PDF surat memuat kop, nomor, TTD Kepsek (scan), QR → `/verify/{token}`.
- BR-F12-3: `/verify/{token}` hanya menampilkan nomor surat, status, tanggal terbit; token tidak valid/dicabut → pesan netral.
- BR-F12-4: PDF dibuat lewat queue; kegagalan tidak membuat nomor "terpakai tanpa dokumen"; retry idempoten. (Ref lama: AC-46, AC-47)
- BR-F12-5: Template mengacu pada contoh di `docs/old physical dokument examples/`.

**Kriteria penerimaan**
- KP-F12-1: Dua penerbitan bersamaan menghasilkan nomor berurutan tanpa duplikat.
- KP-F12-2: Kegagalan pembuatan PDF lalu retry menghasilkan dokumen dengan nomor yang sama, bukan nomor baru.
- KP-F12-3: Halaman verifikasi tidak menampilkan data selain tiga field yang diizinkan.

---

## F13 — Ekspor Akreditasi

**Aktor**: Hubin.

**Alur**: Hubin klik ekspor → attempt `queued` → job chunk 500 → validasi kelengkapan → `completed` + notifikasi tautan → unduh (Hubin-only) hingga 30 hari lalu dihapus otomatis.

**Aturan bisnis**
- BR-F13-1: Field sesuai PD-08 saja.
- BR-F13-2: Gagal/parsial = `failed`, tidak dapat diunduh; retry = attempt baru.
- BR-F13-3: Target < 5 menit untuk 1.752 siswa wajib diuji beban (bukan janji).

**Kriteria penerimaan**
- KP-F13-1: Ekspor yang gagal di tengah tidak menyediakan file untuk diunduh.
- KP-F13-2: Peran selain Hubin tidak dapat mengunduh walau memiliki tautan.
- KP-F13-3: File ekspor terhapus otomatis setelah 30 hari.
- KP-F13-4: Hasil uji beban 1.752 siswa tercatat di `TASKS.md`.

---

## F14 — Notifikasi dan Job Terjadwal

**Aturan bisnis**
- BR-F14-1: Email + in-app wajib; push opt-in/best-effort, tidak diprompt ulang setelah ditolak.
- BR-F14-2: Email retry transient maks 3x; kegagalan permanen tercatat untuk Hubin.
- BR-F14-3: Job harian (sekali per tanggal Jakarta): reminder provisional, reminder H-7 penilaian industri, kedaluwarsa visitasi, hapus ekspor kedaluwarsa. Job per jam: kedaluwarsa magic link, auto re-lock nilai.
- BR-F14-4: Semua job idempoten dan mencatat kegagalan tanpa rahasia.

**Kriteria penerimaan**
- KP-F14-1: Menjalankan job harian dua kali pada tanggal Jakarta yang sama hanya memberi satu efek.
- KP-F14-2: Kegagalan email permanen tampak oleh Hubin tanpa menampilkan isi rahasia.

---

## F15 — Monitoring

**Aturan bisnis**
- BR-F15-1: Walas: kanban read-only 5 kolom, hanya kelasnya (definisi kolom: Q-K).
- BR-F15-2: Kaprog/Hubin: dashboard durasi tunggu per step 5-TTD; antrean gate dengan peringatan kuota.

**Kriteria penerimaan**
- KP-F15-1: Walas tidak melihat siswa kelas lain dan tidak memiliki kontrol ubah.
- KP-F15-2: Dashboard menampilkan durasi tunggu per step berdasarkan hari kerja Senin–Jumat.

---

## F16 — FAQ dan Landing (publik)

**Aturan bisnis**
- BR-F16-1: Landing + `/faq` tanpa login; hanya `is_published = true`.
- BR-F16-2: Filter kategori, pencarian `LIKE`, 7–10 item per halaman.
- BR-F16-3: Hubin CRUD kategori dan FAQ.
- BR-F16-4: Kasus kosong/tanpa hasil ditampilkan dengan pesan jelas.

**Kriteria penerimaan**
- KP-F16-1: FAQ `is_published = false` tidak muncul di publik.
- KP-F16-2: Pencarian tanpa hasil menampilkan pesan jelas.

---

# BAGIAN 3 — PERSYARATAN NON-FUNGSIONAL

Item bertanda **(usulan)** belum ada di dokumen sumber dan perlu konfirmasi PO.

| Area | Persyaratan |
|---|---|
| Keamanan | Rahasia (token, PIN, OTP) ter-hash; pesan galat netral; upload dideteksi dari isi file; file private diakses lewat controller berotorisasi (Ref lama: PD-09); detail kontrol di `TRD.md` §16 |
| Integritas | Invarian §1.5 ditegakkan di server dan, bila mungkin, di database (CHECK/UNIQUE) |
| Audit | Setiap perubahan status mencatat aktor, `active_role`, objek, status lama→baru, alasan; satu transaksi dengan perubahan |
| Performa | Ekspor akreditasi 1.752 siswa < 5 menit (diuji beban); halaman daftar memakai paginasi dan scope di query |
| Responsif | Portal mobile (`/siswa`, `/walas`, `/kepsek`) valid di 360px; panel Filament (`/hubin`, `/kaprog`, `/guru`) valid di ponsel dan desktop; mode terang dan gelap; target sentuh ≥ 44px |
| Aksesibilitas | Status tidak hanya dibedakan warna; kontras sesuai aturan `DESIGN.md` **(usulan: target WCAG AA)** |
| Lokalisasi | Bahasa Indonesia; format tanggal Asia/Jakarta |
| Keandalan | Job idempoten dan dapat diulang; kegagalan tercatat; email retry maks 3x |
| Privasi | Kontak CP perusahaan di-mask sampai disetujui Kaprog; tidak ada NIK penguji |
| Cadangan | DB dan penyimpanan file private dicadangkan terjadwal **(usulan)** |
| Kompatibilitas | Peramban modern pada ponsel dan desktop **(usulan: daftar versi ditetapkan saat M0)** |

---

# BAGIAN 4 — DOKUMEN TERKAIT DAN STRUKTUR FOLDER

```
docs/
├── form-penilaian-references/          # referensi form penilaian → sumber F8, F9, F10
├── old physical dokument examples/     # contoh dokumen fisik → sumber template F12
├── PRD.md                              # dokumen ini
├── TRD.md                              # implementasi teknis
├── TASKS.md                            # pekerjaan bertahap
├── TASKS-REFACTOR-UI.md                # rencana refactor UI/UX
└── schema.sql                          # skema MySQL
```

`DESIGN.md` berada di root repo, bukan di `docs/`.

| Dokumen | Peran | Aturan pembaruan |
|---|---|---|
| `PRD.md` | Sumber kebenaran perilaku produk | Perubahan aturan bisnis dibuat di sini lebih dulu |
| `TRD.md` | Cara membangun | Tidak boleh bertentangan dengan PRD |
| `schema.sql` | Bentuk data | Diperbarui bersama setiap perubahan skema |
| `DESIGN.md` | Acuan visual (pengganti wireframe) | Diperbarui bila komponen/warna berubah |
| `TASKS.md` | Pekerjaan dan hasil uji beban | Menautkan setiap task ke ID `BR-`/`KP-` |

---

# LAMPIRAN A — LOG KEPUTUSAN KONSOLIDASI

A1–A15 dipertahankan dari v2. A16–A17 baru pada v3.

| ID | Temuan | Keputusan | Status |
|---|---|---|---|
| A1 | `pengajuan_siswa.pengajuan_pkl_id` NOT NULL padahal grouping setelah Walas; `company_id` pilihan siswa tak punya kolom | Tambah `company_id`; `pengajuan_pkl_id` NULLABLE + CHECK wajib terisi sejak `surat_pengantar_terbit`; composite FK | Diterapkan di skema |
| A2 | Kolom signer `companies` NOT NULL bertabrakan dengan perusahaan baru buatan siswa | NULLABLE + CHECK nama/email wajib jika `verified_at` terisi; guard di step 3/4 | Diterapkan di skema |
| A3 | `guru_terplot` dipakai AC-15 tetapi tidak ada di enum; status company group tak terdefinisi | Status group: `terbentuk/surat_terbit/berjalan/bubar`; `guru_terplot` = ada baris plotting. **AC-15/AC-16 perlu diselaraskan** | Perlu penyelarasan AC |
| A4 | Nomor surat reset per tahun ajaran vs PD-13/skema per periode | Counter per `tahun_ajaran`. Menyimpang dari PD-13/AC-47 | **Menunggu PO (Q-A)** |
| A5 | Tidak ada tanggal PKL per siswa | `tanggal_mulai_pkl`, `tanggal_selesai_pkl`, default dari periode | Diterapkan |
| A6 | Tidak ada penyimpanan TTD Kepsek untuk surat otomatis (AC-20) | Tabel `signature_assets` | Diterapkan |
| A7 | Task PDF surat pengantar bergantung counter + PDF dasar | Counter + PDF dasar dikerjakan sebelum penerbitan surat (TRD M3 sebelum M4) | Diterapkan di urutan |
| A8 | `FULLTEXT` di `faqs` tidak dipakai | Dihapus | Diterapkan |
| A9 | Stack v1 (Inertia + React + shadcn + Sanctum + Fortify) menambah lapisan | Diganti Filament/Livewire (`TRD.md` §2) | **Menunggu PO (Q-F)** |
| A10 | Surat balasan, keputusan penerimaan, approval withdrawal tak punya kolom | Kolom ditambahkan di `pengajuan_siswa` | Diterapkan |
| A11 | UNIQUE `user_roles` tak efektif saat scope NULL; `kelas.walas_id` duplikat | Generated key `jurusan_key/kelas_key`; `walas_id` dihapus | Diterapkan |
| A12 | Tidak ada tabel dokumentasi visitasi dan token QR | `visitasi_dokumentasi`, `document_qr_tokens` | Diterapkan |
| A13 | Invarian belum dijaga DB | CHECK/UNIQUE ditambahkan (`TRD.md` §6.2) | Diterapkan |
| A14 | `otp_codes.resend_count/cooldown_until` ambigu | Dihitung dari baris per 30 menit + RateLimiter; `invalidated_at` | Diterapkan |
| A15 | AC-33 memakai tanggal 20/21 Okt; AC-46/47 bergantung A4 | Perbarui AC bersamaan keputusan Q-A | Menunggu Q-A |
| A16 | v2 mewajibkan wireframe untuk halaman Blade kustom | **Wireframe ditiadakan**; acuan visual = `DESIGN.md`; verifikasi lewat checkpoint visual | Keputusan baru |
| A17 | Tech stack tercampur di PRD | Dipindah ke `TRD.md` §2; PRD hanya memuat batasan produk | Keputusan baru |

# LAMPIRAN B — KEPUTUSAN PRODUK DAN PERTANYAAN TERBUKA

Default tetap dipakai sampai PO memutuskan. Kolom **Status** membedakan yang butuh persetujuan eksplisit.

| ID | Pertanyaan | Default yang dipakai | Status |
|---|---|---|---|
| Q-A | Counter nomor surat per **tahun ajaran** (batch 1 & 2 berbagi) atau per **periode** (PD-13)? | Per tahun ajaran, agar tidak ada dua surat bernomor sama dalam satu tahun | **Perlu persetujuan PO** (menyimpang dari PD-13/AC-47) |
| Q-B | Perusahaan yang menerima siswa dari > 1 jurusan: plotting dan form industri per jurusan atau per perusahaan? | Plotting 1 guru per company+periode; form/link industri per form definition (satu per jurusan) | Default dipakai |
| Q-C | Sumber tanggal PKL per siswa? | Disalin dari periode saat Kaprog menerima; Kaprog dapat mengubah | Default dipakai |
| Q-D | TTL magic link dan OTP eksternal? | Link 7 hari; OTP 5 menit, maks 3 percobaan; sesi 24 jam; link sidang sampai akhir hari sidang (Jakarta) | Default dipakai |
| Q-E | Rumus `nilai_total` dan skala nilai per item? | Rata-rata item, skala 0–100, dihitung server saat submit | **Perlu konfirmasi PO** (tidak ada di dokumen sumber) |
| Q-F | Setujui penggantian stack (A9)? | Ya; bila tidak, kembali ke TRD v1 dan wireframe wajib | **Perlu persetujuan PO** |
| Q-G | Siapa mengunggah scan TTD Kepsek? | Hubin | Default dipakai |
| Q-H | Perusahaan baru dari siswa tanpa signer: boleh jalan sampai step 3? | Ya; Hubin diberi tahu saat rantai mencapai step 3 | Default dipakai |
| Q-I | Status kelulusan sidang: siapa/rumus? | Opsional, diisi manual Kaprog; kosong bila tidak ada | Default dipakai |
| Q-J | Penggantian guru setelah plotting: perlu surat baru? | Ya, surat baru dengan jejak audit | Default dipakai |
| **Q-K** *(baru)* | Apa definisi 5 kolom kanban Walas? v2 hanya menyebut "5 kolom". | Lima kolom mengikuti posisi pengajuan siswa: (1) Pengajuan & verifikasi, (2) Surat & balasan perusahaan, (3) PKL & bimbingan, (4) Pengesahan laporan (5-TTD), (5) Sidang & selesai | **Perlu keputusan PO** (usulan, bukan dari sumber) |
| **Q-L** *(baru)* | Masa berlaku visitasi dihitung dari tanggal approve atau tanggal terbit surat? | Dari tanggal terbit surat; surat yang tertahan (TTD Kepsek belum diunggah) tidak memotong masa berlaku, dan tanggal dibekukan saat nomor dialokasikan | **Keputusan PO: dari tanggal terbit** |

# LAMPIRAN C — GLOSARIUM

| Istilah | Arti |
|---|---|
| Kaprog | Kepala Program Keahlian (jurusan) |
| Hubin | Hubungan Industri, pengelola administrasi PKL tingkat sekolah |
| Walas | Wali Kelas |
| Kepsek | Kepala Sekolah |
| Company group | Kelompok siswa pada satu perusahaan dan satu periode (`company_id + academic_period_id`); dasar penerbitan surat pengantar |
| Provisional | Siswa sudah diterima Kaprog (`sedang_pkl`, ikut kuota) tetapi dokumen fisik balasan belum dikonfirmasi Hubin; bimbingan terkunci |
| Plotting | Penetapan satu guru pembimbing untuk satu company+periode |
| 5-TTD | Rantai lima tanda tangan pengesahan laporan: Guru, Kaprog, Pembimbing Industri, Kepala Industri, Kepsek |
| Partial-reject | Penolakan di step N tidak mereset step sebelumnya; setelah revisi kembali ke step N |
| Legit | Laporan yang sudah melewati TTD 5; syarat masuk pool Siap Sidang |
| Pool Siap Sidang | Daftar siswa dengan laporan legit yang belum dijadwalkan |
| Magic link | Tautan sekali pakai untuk pengguna tanpa akun, diverifikasi dengan PIN/OTP |
| `active_role` | Peran yang sedang dipakai dalam sesi; menentukan menu dan hak akses |
| Snapshot | Salinan data perusahaan yang disimpan permanen pada pengajuan saat submit |
| Kuota | Maksimum 6 siswa per perusahaan per periode |
| Cascade shift | Penggeseran sisa antrean sidang ke hari berikutnya secara atomik |
