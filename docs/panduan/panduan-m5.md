# Panduan M5 — Pembimbingan & Kunjungan Monitoring

Cakupan: assignment Guru Pembimbing (eksklusif Kaprog, termasuk self-assign), Surat Tugas Monitoring Global/Scoped, Laporan Kunjungan yang gated oleh Surat Tugas.

## 1. Migration

```php
// penugasan_pembimbing
Schema::create('penugasan_pembimbing', function (Blueprint $table) {
    $table->id();
    $table->foreignId('guru_pembimbing_id')->constrained('users');
    $table->foreignId('pengajuan_id')->constrained('pengajuan_pkl')->cascadeOnDelete();
    $table->foreignId('assigned_by')->constrained('users'); // selalu Kaprog, termasuk self-assign
    $table->timestamps();
});

// surat_tugas_monitoring
Schema::create('surat_tugas_monitoring', function (Blueprint $table) {
    $table->id();
    $table->foreignId('periode_id')->constrained('periode_pkl');
    $table->enum('lingkup', ['global', 'scoped']);
    $table->foreignId('kompetensi_keahlian_id')->nullable()->constrained('kompetensi_keahlian'); // wajib isi kalau scoped
    $table->foreignId('diterbitkan_by')->constrained('users');
    $table->foreignId('requested_by')->nullable()->constrained('users'); // Kaprog, khusus scoped
    $table->timestamps();
});

// kunjungan_monitoring
Schema::create('kunjungan_monitoring', function (Blueprint $table) {
    $table->id();
    $table->foreignId('penugasan_id')->constrained('penugasan_pembimbing')->cascadeOnDelete();
    $table->foreignId('surat_tugas_id')->constrained('surat_tugas_monitoring');
    $table->date('tanggal_kunjungan');
    $table->text('catatan')->nullable();
    $table->timestamps();
});
```
Dokumentasi foto/video kunjungan pakai medialibrary, collection `dokumentasi` terikat ke `kunjungan_monitoring`.

## 2. Assignment Guru Pembimbing (BR10)

```php
// app/Http/Controllers/Kaprog/PenugasanController.php
public function store(Request $request, PengajuanPkl $pengajuan)
{
    $this->authorize('assign', $pengajuan); // Policy: Kaprog cuma boleh assign siswa jurusannya sendiri

    $request->validate(['guru_pembimbing_id' => 'required|exists:users,id']);

    PenugasanPembimbing::create([
        'guru_pembimbing_id' => $request->guru_pembimbing_id,
        'pengajuan_id' => $pengajuan->id,
        'assigned_by' => auth()->id(),
    ]);
}
```
- Self-assign: form assignment nampilin nama Kaprog sendiri juga sebagai opsi (dia juga punya `user_id`, cuma jabatannya kaprog) — bukan endpoint terpisah, cukup opsi normal di dropdown yang sama.
- Section **"Bimbingan Saya"** di dashboard Kaprog cuma query `penugasan_pembimbing` where `guru_pembimbing_id = auth()->id()` — reuse komponen kunjungan & (nanti M6) formulir penilaian yang sama persis dipakai role Guru Pembimbing, jangan duplikasi component.

## 3. Surat Tugas Monitoring (BR22, BR23)

```php
// app/Http/Controllers/Hubin/SuratTugasController.php
public function store(Request $request)
{
    $data = $request->validate([
        'periode_id' => 'required|exists:periode_pkl,id',
        'lingkup' => 'required|in:global,scoped',
        'kompetensi_keahlian_id' => 'required_if:lingkup,scoped|nullable|exists:kompetensi_keahlian,id',
    ]);

    $suratTugas = SuratTugasMonitoring::create([
        ...$data,
        'diterbitkan_by' => auth()->id(),
        'requested_by' => $request->input('requested_by'), // null kalau inisiatif Hubin sendiri
    ]);

    // generate PDF pakai pola PenomoranSurat yang sama dari M4
}
```
Request scoped dari Kaprog: halaman/tombol kecil di dashboard Kaprog yang cuma bikin notifikasi ke Hubin (bukan langsung create record) — keputusan terbit tetap di tangan Hubin.

## 4. Laporan Kunjungan — gated oleh Surat Tugas (BR22)

```php
public function store(Request $request, PenugasanPembimbing $penugasan)
{
    $this->authorize('kunjungan', $penugasan); // ownership: guru_pembimbing_id === auth()->id() ATAU self-assign Kaprog

    $suratTugas = SuratTugasMonitoring::query()
        ->where('periode_id', $penugasan->pengajuan->periode_id)
        ->where(function ($q) use ($penugasan) {
            $q->where('lingkup', 'global')
              ->orWhere('kompetensi_keahlian_id', $penugasan->pengajuan->siswa->kompetensi_keahlian_id);
        })
        ->first();

    abort_unless($suratTugas, 422, 'Surat Tugas Monitoring belum diterbitkan untuk periode/jurusan ini.');

    $kunjungan = KunjunganMonitoring::create([
        'penugasan_id' => $penugasan->id,
        'surat_tugas_id' => $suratTugas->id,
        'tanggal_kunjungan' => $request->tanggal_kunjungan,
        'catatan' => $request->catatan,
    ]);

    $kunjungan->addMediaFromRequest('dokumentasi')->toMediaCollection('dokumentasi');
}
```

## Checkpoint akhir M5
- Kaprog cuma bisa assign Guru Pembimbing buat siswa jurusannya sendiri (coba lintas jurusan, harus ketolak Policy).
- Self-assign Kaprog jadi pembimbing muncul di section "Bimbingan Saya" dan berfungsi sama persis kayak Guru Pembimbing biasa (bukan halaman/kode terpisah).
- Hubin bisa terbitkan Surat Tugas Global (semua jurusan langsung ke-cover) dan Scoped (satu jurusan, bisa dikaitkan ke request Kaprog).
- Guru Pembimbing gak bisa isi Laporan Kunjungan kalau Surat Tugas buat periode/jurusannya belum ada — coba isi sebelum surat terbit, harus ditolak backend.
- Setelah surat tugas terbit, form Laporan Kunjungan bisa diisi berkali-kali sepanjang periode (bukan sekali doang).
