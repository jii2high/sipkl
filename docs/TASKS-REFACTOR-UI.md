# TASKS — Refactor UI/UX SIPKL: Panel Filament-first + Portal Mobile

**Turunan dari**: `PRD.md` v3, `TRD.md`, `DESIGN.md`, `docs/TASKS.md`.
**Sasaran**: permukaan UI memakai fitur bawaan Filament; portal mobile satu layout.
**Tidak disentuh**: `app/Actions`, `app/Policies`, `app/Services`, `database/migrations`, `docs/schema.sql`, dan seluruh logika bisnis.
**Jumlah task**: 29 (R0–R6).

## 0. Cara Memakai

### Aturan keras

1. `app/Actions`, `app/Policies`, `app/Services`, migrasi, dan `docs/schema.sql` **tidak boleh diubah**. Task yang tampak membutuhkannya ditulis di §5 (pertanyaan terbuka), bukan direncanakan.
2. Action Filament hanya membungkus Action domain (`app(...)` / `app(SomeAction::class)->handle(...)`). Action domain **tidak** dipindah ke UI.
3. Hindari bentrok nama `Filament\Actions\Action` dengan Action domain. Gaya yang dipakai docs Filament 5: `use Filament\Actions;` lalu `Actions\Action::make(...)`. Bukti bentrok sekarang: `app/Filament/App/Pages/AntreanPengajuan.php:5-11` (Action domain) dan `:19` (`Filament\Actions\Action`).
4. 271 tes Pest harus hijau di akhir tiap task, di MySQL (`php -d memory_limit=1G vendor/bin/pest`).
5. Perubahan URL (`/app/...` hilang) wajib memperbarui tautan, redirect, job, dan tes dalam task yang sama.
6. `docs/TASKS.md` tidak ditimpa. Dokumen ini berdiri sendiri.
7. Teks UI Bahasa Indonesia. Semua teks baru masuk `resources/lang/id/`.

### Singkatan

| Singkatan | Arti |
|---|---|
| **DoD** | Definition of Done `TRD.md` §21 (kecuali butir skema, yang dibekukan di refactor ini) |
| **CV** | Checkpoint visual `TRD.md` §17.2: 360px dan 1280px, terang dan gelap, chip bertulisan, target sentuh ≥ 44px, input ≥ 16px di ponsel |
| **Ukuran** | S / M / L (relatif satu sesi agent) |
| **Native** | Komponen bawaan Filament yang sudah diverifikasi di docs v5 (nama API di §3) |

### Prinsip

- Di tiga panel, Filament = dependency terpasang. Urutan pilihan: Resource → Page + table/schema → Widget → Action/BulkAction → komponen bawaan. Blade kustom di `resources/views/filament/*` hanya dengan alasan satu baris.
- Komponen kustom yang menggandakan fitur bawaan Filament = utang; dihapus, bukan dipakai ulang di panel.
- Portal mobile tidak memakai Filament. Satu layout bersama, 360px, target sentuh ≥ 44px, token dari `DESIGN.md`.
- Warna/label/ikon status: satu sumber. `app/Support/StatusChip.php` sudah menjadi sumbernya (`app/Enums/PengajuanStatus.php:32`, `BimbinganStatus.php:24`, `DocumentService.php:356`); enum mengeksposnya lewat kontrak Filament. Satu sumber, dua konsumen (badge panel, `status-badge` portal).
- Tidak menambah paket. Task yang tidak perlu dicoret dan dicatat di §7.

## 1. Ringkasan Keputusan (final, jangan dibuka ulang)

| Permukaan | Teknologi | Rute | Peran |
|---|---|---|---|
| Panel Filament | Filament 5 | `/hubin`, `/kaprog`, `/guru` | Hubin, Kaprog, Guru Pembimbing |
| Portal mobile | Blade + Livewire biasa (tanpa Filament) | `/siswa`, `/walas`, `/kepsek` | Siswa, Walas, Kepsek |
| Eksternal & publik | Blade + Livewire (tetap) | `/eksternal/*`, `/verify/{token}`, `/faq`, `/` | Tanpa akun |

- Satu `/login` untuk semua peran (OTP first-login kustom tetap). Panel mengarahkan pengguna yang belum login ke `/login`; setelah login diarahkan sesuai `active_role`.
- Panel `app` dibubarkan: Kaprog → `kaprog`, Guru → `guru`, Siswa/Walas/Kepsek → portal mobile.
- Pindah peran: panel lewat `userMenuItems()`, portal lewat menu header.
- Kondisi sekarang (baseline): panel `app` (`AppPanelProvider`) + `hubin` (`HubinPanelProvider`); 15 Page di `app/Filament/App/Pages` dan 6 Page di `app/Filament/Hubin/Pages` semuanya memakai view Blade kustom (`protected string $view`); `app/Filament/App/Resources` dan `App/Widgets` kosong; Resource Hubin (6) sudah native table+schema; `app/Filament/Support` (§14.3 TRD) tidak pernah ada.

## 2. Temuan Verifikasi Dokumen

Semua diverifikasi terhadap berkas di repo, bukan dari ingatan.

| # | Temuan | Bukti aktual | Yang benar | Task |
|---|---|---|---|---|
| 1 | Status milestone berbeda di tiga tempat | `AGENTS.md` = "M0–M6"; `README.md:7` = "M1–M3 selesai"; `docs/TASKS.md` menandai `[x]`/`[✓]` sampai T10.2 dan T10.3; `docs/GO-LIVE.md` ada | **M0–M10 selesai** (klaim pemilik benar). `AGENTS.md` dan `README.md` stale | R0.2 |
| 2 | Jumlah tes | `AGENTS.md` = 205 (era M6, `docs/TASKS.md:406`); `docs/TASKS.md:441` = 257 (T10.1) + T10.3 push | Angka resmi = hasil run suite; 49 berkas tes | R0.1 |
| 3 | Jumlah migrasi | `AGENTS.md` = 13; aktual 18 berkas di `database/migrations` | 18 | R0.2 |
| 4 | Nama folder referensi | Aktual `docs/old physical dokument examples` (ada `k`); `TRD.md:78` dan `PRD.md:558` menulis `old physical document examples` | `old physical dokument examples` | R1.4 |
| 5 | Nama berkas skema | Aktual `docs/schema.sql`; `TRD.md:3,83,251,291` menulis `scheme.sql`; "document" disebut di TRD | `docs/schema.sql` | R1.4 |
| 6 | Versi PRD | `TRD.md:3` = "PRD.md v2"; `PRD.md:3` = "Versi: 3.0" | PRD v3 | R1.2 |
| 7 | ID `Q-K` dan `Q-I` dipakai ganda | PRD Lampiran B: Q-K = definisi 5 kolom kanban, Q-I = status kelulusan. `docs/schema.sql:82` = `-- Q-K: preferensi tema`; `docs/schema.sql:671,804` = `-- Q-I: revisi nilai sidang` | ID di `schema.sql` salah label | R1.4 |
| 8 | Struktur form | `TRD.md:408-432` = JSON dua tingkat; aktual tabel `form_sections`/`form_items` (`docs/schema.sql:598,607`), sudah dicatat `docs/TASKS.md:420` | Relasional | R1.2 |
| 9 | Nama tabel | `TRD.md:259,261,228` = `plotting_guru`, `laporan_signatures`; aktual `guru_pembimbing_plotting` (`docs/schema.sql:309`), `document_signatures` (`docs/schema.sql:463`) | Nama aktual | R1.2 |
| 10 | Komponen bersama | `TRD.md:473` menjanjikan `app/Filament/Support/*`; aktual `app/Support/StatusChip.php` + `resources/views/components/*` | Tidak pernah dibangun; §14.3 diganti | R1.2 |
| 11 | Letak Design.md | Aktual `DESIGN.md` di root; `TRD.md:82` = `docs/Design.md`; `docs/TASKS.md:384` mencatat "docs/Design.md tidak ada" | `DESIGN.md` root | R1.4 |
| 12 | Rute panel | `TRD.md:28,54` + `PRD.md:169` = panel `app`/`hubin`; keputusan baru = 3 panel + portal `/siswa`,`/walas`,`/kepsek` | Keputusan §1 | R1.1, R1.2 |
| 13 | `resources/lang/id` | Aktual `resources/lang/id/pkl.php` | Cocok dengan TRD | – |

## 3. Inventaris Halaman dan Komponen

### 3.1 Komponen Filament v5 yang terverifikasi (docs v5.1.1)

Jangan pakai nama lain sebelum diverifikasi ulang.

| Kebutuhan | API terverifikasi |
|---|---|
| Warna panel dari token `DESIGN.md` | `->colors(['primary' => '#1E40AF', ...])` (hex diterima, palet dihasilkan otomatis); `->darkMode(true)` |
| Pindah peran di panel | `->userMenuItems([Action::make('x')->url(fn () => ...)->icon('heroicon-o-...')])`; `->postToUrl()` bila perlu POST |
| Akses panel | `FilamentUser::canAccessPanel(Panel): bool` |
| Middleware panel | `->authMiddleware([...], isPersistent: true)` (agar jalan juga di request Livewire); `->middleware([...], isPersistent: true)` |
| Badge status dari enum | `enum ... implements Filament\Support\Contracts\HasLabel, HasColor, HasIcon` (`getLabel()`, `getColor()`, `getIcon()`) + `TextColumn->badge()` |
| Halaman kustom | `static ?string $navigationLabel`, `static getNavigationLabel()`, `$navigationGroup` (string/UnitEnum), `getHeaderActions()` |
| Aksi tabel | `Filament\Actions\Action`, `BulkAction`, `ActionGroup::make([...])`, `->recordActions([...])`, `->headerActions([...])`, `requiresConfirmation()`, `->schema([...])`, `modalHeading/Description/SubmitActionLabel` |
| Form dinamis | `Repeater`/`Builder` + `->schema()`, `->relationship()`, `->reorderableWithButtons()`, `->orderColumn('sort_order')` |
| Widget | `->widgets([...])` di panel; `getHeaderWidgets()`/`getFooterWidgets()` di Page; `StatsOverviewWidget`, `TableWidget`, `ChartWidget` |
| Notifikasi | `->databaseNotifications()`, `DatabaseNotificationsPosition::Sidebar`, `Notification::make()->sendToDatabase($recipient)` |
| Impor/ekspor | `ImportAction::make()->importer(X::class)`, `ExportAction::make()->exporter(X::class)`, `ExportFormat`, `ExportColumn` |

### 3.2 Panel `hubin` (tetap di `/hubin`)

| Halaman | Sekarang | Native pengganti | Dihapus | Risiko |
|---|---|---|---|---|
| AntreanSuratPengantar | Page + `table()` + view Blade | Table + `Actions\Action` (`IssueSuratPengantar`) | `filament/hubin/pages/antrean-surat-pengantar.blade.php` | Hold surat tanpa TTD Kepsek tetap di Action domain |
| KonfirmasiDokumenFisik | Page + `table()` + view | Table + `Actions\Action` (`ConfirmPhysicalDocument`) | `.../konfirmasi-dokumen-fisik.blade.php` | Hanya mengubah `is_provisional` |
| KelolaVisitasi | Page + view | Table + `ActionGroup` (approve / reject+alasan / terbit surat) | `.../kelola-visitasi.blade.php` | Alasan reject ≥ 10 karakter |
| PenilaianIndustri | Page + view | Table + `Actions\Action` (revoke / reissue beralasan) | `.../penilaian-industri.blade.php` | Override Hubin wajib alasan |
| EksporAkreditasi | Page + `table()` + `getHeaderActions()` + view | Table + `Actions\Action` (`StartAccreditationExport`) | `.../ekspor-akreditasi.blade.php` | Jangan ganti ke `ExportAction` (bukan kebutuhan) |
| Monitoring | Page + view bersama | `TableWidget`/`StatsOverviewWidget` dari `MonitoringService` | `filament/monitoring.blade.php` | View dipakai Hubin **dan** Kaprog |
| Resources (Users, Companies, AcademicPeriods, Faqs, FaqCategories, SignatureAssets) | Sudah native `form()` + `table()` | Dipertahankan | – | `HasImportAction` custom tetap; `ImportAction` bawaan = opsional, lihat §7 |

### 3.3 Panel `kaprog`

| Halaman asal (`app/Filament/App/Pages`) | Native pengganti | Catatan |
|---|---|---|
| `AntreanPengajuan` (bagian Kaprog) | Table + `Actions\Action` + `BulkAction` (sudah `HasTable`; tinggal buang view + `ViewColumn` kuota → `TextColumn->badge()` + `Actions\Action` kolom) | Bagian Walas pindah ke `/walas` (R4.4) |
| `FormBuilder` | Page + `Repeater` (`->relationship()`, `->reorderableWithButtons()`, `->orderColumn('sort_order')`) + `Actions\Action` publish | Guard "terkunci setelah submission" tetap di `PublishForm` |
| `JadwalSidangPage` | Table + `ActionGroup` (jadwalkan / pindah / batalkan) | Cascade shift tetap di `CascadeShift` |
| `PenilaianSidang` | Table + `Actions\Action` (approve unlock / revoke beralasan) | – |
| `Monitoring` | Widget sama dengan Hubin | Satu kelas widget dipakai dua panel |
| `PlottingGuru` | Table + `Actions\Action` (`Select` guru, `AssignGuru`/`ReplaceGuru`) | Soft-warning multi-wilayah tetap |
| `PengesahanLaporan` (bagian Kaprog) | Table + `BulkAction` (bulk TTD 2) + `Actions\Action` reject (`schema` catatan ≥ 10) | Bagian Kepsek pindah ke `/kepsek` (R4.3) |

### 3.4 Panel `guru`

| Halaman asal | Native pengganti | Catatan |
|---|---|---|
| `BimbinganSiswa` | Table + `Actions\Action` feedback + `FileUpload` | Guard ≥ 4 interaksi tetap di `MarkBagianAkhir` |
| `PenilaianIndustri` | Table + `Actions\Action` generate/reissue link | Window H-1 tetap di server |
| `Visitasi` (bagian Guru) | Table + `Actions\Action` upload dokumentasi (`FileUpload`) | Bagian Kaprog tetap di panel kaprog → halaman ini **dipecah dua** |

### 3.5 Portal mobile (Blade + Livewire, tanpa Filament)

| Halaman asal | Rute baru | Komponen |
|---|---|---|
| `PengajuanSaya` | `/siswa` | Livewire baru; `CompanyAutocomplete` Livewire dipertahankan (`app/Livewire/Support/CompanyAutocomplete.php`) |
| `BimbinganSaya` | `/siswa/bimbingan` | Livewire `WithFileUploads` |
| `JadwalSidangSaya` | `/siswa/jadwal-sidang` | Livewire |
| `PengesahanLaporan` (bagian Kepsek) | `/kepsek` | Livewire; bulk TTD 5, target sentuh 44px |
| `AntreanPengajuan` (bagian Walas) | `/walas` | Livewire Gate 2 |
| `KanbanKelas` | `/walas/kanban` | Livewire; **tetap kustom** (kanban tidak native) |
| `PengaturanNotifikasi` | `/notifikasi` | Livewire; lihat §5 no. 3 |
| – | layout bersama | Livewire Component class referensi: `app/Livewire/Support/RoleSwitcher.php` dipakai ulang di menu header portal |

### 3.6 Komponen bersama: dipertahankan, dihapus, dikecilkan

| Berkas | Keputusan |
|---|---|
| `app/Support/StatusChip.php` | **Dipertahankan** sebagai sumber tunggal; ditambah peta ikon untuk `HasIcon` |
| `app/Enums/{PengajuanStatus,PengajuanPklStatus,BimbinganStatus,SignatureStatus}.php` | Tambah implementasi `HasLabel`/`HasColor`/`HasIcon` yang mendelegasikan ke `StatusChip` |
| `resources/views/components/{status-badge,quota-bar,signature-stepper,pin-input,otp-input,masked-contact,theme-switcher,theme-script}.blade.php` | Dipertahankan untuk portal/eksternal/publik |
| `resources/views/components/layouts/{public,eksternal,auth}.blade.php` | Dipertahankan; ditambah `layouts/portal.blade.php` |
| `resources/views/filament/panel-assets.blade.php` | **Dikecilkan** (font + aturan sentuh saja); warna dari `->colors()` |
| `resources/views/filament/theme-switcher.blade.php` | Dipertahankan (menyimpan preferensi ke `users.theme_preference`), atau pindah ke `userMenuItems` — lihat §5 no. 2 |
| `resources/views/filament/role-switcher.blade.php` | **Dihapus** (diganti `userMenuItems()`) |
| `app/Livewire/Support/RoleSwitcher.php` + `resources/views/livewire/support/role-switcher.blade.php` | Dipertahankan untuk header portal |
| `resources/views/filament/app/pages/*` (14 berkas) | **Dihapus** setelah R3/R4 |
| `resources/views/filament/hubin/pages/*` (5 berkas) + `resources/views/filament/monitoring.blade.php` | **Dihapus** setelah R3.1 |
| `app/Filament/App/**` | **Dihapus** di R5 |
| `app/Providers/Filament/AppPanelProvider.php` | **Diganti** `KaprogPanelProvider` + `GuruPanelProvider`; daftar di `bootstrap/providers.php:5` diperbarui |

### 3.7 Halaman yang wajar tetap kustom

| Halaman | Alasan satu baris |
|---|---|
| Kanban Walas | Papan 5 kolom tidak punya padanan native di Filament. |
| Portal Kepsek (bulk TTD) | Bukan Filament; target sentuh 44px lebih mudah dipastikan dengan Blade. |
| `/eksternal/*`, `/verify/{token}`, `/faq`, `/` , `/login*` | Tanpa akun, di luar panel. |
| `resources/views/pdf/*` | Dokumen cetak, bukan UI. |
| Stepper 5-TTD (portal) | `signature-stepper` = komponen khas (DESIGN.md), tidak ada padanan native. |

## 4. Rencana Bertahap

### R0 — Baseline

#### R0.1 — Baseline cabang, tag, dan tes hijau · Ukuran S
- **Tujuan**: titik pulih sebelum refactor.
- **Dep**: –
- **Langkah**: `git switch jii2high`; `git tag pre-ui-refactor`; catat versi paket sesungguhnya (`composer show filament/filament laravel/framework`, `node -v`); jalankan `npm run build` lalu `php -d memory_limit=1G vendor/bin/pest` dan simpan jumlah tes.
- **AC**: tag ada; keluaran Pest tercatat (jumlah tes + assertion) di `docs/TASKS-REFACTOR-UI.md` §8; versi Filament terpasang dicatat.
- **Tes**: tidak ada tes baru.
- **Risiko**: suite butuh MySQL `sistem_pkl_test` dan aset Vite (`docs/TASKS.md:395`).

#### R0.2 — Perbaiki tabel status milestone · Ukuran S
- **Tujuan**: `AGENTS.md` dan `README.md` tidak lagi menyesatkan.
- **Dep**: R0.1 (angka tes).
- **File**: `AGENTS.md` (baris "M0–M6", "13 migrasi", "205 tes"), `README.md` (baris "M1–M3 selesai").
- **AC**: `AGENTS.md` menyebut M0–M10, 18 migrasi, jumlah tes hasil R0.1; `README.md` menyebut M0–M10.
- **Catatan**: tabel alamat di `README.md` (baris `/app`) baru berubah setelah rute panel berubah, yaitu di R5.3. Di R0.2 `/app` masih benar.
- **Risiko**: rendah.

### R1 — Dokumen

#### R1.1 — PRD: permukaan dan rute baru · Ukuran M
- **Tujuan**: PRD §1.3, §1.6, dan Bagian 2 memakai tiga panel + portal mobile.
- **Dep**: R0.
- **File**: `docs/PRD.md` (§1.3 tabel hak akses, "Rute:" di awal Bagian 2 `:169`, NFR Responsif §3).
- **AC**: tidak ada lagi penyebutan panel `app` di PRD; tabel §1.3 memetakan peran → permukaan.
- **Risiko**: PRD = sumber otoritas perilaku. Perubahan hanya soal permukaan UI, bukan aturan bisnis (BR/KP tidak berubah).

#### R1.2 — TRD: arsitektur tiga panel + portal · Ukuran M
- **Tujuan**: TRD selaras dengan keputusan §1 dan kode aktual.
- **Dep**: R1.1.
- **File**: `docs/TRD.md` §1 (`:28,54`), §2, §3.2 (`:125-135`), §13 (`:408-432` → relasional `form_sections`/`form_items`), §14.2/§14.3 (`:458-473`), §21; perbaiki rujukan `PRD.md v2` (`:3`), `scheme.sql`, `plotting_guru`/`laporan_signatures` (`:228,259,261`), `docs/Design.md` (`:82`).
- **AC**: TRD §1 hanya punya tiga panel Filament + portal Blade; §14.3 menyebut `app/Support/StatusChip.php` + `resources/views/components/*`; tidak ada `scheme.sql`/`plotting_guru`/`laporan_signatures`.
- **Risiko**: TRD juga memuat jadwal milestone; jangan ubah §19 (urutan M0–M10, sudah selesai).

#### R1.3 — AGENTS.md: aturan UI · Ukuran S
- **Tujuan**: aturan Filament-first berlaku untuk agent sesudahnya.
- **Dep**: R1.2.
- **File**: `AGENTS.md` (bagian "Non-obvious rules" + "Stack").
- **AC**: `AGENTS.md` memuat: (a) panel = Filament-first dengan urutan Resource → Page+table/schema → Widget → Action → komponen bawaan; (b) portal mobile tanpa Filament; (c) pengecualian "house component beats native" tidak berlaku untuk UI panel; (d) 3 panel + 3 portal + rute; (e) larangan mengubah Actions/Policies/Services/skema.
- **Risiko**: rendah.

#### R1.4 — Penyelarasan dokumen kecil · Ukuran S
- **Tujuan**: tutup temuan §2 no. 4, 5, 7, 11.
- **Dep**: R1.2.
- **File**: `docs/TRD.md` (nama folder + `schema.sql`), `docs/PRD.md:558`, `docs/schema.sql` komentar (`:82` Q-K → "preferensi tema (bukan Q-K PRD)", `:671,804` Q-I → "revisi nilai sidang"), `docs/SCHEMA-DISCREPANCIES.md` **tidak** dibuat.
- **AC**: `grep -r "scheme.sql\|old physical document examples" docs/` kosong; komentar Q-K/Q-I di `schema.sql` tidak lagi memakai ID PRD.
- **Catatan**: `docs/schema.sql` di sini hanya **komentar** (bukan struktur) — kalau dilarang, tulis di §5 sebagai pertanyaan terbuka.
- **Risiko**: `schema.sql` = dokumen beku. Pratinjau diff ke pemilik sebelum commit.

### R2 — Fondasi

#### R2.1 — Enum status bersama · Ukuran S
- **Tujuan**: satu sumber status dipakai badge panel dan badge portal.
- **Dep**: R1.
- **File**: `app/Enums/{PengajuanStatus,PengajuanPklStatus,BimbinganStatus,SignatureStatus}.php` (tambah `HasLabel`+`HasColor`+`HasIcon`, delegasi ke `StatusChip`), `app/Support/StatusChip.php` (tambah peta ikon + `icon()`), `resources/views/components/status-badge.blade.php` (tetap, membaca `StatusChip`).
- **Native**: `Filament\Support\Contracts\HasLabel`/`HasColor`/`HasIcon` + `TextColumn->badge()`.
- **AC**: kolom status di panel cukup `TextColumn::make('status')->badge()` tanpa closure warna; portal `status-badge` masih menghasilkan chip yang sama; tidak ada peta status kedua.
- **Tes**: satu tes unit bahwa setiap nilai di `StatusChip::CHIPS` punya label, tone, dan ikon; tes bahwa `PengajuanStatus::SedangPkl->getColor()` = `'success'`.
- **Risiko**: status tanpa kelas enum (visitasi, `published`, ekspor `queued/running/completed/failed`) tetap hanya lewat `StatusChip` → §5 no. 1.

#### R2.2 — Tema panel dari DESIGN.md · Ukuran S
- **Tujuan**: warna panel = token `DESIGN.md`, tanpa CSS ganda.
- **Dep**: R2.1.
- **File**: `app/Providers/Filament/*.php` (`->colors([...])` nilai `DESIGN.md`), `resources/views/filament/panel-assets.blade.php` (kecilkan), `resources/views/components/theme-script.blade.php` (kunci seragam).
- **Native**: `->colors(['primary' => '#1E40AF', ...])`, `->darkMode(true)`.
- **AC**: palet panel berasal dari `DESIGN.md`; mode terang/gelap bekerja; kunci penyimpanan tema sama antara panel dan portal.
- **Risiko**: kunci bawaan Filament = `filament:theme:mode` (`resources/views/filament/theme-switcher.blade.php:18`). Portal harus memakai kunci yang sama atau sinkronisasi eksplisit → §5 no. 2.

#### R2.3 — Panel `kaprog` dan `guru`; bubarkan panel `app` · Ukuran M
- **Tujuan**: tiga panel Filament dengan akses per permukaan.
- **Dep**: R2.2.
- **File disentuh**: `app/Providers/Filament/KaprogPanelProvider.php` (baru), `.../GuruPanelProvider.php` (baru), `hubin` (tambah `->authMiddleware([PanelAuthenticate::class, EnsureActiveRole::class], isPersistent: true)`), `app/Models/User.php::canAccessPanel()` (tiga panel), `bootstrap/providers.php`.
- **Dihapus**: `app/Providers/Filament/AppPanelProvider.php` (dipindah isinya).
- **Native**: `->id()`, `->path()`, `->colors()`, `->discoverPages/Resources/Widgets()`, `->authMiddleware([...], isPersistent: true)`.
- **AC**: `/kaprog` hanya Kaprog, `/guru` hanya Guru Pembimbing, `/hubin` hanya Hubin; tamu diarahkan `/login`; peran tidak dimiliki → 403; `EnsureActiveRole` juga berjalan pada request Livewire.
- **Tes**: perbarui `tests/Feature/Auth/ActiveRoleTest.php`; tambah tes 403 lintas panel.
- **Risiko**: `EnsureActiveRole` sekarang dinamis per panel (bukan hanya "peran dimiliki") → kalau perlu pembatasan peran per panel, middleware baru = keputusan baru, bukan logika bisnis. Tandai di §5 no. 6.
- **Catatan**: hapus `app/Filament/App` ditunda ke R5.

#### R2.4 — Redirect sesuai `active_role` · Ukuran S
- **Tujuan**: tujuan pasca-login mengikuti peran aktif.
- **Dep**: R2.3.
- **File**: `app/Models/User.php::panelHomeUrl()` (`:166-172`), `app/Livewire/Auth/Concerns/CompletesLogin.php:33`.
- **AC**: peta `role → url` = `hubin`→`/hubin`, `kaprog`→`/kaprog`, `guru_pembimbing`→`/guru`, `siswa`→`/siswa`, `walas`→`/walas`, `kepsek`→`/kepsek`; tes redirect login (`tests/Feature/Auth/LoginTest.php:47,117`, `ChangePasswordTest.php:48`) hijau.
- **Risiko**: `panelHomeUrl` dipakai juga oleh `RoleSwitcher` (`:36`) → portal butuh URL portal, sudah tercakup peta ini.

#### R2.5 — Pindah peran · Ukuran M
- **Tujuan**: ganti peran dari panel dan dari portal.
- **Dep**: R2.4.
- **File**: panel providers (`->userMenuItems()`), `resources/views/filament/role-switcher.blade.php` (hapus), `app/Livewire/Support/RoleSwitcher.php` (tetap untuk portal), layout portal (baru).
- **Native**: `->userMenuItems([Actions\Action::make('role')->action(...)])`, `->postToUrl()`.
- **AC**: pemilih peran muncul untuk pengguna multi-peran di panel (menu akun) dan portal (header); pindah peran memanggil `SwitchActiveRole` lalu mengarah ke `panelHomeUrl($role)`; tanpa reload penuh; state peran lama bersih (KP-F1-5).
- **Tes**: tes Livewire perpindahan peran dari panel dan portal.
- **Risiko**: `userMenuItems` action berjalan server-side; redirect dari action menu belum divalidasi di v5 terpasang → prototipe kecil dulu, kalau gagal pakai `->url()` + route POST (`->postToUrl()`) dan tulis hasilnya di §8.

#### R2.6 — Rute dan layout portal bersama · Ukuran M
- **Tujuan**: satu kerangka portal mobile.
- **Dep**: R2.5.
- **File baru**: `routes/portal.php` (grup `web` + `auth` + middleware peran portal), `resources/views/components/layouts/portal.blade.php` (header: judul, pemilih peran, lonceng; nav bawah; 360px; target sentuh ≥ 44px), middleware penjaga peran portal.
- **AC**: `/siswa`, `/walas`, `/kepsek` hanya untuk peran masing-masing (403 bila bukan); layout valid di 360px; rute portal **tidak** ikut middleware panel; `/login` tetap satu pintu.
- **Tes**: tes akses 200/403 per peran untuk tiga rute portal.
- **Risiko**: rute portal harus di luar grup `authMiddleware` panel. Verifikasi `bootstrap/app.php` (`withRouting(then: ...)` dipakai untuk eksternal, `docs/TASKS.md:411`).

### R3 — Panel Filament

#### R3.1a — Hubin: antrean surat + dokumen fisik · Ukuran M
- **Dep**: R2.
- **File**: `app/Filament/Hubin/Pages/AntreanSuratPengantar.php`, `KonfirmasiDokumenFisik.php`; dihapus: `resources/views/filament/hubin/pages/{antrean-surat-pengantar,konfirmasi-dokumen-fisik}.blade.php`.
- **Native**: `Table` + `Actions\Action` (`IssueSuratPengantar`, `ConfirmPhysicalDocument`), `ActionGroup`.
- **AC**: aksi dan hasil sama seperti view lama; tidak ada aturan bisnis baru di Page; CV.
- **Tes**: perbarui `tests/Feature/Pengajuan/PengajuanSuratQuotaTest.php:393-405`.
- **Risiko**: pesan "surat ditahan tanpa TTD Kepsek" harus tetap tampil.

#### R3.1b — Hubin: kelola visitasi + penilaian industri · Ukuran M
- **Dep**: R3.1a.
- **File**: `app/Filament/Hubin/Pages/KelolaVisitasi.php`, `PenilaianIndustri.php`; dihapus view keduanya.
- **Native**: `Table` + `ActionGroup` + `Actions\Action` (revoke/reissue, `->schema()` untuk alasan ≥ 10).
- **AC**: reject tanpa alasan ditolak di server; revoke/reissue beralasan; CV.
- **Tes**: `tests/Feature/Plotting/PlottingAndVisitasiTest.php` + tes industri Hubin.
- **Risiko**: halaman KelolaVisitasi sekarang membaca Blade panjang (`kelola-visitasi.blade.php`); pastikan kolom yang ada tidak hilang.

#### R3.1c — Hubin: ekspor + Monitoring widget · Ukuran M
- **Dep**: R3.1b.
- **File**: `app/Filament/Hubin/Pages/EksporAkreditasi.php`, `Monitoring.php`; baru: `app/Filament/Hubin/Widgets/MonitoringWidget.php`; dihapus: `resources/views/filament/{monitoring.blade.php,hubin/pages/ekspor-akreditasi.blade.php}`.
- **Native**: `Table` + `getHeaderActions()` + `Actions\Action` (`StartAccreditationExport`); `->widgets([...])`, `TableWidget`/`StatsOverviewWidget`.
- **AC**: ekspor tetap hanya Hubin; widget menampilkan gate + step 5-TTD dari `MonitoringService`; CV.
- **Tes**: `tests/Feature/Export/AccreditationExportTest.php:206`, `tests/Feature/Monitoring/MonitoringTest.php:165`.
- **Risiko**: view `filament/monitoring.blade.php` dipakai dua panel — hapus hanya setelah Kaprog punya widget (R3.6).

#### R3.2 — Kaprog: antrean Gate 1 · Ukuran M
- **Dep**: R3.1a.
- **File**: `app/Filament/Kaprog/Pages/AntreanPengajuan.php` (pindah dari `App`), hapus `resources/views/filament/app/pages/antrean-pengajuan.blade.php`.
- **Native**: `Table` + `Actions\Action` (approve/reject/accept/withdrawal) + `TextColumn->badge()` (ganti `ViewColumn` `components.quota-bar` → kolom kuota native).
- **AC**: 9 aksi Kaprog tetap terpanggil; kuota tampil sebagai badge berlabel "n dari 6"; CV.
- **Tes**: `tests/Feature/Pengajuan/PengajuanFlowTest.php:28,314-324`.
- **Risiko**: `ViewColumn` kuota (`AntreanPengajuan.php:112`) adalah satu-satunya tempat `quota-bar` dipakai di panel.

#### R3.3 — Kaprog: Form Builder · Ukuran M
- **Dep**: R3.2.
- **File**: `app/Filament/Kaprog/Pages/FormBuilder.php`, hapus `.../form-builder.blade.php`, hapus `resources/views/filament/app/pages/form-builder.blade.php`.
- **Native**: `Repeater` (`->relationship()`, `->reorderableWithButtons()`, `->orderColumn('sort_order')`) + `Actions\Action` publish.
- **AC**: publish tanpa item ditolak; struktur terkunci setelah submission menampilkan penawaran versi baru; KP-F8 tetap lolos.
- **Tes**: `tests/Feature/Form/FormBuilderTest.php:142`.
- **Risiko**: struktur terkunci = validasi di `PublishForm` (Service-area, jangan diubah) — UI hanya menampilkan pesan.

#### R3.4 — Kaprog: jadwal sidang + nilai sidang · Ukuran M
- **Dep**: R3.3.
- **File**: `app/Filament/Kaprog/Pages/{JadwalSidangPage,PenilaianSidang}.php`; hapus view keduanya.
- **Native**: `Table` + `ActionGroup` (jadwalkan/pindah/batalkan) + `Actions\Action` (approve unlock, revoke, `->schema()`).
- **AC**: cascade shift gagal tidak mengubah jadwal + daftar konflik tampil; unlock hanya satu form; KP-F9 tetap lolos.
- **Tes**: `tests/Feature/Sidang/SidangFlowTest.php:308,325`.
- **Risiko**: jadwal sidang = data benar-benar tabular; DESIGN.md mengizinkan tabel di sini.

#### R3.5 — Kaprog: plotting + visitasi · Ukuran M
- **Dep**: R3.4.
- **File**: `app/Filament/Kaprog/Pages/{PlottingGuru,Visitasi}.php`; hapus view keduanya.
- **Native**: `Table` + `Actions\Action` (`Select` guru) + `Actions\Action` pengajuan visitasi.
- **AC**: 1 company+periode = 1 guru; ganti guru = surat baru + audit; soft-warning multi-wilayah tampil; CV.
- **Tes**: `tests/Feature/Plotting/PlottingAndVisitasiTest.php:380,392`.
- **Risiko**: `Visitasi` asal dipakai dua peran → pastikan split Kaprog (panel) dan Guru (R3.7) tidak menyalin aturan bisnis.

#### R3.6 — Kaprog: pengesahan step 2 + Monitoring · Ukuran M
- **Dep**: R3.5, R3.1c.
- **File**: `app/Filament/Kaprog/Pages/{PengesahanLaporan,Monitoring}.php`; hapus `.../pengesahan-laporan.blade.php`; monitoring pakai widget R3.1c.
- **Native**: `Table` + `BulkAction` (bulk TTD 2, hasil per item) + `Actions\Action` reject (`->schema()` catatan ≥ 10) + `getHeaderWidgets()`.
- **AC**: bulk 10 laporan dengan 1 gagal → 9 sukses + 1 alasan; step 2 hanya Kaprog; CV.
- **Tes**: `tests/Feature/Sidang/SidangFlowTest.php` bagian bulk; `tests/Feature/Monitoring/MonitoringTest.php:158`.
- **Risiko**: hasil per item harus dilaporkan UI (DESIGN.md "Bulk action").

#### R3.7 — Guru: bimbingan + penilaian industri + visitasi · Ukuran M
- **Dep**: R3.6.
- **File**: `app/Filament/Guru/Pages/{BimbinganSiswa,PenilaianIndustri,Visitasi}.php`; hapus tiga view.
- **Native**: `Table` + `Actions\Action` (feedback, generate/reissue link, upload dokumentasi) + `FileUpload`.
- **AC**: guru tidak melihat siswa guru lain; generate link hanya di window; unggah dokumentasi hanya setelah surat bernomor; KP-F6/F10 tetap lolos.
- **Tes**: `tests/Feature/Bimbingan/BimbinganFlowTest.php`, `tests/Feature/Industri/IndustriAssessmentTest.php`, `tests/Feature/Plotting/PlottingAndVisitasiTest.php`.
- **Risiko**: tiga halaman dalam satu task → kalau lebih dari satu sesi, pecah jadi R3.7a/b.

### R4 — Portal Mobile

#### R4.1 — Portal siswa: pengajuan · Ukuran M
- **Dep**: R2.6.
- **File baru**: `app/Livewire/Portal/PengajuanSiswa.php` + view.
- **Dihapus**: `app/Filament/App/Pages/PengajuanSaya.php`, `resources/views/filament/app/pages/pengajuan-saya.blade.php`.
- **Komponen**: `CompanyAutocomplete` (dipertahankan), `status-badge`, `masked-contact`.
- **AC**: alur draft → submit → revisi setelah ditolak sama; snapshot tetap immutable (Action domain); CV 360px.
- **Tes**: `tests/Feature/Pengajuan/PengajuanFlowTest.php`, `PengajuanSuratQuotaTest.php:417`.
- **Risiko**: tenggat `PengajuanSaya.php` kini memuat alur panjang di view Blade; pindahkan ke kelas Livewire, bukan menyalin HTML.

#### R4.2 — Portal siswa: bimbingan + jadwal sidang · Ukuran M
- **Dep**: R4.1.
- **File baru**: `app/Livewire/Portal/{BimbinganSiswa,JadwalSidang}.php` + view.
- **Dihapus**: `BimbinganSaya.php`, `JadwalSidangSaya.php`, dua view.
- **AC**: unggah bimbingan (pdf/doc/docx ≤ 10MB, MIME dari isi) berfungsi; progres 3/4 tampil; guard provisional tampil sebagai pesan; CV.
- **Tes**: `tests/Feature/Bimbingan/BimbinganFlowTest.php`, `tests/Feature/Sidang/SidangFlowTest.php`.
- **Risiko**: `FileStorageService` tetap dipakai lewat Action; jangan pakai `Livewire\TemporaryUploadedFile` langsung ke disk privat.

#### R4.3 — Portal Kepsek: TTD 5 · Ukuran M
- **Dep**: R2.6.
- **File baru**: `app/Livewire/Portal/PengesahanKepsek.php` + view.
- **AC**: bulk dan tunggal; tiap laporan independen; target sentuh ≥ 44px; hanya step 5; CV di ponsel.
- **Tes**: tes baru peran Kepsek; `tests/Feature/Uat/RoleSmokeTest.php:33-34`.
- **Risiko**: Kepsek = pengguna ponsel utama; ini checkpoint visual paling penting.

#### R4.4 — Portal Walas: Gate 2 + kanban · Ukuran M
- **Dep**: R2.6.
- **File baru**: `app/Livewire/Portal/AntreanWalas.php`, `KanbanKelas.php` + view (kanban tetap kustom).
- **Dihapus**: `app/Filament/App/Pages/KanbanKelas.php`, `.../kanban-kelas.blade.php`.
- **AC**: Walas hanya kelasnya; tidak ada kontrol ubah; 5 kolom Q-K; horizontal-scroll dengan penanda kolom aktif di 360px; KP-F15-1 tetap lolos.
- **Tes**: `tests/Feature/Monitoring/MonitoringTest.php`, `RoleSmokeTest.php:18-19`.
- **Risiko**: `AntreanPengajuan` Walas dan Kaprog (R3.2) kini dua implementasi UI untuk query yang sama → keduanya wajib memanggil `SignByWalas`/`RejectByWalas`.

#### R4.5 — Halaman preferensi notifikasi · Ukuran S
- **Dep**: R2.6.
- **File baru**: `app/Livewire/Portal/PengaturanNotifikasi.php` + view.
- **Dihapus**: `app/Filament/App/Pages/PengaturanNotifikasi.php`, `.../pengaturan-notifikasi.blade.php`.
- **AC**: opt-in/out tersimpan di `users.push_opt_in`; muncul untuk semua peran berakun; tautan dari menu panel dan portal; push tidak pernah diprompt otomatis.
- **Tes**: `tests/Feature/Notification/PushNotificationTest.php:54`.
- **Risiko**: halaman lintas-peran → §5 no. 3.

### R5 — Bersih-bersih

#### R5.1 — Hapus `app/Filament/App` dan view tergantikan · Ukuran S
- **Dep**: R3, R4.
- **Dihapus**: `app/Filament/App/**`, `resources/views/filament/app/**`, sisa `resources/views/filament/hubin/pages/**`, `resources/views/filament/monitoring.blade.php`, `resources/views/filament/role-switcher.blade.php`.
- **AC**: `grep -rn "App\\\\Filament\\\\App" app tests` kosong; tidak ada view yatim (`grep -rn "filament.app.pages" app`).
- **Tes**: suite penuh hijau.
- **Risiko**: `resources/views/filament/app/pages/pengesahan-laporan.blade.php` pernah bermasalah kompilasi (`docs/TASKS.md:449`); pastikan tidak ada referensi tersisa.

#### R5.2 — Tautan notifikasi, email, dan job · Ukuran S
- **Dep**: R5.1.
- **File**: `app/Jobs/ProvisionalReminderJob.php:57` (`/app/pengajuan-saya` → `/siswa`), `app/Jobs/IndustryAssessmentReminderJob.php:95` (`/app/penilaian-industri` → `/guru`), `app/Services/DocumentService.php:323` (`/hubin` tetap), `tests/Feature/ExampleTest.php:13,17,18`.
- **AC**: `grep -rn "url('/app\|/app/" app tests routes resources` hanya menyisakan tes yang sengaja ditulis.
- **Risiko**: `DocumentService` = area beku. Rujukan `/hubin/signature-assets` di sana **tidak berubah**, jadi tidak ada perubahan Service.

#### R5.3 — Sinkronkan `docs/TASKS.md` dan dokumen · Ukuran S
- **Dep**: R5.2.
- **File**: `docs/TASKS.md` (Catatan Pelaksanaan: baris baru "Refactor UI"), `docs/GO-LIVE.md` (checklist UAT per permukaan baru), `README.md` (tabel "Alamat penting": `/app` → `/kaprog`, `/guru`, `/siswa`, `/walas`, `/kepsek`).
- **AC**: `docs/TASKS.md` menunjuk `docs/TASKS-REFACTOR-UI.md`; GO-LIVE memuat rute baru.
- **Risiko**: rendah.

### R6 — Verifikasi

#### R6.1 — Tes akses per permukaan · Ukuran M
- **Dep**: R5.
- **File**: `tests/Feature/Uat/RoleSmokeTest.php` (tulis ulang tabel URL: `/kaprog`, `/guru`, `/siswa`, `/walas`, `/kepsek`), tes 403 lintas permukaan.
- **AC**: tiap peran membuka permukaannya (200) dan ditolak di permukaan lain (403); tamu → `/login`.
- **Risiko**: ini gerbang utama; kerjakan sebelum menyatakan refactor selesai.

#### R6.2 — Tes Livewire/Filament halaman yang dipindah · Ukuran M
- **Dep**: R6.1.
- **File**: `filament()->setCurrentPanel(Filament::getPanel('kaprog'|'guru'))` di `tests/Feature/{Pengajuan/PengajuanFlowTest,Sidang/SidangFlowTest,Monitoring/MonitoringTest,Plotting/PlottingAndVisitasiTest,Form/FormBuilderTest,Notification/PushNotificationTest}.php`; tambah tes Livewire portal.
- **AC**: semua tes lama tetap menguji hal yang sama setelah panel diganti; tes portal mencakup submit pengajuan, unggah bimbingan, bulk TTD Kepsek.
- **Risiko**: jangan mengubah asersi perilaku; hanya panel/URL.

#### R6.3 — Checkpoint visual + UAT · Ukuran M
- **Dep**: R6.2.
- **Langkah**: CV setiap halaman baru (360px + 1280px, terang + gelap); isi checklist UAT di `docs/GO-LIVE.md`; catat hasil di §8.
- **AC**: tidak ada halaman tanpa CV; temuan penulisan di `docs/TASKS-REFACTOR-UI.md` §8.
- **Risiko**: CV butuh manusia di peramban (`docs/TASKS.md:355,365,388,406` berulang mencatat ini belum pernah tuntas).

## 5. Pertanyaan Terbuka

Jangan rencanakan perubahan sebelum ini diputuskan.

| # | Pertanyaan | Pilihan | Dampak bila salah |
|---|---|---|---|
| 1 | Status tanpa kelas enum (`surat_tugas_visitasi.status`, `form_definitions.status`, `accreditation_exports.status`) — buat enum baru atau cukup `StatusChip`? | (a) Enum baru `VisitasiStatus`/`ExportStatus` (butuh cast model); (b) tetap `StatusChip`, kolom pakai `->badge()` + closure | (a) menyentuh model; (b) kolom panel masih pakai closure, bukan kontrak enum |
| 2 | Kunci penyimpanan tema: Filament memakai `filament:theme:mode`; portal memakai apa? | (a) portal ikut `filament:theme:mode`; (b) kunci netral `pkl:theme` + sinkron | Flash tema / preferensi tidak konsisten |
| 3 | Rute halaman preferensi notifikasi: satu `/notifikasi` untuk semua peran, atau per permukaan? | (a) satu rute `auth`-only; (b) duplikat di panel + portal | Autentikasi ganda / tautan mati di satu permukaan |
| 4 | Aktifkan `->databaseNotifications()`? Tabel `notifications` sudah ada (`docs/schema.sql:734`) dan dipakai notifikasi domain (Laravel Notifiable) | (a) jangan aktifkan dulu; (b) aktifkan dengan periksa kolom | Filament butuh kolom `notifiable_type/notifiable_id/data/read_at` — kalau skema domain berbeda, notifikasi rusak. **Verifikasi kolom sebelum mengaktifkan.** |
| 5 | `userMenuItems()` + perpindahan peran: action menu bisa `redirect()`? | (a) `Action::make()->action(fn () => ...)`; (b) `->url()` + `->postToUrl()` | Pemilih peran tidak berfungsi |
| 6 | Peran per panel: cukup `canAccessPanel()` (User model) atau perlu middleware peran per panel? | (a) cukup; (b) middleware baru | Middleware baru = kelas baru, bukan logika bisnis — masih boleh |
| 7 | `ImportAction`/`ExportAction` bawaan menggantikan `HasImportAction` + `StartAccreditationExport`? | (a) tidak (YAGNI, alur impor sudah jalan); (b) ya | (b) = paket + abstraksi baru untuk fitur yang sudah bekerja |
| 8 | Setelah refactor, apakah `AntreanPengajuan` tetap satu kelas dengan query per peran, atau dua kelas (Kaprog panel, Walas portal)? | (a) dua kelas tipis, satu Action; (b) satu kelas, dua view | Divergensi aturan Gate 1/Gate 2 bila query disalin |
| 9 | Angka resmi tes + migrasi untuk `AGENTS.md` | hasil R0.1 | Dokumen terus menyesatkan |
| 10 | `docs/schema.sql` komentar Q-K/Q-I boleh disentuh? | (a) ya (komentar saja); (b) tidak, catat di sini | Dokumen tetap ambigu |

## 6. Risiko

| # | Risiko | Dampak | Mitigasi |
|---|---|---|---|
| 1 | `/app` hilang memutus tautan tersimpan/disimpan pengguna (job, email, bookmark) | Tautan 404 di email pengingat & notifikasi | R5.2 memperbarui semua tautan; pertimbangkan redirect `/app/{page}` → permukaan baru selama satu rilis (keputusan tambahan, bukan sekarang) |
| 2 | Versi Filament terpasang bisa berbeda dari docs v5.1.1 yang dipakai verifikasi | Nama API salah | R0.1 mencatat versi; verifikasi ulang API di task yang memakainya |
| 3 | Tiga panel baru = tiga set konfigurasi warna/tema yang mudah menyimpang | Inkonsistensi visual | Satu trait/helper konfigurasi panel bersama bila ketiganya identik (kecil, boleh) |
| 4 | Split `AntreanPengajuan`, `Visitasi`, `PengesahanLaporan` menjadi dua permukaan menyalin aturan | Aturan divergen antar permukaan | Keduanya hanya UI; semua keputusan tetap di Action/Policy. Tidak ada `if ($surface)` di Action |
| 5 | Guard otorisasi bergeser dari `canAccess()` + panel ke middleware portal | Celah akses lintas peran | R6.1 menguji 200/403 tiap peran × tiap permukaan |
| 6 | `EnsureActiveRole` non-persistent pada request Livewire | 403 hilang di request AJAX | `->authMiddleware([...], isPersistent: true)` (terverifikasi di docs) |
| 7 | CV belum pernah tuntas sejak M0 (`docs/TASKS.md` berulang) | Refactor selesai tanpa bukti visual | R6.3 memberi tenggat: tidak ada halaman tanpa CV |
| 8 | Halaman KelolaVisitasi/PengesahanLaporan kini Blade panjang; konversi bisa kehilangan field | Fitur hilang tanpa tes | Tambah tes kolom untuk tiap Page yang dikonversi di R3 |
| 9 | `resources/lang/id` dipakai lintas permukaan; teks portal baru menumpuk | Duplikasi terjemahan | Satu kunci per konsep; tidak membuat namespace per permukaan |

## 7. Dicoret (tidak dikerjakan)

| Item | Alasan satu baris |
|---|---|
| Membangun `app/Filament/Support/*` (§14.3 TRD) | Komponen sudah ada di `app/Support/StatusChip.php` + `resources/views/components/*`; membangun ulang = duplikasi. |
| Mengganti impor/ekspor Hubin ke `ImportAction`/`ExportAction` bawaan | Alur `ImportData` + `StartAccreditationExport` sudah berjalan dan teruji; penggantian = paket/abstraksi baru tanpa manfaat. |
| Kanban Walas dengan komponen Filament | Tidak ada padanan native; DESIGN.md minta papan 5 kolom scroll horizontal. |
| Dasbor Filament bawaan (`Dashboard` + `AccountWidget`) sebagai halaman utama | Halaman utama per permukaan sudah ditentukan; dasbor kosong = halaman tanpa isi. |
| Enum status baru untuk semua status (`VisitasiStatus`, `ExportStatus`) | Menunggu §5 no. 1; menambah enum + cast = perubahan model sebelum ada keputusan. |
| Wireframe | Dicabut di PRD v3 (`docs/PRD.md:597`); diganti CV `TRD.md` §17.2. |
| Mengubah Actions/Policies/Services/skema | Dilarang di refactor ini. |

## 8. Catatan Pelaksanaan

| Topik | Catatan |
|---|---|
| R0.1 — tag baseline | `git tag pre-ui-refactor` di `a0dc589` ("project m10"), cabang `jii2high` (ahead 2 dari `origin/jii2high`). |
| R0.1 — versi paket | `php` 8.4.27, `node` v24.22.1, `npm` 11.13.0, `composer` 2.10.2, `laravel/framework` v13.43.0, `filament/filament` v5.11.4 (`filament/tables`, `filament/forms`, `filament/actions` juga v5.11.4), `livewire/livewire` v4.4.8, `pestphp/pest` v5.3.7. |
| R0.1 — baseline tes | `php -d memory_limit=1G vendor/bin/pest` (port dari `phpunit.xml`): **271 tes lulus, 974 assertion**, ~127 detik, setelah `npm run build` hijau. |
| R0.1 — server DB | Lingkungan dev = **MySQL 8.4.3 (Laragon) di port 3306**, sesuai `phpunit.xml` dan `.env`. Database `sistem_pkl` dan `sistem_pkl_test` ada. Ada server lain **MariaDB 12.2.2 di port 3308** (punya `lapaksatoe`, bukan proyek ini) — jangan dipakai untuk tes. `phpunit.xml` tidak diubah. |
| R1 — dokumen | R1.1–R1.4 selesai: `45fabc2` (PRD), `ea5e9c7` (TRD), `477faf6` (AGENTS.md), `e7f029b` (komentar `schema.sql`). PRD — §1.3 dapat kolom "Permukaan", rute Bagian 2, NFR responsif, penamaan `schema.sql`/`DESIGN.md`/folder `old physical dokument examples`. TRD — §1 diagram, §2, §3.1/§3.2, §5.4/§5.5, §6.1/§6.2, §7.2, §9.3, §13.1, §14.2–§14.4, §19, §20, §21. AGENTS.md — lima aturan UI baru. |
| R1 — di luar rencana | PRD §1.5 no.7 dan judul/BR-F8-1 ikut disamakan ke struktur relasional `form_sections`/`form_items` (keputusan pemilik saat sesi ini); §2 no.8 aslinya menugaskan itu ke R1.2. |
| R1.4 — AC grep | `grep -r "scheme.sql\|old physical document examples" docs/` tidak nol. Sisa hanya di `docs/TASKS-REFACTOR-UI.md` sendiri (kutipan temuan §2 dan teks AC) dan `docs/TASKS.md` (beku, tidak ditimpa). PRD, TRD, `schema.sql`, `DESIGN.md` bersih. |
| R1.4 — komentar `schema.sql` | Label `Q-K` (`:82`) dan `Q-I` (`:671`, `:804`) dihapus. Hanya komentar; struktur dan CHECK tidak berubah. Pemilik menyetujui sentuhan komentar di dokumen beku. |
| R2 — fondasi | R2.1–R2.6 selesai. R2.1: `StatusChip::CHIPS` jadi `public`, tambah `ICONS` (tone → Heroicon) + `icon()`, empat enum status mengimplementasikan `HasLabel`/`HasColor`/`HasIcon`. R2.2: warna panel = hex `DESIGN.md` (`primary #1E40AF`, `info #06B6D4`, `success #166534`, `warning #92400E`, `danger #991B1B`) lewat trait `App\Providers\Filament\Concerns\ConfiguresPanel`. R2.3: `KaprogPanelProvider` (`/kaprog`) dan `GuruPanelProvider` (`/guru`) baru; `canAccessPanel` mengenal tiga panel; `authMiddleware(..., isPersistent: true)` di semua panel. R2.4: `User::panelHomeUrl()` memetakan enam peran ke `/hubin`,`/kaprog`,`/guru`,`/siswa`,`/walas`,`/kepsek` (fallback `/login`). R2.5: `userMenuItems()` berisi aksi `switch-<peran>`; pindah peran lewat `POST /role` (`SwitchRoleController`); `resources/views/filament/role-switcher.blade.php` dihapus. R2.6: `routes/portal.php` (`/siswa`, `/walas`, `/kepsek`), middleware `App\Http\Middleware\EnsurePortalRole` (alias `portal.role`), layout `components/layouts/portal.blade.php`, tiga komponen `App\Livewire\Portal\*` (rangka; isi di R4). |
| R2 — tes | Suite penuh hijau: **286 tes, 1174 assertion** (271 → +15: `tests/Unit/StatusChipTest.php`, `tests/Feature/Portal/PortalAccessTest.php`, `tests/Feature/Auth/RoleSwitchTest.php`, tambahan di `ActiveRoleTest`/`ExampleTest`). |
| R2.2 — kunci tema | Panel dan portal sudah memakai kunci yang sama, `filament:theme:mode` (`theme-switcher.blade.php`, `components/theme-script.blade.php`) — §5 no. 2 opsi (a), tanpa kunci baru. `resources/views/filament/panel-assets.blade.php` memang sudah hanya `@vite`, tidak ada CSS ganda untuk dikecilkan. |
| R2.3 — panel `app` sementara | Panel `app` **dipertahankan** sampai R5.1 atas keputusan pemilik sesi ini: ~10 berkas tes masih memanggil `Filament::getPanel('app')` dan `/app/...`, dan halaman baru pindah di R3/R4. Membubarkannya sekarang akan mematikan aturan "271 tes hijau per task" atau memindahkan pekerjaan R6.2 ke depan. `app/Filament/App/**` dan `AppPanelProvider` dihapus di R5.1. |
| R2.5 — userMenuItems redirect | §5 no. 5 diputuskan opsi (b): `Action::make('switch-<peran>')->url(route('role.switch', ...))->postToUrl()` + `POST /role`. Redirect di dalam `->action()` menu akun v5 tidak dipakai, jadi tidak perlu prototipe. Peran tidak dimiliki/di luar daftar ditolak 403/validasi di `SwitchRoleController`. Catatan: menu akun panel berpindah lewat POST, jadi halaman dimuat ulang penuh; AC "tanpa reload penuh" hanya terpenuhi di portal (`RoleSwitcher` Livewire dengan `navigate: true`). Menukar ke `->action()` agar SPA belum diverifikasi di v5 terpasang — lihat §5 no. 5. |
| R2.6 — skeleton portal | Tiga kelas Livewire dibuat kosong (hanya judul) sebagai sasaran rute; R4 mengisi isinya. Navigasi bawah memfilter `Route::has()`, jadi belum ada tautan mati ke `/siswa/bimbingan`, `/siswa/jadwal-sidang`, `/walas/kanban`, `/notifikasi` sebelum R4.5 membangunnya. Lonceng header muncul dengan syarat yang sama. CV 360px/1280px dan terang/gelap belum dikerjakan (manual, R6.3). |
| R3.1a — Hubin: antrean surat + dokumen fisik | Selesai. `AntreanSuratPengantar` dan `KonfirmasiDokumenFisik` membuang `protected string $view`. Tabel dirender native lewat `content()` + `EmbeddedTable::make()` di view bawaan `filament-panels::pages.page`, jadi tidak ada Blade kustom. Direktori `resources/views/filament/hubin/pages/` beserta 5 view di dalamnya dihapus (juga milik R3.1b/c). |
| R3.1b — Hubin: kelola visitasi + penilaian industri | Selesai. `KelolaVisitasi` = `Table` + `ActionGroup` (setujui / tolak beralasan / terbitkan surat / unduh surat); buat surat manual pindah ke header Action ber-`schema()` (`Select` guru `->live()` → perusahaan, `Toggle` umum). `PenilaianIndustri` = `Table` (badge status tautan) + aksi terbitkan / cabut / terbitkan ulang ber-`schema()` alasan minimal `pkl.rejection_note.min_length`. Pengumuman tautan baru pindah dari section "Tautan siap dibagikan" ke notifikasi `->persistent()`; kunci terjemahan baru `pkl.industri.link_email_note`. |
| R3.1b — tes | `PlottingAndVisitasiTest`: `->call('issueLetter', …)` → `->callAction(TestAction::make('issue')->table($visitasi))`; asersi kolom "Guru pembimbing" ditambah. Tes baru di `IndustriAssessmentTest`: halaman Hubin menampilkan tautan aktif, alasan < 10 karakter tidak mengubah state, cabut beralasan mengubah state ke `expired`, tombol "Terbitkan tautan" muncul kembali. |
| R3.1c — Hubin: ekspor + widget monitoring | Selesai. `EksporAkreditasi` pakai `content()` + `EmbeddedTable`; `Monitoring` membuang `gates()`/`ttdSteps()` dan memakai `getHeaderWidgets()` berisi `MonitoringGateWidget` + `MonitoringTtdWidget` (kelas baru di `app/Filament/Widgets/`, `$isLazy = false`). Penyimpangan dari rencana: (a) dua kelas widget, bukan satu `Filament/Hubin/Widgets/MonitoringWidget`; (b) kelas ditaruh di `app/Filament/Widgets/` yang **tidak** masuk `discoverWidgets()` supaya tidak bocor ke Dasbor `hubin`/`kaprog`; R3.6 cukup me-`use` kelas yang sama. `resources/views/filament/monitoring.blade.php` **dipertahankan** karena masih dipakai panel `app` (Kaprog) sampai R3.6. |
| R3.1 — tes | Suite penuh hijau: **287 tes, 1196 assertion** (286 → +1 tes, 1174 → 1196 assertion). |
| R3.2 — Kaprog: antrean Gate 1 | Selesai. `app/Filament/Kaprog/Pages/AntreanPengajuan.php` hanya berisi bagian Kaprog: `Table` native + `content()` + `EmbeddedTable`, tanpa `protected string $view`. Kolom kuota `ViewColumn` (`components.quota-bar`) diganti `TextColumn::make('kuota')->badge()->state(...)->color(...)` yang memakai `QuotaService::label()` dan `QuotaService::tone()` (pola sama dengan Hubin R3.1a); label tetap "Kuota perusahaan", nilai "n dari 6". `app/Filament/App/Pages/AntreanPengajuan.php` dan `resources/views/filament/app/pages/antrean-pengajuan.blade.php` dihapus. Empat aksi Kaprog (Setujui, Terima siswa, Tolak, Setujui penarikan) dan jalur Tolak (company vs Kaprog) dipertahankan; aksi Walas (`signWalas`, `rejectByWalas`) keluar bersama bagian Walas. |
| R3.2 — penyimpangan rencana | Bagian Walas (Gate 2) ikut hilang dari panel `app` sampai R4.4 membangun `/walas`; ini konsekuensi "pindah dari `App`" dan sudah diputuskan pemilik di sesi ini. Tes di luar daftar rencana ikut diperbarui agar suite hijau: `PengajuanSuratQuotaTest:437` → `/kaprog/antrean-pengajuan`; dataset `RoleSmokeTest` (siswa/guru denied → `/kaprog/antrean-pengajuan`, kaprog allowed → `/kaprog/antrean-pengajuan`, entri `allowed` walas dihapus). Teks R3.2 hanya menyebut `PengajuanFlowTest:28,314-324`; baris 28 **tidak** diubah karena `setCurrentPanel('app')` masih dipakai tes `/app/pengajuan-saya` di berkas yang sama, sementara panel permintaan `/kaprog/...` ditetapkan route. |
| R3.2 — tes | Suite penuh hijau: **287 tes, 1195 assertion** (287 → 287 tes, 1196 → 1195 assertion; −1 assertion karena satu URL walas dihapus dari `RoleSmokeTest`). |
| R3.3 — Kaprog: Form Builder | Selesai. `app/Filament/Kaprog/Pages/FormBuilder.php` tanpa `protected string $view`: daftar definisi = `Table` native (kolom status/terkunci/jumlah item + filter periode) lewat `content()` + `EmbeddedTable::make()`, editor struktur = `form()` (statePath `data`) yang dirender lewat `EmbeddedSchema::make('form')`; keduanya di `content()` agar koeksis. Aksi header: `new` (Select periode + nama + jenis + toggle Kehadiran/Catatan), `save`, `publish` (tombol publish tersembunyi bila sudah terbit); aksi baris: `open`, `newVersion` (hanya bila sudah ada submission). `app/Filament/App/Pages/FormBuilder.php` dan `resources/views/filament/app/pages/form-builder.blade.php` dihapus. |
| R3.3 — penyimpangan rencana | Rencana §3.3 menyebut `Repeater` (`->relationship()`, `->orderColumn('sort_order')`); diputuskan pemilik sesi ini untuk **tidak** memakainya: `->relationship()` menulis `form_sections`/`form_items` langsung dari UI, sehingga `SaveFormStructure` (pemegang guard terkunci BR-F8-1 + audit `form_struktur_disimpan`) tidak lagi dipanggil produksi. `Repeater` dipakai sebagai field array biasa (`->reorderableWithButtons()`); struktur tetap disimpan lewat `SaveFormStructure`, dibuat lewat `CreateFormDefinition`, diterbitkan lewat `PublishFormDefinition`, dan diverifikasi lewat `DuplicateFormDefinition`. Tes di luar daftar rencana ikut diperbarui agar suite hijau: dataset `RoleSmokeTest` (`/app/form-builder` → `/kaprog/form-builder` pada daftar allowed kaprog dan denied siswa/walas). |
| R3.3 — tes | Suite penuh hijau: **287 tes, 1213 assertion** (287 → 287 tes, 1195 → 1213 assertion; asersi bertambah dari tes halaman baru di `FormBuilderTest` dan penambahan URL di `RoleSmokeTest`). |
| R3.4 — Kaprog: jadwal sidang + nilai sidang | Selesai. `app/Filament/Kaprog/Pages/JadwalSidangPage.php` tanpa `protected string $view`: periode akademik = `Select` pada `form()` (state `periodId`, `->live()`), daftar jadwal = `Table` native lewat `content()` + `EmbeddedTable::make()`. Aksi header: `examiner` (Tetapkan penguji: nama, NIK opsional, jenis kontak, kontak) dan `schedule` (Jadwalkan: `Select` siswa dari pool Siap Sidang + `Select` penguji + `DatePicker` tanggal, default hari ini Jakarta). Aksi baris `ActionGroup`: `issue` (terbitkan tautan → notifikasi PIN + tombol WhatsApp), `revoke` (schema alasan ≥ `pkl.rejection_note.min_length`), `shift` (`DatePicker` tanggal baru), `cancel` (`requiresConfirmation`). `app/Filament/Kaprog/Pages/PenilaianSidang.php` tanpa view: satu `Table` `FormSubmission` (sidang, `is_submitted`) dengan kolom permintaan revisi + alasan dari `pendingRevision()`, aksi `approveUnlock` (hanya bila ada revisi menunggu) dan `setKelulusan` (schema status + catatan, prefill via `fillForm()`). Cascade shift tetap di `ShiftSidang`; seluruh konflik (`['jadwal' => [...] ]`) ditampilkan lewat notifikasi danger. `app/Filament/App/Pages/{JadwalSidangPage,PenilaianSidang}.php` dan `resources/views/filament/app/pages/{jadwal-sidang,penilaian-sidang}.blade.php` dihapus. |
| R3.4 — penyimpangan rencana | Rencana §3.4 menyebut `PenilaianSidang` dengan tabel revisi terpisah; diputuskan pemilik sesi ini memakai satu tabel `FormSubmission` dengan kolom/aksi revisi, karena Filament mengizinkan satu `table()` per Page dan permintaan revisi tetap terlihat per siswa. `JadwalSidangPage` juga satu tabel jadwal; "Jadwalkan sidang" jadi aksi header dengan pilihan siswa dari pool Siap Sidang, bukan tabel pool terpisah. Tes di luar daftar rencana ikut diperbarui agar suite hijau: `SidangFlowTest` — `setCurrentPanel('kaprog')`, `startShift`/`cancelShift` → `callAction(TestAction::make('cancel')->table($first))`, `startRevoke`/`revokeLink` → `callAction(TestAction::make('revoke')->table($jadwal), ['reason' => ...])`; `RoleSmokeTest` — `/app/jadwal-sidang` → `/kaprog/jadwal-sidang`, `/app/penilaian-sidang` → `/kaprog/penilaian-sidang`. |
| R3.4 — tes | Suite penuh hijau: **288 tes, 1227 assertion** (287 → 288 tes dari tes baru "geser jadwal yang konflik ditolak"; 1213 → 1227 assertion). |
| R3.5 — Kaprog: plotting + visitasi | Selesai. `app/Filament/Kaprog/Pages/PlottingGuru.php` tanpa `protected string $view`: periode = `Select` pada `form()` (`periodId`, `->live()`); daftar perusahaan = `Table` `->records()` dari `PlottingService::eligibleCompanies()` (sumber non-query, jadi tanpa search/sort), kolom perusahaan, jumlah siswa, guru pembimbing, nomor surat. Aksi baris: `assign` (`Select` guru `->live()` + peringatan lunak multi-wilayah lewat `Text` `warning`, prefill guru lama via `fillForm()` → `AssignGuru`, yang menerbitkan surat baru + audit saat guru berganti), `issue` (terbitkan ulang saat hold TTD, `DocumentIssuanceHeld` → notifikasi warning), `download`. `app/Filament/Kaprog/Pages/Visitasi.php` tanpa view: `Table` `SuratTugasVisitasi` milik Kaprog (`kaprog_id`) dengan badge `StatusChip`, perusahaan, nomor surat, masa berlaku, alasan tolak; aksi baris `download`; aksi header `create` (`Select` guru `->live()` → perusahaan multi + toggle umum → `RequestVisitasi`). `app/Filament/App/Pages/{PlottingGuru,Visitasi}.php` dan `resources/views/filament/app/pages/{plotting-guru,visitasi}.blade.php` dihapus. |
| R3.5 — penyimpangan rencana | Sisi Guru pada `Visitasi` (unggah dokumentasi) ikut hilang dari panel `app` sampai R3.7 membangun panel guru — diputuskan pemilik sesi ini sebagai konsekuensi "pecah dua". Dua peringatan lunak lama dipertahankan di modal: multi-wilayah (`jumlah_wilayah > 1`, AC) dan multi-jurusan perusahaan. Tes di luar daftar rencana ikut diperbarui agar suite hijau: `PlottingAndVisitasiTest` — impor pindah ke `App\Filament\Kaprog\Pages`, `setCurrentPanel('kaprog')`, tes "renders for kaprog and guru" jadi "renders for kaprog", ditambah tes aksi tabel `assign`; `RoleSmokeTest` — `/app/plotting-guru` → `/kaprog/plotting-guru` (allowed kaprog, denied walas), `/kaprog/visitasi` masuk allowed kaprog, `/app/visitasi` dihapus dari allowed guru. |
| R3.5 — tes | Suite penuh hijau: **289 tes, 1233 assertion** (288 → 289 tes dari tes aksi tabel plotting; 1227 → 1233 assertion). |
| R3.6 - Kaprog: pengesahan step 2 + monitoring | Selesai. `app/Filament/Kaprog/Pages/PengesahanLaporan.php` tanpa `protected string $view`: satu `Table` `DocumentSignature` berstatus `pending` pada step 2-4 dalam jurusan Kaprog, kolom siswa, perusahaan, step (badge label dari `PengesahanAction::STEP_LABELS`), laporan (tautan unduh `laporan.download`), dan alasan tolak. Aksi baris: `approve` (step 2, `requiresConfirmation` → `SignStep`), `reject` (step 2, `schema` catatan minimal 10 karakter → `RejectStep`), `ActionGroup` `issue`/`reissue` (step 3/4; `reissue` ber-`schema` alasan minimal 10 karakter → `IssueSignerLink`/`ReissueSignerLink`). Bulk `approveBulk` memanggil `BulkApproveSignatures` dan melaporkan hasil per item lewat notifikasi `->persistent()`: judul "n laporan disetujui, m gagal" plus daftar "#id: alasan", lalu `deselectRecordsAfterCompletion()`. `app/Filament/Kaprog/Pages/Monitoring.php` tanpa view memakai `getHeaderWidgets()` berisi `MonitoringGateWidget` + `MonitoringTtdWidget` yang sama dengan R3.1c. Dihapus: `app/Filament/App/Pages/{Monitoring,PengesahanLaporan}.php`, `resources/views/filament/app/pages/pengesahan-laporan.blade.php`, dan `resources/views/filament/monitoring.blade.php`. |
| R3.6 - penyimpangan rencana | Bagian Kepsek (step 5) pada `PengesahanLaporan` ikut hilang dari panel `app` sampai R4.3 membangun `/kepsek`, konsekuensi "bagian Kepsek pindah ke `/kepsek`" (§3.3 baris 112, §3.5 baris 129). `resources/views/filament/monitoring.blade.php` dihapus sekarang, bukan di R5.1, karena R3.6 melepas pemakai terakhirnya. Rencana menaruh tes bulk di `SidangFlowTest`; bulk Kaprog sesungguhnya diuji langsung lewat aksi tabel di `PengesahanFlowTest`. Tes di luar daftar rencana ikut diperbarui agar suite hijau: `MonitoringTest` (impor pindah ke `App\Filament\Kaprog\Pages\Monitoring`, `setCurrentPanel('kaprog')`, URL `/app/monitoring` → `/kaprog/monitoring`), `RoleSmokeTest` (kaprog allowed `/kaprog/pengesahan-laporan` dan `/kaprog/monitoring`; kepsek allowed `/app/pengesahan-laporan` dihapus; denied siswa/walas/guru `/app/monitoring` → `/kaprog/monitoring`; denied guru `/app/pengesahan-laporan` → `/kaprog/pengesahan-laporan`). |
| R3.6 - tes | Suite penuh hijau: **291 tes, 1247 assertion** (289 → 291 tes dari dua tes panel baru di `PengesahanFlowTest`: "kaprog menandatangani step 2 dari panel pengesahan" dan "bulk panel pengesahan melaporkan alasan tiap laporan yang gagal"; 1233 → 1247 assertion). |
| R3.7 — Guru: bimbingan + penilaian industri + visitasi | Selesai dalam satu sesi (tidak dipecah R3.7a/b). `app/Filament/Guru/Pages/BimbinganSiswa.php` tanpa `protected string $view`: satu `Table` `BimbinganSubmission` (`is_archived` palsu) yang query-nya dibatasi `whereHas('pengajuanSiswa.company.plottings', guru_id = pengguna)` sehingga hanya siswa bimbingan guru itu; kolom siswa/perusahaan/status (badge `StatusChip`)/bagian akhir/keterangan/feedback/waktu. Aksi baris: `accept` dan `revisi` (masing-masing `schema` catatan opsional → `GiveFeedback`), `download` (`bimbingan.download`), dan `approveFinal` (tampil hanya untuk bagian akhir `diterima` tanpa `laporan_versions`, → `ApproveLaporanFinal`). `app/Filament/Guru/Pages/PenilaianIndustri.php` tanpa view: periode = `Select` pada `form()` (`periodId`, `->live()`); daftar = `Table` `->records()` dari baris (`company` × `definition`) pilihan guru, sumber non-query karena baris tanpa submission tetap perlu tampil (tombol Terbitkan pertama). Kolom perusahaan/form/siswa/status tautan/nilai/window; aksi baris `issue`/`revoke`/`reissue` memanggil `GenerateIndustryLink`/`RevokeIndustryLink`/`ReissueIndustryLink`; tautan baru diumumkan notifikasi `->persistent()` (`pkl.industri.link_email_note`). `app/Filament/Guru/Pages/Visitasi.php` tanpa view: `Table` `SuratTugasVisitasi` milik guru (`guru_id`) dengan badge status, perusahaan, nomor surat, masa berlaku, daftar dokumentasi (kolom `->html()` berisi tautan `visitasi.documentation.download`), alasan tolak; aksi baris `upload` (tampil hanya bila surat bernomor, status disetujui/dicetak/aktif, dan belum kedaluwarsa) ber-`schema` `Select` perusahaan + `DatePicker` + `FileUpload` + catatan → `UploadDokumentasi`, plus `download` surat. Dihati: `FileUpload->storeFiles(false)` agar berkas privat disimpan lewat `FileStorageService` (bukan dua kali). Dihapus: `app/Filament/App/Pages/{BimbinganSiswa,PenilaianIndustri}.php`, `resources/views/filament/app/pages/{bimbingan-siswa,penilaian-industri}.blade.php` (view `visitasi.blade.php` sudah dihapus di R3.5). |
| R3.7 — penyimpangan rencana | `accept`/`revisi` di `BimbinganSiswa` dibuat aksi datar, bukan `ActionGroup`, agar tes `TestAction::make('accept')->table($record)` menemukan nama aksi top-level (Filament mencocokkan nama pada `getRecordActions()`; aksi bersarang butuh bentuk array). Halaman per-siswa berisi kartu lama diganti tabel per unggahan, sehingga siswa tanpa unggahan tidak lagi muncul; unggahan `bagian akhir` tetap terlihat. Rencana menyebut `Table` + `Actions\Action`; `PenilaianIndustri` memakai `->records()` (bukan `->query()`) supaya baris "belum ada submission" tetap tampil. Daftar dokumentasi visitasi ditampilkan tanpa Blade baru lewat kolom `->html()` (tautan dari `route()`, semua bagian dinamis lewat `e()`). Teks UI memakai string Bahasa Indonesia langsung di kelas Page (mengikuti R3.4–R3.6), bukan kunci `resources/lang/id` baru. Tes di luar daftar rencana ikut diperbarui agar suite hijau: `RoleSmokeTest` — dataset guru pindah panel ke `guru` (`/guru`, `/guru/bimbingan-siswa`, `/guru/penilaian-industri`, `/guru/visitasi` allowed; `/app/pengaturan-notifikasi` tetap), denied kaprog `/app/penilaian-industri` → `/guru/penilaian-industri`, ditambah denied `/guru/bimbingan-siswa` (siswa) dan `/guru/visitasi` (walas). |
| R3.7 — tes | Suite penuh hijau: **295 tes, 1288 assertion** (291 → 295 tes; 1247 → 1288 assertion). Empat tes panel baru: `BimbinganFlowTest` "halaman bimbingan guru hanya menampilkan siswa bimbingannya" dan "guru memberi feedback dan ACC laporan final dari tabel bimbingan"; `IndustriAssessmentTest` "halaman penilaian industri guru menerbitkan tautan dalam window"; `PlottingAndVisitasiTest` "halaman visitasi guru menampilkan unggah dokumentasi hanya setelah surat bernomor" (sekaligus memanggil aksi `upload` dengan `UploadedFile::fake()->image()` dan memastikan satu baris `visitasi_dokumentasi` tersimpan lewat `FileStorageService`). |
| R4.1 — Portal siswa: pengajuan | Selesai. `app/Livewire/Portal/PengajuanSiswa.php` (kelas Livewire penuh, `WithFileUploads`) memuat seluruh alur draf → kirim → revisi setelah ditolak, unggah surat balasan, dan penarikan PKL dengan memanggil Action domain yang sama (`SaveDraft`, `SubmitPengajuan`, `ReopenPengajuan`, `SubmitSuratBalasan`, `RequestWithdrawal`); model diimpor sebagai `PengajuanSiswaModel` karena nama kelas komponen sama. View `resources/views/livewire/portal/pengajuan-siswa.blade.php` memakai layout portal + token `DESIGN.md` (`pkl-card`, `pkl-btn`, `pkl-input`, `status-badge`, `masked-contact`, `CompanyAutocomplete`), target sentuh ≥ 44px. Dihapus: `app/Filament/App/Pages/PengajuanSaya.php` dan `resources/views/filament/app/pages/pengajuan-saya.blade.php`. |
| R4.1 — penyimpangan rencana | Dua pengganti tanpa Filament: (a) toast `Filament\Notifications\Notification` diganti pesan `$flash` + `$flashTone` di dalam view (banner `role="status"`); (b) modal konfirmasi kemiripan `x-filament::modal` + `dispatch('open-modal')` diganti panel inline di dalam card data perusahaan, tombol "Tetap simpan" tetap memanggil `confirmSimilarity()`. CSS baru satu kelas: `.pkl-btn-danger` di `resources/css/app.css` (token `DESIGN.md` button-danger). |
| R4.1 — tes | Suite penuh hijau: **297 tes, 1297 assertion** (295 → 297 tes; 1288 → 1297 assertion). Dua tes Livewire portal baru: `PengajuanFlowTest` "portal siswa menyimpan draf lalu mengirim lewat komponen Livewire" dan `PengajuanSuratQuotaTest` "portal siswa mengunggah surat balasan lewat komponen Livewire". Tes di luar daftar rencana ikut diperbarui agar suite hijau (Aturan Keras no. 4 dan no. 5): `PengajuanFlowTest` — `setCurrentPanel('app')` → `'kaprog'` (URL `/app/pengajuan-saya` → `/siswa`), panel `app` tak lagi dipakai di berkas itu; `PengajuanSuratQuotaTest` — `/app/pengajuan-saya` → `/siswa` (asersi teks "Surat balasan perusahaan"/"Unggah surat balasan" tetap, string yang sama); `RoleSmokeTest` — allowed siswa `/app/pengajuan-saya` → `/siswa`; `app/Jobs/ProvisionalReminderJob.php:57` — tautan notifikasi `/app/pengajuan-saya` → `/siswa` (Aturan Keras no. 5; R5.2 melewatkan baris ini karena sudah beres). |
| R4.6 — Ikon Blade Heroicons + CSP Vite dev | Selesai. Opsi B dipilih pemilik sesi: `blade-ui-kit/blade-heroicons` 2.7.0 dinaikkan dari dependensi transitif Filament menjadi `require` langsung di `composer.json` (`composer.lock` ikut). Lonceng di `resources/views/components/layouts/portal.blade.php` diganti dari SVG inline menjadi `<x-heroicon-o-bell class="h-6 w-6" aria-hidden="true" />`; `aria-label` tetap di `<a>` pembungkus. `DESIGN.md` menetapkan dua bentuk pemakaian satu keluarga ikon: string `heroicon-o-*` di panel Filament, komponen `<x-heroicon-o-*>` di Blade. Akar masalah HTML mentah: saat `composer dev` berjalan, `@vite` memancarkan `http://[::1]:5173` (cross-origin) sedangkan CSP hanya mengizinkan `'self'`, sehingga CSS/JS/HMR diblokir; `php artisan serve` tidak menolong selama `public/hot` masih ada. Perbaikan di `app/Http/Middleware/SecurityHeaders.php`: guard `Vite::isRunningHot()` (bukan `isLocal()`, karena `phpunit.xml` memakai `APP_ENV=testing`), origin dibaca dari `Vite::hotFile()` lalu divalidasi `parse_url` (hanya skema `http`/`https` + host + port, supaya nilai berkas tidak masuk header mentah), lalu ditambahkan ke `script-src`, `style-src`, `img-src`, `font-src`, `connect-src`, plus bentuk `ws://`/`wss://` di `connect-src` untuk HMR. Tanpa hot file CSP tetap sama; produksi tidak berubah. Tindak lanjut: `vite.config.js` menetapkan `server.host: '127.0.0.1'` supaya hot file berisi `http://127.0.0.1:5173`, bukan literal IPv6 `[::1]` yang gagal dibuka browser pada mesin ini; hanya halaman Blade yang bergantung pada `@vite` yang terdampak, panel Filament punya CSS sendiri. |
| R4.6 — tes | Suite penuh hijau: **299 tes, 1301 assertion** (297 → 299 tes; 1297 → 1301 assertion). Dua tes baru di `tests/Feature/Security/SecurityHeadersTest.php`: "CSP mengizinkan origin Vite saat mode hot" (memakai `Vite::useHotFile()` dengan berkas sementara, memastikan origin dan `ws://` muncul) dan "CSP tidak mengizinkan origin Vite tanpa mode hot". `npm run build` hijau. Tidak ada tes ikon terpisah; `PortalAccessTest` sudah merender layout portal sehingga komponen `<x-heroicon-o-bell>` pasti ter-resolve. |
| R4.2 — Portal siswa: bimbingan + jadwal sidang | Selesai. `app/Livewire/Portal/BimbinganSiswa.php` + view memuat unggah bimbingan, progres interaksi dengan progress bar `accent-strong`, guard provisional sebagai banner peringatan, revisi laporan (F7), dan penanda bagian akhir; semua panggilan lewat `SubmitBimbingan`/`UploadRevisiLaporan`/`MarkBagianAkhir`. `app/Livewire/Portal/JadwalSidang.php` + view hanya menampilkan tanggal, penguji, dan status jadwal milik siswa. Rute `/siswa/bimbingan` dan `/siswa/jadwal-sidang` masuk grup `portal.role:siswa`; tautan navigasi bawah layout portal yang sebelumnya disembunyikan `Route::has()` sekarang tampil. Dihapus: `app/Filament/App/Pages/{BimbinganSaya,JadwalSidangSaya}.php` dan dua view `resources/views/filament/app/pages/*`. |
| R4.2 — penyimpangan rencana | Revisi laporan dan penanda bagian akhir ikut dipindah ke portal, bukan dibuang: halaman lama satu-satunya UI untuk F7. Toast panel diganti banner `$flash`/`$flashTone` seperti R4.1; galat validasi Action tampil di field terkait. `JadwalSidang` tidak menampilkan urutan sesi atau nilai (paritas halaman lama; F9 melarang nilai sampai ke siswa). Tes di luar daftar rencana ikut diperbarui agar suite hijau: `RoleSmokeTest` siswa `/app/bimbingan-saya` → `/siswa/bimbingan` dan `/app/jadwal-sidang-saya` → `/siswa/jadwal-sidang`; `PortalAccessTest` menambah dua rute itu ke dataset tamu dan pemilik portal. `docs/GO-LIVE.md:106` masih menyebut `/app/*`; diperbarui di R5.3. |
| R4.2 — tes | Suite penuh hijau: **308 tes, 1331 assertion** (299 → 308 tes; 1301 → 1331 assertion). Empat tes portal bimbingan baru di `BimbinganFlowTest` (unggah + progres, guard provisional, gerbang bagian akhir 3/4 → 4/4, revisi step 4) dan satu tes portal jadwal di `SidangFlowTest` (hanya jadwal milik siswa); `PortalAccessTest` +4 baris dataset. `npm run build` hijau. CV 360px/1280px terang/gelap belum dikerjakan (manual, R6.3). |
| R4.3 — Portal Kepsek: TTD 5 | Selesai. `app/Livewire/Portal/PengesahanKepsek.php` + `resources/views/livewire/portal/pengesahan-kepsek.blade.php` mengisi rangka R2.6. Antrean hanya `document_signatures` step 5 `pending` (tanpa filter jurusan; Kepsek lintas jurusan) beserta relasi siswa/company/laporan. Kartu per laporan: nama siswa, perusahaan, chip `5. Kepsek`, tautan unduh laporan, waktu tunggu, tombol "Setujui (TTD step 5)" ber-`wire:confirm`, dan tombol "Tolak" yang membuka catatan minimal 10 karakter (`RejectStep`). Pilihan satu per laporan lewat checkbox `wire:model.live="selected"`, "Pilih semua" lewat `toggleAll()`, dan bar melayang di atas nav bawah dengan "Setujui (n)" yang memanggil `BulkApproveSignatures` lalu menampilkan ringkasan "n disetujui, m gagal" plus nama tiap laporan gagal dari `bulkResult`. Target sentuh tombol/checkbox ≥ 44px; semua aksi hanya memanggil Action domain. Rute `/kepsek` sudah ada sejak R2.6. |
| R4.3 — penyimpangan rencana | **Pengecualian aturan beku disetujui pemilik sesi** (Opsi A): `app/Actions/Pengesahan/RejectStep.php` kini menulis `signer_user_id` saat aktornya internal, sejajar `SignStep`. Sebelumnya baris internal (step 2/5) yang menjadi `rejected` melanggar CHECK `ck_sign_internal` (`docs/schema.sql:489-490`); ini bug laten pemakaian lama panel `app`, bukan hanya portal. Tanpa perbaikan itu, tombol Tolak step 5 tidak mungkin bekerja, jadi Action diperbaiki sekali di akar, bukan UI menulis kolom langsung. Kontrak eksternal tidak berubah (`$actor === null` tetap tidak menulis `signer_user_id`). Teks UI memakai string Bahasa Indonesia langsung di kelas/view (mengikuti R4.1/R4.2), bukan kunci `resources/lang/id` baru selain `pkl.portal.kepsek_judul` dan `pkl.pengesahan.*` yang sudah ada. |
| R4.3 — tes | Suite penuh hijau: **312 tes, 1357 assertion** (308 → 312 tes; 1331 → 1357 assertion). Empat tes baru di `PengesahanFlowTest`: "tolak step 2 internal mencatat aktor dan alasan"; "portal kepsek hanya menampilkan step 5 dan menandatanganinya" (rantai dimajukan lewat helper `pengesahanAdvanceToStep5`); "portal kepsek menolak step 5 dan mencatat aktor" (catatan < 10 karakter ditolak, lalu catatan valid tersimpan); "bulk portal kepsek memproses tiap laporan independen dan melaporkan yang gagal" (satu step 5 tanpa step 4 signed tetap gagal, yang lain tetap signed). Tes tolak step 4 eksternal ditambah asersi `signer_user_id` tetap `null`. `RoleSmokeTest` dataset kepsek `allowed` ditambah `/kepsek`. `npm run build` hijau; CV 360px/1280px terang/gelap belum dikerjakan (manual, R6.3). |
| R4.4 — Portal Walas: Gate 2 + kanban | Selesai. `app/Livewire/Portal/AntreanWalas.php` + `resources/views/livewire/portal/antrean-walas.blade.php` mengisi rangka R2.6: antrean Gate 2 = pengajuan kelas Walas berstatus `menunggu_ttd_walas` (`visibleTo`), kartu per siswa dengan chip status, tombol "Tanda tangani" ber-`wire:confirm` (`SignByWalas`) dan "Tolak" ber-catatan minimal `pkl.rejection_note.min_length` (`RejectByWalas`); hasil dilaporkan lewat `$flash`/`$flashTone` seperti R4.1–R4.3, bukan toast panel. `app/Livewire/Portal/KanbanKelas.php` + `resources/views/livewire/portal/kanban-kelas.blade.php` memindahkan kanban read-only 5 kolom dari panel `app`: `board()`/`hiddenCount()`/`waitingDays()`/`columnFor()` dipindah apa adanya, view kustom memakai token portal (`--surface`, `--border`, `--primary`), snap horizontal, dan penanda kolom aktif Alpine. Rute `/walas/kanban` (`portal.walas.kanban`) masuk grup `portal.role:walas`; tautan nav bawah layout portal yang sebelumnya menunggu `Route::has` sekarang tampil. Dihapus: `app/Filament/App/Pages/KanbanKelas.php` dan `resources/views/filament/app/pages/kanban-kelas.blade.php`. Tidak ada kontrol ubah di kanban; kedua permukaan memanggil Action yang sama tanpa menyalin aturan Gate 2. |
| R4.4 — penyimpangan rencana | Bagian Walas Gate 2 yang hilang dari panel `app` sejak R3.2 dibangun ulang di portal sebagai `AntreanWalas`, bukan dikembalikan ke panel. Tes di luar daftar rencana ikut diperbarui agar suite hijau: `MonitoringTest` (impor `KanbanKelas` → `App\Livewire\Portal\KanbanKelas`, `setCurrentPanel('app')` dihapus dari tiga tes kanban, tes akses jadi HTTP `/walas/kanban` 200 vs `/kaprog/monitoring` 403 dan sebaliknya); `PortalAccessTest` (`/walas/kanban` ditambah ke dataset tamu dan pemilik portal); `RoleSmokeTest` (`/app/kanban-kelas` → `/walas/kanban` di daftar denied, `/walas` + `/walas/kanban` di allowed walas). Teks UI memakai string Bahasa Indonesia langsung di kelas/view, mengikuti R4.1–R4.3. |
| R4.4 — tes | Suite penuh hijau: **316 tes, 1373 assertion** (312 → 316 tes; 1357 → 1373 assertion). Dua tes portal Walas baru di `PengajuanFlowTest`: "portal walas hanya menampilkan pengajuan kelasnya dan menandatanganinya" (kelas lain tidak tampil, `SignByWalas` dipanggil, `GroupCompanyJob` terkirim) dan "portal walas menolak pengajuan dengan catatan minimal" (catatan < 10 karakter ditolak, lalu catatan valid menyimpan `ditolak_walas`); `PortalAccessTest` +2 baris dataset. `npm run build` hijau; CV 360px/1280px terang/gelap belum dikerjakan (manual, R6.3). |
| R6.3 — CV | *(diisi saat R6.3)*: halaman yang sudah/belum di-CV. |