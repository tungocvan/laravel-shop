# Modules/Role

## Module Overview

`Modules/Role` is the administrator authorization-management module built on `spatie/laravel-permission`.

## Registration

The module is auto-discovered by `Modules\ModuleServiceProvider`. Its current manifest remains `shell`, enabled, and dependent on `User`; manifest-type cleanup is deferred.

## Main Routes

- `/admin/roles` -> `view_role`
- `/admin/roles/create` -> `create_role`
- `/admin/roles/{id}/edit` -> `edit_role`

Legacy `/admin/role...` redirects are preserved.

## Permissions

Declared capabilities remain unchanged:

- `view_role`
- `create_role`
- `edit_role`
- `delete_role`
- `import_role`
- `export_role`

Role routes and sensitive Livewire actions now enforce capability-specific authorization. Permission-catalog mutation is restricted to `Super Admin`.

## Features

- Role search and pagination.
- Transactional role create/edit and permission sync through `Modules\Role\Services\RoleService`.
- Admin-guard-only role management.
- Protected `Super Admin` role cannot be edited or deleted through normal Role UI workflows.
- Single/bulk deletion blocks roles that are in use.
- Permission assignment is restricted to the active server-owned module catalog plus preserved historical permissions already attached to an edited role.
- FastExcel import/export through Shared Import/Export; import rejects non-admin guards and unknown/unsynced permissions.

## Dependencies

- `spatie/laravel-permission`
- `User` module (manifest)
- Shared import/export infrastructure
- `App\Modules\ModulePermissionManager`

## Operational Notes

Role is security-sensitive. Before merging, verify locally that at least one `Super Admin` account still authenticates and retains the global `Gate::before` bypass.

## Developer Notes

- Keep route and Livewire authorization fail-closed.
- Keep mutation/domain invariants in `RoleService`.
- Do not create permissions directly from browser/upload input unless they are declared by the active server-owned module catalog.
- Preserve route names, Livewire aliases and Spatie table contracts.

## Verification Status

Implementation is present on `agent/refactor-role`. GitHub-side static review is complete; local PHP/Pint/PHPUnit execution remains required before merge.

Recommended local checks:

```bash
php artisan test tests/Feature/Role/RoleRouteAuthorizationTest.php
php artisan test
./vendor/bin/pint Modules/Role tests/Feature/Role
```

## Deferred Improvements

- manifest `shell` -> `support` architecture decision
- migration filename hygiene and `module_migrations` ownership
- placeholder `/api/role` cleanup
- broader permission-matrix UI polish
- immutable protected-role key/schema if required later
