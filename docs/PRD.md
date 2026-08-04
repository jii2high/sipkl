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

| Role                          | Deskripsi Singkat                                                                                                                                    |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Siswa**                     | Mengajukan PKL, upload dokumen, memantau status, mengisi Laporan Akhir                                                                               |
| **Kaprog**                    | Memverifikasi pengajuan siswa jurusannya, assign Guru Pembimbing, kelola akun Kaprog di jurusan lain (nonaktifkan)                                   |
| **Guru Pembimbing**           | Membimbing & memonitor siswa yang di-assign, mengisi Laporan Kunjungan dan Formulir Penilaian                                                        |
| **Hubin** (Hubungan Industri) | Mengelola master data (perusahaan, periode, kompetensi keahlian, indikator penilaian), menerbitkan seluruh surat, menerbitkan Surat Tugas Monitoring |

Role di-_derive_ otomatis dari kolom `jabatan` pada `guru_profiles` melalui Observer — tidak ada penetapan role manual lewat UI. Kaprog tetap satu role tunggal (tidak ada role gabungan), namun mendapat permission `kunjungan.*` untuk kasus self-assign sebagai pembimbing (BR10), diakses lewat section terpisah "Bimbingan Saya" di dashboard Kaprog.

**Di luar 4 role di atas**, sistem tidak melayani role lain (mis. Wali Kelas, Kepala Sekolah, Orang Tua) pada versi ini — lihat §10 Out of Scope.

## 4. Definisi & Istilah

- **PKL** — Praktik Kerja Lapangan.
- **Pengajuan PKL** — Permohonan siswa untuk melaksanakan PKL di sebuah perusahaan pada periode tertentu.
- **Surat Pengantar** — Surat resmi sekolah ke perusahaan, diterbitkan Hubin setelah pengajuan disetujui Kaprog.
- **Surat Balasan** — Surat balasan dari perusahaan yang diunggah siswa, terkunci sampai Surat Pengantar terbit (BR20).
- **Resmi PKL** — Status siswa setelah Surat Balasan berhasil diunggah dan lolos validasi sistem (file valid & terbaca); Hubin tetap dapat menolak balasan pasca-fakta bila dokumen ternyata tidak sesuai (BR7).
- **Surat Tugas Monitoring** — Surat resmi dari Hubin yang menjadi syarat sah bagi Guru Pembimbing untuk melakukan kunjungan monitoring; berlingkup **Global** (seluruh jurusan) atau **Scoped** (satu jurusan, atas request Kaprog jurusan tsb).
- **Laporan Kunjungan** — Catatan kunjungan monitoring yang diisi Guru Pembimbing secara berkala (dicicil), terikat pada Surat Tugas Monitoring yang aktif.
- **Laporan Akhir PKL** — Dokumen akhir yang diupload siswa menjelang selesai masa PKL, menjadi syarat pembuka Formulir Penilaian.
- **Formulir Penilaian** — Form penilaian akhir PKL, diisi sekali menjelang sidang, gated sampai Laporan Akhir PKL diupload siswa (BR32).
- **Indikator Penilaian** — Poin penilaian, terbagi 2 kategori: **Umum** (global, `kompetensi_keahlian_id` NULL) dan **Teknis Kejuruan** (scoped per jurusan).
- **Section Penilaian** — Sub-indikator (child dari Indikator Penilaian), nullable — tidak semua jurusan punya sub-indikator.
- **Nilai Akhir** — Dihitung 2 tahap: rata-rata leaf (indikator/sub-indikator) yang terisi per kelompok tujuan, lalu rata-rata antar kelompok yang punya minimal 1 leaf terisi (BR28).
- **Selesai PKL** — Status akhir siswa setelah periode berakhir, Laporan Akhir diunggah, dan Formulir Penilaian ter-submit/Nilai Akhir terisi (BR13).
- **PKL Ulang** — Pengajuan PKL baru oleh siswa yang sebelumnya sudah pernah/gagal PKL di periode lain; tidak punya alur khusus (BR14).

## 5. Alur Bisnis Utama

1. **Pengajuan** — Siswa submit form Pengajuan PKL (nama perusahaan, alamat, periode dipilih dari master Periode PKL). Tidak ada alur khusus untuk PKL ulang — submit lewat form yang sama; pengajuan periode sebelumnya otomatis masuk ke Riwayat PKL siswa (BR14).
2. **Verifikasi Kaprog** — Kaprog jurusan terkait melihat detail pengajuan (via ghost button "Lihat detail" di list), approve/reject dilakukan di halaman detail — bukan langsung dari list (BR4).
3. **Persuratan** — Setelah disetujui, Hubin menerbitkan Surat Pengantar (PDF, bernomor unik). Setelah Surat Pengantar terbit, upload Surat Balasan oleh siswa baru terbuka (BR20).
4. **Resmi PKL** — Begitu Surat Balasan diunggah siswa dan lolos validasi sistem (file valid & terbaca), status siswa otomatis menjadi Resmi PKL — tanpa approval manual di jalur normal. Hubin tetap bisa menolak balasan pasca-fakta bila dokumen ternyata tidak sesuai (BR7).
5. **Assign Guru Pembimbing** — Wewenang eksklusif Kaprog jurusan siswa tsb (BR10); Kaprog juga bisa self-assign sebagai pembimbing.
6. **Penerbitan Surat Tugas Monitoring** — Hubin menerbitkan Surat Tugas Global (seluruh jurusan) atau Scoped (satu jurusan, atas request Kaprog jurusan tsb) (BR22, BR23).
7. **Pembimbingan & Monitoring** — Guru Pembimbing melakukan kunjungan berdasarkan Surat Tugas Monitoring yang aktif, mengisi Laporan Kunjungan secara berkala (dicicil, tidak sekali submit).
8. **Laporan Akhir** — Siswa upload Laporan Akhir PKL menjelang masa PKL berakhir.
9. **Penilaian** — Formulir Penilaian terbuka bagi Guru Pembimbing setelah Laporan Akhir diupload (BR32, diisi sekali menjelang sidang) → Nilai Akhir dihitung otomatis 2 tahap dari leaf indikator yang terisi (BR28).
10. **Selesai PKL** — Status siswa otomatis menjadi Selesai PKL setelah periode berakhir, Laporan Akhir diunggah, dan Formulir Penilaian ter-submit (BR13).

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

| Fitur / Aksi                                                             | Siswa           | Kaprog                               | Guru Pembimbing                 | Hubin      |
| ------------------------------------------------------------------------ | --------------- | ------------------------------------ | ------------------------------- | ---------- |
| Ajukan PKL (`pengajuan.ajukan`)                                          | ✅              | ❌                                   | ❌                              | ❌         |
| Lihat pengajuan sendiri (`pengajuan.lihat-sendiri`)                      | ✅              | ❌                                   | ❌                              | ❌         |
| Lihat pengajuan jurusan (`pengajuan.lihat`)                              | ❌              | ✅ (jurusan sendiri, via Policy)     | ❌                              | ✅ (semua) |
| Setujui/tolak pengajuan (`pengajuan.setujui`)                            | ❌              | ✅ (jurusan sendiri)                 | ❌                              | ❌         |
| Terbitkan Surat Pengantar (`surat.terbitkan`)                            | ❌              | ❌                                   | ❌                              | ✅         |
| Upload Surat Balasan (`surat-balasan.upload`)                            | ✅ (gated BR20) | ❌                                   | ❌                              | ❌         |
| Tolak Surat Balasan pasca-fakta (`surat-balasan.verifikasi`)             | ❌              | ❌                                   | ❌                              | ✅         |
| Assign Guru Pembimbing (`pembimbing.assign`)                             | ❌              | ✅ (jurusan sendiri, eksklusif BR10) | ❌                              | ❌         |
| Self-assign sebagai pembimbing (`kunjungan.*` via Bimbingan Saya)        | ❌              | ✅                                   | —                               | ❌         |
| Terbitkan Surat Tugas Monitoring Global (`surat-tugas.terbitkan-global`) | ❌              | ❌                                   | ❌                              | ✅         |
| Request Surat Tugas Monitoring Scoped (`surat-tugas.request-scoped`)     | ❌              | ✅ (jurusan sendiri)                 | ❌                              | ❌         |
| Terbitkan Surat Tugas Monitoring Scoped (`surat-tugas.terbitkan-scoped`) | ❌              | ❌                                   | ❌                              | ✅         |
| Isi Laporan Kunjungan (`kunjungan.isi`)                                  | ❌              | ✅ (khusus siswa self-assign)        | ✅ (siswa assigned)             | ❌         |
| Upload Laporan Akhir PKL (`laporan-akhir.upload`)                        | ✅              | ❌                                   | ❌                              | ❌         |
| Isi Formulir Penilaian (`penilaian.isi`)                                 | ❌              | ✅ (khusus siswa self-assign)        | ✅ (siswa assigned, gated BR32) | ❌         |
| Kelola Indikator/Section Penilaian (`master-penilaian.kelola`)           | ❌              | ❌                                   | ❌                              | ✅         |
| Kelola master data umum (`master-data.kelola`)                           | ❌              | ❌                                   | ❌                              | ✅         |
| Kelola Akun / Nonaktifkan Kaprog (`akun.kelola`)                         | ❌              | ❌                                   | ❌                              | ✅         |
| Bundling & Cetak (`persuratan.bundling`)                                 | ❌              | ❌                                   | ❌                              | ✅         |
| Lihat Riwayat PKL sendiri (`riwayat.lihat-sendiri`)                      | ✅              | ❌                                   | ❌                              | ❌         |
| Lihat dashboard lintas jurusan (`dashboard.global`)                      | ❌              | ❌                                   | ❌                              | ✅         |
| Lihat dashboard jurusan sendiri (`dashboard.jurusan`)                    | ❌              | ✅                                   | ❌                              | ❌         |

Catatan: baris **Isi Laporan Kunjungan Monitoring** dan **Isi Formulir Penilaian PKL** mencakup kasus Kaprog yang self-assign sebagai pembimbing (BR10) — permission diberikan sebagai tambahan di atas role `kaprog`, bukan lewat role gabungan.

## 8. Business Rules (BR1–BR32)

_Urutan & penomoran BR pada section ini disesuaikan dengan `PRD_v1_2.md` (dikelompokkan per tema), dengan penyesuaian istilah mengikuti skema final (`pembimbingan`, `formulir_penilaian`, `indikator_penilaian`, `section_penilaian`). BR13 menggabungkan syarat asli v1.2 (periode berakhir + Laporan Akhir) dengan keputusan implementasi final bahwa submit Formulir Penilaian juga menjadi syarat — lihat catatan pada baris BR13._

### Pengajuan & Verifikasi

| #   | Deskripsi                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BR1 | Pengajuan PKL bersifat per individu siswa — satu siswa mengisi & submit form pengajuannya sendiri-sendiri, dan hanya dapat memiliki satu pengajuan aktif pada satu waktu.                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| BR2 | Kaprog yang berwenang memverifikasi sebuah pengajuan adalah Kaprog dari kompetensi keahlian (jurusan) yang sama dengan siswa pengaju (data scoping via Policy).                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| BR3 | Kuota 1 perusahaan maksimal 6 siswa per periode bersifat **soft rule** (peringatan), bukan hard block.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| BR4 | Halaman antrean Kaprog hanya menyajikan data ringkas dan ghost button "Lihat detail →". Approve/reject pengajuan hanya dapat dilakukan dari halaman detail — tidak ada aksi cepat di list.                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| BR5 | Tahap Hubin bersifat otomatis (tanpa gate approval manual tambahan), namun wajib menampilkan Pratinjau Surat Pengantar sebelum dicetak/dikirim. Tombol **Cetak** (tidak mengubah status) dan **Kirim** (mengubah status jadi Surat Pengantar terbit) dipisah secara eksplisit.                                                                                                                                                                                                                                                                                                                                                |
| BR6 | Penolakan oleh Kaprog wajib disertai catatan (minimal 15 karakter) dari halaman detail, mengembalikan status pengajuan agar siswa dapat mengajukan ulang (tidak final/permanen).                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| BR7 | Begitu siswa mengunggah dokumen Surat Balasan dan file tersebut lolos validasi sistem (format PDF valid, file terbaca/tidak corrupt), status otomatis berubah menjadi **Resmi PKL** tanpa approval manual dari Kaprog/Hubin di jalur normal. File yang gagal validasi sistem ditolak sebelum sampai mengubah status, siswa diminta unggah ulang. Hubin tetap berwenang menolak balasan **pasca-fakta** (`surat-balasan.verifikasi`, wajib catatan ≥15 karakter, pola sama seperti BR6) bila dokumen ternyata bukan surat penerimaan yang valid meski lolos validasi sistem — status kembali ke Disetujui, siswa unggah ulang. |

### Perusahaan & Surat

| #    | Deskripsi                                                                                                                                                                                                                                                                                                                                                       |
| ---- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BR8  | Surat Pengantar dibundel per perusahaan (bukan per pengajuan individu) — satu surat dapat memuat hingga 6 siswa yang mengajukan ke perusahaan & periode yang sama.                                                                                                                                                                                              |
| BR9  | Data perusahaan bersifat master data persisten lintas periode — tidak terhapus saat pengajuan ditolak/direvisi, dan muncul via autocomplete + peringatan kuota saat dipakai kembali pada periode berikutnya. Pengajuan yang belum diverifikasi Kaprog juga tidak dapat lanjut ke tahap persuratan (Surat Pengantar tidak diterbitkan sebelum status Disetujui). |
| BR18 | Setiap Surat Pengantar dan Surat Tugas Monitoring memiliki nomor surat unik, digenerate sequential per jenis surat per periode/tahun ajaran (detail format & contoh lihat TRD Section 6). Dokumen surat digenerate sebagai PDF dan diarsipkan, dapat diunduh ulang kapan saja oleh pihak berwenang.                                                             |
| BR20 | Upload Surat Balasan oleh siswa terkunci (conditional lock) secara default, aktif setelah Surat Pengantar untuk pengajuan tersebut resmi terbit.                                                                                                                                                                                                                |

### Pembimbingan & Monitoring

| #    | Deskripsi                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| BR10 | Assign Guru Pembimbing adalah wewenang eksklusif Kaprog jurusan siswa yang bersangkutan — **tidak ada** hak override dari Hubin. Dropdown pemilihan wajib menyertakan akun Kaprog sendiri, sehingga Kaprog dapat self-assign sebagai Guru Pembimbing.                                                                                                                                                                                                                                      |
| BR11 | Guru Pembimbing hanya dapat melihat & mengelola siswa yang menjadi bimbingannya (hasil assignment Kaprog).                                                                                                                                                                                                                                                                                                                                                                                 |
| BR12 | Laporan Akhir PKL wajib diunggah siswa sebelum status pengajuan dapat berubah menjadi Selesai PKL.                                                                                                                                                                                                                                                                                                                                                                                         |
| BR13 | Status **Selesai PKL** ditetapkan otomatis setelah seluruh syarat berikut terpenuhi: (a) periode PKL sudah berakhir, (b) Laporan Akhir PKL sudah diunggah, dan (c) Formulir Penilaian sudah _submitted_ sehingga Nilai Akhir terisi — tidak ada aksi manual tambahan. _(Catatan: v1.2 awalnya hanya mensyaratkan (a)+(b); syarat (c) adalah keputusan implementasi final — mohon dikonfirmasi apakah ini yang dimaksud, atau Selesai PKL tetap independen dari Nilai Akhir seperti v1.2.)_ |
| BR14 | PKL ulang tidak memiliki alur/form khusus — siswa submit lewat form Pengajuan PKL biasa; pengajuan dari periode sebelumnya otomatis masuk ke halaman Riwayat PKL siswa (menampilkan seluruh status, bukan hanya yang berhasil).                                                                                                                                                                                                                                                            |
| BR22 | Kunjungan monitoring oleh Guru Pembimbing hanya sah dan dapat dicatat (Laporan Kunjungan) jika ada Surat Tugas Monitoring — Global atau Scoped — yang mencakup jurusan siswa bimbingannya dan berstatus aktif.                                                                                                                                                                                                                                                                             |
| BR23 | Surat Tugas Monitoring memiliki dua lingkup: **Global** (seluruh jurusan, inisiatif Hubin) dan **Scoped** (satu jurusan tertentu, diterbitkan Hubin atas request eksplisit Kaprog jurusan tsb — otomatis scoped ke jurusannya sendiri).                                                                                                                                                                                                                                                    |

### Penilaian PKL

| #    | Deskripsi                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ---- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BR24 | Formulir Penilaian PKL diisi Guru Pembimbing, terikat pada penugasan pembimbingan (`pembimbingan`, bukan langsung ke `pengajuan_pkl`) — satu Formulir = satu kali penilaian per penugasan.                                                                                                                                                                                                                                                                                                |
| BR25 | Struktur Penilaian terbagi 2 kategori: **Umum** (`kompetensi_keahlian_id` NULL, berlaku untuk seluruh jurusan) dan **Teknis Kejuruan** (scoped ke satu jurusan tertentu). Tiap Indikator Penilaian boleh punya Section Penilaian (sub-indikator) — **nullable**, karena tidak semua indikator butuh sub-indikator.                                                                                                                                                                        |
| BR26 | Nilai hanya boleh disimpan di level leaf: jika Indikator punya Section, nilai diisi per Section; jika tidak, nilai diisi langsung di level Indikator. Leaf boleh dikosongkan jika butir tsb tidak dikerjakan siswa — leaf kosong tidak masuk hitungan Nilai Akhir.                                                                                                                                                                                                                        |
| BR27 | Ganti jabatan dibatasi ke satu aksi spesifik: Hubin hanya dapat menonaktifkan akun yang berjabatan Kaprog (bukan dropdown bebas pilih jabatan). Begitu dinonaktifkan, jabatan otomatis berubah menjadi Guru Pembimbing lewat Observer. Jika jurusan jadi tidak punya Kaprog aktif, sistem menampilkan status "Belum ada Kaprog".                                                                                                                                                          |
| BR28 | Skala predikat bersifat fixed/statis: **K** (<70), **C** (70–80), **B** (81–90), **SB** (91–100) — tidak dapat dikustomisasi per jurusan. **Nilai Akhir** Formulir dihitung 2 tahap: (1) rata-rata leaf yang terisi per kelompok tujuan indikator, (2) rata-rata dari nilai antar kelompok yang punya minimal 1 leaf terisi — bukan rata-rata seluruh leaf digabung rata, agar jurusan dengan leaf Teknis Kejuruan lebih banyak tidak mendominasi skor akhir. Hasil dibulatkan 1 desimal. |
| BR29 | Formulir Penilaian juga mencatat Kehadiran (jumlah hari Sakit/Izin/Tanpa Keterangan), Catatan Guru Pembimbing, dan Nama Pembimbing Industri — field pendukung, tidak masuk hitungan Nilai Akhir.                                                                                                                                                                                                                                                                                          |
| BR30 | Penggantian Guru Pembimbing di tengah periode: assignment lama (`pembimbingan`) ditandai diarsipkan (bukan dihapus), assignment baru dibuat aktif. Formulir yang sudah diisi di assignment lama tetap tersimpan sebagai riwayat, namun rekap hasil penilaian hanya menampilkan Formulir dari assignment aktif. Berlaku untuk kasus permanen (resign/pindah tugas) — ketidakhadiran guru di hari sidang diselesaikan lewat penjadwalan ulang sidang, bukan penggantian pembimbing.         |
| BR31 | Submit Formulir Penilaian boleh bersifat partial (tidak semua leaf wajib terisi), dengan syarat minimal 1 leaf terisi per kelompok tujuan yang relevan. Sebelum status berubah menjadi _submitted_, sistem wajib menampilkan modal konfirmasi yang mendaftar leaf yang masih kosong.                                                                                                                                                                                                      |
| BR32 | Formulir Penilaian PKL hanya dapat dibuka/diisi Guru Pembimbing setelah Laporan Akhir PKL diupload siswa bimbingannya (gate eksplisit) — berbeda dari Laporan Kunjungan yang dapat diisi berkala sepanjang periode. Sebelum syarat ini terpenuhi, Formulir belum ter-generate/belum bisa diakses sama sekali.                                                                                                                                                                             |

### Akun & Keamanan

| #    | Deskripsi                                                                                                                                                                                                                |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| BR15 | Password awal akun (siswa & guru) = NIS/NIP, wajib diganti pada login pertama sebelum bisa mengakses dashboard.                                                                                                          |
| BR16 | Import akun massal via Excel memvalidasi keunikan NIS/NIP dan kecocokan `kompetensi_keahlian` dengan master data; baris yang gagal validasi dilaporkan sebagai error per baris, tidak membatalkan seluruh proses import. |

### Audit & Akses

| #    | Deskripsi                                                                                                                                                                                                                                                     |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BR17 | Setiap transisi status pengajuan wajib dicatat sebagai audit trail (aktor, waktu, catatan) ke `pengajuan_pkl_histories` — menjadi basis progress tracker yang dilihat siswa. Dokumen pendukung pengajuan (jika ada) disimpan terlampir pada record pengajuan. |
| BR19 | RBAC: setiap role hanya bisa mengakses/memodifikasi data sesuai cakupannya (siswa = miliknya sendiri, Kaprog = jurusannya, Guru Pembimbing = bimbingannya, Hubin = semua). Detail lengkap di Section 7 — Access Matrix.                                       |
| BR21 | Komponen global **Search & Filter Bar** wajib digunakan di seluruh halaman kelola data serta halaman utama tiap role, sebagai komponen reusable (dibangun sekali di M2).                                                                                      |

## 9. Import/Export Excel (Kebutuhan Bisnis)

- **Import Akun** (Hubin) — upload template Excel berisi data siswa/guru; validasi saat import: NIS/NIP unik, `kompetensi_keahlian` harus match master data, error report per baris kalau ada yang gagal (BR16). Password default = NIS/NIP, wajib ganti di first login (BR15).
- **Export** (Hubin) — ekspor data master (Perusahaan, Periode PKL, Kompetensi Keahlian) dan data operasional (rekap pengajuan, rekap nilai) ke Excel untuk kebutuhan pelaporan/arsip di luar sistem.
- Baris gagal validasi tidak membatalkan keseluruhan proses import — sisanya tetap diproses, baris gagal dilaporkan terpisah.

## 10. Keputusan Desain Kunci

- Skala predikat penilaian **K/C/B/SB** bersifat fixed/statis (BR28).
- Formulir Penilaian memuat field tambahan: jumlah hari sakit/izin/alpa, catatan Guru Pembimbing, nama Pembimbing Industri (BR29).
- Ubah Jabatan Kaprog tidak berupa dropdown bebas — hanya aksi "Nonaktifkan sebagai Kaprog" yang otomatis menjadikan Guru Pembimbing (BR27).
- Jurusan menggunakan tabel **Kompetensi Keahlian** yang sudah ada (bukan tabel baru terpisah).
- UI dibangun mengikuti `DESIGN.md` yang difinalisasi sebelum M0, dan dibangun paralel dengan backend di setiap milestone — bukan overhaul di akhir.
- Tidak ada percabangan logic berdasarkan role secara langsung di Controller — seluruh data scoping lewat Policy.

## 11. Asumsi & Out of Scope

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

## 12. Dokumen Terkait

| Dokumen                         | Path                    | Isi                                                                     |
| ------------------------------- | ----------------------- | ----------------------------------------------------------------------- |
| Technical Requirements Document | `docs/TRD.md`           | Spesifikasi teknis: stack, data model, state machine, RBAC implementasi |
| Coding Plan                     | `docs/coding_plan.md`   | Rencana milestone & urutan pengerjaan per fitur                         |
| Design System                   | `docs/DESIGN.md`        | Warna, tipografi, komponen UI                                           |
| PDM (Physical Data Model)       | `docs/diagrams/PDM.xml` | Diagram ERD/skema database                                              |
| Sitemap & Flow Diagram          | `docs/diagrams/`        | Alur navigasi & business flow per role                                  |
| Mockup Interaktif               | `docs/mockups/`         | Mockup HTML interaktif untuk 4 role                                     |

_Catatan: penamaan `coding_plan.md` menggantikan `milestone.md` — lihat pembahasan lebih lanjut di `docs/coding_plan.md` itu sendiri._
