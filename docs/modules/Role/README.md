# Modules/Role

## Module Overview

`Modules/Role` is the administrator authorization-management module built on `spatie/laravel-permission`. It provides role listing, role create/edit, permission assignment, protected deletion rules, permission generation and import/export.

## Registration

The module is auto-discovered by `Modules\ModuleServiceProvider`.

Manifest:

- `Modules/Role/config/module.php`

Current runtime manifest declares Role as `shell`, enabled, and dependent on `User`.

## Main Routes

- `/admin/roles`
- `/admin/roles/create`
- `/admin/roles/{id}/edit`

Legacy `/admin/role...` URLs redirect to the plural route set.

## Permissions

Declared capabilities:

- `view_role`
- `create_role`
- `edit_role`
- `delete_role`
- `import_role`
- `export_role`

Current source still needs capability enforcement on Role CRUD routes/Livewire actions.

## Features

- Role search and pagination.
- Role creation/editing.
- Permission assignment from the active module permission catalog.
- Single/bulk delete protection for `Super Admin` and roles in use.
- Module-style permission creation.
- FastExcel import/export through the Shared import/export service.

## Dependencies

- `spatie/laravel-permission`
- `User` module (manifest)
- Shared import/export infrastructure
- `App\Modules\ModulePermissionManager`

## Configuration

`Modules/Role/config/module.php` defines module type, enabled state, dependencies and permission capabilities.

No Role-specific environment variables were identified.

## Operational Notes

Role is security-sensitive. Changes to role names, permission catalogs, guards or Spatie tables can affect global authorization behavior.

`Super Admin` is especially important because `Modules\ModuleServiceProvider` grants it a global `Gate::before` bypass.

## Developer Notes

- Keep controllers thin.
- Authorize every sensitive Livewire mutation.
- Keep role mutation workflows in a service rather than Livewire.
- Preserve Spatie table/route/Livewire compatibility during refactors.
- Use Shared import/export infrastructure rather than creating a second engine.
- Do not allow uploaded/browser-provided permission names to create arbitrary capabilities without a server-side allowlist.

See `ANALYSIS.md` for the current technical assessment.

## Future Improvements

Current analysis recommends a **Major Refactor**, focused on:

1. capability authorization,
2. protected-role invariants,
3. canonical Role service,
4. server-approved permission catalog,
5. import guard/catalog hardening,
6. transactional writes,
7. targeted security/regression tests,
8. manifest/dependency/migration hygiene.