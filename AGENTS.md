# AGENTS.md — AI Context

**SI-PKL** (Sistem Informasi PKL) — SMKN 1 Kota Bekasi.
Laravel 13 + Inertia.js v3 + React 19 + Tailwind CSS v4 + shadcn/ui.
**Status:** M1 (Foundation & Auth) in progress — only scaffold exists.

---

## Wajib Baca Sebelum Eksekusi

Sebelum menulis atau memodifikasi kode pada modul/fitur APAPUN, baca
terlebih dahulu bagian terkait di dokumen berikut:

| Dokumen | Wajib Dibaca Saat |
|---|---|---|
| `DESIGN.md` | Sebelum menyentuh UI, warna, font, komponen, layout |
| `docs/PRD.md` §(relevant) | Sebelum implementasi fitur apa pun — pahami BR, RBAC, alur |
| `docs/TRD.md` §(relevant) | Sebelum buat migration, controller, route, policy, observer |
| `docs/milestone.md` | Sebelum mulai milestone baru |
| `docs/panduan/m*.md` | Sebelum mulai milestone baru — ikuti urutan modul/fitur |
| `docs/diagrams/Sitemap.xml` | Sebelum buat/modi halaman baru — pahami struktur navigasi |
| `docs/diagrams/PDM.xml` | Sebelum buat/modi migration, relasi, model — pahami entity & dependency |
| `docs/diagrams/Flow.xml` | Sebelum buat/modi controller, route, middleware — pahami alur state |

**Jangan** memulai implementasi hanya dari AGENTS.md atau komentar singkat.
AGENTS.md adalah ringkasan, bukan pengganti dokumen di `docs/`.

---

## Dev Commands

| Action | Command |
|---|---|
| Dev server (all at once) | `composer dev` — runs `php artisan serve` + queue + Vite concurrently |
| Build frontend | `npm run build` or `npm run build:ssr` |
| Lint PHP | `composer lint` (`pint --parallel`) |
| Lint TS/JS | `npm run lint` (`eslint . --fix`) |
| Format | `npm run format` (`prettier --write resources/`) |
| PHPStan | `composer types:check` (`phpstan analyse`, level 7) |
| TypeScript | `npm run types:check` (`tsc --noEmit`) |
| Full CI check | `composer ci:check` — lint:check → format:check → types:check → test |
| Run all tests | `composer test` (clears config, runs lint → phpstan → pest) |
| Run tests fast | `php artisan test --compact` |

## Testing (Pest PHP v4)

- `tests/Pest.php` — binds `RefreshDatabase` to all Feature tests
- DB: SQLite `:memory:` for testing (configured in `phpunit.xml`)
- Test dirs: `tests/Feature/`, `tests/Unit/`
- Write tests in `*.php` files using Pest syntax (`it()`, `test()`, etc.)

## Architecture Quick Reference

- **Entry:** `resources/js/app.tsx` → Inertia
- **Routes:** `routes/web.php` (main), `routes/settings.php` (profile/security)
- **Backend controllers:** `app/Http/Controllers/` (namespace per role: `Siswa/`, `Kaprog/`, `GuruPembimbing/`, `Hubin/`)
- **Frontend pages:** `resources/js/pages/` (Inertia page components)
- **shadcn/ui:** `resources/js/components/ui/` — new-york style
- **Wayfinder typed routes:** `resources/js/routes/` and `resources/js/actions/` (auto-generated)
- **DB:** MySQL (**target**), SQLite default for local dev
- **CSS entry:** `resources/css/app.css` (Tailwind v4)
- **BLADE entry:** `resources/views/app.blade.php`

## Toolchain Quirks

- **pnpm** hoists `@inertiajs/core` via `pnpm-workspace.yaml`
- **React Compiler** babel plugin active (`vite.config.ts`)
- **Wayfinder** (`@laravel/vite-plugin-wayfinder`) generates typed route imports — after adding routes, check `resources/js/routes/` for new stubs
- **Prettier** uses 4-space tabs, `prettier-plugin-tailwindcss` for class sorting
- **Vite** input: `resources/css/app.css` + `resources/js/app.tsx`
- **Laravel Boost** (`boost.json`) enabled for OpenCode agents

## Key Conventions

- **NO HTML `<form>` tags** — use Inertia `useForm` or `router.post/put/patch`
- **All code & comments in Bahasa Indonesia**
- **Policy-based scoping** — `$this->authorize(...)` in controllers, NOT `if ($user->hasRole(...))` (TRD §2)
- **`Inertia::share`** must use `fn()` closure (fresh per request) (TRD §2)
- **NO `box-shadow`** — card elevation via `--color-border` + `--color-surface` (DESIGN.md §6)
- **Icon set:** `lucide-react` only, not Tabler (DESIGN.md §4)
- **Role derivation** — automatic via `GuruProfileObserver::saved()`, syncs from `guru_profiles.jabatan` (TRD §5)
- **Status state machine:** `diajukan` → `disetujui` → `resmi_pkl` → `selesai_pkl` (back edge: `ditolak`) (PRD §5, TRD §6)
- **3 conditional gates:** (1) Upload Surat Balasan locked until Surat Pengantar exists — BR20 (PRD §9) (2) Penilaian locked until Laporan Akhir uploaded — BR32 (3) Kunjungan locked until Surat Tugas aktif — BR22
- **Navigasi:** Siswa = mobile-first (bottom nav + hamburger + FAB); staf = sidebar desktop (DESIGN.md §6)

## Critical Business Rules (code-impacting)

| # | Rule |
|---|---|
| BR1 | One active pengajuan per student at a time |
| BR4 | Approve/reject ONLY from detail page, not list |
| BR6 | Every status change logs to `pengajuan_pkl_histories` |
| BR7 | Resmi PKL auto-triggered on Surat Balasan verify (no manual button) |
| BR10 | Guru Pembimbing assignment is EXCLUSIVE to Kaprog |
| BR11 | One active pembimbing per student; old record archived on replacement |
| BR13 | Selesai PKL auto-triggered when Nilai Akhir submitted |
| BR17 | Surat number is sequential per type per period (use `lockForUpdate()`) |
| BR20 | Surat Balasan upload locked until Surat Pengantar published |
| BR22 | Kunjungan requires active Surat Tugas Monitoring |
| BR31 | Hubin "Nonaktifkan Kaprog" auto-changes role to `guru_pembimbing` via Observer |
| BR32 | Penilaian locked until Laporan Akhir uploaded |

## Missing Packages (PRD-listed, NOT YET installed)

These need `composer require` when implementing:
- `spatie/laravel-permission` — RBAC
- `barryvdh/laravel-dompdf` — PDF generation
- `maatwebsite/excel` — Excel import/export

## Role Derivation Observer

```php
// app/Observers/GuruProfileObserver.php
public function saved(GuruProfile $profile): void
{
    $roleMap = [
        'Kaprog' => 'kaprog',
        'Guru Pembimbing' => 'guru_pembimbing',
        'Staf Hubin' => 'hubin',
    ];
    if (isset($roleMap[$profile->jabatan])) {
        $profile->user->syncRoles([$roleMap[$profile->jabatan]]);
    }
}
```

## Detailed References

| File | What's inside |
|---|---|
| `DESIGN.md` | Design system: colors, typography, shadcn components, icons, layout |
| `docs/PRD.md` | Full product reqs, 32 BRs, RBAC matrix, use cases |
| `docs/TRD.md` | Tech specs, full DB schema (19 tables), architecture, role derivation |
| `docs/milestone.md` | Timeline M1–M7, per-milestone deliverables |
| `docs/panduan/m*.md` | Per-milestone implementation guides (step-by-step) |

## Repo Skills (in `.opencode/skills/`)

- `frontend-design` — UI layout, visual design decisions
- `shadcn` — shadcn/ui component management (add/configure shadcn components)

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA_REACT) - v3
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
