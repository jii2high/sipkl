# Panduan Implementasi — M3: Pengajuan & Verifikasi Awal

**Referensi:** `PRD.md` §5 (poin 1–2), §6.1–6.2, §8 (BR1–BR6, BR9, BR14, BR15) | `TRD.md` §3.2, §4.1, §5.1, §8 | `milestone.md` M3

---

## 0. Prasyarat

- [ ] Checklist M2 sudah lulus semua. Master data (`perusahaan`, `periode_pkl`, `kompetensi_keahlian`) sudah tersedia dan bisa dipilih dari form.

## 1. Tujuan Milestone

Membangun alur pengajuan PKL siswa sampai verifikasi Kaprog, sekaligus jadi titik pertama pembangunan infrastruktur **audit trail** dan **notifikasi** yang akan dipakai ulang di seluruh milestone berikutnya.

## 2. Urutan Eksekusi Modul/Fitur

### 2.1 Form Pengajuan PKL (Siswa)
- Tabel `pengajuan_pkl` (lihat skema di `TRD.md` §3.2): `siswa_id`, `perusahaan_id`, `periode_pkl_id`, `status` (enum: `diajukan`, `disetujui`, `ditolak`, `resmi_pkl`, `selesai_pkl`), `catatan_verifikasi`, `dokumen_pendukung`.
- **BR1**: siswa hanya boleh punya **satu** pengajuan aktif (status Diajukan/Disetujui/berjalan) pada satu waktu — validasi ini wajib di Form Request/Policy sebelum create.
- **BR2**: data perusahaan (nama, alamat) + periode wajib dipilih dari master — **tidak ada** input periode bebas.
- **BR8**: dokumen pendukung (jika ada) disimpan terlampir di record, bisa diakses ulang Kaprog & Hubin.
- Permission: `pengajuan.ajukan`, `pengajuan.lihat-sendiri` (siswa).
- Controller: `Siswa/PengajuanController.php`.

Smoke test: siswa submit pengajuan baru, tidak bisa submit kedua kalinya selagi ada yang aktif.

### 2.2 Halaman List Pengajuan (Kaprog)
- **BR3**: Kaprog yang berwenang = Kaprog jurusan yang sama dengan siswa pengaju — scoping via **Policy** (`PengajuanPolicy`), **bukan** `if ($role === ...)` inline.
- **BR4**: list hanya menampilkan ghost button **"Lihat detail"** — **tidak ada** aksi cepat (approve/reject) langsung di list.
- Permission: `pengajuan.lihat` (kaprog, scoped).
- Pasang **Search & Filter Bar** (komponen dari M2) di list ini.
- Controller: `Kaprog/PengajuanController.php`.

Smoke test: Kaprog hanya melihat pengajuan siswa jurusannya, tidak bisa lihat jurusan lain.

### 2.3 Halaman Detail Pengajuan — Approve/Reject
- **BR4**: approve/reject **hanya** dari halaman detail, bukan dari list.
- **BR6**: setiap approve/reject wajib tercatat sebagai perubahan status di `pengajuan_pkl_histories` (dibangun di 2.4).
- Catatan verifikasi wajib diisi saat reject (`catatan_verifikasi`, lihat `TRD.md` §3.2).
- **BR5**: pengajuan yang ditolak boleh diajukan ulang siswa yang bersangkutan (tidak permanen) — pastikan status `ditolak` tidak memblokir siswa submit ulang lewat form biasa (2.1).
- **BR9**: pengajuan yang belum disetujui Kaprog tidak boleh lanjut ke persuratan (jadi guard ini juga penting untuk M4 nanti — Surat Pengantar hanya bisa terbit jika status `disetujui`).
- Permission: `pengajuan.setujui`, `pengajuan.tolak` (kaprog, scoped).

Smoke test: approve/reject dari halaman detail berjalan, status berubah sesuai state machine (`TRD.md` §4.1).

### 2.4 `pengajuan_pkl_histories` — Infra Audit Trail (pertama kali dibangun di sini)
- Skema (`TRD.md` §3.2): `pengajuan_pkl_id`, `status_lama`, `status_baru`, `aktor_id`, `catatan`, `created_at`.
- **BR6**: setiap perubahan status (approve/reject/terbit surat/dst.) di seluruh siklus pengajuan wajib tercatat di sini, termasuk aktor dan waktu — bukan hanya untuk M3, tapi jadi infra yang dipakai lagi di M4 (terbit surat), M6 (selesai PKL), dll.
- Desain function/trait pencatatan histori-nya agar reusable dipanggil dari controller manapun yang mengubah status pengajuan.

Smoke test: approve/reject Kaprog tercatat lengkap di `pengajuan_pkl_histories` dengan aktor dan waktu yang benar.

### 2.5 Notification Infra — Laravel DB Notification + Sonner (pertama kali dibangun di sini)
- Tabel `notifications` (bawaan Laravel DB Notification).
- Toast **Sonner** di frontend untuk menampilkan notifikasi real-time.
- Trigger pertama yang diimplementasi di M3: notifikasi ke Siswa saat pengajuan disetujui/ditolak (lihat daftar event lengkap di `TRD.md` §8 — sisanya ditambahkan incremental di M4–M6, **jangan** dikerjakan semua sekaligus di sini).
- Struktur folder: `app/Notifications/`.

Smoke test: notifikasi in-app muncul (toast Sonner + tersimpan di tabel `notifications`) saat status pengajuan berubah.

### 2.6 Riwayat PKL Siswa (termasuk BR14 — PKL ulang)
- **BR14**: PKL ulang **tidak** punya alur khusus — siswa submit lewat form Pengajuan PKL biasa (2.1). Begitu pengajuan baru dibuat, pengajuan periode sebelumnya otomatis masuk ke halaman Riwayat.
- **BR15**: Riwayat menampilkan **seluruh** pengajuan siswa lintas periode beserta status akhirnya (ditolak/selesai/dibatalkan) — bukan hanya yang berhasil.
- Permission: `riwayat.lihat-sendiri` (siswa).
- Controller: `Siswa/RiwayatController.php`.

Smoke test: siswa yang sudah selesai/ditolak PKL sebelumnya lalu submit pengajuan baru — pengajuan lama muncul benar di Riwayat, dan tidak mengganggu data pengajuan aktif yang baru.

## 3. Checklist Testing Akhir Sesi M3

- [ ] Siswa dapat submit pengajuan dan melihat statusnya berubah real-time.
- [ ] Approve/reject Kaprog tercatat di `pengajuan_pkl_histories`.
- [ ] Notifikasi in-app muncul saat status pengajuan berubah.
- [ ] PKL ulang (submit ulang lewat form biasa) muncul benar di Riwayat PKL, tidak merusak data pengajuan aktif.

**Semua item di atas harus lulus sebelum M4 dimulai.**

## 4. Catatan untuk Milestone Berikutnya

Infra `pengajuan_pkl_histories` dan notifikasi **jangan dibangun ulang** di M4–M6 — cukup tambahkan trigger baru yang relevan ke infra yang sudah ada di sini.
