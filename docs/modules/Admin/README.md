# Admin Module

## Module Overview

`Modules/Admin` is the authenticated Admin **shell**. It owns Admin composition and presentation concerns: dashboard entry, shell navigation, layout, header, sidebar, footer, theme/design presentation, menu management, and shell profile/preferences.

It is not the canonical owner of Product, Order, Post, Category, customer/account, Chat, affiliate/marketing, or production database administration domains.

Current recommendation: **Major Refactor** — preserve the working shell and migrate/remove legacy non-shell ownership incrementally.

The approved ownership/reachability baseline is documented in `OWNERSHIP_BASELINE.md`.

## Registration

- Manifest: `Modules/Admin/config/module.php`
- Type: `shell`
- Declared dependencies: `Auth`, `User`, `Role`
- Registration: `Modules/ModuleServiceProvider.php`
- View namespace: `Admin::`
- Livewire prefix: `admin.`

## Canonical Admin Shell

The canonical shell includes:

- `/admin` — dashboard entry/composition
- `/admin/menus/*` — **sidebar/navigation configuration workspace**
- `/admin/profile` — shell profile/preferences UI
- `/admin/layout/*` — general/header/sidebar/footer/design/navigation configuration
- `/admin/admin-header` — Admin header configuration
- Admin shell presentation/layout/header/sidebar/footer/theme services

`/admin/menus` is intentionally canonical Admin ownership. `AdminMenu`, `MenuService`, and `MenuImportExportService` manage Admin sidebar/navigation metadata. A menu link to Product, Order, System, or another module does not transfer ownership of that target domain to Admin.

`Modules/Admin/routes/api.php` is intentionally empty.

## Permissions

Active shell capabilities are based on:

- `admin.dashboard.view`
- `admin.menu.*`
- `admin.profile.*`
- `admin.theme.*`
- `admin.layout.*`
- `admin.header.*`

Do not use menu visibility as a replacement for server-side authorization.

## Features

Current shell architecture includes:

- Admin layout and presentation services
- configurable header/sidebar/footer/design surfaces
- Admin sidebar/navigation management through `MenuService`
- menu JSON import/export through `MenuImportExportService`
- shell profile behavior
- focused Admin contract tests

The module still contains legacy controllers, Livewire components, services, models, imports/exports and views for non-shell domains. Verify reachability before migration or deletion.

## Dependencies

Expected dependencies:

- `Auth`, `User`, `Role`
- Spatie Permission
- Laravel cache/storage/database
- `Modules/Shared` infrastructure when a stable shared contract applies

Business-domain dependencies should point toward the canonical domain owner rather than duplicating ownership in Admin.

## Operational Notes

`Modules/Admin/Services/DatabaseService.php` remains high-risk legacy code. The current Admin database Livewire surface is disabled and aborts database actions with HTTP 403; active Admin routes do not expose database administration.

Do **not** re-enable this service without a separately approved hardened System/database-operation design covering permissions, audit, allowlists, secret handling, process execution, restore validation and recovery.

The current refactor baseline adds tests to keep this dangerous capability isolated; those tests do not make the service production-safe.

## Developer Notes

- Use `Admin::layouts.master` for Admin pages where applicable.
- Keep page Blade files as shells and Livewire focused on UI state/actions.
- Keep `/admin/menus` and sidebar/navigation management in Admin shell ownership.
- Put reusable shell workflows in Admin services.
- Preserve existing route names, permission names and Livewire aliases unless a compatibility plan explicitly changes them.
- Do not add new business-domain models/services to Admin.
- Before removing legacy code, check routes, Blade/Livewire aliases, service imports, jobs/events, tests, production schema usage and compatibility dependencies.
- For current-state evidence see `ANALYSIS.md`, `INFORMATION.md`, and `OWNERSHIP_BASELINE.md`.

## Major Refactor Sequence

The first approved implementation slice is the ownership/reachability baseline plus P0 database isolation and canonical shell guardrails. Later slices must migrate one verified legacy domain family at a time; bulk deletion and schema migration are not authorized by this baseline.
