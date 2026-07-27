# Product Requirements Document (PRD)
## Sistem Informasi PKL (SI-PKL) — SMKN 1 Kota Bekasi

**Versi:** 1.3 (lengkap)
**Tanggal:** 27 Juli 2026
**Status:** Final — siap eksekusi per modul/fitur

---

## 1. Latar Belakang

SMKN 1 Kota Bekasi masih mengelola proses Praktik Kerja Lapangan (PKL) secara manual/semi-manual — mulai dari pengajuan tempat PKL, penerbitan surat pengantar, monitoring kunjungan, hingga penilaian akhir. Proses ini melibatkan banyak pihak (siswa, Kaprog, Guru Pembimbing, Hubin) dan rawan miskomunikasi, surat tercecer, serta sulit dilacak progresnya karena tidak ada sistem pencatatan status maupun histori yang terpusat.

SI-PKL dibangun sebagai sistem digital terpusat untuk mengelola seluruh siklus PKL: pengajuan, verifikasi, persuratan, pembimbingan/monitoring, hingga penilaian akhir dan penutupan status PKL siswa.

Proyek ini dikerjakan oleh tim 3 orang sebagai bagian dari kegiatan PKL tim itu sendiri di tempat PKL (bukan proyek sekolah sampingan), dengan jam kerja mengikuti jam PKL (09.00–16.00).

## 2. Tujuan

1. Mendigitalkan seluruh alur pengajuan PKL siswa, dari pengajuan hingga status Resmi PKL.
2. Menyediakan alur persuratan otomatis (Surat Pengantar, Surat Balasan, Surat Tugas Monitoring) dengan penomoran surat konsisten dan dapat dicetak/diarsipkan sebagai PDF.
3. Memberi Kaprog kendali penuh atas assignment Guru Pembimbing untuk siswa jurusannya.
4. Menyediakan mekanisme monitoring kunjungan (global maupun scoped per jurusan) yang terikat pada Surat Tugas resmi dari Hubin.
5. Menyediakan sistem penilaian akhir PKL yang terstruktur (indikator umum & teknis kejuruan, dengan sub-indikator opsional) dan otomatis menghitung Nilai Akhir.
6. Menyediakan audit trail atas setiap perubahan status pengajuan PKL.
7. Menyediakan notifikasi in-app untuk setiap event penting di sistem.
8. Mengeliminasi ketergantungan pada surat/berkas fisik yang rawan hilang, dengan mengarsipkan seluruh dokumen PKL secara digital.
9. Memberi visibilitas progres PKL siswa secara real-time ke seluruh pihak terkait (siswa, Kaprog, Guru Pembimbing, Hubin).

## 3. Ruang Lingkup & Role

Empat role dalam sistem:

| Role | Deskripsi Singkat |
|---|---|
| **Siswa** | Mengajukan PKL, upload dokumen, memantau status, mengisi Laporan Akhir |
| **Kaprog** | Memverifikasi pengajuan siswa jurusannya, assign Guru Pembimbing, kelola akun Kaprog di jurusan lain (nonaktifkan) |
| **Guru Pembimbing** | Membimbing & memonitor siswa yang di-assign, mengisi Laporan Kunjungan dan Formulir Penilaian |
| **Hubin** (Hubungan Industri) | Mengelola master data (perusahaan, periode, kompetensi keahlian, indikator penilaian), menerbitkan seluruh surat, menerbitkan Surat Tugas Monitoring |

Role di-*derive* otomatis dari kolom `jabatan` pada `guru_profiles` melalui Observer — tidak ada penetapan role manual lewat UI. Kaprog tetap satu role tunggal (tidak ada role gabungan), namun mendapat permission `kunjungan.*` untuk kasus self-assign sebagai pembimbing (BR10), diakses lewat section terpisah "Bimbingan Saya" di dashboard Kaprog.

**Di luar 4 role di atas**, sistem tidak melayani role lain (mis. Wali Kelas, Kepala Sekolah, Orang Tua) pada versi ini — lihat §10 Out of Scope.

## 4. Definisi & Istilah

- **PKL** — Praktik Kerja Lapangan.
- **Pengajuan PKL** — Permohonan siswa untuk melaksanakan PKL di sebuah perusahaan pada periode tertentu.
- **Surat Pengantar** — Surat resmi sekolah ke perusahaan, diterbitkan Hubin setelah pengajuan disetujui Kaprog.
- **Surat Balasan** — Surat balasan dari perusahaan yang diunggah siswa, terkunci sampai Surat Pengantar terbit (BR20).
- **Resmi PKL** — Status siswa setelah Surat Balasan diverifikasi Hubin (BR7).
- **Surat Tugas Monitoring** — Surat resmi dari Hubin yang menjadi syarat sah bagi Guru Pembimbing untuk melakukan kunjungan monitoring; berlingkup **Global** (seluruh jurusan) atau **Scoped** (satu jurusan, atas request Kaprog jurusan tsb).
- **Laporan Kunjungan** — Catatan kunjungan monitoring yang diisi Guru Pembimbing secara berkala (dicicil), terikat pada Surat Tugas Monitoring yang aktif.
- **Laporan Akhir PKL** — Dokumen akhir yang diupload siswa menjelang selesai masa PKL, menjadi syarat pembuka Formulir Penilaian.
- **Formulir Penilaian** — Form penilaian akhir PKL, diisi sekali menjelang sidang, gated sampai Laporan Akhir PKL diupload siswa (BR32).
- **Indikator Penilaian** — Poin penilaian, terbagi 2 kategori: **Umum** (global, `kompetensi_keahlian_id` NULL) dan **Teknis Kejuruan** (scoped per jurusan).
- **Section Penilaian** — Sub-indikator (child dari Indikator Penilaian), nullable — tidak semua jurusan punya sub-indikator.
- **Nilai Akhir** — Hasil rata-rata seluruh leaf indikator/sub-indikator yang terisi (BR28).
- **Selesai PKL** — Status akhir siswa setelah Nilai Akhir terisi (BR13).
- **PKL Ulang** — Pengajuan PKL baru oleh siswa yang sebelumnya sudah pernah/gagal PKL di periode lain; tidak punya alur khusus (BR14).

## 5. Alur Bisnis Utama

1. **Pengajuan** — Siswa submit form Pengajuan PKL (nama perusahaan, alamat, periode dipilih dari master Periode PKL). Tidak ada alur khusus untuk PKL ulang — submit lewat form yang sama; pengajuan periode sebelumnya otomatis masuk ke Riwayat PKL siswa (BR14).
2. **Verifikasi Kaprog** — Kaprog jurusan terkait melihat detail pengajuan (via ghost button "Lihat detail" di list), approve/reject dilakukan di halaman detail — bukan langsung dari list (BR4).
3. **Persuratan** — Setelah disetujui, Hubin menerbitkan Surat Pengantar (PDF, bernomor unik). Setelah Surat Pengantar terbit, upload Surat Balasan oleh siswa baru terbuka (BR20).
4. **Resmi PKL** — Setelah Surat Balasan diverifikasi Hubin, status siswa otomatis menjadi Resmi PKL (BR7).
5. **Assign Guru Pembimbing** — Wewenang eksklusif Kaprog jurusan siswa tsb (BR10); Kaprog juga bisa self-assign sebagai pembimbing.
6. **Penerbitan Surat Tugas Monitoring** — Hubin menerbitkan Surat Tugas Global (seluruh jurusan) atau Scoped (satu jurusan, atas request Kaprog jurusan tsb) (BR22, BR23).
7. **Pembimbingan & Monitoring** — Guru Pembimbing melakukan kunjungan berdasarkan Surat Tugas Monitoring yang aktif, mengisi Laporan Kunjungan secara berkala (dicicil, tidak sekali submit).
8. **Laporan Akhir** — Siswa upload Laporan Akhir PKL menjelang masa PKL berakhir.
9. **Penilaian** — Formulir Penilaian terbuka bagi Guru Pembimbing setelah Laporan Akhir diupload (BR32, diisi sekali menjelang sidang) → Nilai Akhir dihitung otomatis dari rata-rata leaf indikator yang terisi (BR28).
10. **Selesai PKL** — Status siswa otomatis menjadi Selesai PKL setelah Nilai Akhir terisi (BR13).

## 6. Fitur per Role

### 6.1 Siswa
- Pengajuan PKL (form + upload dokumen pendukung)
- Upload Surat Balasan (gated, BR20)
- Riwayat PKL (termasuk histori pengajuan periode sebelumnya / PKL ulang, BR14)
- Lihat status pengajuan real-time beserta histori perubahan status
- Upload Laporan Akhir PKL
- Lihat Nilai Akhir setelah Selesai PKL
- Notifikasi in-app

### 6.2 Kaprog
- Verifikasi pengajuan siswa jurusannya (approve/reject di halaman detail, BR4)
- Assign/ubah Guru Pembimbing per siswa (tabel per baris: Siswa, Perusahaan, dropdown Guru Pembimbing, aksi Simpan/Ubah) — wewenang eksklusif (BR10)
- Self-assign sebagai Guru Pembimbing (section "Bimbingan Saya")
- Request Surat Tugas Monitoring Scoped ke Hubin
- Monitoring progres seluruh siswa jurusannya (dashboard ringkasan status)

### 6.3 Guru Pembimbing
- Lihat daftar siswa bimbingan (assigned)
- Isi Laporan Kunjungan Monitoring (gated oleh Surat Tugas Monitoring aktif, dicicil per kunjungan)
- Isi Formulir Penilaian PKL (gated oleh Laporan Akhir siswa, BR32, sekali isi)
- Input data ketidakhadiran siswa (sakit/izin/alpa), catatan pembimbing, nama pembimbing industri

### 6.4 Hubin
- Kelola master data: Perusahaan, Periode PKL, Kompetensi Keahlian, Indikator Penilaian + Section Penilaian
- Kelola Akun — tabel Kelola Akun Kaprog dengan aksi "Nonaktifkan sebagai Kaprog" (otomatis jadi Guru Pembimbing via Observer)
- Import/Export Excel data master
- Terbitkan seluruh surat: Surat Pengantar, Surat Tugas Monitoring (Global/Scoped)
- Bundling & Cetak (preview dokumen + tombol Cetak/Kirim terpisah)
- Search & Filter Bar global (komponen reusable dipakai di seluruh modul, BR21)
- Dashboard ringkasan seluruh siswa PKL lintas jurusan

## 7. RBAC Functional — Matrix Lengkap

Permission string menggunakan Bahasa Indonesia, format `<domain>.<aksi>`. Data scoping (mis. per-jurusan) dilakukan lewat ownership check di Laravel Policy — bukan percabangan berdasarkan role.

| Fitur / Aksi | Siswa | Kaprog | Guru Pembimbing | Hubin |
|---|---|---|---|---|
| Ajukan PKL (`pengajuan.ajukan`) | ✅ | ❌ | ❌ | ❌ |
| Lihat pengajuan sendiri (`pengajuan.lihat-sendiri`) | ✅ | ❌ | ❌ | ❌ |
| Lihat pengajuan jurusan (`pengajuan.lihat`) | ❌ | ✅ (jurusan sendiri, via Policy) | ❌ | ✅ (semua) |
| Setujui/tolak pengajuan (`pengajuan.setujui`) | ❌ | ✅ (jurusan sendiri) | ❌ | ❌ |
| Terbitkan Surat Pengantar (`surat.terbitkan`) | ❌ | ❌ | ❌ | ✅ |
| Upload Surat Balasan (`surat-balasan.upload`) | ✅ (gated BR20) | ❌ | ❌ | ❌ |
| Verifikasi Surat Balasan (`surat-balasan.verifikasi`) | ❌ | ❌ | ❌ | ✅ |
| Assign Guru Pembimbing (`pembimbing.assign`) | ❌ | ✅ (jurusan sendiri, eksklusif BR10) | ❌ | ❌ |
| Self-assign sebagai pembimbing (`kunjungan.*` via Bimbingan Saya) | ❌ | ✅ | — | ❌ |
| Terbitkan Surat Tugas Monitoring Global (`surat-tugas.terbitkan-global`) | ❌ | ❌ | ❌ | ✅ |
| Request Surat Tugas Monitoring Scoped (`surat-tugas.request-scoped`) | ❌ | ✅ (jurusan sendiri) | ❌ | ❌ |
| Terbitkan Surat Tugas Monitoring Scoped (`surat-tugas.terbitkan-scoped`) | ❌ | ❌ | ❌ | ✅ |
| Isi Laporan Kunjungan (`kunjungan.isi`) | ❌ | ✅ (khusus siswa self-assign) | ✅ (siswa assigned) | ❌ |
| Upload Laporan Akhir PKL (`laporan-akhir.upload`) | ✅ | ❌ | ❌ | ❌ |
| Isi Formulir Penilaian (`penilaian.isi`) | ❌ | ✅ (khusus siswa self-assign) | ✅ (siswa assigned, gated BR32) | ❌ |
| Kelola Indikator/Section Penilaian (`master-penilaian.kelola`) | ❌ | ❌ | ❌ | ✅ |
| Kelola master data umum (`master-data.kelola`) | ❌ | ❌ | ❌ | ✅ |
| Kelola Akun / Nonaktifkan Kaprog (`akun.kelola`) | ❌ | ❌ | ❌ | ✅ |
| Bundling & Cetak (`persuratan.bundling`) | ❌ | ❌ | ❌ | ✅ |
| Lihat Riwayat PKL sendiri (`riwayat.lihat-sendiri`) | ✅ | ❌ | ❌ | ❌ |
| Lihat dashboard lintas jurusan (`dashboard.global`) | ❌ | ❌ | ❌ | ✅ |
| Lihat dashboard jurusan sendiri (`dashboard.jurusan`) | ❌ | ✅ | ❌ | ❌ |

Catatan: baris **Isi Laporan Kunjungan Monitoring** dan **Isi Formulir Penilaian PKL** mencakup kasus Kaprog yang self-assign sebagai pembimbing (BR10) — permission diberikan sebagai tambahan di atas role `kaprog`, bukan lewat role gabungan.

## 8. Business Rules (BR1–BR32)

| # | Deskripsi |
|---|---|
| BR1 | Siswa hanya dapat memiliki satu pengajuan PKL aktif (status Diajukan/Disetujui/berjalan) pada satu waktu; pengajuan baru untuk periode lain hanya bisa dibuat setelah pengajuan sebelumnya selesai atau statusnya final (ditolak/selesai). |
| BR2 | Pengajuan PKL wajib menyertakan data perusahaan (nama, alamat) dan periode yang dipilih dari master Periode PKL — tidak ada input periode bebas. |
| BR3 | Kaprog yang berwenang memverifikasi sebuah pengajuan adalah Kaprog dari jurusan yang sama dengan siswa pengaju (data scoping via Policy). |
| BR4 | Approve/reject pengajuan hanya dapat dilakukan dari halaman detail pengajuan, diakses lewat ghost button "Lihat detail" di list — tidak ada aksi cepat di list. |
| BR5 | Pengajuan yang ditolak Kaprog dapat diajukan ulang oleh siswa yang bersangkutan (tidak final/permanen). |
| BR6 | Setiap perubahan status pengajuan (approve/reject/terbit surat/dst.) wajib dicatat di `pengajuan_pkl_histories` sebagai audit trail, termasuk aktor dan waktu. |
| BR7 | Status **Resmi PKL** aktif otomatis pada siswa setelah Surat Balasan yang diupload diverifikasi oleh Hubin — tidak ada aksi manual tambahan untuk mengubah status ini. |
| BR8 | Dokumen pendukung pengajuan (jika ada) disimpan terlampir pada record pengajuan dan dapat diakses ulang oleh Kaprog & Hubin selama proses verifikasi. |
| BR9 | Pengajuan yang belum diverifikasi Kaprog tidak dapat lanjut ke tahap persuratan (Surat Pengantar tidak dapat diterbitkan Hubin sebelum status Disetujui). |
| BR10 | Assign Guru Pembimbing adalah wewenang eksklusif Kaprog jurusan siswa yang bersangkutan — **tidak ada** hak override dari Hubin. Kaprog dapat self-assign dirinya sendiri sebagai Guru Pembimbing. |
| BR11 | Satu siswa hanya dapat memiliki satu Guru Pembimbing aktif dalam satu periode PKL; penggantian pembimbing mengarsipkan assignment lama (tidak menghapus histori). |
| BR12 | Guru Pembimbing hanya dapat mengakses data (Laporan Kunjungan, Formulir Penilaian) siswa yang secara eksplisit di-assign kepadanya. |
| BR13 | Status **Selesai PKL** aktif otomatis pada siswa setelah Nilai Akhir siswa tersebut terisi (hasil submit Formulir Penilaian) — tidak ada aksi manual tambahan. |
| BR14 | PKL ulang tidak memiliki alur pengajuan khusus — siswa submit lewat form Pengajuan PKL biasa; pengajuan dari periode sebelumnya otomatis masuk ke halaman Riwayat PKL siswa begitu pengajuan baru dibuat. |
| BR15 | Riwayat PKL menampilkan seluruh pengajuan siswa lintas periode beserta status akhirnya (ditolak/selesai/dibatalkan), bukan hanya pengajuan yang berhasil. |
| BR16 | Surat Pengantar hanya dapat diterbitkan Hubin setelah pengajuan berstatus Disetujui oleh Kaprog. |
| BR17 | Setiap Surat Pengantar dan Surat Tugas Monitoring memiliki nomor surat unik yang digenerate sequential per jenis surat per periode/tahun ajaran. |
| BR18 | Dokumen surat (Surat Pengantar, Surat Tugas Monitoring, Bundling) digenerate sebagai PDF dan diarsipkan di storage, dapat diunduh ulang kapan saja oleh pihak berwenang. |
| BR19 | Bundling & Cetak menyediakan preview dokumen sebelum aksi final; tombol **Cetak** dan **Kirim** terpisah — preview tidak otomatis memicu pengiriman. |
| BR20 | Upload Surat Balasan oleh siswa terkunci (conditional lock) sampai Surat Pengantar untuk pengajuan tersebut resmi terbit. |
| BR21 | Komponen global **Search & Filter Bar** wajib digunakan di seluruh modul list/table di sistem sebagai komponen reusable, dibangun sekali di M2. |
| BR22 | Kunjungan monitoring oleh Guru Pembimbing hanya sah dan dapat dicatat (Laporan Kunjungan) jika ada Surat Tugas Monitoring yang diterbitkan Hubin dan berstatus aktif. |
| BR23 | Surat Tugas Monitoring memiliki dua lingkup: **Global** (berlaku untuk seluruh jurusan, diinisiasi Hubin) dan **Scoped** (berlaku untuk satu jurusan tertentu, diterbitkan Hubin atas request eksplisit dari Kaprog jurusan tsb). |
| BR24 | Indikator Penilaian terbagi dua kategori: **Umum** (`kompetensi_keahlian_id` NULL, berlaku untuk seluruh jurusan) dan **Teknis Kejuruan** (scoped ke satu jurusan tertentu). |
| BR25 | Formulir Penilaian adalah entitas induk per siswa per periode PKL, memuat field sakit/izin/alpa (jumlah hari), catatan Guru Pembimbing, dan nama Pembimbing Industri, selain nilai per indikator. |
| BR26 | Section Penilaian adalah sub-indikator (child dari Indikator Penilaian) dan bersifat **nullable** — Hubin tidak wajib membuat Section untuk setiap Indikator; jurusan tanpa kebutuhan sub-indikator cukup menilai di level Indikator langsung. |
| BR27 | Struktur penilaian bertingkat maksimal 2 level: Indikator → Section (tidak ada sub-sub-indikator). |
| BR28 | **Nilai Akhir** siswa dihitung otomatis sebagai rata-rata seluruh nilai leaf yang terisi — leaf adalah Section jika Indikator tsb punya Section, atau Indikator itu sendiri jika tidak punya Section. |
| BR29 | Skala predikat penilaian bersifat fixed/statis dan sama untuk seluruh jurusan: **K** (<70), **C** (70–80), **B** (81–90), **SB** (91–100) — tidak dapat dikustomisasi per jurusan. |
| BR30 | Penggantian Guru Pembimbing berlaku untuk kasus yang bersifat permanen (resign/pindah tugas). Ketidakhadiran Guru Pembimbing di hari sidang penilaian **tidak** memicu penggantian pembimbing — diselesaikan lewat penjadwalan ulang sidang. |
| BR31 | Perubahan jabatan Kaprog menjadi non-aktif (aksi "Nonaktifkan sebagai Kaprog") hanya dapat dilakukan oleh Hubin, dan otomatis mengalihkan role yang bersangkutan menjadi Guru Pembimbing lewat Observer — tidak ada opsi jabatan bebas lain di UI. |
| BR32 | Formulir Penilaian PKL baru terbuka dan dapat diakses Guru Pembimbing setelah Laporan Akhir PKL diupload oleh siswa (gate eksplisit) — berbeda dari Laporan Kunjungan yang dapat diisi berkala sepanjang periode, Formulir Penilaian hanya diisi sekali menjelang sidang. |

## 9. Keputusan Desain Kunci

- Skala predikat penilaian **K/C/B/SB** bersifat fixed/statis (BR29).
- Formulir Penilaian memuat field tambahan: jumlah hari sakit/izin/alpa, catatan Guru Pembimbing, nama Pembimbing Industri (BR25).
- Ubah Jabatan Kaprog tidak berupa dropdown bebas — hanya aksi "Nonaktifkan sebagai Kaprog" yang otomatis menjadikan Guru Pembimbing (BR31).
- Jurusan menggunakan tabel **Kompetensi Keahlian** yang sudah ada (bukan tabel baru terpisah).
- UI dibangun mengikuti `DESIGN.md` yang difinalisasi sebelum M0, dan dibangun paralel dengan backend di setiap milestone — bukan overhaul di akhir.
- Tidak ada percabangan logic berdasarkan role secara langsung di Controller — seluruh data scoping lewat Policy.

## 10. Asumsi & Out of Scope

**Asumsi:**
- Satu siswa hanya PKL di satu perusahaan per periode (tidak ada multi-perusahaan paralel).
- Satu Guru Pembimbing dapat membimbing banyak siswa sekaligus, lintas periode.
- Kaprog dan Guru Pembimbing adalah staf yang sudah terdaftar di `guru_profiles` sebelum sistem digunakan (tidak ada alur pendaftaran staf baru dari sisi publik).

**Out of Scope (versi ini):**
- Tidak ada alur pembayaran/keuangan dalam sistem.
- Tidak ada integrasi eksternal (mis. SSO, sistem akademik lain, e-meterai).
- Notifikasi bersifat in-app (Laravel DB Notification + Sonner), bukan email/SMS/WhatsApp.
- Tidak ada modul UI overhaul terpisah di akhir proyek — seluruh UI selesai inkremental per milestone.
- Tidak ada role tambahan di luar 4 role yang didefinisikan (mis. Wali Kelas, Orang Tua, Kepala Sekolah).
- Tidak ada mobile app native — akses siswa via web responsive (mobile-first).

## 11. Referensi Dokumen Terkait

- `TRD.md` — Technical Requirements Document
- `docs/milestone.md` — Project Timeline & Milestone
- `DESIGN.md` — Design system (warna, tipografi, komponen)
- PDM (Mermaid erDiagram), Sitemap, Flow diagram, mockup HTML interaktif (4 role)
