# Copilot instructions for absence-payroll-laravel

## Big picture
- Laravel 12 app with Livewire Volt/Flux for the UI, Fortify for auth, Octane + FrankenPHP (Docker) for prod. Core domain: attendance ingestion + multi-shift summarization + payroll generation/slips.
- Data flow: Fingerprint devices -> /iclock endpoints -> raw `attendances` -> nightly `attendance_summaries` (Console command) -> payroll data and slip rendering.
- Volt drives most pages via Blade-backed Volt view files under `resources/views/livewire/**`, mapped with `Volt::route()` in `routes/web.php`.

## Local dev and builds
- Quick dev (PHP server, queue listener, logs, Vite) via Composer script:
  - Optional: `composer run dev` (spawns: `php artisan serve`, `php artisan queue:listen --tries=1`, `php artisan pail`, `npm run dev`). Ensure Node deps installed first.
- Frontend: Vite + Tailwind 4 (see `vite.config.js`).
- Tests: Pest configured. Use `composer test` (clears config cache then `artisan test`). `tests/Pest.php` binds `RefreshDatabase` for Feature tests.

## Runtime via Docker (Octane/FrankenPHP)
- `docker-compose.yaml` builds `build.Dockerfile` (FrankenPHP base). Supervisor starts:
  - `php artisan octane:frankenphp` (HTTP worker), and `php artisan schedule:run` (scheduler). App served on :8000 inside container, published as host :8001.
- `public/frankenphp-worker.php` boots the Octane worker.

## Routing and UI conventions (Volt)
- Volt routes in `routes/web.php` like `Volt::route('settings/profile', 'settings.profile')` map to `resources/views/livewire/settings/profile.blade.php`.
- Auth pages use Volt too (`routes/auth.php`). Fortify views set in `App\Providers\FortifyServiceProvider`.
- Admin and leader areas are guarded by custom middleware `is_admin` / `is_leader` (aliases in `bootstrap/app.php`).

## Attendance ingestion (iclock integration)
- All device routes under `Route::middleware('log.iclock')` in `routes/web.php`:
  - GET `iclock/cdata` and `getrequest` handled by `FingerprintController`. POST `iclock/cdata` ingests raw logs. CSRF exempt for `/iclock/cdata` in `bootstrap/app.php`.
- Device registration and control:
  - Unknown SN auto-created (inactive check). Per-device commands are pulled from cache key `device_command_{SN}` in `getrequest`.
- Logging: custom `iclock` channel writes to `storage/logs/iclock-*.log` (`config/logging.php`).

## Background processing and payroll
- Nightly summarization: `app:process-attendance` (`app/Console/Commands/ProcessAttendance.php`) groups raw `attendances` by NIP and date, matches to multi-shifts, computes work hours/late/overtime, writes `attendance_summaries`, then marks raw entries processed.
- Payroll: `Payroll` + `PayrollDetail` models. Slip route: `GET /payroll/{payroll}/slip` -> `PayrollController@showSlip` -> `resources/views/payroll/slip.blade.php`.
- Savings: `App\Services\EmployeeSavingService` provides atomic `deposit`/`withdraw` using DB transactions and `EmployeeSaving` relations.

## Models and relationships (selected)
- `Employee` belongsTo `User`, `Branch`; hasOne `Schedule` and `EmployeeSaving`; many-to-many `PayrollComponent` via `employee_payroll_components` with pivot `amount`. `generateNip()` builds year-prefixed sequential NIP.
- `Attendance` belongsTo `Employee` by `employee_nip -> employees.nip` (note: not the usual `employee_id`).
- `Device` belongsTo `Branch`; casts `is_active` and `last_sync_at`.

## Conventions and gotchas
- HTTPS is forced when `APP_ENV !== local` (`AppServiceProvider@boot`).
- Volt view roots are mounted from `resources/views/livewire` and `resources/views/pages` (`VoltServiceProvider`).
- Middleware aliases are defined in `bootstrap/app.php` (Laravel 12 style). If you add new route middleware, register aliases here.
- Tailwind 4 via `@tailwindcss/vite` plugin; Vite server has CORS enabled in `vite.config.js`.
- Helper autoload: `app/Helpers/Helper.php` (e.g., `compressImage`) via Composer `autoload.files`.

## When adding features
- Prefer Volt for pages: add Blade file under `resources/views/livewire/...` and a matching `Volt::route()` entry.
- For new device endpoints under `/iclock/*`, apply `log.iclock` middleware and add CSRF exemptions if POSTing raw bodies.
- For attendance logic, extend or schedule inside `app:process-attendance` or add new Console Commands and wire them via scheduler.
