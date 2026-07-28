# Panduan M3 — Pengajuan & Verifikasi Awal

Cakupan: alur pengajuan-verifikasi Kaprog dengan state machine, audit trail (`pengajuan_pkl_histories`), infrastruktur notifikasi (Laravel DB Notification + Sonner) yang jadi basis buat M4–M6, plus Riwayat PKL.

## 1. Migration

```php
// pengajuan_pkl
Schema::create('pengajuan_pkl', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siswa_id')->constrained('siswa_profiles')->cascadeOnDelete();
    $table->foreignId('perusahaan_id')->constrained('perusahaan');
    $table->foreignId('periode_id')->constrained('periode_pkl');
    $table->string('status')->default('draft'); // dikelola spatie/laravel-model-states
    $table->foreignId('kaprog_id')->nullable()->constrained('users');
    $table->text('catatan_kaprog')->nullable();
    $table->timestamp('verified_kaprog_at')->nullable();
    $table->foreignId('hubin_id')->nullable()->constrained('users');
    $table->timestamp('surat_dikirim_at')->nullable();
    $table->timestamps();
});

// pengajuan_pkl_histories — audit trail tiap transisi status
Schema::create('pengajuan_pkl_histories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pengajuan_id')->constrained('pengajuan_pkl')->cascadeOnDelete();
    $table->foreignId('actor_id')->constrained('users');
    $table->string('status_dari')->nullable();
    $table->string('status_ke');
    $table->text('catatan')->nullable();
    $table->timestamp('created_at');
});
```
`dokumen_pkl` gak butuh migration terpisah — pakai tabel `media` bawaan spatie/laravel-medialibrary, terikat ke `pengajuan_pkl` lewat collection (`surat_balasan_perusahaan`, dst).

## 2. State machine (spatie/laravel-model-states)

```php
// app/States/Pengajuan/PengajuanState.php
abstract class PengajuanState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, DiajukanKaprog::class)
            ->allowTransition(DiajukanKaprog::class, DitolakKaprog::class)
            ->allowTransition(DiajukanKaprog::class, SiapTerbit::class)
            ->allowTransition(DitolakKaprog::class, Draft::class)
            // transisi SuratPengantarTerbit dst dipasang di M4/M6, jangan didaftarin sebelum fiturnya ada
            ;
    }
}
```
Tiap state (`Draft`, `DiajukanKaprog`, `DitolakKaprog`, `SiapTerbit`) jadi class sendiri di `app/States/Pengajuan/`, override `label()` buat teks badge status di frontend.

## 3. Audit trail otomatis

Jangan panggil manual tiap controller — pasang sekali lewat event transition:
```php
// app/Listeners/CatatRiwayatPengajuan.php
class CatatRiwayatPengajuan
{
    public function handle(StateChanged $event): void
    {
        $event->model->histories()->create([
            'actor_id' => auth()->id(),
            'status_dari' => $event->initialState?->getMorphClass(),
            'status_ke' => $event->finalState->getMorphClass(),
            'catatan' => $event->model->catatan_kaprog,
        ]);
    }
}
```
Daftarin listener ke event `StateChanged` dari `spatie/laravel-model-states` di `EventServiceProvider`. Ini jadi basis progress tracker yang dilihat siswa (BR17) — jangan bikin log manual terpisah di tiap controller, satu sumber aja.

## 4. Controller & alur

```
app/Http/Controllers/Siswa/PengajuanController.php   → store (draft), submit (Draft -> DiajukanKaprog)
app/Http/Controllers/Kaprog/VerifikasiController.php → index (queue jurusannya), show (detail), approve, reject
```
- `VerifikasiController@index` di-scope Policy ke `kompetensi_keahlian_id` milik Kaprog yang login (BR2) — jangan filter di query controller langsung, taruh scoping-nya di Policy biar konsisten dipakai ulang di `show`.
- `approve()` wajib lewat popup konfirmasi di frontend (BR4) — begitu approve, langsung transisi ke `SiapTerbit` dan otomatis masuk antrian Hubin, gak ada tombol submit manual terpisah.
- `reject()` wajib validasi `catatan_kaprog` gak boleh kosong (BR6), transisi balik ke `Draft`.

## 5. Notification infra (Laravel DB Notification + Sonner)

```bash
php artisan make:notification PengajuanMasukAntrian
php artisan make:notification PengajuanDiverifikasi
```
- Pakai `channels => ['database']`, disimpan ke tabel `notifications` bawaan Laravel.
- Frontend: `Inertia::share('notifikasi_belum_dibaca', fn () => auth()->user()?->unreadNotifications()->count())`, tampilin lewat toast Sonner pas ada notif baru + badge counter di layout sidebar.
- Trigger di M3: notif ke Kaprog pas pengajuan masuk antrian, notif ke siswa pas disetujui/ditolak. Trigger sisanya (surat terbit, dst) nyusul di M4–M6 pakai pattern yang sama — jangan bikin ulang infrastrukturnya.

## 6. Riwayat PKL

Halaman list di dashboard Siswa yang nampilin `pengajuan_pkl` milik siswa dari periode-periode sebelumnya (BR14) — cukup query biasa `where('siswa_id', ...)->where('periode_id', '!=', $periodeAktif)`, gak ada tabel/status khusus buat "PKL ulang".

## Checkpoint akhir M3
- Siswa bisa isi & submit pengajuan, statusnya jelas kebaca sebagai stepper visual dari data `pengajuan_pkl_histories`.
- Kaprog cuma lihat antrian dari jurusannya sendiri (coba login sebagai 2 Kaprog beda jurusan, pastiin querinya kescope bener).
- Approve via popup konfirmasi → status otomatis `SiapTerbit`, gak ada langkah manual tambahan.
- Reject tanpa catatan ditolak validasi; reject dengan catatan balikin status ke `Draft` dan siswa bisa lihat alasannya.
- Tiap transisi status masuk ke `pengajuan_pkl_histories` otomatis, tanpa insert manual di controller.
- Notifikasi database + toast Sonner muncul buat kedua notif yang udah di-trigger di M3.
- Riwayat PKL nampilin pengajuan periode lama kalau ada data dummy multi-periode.
