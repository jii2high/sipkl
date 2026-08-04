# Panduan M2 — Master Data & User Mgmt Hubin

Cakupan: Perusahaan/Periode/Kompetensi Keahlian/Indikator Penilaian+Section, Kelola Akun+nonaktifkan Kaprog, Import/Export Excel, plus Search & Filter Bar reusable component.

## 1. Migration master data tambahan

```php
// periode_pkl
Schema::create('periode_pkl', function (Blueprint $table) {
    $table->id();
    $table->string('nama');            // mis. "PKL 2026/2027"
    $table->date('tanggal_mulai');
    $table->date('tanggal_selesai');
    $table->boolean('status_aktif')->default(false);
    $table->timestamps();
});

// perusahaan
Schema::create('perusahaan', function (Blueprint $table) {
    $table->id();
    $table->string('nama');
    $table->text('alamat')->nullable();
    $table->string('telp')->nullable();
    $table->unsignedTinyInteger('kuota_saran')->default(6);
    $table->timestamps();
});

// indikator_penilaian
Schema::create('indikator_penilaian', function (Blueprint $table) {
    $table->id();
    $table->enum('kategori', ['umum', 'teknis_kejuruan']);
    $table->foreignId('kompetensi_keahlian_id')->nullable()->constrained('kompetensi_keahlian');
    $table->unsignedTinyInteger('nomor_urut');
    $table->string('deskripsi');
    $table->timestamps();
});

// section_penilaian — child indikator, nullable karena gak semua jurusan punya sub indikator
Schema::create('section_penilaian', function (Blueprint $table) {
    $table->id();
    $table->foreignId('indikator_penilaian_id')->constrained('indikator_penilaian')->cascadeOnDelete();
    $table->unsignedTinyInteger('nomor_urut');
    $table->string('deskripsi');
    $table->timestamps();
});
```

## 2. Controller & routing — namespace `Hubin/`

```
app/Http/Controllers/Hubin/PerusahaanController.php
app/Http/Controllers/Hubin/PeriodePklController.php
app/Http/Controllers/Hubin/KompetensiKeahlianController.php
app/Http/Controllers/Hubin/IndikatorPenilaianController.php
app/Http/Controllers/Hubin/AkunController.php
```

Semua CRUD standar Inertia (`index` render Data Table shadcn, `store`/`update`/`destroy` via `router.post/put/delete` dari React tanpa `<form>` tag), di-guard permission `master-*.kelola` dari M1.

## 3. Kelola Akun + "Nonaktifkan sebagai Kaprog"

Bukan dropdown bebas ganti jabatan — cuma 1 aksi tombol:

```php
// app/Http/Controllers/Hubin/AkunController.php
public function nonaktifkanKaprog(GuruProfile $guruProfile)
{
    $this->authorize('kelola', $guruProfile); // Policy, bukan abort_unless

    $guruProfile->update(['jabatan' => 'guru_pembimbing', 'kompetensi_keahlian_id' => null]);
    // GuruProfileObserver otomatis sync role spatie ke guru_pembimbing

    return back();
}
```

Cek lagi hasil M1: observer harus jalan otomatis di sini tanpa kode tambahan apapun buat sync role — kalau harus manual `syncRoles()` lagi di sini, berarti observer M1 belum bener.

## 4. Import/Export Excel (maatwebsite/excel)

```bash
php artisan make:import GuruImport
php artisan make:import SiswaImport
php artisan make:export AkunExport
```

- Template Guru: nama, NIP, email, jabatan, kompetensi_keahlian (khusus kaprog).
- Template Siswa: nama, NIS, email, kelas, kompetensi_keahlian, alamat, no_telp.
- Validasi per baris: NIS/NIP unik, `kompetensi_keahlian` harus match master data — pakai `WithValidation` + `SkipsOnFailure`, kumpulin error per baris (jangan batalin seluruh import kalau cuma sebagian baris gagal — BR16).
- Password default = NIS/NIP, `must_change_password = true` — konsisten sama CheckFirstLogin M1.
- Export template kosong (`FromArray` header doang) + export data akun existing (`FromCollection`) buat backup/audit.

## 5. Search & Filter Bar — komponen global reusable

Ini pertama kalinya komponen ini dibangun, dipakai lintas role sampai akhir project (BR21). Taruh di `resources/js/components/search-filter-bar.tsx`, generic props (search query, daftar filter dropdown, callback ke Inertia `router.get()` dengan query string) — jangan bikin versi khusus per halaman, biar M3–M6 tinggal reuse.

## Checkpoint akhir M2

- Hubin bisa CRUD penuh: Perusahaan, Periode PKL, Kompetensi Keahlian, Indikator Penilaian + Section Penilaian (kategori Umum vs Teknis Kejuruan kebedain jelas di form).
- Tombol "Nonaktifkan sebagai Kaprog" di tabel akun bikin `jabatan` berubah **dan** role spatie ikut berubah otomatis, tanpa halaman perlu di-refresh manual buat lihat efeknya.
- Import Excel guru & siswa jalan, baris invalid dilaporkan per baris tapi baris valid tetap masuk.
- Export template kosong & export data akun bisa didownload dan format-nya sesuai kolom yang didefinisiin.
- Search & Filter Bar udah dipasang minimal di 1 halaman listing (Kelola Akun) sebagai bukti komponennya reusable, siap dipakai ulang di M3.
