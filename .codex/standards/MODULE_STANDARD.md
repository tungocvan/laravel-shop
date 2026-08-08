# MODULE STANDARD

Canonical engineering standard for creating, refactoring, and rebuilding Laravel modules in this repository.

Derived from the project master module guidance, reconciled with the actual repository architecture.

## Priority

When instructions conflict, use this order:

1. Current repository reality and source code.
2. `.codex/bootstrap/*` and `ROADMAP.md`.
3. This standard.
4. Module documentation under `docs/modules/<ModuleName>/`.
5. Task-specific implementation plan/spec.

Never introduce an architecture merely because a generic/master prompt mentions it when the repository uses another canonical implementation.

## Core Stack

- Laravel 12.
- Livewire 3.1 class-based components.
- Modular monolith under `Modules/`.
- Module discovery/registration follows the repository's existing `Modules\ModuleServiceProvider` infrastructure.
- MySQL.
- Admin routes use the repository's existing admin authentication and authorization conventions.

Do not introduce `nwidart/laravel-modules`, `module.json`, or module-specific providers unless the repository infrastructure explicitly requires them.

## Module Boundary

Business/domain code belongs inside:

`Modules/<ModuleName>/`

Do not move module business logic into `app/Models`, `app/Services`, or unrelated modules.

Cross-module dependencies must be explicit and point to the canonical domain owner. Shared infrastructure belongs in `Modules/Shared` only when genuinely cross-cutting.

## Architecture Flow

Preferred request/UI flow:

`Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Service -> Model -> Database`

For workflows that use import/export or shared infrastructure, insert those layers explicitly without bypassing domain services.

## Responsibilities

### Route

Routes define URL, name, middleware, controller action and authorization boundary. Admin mutations must not rely only on `auth:admin`; use capability-specific authorization where the repository supports it.

### Controller

Keep controllers thin. They may return views, redirect, and pass simple parameters. Do not place domain queries, transactions, or business workflows in controllers.

### Page Blade

Page Blade is a layout/shell. Do not query the database, call models/services, or implement business rules in Blade.

### Livewire

Livewire owns UI state, validation, UI actions and calls services. Do not place domain transactions or complex business logic in Livewire. Sensitive mutations must enforce authorization at the action boundary.

### Service

Service is the primary domain/workflow layer. It owns queries, filtering, pagination, create/update/delete workflows, transactions, normalization, domain calculations, import/export orchestration and concurrency controls where needed.

Avoid unbounded `get()` on user-controlled or potentially large datasets. Prefer pagination, chunking, cursor/lazy iteration or bounded exports.

### Model

Models define persistence concerns, fillable/casts, relationships, scopes and simple model semantics. Avoid large workflow/business orchestration in models.

## Database

- Use appropriate data types; never use float for money.
- Add indexes for real search/filter/sort patterns.
- Use foreign keys/constraints where appropriate.
- Use nullable/default values intentionally.
- Add concise comments to important or ambiguous business columns when supported.
- Preserve migration history; do not rewrite applied migrations unless the project explicitly permits it.
- Treat destructive schema changes as high risk and require migration/rollback planning.

## Transactions and Concurrency

Use transactions for multi-step writes that must succeed atomically. For high-risk concurrent workflows, evaluate row locking, optimistic versioning, idempotency, unique constraints and duplicate prevention.

## Security

Review authorization, validation, mass assignment, uploads/downloads, private storage, path traversal, token exposure, sensitive logs, queued jobs and public endpoints. Prefer private storage and controlled download responses for sensitive files.

## Import / Export

When spreadsheet import/export exists, reuse the repository's canonical shared import/export infrastructure instead of creating competing engines. Keep business validation and persistence rules in the owning module service.

## Compatibility

Refactors and rebuilds must explicitly evaluate compatibility for routes, route names, permissions, Livewire aliases, database tables/columns, storage paths, public views, imports/exports and external contracts.

Do not silently rename or remove public contracts.

## Quality Gate

Before considering implementation complete:

- Architecture follows repository conventions.
- Authorization exists at sensitive mutation boundaries.
- Business logic is in services.
- Transactions/concurrency are appropriate.
- Queries are bounded and production-safe.
- Tests cover changed critical behavior.
- Documentation reflects implemented reality.
- Formatting and targeted verification pass.
