# AGENTS.md — SIPKL (Sistem Informasi Manajemen PKL, SMKN 1 Kota Bekasi)

## Repo state

- Docs-only planning repo: no code, no git, no `composer.json` yet. The first work item is `TASKS.md` M0.1 (bootstrap Laravel). Don't hunt for an app or assume any framework scaffolding exists.
- Docs and all domain terms are Indonesian (statuses like `sedang_pkl`, `menunggu_verifikasi_kaprog`; classes like `NomorSuratService`, `PengajuanPkl`). Keep file/class/table names consistent with `TASKS.md`.
- `codegraph` MCP is configured in `opencode.jsonc`, but there is no `.codegraph` index (no code yet) — use Grep/Read until code exists.

## Doc hierarchy (what wins)

- `docs/PRD.md` (v2) — source of truth for product behavior. On conflict, PRD wins; implementation details follow `TASKS.md`.
- `docs/TASKS.md` — milestone/task breakdown; its "Aturan Eksekusi" rules bind agents (see below). `AC-xx` codes refer to TRD §11 **as adjusted by PRD Lampiran A**.
- `TRD.md` and `MILESTONES.md` are referenced by the PRD but are NOT in this repo — don't look for them; AC adjustments live in PRD Lampiran A.
- `docs/schema.sql` — MySQL 8.0.16+ reference design, NOT a runnable script. Translate to Laravel migrations per milestone; Laravel framework tables (sessions, cache, jobs, …) come from artisan commands, not this file.
- Reference material: `docs/form-penilaian-references/*.pdf` (scoring forms per jurusan RPL/TKR/TP/DKV) and `docs/old physical dokument examples/*.pdf` (surat pengantar/tugas) — fidelity references for the form builder and PDF letter rendering.

## Locked stack (PRD §1.4 — decided, do not re-litigate)

- Laravel (stable) + Filament multi-panel: panel `hubin` at `/hubin` (desktop) and panel `app` at `/app` (all other roles; navigation follows session `active_role`). Blade + Livewire for public/external pages (`/`, `/faq`, `/login`, `/eksternal/*`).
- MySQL 8.0.16+; queue/cache/session = `database` driver (no Redis); `spatie/laravel-activitylog`, dompdf, endroid/qr-code, maatwebsite/excel. Tests: Pest.
- Rejected by decision: Inertia, React/shadcn, Sanctum, Fortify. Never add packages/abstractions outside the TASKS.md list without a task. Alpine/JS allowed only per-page as a fallback if an AC fails.
- Target structure (adapt to the actual repo once it exists): `app/Actions/<Modul>`, `app/Services`, `app/Policies`, `app/Jobs`, `app/Enums`, `app/Filament/Hubin/*`, `app/Filament/App/*`, `app/Livewire/Public|External`, `tests/Feature/<Modul>`, `tests/Unit`.

## Execution rules (TASKS.md "Aturan Eksekusi")

- One task = one reviewed change. Don't merge tasks; don't start the next milestone before its Checkpoint passes.
- Before coding a task: read the PRD module + §1.5 invariants + referenced AC + related `schema.sql` tables, then inspect actual code.
- Business rules (authorization, quota, state, token, score locks) are enforced **server-side** in Actions/Policies/Services; the UI only calls them. A disabled button is not authorization.
- Every important write: DB transaction + idempotent against double-submit + audit log via `activity_log` that never contains credentials/OTP/PIN/raw tokens.
- Report honestly: files changed, checks actually run (Pest/manual), what remains unverified. Never claim a test passes without running it.
- Docs conflict or decision missing: pause only the tasks that depend on it, cite the ID (e.g. `Q-E`), continue independent tasks.
- Custom Blade pages (landing, external portals, Kanban Walas) require wireframes per TASKS.md — ask before styling if none exist.

## Code patterns

- 1 state machine = 1 Enum + 1 transition table + 1 Action class per transition; unknown transitions are rejected (409). No state-machine package.
- OTP / magic-link / PIN secrets: random ≥32 bytes, shown once, stored SHA-256, single-use, revocable; exactly 1 active token per (type, target); reissue = atomic revoke + issue.

## Invariants (PRD §1.5 — the main bug sources)

- Quota 6: recount `sedang_pkl` per company+period inside the transaction after `SELECT … FOR UPDATE` on `companies`; provisional placements count; the kuota VIEW is read-only.
- 5-TTD sequential with partial-reject: step N opens only after N−1 is `signed`; a reject does not reset earlier steps; revision returns only to the rejecting step via a new report version.
- Scores lock after `is_submitted`: 403 on every write path except the official Kaprog-approved revision (new link+PIN to the original examiner).
- Every authorization check = `active_role` + object scope (jurusan/kelas/ownership/token); wrong active role → 403.
- Time: DB stores UTC; operations run in `Asia/Jakarta`; date-only values are inclusive to 23:59:59 Jakarta; scheduled jobs run once per Jakarta date with `timezone('Asia/Jakarta')`; SLA counts Mon–Fri. All date math goes through `app/Services/JakartaTime.php` (M0.4), never ad-hoc.
- Uploads: extension AND content-derived MIME via `UploadValidator` profiles (`laporan`/`foto`/`balasan`), stored on a private disk; downloads only through an authorizing controller.

## Open decisions (PRD Lampiran B — apply the stated defaults)

- Q-A: surat counter per **tahun ajaran** (not period); Q-B: 1 plotting per company+period, industri form per jurusan; Q-E: `nilai_total` = average of items, scale 0–100; Q-G: Hubin uploads the Kepsek signature scan.
- Tasks that depend on open Qs: M3.2 (Q-A), M5.2/M8.17 (Q-B), M8.5 (Q-E), M9.2 (Q-I). If the PO changes a default, update `schema.sql`/tasks before those tasks.

## Milestone order & critical tasks

- Dependencies: M3 before M4; M6 before M7 and M8; M4.13 before M5.2; M7.12 before M8.7; M8.10 before M9.2.
- KRITIS tasks — code review alone is not enough, they need automated + manual tests: M4.13 (quota concurrency lock), M7.2 (provisional selection guard), M7.4 (≥4 bimbingan interactions guard), M7.11 (partial reject + revision versions), M8.10 (exam score lock).
- Concurrency proofs (M3.3 nomor surat, M4.14 quota) require truly parallel connections/processes — sequential calls prove nothing.

## Environment

- Windows / PowerShell. Once Laravel exists: `php artisan migrate`, tests via `php artisan test` or `php vendor/bin/pest` (TASKS.md writes POSIX-style `./vendor/bin/pest`).
