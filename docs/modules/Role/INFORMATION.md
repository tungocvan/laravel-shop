# Modules/Role - Information

Updated: 2026-08-14

## Purpose

`Modules/Role` manages administrator roles and permissions using `spatie/laravel-permission`.

## Features

- List, search and paginate roles.
- Create/edit roles.
- Assign permissions.
- Delete unused non-protected roles.
- Generate permission records for named module/action combinations.
- Import/export role configuration through the shared import/export foundation.

## Routes

Canonical web routes:

- `GET /admin/roles` -> `admin.role.index`
- `GET /admin/roles/create` -> `admin.role.create`
- `GET /admin/roles/{id}/edit` -> `admin.role.edit`

Legacy redirects exist from `/admin/role...`.

API placeholder:

- `GET /api/role`

## Permissions

Declared in `Modules/Role/config/module.php`:

- `view_role`
- `create_role`
- `edit_role`
- `delete_role`
- `import_role`
- `export_role`

Important limitation: current Role web routes and Livewire CRUD mutations do not consistently enforce these capabilities.

## Controllers

- `Modules\Role\Http\Controllers\RoleController`
- `Modules\Role\Http\Controllers\Api\RoleController`

## Livewire Components

- `Modules\Role\Livewire\RoleTable` -> `role.role-table`
- `Modules\Role\Livewire\RoleForm` -> `role.role-form`

## Blade Views

Page views live under:

- `Modules/Role/resources/views/pages/roles/`

Livewire views:

- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/resources/views/livewire/role-form.blade.php`

## Services

- `Modules\Role\Services\ImportExport`

The service extends:

- `Modules\Shared\Services\ImportExport\BaseImportExportService`

## Imports / Exports

Role import/export uses FastExcel through the shared import/export architecture.

Current import behavior:

- supports dry-run,
- does not support replace mode,
- uses `(name, guard_name)` duplicate identity,
- validates rows,
- wraps non-dry-run writes in a transaction,
- protects `Super Admin` from non-Super-Admin actors,
- can currently create permission records named by imported data.

## Models

Active persistence uses Spatie models directly:

- `Spatie\Permission\Models\Role`
- `Spatie\Permission\Models\Permission`

No module-local Role model is required by the inspected workflows.

## Database Tables

Primary Spatie tables:

- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

Role migration directory also contains a migration for `module_migrations`; its canonical ownership should be reviewed.

## Relationships

Spatie relationships used by current code include:

- Role -> permissions
- Role -> users/model role assignments

`RoleTable` uses `withCount('users')` to protect in-use roles from deletion.

## Shared / Cross-Module Dependencies

Manifest declares:

- `User`

Observed dependencies include:

- `Modules/Shared/Services/ImportExport`
- `App/Modules/ModulePermissionManager`
- `App/Models/User`
- `spatie/laravel-permission`

## Events / Jobs

No Role-specific event/job workflow was proven in this analysis.

## Configuration / Environment Variables

Manifest:

- `Modules/Role/config/module.php`

Current manifest declares:

- `name = Role`
- `type = shell`
- `enabled = true`
- `depends = User`

No Role-specific environment variable was identified.

## Known Limitations

- Capability authorization is missing from core Role CRUD Livewire mutations/routes.
- `Super Admin` can still be edited/renamed through `RoleForm`.
- Permission creation/import can create capabilities outside the active module manifest catalog.
- Role save and bulk delete workflows are not fully transactional.
- Manifest type conflicts with bootstrap architectural description of Role as a support module.
- Negative-year migration filenames remain.
- Placeholder API endpoint remains.

## Maintenance Notes

Treat Role as a security-sensitive module. Any refactor should preserve existing route names, Spatie table contracts and Livewire aliases unless a compatibility migration is explicitly planned. Add denied-access and protected-role tests before broad implementation changes.