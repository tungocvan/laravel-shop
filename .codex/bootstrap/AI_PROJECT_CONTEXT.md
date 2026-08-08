# AI Project Context

This is the compact context pack for AI agents working in the INAFO Pharma Laravel repository.

## Project Stack

- Laravel 12
- PHP 8.3
- Livewire 3
- Modular monolith under `Modules/`
- Bootstrap 5.3
- AdminLTE 4 RC
- Tailwind CSS 4
- Vite 7
- PHPUnit 11
- Laravel Pint

## Key Packages

- Authorization and roles: `spatie/laravel-permission`
- API auth: `laravel/sanctum`
- Social login: `laravel/socialite`
- Excel import/export: `maatwebsite/excel`, `rap2hpoutre/fast-excel`
- PDF generation: `barryvdh/laravel-dompdf`
- Word document generation: `phpoffice/phpword`
- QR codes: `endroid/qr-code`
- Image handling: `intervention/image-laravel`
- Admin UI: `almasaeed2010/adminlte` and npm `admin-lte`

## Coding Style

- Keep code close to existing module patterns.
- Use Laravel conventions and typed PHP.
- Keep controllers thin.
- Keep Livewire focused on UI state and interaction.
- Keep business workflows in services.
- Prefer explicit validation and authorization over implicit trust.
- Use transactions for multi-record writes.
- Avoid broad refactors unless the task and module docs require them.

## Architecture Decisions

- `Modules\ModuleServiceProvider` is the source of truth for module boot behavior.
- Modules are discovered from folders under `Modules/`.
- Module manifests may define `type` and `enabled`.
- Routes, views, migrations, Livewire components, Blade components, translations, helpers, and console commands are registered dynamically.
- `Admin` is a shell module and should not become the canonical owner of business domains.
- `Shared` is for stable reusable infrastructure, especially import/export services and shared UI.
- Canonical domain ownership should move toward modules such as `Product`, `Order`, `Post`, `Category`, `Account`, `Admission`, and `Pharma`.

## Reusable Components

- Shared import/export panel:
  - Livewire: `Modules\Shared\Livewire\ImportExport\Panel`
  - View: `Modules/Shared/Resources/views/livewire/import-export/panel.blade.php`
- Module Blade namespaces:
  - `<ModuleName>::...`
  - `<lower-module>::...`
- Livewire aliases:
  - `<lower-module>.<kebab.path>` 

## Reusable Traits And Concerns

Current shared import/export concerns:

- `Modules\Shared\Services\ImportExport\Concerns\HandlesExportStorage`
- `Modules\Shared\Services\ImportExport\Concerns\HandlesHeaderMapping`
- `Modules\Shared\Services\ImportExport\Concerns\HandlesImportReport`
- `Modules\Shared\Services\ImportExport\Concerns\NormalizesImportRows`

When building import/export features, inspect these concerns before creating new mapping, normalization, report, or storage logic.

## Reusable Services

Important shared service:

- `Modules\Shared\Services\ImportExport\BaseImportExportService`

Representative module services exist throughout:

- `Modules\Admin\Services`
- `Modules\Account\Services`
- `Modules\Post\Services`
- `Modules\Product\Services`
- `Modules\Order\Services`
- `Modules\Pharma\Services`

Prefer extending or adapting local services over duplicating query and workflow logic inside Livewire components.

## AI Working Rules

- Always read `.codex/bootstrap/CODEX_BOOTSTRAP.md`.
- Always read `.codex/bootstrap/PROJECT_BOOTSTRAP.md`.
- Always read `.codex/bootstrap/AI_PROJECT_CONTEXT.md`.
- Always read `ROADMAP.md`.
- For module work, read the module docs under `docs/modules/<ModuleName>/` when present.
- For code changes, read the existing module routes, controllers, pages, Livewire classes, views, services, imports, exports, models, migrations, and database table assumptions.
- Document analysis before risky code changes.
- Never modify unrelated modules.
- Keep tasks idempotent.
