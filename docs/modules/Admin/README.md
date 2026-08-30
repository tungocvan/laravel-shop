# Admin Module

## Module Overview

`Modules/Admin` is the authenticated Admin **shell**. It owns Admin composition and presentation concerns: dashboard entry, shell navigation, layout, header, sidebar, footer, theme/design presentation, menu management, and shell profile/preferences.

It is not the canonical owner of Product, Order, Post, Category, customer/account, Chat, affiliate/marketing, or production database administration domains.

Current analysis recommendation: **Major Refactor** — preserve the working shell and migrate/remove legacy non-shell ownership incrementally.

## Registration

- Manifest: `Modules/Admin/config/module.php`
- Type: `shell`
- Declared dependencies: `Auth`, `User`, `Role`
- Registration: `Modules/ModuleServiceProvider.php`
- View namespace: `Admin::`
- Livewire prefix: `admin.`

## Main Routes

All active Admin routes use `web`, `auth:admin`, `/admin`, and capability-specific permission middleware.

Main route groups:

- `/admin` — dashboard
- `/admin/menus/*` — Admin navigation management
- `/admin/profile` — operator profile
- `/admin/layout/*` — general/header/sidebar/footer/design/navigation shell configuration
- `/admin/admin-header` — Admin header configuration

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
- Admin menu management through `MenuService`
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

## Configuration

Primary manifest:

```text
Modules/Admin/config/module.php
```

Shell layout/design/header/footer behavior is implemented through Admin shell services and persisted settings/configuration used by those services.

## Operational Notes

`Modules/Admin/Services/DatabaseService.php` remains high-risk legacy code. The current Admin database Livewire surface is disabled and aborts database actions with HTTP 403; active Admin routes do not expose database administration.

Do **not** re-enable this service without a separately approved hardened System/database-operation design covering permissions, audit, allowlists, secret handling, process execution, restore validation and recovery.

## Developer Notes

- Use `Admin::layouts.master` for Admin pages where applicable.
- Keep page Blade files as shells and Livewire focused on UI state/actions.
- Put reusable shell workflows in Admin services.
- Preserve existing route names, permission names and Livewire aliases unless a compatibility plan explicitly changes them.
- Do not add new business-domain models/services to Admin.
- Before removing legacy code, check routes, Blade/Livewire aliases, service imports, jobs/events and tests.
- For detailed current-state evidence see `ANALYSIS.md` and `INFORMATION.md`.

## Future Improvements

Next implementation/refactor work is **NOT AUTHORIZED** by this analysis alone.

When separately approved, the next planning step should build a reachability + canonical-ownership matrix for legacy Admin code, then migrate one domain family at a time while keeping the active Admin shell stable.