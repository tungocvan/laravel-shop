# Modules/Role - Analysis

Generated: 2026-06-15

Scope: static analysis of `Modules/Role` only, following:

`Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared Components -> Service -> Import/Export -> Model -> Migration`

No application code was changed.

## Executive Summary

`Modules/Role` provides an admin UI for managing Spatie roles and permissions, generating permission sets for named modules, and importing/exporting the complete role configuration as JSON.

The active web flow is:

1. `Modules/Role/routes/web.php`
2. `Modules/Role/Http/Controllers/RoleController.php`
3. `Modules/Role/resources/views/pages/roles/*.blade.php`
4. `Modules/Role/Livewire/RoleTable.php` or `Modules/Role/Livewire/RoleForm.php`
5. `Modules/Role/resources/views/livewire/*.blade.php`
6. Direct calls to `Spatie\Permission\Models\Role` and `Spatie\Permission\Models\Permission`
7. Five Spatie permission tables plus `module_migrations`

The module is not safe for production administration in its current form. The highest-risk findings are:

- **P0:** Every user authenticated by the `admin` guard can create, edit, delete, import, and export roles and permissions because no declared module permission is enforced.
- **P0:** JSON import accepts role names, guards, and permission names from the uploaded file, so any authenticated admin can create or replace privileged authorization configuration, including a `Super Admin` role.
- **P0:** The edit form allows the existing `Super Admin` role to be renamed and its permissions changed. Deletion protection relies only on the exact mutable string `Super Admin`.
- **P1:** Saving a role does not clear permissions when all checkboxes are unchecked.
- **P1:** Bulk deletion calls an undefined `resetSelection()` method; select-all hooks are also missing.
- **P1:** Role and permission business logic is embedded in Livewire components and duplicated almost exactly in `Modules/Admin/Livewire/System/RoleTable.php`.
- **P1:** Migration filenames begin with `-0001`, and the unrelated `module_migrations` table is owned by the Role module.

## 1. Module Purpose

The module currently provides:

- Admin role listing, search, pagination, single deletion, and bulk deletion.
- Role creation and editing.
- Assignment of Spatie permissions to roles.
- Creation of conventional permission names such as `view_product`.
- JSON export of all roles and assigned permissions.
- JSON import that creates roles and permissions.
- Seeding of module-declared permissions and the `Super Admin` role.

Module manifest:

- File: `Modules/Role/config/module.php`
- Name: `Role`
- Type: `shell`
- Enabled: `true`
- Declared permissions:
  - `view_role`
  - `create_role`
  - `edit_role`
  - `delete_role`

The declared permissions are not enforced by the module's routes, controllers, Livewire methods, or Blade controls.

## 2. Route List

### Web Routes

All web routes are declared in `Modules/Role/routes/web.php`.

Common middleware:

- `web`
- `auth:admin`

Common prefix: `/admin`

Common name prefix: `admin.role.`

| Method | URI | Name | Controller | Result |
|---|---|---|---|---|
| GET | `/admin/role` | `admin.role.index` | `RoleController@index` | Role list page |
| GET | `/admin/role/create` | `admin.role.create` | `RoleController@create` | Role creation page |
| GET | `/admin/role/{id}/edit` | `admin.role.edit` | `RoleController@edit` | Role edit page |

Issues:

- **P0:** `Modules/Role/routes/web.php` requires authentication but does not require `view_role`, `create_role`, or `edit_role`.
- **P1:** `Modules/Role/routes/web.php` leaves `{id}` unconstrained and does not use route model binding.
- **P2:** `Modules/Role/routes/web.php` contains an obsolete commented route block.
- **P1 recommendation:** Add route-level permission middleware matching each operation in `Modules/Role/routes/web.php`.
- **P1 recommendation:** Use typed route model binding or at least `whereNumber('id')` in `Modules/Role/routes/web.php`.

### API Route

Declared in `Modules/Role/routes/api.php`:

| Method | Effective URI | Controller | Middleware | Result |
|---|---|---|---|---|
| GET | `/api/role` | `Modules\Role\Http\Controllers\Api\RoleController@index` | `api` only | Public static JSON success response |

Issues:

- **P2:** `Modules/Role/routes/api.php` exposes a public placeholder endpoint that does not provide role data or business value.
- **P2:** `Modules/Role/routes/api.php` contains a commented `auth:sanctum` route block.
- **P2 recommendation:** Remove the placeholder API route and `Modules/Role/Http/Controllers/Api/RoleController.php`, or define an authenticated, authorized API contract.

## 3. Controllers

### Web Controller

File: `Modules/Role/Http/Controllers/RoleController.php`

Public methods:

- `index()`
  - Returns `Role::pages.roles.index`.
- `create()`
  - Returns `Role::pages.roles.create`.
- `edit($id)`
  - Returns `Role::pages.roles.edit` with the raw route ID.

Issues:

- **P0:** `Modules/Role/Http/Controllers/RoleController.php` performs no capability authorization.
- **P1:** `Modules/Role/Http/Controllers/RoleController.php` has no return types and leaves `$id` untyped.
- **P1:** `Modules/Role/Http/Controllers/RoleController.php` passes an unvalidated identifier to Livewire instead of resolving a role through model binding.
- **P0 recommendation:** Authorize `view_role`, `create_role`, and `edit_role` in `Modules/Role/Http/Controllers/RoleController.php` or route middleware.
- **P1 recommendation:** Add typed `View` returns and bind the configured Spatie Role model in `Modules/Role/Http/Controllers/RoleController.php`.

### API Controller

File: `Modules/Role/Http/Controllers/Api/RoleController.php`

Public methods:

- `index()`
  - Returns `{ "status": "Api Role success" }`.

The controller is a scaffold and does not interact with the Role domain.

## 4. Page Blade Files

### Role List Page

File: `Modules/Role/resources/views/pages/roles/index.blade.php`

- Extends `Admin::layouts.master`.
- Mounts `@livewire('role.role-table')`.

### Role Create Page

File: `Modules/Role/resources/views/pages/roles/create.blade.php`

- Extends `Admin::layouts.master`.
- Mounts `@livewire('role.role-form')`.

### Role Edit Page

File: `Modules/Role/resources/views/pages/roles/edit.blade.php`

- Extends `Admin::layouts.master`.
- Mounts `@livewire('role.role-form', ['id' => $id])`.

Issues:

- **P0:** None of the page files contains `@can` or equivalent permission-aware visibility for its mounted management UI.
- **P1:** `Modules/Role/resources/views/pages/roles/edit.blade.php` forwards the raw route ID.
- **P0 recommendation:** Enforce authorization server-side; add Blade visibility checks only as a secondary UX control in all three page files.

## 5. Livewire PHP Classes

### RoleForm

File: `Modules/Role/Livewire/RoleForm.php`

Public state:

- `$roleId`
- `$isEdit`
- `$name`
- `$selectedPermissions`
- `$permissionGroups`

Public methods:

- `mount($id = null)`
  - Loads every permission from every guard.
  - Removes duplicate names in memory.
  - Groups permissions by the final underscore-separated token.
  - Loads the selected role and its permissions when editing.
- `save()`
  - Validates the role name and permission array.
  - Creates or updates a Spatie role with guard `admin`.
  - Synchronizes permissions only when the selected list is non-empty.
  - Redirects to the role index.
- `render()`
  - Returns `Role::livewire.role-form`.

Issues:

- **P0:** `Modules/Role/Livewire/RoleForm.php` has no authorization in `mount()` or `save()`, so direct Livewire requests bypass any future button visibility checks.
- **P0:** `Modules/Role/Livewire/RoleForm.php` allows editing, renaming, and changing permissions on `Super Admin`.
- **P0:** `Modules/Role/Livewire/RoleForm.php` identifies records by a client-influenced public `$roleId` and does not re-authorize or protect the target during `save()`.
- **P1:** `Modules/Role/Livewire/RoleForm.php` calls `syncPermissions()` only for a non-empty selection. Clearing every checkbox leaves all previous permissions attached.
- **P1:** `Modules/Role/Livewire/RoleForm.php` saves the role before permission synchronization without a transaction; a synchronization failure can leave a partially updated role.
- **P1:** `Modules/Role/Livewire/RoleForm.php` does not filter permissions to `guard_name = admin`.
- **P1:** `Modules/Role/Livewire/RoleForm.php` validates only that `selectedPermissions` is an array, not that every item is a valid admin-guard permission.
- **P1:** `Modules/Role/Livewire/RoleForm.php` uses `unique:roles,name,{id}` without including `guard_name`, while the database uniqueness rule is the composite `(name, guard_name)`.
- **P1:** `Modules/Role/Livewire/RoleForm.php` exposes Eloquent Permission objects through public Livewire state in `$permissionGroups`, increasing serialized component state and coupling UI state to models.
- **P1:** Permission grouping in `Modules/Role/Livewire/RoleForm.php` uses the last token, so names such as `view_blog_post` are grouped under `post`, not `blog_post`.
- **P1 recommendation:** Move create/update and permission synchronization into a transactional Role service called by `Modules/Role/Livewire/RoleForm.php`.
- **P0 recommendation:** Deny mutation of protected system roles in `Modules/Role/Livewire/RoleForm.php` and in the service/policy boundary.
- **P1 recommendation:** Always synchronize the validated permission list, including an empty list, in `Modules/Role/Livewire/RoleForm.php`.
- **P1 recommendation:** Query only admin-guard permissions and validate each submitted permission against that guard.

### RoleTable

File: `Modules/Role/Livewire/RoleTable.php`

Traits:

- `WithPagination`
- `WithFileUploads`

Public state:

- Search and pagination: `$search`, `$perPage`
- Selection: `$selected`, `$selectAll`
- Import: `$showImportModal`, `$importFile`
- Permission creation: `$showPermissionModal`, `$newModuleName`, `$newModuleActions`

Public methods:

- `openPermissionModal()`
- `createModulePermissions()`
- `deleteSelected()`
- `delete($id)`
- `export()`
- `import()`
- `render()`

Issues:

- **P0:** `Modules/Role/Livewire/RoleTable.php` has no authorization on any public action.
- **P0:** `Modules/Role/Livewire/RoleTable.php` import allows the uploaded JSON to define role names, guards, and permission names, including privileged roles and permissions.
- **P0:** `Modules/Role/Livewire/RoleTable.php` protects `Super Admin` only by exact role name. The role can first be renamed through `RoleForm`, then deleted.
- **P0:** `Modules/Role/Livewire/RoleTable.php` permits arbitrary permission namespace creation through `createModulePermissions()`, independently of the permissions declared in module manifests.
- **P1:** `Modules/Role/Livewire/RoleTable.php` calls undefined `resetSelection()` after bulk deletion, causing a runtime error after records have already been deleted.
- **P1:** `Modules/Role/Livewire/RoleTable.php` declares `$selectAll` but does not implement `updatedSelectAll()`, selection reset, search reset, or page reset hooks. The source itself says these methods were omitted.
- **P1:** `Modules/Role/Livewire/RoleTable.php` uses `Role::find($id)` and immediately dereferences `$role->name`; a missing or stale ID causes an error.
- **P1:** `Modules/Role/Livewire/RoleTable.php` bulk deletion is not wrapped in a transaction. A failure can leave only part of the selected set deleted.
- **P1:** `Modules/Role/Livewire/RoleTable.php` import does not validate decoded JSON, row structure, required keys, string lengths, array sizes, allowed guards, or permission existence.
- **P1:** `Modules/Role/Livewire/RoleTable.php` accepts `txt` uploads and has no file-size limit.
- **P1:** `Modules/Role/Livewire/RoleTable.php` defaults missing import guards to `web`, while the rest of the module manages `admin` roles.
- **P1:** `Modules/Role/Livewire/RoleTable.php` looks up imported roles by `name` only but creates them with a composite identity of name and guard. An existing same-name role on another guard can be selected and then fail permission synchronization.
- **P1:** `Modules/Role/Livewire/RoleTable.php` does not clear an existing role's permissions when imported `permissions` is empty or omitted.
- **P1:** `Modules/Role/Livewire/RoleTable.php` has no exception handling or user-safe import report for malformed JSON or row failures.
- **P1 recommendation:** Put all mutating actions behind named permissions and policy/service checks in `Modules/Role/Livewire/RoleTable.php`.
- **P0 recommendation:** Restrict import to the admin guard, prohibit protected role mutation, and validate permission names against an approved server-side catalog.
- **P1 recommendation:** Implement deterministic selection hooks and `resetSelection()` in `Modules/Role/Livewire/RoleTable.php`.
- **P1 recommendation:** Extract import/export and role mutation logic from `Modules/Role/Livewire/RoleTable.php` into dedicated services.

## 6. Livewire Blade Views

### Role Form View

File: `Modules/Role/resources/views/livewire/role-form.blade.php`

Features:

- Role name input.
- Permission groups and checkboxes.
- Save and cancel actions.

Issues:

- **P0:** The save action is always rendered and has no authorization-aware visibility.
- **P0:** The view provides no protected-system-role state; `Super Admin` fields remain editable.
- **P1:** The view uses Tailwind utility classes and emoji icons despite the roadmap identifying Bootstrap 5/AdminLTE 4 as the installed UI stack.
- **P1 recommendation:** Render protected roles read-only and hide unauthorized controls, while retaining server-side checks in `Modules/Role/Livewire/RoleForm.php`.
- **P1 recommendation:** Align `Modules/Role/resources/views/livewire/role-form.blade.php` with the selected project UI stack.

### Role Table View

File: `Modules/Role/resources/views/livewire/role-table.blade.php`

Features:

- Search and page-size selection.
- Per-row and select-all checkboxes.
- Single and bulk deletion.
- JSON import/export.
- Permission module creation.
- Role edit links and user counts.

Issues:

- **P0:** Create, edit, delete, bulk delete, import, export, and permission-generation controls are visible to every authenticated admin.
- **P1:** The select-all checkbox binds to behavior that is absent from `Modules/Role/Livewire/RoleTable.php`.
- **P1:** The view tells the user that `Super Admin` cannot be deleted, but it can be renamed and then deleted.
- **P1:** The view uses Tailwind utility classes while extending an AdminLTE layout.
- **P1:** The JSON import UI does not document a schema, maximum size, accepted guard, protected roles, or replacement semantics.
- **P0 recommendation:** Add permission-aware control visibility in `Modules/Role/resources/views/livewire/role-table.blade.php`, backed by server authorization.
- **P1 recommendation:** Either implement select-all correctly or remove the non-functional control from `Modules/Role/resources/views/livewire/role-table.blade.php`.

## 7. Shared Components

Shared presentation dependencies:

- `Admin::layouts.master`, used by all three page Blade files.
- Laravel's configured pagination view through `$roles->links()`.
- Alpine integration through `@entangle()` in `Modules/Role/resources/views/livewire/role-table.blade.php`.

Available but unused shared import/export foundation:

- `Modules/Shared/Livewire/ImportExport/Panel.php`
- `Modules/Shared/Resources/views/livewire/import-export/panel.blade.php`
- `Modules/Shared/Services/ImportExport/BaseImportExportService.php`

Issues:

- **P1:** `Modules/Role/Livewire/RoleTable.php` implements its own import modal, file validation, transaction, cache handling, error behavior, and export response instead of a dedicated service or the shared import/export foundation.
- **P1:** The shared foundation is spreadsheet-oriented, so Role JSON requires either an explicit JSON-capable extension or a small Role-specific service contract rather than forcing incompatible behavior.
- **P1 recommendation:** Create a Role import/export service with the same validation/reporting/transaction standards as `Modules/Shared/Services/ImportExport/BaseImportExportService.php`, and keep the Livewire component thin.

## 8. Services and Public Methods

There is no `Services` directory and no service class in `Modules/Role`.

Business operations currently embedded in Livewire:

- Role create/update and permission synchronization in `Modules/Role/Livewire/RoleForm.php`.
- Permission generation in `Modules/Role/Livewire/RoleTable.php`.
- Single and bulk role deletion in `Modules/Role/Livewire/RoleTable.php`.
- JSON import/export in `Modules/Role/Livewire/RoleTable.php`.

Issues:

- **P1:** Authorization invariants, protected-role rules, guard rules, transactions, and cache invalidation cannot be reused safely by non-Livewire callers.
- **P1:** The lack of a service boundary contributes directly to duplication in `Modules/Admin/Livewire/System/RoleTable.php`.
- **P1 recommendation:** Introduce a Role service with public methods such as `paginate`, `create`, `update`, `delete`, `bulkDelete`, and `createModulePermissions`.
- **P1 recommendation:** Introduce a separate Role configuration import/export service with explicit schema validation and protected-role rules.

## 9. Models and Database Tables

### Module Model

File: `Modules/Role/Models/Role.php`

- Extends plain `Illuminate\Database\Eloquent\Model`.
- Declares no table, fillable fields, relationships, casts, or Spatie Role contract.
- Is not referenced by the module flow.
- Is not configured in `config/permission.php`.

### Active Models

The module directly uses:

- `Spatie\Permission\Models\Role`
- `Spatie\Permission\Models\Permission`

`config/permission.php` also configures those Spatie models as the canonical authorization models.

### Tables

| Table | Migration | Purpose |
|---|---|---|
| `permissions` | `Modules/Role/database/migrations/-0001_11_30_000010_create_permissions_table.php` | Permission names and guards |
| `roles` | `Modules/Role/database/migrations/-0001_11_30_000011_create_roles_table.php` | Role names and guards |
| `model_has_permissions` | `Modules/Role/database/migrations/-0001_11_30_000012_create_model_has_permissions_table.php` | Direct model-permission morph pivot |
| `model_has_roles` | `Modules/Role/database/migrations/-0001_11_30_000013_create_model_has_roles_table.php` | Model-role morph pivot |
| `role_has_permissions` | `Modules/Role/database/migrations/-0001_11_30_000014_create_role_has_permissions_table.php` | Role-permission pivot |
| `module_migrations` | `Modules/Role/database/migrations/2026_04_20_104916_module_migrations.php` | Tracks module migration names and batches |

Issues:

- **P1:** `Modules/Role/Models/Role.php` looks like an unused scaffold and is not a valid replacement for the configured Spatie Role model.
- **P1:** The module name `Role` suggests domain ownership, but `config/module.php` declares it as a `shell`; ownership should be made explicit.
- **P2 recommendation:** Remove `Modules/Role/Models/Role.php` after confirming no dynamic consumer, or implement and configure it intentionally as a Spatie-compatible model.

## 10. Import/Export Classes

No dedicated Import or Export classes exist in `Modules/Role`.

### Export

Implemented by `RoleTable::export()` in `Modules/Role/Livewire/RoleTable.php`.

Output schema:

```json
[
  {
    "name": "Role Name",
    "guard_name": "admin",
    "permissions": ["view_role", "edit_role"]
  }
]
```

### Import

Implemented by `RoleTable::import()` in `Modules/Role/Livewire/RoleTable.php`.

Behavior:

- Accepts JSON or text MIME validation.
- Decodes the entire file in memory.
- Creates roles and missing permissions.
- Synchronizes non-empty permission arrays.
- Wraps database writes in one transaction.

Issues:

- **P0:** Import is an unrestricted authorization-configuration write path.
- **P1:** There is no versioned schema, row validation, dry run, error report, size bound, guard allowlist, or conflict policy.
- **P1:** Export loads all roles and permissions into memory before streaming the already-built JSON.
- **P1 recommendation:** Extract import and export into dedicated classes/services and define a versioned JSON schema.
- **P1 recommendation:** Validate and normalize the complete document before starting writes; reject unknown guards, protected-role mutations, invalid permission names, oversized arrays, and duplicate role identities.

## 11. Authorization and Security Risks

### P0 Critical

1. **Missing capability authorization**
   - Files:
     - `Modules/Role/routes/web.php`
     - `Modules/Role/Http/Controllers/RoleController.php`
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
     - `Modules/Role/resources/views/livewire/role-form.blade.php`
     - `Modules/Role/resources/views/livewire/role-table.blade.php`
   - Risk: any authenticated admin can alter the authorization system.
   - **P0 recommendation:** Enforce named permissions at route and Livewire action boundaries, denied by default.

2. **Privilege escalation through import**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - Risk: uploaded JSON can create privileged roles and arbitrary permissions or modify an existing role's permission set.
   - **P0 recommendation:** Restrict import to a dedicated high-trust permission, require the `admin` guard, validate against a server-owned permission catalog, and protect system roles.

3. **Mutable and weakly protected Super Admin**
   - Files:
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
   - Risk: `Super Admin` can be renamed or stripped of permissions; after rename, deletion protection no longer applies.
   - **P0 recommendation:** Protect system roles by immutable identifier or configuration, not display name, and deny rename/delete/permission reduction according to explicit policy.

4. **Direct Livewire target tampering**
   - Files:
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
   - Risk: public IDs and action arguments can be changed in requests; UI restrictions alone cannot protect records.
   - **P0 recommendation:** Resolve targets server-side and authorize each action against the resolved role.

### P1 Important

5. **Public placeholder API**
   - Files:
     - `Modules/Role/routes/api.php`
     - `Modules/Role/Http/Controllers/Api/RoleController.php`
   - Risk: unnecessary public attack surface and misleading API availability.
   - **P2 recommendation:** Remove or secure the placeholder endpoint.

6. **No audit trail for authorization changes**
   - Files:
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
   - Risk: role, permission, import, and deletion changes cannot be attributed or reconstructed.
   - **P1 recommendation:** Record actor, action, target, before/after values, and import source metadata for all authorization mutations.

## 12. Validation Problems

1. **Permission array contents are not validated**
   - File: `Modules/Role/Livewire/RoleForm.php`
   - **P1 recommendation:** Validate `selectedPermissions.*` as strings that exist for `guard_name = admin`.

2. **Role uniqueness ignores guard semantics**
   - File: `Modules/Role/Livewire/RoleForm.php`
   - **P1 recommendation:** Apply a composite guard-aware uniqueness rule.

3. **Import document and rows are not validated**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - **P1 recommendation:** Validate decoded JSON type, required keys, scalar types, lengths, allowed guards, permission arrays, duplicate entries, and protected roles before persistence.

4. **Upload validation is weak**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - **P1 recommendation:** Require a JSON file with a strict size limit and verify content independently of client MIME/extension.

5. **Permission action selection is not validated**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - **P1 recommendation:** Validate `newModuleActions` as a fixed boolean map and require at least one selected action.

6. **Record identifiers are untyped and not existence-validated**
   - Files:
     - `Modules/Role/Http/Controllers/RoleController.php`
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
   - **P1 recommendation:** Use route model binding, typed IDs, `findOrFail()`, and authorization after resolution.

## 13. Transaction Risks

1. **Role save and permission synchronization are not atomic**
   - File: `Modules/Role/Livewire/RoleForm.php`
   - A role can be created or renamed even if permission synchronization fails.
   - **P1 recommendation:** Wrap role persistence and permission synchronization in one service transaction.

2. **Bulk deletion is not atomic**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - A failure can leave a partially deleted selection.
   - **P1 recommendation:** Validate the complete target set first, then delete in one transaction.

3. **Import cache invalidation is outside transaction outcome handling**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - Cache is cleared before JSON iteration and again only after success; exceptions skip the final explicit clear and user feedback.
   - **P1 recommendation:** Move import to a service with `try/catch/finally`, transactional writes, consistent cache invalidation, and safe reporting.

4. **Permission generation transaction is local but authorization is absent**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - Database atomicity is present, but unauthorized permission creation remains possible.
   - **P0 recommendation:** Authorize before starting the transaction.

## 14. N+1 and Query Performance Risks

No clear per-row N+1 exists on the role list because `Modules/Role/Livewire/RoleTable.php` uses `withCount('users')`.

Risks:

1. **Unbounded permission load**
   - File: `Modules/Role/Livewire/RoleForm.php`
   - `Permission::all()` loads all guards and then de-duplicates in PHP.
   - **P1 recommendation:** Filter by admin guard, select only required columns, order in SQL, and avoid model objects in public Livewire state.

2. **Unbounded export**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - All roles and permissions are materialized and converted to JSON before the response starts.
   - **P1 recommendation:** Use a bounded export service and stream/chunk when authorization data can grow significantly.

3. **Leading-wildcard search**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - `LIKE %term%` prevents normal prefix-index use.
   - **P2 recommendation:** Keep for small role catalogs; otherwise use prefix search or an indexed search strategy.

4. **No page reset on search or page-size change**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - Users can remain on an invalid page and trigger unnecessary empty queries.
   - **P1 recommendation:** Reset pagination and selection when search or page size changes.

5. **Seeder queries once per permission**
   - File: `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
   - `firstOrCreate()` is executed for every permission.
   - **P2 recommendation:** Use a guard-aware bulk upsert when the permission catalog becomes large.

## 15. Duplicate Logic

### Duplicate RoleTable

`Modules/Role/Livewire/RoleTable.php` is effectively duplicated in:

- `Modules/Admin/Livewire/System/RoleTable.php`

The duplicated implementation includes:

- Public state.
- Permission generation.
- Single and bulk deletion.
- JSON import/export.
- Missing selection hooks.
- Undefined `resetSelection()` call.

Impact:

- Bugs and security fixes must be applied twice.
- It is unclear whether Role or Admin owns authorization management.

- **P1 recommendation:** Make `Modules/Role` the canonical domain owner and let Admin provide only navigation/layout, then remove the duplicate after callers are migrated.

### Duplicate Seeder

Similar classes exist at:

- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `database/seeders/RolesAndPermissionsSeeder.php`

`database/seeders/DatabaseSeeder.php` currently calls the module seeder. The root seeder also uses `Modules/*/Config/module.php`, while actual module directories use lowercase `config` on the current case-sensitive filesystem.

- **P1 recommendation:** Keep one canonical seeder, use the real config path consistently, and add a test proving declared module permissions are discovered.

### Repeated Permission Naming Logic

Permission naming and grouping are spread across:

- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `Modules/Role/config/module.php`

- **P1 recommendation:** Define one permission catalog/naming service and consume it from seeding, UI grouping, validation, and import.

## 16. Files That Look Unused

1. `Modules/Role/Models/Role.php`
   - No repository reference was found.
   - The configured model is `Spatie\Permission\Models\Role`.
   - **P2 recommendation:** Remove after dynamic-reference verification, or convert it intentionally into the configured Spatie-compatible model.

2. `Modules/Role/Http/Controllers/Api/RoleController.php`
   - Used only by a public scaffold route and returns static JSON.
   - **P2 recommendation:** Remove with the placeholder API route unless a real API contract is required.

3. `Modules/Role/database/migrations/2026_04_20_104916_module_migrations.php`
   - Creates infrastructure metadata unrelated to roles or permissions.
   - No other repository reference to `module_migrations` was found.
   - **P1 recommendation:** Confirm intended infrastructure ownership; move it to the module loader/system owner or remove it through a safe migration plan.

4. `database/seeders/RolesAndPermissionsSeeder.php`
   - Outside the module but duplicates the active module seeder and is not called by `database/seeders/DatabaseSeeder.php`.
   - **P2 recommendation:** Remove after confirming no deployment script calls it directly.

## 17. Migration Analysis

### Spatie Permission Tables

Files:

- `Modules/Role/database/migrations/-0001_11_30_000010_create_permissions_table.php`
- `Modules/Role/database/migrations/-0001_11_30_000011_create_roles_table.php`
- `Modules/Role/database/migrations/-0001_11_30_000012_create_model_has_permissions_table.php`
- `Modules/Role/database/migrations/-0001_11_30_000013_create_model_has_roles_table.php`
- `Modules/Role/database/migrations/-0001_11_30_000014_create_role_has_permissions_table.php`

Positive points:

- Composite uniqueness exists for role/permission name plus guard.
- Pivot primary keys prevent duplicate assignments.
- Foreign keys cascade when roles or permissions are deleted.
- Morph lookup indexes exist on model assignment tables.

Issues:

- **P1:** All five filenames begin with a negative-looking year, `-0001`, which is non-standard and can break migration discovery/order assumptions.
- **P1:** The migrations are manually copied package schema. They need compatibility checks against the installed Spatie version and configured options.
- **P1:** Table ownership must remain singular; future publication of Spatie migrations would conflict with these table names.
- **P1 recommendation:** Rename the malformed migration files through an explicit migration-history-compatible plan and add fresh-install migration tests.
- **P1 recommendation:** Compare these schemas with the installed Spatie migration contract and lock one canonical owner.

### Module Migration Tracking Table

File: `Modules/Role/database/migrations/2026_04_20_104916_module_migrations.php`

Issues:

- **P1:** `module_migrations` is infrastructure metadata, not Role domain data.
- **P1:** No active model or service references this table.
- **P1 recommendation:** Move ownership to the module infrastructure layer or remove it after confirming it is unused.

## 18. Refactor Plan

### P0 Critical

1. **Enforce authorization on all role and permission operations**
   - Files:
     - `Modules/Role/routes/web.php`
     - `Modules/Role/Http/Controllers/RoleController.php`
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
     - `Modules/Role/resources/views/livewire/role-form.blade.php`
     - `Modules/Role/resources/views/livewire/role-table.blade.php`
   - Require `view_role`, `create_role`, `edit_role`, and `delete_role`, plus dedicated high-trust permissions for import/export and permission-catalog changes.

2. **Protect system roles independently of mutable names**
   - Files:
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
   - Prevent unauthorized rename, permission reduction, import overwrite, and deletion of protected roles.

3. **Harden or disable JSON import**
   - Files:
     - `Modules/Role/Livewire/RoleTable.php`
     - `Modules/Role/resources/views/livewire/role-table.blade.php`
   - Until a strict schema, guard allowlist, protected-role policy, authorization gate, validation report, and regression tests exist, import should not be available in production.

4. **Authorize every Livewire action server-side**
   - Files:
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
   - Do not rely on route access or hidden Blade controls.

### P1 Important

1. **Create canonical services**
   - Files:
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
   - Extract transactional role CRUD, protected-role rules, permission generation, cache handling, and import/export.

2. **Repair form correctness**
   - File: `Modules/Role/Livewire/RoleForm.php`
   - Always sync validated permissions, including an empty array; filter by admin guard; use guard-aware uniqueness; wrap save in a transaction.

3. **Repair table selection and deletion**
   - Files:
     - `Modules/Role/Livewire/RoleTable.php`
     - `Modules/Role/resources/views/livewire/role-table.blade.php`
   - Implement selection hooks, page resets, target validation, atomic deletion, and the missing `resetSelection()`.

4. **Define a strict import/export contract**
   - Files:
     - `Modules/Role/Livewire/RoleTable.php`
     - `Modules/Shared/Services/ImportExport/BaseImportExportService.php`
   - Use a Role-specific JSON service compatible with shared reporting and transaction standards.

5. **Consolidate duplicate ownership**
   - Files:
     - `Modules/Role/Livewire/RoleTable.php`
     - `Modules/Admin/Livewire/System/RoleTable.php`
     - `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
     - `database/seeders/RolesAndPermissionsSeeder.php`
   - Keep one canonical implementation and one canonical seeder.

6. **Repair migration hygiene**
   - Files:
     - `Modules/Role/database/migrations/-0001_11_30_000010_create_permissions_table.php`
     - `Modules/Role/database/migrations/-0001_11_30_000011_create_roles_table.php`
     - `Modules/Role/database/migrations/-0001_11_30_000012_create_model_has_permissions_table.php`
     - `Modules/Role/database/migrations/-0001_11_30_000013_create_model_has_roles_table.php`
     - `Modules/Role/database/migrations/-0001_11_30_000014_create_role_has_permissions_table.php`
     - `Modules/Role/database/migrations/2026_04_20_104916_module_migrations.php`
   - Establish deterministic ordering, installed-package compatibility, and correct table ownership.

7. **Add authorization and regression tests**
   - Target behavior from:
     - `Modules/Role/routes/web.php`
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
   - Cover denied access, protected roles, empty permission synchronization, malformed imports, guard conflicts, atomic deletion, and migration boot.

8. **Align presentation stack**
   - Files:
     - `Modules/Role/resources/views/livewire/role-form.blade.php`
     - `Modules/Role/resources/views/livewire/role-table.blade.php`
   - Reconcile Tailwind-style markup with the project's chosen Bootstrap/AdminLTE stack.

### P2 Nice to Have

1. **Remove confirmed scaffolds and comments**
   - Files:
     - `Modules/Role/routes/web.php`
     - `Modules/Role/routes/api.php`
     - `Modules/Role/Http/Controllers/Api/RoleController.php`
     - `Modules/Role/Models/Role.php`

2. **Improve search and bounded export**
   - File: `Modules/Role/Livewire/RoleTable.php`
   - Optimize only if role/permission volume justifies it.

3. **Improve permission grouping metadata**
   - Files:
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/config/module.php`
   - Replace underscore parsing with explicit module/action metadata.

4. **Improve operational feedback**
   - Files:
     - `Modules/Role/Livewire/RoleForm.php`
     - `Modules/Role/Livewire/RoleTable.php`
     - `Modules/Role/resources/views/livewire/role-table.blade.php`
   - Add structured import reports, conflict summaries, and audit references without exposing raw exceptions.

## Recommended Implementation Order

1. Apply P0 authorization gates and protected-role rules.
2. Disable or harden import before allowing further production use.
3. Add regression tests for denied access and Super Admin protection.
4. Extract one canonical Role service and import/export service.
5. Fix form synchronization, guard validation, selection, deletion, and transaction behavior.
6. Remove the duplicate Admin implementation and duplicate seeder.
7. Repair migration names and ownership with fresh-install tests.
8. Clean up unused scaffolds and align the UI stack.

## Verification Constraints

- This is static analysis; no application code was changed.
- The available shell does not expose `php`, so Laravel route listing, Livewire tests, migrations, and PHPUnit were not executed.
- Repository references were searched to distinguish active files, duplicates, and apparently unused scaffolds.
