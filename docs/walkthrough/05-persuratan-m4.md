# Walkthrough SI-PKL — 05. Persuratan (M4)

Modul persuratan adalah yang **paling kompleks** di milestone M1–M4. Ada dua
surat yang berperan:

1. **Surat Pengantar** — diterbitkan Hubin (bundling beberapa siswa per perusahaan).
2. **Surat Balasan** — diunggah siswa sebagai balasan dari perusahaan.

Alur singkatnya:

```
pengajuan disetujui → Hubin bundling & terbit Surat Pengantar (BR17/BR18)
                    → siswa upload Surat Balasan (BR20) → status otomatis resmi_pkl (BR7)
```

---

## 5.1 Route — `routes/web.php:100-108`

```php
Route::prefix('surat-pengantar')->name('surat-pengantar.')->middleware('permission:surat.terbitkan')->group(function () {
    Route::get('/',                [SuratPengantarController::class, 'index'])->name('index');
    Route::get('{perusahaan}/{periode}', ...)->name('preview');
    Route::get('{perusahaan}/{periode}/cetak', ...)->name('cetak');
    Route::post('{perusahaan}/{periode}/kirim', ...)->name('kirim');
    Route::get('download/{suratPengantar}', ...)->name('download');
    Route::post('{pengajuan}/tolak-balasan', ...)->name('tolak-balasan')
        ->middleware('permission:surat-balasan.verifikasi');
});
```

Semua route kecuali `download` & `tolak-balasan` butuh `surat.terbitkan`.
`tolak-balasan` butuh permission tambahan `surat-balasan.verifikasi`.

---

## 5.2 `index()` — daftar perusahaan siap bundling

```php
$periode = PeriodePkl::where('status_aktif', true)->first();

$perusahaans = Perusahaan::query()
    ->when($periode, fn ($q) => $q->whereHas('pengajuanPkls', fn ($q2) => $q2
        ->where('periode_id', $periode->id)
        ->where('status', 'disetujui')))
    ->withCount(['pengajuanPkls as jumlah_siap' => fn ($q) => $q
        ->when($periode, fn ($q2) => $q2->where('periode_id', $periode->id))
        ->where('status', 'disetujui')])
    ->get();
```

- Hanya menampilkan perusahaan yang **punya pengajuan `disetujui`** di periode
  aktif.
- `jumlah_siap` = jumlah siswa "siap surat" per perusahaan (dipakai di kartu list).

---

## 5.3 `preview()` — dua tab per perusahaan+periode

```php
$pengajuans = PengajuanPkl::with(['siswa.user', 'siswa.kompetensiKeahlian'])
    ->where('perusahaan_id', $perusahaan->id)
    ->where('periode_id', $periode->id)
    ->where('status', 'disetujui')       // Tab 1: "Kirim Surat"
    ->get();

$balasanMasuk = PengajuanPkl::with(['siswa.user', 'suratBalasan'])
    ->where(...)->where('status', 'resmi_pkl')   // Tab 2: "Balasan Masuk"
    ->get();

$lewatKuota = $pengajuans->count() > $perusahaan->kuota_saran;  // warning kuota
```

Props ke halaman `hubin/surat-pengantar/preview`: perusahaan, periode, pengajuans,
balasanMasuk, lewatKuota. Halaman ini punya tombol Cetak (PDF) dan Kirim Surat.

---

## 5.4 `cetak()` — preview PDF (TIDAK membuat record)

```php
$pengajuans = ...;   // sama seperti preview

// Objek surat SEMENTARA — tidak disimpan ke DB
$suratSementara = new SuratPengantar([
    'nomor_surat' => '___/421.5/SMKN 1-Cadisdik.Wil.II/___/'.now()->year,
    'tanggal_terbit' => now()->toDateString(),
]);
$suratSementara->setRelation('perusahaan', $perusahaan);
$suratSementara->setRelation('periode', $periode);
$suratSementara->setRelation('pengajuans', $pengajuans);

$pdf = Pdf::loadView('pdf.surat-pengantar', ['suratPengantar' => $suratSementara])
    ->setPaper('a4', 'portrait');

return $pdf->download("surat-pengantar-preview-{$perusahaan->id}.pdf");
```

Poin penting: `cetak` **murni preview** — memakai model `SuratPengantar` tanpa
`save()` (`setRelation` dipakai untuk memberi relasi ke view PDF). Nomor surat
masih placeholder `___`. Tidak mengubah status apa pun.

---

## 5.5 `kirim()` — terbit surat secara resmi (dalam transaksi)

Ini method terpenting di modul ini. Berjalan dalam **satu transaksi DB** agar
semua-atau-tidak:

```php
$suratPengantar = DB::transaction(function () use ($request, $perusahaan, $periode) {
    $pengajuans = PengajuanPkl::with(['siswa.user', 'siswa.kompetensiKeahlian'])
        ->where('perusahaan_id', $perusahaan->id)
        ->where('periode_id', $periode->id)
        ->where('status', 'disetujui')
        ->get();

    abort_if($pengajuans->isEmpty(), 422, 'Tidak ada pengajuan yang siap untuk perusahaan ini.');

    // (1) Nomor surat sequential
    $nomorSurat = PenomoranSurat::suratPengantar($periode->id);

    // (2) Simpan record surat
    $surat = SuratPengantar::create([
        'nomor_surat'   => $nomorSurat,
        'perusahaan_id' => $perusahaan->id,
        'periode_id'    => $periode->id,
        'tanggal_terbit' => now()->toDateString(),
        'generated_by'  => $request->user()->id,
    ]);

    // (3) Attach semua pengajuan ke pivot surat_pengantar_pengajuan
    $surat->pengajuans()->attach($pengajuans->pluck('id'));

    // (4) Generate PDF final
    $surat->setRelation('perusahaan', $perusahaan);
    $surat->setRelation('periode', $periode);
    $surat->setRelation('pengajuans', $pengajuans);
    $pdf = Pdf::loadView('pdf.surat-pengantar', ['suratPengantar' => $surat])
        ->setPaper('a4', 'portrait');

    // (5) Simpan file PDF ke storage
    $namaFile = "surat-pengantar/{$surat->id}-{$nomorSurat}.pdf";
    $namaFile = preg_replace('/[\/\\\:*?"<>|]/', '_', $namaFile);  // amankan karakter file
    Storage::put($namaFile, $pdf->output());
    $surat->update(['file_path' => $namaFile]);

    return $surat;
});

// Setelah transaksi sukses → notifikasi ke semua siswa terkait
$suratPengantar->pengajuans->each(function ($pengajuan) {
    $pengajuan->siswa?->user->notify(new SuratPengantarTerbit($pengajuan));
});
```

Urutan logika:

1. Ambil pengajuan `disetujui` milik perusahaan+periode; abort kalau kosong.
2. Minta **nomor surat berikutnya** dari `PenomoranSurat` (BR17/BR18).
3. Buat record `surat_pengantar`.
4. Hubungkan siswa ke surat lewat pivot `surat_pengantar_pengajuan`.
5. Generate PDF dompdf dari Blade `resources/views/pdf/surat-pengantar.blade.php`.
6. Simpan PDF ke storage (`storage/app/private/surat-pengantar/`).
7. Di luar transaksi: kirim notifikasi `SuratPengantarTerbit` ke tiap siswa.

> Catatan: status pengajuan TIDAK langsung berubah saat surat terbit. Siswa
> menyadari suratnya terbit via notifikasi + aksesor `exists_surat_pengantar`
> yang membuat tombol upload balasan muncul (BR20).

---

## 5.6 `PenomoranSurat` — nomor surat sequential (BR17/BR18)

`app/Support/PenomoranSurat.php`:

```php
public static function suratPengantar(int $periodeId): string
{
    $query = SuratPengantar::where('periode_id', $periodeId);
    if (DB::connection()->getDriverName() !== 'sqlite') {
        $query->lockForUpdate();   // cegah race condition (BR17)
    }
    $urut = $query->count() + 1;

    return self::format($urut);
}

private static function format(int $urut): string
{
    $bulanRomawi = self::toRomawi(now()->month);
    $tahun = now()->year;
    $wilayah = self::NO_WILAYAH;   // 'II'

    return "{$urut}/421.5/SMKN 1-Cadisdik.Wil.{$wilayah}/{$bulanRomawi}/{$tahun}";
}
```

- Nomor **sequential per periode per tahun** (di-hitung ulang dari jumlah record
  periode itu + 1).
- `lockForUpdate()` memastikan dua request `kirim` bersamaan tidak mendapat nomor
  yang sama (BR17). Di SQLite (`:memory:` dev/test) `lockForUpdate` di-skip
  karena tidak didukung.
- Format akhir (BR18): `1/421.5/SMKN 1-Cadisdik.Wil.II/VIII/2026`

> **Catatan ceiling (ponytail):** penomoran ini count-based, bukan kolom counter.
> Dengan `lockForUpdate` masih aman untuk skala sekolah ini; upgrade ke kolom
> `nomor_terakhir` per periode kalau throughput naik drastis.

---

## 5.7 `download()` — ambil PDF tersimpan

```php
$this->authorize('view', $suratPengantar);
abort_if(! $suratPengantar->file_path || ! Storage::exists($suratPengantar->file_path), 404);

return response(Storage::get($suratPengantar->file_path))
    ->header('Content-Type', 'application/pdf')
    ->header('Content-Disposition', 'attachment; filename="surat-pengantar-'.$suratPengantar->id.'.pdf"');
```

- `SuratPengantarPolicy::view` mengizinkan Hubin atau siswa yang pengajuannya
  ada di surat tersebut.

---

## 5.8 Sisi Siswa — `SuratBalasanController`

Route (permission `surat-balasan.upload`):

```
POST /siswa/pengajuan/{pengajuan}/surat-balasan   store    (upload)
GET  /siswa/pengajuan/{pengajuan}/surat-balasan   download (ambil PDF sendiri)
```

### `store()` — upload balasan (BR20 gate + BR7 auto resmi)

```php
$this->authorize('uploadBalasan', $pengajuan);   // lapis 1 (policy)

// BR20 — lapis 2: cek surat pengantar via relasi
abort_unless(
    $pengajuan->suratPengantars()->exists(),
    403,
    'Upload surat balasan belum tersedia. Surat Pengantar belum diterbitkan untuk pengajuan ini.'
);

$validated = $request->validate([
    'file' => ['required', 'file', 'mimes:pdf', 'max:5120'],   // PDF, ≤5MB
]);

// Upload ulang: hapus file & record lama
if ($pengajuan->suratBalasan) {
    Storage::delete($pengajuan->suratBalasan->file_path);
    $pengajuan->suratBalasan->delete();
}

$path = $request->file('file')->store('surat-balasan');
SuratBalasan::create(['pengajuan_id' => $pengajuan->id, 'file_path' => $path]);

// BR7 — status otomatis resmi_pkl (tidak ada tombol manual)
$pengajuan->status = 'resmi_pkl';
$pengajuan->save();

$pengajuan->siswa?->user->notify(new SuratBalasanDiupload($pengajuan));
```

Aturan bisnis yang di-enforce di sini:

- **BR20**: dua lapis gate — policy `uploadBalasan` (perlu status `disetujui`
  + surat pengantar ada) dan `abort_unless` ulang di controller.
- **BR7**: setelah file valid, status **langsung** `resmi_pkl` — otomatis, tanpa
  tombol/konfirmasi Hubin.
- Upload ulang menghapus balasan lama (replace file).

### `download()`

```php
$this->authorize('lihatSendiri', $pengajuan);   // hanya pemilik
abort_if(! $suratBalasan || ! Storage::exists(...), 404);
return response(Storage::get(...))->header('Content-Type', 'application/pdf')...;
```

---

## 5.9 `balasanMasuk()` & `tolakBalasan()` — verifikasi pasca-fakta (BR7 reverse)

### `balasanMasuk()` — tab daftar pengajuan `resmi_pkl`

```php
PengajuanPkl::with(['siswa.user', 'suratBalasan'])
    ->where('perusahaan_id', $perusahaan->id)
    ->where('periode_id', $periode->id)
    ->where('status', 'resmi_pkl')
    ->get();
```

Catatan: method ini terdefinisi di controller, tapi route-nya tidak eksplisit
(memakai data `balasanMasuk` dari `preview`). Daftar pengajuan yang sudah resmi
dipakai Hubin untuk melihat/verifikasi balasan masuk.

### `tolakBalasan()` — tolak balasan yang sudah terupload

```php
$this->authorize('tolakBalasan', $pengajuan);   // hubin + status resmi_pkl

$validated = $request->validate(['catatan' => 'required|string|min:15']);

DB::transaction(function () use ($pengajuan, $validated, $request) {
    // Hapus record surat balasan
    $pengajuan->suratBalasan?->delete();

    // Status kembali ke disetujui (bisa upload ulang)
    $pengajuan->status = 'disetujui';
    $pengajuan->catatan_kaprog = null;   // bersihkan catatan lama
    $pengajuan->save();

    // History MANUAL — observer tidak menyimpan catatan hubin (hanya catatan_kaprog)
    $pengajuan->histories()->create([
        'actor_id'    => $request->user()->id,
        'status_dari' => 'resmi_pkl',
        'status_ke'   => 'disetujui',
        'catatan'     => '[Hubin] Surat balasan ditolak: '.$validated['catatan'],
        'created_at'  => now(),
    ]);
});

$pengajuan->siswa?->user->notify(new SuratBalasanDitolak($pengajuan, $validated['catatan']));
```

Poin penting:

- **Back-edge state machine**: `resmi_pkl → disetujui` (bukan ke `diajukan`).
- History ditulis **manual** karena `PengajuanPklObserver` hanya mencatat
  `catatan_kaprog` — alasan tolak dari Hubin tidak akan tersimpan otomatis.
  (Komentar di kode baris 221–223 menjelaskan ini.)
- Catatan ditolak minimal 15 karakter, sama seperti reject Kaprog.

---

## 5.10 Template PDF — `resources/views/pdf/surat-pengantar.blade.php`

Template Blade standar surat dinas:

- Kop sekolah (logo SMKN 1 Kota Bekasi, nama, alamat).
- Nomor surat dari `$suratPengantar->nomor_surat`.
- Tabel daftar siswa (nama, kelas, jurusan) dari `$suratPengantar->pengajuans`.
- Kolom TTD.

Digunakan oleh `cetak()` (surat sementara) dan `kirim()` (surat final).

---

## 5.11 Ringkasan Alur Persuratan

```
Hubin                              Siswa
  │  GET /hubin/surat-pengantar (list perusahaan siap)        │
  │  GET .../{perusahaan}/{periode}  → preview 2 tab          │
  │  GET .../cetak  → PDF preview (tanpa record)              │
  │  POST .../kirim → DB::transaction:                        │
  │      • PenomoranSurat (lockForUpdate, BR17)               │
  │      • SuratPengantar::create + attach pivot              │
  │      • PDF dompdf → storage                               │
  │  notif SuratPengantarTerbit ─────────────────────────────▶│
  │                                                            │  upload PDF balasan (BR20, ≤5MB)
  │                                                            │  status → resmi_pkl otomatis (BR7)
  │  notif SuratBalasanDiupload ◀─────────────────────────────│
  │  POST .../tolak-balasan (catatan ≥15)                     │
  │      hapus balasan, status → disetujui                    │
  │  notif SuratBalasanDitolak ──────────────────────────────▶│  → siswa upload ulang
```

## 5.12 File Terkait

| File | Peran |
|---|---|
| `app/Http/Controllers/Hubin/SuratPengantarController.php` | index/preview/cetak/kirim/download/balasanMasuk/tolakBalasan |
| `app/Http/Controllers/Siswa/SuratBalasanController.php` | store/download (BR20, BR7) |
| `app/Support/PenomoranSurat.php` | Nomor surat sequential (BR17/BR18) |
| `app/Policies/SuratPengantarPolicy.php` | Otorisasi surat |
| `app/Notifications/{SuratPengantarTerbit,SuratBalasanDiupload,SuratBalasanDitolak}.php` | Notifikasi |
| `resources/views/pdf/surat-pengantar.blade.php` | Template PDF |
| `resources/js/pages/hubin/surat-pengantar/{index,preview}.tsx` | Halaman Hubin |
