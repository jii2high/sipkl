# Panduan M6 — Penilaian Akhir & Selesai PKL

Cakupan: upload Laporan Akhir PKL, Formulir Penilaian yang gated (BR32), kalkulasi Nilai Akhir otomatis (BR28), auto Selesai PKL (BR13).

## 1. Migration

```php
// formulir_penilaian
Schema::create('formulir_penilaian', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pengajuan_id')->constrained('pengajuan_pkl')->cascadeOnDelete();
    $table->foreignId('guru_pembimbing_id')->constrained('users');
    $table->unsignedTinyInteger('sakit')->default(0);
    $table->unsignedTinyInteger('izin')->default(0);
    $table->unsignedTinyInteger('alpa')->default(0);
    $table->text('catatan_guru_pembimbing')->nullable();
    $table->string('nama_pembimbing_industri')->nullable();
    $table->decimal('nilai_akhir', 5, 2)->nullable(); // dihitung otomatis, bukan input manual
    $table->timestamps();
});

// nilai_penilaian — leaf level, terikat ke indikator ATAU section (salah satu)
Schema::create('nilai_penilaian', function (Blueprint $table) {
    $table->id();
    $table->foreignId('formulir_penilaian_id')->constrained('formulir_penilaian')->cascadeOnDelete();
    $table->foreignId('indikator_penilaian_id')->nullable()->constrained('indikator_penilaian');
    $table->foreignId('section_penilaian_id')->nullable()->constrained('section_penilaian');
    $table->decimal('nilai', 5, 2);
    $table->char('predikat', 2); // K/C/B/SB, dihitung dari skala fixed BR25
    $table->timestamps();
});
```

## 2. Upload Laporan Akhir → buka gate (BR32)

```php
// app/Http/Controllers/Siswa/LaporanAkhirController.php
public function store(Request $request, PengajuanPkl $pengajuan)
{
    $this->authorize('update', $pengajuan);

    $pengajuan->addMediaFromRequest('laporan_akhir')->toMediaCollection('laporan_akhir');
    $pengajuan->update(['laporan_akhir_uploaded_at' => now()]);

    // notifikasi ke Guru Pembimbing: Formulir Penilaian sekarang bisa diisi
    $pengajuan->penugasan->guruPembimbing->notify(new FormulirPenilaianTerbuka($pengajuan));
}
```
Gate-nya cukup kolom `laporan_akhir_uploaded_at` (nullable timestamp) di `pengajuan_pkl` — bukan state terpisah di state machine, karena ini independen dari alur `PengajuanState` (BR32 catatan v1.2: gate eksplisit, bukan bagian dari transisi status utama).

## 3. Formulir Penilaian — gated + skala predikat fixed (BR24, BR25)

```php
// app/Http/Controllers/GuruPembimbing/FormulirPenilaianController.php
public function create(PengajuanPkl $pengajuan)
{
    abort_unless($pengajuan->laporan_akhir_uploaded_at, 403, 'Laporan Akhir PKL belum diupload siswa.');

    $indikatorUmum = IndikatorPenilaian::where('kategori', 'umum')->with('sections')->get();
    $indikatorTeknis = IndikatorPenilaian::where('kategori', 'teknis_kejuruan')
        ->where('kompetensi_keahlian_id', $pengajuan->siswa->kompetensi_keahlian_id)
        ->with('sections')
        ->get();

    return Inertia::render('guru-pembimbing/penilaian/form', compact('pengajuan', 'indikatorUmum', 'indikatorTeknis'));
}
```
Helper skala predikat (BR25), dipakai tiap kali `nilai_penilaian` disimpan:
```php
// app/Support/SkalaPredikat.php
class SkalaPredikat
{
    public static function dari(float $nilai): string
    {
        return match (true) {
            $nilai < 70 => 'K',
            $nilai <= 80 => 'C',
            $nilai <= 90 => 'B',
            default => 'SB',
        };
    }
}
```

## 4. Kalkulasi Nilai Akhir otomatis (BR28)

Rata-rata dari leaf yang **terisi** aja, bukan seluruh indikator di master (karena section nullable per jurusan — BR24):
```php
// app/Actions/Penilaian/HitungNilaiAkhir.php
class HitungNilaiAkhir
{
    public function handle(FormulirPenilaian $formulir): void
    {
        $rataRata = $formulir->nilaiPenilaian()->avg('nilai');
        $formulir->update(['nilai_akhir' => round($rataRata, 2)]);
    }
}
```
Panggil action ini di akhir `store()`/`update()` formulir, setelah semua baris `nilai_penilaian` disimpan — jangan hitung on-the-fly di frontend, biar sumber kebenarannya di backend.

## 5. Auto Selesai PKL (BR13)

Dua syarat harus ketemu sekaligus: periode berakhir DAN laporan akhir sudah diupload. Paling aman lewat scheduled command, bukan cuma dicek pas ada request masuk (karena syarat "periode berakhir" bisa kejadian kapan aja tanpa ada yang buka aplikasi):
```php
// app/Console/Commands/TandaiSelesaiPkl.php
class TandaiSelesaiPkl extends Command
{
    protected $signature = 'pkl:tandai-selesai';

    public function handle(): void
    {
        PengajuanPkl::whereState('status', ResmiPkl::class)
            ->whereNotNull('laporan_akhir_uploaded_at')
            ->whereHas('periode', fn ($q) => $q->where('tanggal_selesai', '<', now()))
            ->each(fn ($pengajuan) => $pengajuan->status->transitionTo(SelesaiPkl::class));
    }
}
```
Daftarin ke scheduler (`routes/console.php`, jalan harian). Tambah juga transisi baru ini ke `PengajuanState::config()` (`allowTransition(ResmiPkl::class, SelesaiPkl::class)`), reuse config yang sama dari M3/M4, jangan bikin baru.

## Checkpoint akhir M6
- Upload Laporan Akhir langsung bikin Formulir Penilaian kebuka buat Guru Pembimbing terkait (coba akses sebelum upload, harus ketolak 403).
- Formulir Penilaian nampilin indikator Umum + Teknis Kejuruan sesuai jurusan siswa yang benar, section yang nullable gak muncul kalau emang gak ada.
- Simpan nilai per indikator/section otomatis dapat predikat yang benar sesuai skala BR25 (tes di batas: 69, 70, 80, 81, 90, 91).
- Nilai Akhir di Formulir keitung otomatis dari rata-rata leaf yang keisi aja, coba dengan jurusan yang punya section vs yang enggak.
- Command `pkl:tandai-selesai` cuma nyentuh pengajuan yang periode-nya udah lewat DAN laporan akhirnya udah masuk — coba data yang cuma penuhin salah satu syarat, harus tetap `ResmiPkl`.
