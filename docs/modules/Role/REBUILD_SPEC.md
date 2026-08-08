# Modules/Role - Rebuild Specification

Status: **Implementation source of truth**

Version: **1.0**

Generated: 2026-06-15

Source documents:

- `docs/modules/Role/ANALYSIS.md`
- `docs/modules/Role/REFACTOR_PLAN.md`
- `ROADMAP.md`

Target platform:

- Laravel 12
- Livewire 3
- PHP 8.3
- Spatie Laravel Permission 6
- Bootstrap 5.3
- AdminLTE 4

This specification defines the target architecture and behavior for rebuilding `Modules/Role`. When current Role code conflicts with this document, this document takes precedence unless amended through an explicit architecture decision.

No implementation code is included here.

## Scope

`Modules/Role` owns administrative authorization configuration:

- List and search admin-guard roles.
- Create and update ordinary roles.
- Assign approved permissions to ordinary roles.
- Delete one or more ordinary roles.
- Discover the canonical permission catalog from enabled module manifests.
- Seed and reconcile permission definitions.
- Export role configuration through a versioned JSON document.
- Validate and import role configuration through a restricted, auditable workflow.
- Protect system roles from rename, permission reduction, import overwrite, and deletion.
- Record security-sensitive authorization changes.

`Modules/Role` does not own:

- User authentication or session eligibility.
- User/account CRUD.
- Assignment of roles to individual users.
- Admin layout or navigation shell.
- Runtime creation of arbitrary permission namespaces.
- Multi-guard role administration.
- A public Role API.
- Platform module-migration tracking.

## Binding Architecture Decisions

| Decision | Specification |
|---|---|
| Module ownership | `Modules/Role` is a `support` module and the canonical owner of role and permission administration. |
| Admin ownership | `Modules/Admin` may provide layout, menu, and navigation but must not duplicate Role Livewire, services, models, imports, or exports. |
| Supported guard | Version 1 supports `guard_name = admin` only. |
| Canonical Role model | `Modules\Role\Models\Role` extends Spatie's Role model and is configured in `config/permission.php`. |
| Canonical Permission model | `Modules\Role\Models\Permission` extends Spatie's Permission model and is configured in `config/permission.php`. |
| Role identity | Persistent identity is role primary key plus `guard_name`; name is mutable only for ordinary roles. |
| Protected roles | Protected role keys are server-owned configuration. Protection never depends only on display name. |
| Initial protected role | `super_admin` is protected and seeded with display name `Super Admin`. |
| Protected-role mutation | Protected roles cannot be renamed, deleted, bulk-deleted, imported, or have permissions reduced through the standard UI. |
| Permission definitions | Enabled module manifests are the canonical permission catalog. Runtime arbitrary permission creation is not supported. |
| Permission naming | Permission names are stable snake_case capability keys; grouping uses manifest metadata, not underscore parsing. |
| API | `Modules/Role/routes/api.php` exposes no public route in version 1. |
| Import format | Versioned UTF-8 JSON only, maximum 2 MiB by default. |
| Import behavior | Import updates or creates ordinary roles only; it never creates permissions and never accepts non-admin guards. |
| Export behavior | Export contains ordinary and protected role snapshots, but protected roles are marked read-only and ignored by import. |
| Import execution | Dry run is mandatory before apply. Apply requires a fresh matching dry-run token/hash. |
| Destructive actions | Single and bulk deletes are transactional, authorized, protected-role aware, and audited. |
| Interactive page size | Allowed values are 10, 25, 50, and 100. `All` is not supported. |
| Select all | Select-all applies to the current page only in version 1. |
| UI stack | Bootstrap 5.3 and AdminLTE 4 conventions are required; Tailwind-only utilities and emoji semantics are not part of the target. |
| Migration history | Existing production migration records must not be broken by blindly renaming migration files. |

## Target Module Structure

```text
Modules/Role/
├── Actions/
│   ├── CreateRole.php
│   ├── UpdateRole.php
│   ├── DeleteRole.php
│   ├── BulkDeleteRoles.php
│   └── ApplyRoleImport.php
├── Data/
│   ├── RoleData.php
│   ├── RoleFilters.php
│   ├── RoleImportDocument.php
│   ├── RoleImportRow.php
│   ├── RoleImportOptions.php
│   ├── RoleImportReport.php
│   └── RoleExportOptions.php
├── Enums/
│   ├── RoleAuditAction.php
│   ├── RoleImportStatus.php
│   └── RoleImportMode.php
├── Exceptions/
│   ├── ProtectedRoleException.php
│   ├── RoleConflictException.php
│   └── RoleImportException.php
├── Http/Controllers/
│   └── RoleController.php
├── Livewire/
│   ├── RoleForm.php
│   ├── RoleTable.php
│   └── RoleImportPanel.php
├── Models/
│   ├── Role.php
│   ├── Permission.php
│   ├── RoleAudit.php
│   └── RoleImport.php
├── Policies/
│   └── RolePolicy.php
├── Queries/
│   └── RoleQuery.php
├── Services/
│   ├── RoleService.php
│   ├── PermissionCatalogService.php
│   └── RoleConfigurationImportExportService.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── RolesAndPermissionsSeeder.php
├── resources/views/
│   ├── livewire/
│   │   ├── role-form.blade.php
│   │   ├── role-table.blade.php
│   │   └── role-import-panel.blade.php
│   └── pages/roles/
│       ├── index.blade.php
│       ├── create.blade.php
│       └── edit.blade.php
├── config/
│   ├── module.php
│   └── role.php
└── routes/
    └── web.php
```

The following current artifacts must not exist in the target state:

- Duplicate `Modules/Admin/Livewire/System/RoleTable.php`.
- Duplicate `Modules/Admin/resources/views/livewire/system/role-table.blade.php`.
- Duplicate root `database/seeders/RolesAndPermissionsSeeder.php`.
- Placeholder `Modules/Role/Http/Controllers/Api/RoleController.php`.
- Public placeholder route in `Modules/Role/routes/api.php`.
- Plain, unconfigured `Modules/Role/Models/Role.php`.
- Role-owned `module_migrations` infrastructure table.
- Runtime “create module permissions” UI that accepts arbitrary names.

## Module Boundaries

```text
Role Routes / Controllers
    -> Role Livewire
        -> Role Actions / Services / Queries
            -> Role Policy / DTOs / Permission Catalog
                -> Configured Spatie-compatible Role and Permission Models
                    -> Spatie Tables / Audit Tables / Private Storage

Role Import / Export
    -> Shared import/export report and storage contracts
    -> Role-specific JSON parser and validator

Admin
    -> Links to Role routes and renders shared layout
    -> Does not own Role domain behavior
```

Rules:

- Blade must not execute Eloquent queries.
- Livewire must not contain business transactions.
- Controllers remain page adapters.
- Services must not return Blade views or Livewire responses.
- The Role module must not mutate users or assign roles to users.
- The Account/User modules may consume the configured Role model through a contract but may not create permission definitions.
- Permission catalog discovery is centralized in `PermissionCatalogService`.
- Every write operation accepts an authenticated actor context and produces an audit record.

# 1. Database Design

## 1.1 Existing Spatie Authorization Tables

The following tables remain canonical:

- `roles`
- `permissions`
- `role_has_permissions`
- `model_has_roles`
- `model_has_permissions`

Their schema must match the installed Spatie Laravel Permission version and `config/permission.php`.

### `roles`

| Column | Type | Null | Constraint |
|---|---|---:|---|
| `id` | bigint unsigned | No | Primary key |
| `name` | varchar(125) | No | Unique with `guard_name` |
| `guard_name` | varchar(50) | No | Version 1 Role UI supports `admin` only |
| timestamps | timestamps | No | Standard Laravel timestamps |

Required indexes:

- Unique `name`, `guard_name`.
- Index `guard_name`, `name` if not covered adequately by the unique index for measured list queries.

Rules:

- Names are trimmed and normalized for duplicate comparison.
- Name length is limited to 125 at application and database boundaries.
- Protected status is not inferred from `name`.
- Version 1 does not add soft deletes to Spatie roles.
- Ordinary deletion removes role-permission and model-role pivots through existing foreign-key behavior.

### `permissions`

| Column | Type | Null | Constraint |
|---|---|---:|---|
| `id` | bigint unsigned | No | Primary key |
| `name` | varchar(125) | No | Unique with `guard_name` |
| `guard_name` | varchar(50) | No | `admin` for module-declared permissions |
| timestamps | timestamps | No | Standard Laravel timestamps |

Rules:

- Permission names come from enabled module manifests.
- The standard UI and import cannot create undeclared permissions.
- Removing a permission from a manifest does not immediately delete it. Reconciliation first reports assignments and requires a separate controlled cleanup decision.

### Pivot Tables

`role_has_permissions`:

- Composite primary key: `permission_id`, `role_id`.
- Foreign keys cascade on role or permission deletion.

`model_has_roles`:

- Composite primary key: `role_id`, `model_id`, `model_type`.
- Index on `model_id`, `model_type`.

`model_has_permissions`:

- Composite primary key: `permission_id`, `model_id`, `model_type`.
- Index on `model_id`, `model_type`.

Rules:

- The Role module does not manually insert or delete model assignment pivots.
- User role assignment remains owned by the account/user workflow.
- Role deletion is prohibited for protected roles.
- Deleting an ordinary role with assigned users requires explicit confirmation displaying assignment count.

## 1.2 Protected Role Configuration

Protected roles are configured in:

- `Modules/Role/config/role.php`

Required configuration:

| Key | Purpose |
|---|---|
| `guard` | Fixed value `admin` |
| `protected_roles` | Stable map of protected keys to expected role names |
| `import.max_bytes` | Default 2 MiB |
| `import.max_roles` | Default 500 |
| `import.max_permissions_per_role` | Default 500 |
| `import.dry_run_ttl_minutes` | Default 15 |
| `export.retention_hours` | Default 24 |

Initial protected role:

| Key | Display Name | Rules |
|---|---|---|
| `super_admin` | `Super Admin` | Cannot be renamed, deleted, imported, or have permissions reduced through standard workflows |

Protected role resolution:

- Seeder creates or resolves the protected role for the `admin` guard.
- The resolved database ID is cached through a server-side resolver.
- Policy and services use the protected-role resolver.
- Display-name comparison alone is prohibited.

## 1.3 `role_audits`

Model:

- `Modules\Role\Models\RoleAudit`

Purpose:

- Immutable audit trail for security-sensitive role and permission changes.

| Column | Type | Null | Purpose |
|---|---|---:|---|
| `id` | bigint unsigned | No | Primary key |
| `uuid` | uuid | No | Public-safe audit identifier, unique |
| `actor_id` | bigint unsigned | Yes | User who initiated the action |
| `action` | varchar(50) | No | `RoleAuditAction` enum |
| `role_id` | bigint unsigned | Yes | Target role; nullable after deletion |
| `role_name` | varchar(125) | Yes | Snapshot |
| `guard_name` | varchar(50) | No | Expected `admin` |
| `before` | json | Yes | Redacted pre-change snapshot |
| `after` | json | Yes | Redacted post-change snapshot |
| `metadata` | json | Yes | Request ID, import ID, counts, source |
| `ip_hash` | varchar(64) | Yes | Optional privacy-preserving correlation |
| `created_at` | timestamp | No | Immutable event time |

Required indexes:

- Unique `uuid`.
- Index `actor_id`, `created_at`.
- Index `role_id`, `created_at`.
- Index `action`, `created_at`.

Rules:

- No `updated_at`.
- Application code must not update or delete audit rows.
- Audit snapshots contain role name, guard, protected flag, and permission-name arrays.
- Uploaded file content, session data, and raw exception traces are never stored.
- Audit persistence occurs inside the same transaction as the authorization change.

## 1.4 `role_imports`

Model:

- `Modules\Role\Models\RoleImport`

Purpose:

- Track dry-run and apply lifecycle for restricted imports.

| Column | Type | Null | Purpose |
|---|---|---:|---|
| `id` | bigint unsigned | No | Primary key |
| `uuid` | uuid | No | Public-safe identifier, unique |
| `requested_by` | bigint unsigned | No | Actor user ID |
| `source_path` | varchar(500) | No | Private storage path |
| `original_filename` | varchar(255) | No | Display metadata |
| `file_sha256` | char(64) | No | Content identity |
| `schema_version` | varchar(20) | No | Version `1.0` |
| `mode` | varchar(30) | No | `update_or_create` in version 1 |
| `status` | varchar(30) | No | `RoleImportStatus` |
| `dry_run_token_hash` | char(64) | Yes | Server-side approval token hash |
| `dry_run_expires_at` | timestamp | Yes | Apply deadline |
| `total_roles` | unsigned integer | No | Default 0 |
| `create_count` | unsigned integer | No | Default 0 |
| `update_count` | unsigned integer | No | Default 0 |
| `unchanged_count` | unsigned integer | No | Default 0 |
| `error_count` | unsigned integer | No | Default 0 |
| `report` | json | Yes | Structured validation/conflict report |
| `started_at` | timestamp | Yes | Processing start |
| `finished_at` | timestamp | Yes | Completion time |
| `expires_at` | timestamp | No | File retention deadline |
| `failure_code` | varchar(100) | Yes | Stable internal code |
| timestamps | timestamps | No | |

Required indexes:

- Unique `uuid`.
- Index `requested_by`, `created_at`.
- Index `status`, `created_at`.
- Index `file_sha256`.
- Index `expires_at`.

Rules:

- Source files are stored on a private disk.
- Only the requester or a user with `audit_role` may view the operation.
- A successful dry run does not write `roles`, `permissions`, or pivots.
- Apply is allowed only when actor, file hash, dry-run token, schema version, and expiry still match.
- Files and detailed reports expire after the configured retention period.

## 1.5 Export Storage

Version 1 does not require a `role_exports` database table because export is small and synchronous.

Rules:

- Export is generated from a lazy/bounded query.
- The response is downloaded directly or stored temporarily on a private disk.
- Temporary export retention is at most 24 hours.
- Export activity creates a `role_audits` row with counts and document hash.
- If measured volume later requires queued export, introduce an operation table through a new specification version.

## 1.6 `module_migrations` Ownership

`Modules/Role/database/migrations/2026_04_20_104916_module_migrations.php` is not part of the target Role schema.

Requirements:

- Confirm no external module loader or deployment process uses `module_migrations`.
- If unused, retire it through a forward migration or baseline process.
- If required, move ownership to platform/module infrastructure.
- Never drop the table without production data and consumer verification.

## 1.7 Migration Strategy

Migration work must follow this order:

1. Inspect the installed Spatie version in `composer.lock`.
2. Compare existing permission schemas with the package migration contract.
3. Document current production migration records for all malformed `-0001` files.
4. Select one migration-history strategy for fresh and existing installations.
5. Configure canonical Role and Permission models.
6. Add `role_audits`.
7. Add `role_imports`.
8. Resolve or move `module_migrations` ownership.
9. Remove duplicate migration ownership.
10. Run fresh-install and upgrade smoke tests.

Migration rules:

- Do not blindly rename migrations already recorded in production.
- Historical migration files may be retained as a compatibility baseline when necessary.
- New schema changes use valid Laravel timestamps and forward migrations.
- MySQL-compatible behavior is authoritative; SQLite-only success is insufficient.
- `down()` methods must be reversible where safe.
- Audit table rollback must not silently discard production security records.
- Deployment requires backup, migration status capture, and rollback/runbook steps.

# 2. Model Design

## 2.1 Canonical `Role` Model

Target:

- `Modules/Role/Models/Role.php`

Requirements:

- Extends `Spatie\Permission\Models\Role`.
- Implements the package Role contract through inheritance.
- Is configured as `models.role` in `config/permission.php`.
- Uses the canonical `roles` table from `config/permission.php`.
- Adds no alternative role relationships that bypass Spatie.
- Exposes no mass assignment path for browser-controlled `guard_name`.

Allowed domain helpers:

- `isProtected(): bool`
- `isAdminGuard(): bool`

Rules:

- `isProtected()` delegates to the protected-role resolver/configuration.
- Business writes do not live in model events.
- Cache invalidation, auditing, import behavior, and actor authorization do not live in the model.
- List queries use `withCount('users')`; Blade must not call `users()->count()`.

## 2.2 Canonical `Permission` Model

Target:

- `Modules/Role/Models/Permission.php`

Requirements:

- Extends `Spatie\Permission\Models\Permission`.
- Is configured as `models.permission` in `config/permission.php`.
- Uses the canonical `permissions` table.
- Permission creation occurs only through `PermissionCatalogService` or canonical seeding.

Allowed domain helpers:

- `catalogMetadata(): array` or equivalent read-only catalog lookup.

Rules:

- Do not infer module ownership solely by splitting the permission name.
- Do not expose arbitrary create/update/delete permission screens in version 1.

## 2.3 `RoleAudit`

Target:

- `Modules/Role/Models/RoleAudit.php`

Responsibilities:

- Represent immutable authorization change records.
- Cast `action` to `RoleAuditAction`.
- Cast `before`, `after`, and `metadata` to arrays.
- Expose actor and optional role relationships.

Rules:

- Model updates and deletes are prohibited by application policy.
- Missing target role after deletion must not break audit display.
- Audit display uses snapshots rather than live role state.

## 2.4 `RoleImport`

Target:

- `Modules/Role/Models/RoleImport.php`

Responsibilities:

- Represent import lifecycle and report state.
- Cast status/mode enums, report JSON, and timestamps.
- Expose requester relationship.
- Determine whether a dry run is still applicable.

Rules:

- Source paths are never mass assigned from Livewire.
- Status transitions are controlled by the import/export service.
- Public UI uses `uuid`, never sequential ID.

## 2.5 Enums

Target directory:

- `Modules/Role/Enums`

Required enums:

`RoleAuditAction`:

- `ROLE_CREATED`
- `ROLE_UPDATED`
- `ROLE_DELETED`
- `ROLES_BULK_DELETED`
- `PERMISSION_CATALOG_SYNCED`
- `ROLE_IMPORT_DRY_RUN`
- `ROLE_IMPORT_APPLIED`
- `ROLE_EXPORT_DOWNLOADED`
- `ROLE_IMPORT_FAILED`

`RoleImportStatus`:

- `UPLOADED`
- `VALIDATING`
- `DRY_RUN_READY`
- `APPLYING`
- `COMPLETED`
- `FAILED`
- `EXPIRED`

`RoleImportMode`:

- `UPDATE_OR_CREATE`

## 2.6 Data Transfer Objects

Target directory:

- `Modules/Role/Data`

### `RoleFilters`

Fields:

- Search string.
- Per-page value.
- Sort field.
- Sort direction.

Rules:

- Search is trimmed and length-limited.
- Per-page is one of 10, 25, 50, 100.
- Sort fields are allowlisted.
- Guard is not browser-controlled; it is always `admin`.

### `RoleData`

Fields:

- Role name.
- Permission-name array.

Rules:

- Name is trimmed and normalized.
- Permission names are unique and sorted.
- Guard is injected by the service as `admin`.
- DTO construction does not authorize or query the database.

### `RoleImportDocument`

Fields:

- Schema version.
- Exported-at timestamp.
- Source application metadata.
- Guard.
- Role rows.

### `RoleImportRow`

Fields:

- Role name.
- Guard.
- Protected flag from source for reporting only.
- Permission-name array.

Rules:

- The source protected flag is never trusted to grant protected status.
- Protected status is resolved server-side.

### `RoleImportOptions`

Fields:

- Mode.
- Dry-run boolean.
- Expected file hash.
- Optional dry-run token for apply.

### `RoleImportReport`

Fields:

- Import UUID.
- Schema version.
- File hash.
- Total/create/update/unchanged/error counts.
- Row-level errors.
- Protected-role conflicts.
- Unknown permissions.
- Dry-run expiry.
- Apply eligibility.

### `RoleExportOptions`

Fields:

- Include protected role snapshots.
- Pretty-print flag.
- Normalized filters if export filtering is later supported.

## 2.7 Policy

Target:

- `Modules/Role/Policies/RolePolicy.php`

Required abilities:

- `viewAny`
- `view`
- `create`
- `update`
- `delete`
- `bulkDelete`
- `import`
- `export`
- `syncPermissionCatalog`
- `viewAudit`

Rules:

- Broad capability comes from named permissions.
- Record-level decisions include guard and protected-role status.
- Protected roles deny update and delete in standard workflows.
- Import and permission-catalog synchronization require dedicated permissions.
- Policy checks do not rely on role names.
- `Gate::before` Super Admin bypass may grant broad access, but protected-role invariants still remain enforced in services.

# 3. Service Design

## 3.1 Design Style

Use:

- A query object for read composition.
- Explicit actions or a cohesive service for state changes.
- A dedicated permission catalog service.
- A dedicated JSON import/export service.

Do not introduce a generic repository abstraction.

## 3.2 `RoleQuery`

Target:

- `Modules/Role/Queries/RoleQuery.php`

Required methods:

| Method | Output | Responsibility |
|---|---|---|
| `forAdminList(RoleFilters $filters)` | Builder | Admin-guard list query with user count |
| `findAdminRole(int $id)` | Role | Resolve admin role or fail |
| `findAdminRoles(array $ids)` | Collection | Resolve unique target set |
| `forExport()` | LazyCollection/Builder | Deterministic bounded export query |

List requirements:

- Always constrain `guard_name = admin`.
- Select only list columns.
- Use `withCount('users')`.
- Apply allowlisted sort fields.
- Search behavior is measured; contains-search is acceptable for small catalogs.

## 3.3 `RoleService`

Target:

- `Modules/Role/Services/RoleService.php`

Public methods:

| Method | Input | Output | Responsibility |
|---|---|---|---|
| `createRole` | Actor, `RoleData` | Role | Create ordinary admin role and synchronize permissions |
| `updateRole` | Actor, Role, `RoleData` | Role | Update ordinary role atomically |
| `deleteRole` | Actor, Role | void | Delete one ordinary role atomically |
| `bulkDeleteRoles` | Actor, role IDs | Result DTO | Validate and delete ordinary roles atomically |
| `permissionGroups` | none | array | Return scalar permission groups from catalog |
| `isProtected` | Role | bool | Resolve protected status |

Mandatory behavior:

- Re-resolve browser-supplied IDs through `RoleQuery`.
- Require `guard_name = admin`.
- Enforce protected-role invariants independently of policy.
- Validate permission names against `PermissionCatalogService`.
- Synchronize empty permission arrays correctly.
- Wrap role persistence, pivot synchronization, audit write, and cache invalidation coordination in a transaction.
- Return typed results or throw domain exceptions.
- Never assign roles to users.
- Never create undeclared permissions.

## 3.4 Explicit Actions

Target actions:

- `Modules/Role/Actions/CreateRole.php`
- `Modules/Role/Actions/UpdateRole.php`
- `Modules/Role/Actions/DeleteRole.php`
- `Modules/Role/Actions/BulkDeleteRoles.php`
- `Modules/Role/Actions/ApplyRoleImport.php`

Decision:

- Actions may be thin wrappers around domain services where they provide one use-case boundary.
- Do not duplicate invariants between actions and `RoleService`.
- Livewire may call actions; it must not call Eloquent writes directly.

## 3.5 `PermissionCatalogService`

Target:

- `Modules/Role/Services/PermissionCatalogService.php`

Public methods:

| Method | Output | Responsibility |
|---|---|---|
| `catalog()` | Collection/array | Read normalized permissions from enabled module manifests |
| `groupedCatalog()` | array | Return scalar groups for UI |
| `allowedNames()` | array | Return admin-guard permission allowlist |
| `validateNames(array $names)` | array | Return normalized valid names or throw validation exception |
| `syncCatalog(Actor $actor)` | Sync report | Create missing declared permissions and report stale ones |

Manifest permission shape:

```text
permissions:
  - name: view_role
    action: view
    resource: role
    group: role
    label: View roles
    guard: admin
    sensitive: false
```

Rules:

- Existing simple string manifests may be supported during migration through one normalizer.
- Target state uses structured metadata.
- Only enabled modules contribute permissions.
- Duplicate names with conflicting metadata fail module validation.
- `syncCatalog()` creates missing declarations but does not silently delete stale assigned permissions.
- Catalog results may be cached only with explicit invalidation when module configuration changes.

## 3.6 `RoleConfigurationImportExportService`

Target:

- `Modules/Role/Services/RoleConfigurationImportExportService.php`

Public methods:

| Method | Input | Output |
|---|---|---|
| `createExport` | Actor, `RoleExportOptions` | Download/export result |
| `uploadImport` | Actor, uploaded file metadata | RoleImport |
| `dryRun` | Actor, RoleImport | RoleImportReport |
| `apply` | Actor, RoleImport, dry-run token | RoleImportReport |
| `expire` | RoleImport | void |

Mandatory behavior:

- Store uploads privately with generated paths.
- Verify extension, MIME, byte size, UTF-8, JSON syntax, schema version, document shape, and file hash.
- Reject non-admin guards.
- Reject unknown permissions.
- Reject protected-role rows from apply.
- Validate all rows before any role write.
- Generate a short-lived dry-run token bound to actor, import UUID, file hash, and report hash.
- Re-run critical validation during apply.
- Apply all role changes in one transaction.
- Audit dry run, apply, export, and failure outcomes.
- Return safe error codes and structured reports.
- Remove expired source files and token hashes.

## 3.7 Protected Role Resolver

The protected-role resolver may be a small service or part of `RoleService`.

Required methods:

- Resolve protected keys to admin role IDs.
- Determine whether a Role is protected.
- Verify protected roles exist.
- Verify protected roles retain required permissions.

Rules:

- Protected role identity is server-owned.
- Missing protected role is an operational error surfaced by health checks.
- The standard UI cannot repair protected-role corruption; repair uses a controlled seeder/console process.

## 3.8 Domain Exceptions and Error Handling

Required exceptions:

- `ProtectedRoleException`
- `RoleConflictException`
- `RoleImportException`

Rules:

- Validation failures return field or row errors.
- Authorization failures return 403 behavior.
- Missing records return 404 behavior.
- Protected-role conflicts return a safe domain message.
- Unexpected exceptions are logged with correlation ID and return a generic message.
- Raw SQL, file paths, stack traces, and package internals are not shown to users.

# 4. Livewire Design

## 4.1 General Rules

- Every public Livewire method is treated as an HTTP endpoint.
- Every sensitive method authorizes independently.
- Public properties contain scalar, enum-backed, or array state only.
- Eloquent collections are not stored in public properties.
- Browser-provided IDs are re-resolved before use.
- Business transactions remain in services/actions.
- Validation is performed before DTO construction.
- Components dispatch UI events only after successful service completion.

## 4.2 `RoleTable`

Target:

- `Modules/Role/Livewire/RoleTable.php`

Public state:

- `search: string`
- `perPage: int`
- `sortField: string`
- `sortDirection: string`
- `selectedIds: array`
- `selectCurrentPage: bool`
- Delete confirmation state

Public methods:

- `mount()`
- `updatedSearch()`
- `updatedPerPage()`
- `updatedPage()`
- `updatedSelectCurrentPage()`
- `delete(int $roleId)`
- `bulkDelete()`
- `export()`
- `render()`

Rules:

- `mount()` authorizes `viewAny`.
- `delete()` authorizes the resolved role.
- `bulkDelete()` validates IDs, authorizes bulk delete, and delegates to service.
- `export()` requires `export_role`.
- Search, per-page, sort, and page changes reset selection.
- Select-all selects only visible current-page ordinary roles.
- Protected roles never enter `selectedIds`.
- Render uses `RoleQuery`, not direct write logic.
- Import state is not embedded in this component.

## 4.3 `RoleForm`

Target:

- `Modules/Role/Livewire/RoleForm.php`

Public state:

- Locked nullable role ID.
- `name: string`
- `selectedPermissions: array`
- Scalar permission groups.
- Read-only protected flag.

Public methods:

- `mount(?Role $role = null)`
- `save()`
- `render()`

Validation:

- Name required, string, trimmed, max 125.
- Composite uniqueness for name and `admin` guard.
- `selectedPermissions` is an array.
- Every item is a unique string in the canonical admin permission catalog.

Rules:

- Create and edit modes authorize separately.
- Edit resolves the role through route binding and re-resolves before save.
- Protected role edit is denied.
- Save builds `RoleData` and calls create/update action.
- Empty permission selection is valid for ordinary roles and clears assignments.
- Permission groups are scalar arrays from `PermissionCatalogService`.
- Success redirects to `admin.role.index`.

## 4.4 `RoleImportPanel`

Target:

- `Modules/Role/Livewire/RoleImportPanel.php`

Public state:

- Uploaded file.
- Import UUID.
- Dry-run report.
- Dry-run token held only as required for immediate apply.
- Confirmation text/state.

Public methods:

- `uploadAndValidate()`
- `runDryRun()`
- `applyImport()`
- `clear()`
- `render()`

Rules:

- `mount()` and every action require `import_role`.
- Upload validation: required file, JSON extension/content, maximum configured size.
- Apply button is unavailable until a successful dry run.
- Apply requires explicit confirmation and an unexpired dry-run token.
- File content changes invalidate the dry run.
- Protected-role conflicts and unknown permissions are blocking errors.
- The component displays structured reports and correlation/audit identifiers.
- No database role writes occur in the component.

## 4.5 Controllers and Routes

Target web routes:

| Method | URI | Name | Ability |
|---|---|---|---|
| GET | `/admin/roles` | `admin.roles.index` | `view_role` |
| GET | `/admin/roles/create` | `admin.roles.create` | `create_role` |
| GET | `/admin/roles/{role}/edit` | `admin.roles.edit` | `edit_role` plus record policy |
| GET | `/admin/roles/import` | `admin.roles.import` | `import_role` |

Requirements:

- Middleware: `web`, `auth:admin`, named permission middleware.
- Route model binding resolves only `admin`-guard roles.
- Route names use plural `admin.roles.*`.
- Controller methods have typed `View` returns.
- No Role API routes exist in version 1.

## 4.6 Livewire Aliases

Canonical aliases:

- `role.role-table`
- `role.role-form`
- `role.role-import-panel`

Only `Modules/Role` registers these components.

# 5. Import Design

## 5.1 Format

- File type: JSON.
- Encoding: UTF-8 without requirement for BOM.
- Maximum size: 2 MiB by default.
- Schema version: `1.0`.
- Maximum roles: 500.
- Maximum permissions per role: 500.
- Guard: `admin` only.

## 5.2 Version 1 Document Contract

Conceptual structure:

```text
schema_version: "1.0"
exported_at: ISO-8601 timestamp
source:
  application: string
  environment: optional string
guard: "admin"
roles:
  - name: string
    protected: boolean
    permissions:
      - permission_name
```

Rules:

- Unknown top-level fields may be ignored only if the schema explicitly permits forward-compatible metadata.
- Missing required fields fail validation.
- Duplicate role names fail validation.
- Duplicate permissions within a role are normalized and reported.
- Permission order has no semantic meaning.
- Role name plus fixed guard is the import identity.
- Source `protected` is informational only.

## 5.3 Import Modes

Version 1 supports:

- `update_or_create`

Semantics:

- Create an ordinary role when `(name, admin)` does not exist.
- Replace an ordinary role's permission set when it exists.
- Empty permission array clears all permissions on an ordinary role.
- Omitted `permissions` is invalid.
- Roles absent from the document are not deleted.
- Protected role rows are rejected and block apply.
- Unknown permission names are rejected and block apply.
- Permissions are never created by import.

Unsupported in version 1:

- Replace-all roles.
- Delete-missing roles.
- Cross-guard import.
- Protected-role restore.
- Permission definition import.
- User-role assignment import.

## 5.4 Validation Pipeline

1. Authorize actor for `import_role`.
2. Validate upload metadata and size.
3. Store privately with generated path.
4. Compute SHA-256.
5. Parse JSON with exception-on-error behavior.
6. Validate schema version and document shape.
7. Validate fixed guard.
8. Validate role count and row fields.
9. Validate unique role identities.
10. Resolve protected roles server-side.
11. Validate all permission names against catalog.
12. Compute create/update/unchanged plan.
13. Generate structured dry-run report.
14. Issue short-lived token bound to actor, file, and report.
15. Revalidate token and critical state at apply.
16. Apply in one transaction.
17. Write audit rows and invalidate permission cache.

## 5.5 Import Report

Report includes:

- Import UUID.
- File hash.
- Schema version.
- Total roles.
- Create/update/unchanged/error counts.
- Row number or role name.
- Field.
- Stable error code.
- Safe reason.
- Protected-role conflicts.
- Unknown permissions.
- Dry-run expiry.
- Apply eligibility.
- Audit UUID after apply.

Raw exceptions are excluded.

## 5.6 Storage and Cleanup

- Disk: private.
- Prefix: `role/imports/{actor-id}/{import-uuid}/`.
- Original filename is metadata only.
- Files expire after 24 hours by default.
- Expired dry-run tokens cannot be reused.
- Scheduled cleanup removes expired files and marks records expired.

# 6. Export Design

## 6.1 Export Contract

Export uses the same version 1 JSON document contract as import.

Properties:

- Deterministic role order by normalized name.
- Deterministic permission order by name.
- Fixed guard `admin`.
- UTF-8 JSON.
- Pretty printing allowed.
- No user assignments.
- No numeric role or permission database IDs.
- No audit records.
- No secrets or internal file paths.

## 6.2 Protected Roles in Export

- Protected roles may be included for visibility and backup inspection.
- Each protected role is marked `protected: true`.
- Version 1 import refuses to apply protected role rows.
- Recovery of protected roles is handled through the canonical seeder/controlled console process, not standard import.

## 6.3 Export Workflow

1. Authorize `export_role`.
2. Query admin roles through `RoleQuery::forExport()`.
3. Eager/lazy load permission names without N+1 queries.
4. Map to deterministic scalar rows.
5. Serialize with bounded memory behavior.
6. Compute document hash.
7. Return download or private temporary file.
8. Write export audit event.

Filename:

- `roles-admin-v1-YYYYMMDD-HHMMSS.json`

## 6.4 Export Performance

- No `Role::with(...)->get()` for unbounded datasets.
- Use lazy iteration or chunking when role count exceeds a measured threshold.
- Query count must not grow linearly with role count.
- Export must remain round-trip compatible for ordinary roles.

# 7. Permissions

## 7.1 Required Capability Catalog

`Modules/Role/config/module.php` must declare structured metadata for:

| Permission | Purpose |
|---|---|
| `view_role` | View role list and details |
| `create_role` | Create ordinary roles |
| `edit_role` | Update ordinary roles and permission sets |
| `delete_role` | Delete ordinary roles |
| `import_role` | Upload, dry-run, and apply role configuration imports |
| `export_role` | Export role configuration |
| `sync_permission_catalog` | Reconcile manifest-declared permissions |
| `audit_role` | View Role audit and import operation history |

Rules:

- Permission names use `guard_name = admin`.
- `import_role`, `sync_permission_catalog`, and `audit_role` are high-trust permissions.
- `edit_role` does not imply import or catalog synchronization.
- `delete_role` does not bypass protected-role rules.
- Menu visibility uses `view_role`.

## 7.2 Authorization Matrix

| Operation | Route/Component Ability | Record Rule |
|---|---|---|
| List roles | `view_role` | Admin guard only |
| Create role | `create_role` | Ordinary role only |
| Edit role | `edit_role` | Admin guard, not protected |
| Delete role | `delete_role` | Admin guard, not protected |
| Bulk delete | `delete_role` | Every target ordinary and admin guard |
| Export | `export_role` | Admin guard dataset |
| Import dry run/apply | `import_role` | No protected-role changes |
| Sync catalog | `sync_permission_catalog` | Manifest declarations only |
| View audits | `audit_role` | According to audit visibility policy |

## 7.3 Enforcement Layers

Authorization is enforced at:

1. Route middleware.
2. Controller/page authorization.
3. Every public Livewire action.
4. Record policy.
5. Service-level domain invariants.

Blade visibility is not a security boundary.

## 7.4 Super Admin Bypass

The current global `Gate::before` in `Modules/ModuleServiceProvider.php` may grant capabilities to `Super Admin`.

Binding rule:

- Capability bypass may authorize an actor.
- It must not bypass protected-role invariants enforced by `RoleService`.
- A Super Admin actor still cannot rename, delete, import over, or reduce the protected role through standard workflows.

# 8. Transactions

## 8.1 Create Role Transaction

Atomic unit:

1. Validate actor capability.
2. Validate role identity and permissions.
3. Create admin role.
4. Synchronize complete permission set.
5. Write `ROLE_CREATED` audit.
6. Commit.
7. Invalidate Spatie cache after commit.

Failure result:

- No role or pivot remains.
- No success audit remains.

## 8.2 Update Role Transaction

Atomic unit:

1. Re-resolve role.
2. Authorize target.
3. Enforce protected-role rule.
4. Capture before snapshot.
5. Update name.
6. Synchronize complete permission set, including empty array.
7. Write `ROLE_UPDATED` audit.
8. Commit.
9. Invalidate cache after commit.

## 8.3 Delete Role Transaction

Atomic unit:

1. Re-resolve target.
2. Authorize.
3. Verify admin guard.
4. Reject protected role.
5. Capture assignment count and snapshot.
6. Delete role and dependent pivots.
7. Write `ROLE_DELETED` audit.
8. Commit.
9. Invalidate cache after commit.

## 8.4 Bulk Delete Transaction

Atomic unit:

1. Normalize unique IDs.
2. Resolve the complete set.
3. Fail if any ID is missing, wrong guard, unauthorized, or protected.
4. Capture snapshots and assignment counts.
5. Delete all targets.
6. Write one batch audit plus target metadata.
7. Commit.
8. Invalidate cache once after commit.

Partial bulk deletion is prohibited.

## 8.5 Permission Catalog Sync Transaction

Atomic unit:

1. Authorize `sync_permission_catalog`.
2. Validate the complete manifest catalog.
3. Create missing admin permissions.
4. Report stale permissions without deleting them.
5. Write catalog sync audit.
6. Commit.
7. Invalidate cache after commit.

## 8.6 Import Apply Transaction

Before transaction:

- Validate actor.
- Validate dry-run token, expiry, actor, file hash, report hash, schema, catalog, and protected-role state.

Atomic unit:

1. Mark import `APPLYING`.
2. Re-resolve every target identity.
3. Create/update every ordinary role.
4. Synchronize every complete permission set.
5. Write per-role or aggregated audit snapshots.
6. Mark import `COMPLETED`.
7. Commit.
8. Invalidate cache once after commit.

Failure:

- Roll back all role, pivot, audit, and completion-state writes.
- Record a safe failure status in a separate controlled transaction.
- Log correlation ID and stable failure code.

## 8.7 Concurrency

- Database unique constraints remain authoritative for `(name, guard_name)`.
- Services translate duplicate-key races into `RoleConflictException`.
- Protected-role resolution must tolerate concurrent reads.
- Import apply revalidates state after dry run.
- Cache invalidation occurs after successful commit only.

# 9. UI Components

## 9.1 Page Shells

Pages:

- `Modules/Role/resources/views/pages/roles/index.blade.php`
- `Modules/Role/resources/views/pages/roles/create.blade.php`
- `Modules/Role/resources/views/pages/roles/edit.blade.php`
- Optional import page or modal shell owned by Role.

Rules:

- Extend `Admin::layouts.master`.
- Contain page title, breadcrumbs, and one canonical Livewire mount.
- Do not query permissions or roles.

## 9.2 Role Table UI

Required elements:

- Search field.
- Page-size select: 10, 25, 50, 100.
- Role name.
- User assignment count.
- Guard badge.
- Protected-role badge.
- Created date.
- Authorized action menu.
- Current-page selection.
- Bulk delete confirmation.
- Export action.
- Link to restricted import workflow.

Behavior:

- Protected roles cannot be selected.
- Delete confirmation displays user assignment count.
- Unauthorized controls are hidden.
- Server-side denial remains authoritative.
- Loading and disabled states target the specific action.
- Empty and error states are explicit.

## 9.3 Role Form UI

Required sections:

- Role identity.
- Permission groups.
- Selected permission count.
- Validation summary.
- Save/cancel actions.

Behavior:

- Permission groups use manifest labels and metadata.
- No emoji conveys required meaning.
- Checkboxes remain keyboard accessible.
- Protected role edit displays a read-only explanation or returns 403 according to route policy.
- Save button is visible only when authorized.
- Empty permission selection is allowed for ordinary roles with clear warning.

## 9.4 Import UI

Required elements:

- Schema version.
- JSON-only and size-limit guidance.
- Download/export-current-configuration link.
- File upload.
- Dry-run action.
- Create/update/unchanged/error summary.
- Row-level errors.
- Protected-role and unknown-permission conflicts.
- Explicit apply confirmation.
- Dry-run expiry.
- Audit/import UUID after completion.

Rules:

- Apply is impossible before successful dry run.
- File replacement clears report and token.
- Raw exception text is never displayed.

## 9.5 Shared Components

Preferred shared components:

- AdminLTE card.
- Form group and validation feedback.
- Confirm modal.
- Alert/notification.
- Pagination.
- Structured import report table.

Shared import/export integration:

- Reuse report DTO conventions, private storage, cleanup, and status presentation from `Modules/Shared`.
- Do not force Role JSON into spreadsheet-specific `BaseImportExportService` behavior.
- Introduce a format-neutral shared contract if necessary.

## 9.6 Accessibility and Frontend Rules

- Bootstrap 5.3/AdminLTE 4 classes are canonical.
- Modal focus is trapped and restored.
- Buttons have accessible names.
- Validation errors are associated with fields.
- Color is not the only protected/error indicator.
- Confirmation dialogs identify the affected role count.
- Frontend build must not depend on Tailwind for Role screens.

# 10. Test Strategy

## 10.1 Test Layers

Required layers:

- Unit tests for catalog parsing, DTO normalization, protected-role resolution, and service invariants.
- Feature tests for routes, controllers, policy, Livewire, imports, exports, audits, and migrations.
- Architecture tests for canonical ownership and forbidden duplicates.
- Query-count and bounded-memory tests where practical.
- Frontend build and view-render tests.

## 10.2 Authorization Tests

Required scenarios:

- Guest cannot access Role pages.
- Authenticated non-admin-guard user cannot access Role pages.
- Admin-guard user without `view_role` receives denial.
- Each capability grants only its intended operation.
- Direct Livewire method calls are denied without capability.
- Hidden controls do not substitute for server authorization.
- Wrong-guard roles cannot be targeted.

Target tests:

- `tests/Feature/Role/RoleRouteAuthorizationTest.php`
- `tests/Feature/Role/RoleLivewireAuthorizationTest.php`
- `tests/Feature/Role/RoleTargetAuthorizationTest.php`

## 10.3 Protected Role Tests

Required scenarios:

- Protected role cannot be renamed.
- Protected role permissions cannot be reduced.
- Protected role cannot be single-deleted.
- Protected role cannot be bulk-deleted.
- Protected role cannot be imported.
- Display-name changes do not bypass protected identity.
- Super Admin capability bypass does not bypass service invariant.

Target:

- `tests/Feature/Role/ProtectedRoleTest.php`

## 10.4 CRUD and Validation Tests

Required scenarios:

- Ordinary role create succeeds with valid permissions.
- Duplicate name on admin guard fails.
- Same name on another guard does not enter the admin UI.
- Forged or unknown permission fails.
- Wrong-guard permission fails.
- Empty permission array clears all permissions.
- Transaction rollback restores role and pivots after failure.
- Missing or stale ID returns controlled 404/domain behavior.

Target tests:

- `tests/Feature/Role/RoleFormPersistenceTest.php`
- `tests/Feature/Role/RoleValidationTest.php`
- `tests/Feature/Role/RoleGuardIsolationTest.php`
- `tests/Unit/Role/RoleServiceTransactionTest.php`

## 10.5 Selection and Delete Tests

Required scenarios:

- Current-page select-all selects only visible ordinary roles.
- Search, page, per-page, and sort changes reset selection.
- Protected roles never enter selection.
- Missing selected ID blocks the complete bulk operation.
- Any protected/unauthorized target blocks the complete bulk operation.
- Bulk delete is all-or-nothing.
- Assignment counts are available without N+1 queries.

Target:

- `tests/Feature/Role/RoleTableSelectionTest.php`
- `tests/Feature/Role/RoleDeletionTest.php`
- `tests/Unit/Role/RoleBulkDeleteTransactionTest.php`

## 10.6 Import Tests

Fixtures:

- Valid version 1 document.
- Malformed JSON.
- Wrong schema version.
- Non-admin guard.
- Duplicate roles.
- Unknown permission.
- Protected role.
- Missing permissions field.
- Empty permission array.
- Oversized file.
- Too many roles.
- Too many permissions.
- Changed file after dry run.
- Expired token.

Required scenarios:

- Dry run writes no authorization data.
- Apply requires matching actor, file hash, report hash, and token.
- Import creates and updates ordinary roles.
- Empty permission array clears permissions.
- Roles absent from file remain unchanged.
- Unknown permissions are not created.
- Any apply failure rolls back every role and pivot change.
- Safe report and audit records are produced.

Target tests:

- `tests/Feature/Role/RoleImportAuthorizationTest.php`
- `tests/Feature/Role/RoleImportValidationTest.php`
- `tests/Feature/Role/RoleImportTransactionTest.php`
- `tests/Feature/Role/RoleImportErrorReportTest.php`

## 10.7 Export Tests

Required scenarios:

- Unauthorized export is denied.
- Output conforms to schema version 1.
- Only admin-guard roles are included.
- Role and permission ordering is deterministic.
- No user assignments or database IDs are exposed.
- Protected roles are marked read-only.
- Ordinary-role export/import round trip preserves permission sets.
- Query count does not scale linearly with role count.

Target:

- `tests/Feature/Role/RoleImportExportRoundTripTest.php`
- `tests/Feature/Role/RoleExportPerformanceTest.php`

## 10.8 Permission Catalog and Seeder Tests

Required scenarios:

- Every enabled module manifest is discovered.
- Disabled modules are ignored.
- Lowercase `config/module.php` works on case-sensitive filesystems.
- Duplicate permission names with conflicting metadata fail.
- Seeder creates missing admin permissions.
- Seeder creates/resolves protected role.
- Seeder synchronizes protected role permissions.
- Stale permissions are reported, not silently deleted.

Target:

- `tests/Unit/Role/PermissionCatalogDiscoveryTest.php`
- `tests/Feature/Role/RolePermissionSeederTest.php`

## 10.9 Audit Tests

Required scenarios:

- Create, update, delete, bulk delete, import, export, and catalog sync create audit rows.
- Before/after snapshots are correct.
- Failed transactions do not leave success audits.
- Audit rows are immutable through application paths.
- Raw import content and exception traces are absent.
- Audit visibility requires `audit_role`.

Target:

- `tests/Feature/Role/RoleAuditTest.php`

## 10.10 Migration Tests

Required scenarios:

- Fresh MySQL-compatible install creates canonical Spatie tables once.
- Existing install upgrade does not duplicate tables.
- Migration history strategy handles malformed `-0001` records.
- Role and Permission configured models operate against the schema.
- `role_audits` and `role_imports` indexes and constraints exist.
- `module_migrations` transition follows the approved baseline.
- Rollback behavior is documented and tested where safe.

Target:

- `tests/Feature/Role/RoleMigrationSmokeTest.php`

## 10.11 Architecture Tests

Required assertions:

- `Modules/Role` is the only module defining Role management Livewire components.
- `Modules/Admin` does not contain Role domain services or duplicate components.
- One canonical Role model is configured.
- One canonical Permission model is configured.
- Only the Role module writes permission definitions.
- Livewire components do not call `DB::transaction()` or direct model write methods.
- Blade files do not execute role/permission queries.

Target:

- `tests/Architecture/RoleModuleOwnershipTest.php`
- `tests/Architecture/CanonicalRoleModelTest.php`

## 10.12 Performance and View Tests

Required budgets:

- Role list uses a bounded query count independent of displayed role count.
- Role form permission catalog uses bounded queries and scalar state.
- Export does not materialize unnecessary user assignments.
- No `All` page size.
- Role views render with Bootstrap/AdminLTE assets and without required Tailwind utilities.

Target:

- `tests/Feature/Role/RoleFormQueryTest.php`
- `tests/Feature/Role/RoleViewRenderTest.php`
- Frontend production build check.

## 10.13 CI Release Gate

The rebuilt module is not complete until CI passes:

1. PHP syntax and formatting.
2. Static analysis.
3. Role unit and feature tests.
4. Authorization denial tests.
5. Import/export fixture tests.
6. Migration smoke tests against MySQL-compatible infrastructure.
7. Architecture ownership tests.
8. Frontend production build.

## Acceptance Criteria

The Role rebuild is accepted only when:

- No Role route or Livewire mutation relies on `auth:admin` alone.
- Protected roles cannot be modified through standard UI, import, or crafted Livewire requests.
- The module supports the `admin` guard only.
- Every submitted permission is validated against the manifest-owned catalog.
- Empty permission arrays correctly clear ordinary role permissions.
- Create, update, delete, bulk delete, and import are transactional.
- Import requires dry run and a matching short-lived apply token.
- Import never creates permission definitions.
- Export and import round-trip ordinary role configuration.
- One Role implementation, one model configuration, and one seeder are canonical.
- Authorization changes are auditable.
- Migration paths pass on fresh and existing installations.
- Role screens use Bootstrap 5.3/AdminLTE 4.

## Implementation Sequence

1. Add authorization tests and protected-role tests that fail against current behavior.
2. Establish canonical models, module type, permission catalog, and policy.
3. Implement Role query/service/action boundaries.
4. Rebuild Role form and table against services.
5. Add audit schema and transactional writes.
6. Build dry-run-first import and deterministic export.
7. Consolidate duplicate Admin component and duplicate seeder.
8. Repair migration baseline and infrastructure ownership.
9. Align views with Bootstrap/AdminLTE.
10. Remove confirmed placeholders and stale artifacts.

## Planning Constraints

- This document does not implement any code.
- Exact migration filenames and production-history transitions require a database migration baseline review before implementation.
- Exact Spatie schema details must be verified against the installed package version in `composer.lock`.
- Removing duplicate Admin or root seeder files requires dynamic-reference, route, menu, and deployment-script verification.
- The current shell does not expose `php`, so Laravel, Livewire, migration, and PHPUnit behavior was not executed while preparing this specification.
