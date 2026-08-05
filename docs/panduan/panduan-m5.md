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
    $table->enum('status', ['aktif', 'nonaktif'])->default('aktif'); // nonaktif saat diganti (BR30)
    $table->timestamp('ended_at')->nullable(); // terisi saat status jadi nonaktif
    $table->timestamps();
});

// surat_tugas_monitoring
Schema::create('surat_tugas_monitoring', function (Blueprint $table) {
    $table->id();
    $table->foreignId('periode_pkl_id')->constrained('periode_pkl');
    $table->enum('lingkup', ['global', 'scoped']);
    $table->foreignId('kompetensi_keahlian_id')->nullable()->constrained('kompetensi_keahlian'); // wajib isi kalau scoped
    $table->foreignId('requested_by')->nullable()->constrained('users'); // Kaprog, khusus scoped
    $table->foreignId('diterbitkan_by')->constrained('users'); // Hubin
    $table->string('nomor_surat')->unique(); // BR18, sequential per jenis+periode
    $table->date('tanggal_terbit');
    $table->string('file'); // path arsip PDF
    $table->enum('status', ['aktif', 'nonaktif'])->default('aktif'); // nonaktif = dicabut Hubin
    $table->date('berlaku_hingga')->nullable(); // null = berlaku penuh sepanjang periode (BR22)
    $table->timestamps();
});

// kunjungan_monitoring
Schema::create('kunjungan_monitoring', function (Blueprint $table) {
    $table->id();
    $table->foreignId('penugasan_id')->constrained('penugasan_pembimbing')->cascadeOnDelete();
    $table->foreignId('surat_tugas_id')->constrained('surat_tugas_monitoring');
    $table->date('tanggal_kunjungan');
    $table->text('catatan')->nullable();
    $table->string('dokumentasi_path')->nullable(); // path file foto/video, boleh JSON/array untuk banyak file
    $table->timestamps();
});
```

Dokumentasi foto/video kunjungan disimpan sebagai kolom path biasa di `kunjungan_monitoring.dokumentasi_path` — konsisten dengan keputusan M4 (bukan medialibrary). Kolom boleh menampung satu path atau banyak path (JSON/array) bila satu kunjungan punya lebih dari satu file.

## 2. Assignment Guru Pembimbing (BR10)

```php
// app/Http/Controllers/Kaprog/PenugasanController.php
public function store(Request $request, PengajuanPkl $pengajuan)
{
    $this->authorize('assign', $pengajuan); // Policy: Kaprog cuma boleh assign siswa jurusannya sendiri

    $request->validate(['guru_pembimbing_id' => 'required|exists:users,id']);

    // BR30: penggantian pembimbing → arsipkan assignment aktif yang lama dulu
    $pengajuan->penugasanAktif()?->update(['status' => 'nonaktif', 'ended_at' => now()]);

    PenugasanPembimbing::create([
        'guru_pembimbing_id' => $request->guru_pembimbing_id,
        'pengajuan_id' => $pengajuan->id,
        'assigned_by' => auth()->id(),
    ]);

    // Self-assign: Kaprog jadi pembimbing dirinya sendiri — beri permission kunjungan.*
    // (tetap dimiliki walau penugasan nanti dinonaktifkan; Policy yang membatasi akses)
    if ($request->guru_pembimbing_id === auth()->id()) {
        auth()->user()->givePermissionTo('kunjungan.isi', 'kunjungan.lihat');
    }
}
```

- Self-assign: form assignment nampilin nama Kaprog sendiri juga sebagai opsi (dia juga punya `user_id`, cuma jabatannya kaprog) — bukan endpoint terpisah, cukup opsi normal di dropdown yang sama. Deteksi self-assign cukup lewat `assigned_by === guru_pembimbing_id`, tanpa kolom flag terpisah.
- Section **"Bimbingan Saya"** di dashboard Kaprog cuma query `penugasan_pembimbing` where `guru_pembimbing_id = auth()->id()` — reuse komponen kunjungan & (nanti M6) formulir penilaian yang sama persis dipakai role Guru Pembimbing, jangan duplikasi component.

## 3. Surat Tugas Monitoring (BR22, BR23)

```php
// app/Http/Controllers/Hubin/SuratTugasController.php
public function store(Request $request)
{
    $data = $request->validate([
        'periode_pkl_id' => 'required|exists:periode_pkl,id',
        'lingkup' => 'required|in:global,scoped',
        'kompetensi_keahlian_id' => 'required_if:lingkup,scoped|nullable|exists:kompetensi_keahlian,id',
        'requested_by' => 'nullable|exists:users,id',
        'berlaku_hingga' => 'nullable|date|after_or_equal:today',
    ]);

    $suratTugas = SuratTugasMonitoring::create([
        ...$data,
        'diterbitkan_by' => auth()->id(),
        'requested_by' => $request->input('requested_by'), // null kalau inisiatif Hubin sendiri
        'nomor_surat' => PenomoranSurat::generate('surat_tugas', $data['periode_pkl_id']), // BR18, helper sama dari M4
        'tanggal_terbit' => now()->toDateString(),
        'status' => 'aktif',
        'berlaku_hingga' => $request->input('berlaku_hingga'), // null = berlaku penuh sepanjang periode
    ]);

    $suratTugas->generatePdf(); // render Blade → PDF → simpan path ke kolom `file` (pola M4)

    // Notif "Surat Tugas terbit" (TRD §8): ke Guru Pembimbing terkait;
    // khusus Scoped juga ke Kaprog peminta (requested_by)
    notifyPembimbingSuratTugas($suratTugas);
}
```

Request scoped dari Kaprog: halaman/tombol kecil di dashboard Kaprog yang cuma bikin notifikasi ke Hubin (bukan langsung create record) — keputusan terbit tetap di tangan Hubin. Notifikasi wajib memuat jurusan & periode yang diminta supaya Hubin tinggal isi form terbitkan tanpa bolak-balik. Tidak ada tabel penyimpan request pending — data request hidup di notifikasi DB.

## 4. Laporan Kunjungan — gated oleh Surat Tugas (BR22)

```php
// app/Http/Controllers/GuruPembimbing/KunjunganController.php
public function store(Request $request, PenugasanPembimbing $penugasan)
{
    // KunjunganPolicy@create: ownership + gate Surat Tugas aktif (BR22).
    // Gate surat tugas WAJIB di sini (deny 403), bukan cuma disable tombol di UI.
    $this->authorize('create', [KunjunganMonitoring::class, $penugasan]);

    // Pasti ada setelah authorize — query scope sama persis dengan yang dipakai Policy
    $suratTugas = SuratTugasMonitoring::query()
        ->where('periode_pkl_id', $penugasan->pengajuan->periode_pkl_id)
        ->where('status', 'aktif')
        ->where(function ($q) use ($penugasan) {
            $q->where('lingkup', 'global')
              ->orWhere('kompetensi_keahlian_id', $penugasan->pengajuan->siswa->kompetensi_keahlian_id);
        })
        ->where(function ($q) use ($request) {
            $q->whereNull('berlaku_hingga')->orWhereDate('berlaku_hingga', '>=', $request->tanggal_kunjungan);
        })
        ->orderByDesc('tanggal_terbit') // kalau >1 surat cocok, pakai terbit terbaru
        ->first();

    $kunjungan = KunjunganMonitoring::create([
        'penugasan_id' => $penugasan->id,
        'surat_tugas_id' => $suratTugas->id,
        'tanggal_kunjungan' => $request->tanggal_kunjungan,
        'catatan' => $request->catatan,
        'dokumentasi_path' => $request->hasFile('dokumentasi')
            ? collect($request->file('dokumentasi'))
                ->map(fn ($file) => $file->store('kunjungan/'.$penugasan->pengajuan_id))
                ->toJson()
            : null,
    ]);

    // Notif "Laporan Kunjungan baru" (TRD §8): ke Siswa & Hubin
    notifyKunjunganBaru($kunjungan);
}
```

## Checkpoint akhir M5

- Kaprog cuma bisa assign Guru Pembimbing buat siswa jurusannya sendiri (coba lintas jurusan, harus ketolak Policy).
- Self-assign Kaprog jadi pembimbing muncul di section "Bimbingan Saya" dan berfungsi sama persis kayak Guru Pembimbing biasa (bukan halaman/kode terpisah), tanpa mengubah role Spatie Kaprog itu.
- Penggantian pembimbing (aksi Ubah) menonaktifkan assignment lama (BR30) — riwayat assignment tetap tersimpan, bukan dihapus.
- Hubin bisa terbitkan Surat Tugas Global (semua jurusan langsung ke-cover) dan Scoped (satu jurusan, bisa dikaitkan ke request Kaprog). Nomor surat unik (BR18), PDF tersimpan, status default aktif.
- Guru Pembimbing gak bisa isi Laporan Kunjungan kalau Surat Tugas buat periode/jurusannya belum ada, nonaktif, atau lewat `berlaku_hingga` — coba isi sebelum syarat terpenuhi, harus ditolak backend dengan 403 lewat Policy (bukan cuma UI).
- Setelah surat tugas terbit & aktif, form Laporan Kunjungan bisa diisi berkali-kali sepanjang periode (bukan sekali doang).
- Notif (TRD §8): Surat Tugas terbit → Guru Pembimbing (+Kaprog peminta kalau Scoped); Laporan Kunjungan baru → Siswa & Hubin; Request Scoped dari Kaprog → Hubin.
