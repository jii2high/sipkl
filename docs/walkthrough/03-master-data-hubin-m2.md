# Walkthrough SI-PKL — 03. Master Data & Kelola Akun Hubin (M2)

Modul ini dikelola **eksklusif oleh Hubin** (`permission:master-data.kelola` /
`master-penilaian.kelola` / `akun.kelola`). Terdiri dari:

1. CRUD Perusahaan mitra
2. CRUD Periode PKL
3. CRUD Kompetensi Keahlian (jurusan)
4. CRUD Indikator Penilaian + Section (anak)
5. Kelola Akun Kaprog + Import/Export Excel

---

## 3.1 Pola CRUD Standar

Semua controller master data mengikuti pola yang sama. Ambil contoh
**`app/Http/Controllers/Hubin/PerusahaanController.php`**:

```php
public function index(): Response
{
    return Inertia::render('hubin/perusahaan/index', [
        'perusahaan' => Perusahaan::query()
            ->when(request('search'), fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
            ->paginate(10)
            ->withQueryString(),
    ]);
}

public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([...]);   // validasi inline
    Perusahaan::create($validated);
    return redirect()->route('hubin.perusahaan.index');
}
```

Pola yang konsisten:

| Method | Tugas | Return |
|---|---|---|
| `index()` | List + search + `paginate(10)->withQueryString()` | `Inertia::render(...)` |
| `store(Request)` | Validasi inline → `Model::create()` | `redirect()->route(...)` |
| `update(Request, Model)` | Validasi → `$model->update()` | redirect |
| `destroy(Model)` | `$model->delete()` | redirect |

Variasi antar controller hanya pada kolom & aturan validasi:

| Controller | Model | Validasi kunci |
|---|---|---|
| `PerusahaanController` | `Perusahaan` | `nama` required max 150, `kuota_saran` integer 1–255 |
| `PeriodePklController` | `PeriodePkl` | nama + `tanggal_mulai`/`tanggal_selesai` (date) + `status_aktif` |
| `KompetensiKeahlianController` | `KompetensiKeahlian` | `nama` + `acronym` |
| `IndikatorPenilaianController` | `IndikatorPenilaian` | `kategori` (umum/teknis_kejuruan) + `deskripsi` + `nomor_urut` |

> **Catatan gaya kode:** validasi memakai `$request->validate()` inline, bukan
> Form Request — kecuali di `Settings\` yang sudah pakai Form Request
> (`ProfileUpdateRequest`, `PasswordUpdateRequest`, dst.).

### Indikator + Section (relasi anak)

`IndikatorPenilaianController` punya 3 method tambahan untuk anak-nya
(`SectionPenilaian`, tabel `section_penilaians`):

```php
Route::post('indikator-penilaian/{indikatorPenilaian}/section', ...@storeSection);
Route::put('indikator-penilaian/section/{section}',        ...@updateSection);
Route::delete('indikator-penilaian/section/{section}',     ...@destroySection);
```

Relasi: `IndikatorPenilaian hasMany SectionPenilaian` (hapus indikator →
section ikut terhapus via `cascadeOnDelete` di migration).

---

## 3.2 Kelola Akun — `app/Http/Controllers/Hubin/AkunController.php`

### Index — daftar Kaprog

```php
$guru = GuruProfile::with('user', 'kompetensiKeahlian')
    ->where('jabatan', 'kaprog')
    ->when(request('search'), fn ($q, $v) => $q->whereHas('user', ...))
    ->paginate(10);
```

Hanya menampilkan guru dengan `jabatan = 'kaprog'`.

### BR31 — Nonaktifkan Kaprog

```php
public function nonaktifkanKaprog(GuruProfile $guruProfile): RedirectResponse
{
    $guruProfile->update([
        'jabatan' => 'guru_pembimbing',   // di-demote
        'kompetensi_keahlian_id' => null, // lepas jurusan
    ]);

    return back();
}
```

- Route: `PATCH /hubin/kelola-akun/{guruProfile}/nonaktifkan`.
- **Peran observer**: setelah `update()`, `GuruProfileObserver::saved()` terpanggil
  dan menjalankan `$guruProfile->user->syncRoles([$guruProfile->jabatan])` →
  role otomatis jadi `guru_pembimbing`. Inilah cara BR31 "auto-change role"
  bekerja (app/Observers/GuruProfileObserver.php:9).

---

## 3.3 Role Derivation — `GuruProfileObserver`

```php
public function saved(GuruProfile $guruProfile): void
{
    $guruProfile->user->syncRoles([$guruProfile->jabatan]);
}
```

- `saved` dipicu saat **create DAN update**.
- Nilai `jabatan` (kaprog / guru_pembimbing / hubin) langsung menjadi nama role.
- Implikasi: **role guru tidak pernah di-set manual lewat UI** — selalu ikut
  kolom `guru_profiles.jabatan`. (BR31 memanfaatkan ini.)

---

## 3.4 Import & Export Excel (Maatwebsite)

Route terkait (semua `permission:akun.kelola`, kecuali template):

```
POST  /hubin/kelola-akun/import-guru       importGuru
POST  /hubin/kelola-akun/import-siswa      importSiswa
GET   /hubin/kelola-akun/export            export          (akun.xlsx)
GET   /hubin/kelola-akun/template-guru     templateGuru    (tanpa permission khusus)
GET   /hubin/kelola-akun/template-siswa    templateSiswa
```

### Import — `app/Imports/GuruImport.php` & `SiswaImport.php`

Kedua class memakai 4 interface Maatwebsite:

| Interface | Fungsi |
|---|---|
| `ToCollection` | Proses seluruh baris sekaligus di `collection()` |
| `WithHeadingRow` | Baris pertama dianggap header (kolom diakses via nama, mis. `$row['nis']`) |
| `WithValidation` | Validasi per baris via method `rules()` |
| `SkipsOnFailure` | Baris gagal validasi → masuk `$this->errors[]`, tidak menghentikan import |

Alur `GuruImport`:

```php
public function collection(Collection $rows): void
{
    foreach ($rows as $row) {
        $user = User::create([
            'name' => $row['nama'],
            'email' => $row['email'],
            'password' => Hash::make($row['nip']),   // password awal = NIP
            'must_change_password' => true,          // wajib ganti password
        ]);

        // cari jurusan by nama; jika tidak ada → catat error, jurusanId = null
        GuruProfile::create([... 'jabatan' => $row['jabatan'], ...]);
    }
}
```

Perbedaan `SiswaImport`: password awal = NIS, plus `$user->assignRole('siswa')`
eksplisit (role guru datang otomatis dari observer).

Controller mengumpulkan error:

```php
$import = new GuruImport;
Excel::import($import, $request->file('file'));

if ($import->errors) {
    return back()->with('importErrors', $import->errors);  // ditampilkan di halaman
}
```

### Export — `app/Exports/AkunExport.php`

Semua user (siswa + guru) dengan kolom: nama, NIS/NIP, email, role, kelas/jabatan,
jurusan. Didownload sebagai `akun.xlsx`.

### Template — `TemplateGuruExport.php` / `TemplateSiswaExport.php`

File Excel berisi satu baris contoh sebagai panduan kolom import.

---

## 3.5 Frontend Halaman Master Data

Contoh pola frontend: `resources/js/pages/hubin/perusahaan/index.tsx`

```
[Tabel: header + rows dari perusahaan.data]
   ├─ aksi per baris: Edit / Hapus
   └─ Dialog (shadcn) untuk Tambah & Edit — state `open` + `edit`
```

Poin penting:

- **Satu dialog** untuk create & edit — dibedakan dengan state `edit` (null = create,
  objek = edit).
- Submit memakai `router.post` / `router.put` **dengan URL string hardcoded**
  (`/hubin/perusahaan/${edit.id}`) — ini tercatat sebagai tech debt, belum migrasi
  penuh ke Wayfinder.
- Hapus memakai `confirm(...)` — ini **melanggar DESIGN.md** yang mewajibkan
  AlertDialog untuk konfirmasi kritis (lihat `10-status-dan-tech-debt`).
- Props ber-tipe: `PerusahaanItem` interface + `Props` (data + `meta` pagination).
- Breadcrumb via properti statis `Index.layout = { breadcrumbs: [...] }`.

---

## 3.6 Ringkasan Alur Import Guru

```
Hubin upload guru.xlsx
   → POST /hubin/kelola-akun/import-guru (permission:akun.kelola)
   → AkunController@importGuru → Excel::import(GuruImport)
       → setiap baris: validasi (WithValidation) → User::create + GuruProfile::create
       → GuruProfileObserver::saved → syncRoles(['jabatan'])
       → baris gagal → errors[]
   → redirect back dengan flash 'importErrors' | 'success'
```

## 3.7 File Terkait

| File | Peran |
|---|---|
| `app/Http/Controllers/Hubin/{Perusahaan,PeriodePkl,KompetensiKeahlian,IndikatorPenilaian,Akun}Controller.php` | CRUD + kelola akun |
| `app/Imports/{Guru,Siswa}Import.php` | Import Excel |
| `app/Exports/{Akun,TemplateGuru,TemplateSiswa}Export.php` | Export & template |
| `app/Observers/GuruProfileObserver.php` | Role derivation otomatis |
| `resources/js/pages/hubin/{perusahaan,periode-pkl,kompetensi-keahlian,indikator-penilaian,kelola-akun}/index.tsx` | Halaman frontend |
