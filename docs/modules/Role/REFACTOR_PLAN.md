# Modules/Role - Refactor Plan

Generated: 2026-06-15

Sources:

- `docs/modules/Role/ANALYSIS.md`
- `ROADMAP.md`

Scope: architecture and implementation planning for `Modules/Role`. This document proposes changes but contains no code.

## Estimation Scale

### Estimated Risk

- **Critical:** Current behavior enables privilege escalation, authorization bypass, or loss of administrative control.
- **High:** Change affects persisted authorization data, guards, migrations, imports, or multiple role-management workflows.
- **Medium:** Change can cause a visible regression but has a contained rollback path.
- **Low:** Local cleanup, presentation, or maintainability work with limited runtime impact.

### Estimated Complexity

- **Low:** Localized change with few dependencies.
- **Medium:** Several files or one workflow with focused tests.
- **High:** Cross-layer or cross-module work involving authorization, data migration, or compatibility.
- **Critical:** Architectural migration with broad dependency and rollout requirements.

### Estimated Effort

- **XS:** Less than 0.5 day.
- **S:** 0.5-1 day.
- **M:** 2-4 days.
- **L:** 1-2 weeks.
- **XL:** More than 2 weeks or requires cross-module migration.

Effort includes implementation, tests, review, migration planning, and rollback preparation where relevant.

## Architectural Direction

The Role refactor should follow these Laravel 12 and Livewire 3 principles:

- `Modules/Role` is the canonical owner of role and permission administration; `Modules/Admin` remains a presentation shell.
- Authentication with `auth:admin` is not authorization. Routes, controllers, and every public Livewire action must fail closed through named capabilities and target-level policy checks.
- Protected system roles are identified by an immutable server-side rule, not a mutable display name.
- Livewire components coordinate UI state only. Role mutation, permission catalog rules, transactions, cache invalidation, import/export, and auditing belong in services.
- The `admin` guard is explicit throughout validation, queries, uniqueness, imports, and permission synchronization.
- Imported authorization data is untrusted. It must pass a versioned schema, allowlists, protected-role rules, size bounds, dry-run validation, and atomic persistence.
- One configured Spatie Role model and one set of permission tables are canonical.
- Destructive and multi-record changes are transactional and auditable.
- Public Livewire properties contain scalar or array view state rather than Eloquent model collections.
- Regression tests are a release gate for denied access, protected roles, guard isolation, imports, and migrations.

# P0 Critical

## P0-01 - Role Management Has No Capability-Level Authorization

**Issue**

The module declares `view_role`, `create_role`, `edit_role`, and `delete_role`, but web routes, controllers, page views, and Livewire actions rely only on `auth:admin`. Every authenticated admin can reach and invoke role-management operations.

This workstream covers:

- Missing permission middleware on list, create, and edit routes.
- Missing authorization in `RoleController`.
- Missing authorization in `RoleForm::mount()` and `RoleForm::save()`.
- Missing authorization on every public `RoleTable` action.
- Unconditional rendering of create, edit, delete, bulk-delete, import, export, save, and permission-generation controls.
- Unauthorized permission creation being allowed before the existing transaction begins.

**Root Cause**

Authentication was treated as sufficient authorization. Permission names exist as manifest metadata, but there is no policy or capability map connecting them to HTTP routes and Livewire request endpoints.

**Business Impact**

Any staff account with admin-guard login access can alter the authorization model for the entire application. This can grant unauthorized access to sensitive modules, remove legitimate access, or cause a full administrative compromise.

**Technical Impact**

The module has no fail-closed security boundary. Blade visibility cannot secure direct URLs or crafted Livewire requests, and future reuse of either Livewire component would inherit the same vulnerability.

**Proposed Solution**

- Define an explicit Role capability map for view, create, edit, delete, import, export, and permission-catalog management.
- Add dedicated high-trust permissions such as `import_role`, `export_role`, and `manage_permission_catalog` to the module manifest.
- Apply permission middleware to every web route.
- Authorize page access in the controller or route boundary.
- Authorize every public Livewire method independently, including modal-opening methods when they reveal sensitive configuration.
- Resolve and authorize each target Role record before mutation.
- Use Blade `@can` or equivalent only as a secondary UX control.
- Add route and Livewire tests for denied and allowed users under the `admin` guard.

**Files To Change**

- `Modules/Role/config/module.php`
- `Modules/Role/routes/web.php`
- `Modules/Role/Http/Controllers/RoleController.php`
- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/resources/views/pages/roles/index.blade.php`
- `Modules/Role/resources/views/pages/roles/create.blade.php`
- `Modules/Role/resources/views/pages/roles/edit.blade.php`
- `Modules/Role/resources/views/livewire/role-form.blade.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/Policies/RolePolicy.php` (new)
- `app/Providers/AppServiceProvider.php`
- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `tests/Feature/Role/RoleRouteAuthorizationTest.php` (new)
- `tests/Feature/Role/RoleLivewireAuthorizationTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** High

**Estimated Effort:** L

## P0-02 - Protected System Roles Depend on the Mutable Name `Super Admin`

**Issue**

The form can rename `Super Admin` and change or clear its permissions. Deletion code protects only roles whose current name exactly equals `Super Admin`; after a rename, the same role can be deleted. Import can also target or create a role with that name.

This workstream covers:

- Editing and renaming the protected role.
- Reducing the protected role's permission set.
- Deletion protection based on a mutable display name.
- Bulk deletion using the same weak name comparison.
- Misleading Blade messaging that claims the role cannot be deleted.
- Import overwrite or creation of protected roles.

**Root Cause**

A business invariant was implemented independently in view and deletion code as a string comparison. There is no immutable protected-role identity or centralized policy enforced across all write paths.

**Business Impact**

Operators can remove the recovery role, strip its access, or create a misleading duplicate. The organization can lose administrative control or retain an account that appears protected but is not.

**Technical Impact**

Protection varies by entry point and is bypassable through rename, import, or future service callers. The system cannot reliably distinguish system-managed roles from ordinary roles.

**Proposed Solution**

- Define protected role identities in server-owned configuration using stable identifiers or immutable role keys.
- Decide and document whether protected roles may be renamed, deleted, imported, or have permissions reduced.
- Enforce the invariant in a canonical Role service and Role policy, not only in Livewire or Blade.
- Prevent import from creating, replacing, renaming, or reducing protected roles unless an explicit recovery-only workflow exists.
- Ensure at least one usable protected administrator role remains.
- Render protected roles as read-only where policy denies mutation.
- Add regression tests for form save, single delete, bulk delete, and import.

**Files To Change**

- `Modules/Role/config/module.php`
- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/resources/views/livewire/role-form.blade.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/Services/RoleService.php` (new)
- `Modules/Role/Policies/RolePolicy.php` (new)
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `tests/Feature/Role/ProtectedRoleTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** High

**Estimated Effort:** L

## P0-03 - JSON Import Is an Unrestricted Privilege-Escalation Path

**Issue**

The uploaded JSON controls role names, guard names, and permission names. The importer creates missing roles and permissions and synchronizes them without a schema, server-owned catalog, protected-role policy, or dedicated authorization permission.

**Root Cause**

Import was implemented as a trusted backup restore mechanism inside a general Livewire action. External data is treated as authoritative authorization configuration.

**Business Impact**

An authenticated operator can create privileged roles, add arbitrary permissions, modify existing authorization, or introduce cross-guard role configuration. A malformed import can also disrupt access for many users at once.

**Technical Impact**

The importer crosses role, permission, guard, cache, and transaction boundaries without a validated contract. It is difficult to audit, test, retry, or recover safely.

**Proposed Solution**

- Disable import in production until the hardened workflow and regression tests exist.
- Require a dedicated high-trust permission separate from normal role editing.
- Define a versioned JSON schema with explicit document and row limits.
- Accept only the `admin` guard for this module unless a separate cross-guard workflow is designed.
- Validate role identity, permission names, duplicate entries, array sizes, protected roles, and conflict behavior before any write.
- Resolve permission names from a server-owned catalog; do not create arbitrary permissions during normal import.
- Add dry-run output and a structured error/conflict report.
- Persist only after complete validation, in one transaction, with consistent Spatie cache invalidation.
- Create an immutable audit record containing actor, source filename/hash, summary, and before/after changes.

**Files To Change**

- `Modules/Role/config/module.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `Modules/Role/Data/RoleImportDocument.php` (new)
- `Modules/Role/Data/RoleImportRow.php` (new)
- `Modules/Shared/Services/ImportExport/BaseImportExportService.php`
- `Modules/Shared/Livewire/ImportExport/Panel.php`
- `Modules/Shared/Resources/views/livewire/import-export/panel.blade.php`
- `tests/Feature/Role/RoleImportAuthorizationTest.php` (new)
- `tests/Feature/Role/RoleImportValidationTest.php` (new)
- `tests/Feature/Role/RoleImportTransactionTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** High

**Estimated Effort:** L

## P0-04 - Client-Controlled Livewire Targets Are Not Re-Resolved and Authorized

**Issue**

`RoleForm` stores a public `$roleId`; table actions accept public role IDs. The code does not consistently resolve with `findOrFail()`, verify the target guard, protect system roles, or authorize the specific target immediately before mutation.

**Root Cause**

Livewire public properties and method arguments were treated as trusted component state rather than browser-controlled request data.

**Business Impact**

An operator can tamper with component payloads to target roles that were not displayed or intended for that action. Missing or stale IDs can also produce partial operations or runtime failures.

**Technical Impact**

The module is vulnerable to insecure direct object reference behavior. Route checks and hidden buttons do not protect later Livewire requests.

**Proposed Solution**

- Treat every public property and action argument as untrusted.
- Resolve records immediately before reads and writes using the configured Spatie Role model.
- Require the expected `admin` guard and authorize the resolved role.
- Keep only scalar identifiers in Livewire state and avoid trusting prior mount-time authorization.
- Use locked properties where appropriate, while retaining server-side re-resolution and authorization.
- Add tampering, stale-ID, wrong-guard, and missing-record tests.

**Files To Change**

- `Modules/Role/Http/Controllers/RoleController.php`
- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Policies/RolePolicy.php` (new)
- `Modules/Role/Services/RoleService.php` (new)
- `tests/Feature/Role/RoleTargetAuthorizationTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** Medium

**Estimated Effort:** M

# P1 Important

## P1-01 - Role Business Logic Has No Canonical Service Boundary

**Issue**

Role CRUD, permission synchronization, permission generation, deletion, import/export, cache invalidation, and protected-role behavior are embedded in Livewire components.

**Root Cause**

The module was built around UI workflows rather than domain operations. No service layer was introduced when authorization behavior became shared and security-sensitive.

**Business Impact**

Different callers can apply different authorization rules and produce inconsistent role configuration. Fixes are slower and riskier because UI and business behavior cannot be tested independently.

**Technical Impact**

Livewire classes have too many responsibilities, transaction boundaries are inconsistent, and non-Livewire callers cannot reuse invariants safely.

**Proposed Solution**

- Introduce a canonical Role service for querying, create/update, permission synchronization, protected-role enforcement, deletion, bulk deletion, and permission-catalog creation.
- Introduce a separate import/export service because document validation and reporting have different responsibilities.
- Keep authorization decisions in policy/service boundaries and keep Livewire focused on validated UI state.
- Return explicit result objects or domain exceptions rather than raw database behavior.
- Centralize Spatie cache invalidation after successful writes.

**Files To Change**

- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/RoleService.php` (new)
- `Modules/Role/Services/PermissionCatalogService.php` (new)
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `Modules/Role/Exceptions/ProtectedRoleException.php` (new)
- `Modules/Role/Exceptions/RoleImportException.php` (new)
- `tests/Unit/Role/RoleServiceTest.php` (new)
- `tests/Unit/Role/PermissionCatalogServiceTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-02 - Role Form Persistence and Permission Synchronization Are Incorrect

**Issue**

Saving a role synchronizes permissions only when the selected array is non-empty. Clearing every checkbox therefore retains old permissions. Role persistence and permission synchronization are also not atomic.

**Root Cause**

An empty selection was interpreted as “do nothing” rather than a valid desired state. The two related writes were implemented without a service transaction.

**Business Impact**

Administrators cannot reliably revoke all permissions from a role. A synchronization failure can leave a renamed or newly created role with an unintended permission set.

**Technical Impact**

The persisted role state can diverge from the submitted form. Retry behavior is ambiguous and partial writes are possible.

**Proposed Solution**

- Always synchronize the validated permission list, including an empty list.
- Wrap role create/update and permission synchronization in one transaction.
- Re-resolve the role after persistence where needed.
- Invalidate permission cache only after successful commit.
- Add tests for clearing all permissions, synchronization failure rollback, create, and update.

**Files To Change**

- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Services/RoleService.php` (new)
- `tests/Feature/Role/RoleFormPersistenceTest.php` (new)
- `tests/Unit/Role/RoleServiceTransactionTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-03 - Guard Handling and Role Uniqueness Are Inconsistent

**Issue**

The form loads permissions from every guard, validates role name globally rather than by `(name, guard_name)`, and forces created roles to `admin`. Import defaults missing guards to `web`, finds roles by name only, and then synchronizes permissions for the uploaded guard.

**Root Cause**

Guard identity is handled as an incidental string rather than part of the role and permission aggregate identity.

**Business Impact**

Users can see or select permissions from the wrong guard. Imports can update the wrong role or fail after partial preparation, and legitimate same-name roles on different guards can be incorrectly rejected.

**Technical Impact**

Application validation disagrees with the database composite unique constraint. Spatie guard mismatch exceptions are likely, and authorization data can become confusing across guards.

**Proposed Solution**

- Declare `admin` as the only supported guard for this module unless a separate multi-guard requirement is approved.
- Filter all role and permission queries by `guard_name = admin`.
- Use composite, guard-aware uniqueness validation.
- Include guard in every role lookup identity.
- Reject missing, unknown, or non-admin guards during import.
- Add guard-isolation tests for form, list, import, and service operations.

**Files To Change**

- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/RoleService.php` (new)
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `Modules/Role/config/module.php`
- `tests/Feature/Role/RoleGuardIsolationTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-04 - Form and Permission-Creation Validation Are Incomplete

**Issue**

The form validates only that `selectedPermissions` is an array. Permission values are not verified as existing admin permissions. The permission-generation action does not validate the fixed action map or require at least one selected action.

**Root Cause**

Validation covers top-level UI shape but not the domain meaning of nested values.

**Business Impact**

Invalid or manipulated submissions can produce errors, select unauthorized permissions, or create meaningless permission modules with no actions.

**Technical Impact**

Spatie exceptions become the validation boundary. Domain constraints are duplicated implicitly in UI options rather than enforced server-side.

**Proposed Solution**

- Validate `selectedPermissions.*` as bounded strings that exist for the `admin` guard.
- Normalize and de-duplicate permission names before service calls.
- Validate `newModuleActions` as an allowlisted boolean map.
- Require at least one selected action.
- Apply length and naming limits to module names based on the permission catalog contract.
- Add validation tests for forged permission names, wrong guards, invalid action keys, and empty action sets.

**Files To Change**

- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/PermissionCatalogService.php` (new)
- `tests/Feature/Role/RoleValidationTest.php` (new)
- `tests/Feature/Role/PermissionCatalogValidationTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-05 - Role Table Selection and Deletion Workflow Is Broken

**Issue**

Bulk deletion calls an undefined `resetSelection()` after records are deleted. The `selectAll` property has no update hook, and search, pagination, and page-size changes do not reset selection or page state. Single deletion dereferences the result of `Role::find()` without handling a missing record.

**Root Cause**

The component was copied with an explicit comment saying selection methods were omitted. The Blade controls were retained even though their backing behavior was incomplete.

**Business Impact**

Bulk deletion can succeed in the database and then fail in the UI, leaving operators uncertain about what was removed. Select-all does not behave as advertised, and stale selections can delete unintended records.

**Technical Impact**

The component has a guaranteed runtime error and inconsistent state across filters and pages. Missing records cause null dereferences.

**Proposed Solution**

- Implement `resetSelection()`, search/page hooks, per-page hooks, and deterministic select-all behavior.
- Define whether select-all means current page or all filtered records, and communicate it in the UI.
- Validate selected IDs as existing admin-guard roles before mutation.
- Resolve single-delete targets with `findOrFail()` or a domain result.
- Exclude protected roles server-side, regardless of checkbox visibility.
- Add Livewire tests for every selection transition and stale-ID behavior.

**Files To Change**

- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/Services/RoleService.php` (new)
- `tests/Feature/Role/RoleTableSelectionTest.php` (new)
- `tests/Feature/Role/RoleDeletionTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-06 - Multi-Record Deletion Is Not Atomic or Auditable

**Issue**

Bulk deletion loops over roles without a transaction or complete pre-validation. There is no audit record for single deletion, bulk deletion, role updates, permission generation, or import.

**Root Cause**

Destructive actions were implemented directly in the component as individual model calls. Operational logging was limited to transient UI notifications.

**Business Impact**

A failure can delete only part of the requested set. Security-sensitive changes cannot be attributed, reviewed, or reconstructed during an incident.

**Technical Impact**

The operation is not all-or-nothing, rollback is difficult, and there is no durable evidence of before/after authorization state.

**Proposed Solution**

- Resolve and validate the complete target set before deletion.
- Enforce protected-role and guard rules before starting writes.
- Delete the set in one database transaction.
- Record actor, action, target IDs, before/after snapshots, source, and correlation ID.
- Avoid logging sensitive request internals or raw uploaded content.
- Add rollback and audit-record tests.

**Files To Change**

- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/RoleService.php` (new)
- `Modules/Role/Services/PermissionCatalogService.php` (new)
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `Modules/Role/Models/RoleAudit.php` (new)
- `Modules/Role/database/migrations/*_create_role_audits_table.php` (new)
- `tests/Feature/Role/RoleAuditTest.php` (new)
- `tests/Unit/Role/RoleBulkDeleteTransactionTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-07 - Import/Export Lacks a Dedicated, Versioned Contract

**Issue**

Import and export are methods on `RoleTable`. Import has no document version, strict JSON structure, size bound, dry run, error report, conflict policy, or safe exception handling. It accepts `txt`, and an empty permission list does not clear existing permissions. Export materializes all data before returning a streamed response.

**Root Cause**

Backup-style serialization was added to the UI component without an import/export architecture or compatibility contract.

**Business Impact**

Operators cannot predict whether import creates, updates, replaces, or preserves data. Malformed files produce poor feedback, and exported files may not restore authorization state exactly.

**Technical Impact**

Parsing, validation, transactions, cache handling, persistence, and HTTP response logic are tightly coupled. Format changes cannot be versioned safely.

**Proposed Solution**

- Create a Role-specific JSON import/export service with a versioned schema.
- Define exact empty-array semantics, conflict modes, role identity, guard behavior, and permission catalog rules.
- Validate the entire document before persistence and produce row-level errors.
- Add dry-run support and safe domain exceptions.
- Restrict extension, MIME, content, and maximum file size.
- Make exported documents deterministic and round-trip testable.
- Stream or chunk output where role/permission volume warrants it.
- Integrate with shared import/export reporting patterns without forcing spreadsheet-only behavior.

**Files To Change**

- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `Modules/Role/Data/RoleImportDocument.php` (new)
- `Modules/Role/Data/RoleImportRow.php` (new)
- `Modules/Shared/Services/ImportExport/BaseImportExportService.php`
- `Modules/Shared/Livewire/ImportExport/Panel.php`
- `Modules/Shared/Resources/views/livewire/import-export/panel.blade.php`
- `tests/Feature/Role/RoleImportExportRoundTripTest.php` (new)
- `tests/Feature/Role/RoleImportErrorReportTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-08 - Permission Catalog Naming and Grouping Have No Single Source of Truth

**Issue**

Permission creation uses `action_module`, form grouping infers the module from the final underscore token, the seeder reads manifest arrays, and validation has no canonical catalog. Names such as `view_blog_post` are grouped as `post`.

**Root Cause**

Permission names are treated as parseable strings instead of structured metadata. Naming logic is repeated across UI, seeding, and mutation paths.

**Business Impact**

Administrators can see misleading groups, create permissions that do not correspond to a real module, and manage inconsistent authorization labels.

**Technical Impact**

String parsing fails for multi-word modules and cannot reliably distinguish action from resource. Import validation and UI display cannot share one authoritative catalog.

**Proposed Solution**

- Define structured permission metadata in module manifests or a canonical catalog service.
- Store or derive action, module key, label, guard, and protected status consistently.
- Make the seeder, form grouping, permission generation, and import validation consume the same catalog.
- Decide whether runtime permission creation is permitted; prefer manifest-driven definitions for deployed modules.
- Add catalog discovery and grouping tests.

**Files To Change**

- `Modules/Role/config/module.php`
- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `Modules/Role/Services/PermissionCatalogService.php` (new)
- `Modules/*/config/module.php`
- `tests/Unit/Role/PermissionCatalogDiscoveryTest.php` (new)
- `tests/Feature/Role/PermissionGroupingTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-09 - Role Logic Is Duplicated in the Admin Module

**Issue**

`Modules/Role/Livewire/RoleTable.php` is effectively duplicated in `Modules/Admin/Livewire/System/RoleTable.php`, including its security defects, missing selection hooks, import/export logic, and undefined method call.

**Root Cause**

Domain ownership between Role and Admin was never established. The Admin shell copied feature code instead of routing to a canonical module.

**Business Impact**

Bug fixes can be applied to one screen but not the other. Operators may encounter different behavior depending on which admin entry point is active.

**Technical Impact**

Two components own the same persisted authorization data. Test coverage and maintenance cost double, and architecture boundaries remain unclear.

**Proposed Solution**

- Declare `Modules/Role` as the canonical authorization-management owner.
- Let `Modules/Admin` provide layout, menu, and navigation only.
- Migrate all callers to the Role component and service.
- Remove the duplicate only after route, alias, menu, and regression tests prove no active caller remains.
- Add an architecture test preventing Admin from defining a second Role management implementation.

**Files To Change**

- `Modules/Role/config/module.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/RoleService.php` (new)
- `Modules/Admin/Livewire/System/RoleTable.php`
- `Modules/Admin/resources/views/livewire/system/role-table.blade.php`
- `Modules/Admin/data/menus.json`
- `Modules/Admin/routes/web.php`
- `tests/Architecture/RoleModuleOwnershipTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-10 - Seeder Ownership and Module Permission Discovery Are Duplicated

**Issue**

Role/permission seeders exist in both `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php` and `database/seeders/RolesAndPermissionsSeeder.php`. The root duplicate uses `Modules/*/Config/module.php`, while current module directories use lowercase `config` on the case-sensitive filesystem.

**Root Cause**

Seeder logic was copied during module migration without selecting one canonical owner or validating filesystem casing.

**Business Impact**

Deployments can seed different permission sets depending on which class is called. Missing permissions can deny legitimate staff access or leave expected controls unprotected.

**Technical Impact**

Permission discovery is environment-sensitive and duplicated. Future manifest changes may not reach all deployment paths.

**Proposed Solution**

- Keep `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php` as the canonical seeder if Role owns the domain.
- Remove or delegate the root duplicate after checking deployment scripts.
- Use the exact lowercase `config/module.php` path.
- Validate manifest structure, enabled state, guard, and permission metadata.
- Use deterministic, guard-aware upsert behavior.
- Add a test proving all enabled module permissions are discovered and assigned to the protected administrator role.

**Files To Change**

- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `database/seeders/RolesAndPermissionsSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `Modules/*/config/module.php`
- `tests/Feature/Role/RolePermissionSeederTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-11 - Model and Module Ownership Are Ambiguous

**Issue**

`Modules/Role/Models/Role.php` is an unused plain Eloquent model, while `config/permission.php` and all active code use `Spatie\Permission\Models\Role`. The module is named `Role` but its manifest type is `shell`.

**Root Cause**

Scaffolding generated a local model and shell manifest without reconciling them with Spatie's configured domain model.

**Business Impact**

Future developers may import the wrong Role class and bypass Spatie relationships or guard behavior, causing authorization defects.

**Technical Impact**

There are two apparent model candidates but only one valid configured model. Dependency and ownership rules are not self-documenting.

**Proposed Solution**

- Decide whether to use Spatie's model directly or create a deliberate module-owned subclass implementing the Spatie Role contract.
- Configure exactly one canonical Role model in `config/permission.php`.
- Remove the unused scaffold after reference and serialization checks.
- Change the module type to the project's canonical domain type if Role owns authorization behavior.
- Add an architecture test preventing plain Eloquent Role models from being introduced.

**Files To Change**

- `Modules/Role/Models/Role.php`
- `Modules/Role/config/module.php`
- `config/permission.php`
- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/RoleService.php` (new)
- `tests/Architecture/CanonicalRoleModelTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-12 - Migration Names, Schema Compatibility, and Table Ownership Are Unsafe

**Issue**

Five Spatie permission migrations use malformed `-0001` filenames. The schemas are manually copied and may drift from the installed Spatie version. A future package migration publication could create duplicate table ownership. The Role module also creates the unrelated `module_migrations` table.

**Root Cause**

Migration files were imported without preserving valid Laravel timestamps or documenting schema ownership. Infrastructure migration tracking was placed in the Role module without an active consumer.

**Business Impact**

Fresh installations and upgrades can fail, run migrations in an unexpected order, or create incompatible authorization tables. Migration failure can block deployment or make the authorization system unavailable.

**Technical Impact**

Migration discovery is non-standard, package compatibility is unverified, and rollback ownership is unclear. Renaming already-recorded migrations requires a production-safe history strategy.

**Proposed Solution**

- Compare the five schemas with the exact installed Spatie permission version and current configuration.
- Declare one canonical owner for all Spatie permission tables.
- Prepare an environment-aware migration-history plan before renaming files; do not simply rename files already recorded in production.
- Add fresh-install and existing-install smoke tests for the production database and supported test database.
- Move `module_migrations` to the module infrastructure owner or remove it through an explicit safe migration after confirming no external consumer.
- Document rollback and deployment sequencing.

**Files To Change**

- `Modules/Role/database/migrations/-0001_11_30_000010_create_permissions_table.php`
- `Modules/Role/database/migrations/-0001_11_30_000011_create_roles_table.php`
- `Modules/Role/database/migrations/-0001_11_30_000012_create_model_has_permissions_table.php`
- `Modules/Role/database/migrations/-0001_11_30_000013_create_model_has_roles_table.php`
- `Modules/Role/database/migrations/-0001_11_30_000014_create_role_has_permissions_table.php`
- `Modules/Role/database/migrations/2026_04_20_104916_module_migrations.php`
- `config/permission.php`
- `composer.lock`
- `Modules/ModuleServiceProvider.php`
- `tests/Feature/Role/RoleMigrationSmokeTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-13 - Permission Loading and Livewire State Are Unbounded

**Issue**

`RoleForm::mount()` loads every permission from every guard, de-duplicates in PHP, and stores Eloquent Permission models in public Livewire state. Export loads all roles and permission relations and builds the complete JSON collection before response output.

**Root Cause**

Queries and component state were designed for a small initial dataset without guard filtering, scalar view models, or bounded export behavior.

**Business Impact**

Role screens and exports become slower as modules and permissions grow. Large serialized Livewire payloads degrade administrator experience and can increase request failures.

**Technical Impact**

Unnecessary rows cross the database and Livewire hydration boundary. Export is nominally streamed but still fully materialized in memory.

**Proposed Solution**

- Query only `admin` permissions and select only required fields.
- Order and group using catalog metadata rather than Eloquent objects in public state.
- Store scalar arrays suitable for Livewire serialization.
- Move export queries into the import/export service and use lazy iteration or bounded chunks where justified.
- Add query-count, payload-shape, and export-volume tests.

**Files To Change**

- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/PermissionCatalogService.php` (new)
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `tests/Feature/Role/RoleFormQueryTest.php` (new)
- `tests/Feature/Role/RoleExportPerformanceTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-14 - Route and Controller Contracts Are Weak

**Issue**

The edit route accepts an unconstrained `{id}`. The controller uses an untyped parameter, has no return types, and passes the raw ID to the page and Livewire component.

**Root Cause**

The controller was treated as a thin view dispatcher without applying Laravel's route binding and typed contract conventions.

**Business Impact**

Invalid URLs produce inconsistent errors, and authorization cannot be naturally attached to a resolved role.

**Technical Impact**

Record resolution is deferred to client-influenced component state. Static analysis and framework-assisted binding provide little protection.

**Proposed Solution**

- Use route model binding against the canonical configured Role model or a controlled numeric constraint if binding is not feasible.
- Add typed controller parameters and `View` return types.
- Resolve guard scope and authorization before rendering edit pages.
- Pass a stable identifier or authorized view model to Livewire.
- Add 404, wrong-guard, and authorization route tests.

**Files To Change**

- `Modules/Role/routes/web.php`
- `Modules/Role/Http/Controllers/RoleController.php`
- `Modules/Role/resources/views/pages/roles/edit.blade.php`
- `Modules/Role/Livewire/RoleForm.php`
- `tests/Feature/Role/RoleRouteBindingTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** Low

**Estimated Effort:** S

## P1-15 - Error Handling and Operator Feedback Are Not Production-Grade

**Issue**

Malformed JSON, missing keys, wrong structures, stale IDs, Spatie guard mismatches, and persistence errors have no consistent exception handling or structured report. Import cache cleanup is not guaranteed through a `finally` path.

**Root Cause**

Framework and package exceptions are allowed to escape UI actions, and notifications are used as the only operational feedback mechanism.

**Business Impact**

Operators cannot determine what failed or whether any data changed. Support teams lack correlation data for investigating authorization incidents.

**Technical Impact**

Error behavior differs by action, cache state may be uncertain after failures, and internal exception details may leak if global handling is permissive.

**Proposed Solution**

- Use domain exceptions and safe user-facing messages.
- Add structured logs with actor, action, correlation ID, and redacted context.
- Guarantee transaction rollback and permission-cache handling with explicit control flow.
- Return structured import validation and conflict reports.
- Distinguish validation, authorization, conflict, protected-role, and unexpected failures.
- Add failure-path tests.

**Files To Change**

- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/RoleService.php` (new)
- `Modules/Role/Services/PermissionCatalogService.php` (new)
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `Modules/Role/Exceptions/ProtectedRoleException.php` (new)
- `Modules/Role/Exceptions/RoleImportException.php` (new)
- `app/Exceptions/Handler.php` or Laravel 12 exception configuration in `bootstrap/app.php`
- `tests/Feature/Role/RoleFailureHandlingTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-16 - Role Views Do Not Match the Installed UI Stack

**Issue**

Both Livewire views use Tailwind utility classes and emoji icons while extending an AdminLTE layout. The project roadmap identifies Bootstrap 5.3 and AdminLTE 4 RC as the installed stack.

**Root Cause**

The views were created from a different frontend convention without reconciling project dependencies and layout APIs.

**Business Impact**

The role-management UI can render inconsistently, lose responsive behavior, or become expensive to maintain alongside other admin screens.

**Technical Impact**

Styles depend on utilities that may not be built or supported. Shared components and accessibility conventions cannot be reused consistently.

**Proposed Solution**

- Confirm the project-wide Bootstrap 5/AdminLTE 4 decision from `ROADMAP.md`.
- Rebuild the two views using existing Admin module components and Bootstrap conventions.
- Replace emoji-dependent semantics with the established icon system.
- Preserve Livewire loading, validation, modal, and accessibility behavior.
- Add frontend build and focused browser/component rendering checks.

**Files To Change**

- `Modules/Role/resources/views/livewire/role-form.blade.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/resources/views/pages/roles/index.blade.php`
- `Modules/Role/resources/views/pages/roles/create.blade.php`
- `Modules/Role/resources/views/pages/roles/edit.blade.php`
- `Modules/Admin/resources/views/components/*`
- `resources/css/*`
- `resources/js/*`
- `tests/Feature/Role/RoleViewRenderTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** Medium

**Estimated Effort:** M

# P2 Nice to Have

## P2-01 - Public Placeholder API Adds Unnecessary Surface

**Issue**

`GET /api/role` is public and returns only a static success message. The route and controller do not provide a Role API contract.

**Root Cause**

Module scaffolding was left active after generation.

**Business Impact**

The endpoint can mislead integrators into assuming a supported API exists and creates unnecessary public surface.

**Technical Impact**

Route inventory and security review contain a non-functional endpoint. Future developers may extend it without authentication or authorization conventions.

**Proposed Solution**

- Remove the route and controller if no API requirement exists.
- If an API is required, define versioning, authentication, authorization, resource serialization, pagination, and tests before exposing role data.

**Files To Change**

- `Modules/Role/routes/api.php`
- `Modules/Role/Http/Controllers/Api/RoleController.php`
- `tests/Feature/Role/RoleApiTest.php` (new only if API retained)

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** XS

## P2-02 - Unused Role Model Scaffold Should Be Removed or Made Intentional

**Issue**

`Modules/Role/Models/Role.php` is not referenced and does not implement Spatie's Role contract.

**Root Cause**

Generic module scaffolding generated a model that was never integrated.

**Business Impact**

The file increases the chance that future code imports the wrong Role class.

**Technical Impact**

Static analysis and code navigation show an ambiguous model that cannot safely replace the configured Spatie model.

**Proposed Solution**

- Remove the file after checking dynamic references, or replace it only as part of the canonical-model decision in P1-11.
- Add an architecture test for the selected Role model.

**Files To Change**

- `Modules/Role/Models/Role.php`
- `config/permission.php`
- `tests/Architecture/CanonicalRoleModelTest.php` (new)

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** XS

## P2-03 - Obsolete Commented Route and Implementation Notes Create Noise

**Issue**

Route files retain commented alternatives, and `RoleTable` contains comments stating that required selection methods were omitted and should be copied later.

**Root Cause**

Temporary development notes were committed as permanent source documentation.

**Business Impact**

Developers can follow obsolete examples or assume incomplete behavior is intentional.

**Technical Impact**

Source files mix executable behavior with stale instructions, reducing review clarity.

**Proposed Solution**

- Remove obsolete commented routes and implementation placeholders after the real behavior is covered by tests.
- Keep only comments that explain non-obvious invariants.

**Files To Change**

- `Modules/Role/routes/web.php`
- `Modules/Role/routes/api.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Livewire/RoleForm.php`

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** XS

## P2-04 - Search and Pagination Can Be Refined

**Issue**

Role search uses a leading wildcard and pagination does not reset when search or page size changes.

**Root Cause**

The list was optimized for implementation simplicity and expected small role volume.

**Business Impact**

Administrators can land on empty pages after filter changes. Search can slow if the role catalog grows substantially.

**Technical Impact**

`LIKE %term%` cannot use a normal prefix index efficiently, and stale page state produces unnecessary queries.

**Proposed Solution**

- Reset page and selection when search or page size changes as part of P1-05.
- Keep contains-search for small catalogs; adopt prefix search or an indexed strategy only after measuring.
- Add a query budget test if role volume becomes significant.

**Files To Change**

- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `tests/Feature/Role/RoleSearchTest.php` (new)

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** S

## P2-05 - Seeder Can Use Bounded Bulk Persistence

**Issue**

The module seeder executes `firstOrCreate()` once per permission.

**Root Cause**

Seeder implementation prioritizes readability over query count.

**Business Impact**

Deployment and test seeding becomes slower as the permission catalog expands.

**Technical Impact**

The number of database round trips grows linearly with permission count.

**Proposed Solution**

- After catalog consolidation, use a deterministic guard-aware bulk upsert.
- Preserve timestamps and uniqueness behavior.
- Measure before optimizing because current catalogs may be small.

**Files To Change**

- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `Modules/Role/Services/PermissionCatalogService.php` (new)
- `tests/Feature/Role/RolePermissionSeederTest.php` (new)

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** S

## P2-06 - Import and Authorization Operations Need Better UX Feedback

**Issue**

The UI provides only basic notifications and does not show import schema, file limit, guard rules, protected-role conflicts, dry-run results, or durable audit references.

**Root Cause**

Operational feedback was designed around synchronous happy-path actions.

**Business Impact**

Administrators cannot confidently predict or verify authorization changes, increasing support requests and operational mistakes.

**Technical Impact**

The UI has no structured result contract from services and cannot represent partial validation or conflict details.

**Proposed Solution**

- Display schema version, maximum file size, supported guard, conflict mode, and protected-role policy.
- Add dry-run summaries and downloadable error reports.
- Display an audit/correlation identifier after sensitive operations.
- Present clear permission-denied and protected-role states.

**Files To Change**

- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/resources/views/livewire/role-form.blade.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Services/RoleConfigurationImportExportService.php` (new)
- `Modules/Shared/Livewire/ImportExport/Panel.php`
- `Modules/Shared/Resources/views/livewire/import-export/panel.blade.php`

**Estimated Risk:** Low

**Estimated Complexity:** Medium

**Estimated Effort:** M

## Finding Coverage Matrix

The following matrix maps every issue category from `docs/modules/Role/ANALYSIS.md` to this plan.

| Analysis Finding | Refactor Item |
|---|---|
| Routes enforce only `auth:admin` | P0-01 |
| Controllers have no authorization | P0-01 |
| Page and Livewire controls have no capability visibility | P0-01 |
| Public Livewire actions have no authorization | P0-01, P0-04 |
| Arbitrary permission namespace creation | P0-01, P1-04, P1-08 |
| Super Admin can be renamed, reduced, imported, then deleted | P0-02 |
| Livewire IDs can be tampered with | P0-04 |
| Import can create privileged roles and permissions | P0-03 |
| Import lacks schema, size, guard, row, conflict, and error validation | P0-03, P1-07, P1-15 |
| Import accepts `txt` | P1-07 |
| Import defaults to `web` and looks up by name only | P1-03 |
| Empty imported permission list does not clear permissions | P1-07 |
| Role form cannot clear all permissions | P1-02 |
| Role save is not atomic | P1-02 |
| Bulk delete is not atomic | P1-06 |
| Permission cache handling is inconsistent on import failure | P1-07, P1-15 |
| Permission values are not validated | P1-04 |
| Role uniqueness is not guard-aware | P1-03 |
| Permission action map is not validated | P1-04 |
| Route and record IDs are untyped or not existence-validated | P0-04, P1-14 |
| `resetSelection()` is undefined | P1-05 |
| Select-all and reset hooks are missing | P1-05 |
| Missing role can be dereferenced | P0-04, P1-05 |
| No audit trail | P1-06 |
| No Role service layer | P1-01 |
| Role JSON bypasses shared import/export standards | P0-03, P1-07 |
| Permission grouping fails for multi-word modules | P1-08 |
| Permission naming logic is duplicated | P1-08 |
| RoleTable is duplicated in Admin | P1-09 |
| Seeder is duplicated and has config path casing risk | P1-10 |
| Module Role model is unused and incompatible | P1-11, P2-02 |
| Module type/ownership is ambiguous | P1-09, P1-11 |
| Negative-year migration filenames | P1-12 |
| Spatie migration compatibility and ownership are unverified | P1-12 |
| `module_migrations` has unrelated ownership and no known consumer | P1-12 |
| All permissions are loaded across guards | P1-03, P1-13 |
| Eloquent models are stored in public Livewire state | P1-13 |
| Export is fully materialized | P1-07, P1-13 |
| Search uses leading wildcard | P2-04 |
| Search/per-page does not reset pagination | P1-05, P2-04 |
| Seeder performs one query per permission | P2-05 |
| Tailwind/emoji markup conflicts with Bootstrap/AdminLTE | P1-16 |
| Public placeholder API | P2-01 |
| Obsolete commented routes and implementation notes | P2-03 |
| Operator feedback is incomplete | P1-15, P2-06 |

## Recommended Implementation Order

### Phase 1 - Containment and Security Guardrails

Goal: stop unauthorized or destructive authorization changes before broader refactoring.

1. Implement P0-01 capability authorization at route, controller, Livewire action, and Blade boundaries.
2. Implement P0-02 immutable protected-role rules.
3. Disable Role JSON import in production, then implement the P0-03 authorization gate and validation contract.
4. Implement P0-04 server-side target resolution and authorization.
5. Add the P0 route, Livewire, target-tampering, protected-role, and import-denial regression tests.
6. Add the minimum audit events from P1-06 for security-sensitive writes.

Phase 1 release gate:

- Unauthorized admin users cannot view or invoke Role actions.
- Protected roles cannot be renamed, reduced, deleted, or overwritten by import.
- Import is either disabled or passes strict authorization and rejection tests.
- Direct Livewire payload tampering is denied.

### Phase 2 - Correctness, Transactions, and Canonical Architecture

Goal: create one reliable domain implementation and repair data-integrity behavior.

1. Implement P1-01 canonical services.
2. Implement P1-02 atomic role save and empty permission synchronization.
3. Implement P1-03 guard isolation and composite uniqueness.
4. Implement P1-04 complete nested validation.
5. Implement P1-05 selection and deletion correctness.
6. Complete P1-06 transactional deletion and audit records.
7. Implement P1-07 versioned Role import/export.
8. Implement P1-08 canonical permission catalog.
9. Consolidate duplicate Admin code and seeders through P1-09 and P1-10.
10. Resolve canonical model and module ownership through P1-11.
11. Repair migration hygiene and ownership through P1-12 with fresh/existing install tests.
12. Normalize failure handling through P1-15.

Phase 2 release gate:

- Role changes are transactional, guard-safe, auditable, and service-owned.
- Exported configuration round-trips through import without privilege or data drift.
- Only one RoleTable implementation, one Role model configuration, and one seeder are canonical.
- Fresh and existing database migration paths pass.

### Phase 3 - Performance, Presentation, and Cleanup

Goal: reduce operational cost and remove obsolete artifacts after behavior is protected by tests.

1. Implement P1-13 bounded queries and scalar Livewire state.
2. Implement P1-14 typed route/controller contracts.
3. Align the Role UI with Bootstrap/AdminLTE through P1-16.
4. Remove or formalize the API through P2-01.
5. Remove the unused model scaffold through P2-02 after the canonical-model decision.
6. Remove stale comments through P2-03.
7. Apply measured search and seeder optimizations through P2-04 and P2-05.
8. Improve structured operator feedback through P2-06.

Phase 3 release gate:

- Role screens conform to the selected frontend stack.
- No confirmed duplicate, placeholder, or misleading Role artifacts remain.
- Query, payload, and export behavior stay within agreed budgets.

## Verification Strategy

The implementation should add focused coverage in this order:

1. Route and Livewire authorization tests.
2. Protected-role and direct-target tampering tests.
3. Guard isolation and validation tests.
4. Transaction rollback and audit tests.
5. Import rejection, dry-run, conflict, and round-trip tests.
6. Selection and deletion workflow tests.
7. Seeder discovery and canonical ownership architecture tests.
8. Fresh-install and existing-install migration tests.
9. Query-count, export-volume, and view-render tests.

## Planning Constraints

- This document does not implement any change.
- Exact migration rename and removal steps require inspection of production migration history before execution.
- Removing duplicate files requires route, Livewire alias, menu, deployment-script, and dynamic-reference verification.
- The current shell does not expose `php`, so Laravel, Livewire, migration, and PHPUnit behavior was not executed while preparing this plan.
