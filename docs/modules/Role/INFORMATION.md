# Modules/Role - Information

Updated: 2026-08-14

## Purpose

`Modules/Role` manages administrator roles and permissions using `spatie/laravel-permission`.

## Features

- List/search/paginate admin roles.
- Create/edit roles through `RoleService`.
- Assign permissions from the active server-owned module catalog.
- Protect `Super Admin` from normal edit/delete workflows.
- Delete unused roles singly or in bulk through transactional service methods.
- Synchronize declared module permissions from the Role UI only for `Super Admin` and only when the requested permission names are present in the active module catalog.
- Import/export through the Shared Import/Export foundation.

## Routes

- `GET /admin/roles` -> `admin.role.index` -> `view_role`
- `GET /admin/roles/create` -> `admin.role.create` -> `create_role`
- `GET /admin/roles/{id}/edit` -> `admin.role.edit` -> `edit_role`

Legacy `/admin/role...` redirects remain.

## Permissions

- `view_role`
- `create_role`
- `edit_role`
- `delete_role`
- `import_role`
- `export_role`

Sensitive Livewire mutations now re-authorize independently of route access.

## Controllers

- `Modules\Role\Http\Controllers\RoleController`
- `Modules\Role\Http\Controllers\Api\RoleController` (placeholder; cleanup deferred)

## Livewire Components

- `role.role-table`
- `role.role-form`

## Services

- `Modules\Role\Services\RoleService`
  - admin-guard role resolution/query
  - transactional create/update + permission sync
  - protected-role invariant
  - transactional single/bulk delete
  - server-approved permission validation
- `Modules\Role\Services\ImportExport`
  - Shared/FastExcel import-export
  - admin guard only
  - rejects unknown or unsynced permissions
  - no arbitrary permission creation from uploaded data

## Models / Database

Persistence uses Spatie models and tables:

- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

No schema change was introduced by this refactor.

## Dependencies

- `spatie/laravel-permission`
- `App\Modules\ModulePermissionManager`
- `Modules/Shared/Services/ImportExport`
- `User` module as currently declared by manifest

## Security Invariants

- Role UI is admin-guard only.
- Direct Livewire save/delete/bulk-delete calls require the appropriate named permission.
- Permission-catalog mutation requires `Super Admin`.
- `Super Admin` cannot be edited or deleted through normal Role UI/service workflows.
- Role assignment/import cannot create arbitrary permission rows from browser/upload values.

## Known Deferred Work

- manifest `shell` vs architectural `support` drift
- malformed historical migration filenames
- `module_migrations` ownership
- public placeholder API route
- broader UI authorization visibility/polish

## Verification

Implementation branch: `agent/refactor-role`.

Run locally before merge:

```bash
php artisan test tests/Feature/Role/RoleRouteAuthorizationTest.php
php artisan test
./vendor/bin/pint Modules/Role tests/Feature/Role
```
