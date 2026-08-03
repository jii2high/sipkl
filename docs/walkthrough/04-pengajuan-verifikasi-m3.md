# Walkthrough SI-PKL — 04. Pengajuan & Verifikasi (M3)

Modul ini adalah **jantung alur PKL**: siswa mengajukan, Kaprog memverifikasi.
Status pengajuan bergerak sesuai state machine (detail lengkap di `06-alur-utuh`).

---

## 4.1 Model & Tabel

**`pengajuan_pkl`** (model `PengajuanPkl`) — satu baris = satu pengajuan PKL.

Kolom kunci:

| Kolom | Arti |
|---|---|
| `siswa_id` | FK ke `siswa_profiles.id` |
| `perusahaan_id` | FK ke `perusahaans.id` |
| `periode_id` | FK ke `periode_pkls.id` |
| `status` | `draft` (default) → `diajukan` → `disetujui` → `resmi_pkl` → (nanti `selesai_pkl`); back-edge `ditolak` |
| `kaprog_id` | FK user yang approve/reject |
| `catatan_kaprog` | Alasan penolakan |
| `verified_kaprog_at` | Waktu verifikasi |
| `nomor_pengajuan` | (cadangan, belum dipakai aktif) |

Aksesor penting (dikirim otomatis ke frontend):

```php
// app/Models/PengajuanPkl.php:93
public function getExistsSuratPengantarAttribute(): bool
{
    return $this->suratPengantars()->exists();   // apakah surat pengantar sudah terbit?
}
```

**`pengajuan_pkl_histories`** (model `PengajuanPklHistory`) — audit trail.
Setiap transisi status tersimpan di sini (BR6). Tidak punya kolom `updated_at`
(`const UPDATED_AT = null`), hanya `created_at` buatan manual.

---

## 4.2 Sisi Siswa — `app/Http/Controllers/Siswa/PengajuanController.php`

### `index()` — halaman Pengajuan PKL

```php
$siswaId = request()->user()->siswaProfile->id;
$periodeAktif = PeriodePkl::where('status_aktif', true)->first();

$pengajuanAktif = null;
if ($periodeAktif) {
    $pengajuanAktif = PengajuanPkl::with(['perusahaan', 'periode', 'histories.actor'])
        ->where('siswa_id', $siswaId)
        ->where('periode_id', $periodeAktif->id)
        ->whereNotIn('status', ['ditolak'])   // pengajuan ditolak tidak dianggap "aktif"
        ->first();
}
```

- Mengambil pengajuan siswa di **periode aktif**, kecuali yang `ditolak`.
- Props ke frontend: `pengajuan`, `perusahaan` (daftar semua, untuk dropdown),
  `periodeAktif`.

### `store()` — simpan draft (BR1 di-enforce di policy)

```php
$this->authorize('create', PengajuanPkl::class);   // BR1: cek belum ada pengajuan aktif

$validated = $request->validate(['perusahaan_id' => 'required|exists:perusahaan,id']);

$pengajuan = PengajuanPkl::create([
    'siswa_id' => $siswa->id,
    'perusahaan_id' => $validated['perusahaan_id'],
    'periode_id' => $periodeAktif->id,     // status default = draft
]);
```

### `submit()` — kirim ke Kaprog

```php
$this->authorize('submit', $pengajuan);   // pemilik + status draft/ditolak

$pengajuan->status = 'diajukan';
$pengajuan->save();                       // observer mencatat history (BR6)

$kaprogs = User::role('kaprog')
    ->whereHas('guruProfile', fn ($q) => $q
        ->where('kompetensi_keahlian_id', $siswa->kompetensi_keahlian_id))
    ->get();

foreach ($kaprogs as $kaprog) {
    $kaprog->notify(new PengajuanMasukAntrian($pengajuan));   // notifikasi DB
}
```

- Notifikasi dikirim ke **semua** Kaprog sejurusan (yang kompetensi keahlihannya
  sama dengan siswa).

### `riwayat()` — halaman Riwayat

```php
PengajuanPkl::with(['perusahaan', 'periode', 'histories.actor'])
    ->where('siswa_id', $siswaId)
    ->when($periodeAktif, fn ($q) => $q->where('periode_id', '!=', $periodeAktif->id))
    ->orderBy('created_at', 'desc')
    ->paginate(10);
```

Pengajuan di periode **selain** periode aktif (artinya: riwayat lama).

---

## 4.3 Policy — `app/Policies/PengajuanPklPolicy.php`

Semua aturan data di sini. Ringkasan per method:

| Method | Syarat (semua harus terpenuhi) |
|---|---|
| `viewAny` | punya `pengajuan.lihat` ATAU `pengajuan.lihat-sendiri` |
| `view` | (a) owner (lihat-sendiri) ATAU (b) Kaprog sejurusan ATAU (c) Hubin |
| `create` | punya `pengajuan.ajukan` **DAN tidak ada pengajuan aktif non-ditolak** (BR1) |
| `submit` | punya `pengajuan.ajukan` + pemilik + status `draft`/`ditolak` |
| `approve` | punya `pengajuan.setujui` + Kaprog **sejurusan** + status `diajukan` (BR4) |
| `reject` | sama dengan approve, beda permission |
| `uploadBalasan` | role siswa + pemilik + status `disetujui` + surat pengantar sudah ada (BR20) |
| `lihatSendiri` | role siswa + pemilik |
| `tolakBalasan` | role hubin + status `resmi_pkl` |

Contoh BR1 (`create`, baris 41–44):

```php
return ! PengajuanPkl::where('siswa_id', $user->siswaProfile?->id)
    ->whereHas('periode', fn ($q) => $q->where('status_aktif', true))
    ->whereNotIn('status', ['ditolak'])
    ->exists();
```

Contoh BR4 (`approve`, baris 54–59) — hanya Kaprog yang kompetensi keahlihannya
sama dengan siswa (scoping jurusan):

```php
return $user->hasPermissionTo('pengajuan.setujui')
    && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id
    && $pengajuan->status === 'diajukan';
```

---

## 4.4 Sisi Kaprog — `app/Http/Controllers/Kaprog/VerifikasiController.php`

### `index()` — antrian verifikasi

```php
PengajuanPkl::with(['siswa.user', 'perusahaan', 'periode'])
    ->whereHas('siswa', fn ($q) => $q->where('kompetensi_keahlian_id', $guruProfile->kompetensi_keahlian_id))
    ->where('status', 'diajukan')
    ->when(request('search'), ...)
    ->paginate(10);
```

Scoping di query: hanya pengajuan yang **siswanya sejurusan dengan Kaprog** dan
statusnya `diajukan`. (Ini duplikat dari policy, tapi di level query untuk
membatasi data yang dikirim.)

### `show()` — detail + approve/reject (BR4)

```php
$this->authorize('view', $pengajuan);
$pengajuan->load(['siswa.user', 'perusahaan', 'periode', 'histories.actor']);
```

> **BR4: tombol approve/reject HANYA ada di halaman detail** — tidak di list.
> Frontend-nya: `kaprog/verifikasi/show.tsx` (dua dialog, satu untuk approve,
> satu untuk reject dengan textarea catatan).

### `approve()`

```php
$this->authorize('approve', $pengajuan);

$pengajuan->kaprog_id = (int) $request->user()->id;
$pengajuan->verified_kaprog_at = now();
$pengajuan->status = 'disetujui';
$pengajuan->save();                            // observer mencatat history

$pengajuan->siswa->user->notify(new PengajuanDiverifikasi($pengajuan, 'disetujui'));
```

### `reject()`

```php
$validated = $request->validate(['catatan_kaprog' => 'required|string|min:15']);  // ≥15 karakter

$pengajuan->kaprog_id = ...;
$pengajuan->catatan_kaprog = $validated['catatan_kaprog'];
$pengajuan->status = 'ditolak';
$pengajuan->save();

$pengajuan->siswa->user->notify(new PengajuanDiverifikasi($pengajuan, 'ditolak'));
```

Aturan **catatan minimal 15 karakter** juga diberlakukan di sisi Hubin untuk
tolak-balasan (lihat `05-persuratan`).

---

## 4.5 Audit Trail Otomatis — `app/Observers/PengajuanPklObserver.php`

```php
public function created(PengajuanPkl $pengajuan): void
{
    $pengajuan->histories()->create([
        'actor_id'    => auth()->id() ?? $pengajuan->siswa->user_id ?? 1,
        'status_dari' => null,
        'status_ke'   => $pengajuan->status,
        'catatan'     => $pengajuan->catatan_kaprog,
        'created_at'  => now(),
    ]);
}

public function updated(PengajuanPkl $pengajuan): void
{
    if ($pengajuan->isDirty('status')) {            // hanya kalau status berubah
        $pengajuan->histories()->create([
            'actor_id'    => auth()->id() ?? ...,
            'status_dari' => $pengajuan->getOriginal('status'),
            'status_ke'   => $pengajuan->status,
            'catatan'     => $pengajuan->catatan_kaprog,
            'created_at'  => now(),
        ]);
    }
}
```

- **BR6**: setiap pembuatan & perubahan status otomatis tercatat — developer
  tidak perlu menulis history secara manual (kecuali kasus khusus tolak-balasan,
  lihat `05`).
- `actor_id` = user yang login; fallback ke `siswa->user_id` (mis. saat CLI/seeder).

---

## 4.6 Notifikasi (database channel)

| Notifikasi | Dikirim ke | Kapan | Payload |
|---|---|---|---|
| `PengajuanMasukAntrian` | Kaprog sejurusan | siswa submit | title/message/pengajuan_id/type |
| `PengajuanDiverifikasi` | Siswa | approve/reject | hasil + catatan |

Semuanya `implements ShouldQueue` → diproses oleh worker queue (bagian dari
`composer dev`). Data ditampilkan via shared props `notifikasi_belum_dibaca` dan
`notifikasi_terbaru` (lihat `01`).

Contoh `app/Notifications/PengajuanMasukAntrian.php`:

```php
public function via(object $notifiable): array { return ['database']; }

public function toDatabase(object $notifiable): DatabaseMessage
{
    return new DatabaseMessage([
        'title'   => 'Pengajuan PKL Baru',
        'message' => "{$this->pengajuan->siswa->user->name} mengajukan PKL ke {$this->pengajuan->perusahaan->nama}.",
        'pengajuan_id' => $this->pengajuan->id,
        'type' => 'pengajuan_masuk',
    ]);
}
```

---

## 4.7 Ringkasan Alur

```
Siswa                       Kaprog
  │  POST /siswa/pengajuan (draft, BR1)      │
  │  POST /siswa/pengajuan/{id}/submit       │
  │      status → diajukan                   │
  │      notif PengajuanMasukAntrian ───────▶│
  │                                          │  GET /kaprog/verifikasi (antrian sejurusan)
  │                                          │  GET /kaprog/verifikasi/{id} (detail)
  │                                          │  POST .../approve | .../reject (BR4)
  │  notif PengajuanDiverifikasi ◀───────────│      status → disetujui | ditolak
  │  (ditolak → siswa bisa submit ulang)     │
```

## 4.8 File Terkait

| File | Peran |
|---|---|
| `app/Http/Controllers/Siswa/PengajuanController.php` | index/store/submit/riwayat |
| `app/Http/Controllers/Kaprog/VerifikasiController.php` | index/show/approve/reject |
| `app/Policies/PengajuanPklPolicy.php` | BR1, BR4, BR20 (upload), scoping |
| `app/Observers/PengajuanPklObserver.php` | Audit trail (BR6) |
| `app/Notifications/PengajuanMasukAntrian.php`, `PengajuanDiverifikasi.php` | Notifikasi |
| `resources/js/pages/siswa/pengajuan/index.tsx`, `siswa/riwayat/index.tsx` | Halaman siswa |
| `resources/js/pages/kaprog/verifikasi/{index,show}.tsx` | Halaman Kaprog |
