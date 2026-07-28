# Panduan M4 — Persuratan

Cakupan: generate PDF, bundling per perusahaan, preview + Cetak/Kirim terpisah, lock upload balasan (BR20), auto Resmi PKL (BR7).

## 1. Migration

```php
// surat_pengantar
Schema::create('surat_pengantar', function (Blueprint $table) {
    $table->id();
    $table->foreignId('perusahaan_id')->constrained('perusahaan');
    $table->foreignId('periode_id')->constrained('periode_pkl');
    $table->string('nomor_surat')->unique();
    $table->date('tanggal_terbit');
    $table->foreignId('generated_by')->constrained('users');
    $table->timestamps();
});

// pivot — 1 surat pengantar bisa nampung banyak pengajuan (maks 6 siswa)
Schema::create('surat_pengantar_pengajuan', function (Blueprint $table) {
    $table->id();
    $table->foreignId('surat_pengantar_id')->constrained('surat_pengantar')->cascadeOnDelete();
    $table->foreignId('pengajuan_id')->constrained('pengajuan_pkl')->cascadeOnDelete();
});
```
File PDF-nya sendiri disimpan lewat spatie/laravel-medialibrary, terikat ke `surat_pengantar` (collection `file`).

## 2. Update state machine

Tambah transisi baru ke `PengajuanState::config()` dari M3 — jangan bikin file state config baru terpisah:
```php
->allowTransition(SiapTerbit::class, SuratPengantarTerbit::class)
->allowTransition(SuratPengantarTerbit::class, MenungguBalasanPerusahaan::class)
->allowTransition(MenungguBalasanPerusahaan::class, ResmiPkl::class)
```

## 3. Generate PDF (spatie/laravel-pdf)

```php
use Spatie\LaravelPdf\Facades\Pdf;

Pdf::view('pdf.surat-pengantar', ['suratPengantar' => $suratPengantar])
    ->format('a4')
    ->save(storage_path('app/temp/surat-pengantar.pdf'));
```
- View `resources/views/pdf/surat-pengantar.blade.php` pakai Tailwind lewat CDN/inline (spatie/laravel-pdf render via headless Chrome, jadi class Tailwind biasa jalan).
- Kop surat, nomor surat (format BR18), daftar siswa (maks 6) diambil dari relasi `surat_pengantar_pengajuan`.
- Nomor surat di-generate lewat helper terpusat, bukan di-hardcode di view:
  ```php
  // app/Support/PenomoranSurat.php
  class PenomoranSurat
  {
      public static function suratPengantar(int $noUrut, int $noWilayah): string
      {
          $bulanRomawi = self::romawi(now()->month);
          return "{$noUrut}/421.5/SMKN 1-Cadisdik.Wil.{$noWilayah}/{$bulanRomawi}/" . now()->year;
      }
  }
  ```

## 4. Bundling per perusahaan

```php
// app/Http/Controllers/Hubin/SuratPengantarController.php
public function preview(Perusahaan $perusahaan, PeriodePkl $periode)
{
    $pengajuanSiap = PengajuanPkl::query()
        ->where('perusahaan_id', $perusahaan->id)
        ->where('periode_id', $periode->id)
        ->whereState('status', SiapTerbit::class)
        ->get();

    // BR3: kuota 6 siswa itu soft rule — tampilin warning, jangan di-block
    $lewatKuota = $pengajuanSiap->count() > $perusahaan->kuota_saran;

    return Inertia::render('hubin/surat-pengantar/preview', compact('pengajuanSiap', 'lewatKuota'));
}
```
Tombol **Cetak** (download PDF) dan **Kirim ke Siswa** (transisi status + trigger notifikasi) dipisah — Hubin bisa preview & cetak berkali-kali sebelum benar-benar "Kirim" yang mengubah state.

## 5. BR20 — lock upload Surat Balasan

Di frontend Siswa, komponen upload balasan perusahaan di-disable/hidden sampai `pengajuan->status` == `SuratPengantarTerbit` atau lanjutannya. Validasi wajib juga di backend (jangan cuma disable tombol di UI):
```php
public function uploadBalasan(Request $request, PengajuanPkl $pengajuan)
{
    abort_unless($pengajuan->status instanceof SuratPengantarTerbit, 403);
    // ...
}
```

## 6. BR7 — auto Resmi PKL

Begitu file balasan perusahaan berhasil diupload, transisi otomatis tanpa approval manual:
```php
public function uploadBalasan(Request $request, PengajuanPkl $pengajuan)
{
    abort_unless($pengajuan->status instanceof SuratPengantarTerbit, 403);

    $pengajuan->addMediaFromRequest('file')->toMediaCollection('surat_balasan_perusahaan');
    $pengajuan->status->transitionTo(ResmiPkl::class);

    return back();
}
```

## Checkpoint akhir M4
- Preview surat pengantar nampilin daftar siswa yang benar per perusahaan+periode, warning muncul kalau lewat 6 siswa (tapi tetap bisa lanjut cetak).
- Tombol Cetak menghasilkan PDF dengan nomor surat sesuai format BR18, gak mengubah status.
- Tombol Kirim mengubah status jadi `SuratPengantarTerbit` dan trigger notifikasi ke siswa terkait.
- Upload balasan ditolak backend (bukan cuma UI) kalau status belum `SuratPengantarTerbit`.
- Upload balasan sukses langsung mengubah status jadi `ResmiPkl` otomatis tanpa ada approval tambahan dari siapa pun.
- Riwayat status (dari M3) mencatat transisi-transisi baru ini dengan benar.
