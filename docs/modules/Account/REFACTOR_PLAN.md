# Modules/Account - Refactor Plan

Generated: 2026-06-14

Sources:

- `docs/modules/Account/ANALYSIS.md`
- `ROADMAP.md`

Scope: architecture and implementation planning for `Modules/Account`. This document proposes changes but contains no code.

## Estimation Scale

### Estimated Risk

- **Critical:** Current behavior can cause privilege escalation, sensitive-data exposure, authorization bypass, or destructive data inconsistency.
- **High:** Change affects authentication, roles, persisted data, migrations, imports, or multiple Account workflows.
- **Medium:** Change can cause a visible regression but has a contained rollback path.
- **Low:** Local cleanup or maintainability work with limited runtime impact.

### Estimated Effort

- **XS:** Less than 0.5 day.
- **S:** 0.5-1 day.
- **M:** 2-4 days.
- **L:** 1-2 weeks.
- **XL:** More than 2 weeks or requires cross-module migration.

Effort includes implementation, focused tests, review, and migration/rollback preparation where relevant.

## Architectural Direction

The Account refactor should follow these Laravel 12 and Livewire 3 principles:

- Authorization is enforced server-side at route/controller and Livewire action boundaries.
- One canonical user model owns authentication, roles, permissions, relationships, and the `users` table.
- Domain invariants live below the UI so imports, controllers, jobs, and Livewire actions behave consistently.
- Sensitive identity files use private storage and authorized delivery.
- Imports and exports share one documented, round-trip-capable contract.
- Large operations are bounded, chunked, lazy, or queued.
- Eloquent relationships and database constraints express the same cardinality and ownership rules.
- Livewire public methods are treated as request endpoints, not trusted internal calls.

# P0 Critical

## P0-01 - Account Routes Do Not Enforce Declared Permissions

**Issue**

`Modules/Account/config/module.php` declares `view_account`, `create_account`, `edit_account`, and `delete_account`, but `Modules/Account/routes/web.php` applies only `web` and `auth:admin`. Any authenticated admin-guard user can open Account pages.

**Root Cause**

Authentication and authorization were treated as equivalent. Permission metadata was added to the module manifest and menu, but it was not connected to route middleware, policies, or controller authorization.

**Business Impact**

Staff with unrelated admin access can view personal data and reach account-management workflows. This violates least privilege and can expose employee, customer, identity, and tax information.

**Technical Impact**

The route layer provides no fail-closed capability check. Menu visibility in `Modules/Admin/data/menus.json` is cosmetic and can be bypassed by entering a URL directly.

**Proposed Solution**

- Apply named permission middleware to each route according to its capability.
- Use `view_account` for index, `create_account` for create, and `edit_account` for edit.
- Introduce explicit permissions for import, export, activation, and role assignment if those operations must be independently delegated.
- Keep route middleware aligned with policies used by Livewire actions.
- Add feature tests for allowed and denied access through the `admin` guard.

**Files To Change**

- `Modules/Account/config/module.php`
- `Modules/Account/routes/web.php`
- `Modules/Account/Http/Controllers/AccountController.php`
- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `Modules/Admin/data/menus.json`
- `tests/Feature/Account/AccountRouteAuthorizationTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P0-02 - Livewire Actions Have No Server-Side Authorization

**Issue**

Public Livewire methods can create, update, activate, delete, bulk-delete, import, and export accounts without calling a gate, policy, or permission check.

**Root Cause**

The implementation assumes that reaching the page through an authenticated route and hiding controls in the UI is sufficient. In Livewire 3, every public action can be invoked through a component request and must authorize independently.

**Business Impact**

A low-privilege authenticated user can potentially change account state, delete accounts, export personal data, or import privileged data by crafting Livewire requests.

**Technical Impact**

Authorization is coupled to navigation rather than each state transition. Future reuse of the components from another page would inherit the same bypass.

**Proposed Solution**

- Create an Account policy or dedicated authorization service around the canonical user model.
- Call `authorize()` or equivalent permission checks in every public action that reads sensitive data or changes state.
- Authorize target records, not only broad capabilities.
- Apply visibility checks in Blade for usability, while retaining server-side checks as the security boundary.
- Test direct Livewire calls for both denied and allowed users.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `Modules/Account/resources/views/livewire/accounts/form.blade.php`
- `app/Policies/UserPolicy.php` or `Modules/Account/Policies/UserPolicy.php` (new; final location depends on canonical user ownership)
- `app/Providers/AppServiceProvider.php`
- `tests/Feature/Account/AccountLivewireAuthorizationTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** High

**Estimated Effort:** L

## P0-03 - Import Can Create and Assign Arbitrary Privileged Roles

**Issue**

`AccountImportService` accepts workbook-controlled `role_name` and `guard_name`, creates missing roles with `firstOrCreate()`, and assigns them. The current validation does not forbid `Super Admin`.

**Root Cause**

Role provisioning and account-data import were combined into one workflow. External workbook values are treated as authoritative permission definitions instead of references to a controlled server-side allowlist.

**Business Impact**

Anyone with Account import access can escalate a user to `Super Admin`, create misleading privileged roles, or assign roles for an unintended guard.

**Technical Impact**

The import crosses the Account/Role module boundary and mutates authorization configuration. A data-import feature becomes a privilege-management endpoint.

**Proposed Solution**

- Immediately reject `Super Admin` and unknown roles in ordinary Account imports.
- Stop creating roles during account import.
- Resolve role names only from an allowlist of existing `admin`-guard roles.
- Require a separate permission such as `assign_account_role`.
- Prefer importing a stable role identifier or approved role slug.
- Log role assignment as a security-sensitive audit event.
- Add import tests proving privilege escalation is rejected.

**Files To Change**

- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/config/module.php`
- `Modules/Role/database/seeders/RolesAndPermissionsSeeder.php`
- `Modules/Role/Models/*` or the canonical Role service selected by the project
- `tests/Feature/Account/AccountRoleImportTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** High

**Estimated Effort:** M

## P0-04 - Multiple User Models Break Role Morph and Ownership Consistency

**Issue**

Authentication uses `App\Models\User`, while Account CRUD uses `Modules\Account\Models\User`. Both map to `users`, but Spatie role pivots include `model_type`. The Account model also mixes a custom `accountRoles()` relation fixed to `App\Models\User` with trait methods that use its own morph class.

This workstream also covers the related findings:

- Super Admin checks can read a different role morph set.
- Account role display and Spatie `hasRole()` can disagree.
- Pivot cleanup filters `Modules\Account\Models\User` while existing pivots may use `App\Models\User`.
- Orphaned role/permission pivots can survive force deletion.
- Admin and Account code have competing ownership of the same user entity.

**Root Cause**

The Account module introduced a second aggregate root for an existing framework authentication model without a morph map, migration strategy, or shared contract.

**Business Impact**

Super Admin protections may fail unpredictably. Users can appear to have different roles in different screens, deleted accounts can retain authorization records, and role changes can target the wrong morph identity.

**Technical Impact**

Authentication, authorization, Account CRUD, Admin screens, relationships, and pivot cleanup do not share one model identity. This blocks reliable policies and creates cross-module data corruption risk.

**Proposed Solution**

- Select one canonical user model for the application.
- Recommended direction: retain `App\Models\User` as the authentication aggregate and move Account-specific relationships/casts onto it, while Account remains the domain service/UI owner.
- Alternatively, change the auth provider to the Account model only after auditing every user reference; do not maintain both concrete models.
- Define a stable morph map before migrating existing role/permission pivot `model_type` values.
- Migrate pivot rows transactionally and make the migration idempotent.
- Remove the custom relation once the canonical Spatie `roles` relationship is authoritative.
- Add architecture tests preventing a second concrete model from owning `users`.
- Add regression tests for login, role display, `hasRole`, Super Admin checks, assignment, deletion, and pivot cleanup.

**Files To Change**

- `app/Models/User.php`
- `Modules/Account/Models/User.php`
- `config/auth.php`
- `config/permission.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Models/EmployeeProfile.php`
- `Modules/Account/Models/CustomerProfile.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- `Modules/Account/Models/UserMeta.php`
- `Modules/ModuleServiceProvider.php` or `app/Providers/AppServiceProvider.php`
- `database/migrations/*_normalize_user_role_morph_types.php` (new)
- `tests/Feature/Account/CanonicalUserModelTest.php` (new)
- `tests/Feature/Account/UserRoleMorphMigrationTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** Critical

**Estimated Effort:** XL

## P0-05 - Super Admin Can Be Deactivated

**Issue**

`AccountService::toggleActive()` toggles any account without enforcing the Super Admin invariant.

**Root Cause**

The Super Admin guard was implemented only in delete paths. Account state transitions do not share a central domain rule.

**Business Impact**

An operator can lock out the highest-privilege recovery account, potentially causing an administrative outage.

**Technical Impact**

Protection varies by method and can be bypassed by any caller that invokes `toggleActive()`. Adding another state-changing entry point would likely repeat the defect.

**Proposed Solution**

- Define a domain invariant that prevents deactivation or destructive modification of protected accounts.
- Enforce it in the service/policy layer, not only in Blade or Livewire.
- Decide whether a protected account may edit itself and whether at least one active Super Admin must always remain.
- Add concurrency-aware tests for the last-active-Super-Admin rule if adopted.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- Canonical user model selected by P0-04
- Account policy selected by P0-02
- `Modules/Account/Livewire/Accounts/Index.php`
- `tests/Feature/Account/SuperAdminProtectionTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P0-06 - Identity Documents Are Public and Uploads Are Unvalidated

**Issue**

The form accepts front/back identity images and portrait images without validation rules, stores them on the `public` disk, and renders them with `Storage::url()`.

This item covers both analysis findings:

- Missing server-side type/MIME/size validation.
- Sensitive identity documents are publicly addressable.

**Root Cause**

The upload UI was implemented before a data-classification and file-access policy. Browser `accept="image/*"` was treated as validation, and the public disk was used for convenience.

**Business Impact**

Sensitive identity and tax documents can be exposed, replaced with unexpected files, retained indefinitely, or accessed without record-level authorization. This creates privacy, compliance, and fraud risk.

**Technical Impact**

The module lacks a trusted upload boundary, private delivery endpoint, ownership checks, antivirus/content inspection strategy, and file lifecycle management.

**Proposed Solution**

- Add Livewire 3 validation for actual image content, MIME type, extension, dimensions where relevant, and maximum size.
- Store identity documents on a private disk with non-guessable generated names.
- Add an authorized preview/download controller or signed short-lived delivery mechanism.
- Check both broad permission and target-record authorization before delivery.
- Keep metadata paths server-controlled.
- Add tests for invalid MIME, oversized files, unauthorized download, and successful authorized retrieval.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/resources/views/livewire/accounts/form.blade.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- `Modules/Account/routes/web.php`
- `Modules/Account/Http/Controllers/IdentityDocumentController.php` (new)
- `config/filesystems.php`
- `tests/Feature/Account/IdentityDocumentSecurityTest.php` (new)

**Estimated Risk:** Critical

**Estimated Complexity:** High

**Estimated Effort:** L

# P1 Important

## P1-01 - Identity and Tax Fields Are Not Validated

**Issue**

The form exposes `identity_type`, `identity_number`, `issued_date`, `issued_place`, `tax_code`, `tax_registered_name`, `tax_address`, and `identity_note`, but none are included in `Form::rules()`.

**Root Cause**

The Blade form and model persistence were extended after the initial Livewire rules were written.

**Business Impact**

Invalid or malformed identity and tax data reduces trust in customer/employee records and can break downstream reporting or compliance workflows.

**Technical Impact**

Blade error blocks are ineffective, service callers receive no invariant enforcement, and database values can exceed intended formats or contain unsupported enum values.

**Proposed Solution**

- Add conditional Livewire rules for identity type, number, issue date/place, tax fields, and notes.
- Use Laravel `Rule::in`, length limits, normalized nullable values, and date bounds.
- Add service-level DTO/invariant validation so imports and future API callers use the same contract.
- Define conditional requirements, for example requiring an identity number when an identity type is selected.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- `Modules/Account/resources/views/livewire/accounts/form.blade.php`
- `tests/Feature/Account/AccountIdentityValidationTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-02 - Profile Codes Lack Application-Level Uniqueness Validation

**Issue**

`employee_code` and `customer_code` are unique in the database but are not validated as unique in the Livewire form.

**Root Cause**

The implementation relies on database exceptions as user-facing validation.

**Business Impact**

Users receive failed saves instead of actionable feedback, and duplicate-code attempts can interrupt account onboarding.

**Technical Impact**

Constraint violations escape the normal validation flow. Update rules also need to ignore the current profile record, which is not currently modeled in the form validation.

**Proposed Solution**

- Add conditional `Rule::unique()` checks for each profile table.
- Ignore the current user's profile during update.
- Retain database unique constraints as the final concurrency boundary.
- Catch duplicate-key races and translate them into a domain validation response.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/database/migrations/2026_05_26_143725_employee_profiles.php`
- `Modules/Account/database/migrations/2026_05_26_143744_customer_profiles.php`
- `tests/Feature/Account/AccountProfileCodeValidationTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-03 - Password Validation Does Not Use Laravel's Password Policy

**Issue**

Passwords are validated with only `string|min:8|confirmed`.

**Root Cause**

The form uses a local fixed rule rather than Laravel's reusable `Password` rule or an application-wide password policy.

**Business Impact**

Weak passwords increase account takeover risk, especially for admin-capable employee accounts.

**Technical Impact**

Password requirements can drift between registration, Account administration, password reset, and import.

**Proposed Solution**

- Define one application password policy using `Illuminate\Validation\Rules\Password`.
- Reuse it in Account create/update and any other password entry points.
- Decide whether imported accounts may receive passwords; prefer invitation/reset flows over workbook plaintext passwords.
- Never export passwords.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Services/AccountImportService.php`
- `app/Providers/AppServiceProvider.php` or a dedicated security configuration file
- Related authentication password forms using the shared policy
- `tests/Feature/Account/AccountPasswordPolicyTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-04 - Active-Status Filter Is Broken

**Issue**

`Index::filters()` sends `is_active`, but `AccountService::paginateForAdmin()` looks for `status`.

**Root Cause**

Filtering logic was duplicated across multiple query methods and parameter names diverged.

**Business Impact**

Administrators cannot reliably isolate active or inactive accounts, making account audits and cleanup inaccurate.

**Technical Impact**

The UI appears functional while silently returning incorrect results. The same filter duplication affects list, export, and bulk selection consistency.

**Proposed Solution**

- Define a typed Account filter object or a single normalized array contract.
- Centralize query application in one method/query object.
- Use the same filtered query for pagination, selected IDs, and export.
- Add tests asserting identical filter results across all consumers.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Data/AccountFilters.php` (new, if DTO approach is selected)
- `tests/Feature/Account/AccountFilteringTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-05 - Import File-Type Contract Is Inconsistent

**Issue**

The file input accepts `.xlsx,.csv`, while Livewire validation permits `.xlsx,.xls`. The active importer is designed around a multi-sheet workbook, which CSV cannot represent.

**Root Cause**

UI attributes, validation rules, and importer capabilities were changed independently.

**Business Impact**

Users can select files that are later rejected or structurally impossible to import, creating failed onboarding and support work.

**Technical Impact**

Client hints and server validation disagree. Supporting legacy XLS also needs explicit package compatibility verification.

**Proposed Solution**

- Choose the supported format based on the final import contract.
- For the current multi-sheet design, prefer XLSX only unless XLS is explicitly tested.
- Align Blade `accept`, Livewire rules, service validation, documentation, and template generation.
- Validate file signatures/content, not only extensions.

**Files To Change**

- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Services/AccountImportService.php`
- Import template/documentation introduced by P1-10
- `tests/Feature/Account/AccountImportFileValidationTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** Low

**Estimated Effort:** S

## P1-06 - Account-Type Vocabulary Is Inconsistent

**Issue**

The importer allows `collaborator`, while the form, service profile synchronization, and migration define only `employee` and `customer`.

**Root Cause**

Account types are represented as repeated string literals without a canonical enum or database-level domain definition.

**Business Impact**

Imported collaborators can exist without the expected profile behavior, creating incomplete records and ambiguous business ownership.

**Technical Impact**

Conditional validation and `syncProfile()` do not handle the third state. Queries and reports may silently exclude or misclassify it.

**Proposed Solution**

- Decide the supported business account types.
- Represent them with a PHP backed enum or centralized value object.
- Reuse that enum in Livewire validation, import validation, service branching, casts, and tests.
- If `collaborator` is required, design its profile and migration explicitly before accepting it.

**Files To Change**

- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Services/AccountService.php`
- Canonical user model selected by P0-04
- `Modules/Account/database/migrations/2026_05_26_143653_update_users_for_account.php`
- `Modules/Account/Enums/AccountType.php` (new)
- `tests/Unit/Account/AccountTypeTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-07 - Multi-Sheet Import Fields Do Not Match the Schema

**Issue**

The active importer reads/writes fields that do not exist in Account models or migrations:

- Employee: `hire_date`, `avatar_4x6_path` instead of `joined_date` and the identity portrait field.
- Customer: `customer_group`, `company_name`, and `tax_code` are absent from `customer_profiles`.
- Identity: `full_name`, `front_image_path`, and `back_image_path` do not match the identity model/schema.
- User: `note` is not a fillable or migrated `users` field.

**Root Cause**

The workbook specification was created against a different or earlier schema and was not versioned or tested against the migrations.

**Business Impact**

Imports can silently discard data, fail mid-run, or produce incomplete customer and employee records. Users cannot trust import reports as evidence that all supplied data was persisted.

**Technical Impact**

Mass assignment filters unknown fields, while some schema mismatches can cause constraint failures. Validation currently approves fields that persistence cannot store.

**Proposed Solution**

- Define a versioned workbook schema from the canonical domain model.
- Map every column explicitly to a real field or approved transformation.
- Remove unsupported columns or add intentional migrations after business approval.
- Validate normalized payloads against service DTOs before persistence.
- Add fixture tests for every sheet and every mapped column.
- Reject unknown headers for strict templates, or report them clearly.

**Files To Change**

- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Models/EmployeeProfile.php`
- `Modules/Account/Models/CustomerProfile.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- Canonical user model selected by P0-04
- `Modules/Account/database/migrations/2026_05_26_143725_employee_profiles.php`
- `Modules/Account/database/migrations/2026_05_26_143744_customer_profiles.php`
- `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php`
- `Modules/Account/Data/Import/*` (new DTO/mapping classes)
- `tests/Feature/Account/AccountMultiSheetImportTest.php` (new)
- `tests/Fixtures/Account/*` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-08 - Legacy Single-Sheet Import Has Weak Validation

**Issue**

`AccountService::importFromExcel()` silently skips blank emails and performs minimal validation for dates, codes, states, and duplicate constraints.

**Root Cause**

The legacy importer predates the structured import-report service and remains in the general CRUD service without a dedicated contract.

**Business Impact**

If reused, it can partially normalize bad business data or fail with opaque database exceptions.

**Technical Impact**

There are two validation standards in the same module. Dead or forgotten entry points increase the chance that future code chooses the unsafe path.

**Proposed Solution**

- Confirm that the method has no dynamic caller.
- Remove it after the canonical import workflow is covered by tests.
- If backward compatibility is required, make it an adapter into the same normalized import service rather than a separate implementation.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Services/AccountImportService.php`
- `tests/Feature/Account/AccountImportCompatibilityTest.php` (new if compatibility is retained)

**Estimated Risk:** Medium

**Estimated Complexity:** Medium

**Estimated Effort:** S-M

## P1-09 - Export and Import Formats Cannot Round-Trip

**Issue**

The active export creates one worksheet with Vietnamese display headers. The active import requires five worksheets with English snake-case headers.

**Root Cause**

Export was designed as a human-readable report, while import was designed as a separate bulk-provisioning format. Both are presented under one Import/Export UI without distinct contracts or templates.

**Business Impact**

Administrators cannot export, edit, and re-import account data. This causes manual transformations and increases data-entry errors.

**Technical Impact**

There is no canonical schema version, template endpoint, compatibility test, or stable header mapping.

**Proposed Solution**

- Separate "report export" from "round-trip data export" if both are required.
- Define a versioned multi-sheet template and export mode that exactly matches the importer.
- Add metadata such as template version without trusting it for authorization.
- Provide a generated template and field documentation.
- Add an automated export-import round-trip test.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `tests/Feature/Account/AccountImportExportRoundTripTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-10 - Account Import/Export Bypasses the Shared Framework

**Issue**

Account maintains custom import/export flows instead of integrating with `Modules/Shared/Services/ImportExport/BaseImportExportService.php` and `Modules/Shared/Livewire/ImportExport/Panel.php`.

**Root Cause**

The shared abstraction currently targets a simpler single-sheet/model workflow, while Account requires multi-entity, multi-sheet orchestration. Account solved the gap locally rather than extending the shared contract.

**Business Impact**

Import behavior, reports, file cleanup, dry runs, and validation differ across modules, increasing training and operational support costs.

**Technical Impact**

Header normalization, error reporting, storage, transaction handling, and UI behavior are duplicated. Fixes in the shared implementation do not benefit Account.

**Proposed Solution**

- Extend the shared import/export architecture with a multi-sheet orchestration contract rather than forcing Account into a single-model base class.
- Reuse shared concerns for file validation, normalized reports, storage, cleanup, dry-run behavior, and audit logging.
- Keep Account-specific sheet mapping and cross-entity transaction logic inside Account.
- Avoid passing arbitrary service-class names from the browser for sensitive operations.

**Files To Change**

- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `Modules/Shared/Services/ImportExport/BaseImportExportService.php`
- `Modules/Shared/Livewire/ImportExport/Panel.php`
- `Modules/Shared/Services/ImportExport/*` (new multi-sheet contract/concerns)
- `tests/Feature/Shared/MultiSheetImportExportTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Critical

**Estimated Effort:** XL

## P1-11 - Sensitive Export Files Are Public and Retained

**Issue**

Account exports are written under `storage/app/public/exports`, then downloaded with `deleteFileAfterSend(false)`.

**Root Cause**

Exports use the public disk as a convenient temporary location and have no retention policy.

**Business Impact**

Files containing names, emails, phones, employee data, and addresses can remain accessible and accumulate indefinitely.

**Technical Impact**

The module has no private export storage, ownership metadata, expiry, cleanup job, or authorized re-download flow.

**Proposed Solution**

- Store exports on a private temporary disk.
- Stream directly where practical, or delete after a successful response.
- For queued exports, record owner, permission, expiry, status, and a server-generated identifier.
- Add scheduled cleanup and access auditing.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `config/filesystems.php`
- `app/Console/Kernel.php` or `routes/console.php`
- `Modules/Account/Jobs/ExportAccounts.php` (new if queued)
- `tests/Feature/Account/AccountExportSecurityTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-12 - Imported Workbooks Are Not Deleted

**Issue**

Uploaded workbooks are stored in `storage/app/imports/account` and remain after success or failure.

**Root Cause**

The Livewire action stores a durable copy but does not use a `finally` cleanup path or a retention policy.

**Business Impact**

Personal data and potentially plaintext temporary passwords remain on disk longer than necessary.

**Technical Impact**

Storage grows without bounds and failed imports leave sensitive diagnostic artifacts unmanaged.

**Proposed Solution**

- Prefer the temporary uploaded file path when synchronous processing is safe.
- If persistence is needed for queued processing, store privately with owner and expiry metadata.
- Delete files in a reliable cleanup path after processing.
- Add scheduled cleanup for abandoned files.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Services/AccountImportService.php`
- `config/filesystems.php`
- `routes/console.php`
- `tests/Feature/Account/AccountImportFileLifecycleTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-13 - Raw Exception Messages Are Exposed

**Issue**

Caught exception messages are included in the import report and flashed directly to the browser.

**Root Cause**

Internal diagnostic details and user-facing error messages share the same channel.

**Business Impact**

Users may see database names, SQL errors, filesystem paths, or package details. This leaks implementation information and creates confusing support messages.

**Technical Impact**

Errors are not consistently categorized, redacted, correlated, or logged with structured context.

**Proposed Solution**

- Return stable, translated domain error messages to users.
- Log the original exception with a correlation ID and redacted context.
- Keep row-level validation errors, but replace system exceptions with a generic failure reason.
- Follow the project-wide error-handling direction in `ROADMAP.md` P1-12.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Exceptions/*` (new)
- Logging configuration if correlation/redaction support is introduced
- `tests/Feature/Account/AccountImportErrorDisclosureTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-14 - API Route Is Public and Calls a Missing Method

**Issue**

`GET /api/account` is active without Sanctum middleware and points to an empty controller with no `index()` method.

**Root Cause**

Scaffold code was left enabled while the secure route block was commented out.

**Business Impact**

The endpoint currently fails. If implemented later without revisiting middleware, it could expose account data publicly.

**Technical Impact**

Route boot succeeds but request execution fails with a controller method error. The dead endpoint also creates misleading API surface.

**Proposed Solution**

- Remove the route and controller if no Account API is currently required.
- If required, implement it deliberately with Sanctum, capability authorization, pagination, API resources, field minimization, and rate limiting.
- Do not expose identity/tax fields through the list endpoint by default.

**Files To Change**

- `Modules/Account/routes/api.php`
- `Modules/Account/Http/Controllers/Api/AccountController.php`
- `Modules/Account/Http/Resources/AccountResource.php` (new if API retained)
- `Modules/Account/config/module.php`
- `tests/Feature/Account/AccountApiTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Low if removed; High if implemented

**Estimated Effort:** XS or L

## P1-15 - Identity Number and Tax-Code Uniqueness Is Undefined

**Issue**

Identity number and tax code are indexed but not unique, so different users can store duplicate identifiers.

**Root Cause**

The migration optimizes lookup without documenting the business uniqueness rule.

**Business Impact**

Duplicate identities can enable duplicate customer records, tax-reporting errors, and fraud or compliance problems.

**Technical Impact**

Application validation cannot be made authoritative until null handling, identity type scope, historical records, and soft-delete behavior are defined.

**Proposed Solution**

- Obtain a business decision for uniqueness scope:
  - global identity number,
  - identity type plus number,
  - tax code,
  - and whether soft-deleted records reserve values.
- Audit existing duplicates before adding constraints.
- Add normalized columns or indexes if formatting differences must be ignored.
- Add matching application validation and import checks.

**Files To Change**

- `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php`
- New corrective migration under `Modules/Account/database/migrations`
- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- `tests/Feature/Account/IdentityUniquenessTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-16 - File Writes Occur Outside the Database Transaction

**Issue**

Identity images are stored before `AccountService::create()` or `update()` begins its database transaction. A later database failure leaves orphan files.

**Root Cause**

Livewire owns file persistence while the service owns database persistence, with no shared unit-of-work or compensating cleanup.

**Business Impact**

Failed account saves can leave sensitive files on disk without a corresponding record or owner.

**Technical Impact**

Filesystem and database state are not atomic. Laravel database rollback cannot undo storage writes.

**Proposed Solution**

- Move file lifecycle orchestration into an application service.
- Stage uploads under a temporary private path.
- Commit database changes, then promote files, or register compensating cleanup for failures.
- Preserve old files until the new database state is confirmed.
- Test database failure after upload.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Services/IdentityDocumentService.php` (new)
- `config/filesystems.php`
- `tests/Feature/Account/IdentityFileTransactionTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-17 - Replaced and Deleted Identity Files Are Not Cleaned Up

**Issue**

Uploading a replacement does not remove the previous file, and account deletion does not remove identity files.

**Root Cause**

Only the new path is persisted. The module has no file ownership aggregate, replacement workflow, or deletion hook/service.

**Business Impact**

Obsolete identity documents remain accessible and retained, conflicting with privacy expectations and storage-retention policies.

**Technical Impact**

Storage leaks over time. Record deletion does not represent complete data deletion.

**Proposed Solution**

- Track old paths during update and delete them only after successful persistence.
- Centralize file cleanup in the identity document service introduced by P1-16.
- Decide retention/audit requirements before permanent deletion.
- Add reconciliation tooling for existing orphan files.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Services/IdentityDocumentService.php` (new)
- `Modules/Account/Models/UserIdentityProfile.php`
- `tests/Feature/Account/IdentityFileCleanupTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-18 - Soft Delete and Force Delete Semantics Conflict

**Issue**

Profile rows are soft-deleted, then the user is force-deleted. Foreign-key cascades can permanently remove the same profiles, defeating recovery semantics.

**Root Cause**

Soft deletion was added to models, while service deletion was designed as permanent deletion with manual cleanup.

**Business Impact**

Deleted account data may be unrecoverable even though the schema suggests recovery. This can surprise administrators and complicate audit/legal retention.

**Technical Impact**

Model traits, service behavior, and foreign-key actions express conflicting lifecycle rules.

**Proposed Solution**

- Choose one lifecycle:
  - soft-delete user and related profiles for recoverability, or
  - permanently purge all related records and private files through an explicit purge operation.
- Separate "deactivate", "archive", and "purge" if business needs differ.
- Use model/service events carefully; keep destructive behavior explicit and testable.
- Document retention and restoration behavior.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- Canonical user model selected by P0-04
- `Modules/Account/Models/EmployeeProfile.php`
- `Modules/Account/Models/CustomerProfile.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- Account migrations defining foreign keys
- `tests/Feature/Account/AccountDeletionLifecycleTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-19 - Bulk Delete Uses a Long Transaction and Repeated Queries

**Issue**

`bulkDelete()` loads all selected users and performs repeated profile, pivot, and delete queries inside one transaction.

**Root Cause**

Single-account deletion logic was copied into a collection callback instead of being designed as a bounded bulk workflow.

**Business Impact**

Large bulk deletion can time out, lock account tables, or fail as one large unit after substantial work.

**Technical Impact**

Transaction duration grows with selection size. Repeated queries increase database load, and the current UI permits selecting all matching records.

**Proposed Solution**

- Define a maximum synchronous batch size.
- Reuse one canonical deletion operation rather than duplicate statements.
- For large requests, queue chunks with an operation record and progress reporting.
- Preserve all authorization and protected-account checks in each chunk.
- Make retries idempotent.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Jobs/BulkDeleteAccounts.php` (new if queued)
- `Modules/Account/Models/AccountOperation.php` and migration (new if operation tracking is adopted)
- `tests/Feature/Account/AccountBulkDeleteTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-20 - Identity Import Lookup Conflicts With Database Cardinality

**Issue**

Identity import uses `user_id`, `identity_type`, and `identity_number` as the `updateOrCreate` lookup, while the table allows only one row per `user_id`.

**Root Cause**

The importer models identity records as potentially multiple per user, while the migration models them as one-to-one.

**Business Impact**

Changing a user's identity number during import can fail the entire workbook.

**Technical Impact**

Eloquent lookup semantics and the unique constraint are incompatible.

**Proposed Solution**

- Confirm one-to-one identity profile ownership.
- If one-to-one remains correct, use `user_id` as the update key.
- If multiple identities are required, redesign the migration, model relationships, form, and uniqueness constraints together.
- Add tests for identity number changes and repeated imports.

**Files To Change**

- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- Canonical user model selected by P0-04
- `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php`
- `tests/Feature/Account/IdentityImportCardinalityTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-21 - Import Loads and Validates the Entire Workbook in Memory

**Issue**

The importer materializes all sheets and validates every row before persistence.

**Root Cause**

The all-or-nothing validation/report design uses collections for simplicity and has no file-size/row-count workload policy.

**Business Impact**

Large account imports can exhaust PHP memory, time out web requests, and leave users without reliable progress.

**Technical Impact**

The synchronous Livewire request performs CPU, memory, database, and file work in one process. Transaction benefits do not protect against pre-transaction memory failure.

**Proposed Solution**

- Set explicit row and file-size limits for synchronous imports.
- Queue larger imports.
- Parse sheets in chunks where the library supports it, or use a streaming-capable reader.
- Preload reference data in bounded maps.
- Store progress and a downloadable error report.
- Decide whether strict all-or-nothing semantics are required or whether validated chunk commits are acceptable.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Jobs/ImportAccounts.php` (new)
- `Modules/Account/Models/AccountImport.php` and migration (new if job tracking is adopted)
- `config/queue.php`
- `tests/Feature/Account/QueuedAccountImportTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Critical

**Estimated Effort:** XL

## P1-22 - Account-Type Migration Is Not Reversible

**Issue**

The migration adding `users.account_type` has an empty `down()` method.

**Root Cause**

Rollback behavior was left unfinished.

**Business Impact**

Deployment rollback can leave schema and code versions incompatible.

**Technical Impact**

Migration smoke tests cannot prove reversible deployment. A failed release may require manual database intervention.

**Proposed Solution**

- Add a safe rollback that drops `account_type`.
- Before rollback, account for dependent code and profile data.
- Add migration tests for fresh install and rollback on the supported production database.

**Files To Change**

- `Modules/Account/database/migrations/2026_05_26_143653_update_users_for_account.php`
- `tests/Feature/Account/AccountMigrationTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Low

**Estimated Effort:** S

## P1-23 - Role Display Causes N+1 Queries

**Issue**

The list eager-loads `roles`, but the view reads `accountRoles`, and `isSuperAdmin()` queries `accountRoles()` for each row.

**Root Cause**

The duplicate user-model workaround introduced a second role relationship that is not the relationship being eager-loaded.

**Business Impact**

Account-list response time degrades as page size increases, particularly for `All`.

**Technical Impact**

The view can execute one additional role query per account. Query behavior is coupled to helper methods.

**Proposed Solution**

- Resolve P0-04 first.
- Use the canonical Spatie `roles` relationship.
- Determine Super Admin status from the already-loaded collection or an efficient query scope.
- Add a query-count test for the list page.

**Files To Change**

- Canonical user model selected by P0-04
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `tests/Feature/Account/AccountListQueryCountTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-24 - Account List Eager-Loads Unused Identity Data

**Issue**

`paginateForAdmin()` loads `identityProfiles`, but the list view does not display identity data.

**Root Cause**

The list query was expanded using the detail model graph without measuring the view's actual data requirements.

**Business Impact**

Administrators experience slower lists and higher memory consumption for no user-visible benefit.

**Technical Impact**

Every page performs extra query/hydration work and loads sensitive fields into memory unnecessarily.

**Proposed Solution**

- Select only the relationships and columns used by the list.
- Keep identity data in the authorized edit/detail flow.
- Add query and payload-size regression tests.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `tests/Feature/Account/AccountListQueryCountTest.php` (new)

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** S

## P1-25 - Unbounded "All" Listing Can Exhaust Livewire

**Issue**

Selecting `All` returns every matching user and eager-loaded relationship in one Livewire render.

**Root Cause**

Pagination options expose an unbounded collection mode without a server-side maximum.

**Business Impact**

Large account datasets can make the administration page slow or unavailable.

**Technical Impact**

The request consumes unbounded database rows, PHP memory, serialization time, HTML size, and browser memory.

**Proposed Solution**

- Remove `All` from interactive page-size options.
- Set a server-side maximum regardless of client input.
- Provide export as the supported full-dataset workflow.
- Validate `$perPage` against an allowlist in Livewire.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `Modules/Account/Services/AccountService.php`
- `tests/Feature/Account/AccountPaginationLimitTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Low

**Estimated Effort:** S

## P1-26 - Select-All Loads Every Matching Account

**Issue**

Select-all calls `getDeletableIds()`, which loads every matching user and role relation, filters in PHP, and stores all IDs in Livewire state.

**Root Cause**

The UI models "select all matching records" as a literal browser-side ID array.

**Business Impact**

Large datasets make bulk selection slow and unreliable and increase accidental mass-deletion risk.

**Technical Impact**

Database, server memory, Livewire payload size, and browser state all grow with total result count.

**Proposed Solution**

- Keep page selection bounded to the current page, or represent "all matching" as a server-side operation filter.
- Require a second confirmation showing the affected count.
- Execute large bulk operations through a queued operation.
- Apply protected-account filtering in SQL using the canonical role relation.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `Modules/Account/Services/AccountService.php`
- `tests/Feature/Account/AccountBulkSelectionTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-27 - Export Loads the Full Dataset Before Writing

**Issue**

`exportRows()` calls `get()->map()`, materializing all users and profiles before FastExcel writes the file.

**Root Cause**

The export API is collection-based rather than generator/lazy-query based.

**Business Impact**

Large exports can fail, time out, or impact other admin users.

**Technical Impact**

Memory usage scales with all selected rows. The web request remains occupied for the full export duration.

**Proposed Solution**

- Use a generator or lazy collection compatible with FastExcel.
- Queue large exports and notify the initiating user when complete.
- Reuse the canonical filtered query without hydrating unused fields.
- Apply private storage and expiry from P1-11.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Jobs/ExportAccounts.php` (new)
- `tests/Feature/Account/QueuedAccountExportTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-28 - Import Performs Repeated Per-Row User Queries

**Issue**

Validation and persistence repeatedly call user lookups through `emailExistsInFileOrDatabase()`, `getAccountType()`, `findUserByEmail()`, and Super Admin checks.

**Root Cause**

Each validation method operates independently and does not share a preloaded import context.

**Business Impact**

Import duration grows rapidly with workbook size and can overload the database.

**Technical Impact**

The importer exhibits query multiplication across sheets and phases.

**Proposed Solution**

- Normalize all relevant emails first.
- Load existing users, account types, and protected-role status in bounded queries.
- Build an immutable import context keyed by normalized email.
- Reuse the context during validation and persistence, refreshing only when newly created users require IDs.
- Add query-count tests for representative workbook sizes.

**Files To Change**

- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Data/Import/AccountImportContext.php` (new)
- Canonical user model selected by P0-04
- `tests/Feature/Account/AccountImportQueryCountTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-29 - Role Import Performs Per-Row Role Queries

**Issue**

Role import calls `firstOrCreate()` and role checks for every row.

**Root Cause**

Roles are resolved during persistence rather than validated and preloaded as controlled reference data.

**Business Impact**

Large imports are slower, and the current behavior compounds the privilege-escalation risk.

**Technical Impact**

The importer performs repeated reads/writes and can create authorization metadata inside the account transaction.

**Proposed Solution**

- Resolve P0-03 first by forbidding role creation.
- Preload the approved role map once.
- Validate all role references before any account write.
- Assign roles through the canonical user model and explicit role-sync policy.

**Files To Change**

- `Modules/Account/Services/AccountImportService.php`
- Canonical user model selected by P0-04
- Canonical Role service/model selected by P0-03
- `tests/Feature/Account/AccountRoleImportQueryTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-30 - Query Construction Is Duplicated Across Service Methods

**Issue**

`paginate()`, `paginateForAdmin()`, `getDeletableIds()`, and `exportRows()` repeat search, account-type, active-state, ordering, and relationship logic. `paginate()` and `paginateForAdmin()` are competing list implementations.

**Root Cause**

Each use case built its own Eloquent query instead of sharing a query object/scope and normalized filters.

**Business Impact**

Lists, exports, and bulk operations can return different account sets for the same visible filters.

**Technical Impact**

The current `status`/`is_active` defect demonstrates drift. Every new filter must be implemented in multiple places.

**Proposed Solution**

- Introduce one Account query object or private query builder accepting a typed filter DTO.
- Let callers add only use-case-specific selects/eager loads.
- Remove the unused duplicate pagination method after reference verification.
- Test list/export/selection parity.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Queries/AccountQuery.php` (new)
- `Modules/Account/Data/AccountFilters.php` (new)
- `tests/Feature/Account/AccountQueryParityTest.php` (new)

**Estimated Risk:** Medium

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-31 - Single and Bulk Delete Duplicate Destructive Logic

**Issue**

`delete()` and `bulkDelete()` repeat profile deletion, role/permission pivot deletion, protected-account checks, and force deletion.

**Root Cause**

Bulk deletion copied the single-delete implementation rather than delegating to a shared domain operation.

**Business Impact**

Security or cleanup fixes can be applied to one path and missed in the other.

**Technical Impact**

Destructive behavior has two maintenance points and is already entangled with incorrect morph types.

**Proposed Solution**

- Create one internal account deletion/purge operation used by single and bulk workflows.
- Keep transaction ownership explicit: one transaction for single delete, bounded chunk transactions for bulk.
- Centralize protected-account checks, pivot handling, profile lifecycle, and file cleanup.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Actions/DeleteAccount.php` (new)
- `Modules/Account/Actions/PurgeAccount.php` (new if archive/purge are separated)
- `tests/Feature/Account/AccountDeletionParityTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** High

**Estimated Effort:** L

## P1-32 - Identity Relationship Cardinality Is Contradictory

**Issue**

The user model defines both `identityProfile()` as has-one and `identityProfiles()` as has-many, while the migration enforces unique `user_id`.

**Root Cause**

The model evolved toward multiple identity records without a corresponding schema decision.

**Business Impact**

Developers cannot tell whether users may have one document profile or multiple identities, leading to inconsistent features and imports.

**Technical Impact**

Queries eager-load the wrong relationship, import lookup keys conflict with the schema, and future code may incorrectly append records.

**Proposed Solution**

- Make a business decision on cardinality.
- Current form and schema indicate one-to-one; if retained, remove `identityProfiles()` and use `identityProfile()` consistently.
- If multiple identities are required, redesign the UI, migration, unique indexes, import template, and service APIs together.

**Files To Change**

- Canonical user model selected by P0-04
- `Modules/Account/Models/UserIdentityProfile.php`
- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php`
- `tests/Feature/Account/IdentityProfileCardinalityTest.php` (new)

**Estimated Risk:** High

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P1-33 - Account Module Lacks Regression and Integration Coverage

**Issue**

The analysis found no meaningful Account-specific automated coverage for authorization, Livewire actions, role morphs, imports, exports, files, transactions, or migrations.

**Root Cause**

The module grew through UI and service implementation without a test harness scaled to its security and data-integrity risk.

**Business Impact**

Fixing the module is dangerous because regressions in login, permissions, account deletion, or imports may reach production unnoticed.

**Technical Impact**

The canonical-model migration and import rewrite cannot be verified confidently. Query and security regressions have no merge gate.

**Proposed Solution**

- Build tests alongside each P0/P1 work item rather than as a final batch.
- Cover:
  - route and Livewire authorization,
  - canonical user roles/morph types,
  - protected accounts,
  - form validation,
  - private file access/lifecycle,
  - transaction rollback,
  - import fixtures and privilege rejection,
  - export/import round trip,
  - query-count budgets,
  - migration up/down.
- Add Account tests to CI according to `ROADMAP.md` P1-10.

**Files To Change**

- `tests/Feature/Account/*` (new)
- `tests/Unit/Account/*` (new)
- `phpunit.xml`
- CI workflow files used by the repository
- Account source files touched by the individual work items

**Estimated Risk:** High

**Estimated Complexity:** Critical

**Estimated Effort:** XL, delivered incrementally

# P2 Nice to Have

## P2-01 - Date Fields Lack Domain Bounds

**Issue**

Joined date, birthday, and issued date accept any parseable date, including unreasonable future dates.

**Root Cause**

Validation checks syntax but not business meaning.

**Business Impact**

Reports can contain impossible employment, age, or identity-document timelines.

**Technical Impact**

Invalid temporal data propagates into exports and future analytics.

**Proposed Solution**

- Define business bounds for each date.
- Use Laravel date rules and custom messages.
- Apply identical rules to form and import DTOs.

**Files To Change**

- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Data/*` validation objects introduced by P1 work
- `tests/Feature/Account/AccountDateValidationTest.php` (new)

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** S

## P2-02 - Toggle Uses a Non-Atomic Read/Write Sequence

**Issue**

`toggleActive()` reads the current value and writes its inverse without optimistic locking.

**Root Cause**

The method models state change as a UI toggle rather than an explicit command to activate or deactivate.

**Business Impact**

Near-simultaneous actions can leave an unexpected final state.

**Technical Impact**

Retries are not idempotent, and concurrent requests can overwrite each other.

**Proposed Solution**

- Prefer explicit `activate` and `deactivate` commands with desired-state semantics.
- Include protected-account invariants from P0-05.
- Use an atomic update or version/timestamp check where concurrency matters.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/resources/views/livewire/accounts/index.blade.php`
- `tests/Feature/Account/AccountActivationTest.php` (new)

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** S

## P2-03 - Leading-Wildcard Search Does Not Scale

**Issue**

Search uses `%term%` against name, email, and phone.

**Root Cause**

The query prioritizes flexible substring matching without an index/search strategy.

**Business Impact**

Search latency increases as the account table grows.

**Technical Impact**

Ordinary B-tree indexes cannot efficiently serve leading-wildcard predicates.

**Proposed Solution**

- Measure real account volume and query plans first.
- Normalize exact/prefix search for email and phone.
- Consider full-text or a dedicated search service only if data volume justifies it.
- Debounce Livewire input and enforce a minimum search length.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Queries/AccountQuery.php` (new under P1-30)
- `Modules/Account/Livewire/Accounts/Index.php`
- New migration for approved indexes if required
- `tests/Feature/Account/AccountSearchTest.php` (new)

**Estimated Risk:** Low

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P2-04 - Empty Scaffold Model Appears Unused

**Issue**

`Modules/Account/Models/Account.php` is an empty model, has no references, and implies an `accounts` table that does not exist.

**Root Cause**

The module generator created a placeholder model that was never adopted.

**Business Impact**

Minimal direct impact, but the class misleads developers about the domain aggregate and table ownership.

**Technical Impact**

Accidental use would query a nonexistent `accounts` table.

**Proposed Solution**

- Confirm no dynamic reference after route/component tests exist.
- Remove the model rather than repurposing it ambiguously.

**Files To Change**

- `Modules/Account/Models/Account.php`
- Any documentation or generated catalog referencing it

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** XS

## P2-05 - Scaffold Page and Placeholder Views Appear Unused

**Issue**

The generic Account page and placeholder views are not connected to active routes/components.

**Root Cause**

Initial module scaffolding remained after real pages were added.

**Business Impact**

No direct runtime impact.

**Technical Impact**

Dead files increase navigation noise and can be mistaken for active UI.

**Proposed Solution**

- Confirm no dynamic references after route/view tests.
- Remove the scaffold page and placeholders together.

**Files To Change**

- `Modules/Account/resources/views/account.blade.php`
- `Modules/Account/resources/views/components/placeholder.blade.php`
- `Modules/Account/resources/views/livewire/placeholder.blade.php`

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** XS

## P2-06 - Empty Module README Provides No Contract

**Issue**

`Modules/Account/readme.md` is empty.

**Root Cause**

Documentation scaffolding was created but not maintained.

**Business Impact**

New developers lack a concise module ownership and workflow reference.

**Technical Impact**

Important decisions remain discoverable only by reading source and analysis documents.

**Proposed Solution**

- Either remove the empty file or replace it with a short module contract linking to:
  - `docs/modules/Account/ANALYSIS.md`
  - `docs/modules/Account/REFACTOR_PLAN.md`
  - the final import template specification
  - authorization and ownership rules.

**Files To Change**

- `Modules/Account/readme.md`
- `docs/modules/Account/ANALYSIS.md` if links are added
- `docs/modules/Account/REFACTOR_PLAN.md` if links are added later

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** XS

## P2-07 - Unused Service Methods Increase Ambiguity

**Issue**

`AccountService::paginate()` and `AccountService::importFromExcel()` have no active caller and overlap active implementations.

**Root Cause**

Old code paths were retained during iterative development.

**Business Impact**

No immediate runtime impact, but future developers may accidentally call obsolete behavior.

**Technical Impact**

The service API advertises unsupported alternatives and increases test/refactor scope.

**Proposed Solution**

- Verify no dynamic service calls.
- Remove `paginate()` after P1-30 centralizes queries.
- Remove or adapt `importFromExcel()` after P1-08 through P1-10 establish the canonical import path.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- Account tests and documentation referencing the canonical methods

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** S

## P2-08 - Relationship and Service Return Types Are Incomplete

**Issue**

Several Eloquent relationships and service methods lack explicit return types.

**Root Cause**

The module was written incrementally without a consistent static-analysis standard.

**Business Impact**

No direct user impact, but maintenance and onboarding are slower.

**Technical Impact**

IDE inference and static analysis are weaker, and relationship cardinality mistakes are easier to miss.

**Proposed Solution**

- Add Laravel relationship return types after canonical user ownership and identity cardinality are resolved.
- Add concrete paginator/collection/response return types to service methods.
- Introduce PHPStan/Larastan only as part of the repository-wide CI strategy, not as an isolated Account-only rule set.

**Files To Change**

- Canonical user model selected by P0-04
- `Modules/Account/Models/EmployeeProfile.php`
- `Modules/Account/Models/CustomerProfile.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- `Modules/Account/Models/UserMeta.php`
- `Modules/Account/Services/AccountService.php`
- Static-analysis configuration introduced by project CI

**Estimated Risk:** Low

**Estimated Complexity:** Medium

**Estimated Effort:** M

## P2-09 - `userPayload()` Has an Unused Parameter

**Issue**

`AccountService::userPayload()` accepts `$isUpdate`, but the parameter is never used.

**Root Cause**

The method signature anticipated different create/update behavior that was not implemented.

**Business Impact**

No direct runtime impact.

**Technical Impact**

The signature implies behavior that does not exist and makes callers harder to understand.

**Proposed Solution**

- Remove the parameter after tests cover create/update payload behavior.
- If create/update rules genuinely differ, express them through separate DTO construction instead of a dormant boolean flag.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `tests/Unit/Account/AccountPayloadTest.php` (new)

**Estimated Risk:** Low

**Estimated Complexity:** Low

**Estimated Effort:** XS

## P2-10 - Mapping and Payload Construction Need Explicit DTOs

**Issue**

Associative arrays for users, profiles, identity data, filters, and workbook rows are constructed repeatedly with string keys.

**Root Cause**

The module has no typed application-layer request/DTO boundary.

**Business Impact**

Field-name drift causes failed imports and inconsistent records, as already demonstrated by schema mismatches.

**Technical Impact**

Renames are not discoverable by static analysis. Validation, normalization, persistence, and export mapping each define their own shape.

**Proposed Solution**

- Introduce small typed DTOs/value objects only where they remove real ambiguity:
  - Account filters
  - User payload
  - Employee profile payload
  - Customer profile payload
  - Identity profile payload
  - Import row/result
- Keep Eloquent models focused on persistence.
- Avoid a large generic repository layer; use explicit Account actions/services.

**Files To Change**

- `Modules/Account/Services/AccountService.php`
- `Modules/Account/Services/AccountImportService.php`
- `Modules/Account/Livewire/Accounts/Form.php`
- `Modules/Account/Livewire/Accounts/Index.php`
- `Modules/Account/Data/*` (new)
- `tests/Unit/Account/Data/*` (new)

**Estimated Risk:** Low

**Estimated Complexity:** High

**Estimated Effort:** L

# Recommended Implementation Order

## Phase 1 - Security and Ownership Containment

Goal: eliminate authorization bypass, privilege escalation, model-identity ambiguity, and public identity-document exposure before broader refactoring.

1. **P0-01:** Enforce route permissions.
2. **P0-02:** Authorize every Livewire action and target record.
3. **P0-03:** Disable arbitrary role creation/assignment through import.
4. **P0-04:** Select the canonical user model, define the morph strategy, and prepare a tested pivot migration.
5. **P0-05:** Enforce protected-account invariants for activation and deletion.
6. **P0-06:** Validate uploads and move identity documents to private storage.
7. **P1-13:** Stop exposing raw system errors.
8. **P1-14:** Remove or secure the broken public API route.
9. **P1-11/P1-12:** Move export/import artifacts to private, lifecycle-managed storage.
10. **P1-33:** Add security regression tests alongside each change.

Phase 1 exit criteria:

- Account routes and Livewire requests deny unauthorized users.
- Account import cannot create or grant privileged roles.
- Authentication, Account CRUD, Spatie roles, and pivots use one user morph identity.
- A Super Admin cannot be deactivated or deleted through Account workflows.
- Identity documents and PII files are not publicly addressable.
- Security behavior is covered by automated tests.

## Phase 2 - Correctness and Data Integrity

Goal: make Account forms, imports, exports, migrations, and deletion behavior internally consistent and recoverable.

1. **P1-01/P1-02/P1-03:** Complete identity, profile-code, and password validation.
2. **P1-06:** Establish the canonical account-type enum.
3. **P1-07:** Align all import fields with models and migrations.
4. **P1-15:** Define and enforce identity/tax uniqueness.
5. **P1-20/P1-32:** Resolve identity-profile cardinality and import lookup semantics.
6. **P1-09/P1-10:** Define one round-trip import/export contract and shared multi-sheet architecture.
7. **P1-08:** Remove or adapt the legacy single-sheet importer.
8. **P1-16/P1-17:** Make file writes compensatable and clean up replaced/deleted files.
9. **P1-18/P1-31:** Define account archive/purge semantics and centralize deletion.
10. **P1-22:** Make migrations reversible.
11. **P1-04/P1-05:** Fix filter and file-type contract defects.
12. **P1-33:** Add transaction, fixture, migration, and round-trip tests.

Phase 2 exit criteria:

- Every form/import field maps to a validated domain field and persisted schema column.
- Exported round-trip workbooks can be re-imported under a versioned contract.
- Account deletion/recovery and identity-file retention are explicit and tested.
- Account migrations pass fresh-install and rollback tests.
- Imports cannot partially corrupt Account data.

## Phase 3 - Performance, Maintainability, and Cleanup

Goal: bound workload growth, remove duplicated implementations, and leave a smaller, clearer Account module.

1. **P1-30:** Centralize filtered Account query construction.
2. **P1-23/P1-24:** Remove role N+1 queries and unused eager loads.
3. **P1-25/P1-26:** Remove unbounded list/select-all state.
4. **P1-27:** Stream or queue exports.
5. **P1-21/P1-28/P1-29:** Queue/chunk imports and preload user/role context.
6. **P1-19:** Convert large bulk deletion into bounded, idempotent operations.
7. **P2-03:** Optimize search based on measured query plans.
8. **P2-10/P2-08:** Introduce focused DTOs and complete type declarations.
9. **P2-07/P2-09:** Remove unused service methods and dead parameters.
10. **P2-04/P2-05/P2-06:** Remove confirmed scaffold artifacts and document the final module contract.
11. **P2-01/P2-02:** Add domain date bounds and explicit activation commands.
12. **P1-33:** Add query-count and workload-limit tests to CI.

Phase 3 exit criteria:

- Interactive Account pages and Livewire state are bounded.
- Imports/exports have predictable memory and query behavior.
- List, export, and bulk actions use the same filter contract.
- Duplicate/dead Account code is removed.
- The module has a documented ownership model and CI-enforced regression suite.

## Dependencies and Sequencing Notes

- P0-04 is the central architectural dependency. Role fixes, policies, N+1 cleanup, deletion, and relationship typing should not finalize until the canonical user model is chosen.
- P0-06 should establish private storage before P1-16 and P1-17 implement full file transaction/lifecycle behavior.
- P1-07 must precede P1-09 because a round-trip format cannot be designed against an inconsistent schema.
- P1-10 should extend the shared import/export contract without forcing Account's multi-entity workflow into a single-model abstraction.
- P1-18 must be decided before P1-31 consolidates deletion behavior.
- Performance work should follow correctness work; optimizing duplicated or incorrect queries would harden the wrong design.

## Planning Constraints

- No code changes are included in this document.
- Estimates should be revisited after runtime route, migration, and test execution is available.
- Existing production data must be audited before changing role morph types, uniqueness constraints, or deletion semantics.
- Every implementation pull request should stay narrowly scoped and include the tests appropriate to its risk.
