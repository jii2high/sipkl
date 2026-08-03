# Walkthrough SI-PKL — 07. RBAC & Policy

Otorisasi di aplikasi ini bekerja di **dua lapisan** yang saling melengkapi.
Memahami pembagian keduanya adalah kunci membaca semua controller.

---

## 7.1 Dua Lapisan Otorisasi

### Lapisan 1 — Middleware `permission:` (di route)

```php
Route::post('pengajuan', ...)->middleware('permission:pengajuan.ajukan');
```

- Menerjemahkan: "apakah **peran** user memberi izin melakukan kategori aksi ini?"
- Cek dilakukan **sebelum** controller dijalankan (Spatie `PermissionMiddleware`).
- 403 otomatis jika tidak punya izin.
- Tidak tahu apa-apa tentang **objek tertentu** (pengajuan siapa, jurusan apa).

### Lapisan 2 — Policy `$this->authorize()` (di controller)

```php
$this->authorize('approve', $pengajuan);
```

- Menerjemahkan: "apakah user berhak atas **objek ini** dalam **kondisi ini**?"
- Cek dilakukan di dalam controller (bisa pakai `$request->user()`).
- Tempat semua aturan bisnis berbasis data: owner, jurusan, status.

> Konvensi proyek (TRD §2): **selalu pakai policy**, jangan `if ($user->hasRole(...))`
> di controller. Penulis kode ini juga memakai policy untuk aturan berbasis status.

---

## 7.2 Role & Permission — `database/seeders/PermissionSeeder.php`

### Role (4)

Dibuat di `RoleSeeder`: `siswa`, `kaprog`, `guru_pembimbing`, `hubin`.

### Permission (13)

| Permission | Arti | Pemilik |
|---|---|---|
| `master-data.kelola` | CRUD perusahaan/periode/jurusan | hubin |
| `master-penilaian.kelola` | CRUD indikator + section | hubin |
| `akun.kelola` | kelola akun, import/export | hubin |
| `pengajuan.ajukan` | buat & submit pengajuan | siswa |
| `pengajuan.lihat-sendiri` | lihat pengajuan milik sendiri | siswa |
| `riwayat.lihat-sendiri` | lihat riwayat sendiri | siswa |
| `surat-balasan.upload` | upload & download balasan | siswa |
| `pengajuan.lihat` | lihat antrian verifikasi | kaprog |
| `pengajuan.setujui` | approve pengajuan | kaprog |
| `pengajuan.tolak` | reject pengajuan | kaprog |
| `surat.terbitkan` | bundling & terbit surat pengantar | hubin |
| `surat-balasan.verifikasi` | tolak balasan | hubin |
| `persuratan.bundling` | akses modul persuratan | hubin |

### Tabel matriks role × permission

```
permission                   siswa    kaprog    guru_pembimbing    hubin
───────────────────────────  ──────   ──────    ───────────────    ──────
master-data.kelola             –        –            –              ✔
master-penilaian.kelola        –        –            –              ✔
akun.kelola                    –        –            –              ✔
surat.terbitkan                –        –            –              ✔
surat-balasan.verifikasi       –        –            –              ✔
persuratan.bundling            –        –            –              ✔
pengajuan.ajukan               ✔        –            –              –
pengajuan.lihat-sendiri        ✔        –            –              –
riwayat.lihat-sendiri          ✔        –            –              –
surat-balasan.upload           ✔        –            –              –
pengajuan.lihat                –        ✔            –              –
pengajuan.setujui              –        ✔            –              –
pengajuan.tolak                –        ✔            –              –
```

> **Role derivation**: role guru otomatis dari `guru_profiles.jabatan` via
> `GuruProfileObserver` (BR31). Role `siswa` di-assign eksplisit di `SiswaImport`.
> Tidak ada UI untuk set role manual.

---

## 7.3 `PengajuanPklPolicy` — dibedah

`app/Policies/PengajuanPklPolicy.php`. Catatan penting: permission memakai
`hasPermissionTo`, bukan `hasRole` — karena permission bisa dipakai di beberapa
role.

### viewAny
```php
return $user->hasAnyPermission(['pengajuan.lihat', 'pengajuan.lihat-sendiri']);
```
Siapa pun dengan salah satu dari dua izin lihat (kaprog atau siswa).

### view — 3 jalur masuk
```php
// (1) siswa pemilik
if ($user->hasPermissionTo('pengajuan.lihat-sendiri') && $user->siswaProfile?->id === $pengajuan->siswa_id) return true;

// (2) kaprog sejurusan (guru dengan kompetensi_keahlian sama dengan siswa)
if ($user->hasPermissionTo('pengajuan.lihat') && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id) return true;

// (3) hubin (punya permission 'pengajuan.lihat'? tidak... tapi cek role hubin eksplisit)
if ($user->hasPermissionTo('pengajuan.lihat') && $user->hasRole('hubin')) return true;
```

> **Anomali (perlu dicatat)**: jalur (3) memerlukan `pengajuan.lihat` DAN role
> `hubin` — padahal role `hubin` **tidak** punya permission `pengajuan.lihat`
> (lihat matriks). Artinya Hubin sebenarnya **tidak bisa** membuka detail
> pengajuan lewat policy ini. Ini bisa jadi bug tersembunyi.

### create — BR1
```php
if (! $user->hasPermissionTo('pengajuan.ajukan')) return false;

return ! PengajuanPkl::where('siswa_id', $user->siswaProfile?->id)
    ->whereHas('periode', fn ($q) => $q->where('status_aktif', true))
    ->whereNotIn('status', ['ditolak'])
    ->exists();   // satu pengajuan aktif per siswa per periode
```

### submit
```php
return $user->hasPermissionTo('pengajuan.ajukan')
    && $user->siswaProfile?->id === $pengajuan->siswa_id
    && in_array($pengajuan->status, ['draft', 'ditolak']);
```
Siswa hanya bisa submit kalau status masih `draft` atau `ditolak` (bisa ubah pikiran
setelah ditolak).

### approve / reject — BR4
```php
return $user->hasPermissionTo('pengajuan.setujui')
    && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id
    && $pengajuan->status === 'diajukan';
```
Kaprog **sejurusan** + status masih `diajukan`. BR4 juga di-*enforce* di frontend
(tombol hanya di halaman detail).

### uploadBalasan — BR20
```php
if (! $user->hasRole('siswa') || ! $user->siswaProfile) return false;

return $pengajuan->siswa_id === $user->siswaProfile->id
    && $pengajuan->status === 'disetujui'
    && $pengajuan->suratPengantars()->exists();   // surat pengantar SUDAH terbit
```

### lihatSendiri / tolakBalasan
```php
// lihatSendiri: role siswa + pemilik
// tolakBalasan: role hubin + status resmi_pkl
```

---

## 7.4 `SuratPengantarPolicy`

| Method | Logika |
|---|---|
| `viewAny` | role `hubin` saja |
| `create` | role `hubin` saja |
| `view` | hubin, atau siswa yang pengajuannya ada di surat itu |
| `uploadBalasan` / `tolakBalasan` | duplikat dari `PengajuanPklPolicy` (delegated) |
| `update` / `delete` | selalu `false` — surat tidak bisa diubah/dihapus |

---

## 7.5 Ringkasan: Kapan Pakai Apa

| Situasi | Mekanisme |
|---|---|
| "Route ini hanya boleh Hubin" | middleware `permission:akun.kelola` |
| "Halaman lihat daftar butuh izin lihat" | `viewAny` policy |
| "User hanya boleh lihat pengajuan sendiri" | `view` policy (owner check) |
| "Kaprog hanya boleh verifikasi sejurusan" | query scope (index) + policy (approve/reject) |
| "Siswa tidak boleh submit dua pengajuan" | `create` policy (BR1) |
| "Upload balasan dilarang sampai surat ada" | `uploadBalasan` policy + `abort_unless` controller (BR20) |

Aturan praktis: **permission untuk kategori aksi, policy untuk objek + kondisi**.
Ketika kedua-duanya gagal cek → Laravel mengembalikan 403 (di Inertia:
`403.tsx` atau error Inertia default).
