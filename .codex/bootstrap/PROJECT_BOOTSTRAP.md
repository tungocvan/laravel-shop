# Project Bootstrap

This document captures repository-specific facts that an AI agent must know before changing code.

## Project Identity

- Project: INAFO Pharma Laravel application.
- Primary framework: Laravel 12.
- Runtime: PHP 8.3.
- Frontend build: Vite 7, Tailwind CSS 4, Bootstrap 5.3, AdminLTE 4 RC.
- UI runtime: Livewire 3.
- Architecture: first-party modular monolith under `Modules/`.

## composer.json Analysis

Required production packages:

- `laravel/framework:^12.0` provides the application foundation.
- `livewire/livewire:^3.6` powers interactive server-rendered UI.
- `spatie/laravel-permission:^6.23` provides roles and permissions.
- `laravel/sanctum:^4.2` supports API authentication.
- `laravel/socialite:^5.22` supports social login.
- `maatwebsite/excel:^3.1.64` and `rap2hpoutre/fast-excel:^5.7` support spreadsheet import/export.
- `barryvdh/laravel-dompdf:^3.1` and `phpoffice/phpword:^1.4` support document generation.
- `endroid/qr-code:5.0.7` supports QR code generation.
- `intervention/image-laravel:^1.5` supports image processing.
- `almasaeed2010/adminlte:4.0.0-rc.3` supports the admin interface.

Development packages:

- `laravel/pint` for formatting.
- `phpunit/phpunit` for tests.
- `laravel/pail` for log inspection.
- `laravel/sail`, `fakerphp/faker`, `mockery/mockery`, and `nunomaduro/collision`.

Composer scripts:

- `composer setup` installs dependencies, creates `.env`, generates key, runs migrations, installs npm packages, and builds assets.
- `composer dev` starts server, queue listener, log tailing, and Vite concurrently.
- `composer test` clears config and runs `php artisan test`.

## Autoload Structure

PSR-4 autoload roots:

- `App\` maps to `app/`.
- `Modules\` maps to `Modules/`.
- `Database\Factories\` maps to `database/factories/`.
- `Database\Seeders\` maps to `database/seeders/`.
- `Tests\` maps to `tests/` for development.

Any generated class under `Modules/<ModuleName>` must use the namespace `Modules\<ModuleName>`.

## Modules/ModuleServiceProvider.php Analysis

`Modules\ModuleServiceProvider` discovers and registers all modules dynamically.

Discovery:

- Scans `base_path('Modules')`.
- Reads `config/module.php` or `Config/module.php` when present.
- Falls back to inferred module type when no valid manifest exists.
- Valid module types are `shell`, `support`, and `domain`.
- Boot order is `shell`, then `support`, then `domain`.
- Fallback types: `Admin` is `shell`; `Auth`, `Role`, and `Template` are `support`; all others default to `domain`.
- Stores the discovered registry in `config('modules.registry')`.

Registration:

- Config files are merged as `<lower-module>.<file-name>`.
- `routes/web.php` is loaded directly.
- `routes/api.php` is loaded under `api` prefix with `api` middleware.
- Views are registered under both `<ModuleName>` and `<lower-module>` namespaces.
- Translations are loaded from `resources/lang`.
- Helpers under `Helpers` are required once.
- Migrations are loaded from `database/migrations` or `Database/Migrations`.
- Livewire components are registered automatically as `<lower-module>.<kebab.component.path>`.
- Blade component namespaces are registered from `View/Components` or `Http/Components`.
- Anonymous Blade components are registered from `resources/views/components`.
- Console commands are discovered under `Console` only while running in console.
- A `Gate::before` rule grants all abilities to users with the `Super Admin` role.

Architectural implication:

- Module folder structure and class namespaces must align, or auto-registration will silently skip classes that cannot autoload.
- Route, Livewire, view, migration, and component generation should follow this provider rather than relying on package-specific module tooling.

## Module Namespace Structure

Current modules:

- `Account`
- `Admin`
- `Admission`
- `Auth`
- `Category`
- `Chat`
- `Order`
- `Partner`
- `Pharma`
- `Post`
- `Product`
- `Role`
- `Shared`
- `System`
- `Template`
- `User`
- `Website`

Module namespace examples:

- `Modules\Product\Models\Product`
- `Modules\Product\Services\ProductService`
- `Modules\Product\Livewire\Products\ProductTable`
- `Modules\Product\Http\Controllers\ProductController`

## Shared Services

Shared services currently include `Modules/Shared/Services/ImportExport`:

- `BaseImportExportService`
- `Concerns/HandlesExportStorage`
- `Concerns/HandlesHeaderMapping`
- `Concerns/HandlesImportReport`
- `Concerns/NormalizesImportRows`

Use shared services when they define a stable contract. Do not add shared code only to avoid a single local dependency.

## Shared Components

Shared Livewire and view assets include:

- `Modules/Shared/Livewire/ImportExport/Panel.php`
- `Modules/Shared/Resources/views/livewire/import-export/panel.blade.php`

The provider supports both `resources` and `Resources` casing for resource registration. Preserve the casing already used in the target module.

## Route Registration Strategy

- Module web routes live in `Modules/<ModuleName>/routes/web.php`.
- Module API routes live in `Modules/<ModuleName>/routes/api.php`.
- API route files are wrapped with `Route::prefix('api')->middleware('api')`.
- Web route files must declare their own middleware, prefixes, names, and controllers.
- Route names should identify the owning module and capability.
- Privileged routes must use explicit permission middleware or policy checks in addition to authentication.

## Storage Structure

Expected storage patterns:

- Use Laravel Storage disks instead of direct file path manipulation.
- Use private storage for imports, exports, reports, PDFs, generated documents, and backups containing sensitive data.
- Public storage should be limited to assets intended for public access.
- Any generated file path must be server-controlled.
- Temporary import/export files need a cleanup or retention policy.

## Asset Structure

Frontend dependencies from `package.json`:

- Bootstrap 5.3
- AdminLTE 4 RC
- Font Awesome 7
- Tailwind CSS 4
- Vite 7
- Laravel Vite Plugin 2
- Axios
- Laravel Echo
- Socket.IO client

Asset rules:

- Avoid mixing AdminLTE 3 or Bootstrap 4 APIs into new UI.
- Use the current installed stack unless a roadmap task explicitly changes it.
- Keep module-specific assets close to the module when the existing module pattern supports it.
- Verify any broad UI change with `npm run build`.

## Queue And Cache Strategy

Observed scripts run `php artisan queue:listen --tries=1` during development.

Recommended strategy:

- Queue long-running imports, exports, document generation, notifications, and external integrations.
- Make queued work idempotent, especially when it creates files, records, orders, invoices, or reports.
- Track progress for user-facing long-running operations.
- Cache stable catalogs, menus, settings, permissions, and homepage data only with explicit invalidation.
- Do not cache permission decisions in a way that survives role changes unless invalidation is guaranteed.

## Roadmap Alignment

The repository roadmap prioritizes:

- P0 security containment for privileged system and database operations.
- Explicit capability-level authorization.
- Removal of arbitrary command execution from web requests.
- Safer backup, restore, import, export, and file handling.
- Canonical module ownership and dependency rules.
- Unified import/export architecture.
- Better tests, CI, validation, migration hygiene, and performance profiling.

Any AI task should preserve or advance these priorities.
