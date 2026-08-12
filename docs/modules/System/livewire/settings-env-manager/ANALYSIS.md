# System Livewire Analysis — Settings/EnvManager

Analysis date: 2026-08-12
Refactor status: **Implemented — pending local focused test verification.**

## Executive Summary

`EnvManager` has been refactored from an unauthorised project-root `.env.<suffix>` copier into a narrow private snapshot toolbar for the existing ENV administration page.

The component now delegates filesystem work to `EnvSnapshotService`, enforces `system.env.update`, exposes only the fixed `production` and `local` operation IDs, and no longer owns unrelated tab state.

## Current Architecture

```text
/admin/system/settings/env
→ system.settings.env-manager
→ system.env.update
→ EnvSnapshotService
→ storage/app/private/backups/env-snapshots/
```

The page/menu remains protected by `system.env.view`. Snapshot mutation is independently protected by `system.env.update`.

## Security Model

`EnvSnapshotService` owns the fixed operation registry:

- `production`
- `local`

The browser cannot supply a destination path, filename or arbitrary suffix. Unsupported operation IDs are rejected before filesystem work.

Snapshots are stored outside the project root under private storage. The service best-effort enforces directory mode `0700` and file mode `0600`, serializes creation with `Cache::lock('system:env-snapshot:create', ...)`, generates collision-resistant server-owned filenames, and keeps only the latest five matching snapshots per type.

Retention only considers the service-owned `env-<type>-*.env` pattern and does not delete unrelated files.

## Livewire Boundary

`EnvManager` now uses `AuthorizesSystemActions` and exposes one mutation:

```php
createSnapshot(string $operation, EnvSnapshotService $service)
```

It enforces `system.env.update` before service execution. Technical failures are reported server-side and the browser receives a generic error message. Snapshot contents and filesystem paths are never returned to the UI.

The old `activeTab`, `getTabsDefinition()` and `exportEnv()` logic were removed.

## UI / Integration

The component is intentionally mounted in the header of the existing `/admin/system/settings/env` page. It does not have a standalone route or Admin Menu entry.

The toolbar provides fixed Production/Local actions, confirmation, loading/disabled states, a private-secret warning and read-only behavior for users without update permission.

## Legacy Service Note

`EnvManagerService::exportToEnvironment()` remains in the repository for compatibility but is no longer used by this Livewire component. New snapshot workflows must use `EnvSnapshotService` and must not write `.env.production` / `.env.local` in the project root.

## Admin Menu

No new menu was created. Canonical entry remains:

```text
Công cụ Hệ thống
└── Quản lý ENV
    /admin/system/settings/env
    system.env.view
```

## Test Coverage

Focused test file:

`tests/Feature/System/SystemEnvSnapshotTest.php`

It locks the main source-level invariants: update authorization, fixed/private snapshot service, lock/retention/permission policy, active ENV-page mount, fixed UI actions and removal of legacy Livewire snapshot delegation.

Local runtime test verification is still required after pulling the implementation.

## Remaining Scope

Restore, download, listing and delete snapshot capabilities were intentionally not added. Any such feature should receive a separate privileged workflow analysis/refactor plan.
