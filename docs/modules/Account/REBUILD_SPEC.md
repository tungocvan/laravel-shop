# Modules/Account - Rebuild Specification

Status: **Implementation source of truth**

Version: **1.0**

Generated: 2026-06-14

Source documents:

- `docs/modules/Account/ANALYSIS.md`
- `docs/modules/Account/REFACTOR_PLAN.md`
- `ROADMAP.md`

This specification defines the target architecture and behavior for rebuilding `Modules/Account`. When existing Account code conflicts with this document, this document takes precedence unless it is amended through an explicit architecture decision.

No implementation code is included here.

## Scope

The Account module owns administrative account-management use cases:

- Search and list employee/customer accounts.
- Create and edit accounts.
- Activate, deactivate, archive, restore, and explicitly purge accounts.
- Maintain employee, customer, identity, and extensible metadata profiles.
- Assign approved roles when separately authorized.
- Import and export accounts through a versioned workbook contract.
- Store and deliver identity documents securely.

The Account module does not own:

- Authentication infrastructure.
- Role and permission definitions.
- Public customer self-service profile management.
- Orders, addresses, affiliate data, or chat data.
- A public Account API.

## Binding Architecture Decisions

| Decision | Specification |
|---|---|
| Canonical user model | `App\Models\User` is the only concrete Eloquent/authentication model for the `users` table. |
| Account module ownership | `Modules/Account` owns account administration services, profile models, queries, policies, imports, exports, and Livewire UI. |
| Duplicate user model | `Modules/Account/Models/User.php` must be retired after callers and role morph data are migrated. |
| Role morph identity | Spatie pivots use the canonical `App\Models\User` morph identity. A stable morph map may be introduced, but only with an idempotent data migration. |
| Account types | Only `employee` and `customer` are supported in version 1. |
| Identity cardinality | A user has zero or one identity profile. |
| File storage | Identity documents, import files, export files, and error reports are private. |
| Password import | Workbooks cannot set or overwrite passwords. New imported users receive an invitation/password-reset flow. |
| Role import | Imports may reference approved existing roles only. Imports cannot create roles or assign `Super Admin`. |
| API | `Modules/Account/routes/api.php` exposes no routes in version 1. |
| Interactive page size | Allowed values are 10, 25, 50, and 100. `All` is not supported. |
| Destructive lifecycle | Normal delete means archive through soft delete. Permanent purge is a separate restricted operation. |
| Bulk operations | Current-page actions may run synchronously. Cross-page or large operations must run as queued, tracked jobs. |
| Import format | XLSX workbook, schema version 1, with named sheets. CSV and legacy XLS are not supported. |
| Export formats | Human-readable report export and round-trip data export are separate operations. |

## Module Boundaries

Target dependency direction:

```text
Account Routes / Controllers
    -> Account Livewire
        -> Account Application Services / Actions
            -> Account Queries / DTOs / Policies
                -> App\Models\User + Account Profile Models
                    -> Database / Private Storage / Queue

Account Import / Export
    -> Shared ImportExport contracts and report/storage concerns
    -> Account-specific multi-sheet orchestration

Account Role Assignment
    -> Existing Role module or Spatie role model through an Account-facing contract
```

Rules:

- Blade and Livewire must not query Eloquent directly for Account business operations.
- Controllers remain page and authorized-file-delivery adapters.
- Services must not return Blade views.
- Account must not create role definitions.
- Admin may link to Account pages but must not duplicate Account CRUD services or models.
- Account profile models may depend on `App\Models\User`.
- `App\Models\User` may expose Account profile relationships but must not absorb Account application workflows.

# 1. Database Design

## 1.1 Existing `users` Table

Owner: core authentication model `App\Models\User`.

Account uses these fields:

| Column | Type | Null | Default | Rules |
|---|---|---:|---|---|
| `id` | bigint unsigned | No | auto | Primary key |
| `name` | varchar(255) | Yes | null | Trimmed; max 255 |
| `email` | varchar(255) | No | none | Unique; normalized lowercase |
| `phone` | varchar(30) | Yes | null | Normalized string |
| `avatar` | varchar(255) | Yes | null | Not managed by identity-document workflow |
| `account_type` | varchar(30) | No | `customer` | `employee` or `customer` only |
| `password` | varchar(255) | Yes | null | Hashed; never imported/exported |
| `is_active` | boolean | No | true | Explicit activate/deactivate commands |
| `email_verified_at` | timestamp | Yes | null | Existing auth behavior |
| `last_login_at` | timestamp | Yes | null | Existing auth behavior |
| `deleted_at` | timestamp | Yes | null | Archive state |
| timestamps | timestamps | No | none | Standard Laravel timestamps |

Required indexes:

- Unique index on normalized `email`.
- Index on `account_type`.
- Index on `is_active`.
- Composite index on `deleted_at`, `account_type`, `is_active` only if query-plan measurements justify it.

Rules:

- No second Eloquent model may own `users`.
- `account_type` is cast to `Modules\Account\Enums\AccountType`.
- `password` uses Laravel's `hashed` cast.
- Archived users are excluded by default through `SoftDeletes`.
- Account list/search may optionally include archived users only with `restore_account`.

## 1.2 `employee_profiles`

Model: `Modules\Account\Models\EmployeeProfile`

Cardinality: zero or one employee profile per user.

| Column | Type | Null | Constraint |
|---|---|---:|---|
| `id` | bigint unsigned | No | Primary key |
| `user_id` | bigint unsigned | No | Unique FK to `users.id` |
| `employee_code` | varchar(100) | No | Unique |
| `department` | varchar(255) | Yes | |
| `position` | varchar(255) | Yes | |
| `joined_date` | date | Yes | Must not be in the future |
| `work_phone` | varchar(30) | Yes | |
| `work_email` | varchar(255) | Yes | Valid email |
| `status` | varchar(30) | No | Enum: `active`, `inactive`, `resigned` |
| `note` | text | Yes | Internal; max application length 5,000 |
| `deleted_at` | timestamp | Yes | Mirrors archive lifecycle |
| timestamps | timestamps | No | |

Indexes:

- Unique `user_id`.
- Unique `employee_code`.
- Index `status`.
- Do not add a redundant composite index beginning with unique `employee_code`.

Foreign-key behavior:

- `user_id` references `users.id`.
- Physical user purge cascades to this table.
- Ordinary archive uses soft deletes and does not depend on cascade behavior.

## 1.3 `customer_profiles`

Model: `Modules\Account\Models\CustomerProfile`

Cardinality: zero or one customer profile per user.

| Column | Type | Null | Constraint |
|---|---|---:|---|
| `id` | bigint unsigned | No | Primary key |
| `user_id` | bigint unsigned | No | Unique FK to `users.id` |
| `customer_code` | varchar(100) | No | Unique |
| `gender` | varchar(20) | Yes | Enum: `male`, `female`, `other` |
| `birthday` | date | Yes | Not in future; minimum reasonable date configurable |
| `address` | varchar(255) | Yes | |
| `province` | varchar(255) | Yes | |
| `district` | varchar(255) | Yes | |
| `ward` | varchar(255) | Yes | |
| `status` | varchar(30) | No | Enum: `active`, `inactive`, `blocked` |
| `note` | text | Yes | Internal; max application length 5,000 |
| `deleted_at` | timestamp | Yes | Mirrors archive lifecycle |
| timestamps | timestamps | No | |

Indexes:

- Unique `user_id`.
- Unique `customer_code`.
- Index `status`.
- Location indexes must be added only after query-plan evidence; a three-column location index is not required by this specification.

## 1.4 `user_identity_profiles`

Model: `Modules\Account\Models\UserIdentityProfile`

Cardinality: zero or one identity profile per user.

| Column | Type | Null | Constraint |
|---|---|---:|---|
| `id` | bigint unsigned | No | Primary key |
| `user_id` | bigint unsigned | No | Unique FK to `users.id` |
| `identity_type` | varchar(50) | Yes | Enum: `citizen_id`, `passport`, `tax_code`, `other` |
| `identity_number` | varchar(100) | Yes | Normalized |
| `issued_date` | date | Yes | Not in future |
| `issued_place` | varchar(255) | Yes | |
| `front_image` | varchar(500) | Yes | Private disk relative path |
| `back_image` | varchar(500) | Yes | Private disk relative path |
| `portrait_4x6_image` | varchar(500) | Yes | Private disk relative path |
| `tax_code` | varchar(100) | Yes | Normalized |
| `tax_registered_name` | varchar(255) | Yes | |
| `tax_address` | varchar(255) | Yes | |
| `note` | text | Yes | Internal; max application length 5,000 |
| `deleted_at` | timestamp | Yes | |
| timestamps | timestamps | No | |

Uniqueness:

- `user_id` is unique.
- Non-null normalized `identity_number` must be unique within `identity_type`.
- Non-null normalized `tax_code` must be globally unique.
- Existing duplicates must be audited and resolved before unique constraints are deployed.
- Soft-deleted rows continue to reserve identity and tax values until permanent purge. Reuse requires an explicit business-approved purge.

Indexes:

- Unique `user_id`.
- Unique composite `identity_type`, `identity_number`.
- Unique `tax_code`.

File-path rules:

- Paths are generated by the server.
- Paths are never accepted from Livewire text input or import workbooks.
- Storage root: private disk prefix `account/identity/{user_uuid-or-id}/`.
- Original client filenames are metadata only and must not become storage paths.

## 1.5 `user_metas`

Model: `Modules\Account\Models\UserMeta`

Purpose: low-risk extensible attributes that do not justify first-class columns.

| Column | Type | Null | Constraint |
|---|---|---:|---|
| `id` | bigint unsigned | No | Primary key |
| `user_id` | bigint unsigned | No | FK to `users.id` |
| `key` | varchar(100) | No | Unique per user |
| `value` | text | Yes | |
| `group_name` | varchar(100) | No | Default `general` |
| `type` | varchar(30) | No | Enum: `text`, `textarea`, `json`, `image` |
| `label` | varchar(255) | Yes | |
| timestamps | timestamps | No | |

Rules:

- Unique `user_id`, `key`.
- Sensitive identity or authorization data must not be stored as generic meta.
- JSON values must use a JSON cast and validate successfully before persistence.

## 1.6 Account Operation Tables

### `account_imports`

Required for queued imports and auditability.

| Column | Purpose |
|---|---|
| `id` | Primary key |
| `uuid` | Public-safe operation identifier, unique |
| `requested_by` | FK to `users.id` |
| `source_path` | Private storage path |
| `original_filename` | Display metadata |
| `schema_version` | Workbook schema version |
| `status` | `pending`, `validating`, `queued`, `processing`, `completed`, `failed`, `cancelled` |
| `mode` | Version 1 supports `update_or_create` only |
| `total_rows` | Count |
| `processed_rows` | Count |
| `success_rows` | Count |
| `error_rows` | Count |
| `error_report_path` | Private path |
| `started_at` | Nullable timestamp |
| `finished_at` | Nullable timestamp |
| `expires_at` | File-retention deadline |
| `failure_code` | Stable internal code, nullable |
| timestamps | |

### `account_exports`

Required for queued exports and controlled downloads.

| Column | Purpose |
|---|---|
| `id` | Primary key |
| `uuid` | Public-safe operation identifier, unique |
| `requested_by` | FK to `users.id` |
| `type` | `report` or `round_trip` |
| `filters` | JSON snapshot of normalized filters |
| `status` | `pending`, `processing`, `completed`, `failed`, `expired` |
| `file_path` | Private path |
| `row_count` | Count |
| `started_at` | Nullable timestamp |
| `finished_at` | Nullable timestamp |
| `expires_at` | Download deadline |
| `failure_code` | Stable internal code, nullable |
| timestamps | |

Rules for both tables:

- Operation records are visible only to the requester or a user with a dedicated audit permission.
- Files expire after a configurable period, default 24 hours.
- Cleanup marks records expired and removes files.
- Failure messages shown to users use stable codes, not raw exception text.

## 1.7 Role and Permission Pivots

Existing Spatie tables remain authoritative:

- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

Requirements:

- `model_type` must resolve consistently to the canonical user model.
- Existing mixed morph types must be normalized through an idempotent migration.
- Account services must use Spatie relation methods after normalization; manual pivot SQL is prohibited for ordinary role assignment/removal.

## 1.8 Migration Strategy

Implementation migrations must:

1. Audit existing user morph types and conflicting role pivots.
2. Establish the canonical user morph identity.
3. Normalize pivot data without duplicating primary-key tuples.
4. Audit duplicate identity numbers and tax codes.
5. Resolve duplicates before applying unique constraints.
6. Add operation tables.
7. Correct indexes and reversible `down()` methods.
8. Preserve existing production data.

Migration requirements:

- Every migration has a meaningful `down()` unless rollback would be unsafe; unsafe rollback must fail explicitly with documentation rather than silently do nothing.
- Migration tests run against MySQL-compatible behavior, not SQLite alone.
- Data migrations are idempotent.
- Destructive schema changes require a backup and deployment runbook.

# 2. Model Design

## 2.1 Canonical User Model

File: `app/Models/User.php`

Responsibilities:

- Laravel authentication.
- Sanctum/auth traits already required by the application.
- Spatie roles and permissions.
- Soft deletion.
- Account profile relationships.
- Casts for account type, booleans, timestamps, and password.

Required Account relationships:

```text
employeeProfile(): HasOne
customerProfile(): HasOne
identityProfile(): HasOne
metas(): HasMany
```

Rules:

- There is no `identityProfiles()` has-many relation in version 1.
- There is no custom `accountRoles()` relation.
- `roles()` from Spatie is authoritative.
- `isSuperAdmin()` must read the loaded canonical `roles` relation when available and must not create N+1 queries.
- `isProtectedAccount()` is a domain-oriented helper or service rule, not a UI-only property.
- Account-specific query behavior belongs in `Modules/Account/Queries`, not as a growing set of model scopes.

## 2.2 Account Profile Models

Files:

- `Modules/Account/Models/EmployeeProfile.php`
- `Modules/Account/Models/CustomerProfile.php`
- `Modules/Account/Models/UserIdentityProfile.php`
- `Modules/Account/Models/UserMeta.php`

Requirements:

- All `user()` relationships return `BelongsTo` and reference `App\Models\User`.
- Fillable fields exactly match the database design.
- Date and enum fields use casts.
- Models contain persistence representation and simple relationships, not orchestration.
- File deletion, role assignment, import orchestration, and permission checks do not live in model events.

## 2.3 Enums

Target directory: `Modules/Account/Enums`

Required enums:

- `AccountType`: `employee`, `customer`
- `EmployeeStatus`: `active`, `inactive`, `resigned`
- `CustomerStatus`: `active`, `inactive`, `blocked`
- `Gender`: `male`, `female`, `other`
- `IdentityType`: `citizen_id`, `passport`, `tax_code`, `other`
- `AccountImportStatus`
- `AccountExportStatus`
- `AccountExportType`

Enums are used by:

- Model casts.
- Livewire options and validation.
- Import normalization and validation.
- Service DTOs.
- Export mapping.
- Tests.

## 2.4 Data Transfer Objects

Target directory: `Modules/Account/Data`

Required DTOs:

- `AccountFilters`
- `AccountData`
- `EmployeeProfileData`
- `CustomerProfileData`
- `IdentityProfileData`
- `IdentityUploadData`
- `AccountImportOptions`
- `AccountImportResult`
- `AccountExportOptions`

Rules:

- DTOs are immutable after construction.
- DTO construction normalizes whitespace, lowercase email, nullable blank values, phone formatting, and identity/tax identifiers.
- Validation remains explicit; DTOs do not silently coerce unsupported enum values.
- Livewire, import, and future API adapters produce the same DTOs.

## 2.5 Policies

Target file: `Modules/Account/Policies/AccountPolicy.php` or `app/Policies/UserPolicy.php`.

The implementation must choose one location and register it explicitly. The policy operates on `App\Models\User`.

Required abilities:

- `viewAny`
- `view`
- `create`
- `update`
- `activate`
- `deactivate`
- `archive`
- `restore`
- `purge`
- `import`
- `export`
- `assignRole`
- `viewIdentityDocument`

Policy rules:

- Permissions provide broad capability.
- Policies provide record-level restrictions.
- A user cannot purge themselves.
- A protected Super Admin cannot be archived, purged, or deactivated.
- At least one active Super Admin must remain.
- Identity documents require `view_account_identity` plus permission to view the target account.

# 3. Service Design

## 3.1 Design Style

Use explicit application actions for state changes and a query service for reads. Do not build a generic repository abstraction.

Target directories:

```text
Modules/Account/
├── Actions/
├── Data/
├── Enums/
├── Exceptions/
├── Jobs/
├── Models/
├── Policies/
├── Queries/
└── Services/
```

## 3.2 Query Service

Target: `Modules/Account/Queries/AccountQuery.php`

Responsibilities:

- Accept `AccountFilters`.
- Apply search, account type, active state, archive state, and approved ordering.
- Return an Eloquent builder so each use case selects only required columns/relations.

Required methods:

```text
forAdminList(AccountFilters): Builder
forExport(AccountFilters): Builder
countForBulkOperation(AccountFilters): int
idsForBulkOperation(AccountFilters, int $limit, ?int $afterId): LazyCollection|Collection
```

List query requirements:

- Select only list columns.
- Eager-load `roles:id,name,guard_name`.
- Eager-load only the profile matching `account_type` where practical.
- Never eager-load identity data for the index.
- Use stable ordering by `id` descending unless the user selects an approved sort.
- Sort columns must be allowlisted.

Search behavior:

- Trim input.
- Minimum search length: 2 characters, except exact email/phone search.
- Email and phone favor exact/prefix matching.
- Name may use contains matching initially.
- Search implementation must be revisited using measured MySQL query plans before adding full-text search.

## 3.3 Account Read Service

Target: `Modules/Account/Services/AccountReadService.php`

Responsibilities:

- Paginated admin list.
- Authorized detail retrieval.
- Form hydration data.
- Approved-role options for users with role-assignment permission.

Required methods:

```text
paginate(AccountFilters, int $perPage): LengthAwarePaginator
findForView(int $userId): User
findForEdit(int $userId): User
approvedAssignableRoles(): Collection
```

## 3.4 Create and Update Actions

Targets:

- `Modules/Account/Actions/CreateAccount.php`
- `Modules/Account/Actions/UpdateAccount.php`

Inputs:

- Canonical actor `App\Models\User`
- Account/profile DTOs
- Optional staged identity uploads
- Optional approved role IDs only when actor has `assign_account_role`

Outputs:

- Fresh canonical `App\Models\User` with required relationships loaded.

Rules:

- Authorize before opening the transaction.
- Re-check critical protected-account invariants inside the transaction where race conditions matter.
- Account type controls which profile exists.
- Switching employee to customer soft-deletes the employee profile and creates/restores customer profile.
- Switching customer to employee performs the inverse.
- No unsupported profile survives as active for the current account type.
- Role assignment is separate from basic account fields and may be omitted.
- Password create behavior:
  - Manual create may accept a password meeting the shared application policy, or issue an invitation.
  - Import always issues an invitation and never receives a password.

## 3.5 Activation Actions

Targets:

- `Modules/Account/Actions/ActivateAccount.php`
- `Modules/Account/Actions/DeactivateAccount.php`

Rules:

- Desired-state commands replace toggle semantics.
- Operations are idempotent.
- Deactivation checks policy and protected-account rules.
- Deactivation of the last active Super Admin is prohibited.
- Activation/deactivation creates an audit event/log entry.

## 3.6 Archive, Restore, and Purge

Targets:

- `Modules/Account/Actions/ArchiveAccount.php`
- `Modules/Account/Actions/RestoreAccount.php`
- `Modules/Account/Actions/PurgeAccount.php`

Archive:

- Soft-deletes the user and active profile rows in one transaction.
- Does not delete identity files.
- Removes active sessions/tokens as appropriate.
- Does not manually delete role pivots.

Restore:

- Restores the user and profile matching the account type.
- Restores identity profile and metas if archived.
- Does not automatically reactivate the account unless explicitly specified; default `is_active` remains false after restore for review.

Purge:

- Requires `purge_account`.
- Is prohibited for the current actor and protected accounts.
- Permanently deletes user-owned Account profiles and role/permission pivots through canonical model behavior.
- Deletes private identity files after database commit.
- Is audited.
- Must not be exposed as a routine list-row action.

## 3.7 Identity Document Service

Target: `Modules/Account/Services/IdentityDocumentService.php`

Responsibilities:

- Validate staged upload metadata.
- Generate paths.
- Store private files.
- Replace files with compensating cleanup.
- Delete files after purge.
- Produce authorized download responses.

Required methods:

```text
stage(TemporaryUploadedFile, IdentityDocumentKind): StagedIdentityFile
promote(StagedIdentityFile, User): string
replace(?string $oldPath, StagedIdentityFile, User): string
delete(?string $path): void
download(User $actor, User $subject, IdentityDocumentKind): BinaryFileResponse
```

Rules:

- Allowed formats: JPEG, PNG, WEBP.
- Maximum size: 5 MB per file.
- MIME is determined server-side.
- SVG, PDF, executable, archive, and polyglot files are rejected in version 1.
- Optional image re-encoding should be considered to strip metadata.
- Downloads use `Content-Disposition: inline` only for verified images and safe headers.
- Raw private paths are never returned to the browser.

## 3.8 Role Assignment Service

Target: `Modules/Account/Services/AccountRoleService.php`

Responsibilities:

- List approved assignable roles.
- Synchronize allowed roles for an account.
- Reject `Super Admin` unless a separately designed break-glass workflow is introduced.
- Use only `admin` guard roles.

Required methods:

```text
assignableRoles(User $actor): Collection
syncRoles(User $actor, User $subject, array $roleIds): void
```

Rules:

- Account import calls this service only with pre-approved role references.
- It never creates roles.
- Every assignment is audited.

## 3.9 Error Contract

Target: `Modules/Account/Exceptions`

Expected domain exceptions:

- `ProtectedAccountException`
- `AccountAuthorizationException` only if policy exceptions need domain translation
- `DuplicateProfileCodeException`
- `DuplicateIdentityException`
- `InvalidAccountTypeTransitionException`
- `AccountImportValidationException`
- `IdentityDocumentException`

Rules:

- UI receives stable, translated messages.
- Logs contain exception class, correlation ID, actor ID, subject ID, operation ID, and redacted context.
- Passwords, tokens, identity numbers, tax codes, and file contents are never logged.

# 4. Livewire Design

## 4.1 Components

Target components:

```text
Modules/Account/Livewire/Accounts/Index.php
Modules/Account/Livewire/Accounts/Form.php
Modules/Account/Livewire/Accounts/ImportPanel.php
Modules/Account/Livewire/Accounts/ExportPanel.php
Modules/Account/Livewire/Accounts/OperationStatus.php
```

The implementation may keep import/export inside `Index` initially only if state remains small and authorization/testing stay clear. The target architecture separates them.

## 4.2 Account Index Component

Responsibilities:

- Own only filter, pagination, page selection, and modal state.
- Delegate reads to `AccountReadService`.
- Delegate mutations to explicit actions.

Public state:

- `search: string`
- `accountType: string`
- `isActive: string`
- `archiveState: string`
- `perPage: int`
- `selectedIds: array`
- confirmation/modal identifiers

Allowed `perPage`:

- 10
- 25
- 50
- 100

Rules:

- No `All`.
- Selection is current-page only.
- Cross-page bulk operations use filters submitted to a queued operation, not a Livewire ID array.
- Every public method authorizes.
- Public IDs are validated as integers and resolved server-side.
- Filter changes reset pagination and current-page selection.
- Query-string synchronization may be used for shareable filters.

Actions:

```text
activate(int $userId)
deactivate(int $userId)
archive(int $userId)
restore(int $userId)
archiveSelected()
openImport()
openExport()
```

There is no generic `toggleActive()`.

## 4.3 Account Form Component

Responsibilities:

- Adapt UI state into validated DTOs.
- Authorize create or update.
- Stage uploads.
- Delegate persistence to create/update actions.

Form sections:

- Basic account.
- Account-type-specific profile.
- Identity/tax profile.
- Identity documents.
- Roles, visible only with `assign_account_role`.

Validation:

- Uses enum-driven rules.
- Uses Laravel `Password` rule.
- Validates unique employee/customer codes while ignoring the current profile.
- Validates identity/tax uniqueness according to database rules.
- Validates date bounds.
- Validates upload type and size.
- Password confirmation is required only when a password is supplied.

Livewire 3 rules:

- Do not use `wire:model.live` on every form field.
- Use deferred/default `wire:model` for ordinary fields.
- Use `.live` only when account type must immediately change visible profile sections.
- Upload progress is scoped to each upload property.
- Validation errors are shown next to fields and summarized at the top.

Save behavior:

- Disable submit while saving.
- Prevent duplicate submission.
- On create, redirect to edit/detail with success notice.
- On update, return to the list or remain on edit according to one consistent UX decision; version 1 defaults to list.
- Raw exceptions are not shown.

## 4.4 Import Panel

Responsibilities:

- Authorize `import_account`.
- Accept XLSX only.
- Create an `account_imports` operation.
- Upload privately.
- Show validation mode, progress, status, and error-report download.

Rules:

- Maximum synchronous file size: 2 MB and 500 total data rows.
- Larger accepted files are queued, up to configurable hard limits.
- Hard default limit: 10 MB and 50,000 total rows.
- File limits are server-side configuration values.
- Import is never executed by a second browser request using a client-supplied path.

## 4.5 Export Panel

Responsibilities:

- Authorize `export_account`.
- Choose `report` or `round_trip`.
- Display normalized active filters.
- Create a queued export operation when estimated row count exceeds the synchronous threshold.

Default thresholds:

- Up to 1,000 rows: synchronous streaming is allowed.
- Above 1,000 rows: queued export.

## 4.6 Operation Status

Responsibilities:

- Poll or listen for import/export operation state.
- Show progress counts.
- Provide authorized private download when completed.
- Provide stable failure code and safe message.

Rules:

- Status checks authorize ownership/audit permission.
- Poll interval must be reasonable and stop on terminal states.
- Livewire component does not expose storage paths.

## 4.7 Blade Views

Target views:

```text
Modules/Account/resources/views/pages/index.blade.php
Modules/Account/resources/views/pages/create.blade.php
Modules/Account/resources/views/pages/edit.blade.php
Modules/Account/resources/views/livewire/accounts/index.blade.php
Modules/Account/resources/views/livewire/accounts/form.blade.php
Modules/Account/resources/views/livewire/accounts/import-panel.blade.php
Modules/Account/resources/views/livewire/accounts/export-panel.blade.php
Modules/Account/resources/views/livewire/accounts/operation-status.blade.php
```

Requirements:

- Blade uses `@can` for action visibility, but server-side authorization remains mandatory.
- The list never displays full identity/tax values.
- Sensitive values shown on edit/detail are masked by default where practical.
- Destructive actions use explicit confirmation with account name/email.
- Bulk actions show affected count and scope.
- The UI clearly distinguishes deactivate, archive, and purge.
- Purge is not available from the normal list.

# 5. Import Design

## 5.1 Workbook Contract

Format: XLSX

Schema version: `1`

Required sheets:

1. `manifest`
2. `users`
3. `employee_profiles`
4. `customer_profiles`
5. `identity_profiles`
6. `user_roles`

All sheets must exist. Data sheets may be empty.

Header rules:

- Exact lowercase snake-case canonical headers.
- Leading/trailing whitespace is normalized.
- Unknown headers produce warnings in lenient template-minor versions and errors in version 1 strict mode.
- Duplicate headers are errors.

## 5.2 `manifest` Sheet

One data row:

| Header | Required | Value |
|---|---:|---|
| `schema_name` | Yes | `inafo_account` |
| `schema_version` | Yes | `1` |
| `generated_at` | No | ISO 8601 |
| `generated_by` | No | Informational only |

The manifest is never trusted for authorization.

## 5.3 `users` Sheet

| Header | Required | Rules |
|---|---:|---|
| `email` | Yes | Valid, normalized lowercase, unique in workbook |
| `name` | Yes | Max 255 |
| `phone` | No | Max 30 |
| `account_type` | Yes | `employee` or `customer` |
| `is_active` | No | `1` or `0`; default `1` |

Not allowed:

- Password
- Super Admin flag
- Raw permission names
- Tokens
- Identity file paths

Behavior:

- Email is the external natural key.
- Existing users are updated only for fields included by the version 1 contract.
- Protected accounts cannot be deactivated or have account type changed by import.
- New users receive an invitation/password-reset flow.

## 5.4 `employee_profiles` Sheet

| Header | Required | Rules |
|---|---:|---|
| `email` | Yes | Must resolve to employee account |
| `employee_code` | Yes | Max 100; unique |
| `department` | No | Max 255 |
| `position` | No | Max 255 |
| `joined_date` | No | `YYYY-MM-DD`; not future |
| `work_phone` | No | Max 30 |
| `work_email` | No | Valid email |
| `status` | No | `active`, `inactive`, `resigned` |
| `note` | No | Max 5,000 |

## 5.5 `customer_profiles` Sheet

| Header | Required | Rules |
|---|---:|---|
| `email` | Yes | Must resolve to customer account |
| `customer_code` | Yes | Max 100; unique |
| `gender` | No | `male`, `female`, `other` |
| `birthday` | No | `YYYY-MM-DD`; not future |
| `address` | No | Max 255 |
| `province` | No | Max 255 |
| `district` | No | Max 255 |
| `ward` | No | Max 255 |
| `status` | No | `active`, `inactive`, `blocked` |
| `note` | No | Max 5,000 |

## 5.6 `identity_profiles` Sheet

| Header | Required | Rules |
|---|---:|---|
| `email` | Yes | Must resolve to user |
| `identity_type` | No | Canonical enum |
| `identity_number` | Conditional | Required when identity type is set |
| `issued_date` | No | `YYYY-MM-DD`; not future |
| `issued_place` | No | Max 255 |
| `tax_code` | No | Max 100; normalized |
| `tax_registered_name` | No | Max 255 |
| `tax_address` | No | Max 255 |
| `note` | No | Max 5,000 |

Identity images are not imported through workbook paths or URLs in version 1.

## 5.7 `user_roles` Sheet

| Header | Required | Rules |
|---|---:|---|
| `email` | Yes | Must resolve to user |
| `role_name` | Yes | Must be an existing approved `admin` role |

Rules:

- `guard_name` is not workbook-controlled.
- `Super Admin` is forbidden.
- Unknown roles are errors.
- The actor must have `assign_account_role`.
- Role assignment mode is additive in version 1; import does not remove existing roles.

## 5.8 Validation Pipeline

Order:

1. Validate file signature, size, and XLSX structure.
2. Validate manifest.
3. Validate required sheets and headers.
4. Normalize rows.
5. Detect duplicate emails/codes/identity values within workbook.
6. Preload existing users and approved roles.
7. Validate cross-sheet references.
8. Validate protected-account invariants.
9. Produce complete validation report.
10. Persist only when validation has zero errors.

Warnings do not block import. Errors block all persistence.

## 5.9 Persistence Semantics

Mode: `update_or_create`

Transaction:

- A synchronous import of up to 500 rows uses one transaction.
- Queued imports process a validated operation in bounded chunks.
- If strict all-or-nothing behavior is required for a queued import, use a staging-table approach before one final merge transaction.
- Version 1 source of truth prefers all-or-nothing business semantics; implementation must not silently switch to partial success.

Idempotency:

- Reprocessing the same normalized workbook produces the same state.
- Email, employee code, customer code, identity keys, and approved roles are stable identifiers.
- Jobs use the operation UUID and status transition guards to avoid duplicate execution.

## 5.10 Import Reports

Report fields:

- operation UUID
- schema version
- total rows
- valid rows
- error rows
- warnings
- per-sheet row/column error details
- safe failure code

Error report:

- XLSX or CSV stored privately.
- Download requires operation ownership or `audit_account_operations`.
- Expires with the source import file.

# 6. Export Design

## 6.1 Export Types

### Report Export

Purpose: human-readable administrative report.

Format:

- XLSX
- One sheet named `accounts`
- Localized display headings are allowed.

Includes:

- ID
- account type
- name
- email
- phone
- active/archive status
- approved role names
- employee/customer summary fields

Excludes:

- Passwords and tokens
- Identity image paths
- Full identity numbers by default
- Full tax code by default
- Internal storage paths

Sensitive identity fields require a separately authorized export mode not included in version 1.

### Round-Trip Export

Purpose: edit and re-import account data.

Format:

- Exact Import Schema Version 1.
- Includes manifest and all required sheets.
- Excludes passwords and identity files.
- Includes only roles the actor is authorized to assign.

## 6.2 Export Query Behavior

- Uses `AccountQuery::forExport()`.
- Streams/lazily iterates rows.
- Selects only required columns.
- Applies the same normalized filters as the index.
- Never calls `get()` on the full export dataset.

## 6.3 Delivery and Retention

- Files are stored privately.
- Synchronous exports may stream without durable storage.
- Queued exports create `account_exports` records.
- Download route uses export UUID, not path.
- Authorization checks requester ownership or audit permission.
- Default expiry: 24 hours.
- Cleanup removes expired files and marks operation records expired.

## 6.4 Export Naming

Suggested download names:

```text
accounts-report-YYYYMMDD-HHMMSS.xlsx
accounts-round-trip-v1-YYYYMMDD-HHMMSS.xlsx
```

Storage names must be server-generated UUIDs and need not match download names.

# 7. Permissions

## 7.1 Permission Catalog

`Modules/Account/config/module.php` must declare:

| Permission | Purpose |
|---|---|
| `view_account` | View account list and non-sensitive detail |
| `create_account` | Create an account |
| `edit_account` | Edit basic/profile data |
| `activate_account` | Activate an account |
| `deactivate_account` | Deactivate an account |
| `archive_account` | Soft-delete/archive an account |
| `restore_account` | View/restore archived accounts |
| `purge_account` | Permanently purge an archived account |
| `import_account` | Upload and execute Account imports |
| `export_account` | Export non-sensitive Account data |
| `assign_account_role` | Assign approved roles |
| `view_account_identity` | View/download identity documents and full identity data |
| `audit_account_operations` | View other users' import/export operation records |

## 7.2 Route Mapping

| Route/operation | Required permission |
|---|---|
| Account index | `view_account` |
| Account create page/action | `create_account` |
| Account edit page/action | `edit_account` plus record policy |
| Activate | `activate_account` plus record policy |
| Deactivate | `deactivate_account` plus record policy |
| Archive | `archive_account` plus record policy |
| Restore | `restore_account` plus record policy |
| Purge | `purge_account` plus record policy |
| Import | `import_account` |
| Export | `export_account` |
| Assign roles | `assign_account_role` |
| View identity files | `view_account_identity` plus record policy |

## 7.3 Super Admin Rules

- Existing project-wide `Gate::before` may grant Super Admin all permissions.
- Account domain invariants still prohibit:
  - deleting or deactivating the last active Super Admin,
  - ordinary import assignment of `Super Admin`,
  - self-purge.
- A future break-glass workflow must be separately specified and audited.

## 7.4 UI Authorization

- Blade `@can` controls visibility.
- Livewire methods authorize again.
- Services/actions enforce protected-account invariants.
- Queue jobs re-authorize against the stored requester where the operation is security-sensitive and verify the operation was authorized at creation.

# 8. Transactions

## 8.1 General Rules

- Authorization occurs before transaction start.
- Mutable business state is re-checked inside the transaction when concurrency can invalidate authorization assumptions.
- Transactions contain database work only where possible.
- External filesystem work uses staging and compensating cleanup.
- Events/notifications that depend on committed data dispatch after commit.
- Raw exceptions are logged and translated at the boundary.

## 8.2 Create Account

Transaction includes:

1. Create canonical user.
2. Create the matching profile.
3. Create/update identity profile metadata.
4. Assign approved roles.
5. Write audit event.

File flow:

1. Validate/stage upload privately.
2. Execute database transaction.
3. Promote staged file after commit or use a compensating strategy.
4. If promotion fails, mark document operation failed and preserve recoverable state; do not silently store a broken path.

## 8.3 Update Account

Transaction includes:

1. Lock target user row when protected-account or type-transition rules require it.
2. Update basic fields.
3. Synchronize exactly one profile type.
4. Update identity metadata.
5. Synchronize approved roles if requested.
6. Write audit event.

Old identity files are deleted only after the new file path is committed and accessible.

## 8.4 Activate/Deactivate

- Uses explicit desired state.
- Locks or atomically checks protected Super Admin count when deactivating a Super Admin.
- Is idempotent.
- Writes audit event after successful state transition.

## 8.5 Archive/Restore/Purge

Archive and restore are transactional.

Purge:

1. Authorize and validate invariants.
2. Record paths scheduled for deletion.
3. Permanently delete database records and pivots transactionally.
4. Delete files after commit.
5. Failed file deletion is retried by a cleanup job and logged.

## 8.6 Import

- Validation performs no writes.
- Persistence starts only after zero validation errors.
- Role definitions are never created.
- New-user invitation dispatch occurs after commit.
- Operation status transitions use guarded updates to prevent duplicate workers.

## 8.7 Bulk Operations

- Synchronous current-page operations use a bounded transaction.
- Large operations are chunked by stable user ID.
- Each chunk is idempotent and separately transactional.
- Operation status records progress and failures.
- Protected-account failures are reported and not bypassed.

# 9. UI Components

## 9.1 Page Structure

Routes remain page-controller based:

```text
GET /admin/accounts
GET /admin/accounts/create
GET /admin/accounts/{user}/edit
GET /admin/accounts/{user}/identity/{kind}
GET /admin/account-imports/{operation}/report
GET /admin/account-exports/{operation}/download
```

Controllers:

- `Modules/Account/Http/Controllers/AccountController.php`
- `Modules/Account/Http/Controllers/IdentityDocumentController.php`
- `Modules/Account/Http/Controllers/AccountImportDownloadController.php`
- `Modules/Account/Http/Controllers/AccountExportDownloadController.php`

Routes use canonical model binding where applicable.

## 9.2 Account List

Displayed columns:

- Selection
- Name/email
- Account type
- Approved role badges
- Employee/customer code and short profile summary
- Active/archive status
- Created date
- Authorized actions

Filters:

- Search
- Account type
- Active state
- Archive state
- Page size

Actions:

- Create
- Import
- Report export
- Round-trip export
- Activate/deactivate
- Edit
- Archive
- Restore when viewing archived records

Not present:

- Purge as a routine row action
- Identity numbers
- Identity image thumbnails
- Unbounded `All`

## 9.3 Account Form

Basic section:

- Name
- Email
- Phone
- Account type
- Active state, only for authorized actors
- Password/invitation controls

Conditional profile section:

- Employee or customer fields, never both simultaneously.

Identity section:

- Visible only to users allowed to edit Account data.
- Full document preview/download requires `view_account_identity`.
- Existing documents are displayed through authorized routes, not storage URLs.

Role section:

- Visible only with `assign_account_role`.
- Shows approved existing roles.
- Does not offer `Super Admin`.

## 9.4 Import/Export UX

Import:

- Download template.
- Choose XLSX.
- Explain schema version and limits.
- Validate/upload.
- Show queued status and progress.
- Show row errors without raw exceptions.
- Download error report.

Export:

- Choose report or round-trip.
- Show active filters and estimated row count.
- Explain queued behavior for large exports.
- Show expiry time for completed download.

## 9.5 Shared Components

Account should reuse shared components for:

- Notifications.
- Modal/dialog shell.
- Pagination.
- Import/export operation progress.
- File upload progress.
- Confirmation dialogs.

Account-specific components remain in `Modules/Account` when they encode Account business rules.

## 9.6 Accessibility and Livewire Behavior

- Inputs have labels and associated error text.
- Buttons expose loading and disabled states.
- Confirmation dialogs are keyboard accessible.
- Dynamic account-type sections preserve valid state and clear invalid hidden state intentionally.
- Livewire keys are stable in repeated rows.
- Polling stops at terminal operation status.
- Flash/session messages do not contain raw exceptions.

# 10. Test Strategy

## 10.1 Test Layers

### Unit Tests

Target: `tests/Unit/Account`

Cover:

- Enums.
- DTO normalization.
- Account filter normalization.
- Workbook row mapping.
- Protected-account rule calculations.
- Date bounds.
- Filename/path generation.
- Error-report mapping.

### Feature and Integration Tests

Target: `tests/Feature/Account`

Cover:

- Routes and middleware.
- Policies.
- Livewire components.
- Actions/services with database.
- File storage and authorized delivery.
- Import/export.
- Queue jobs.
- Migration/data migration behavior.
- Query-count and workload limits.

## 10.2 Database Test Matrix

Required:

- MySQL-compatible integration suite for migrations, indexes, foreign keys, and locking behavior.
- SQLite may be used for fast unit-like feature tests only when behavior is equivalent.

Migration tests:

- Fresh migration succeeds.
- Rollback succeeds where specified.
- Existing mixed role morph rows normalize idempotently.
- Duplicate identity/tax preflight detects conflicts.
- Unique constraints behave correctly with nulls and soft-deleted rows.

## 10.3 Authorization Tests

For every ability:

- Guest denied.
- Non-admin guard denied.
- Authenticated admin without permission denied.
- Authorized actor allowed.
- Record-level restriction denied where applicable.

Explicit tests:

- Direct Livewire action invocation cannot bypass permissions.
- Menu visibility is not the enforcement boundary.
- Identity download requires both capability and target authorization.
- Import cannot assign or create Super Admin.
- Self-purge denied.
- Last active Super Admin deactivation/archive/purge denied.

## 10.4 Model and Relationship Tests

- Only `App\Models\User` owns `users`.
- Profile relationships use `App\Models\User`.
- Employee/customer/identity profiles are one-to-one.
- Account type cast uses `AccountType`.
- Spatie `roles` relation uses the canonical morph identity.
- List rendering does not cause per-row role queries.

## 10.5 Livewire Tests

Index:

- Filters update results correctly.
- `is_active` behavior is correct.
- Pagination values outside allowlist are rejected/reset.
- No `All` option.
- Selection is page-bounded.
- Activate/deactivate/archive/restore authorize correctly.
- Raw exceptions are not shown.

Form:

- Create employee/customer.
- Update each type.
- Type transition synchronizes profiles.
- Unique profile-code validation.
- Password policy.
- Identity/tax validation.
- Date bounds.
- Upload validation.
- Duplicate submit protection.
- Role section authorization.

## 10.6 Service and Transaction Tests

- Create rollback leaves no partial user/profile/role state.
- Update rollback preserves prior state.
- Failed file promotion leaves no broken stored path.
- Replacement cleans old file only after success.
- Archive/restore behavior matches specification.
- Purge deletes database data and eventually deletes files.
- Activation/deactivation is idempotent.
- Concurrent last-Super-Admin protection is tested on MySQL.
- Bulk jobs are idempotent across retries.

## 10.7 Import Tests

Fixtures must cover:

- Valid version 1 workbook.
- Missing manifest.
- Wrong schema version.
- Missing sheet.
- Duplicate header.
- Unknown header.
- Duplicate email in workbook.
- Existing email update.
- Invalid account type.
- Wrong profile for account type.
- Duplicate employee/customer code.
- Duplicate identity/tax value.
- Invalid dates.
- Unknown role.
- Super Admin role attempt.
- Password column attempt.
- Protected account mutation attempt.
- Large queued workbook.
- Retry/idempotency.

Assertions:

- Validation errors include sheet, row, column, safe reason.
- Validation failure writes no business data.
- Successful import is round-trip compatible.
- Source/error files obey private storage and expiry rules.
- Query count stays within an agreed budget for fixture size.

## 10.8 Export Tests

- Report export contains approved columns only.
- Sensitive fields and storage paths are absent.
- Round-trip export conforms to import schema version 1.
- Round-trip export can be imported into an equivalent empty dataset.
- Filters match index behavior.
- Large export is queued.
- Download authorization and expiry work.
- Export uses lazy/chunked iteration within memory budget.

## 10.9 Query Performance Tests

Budgets must be finalized after implementation, with these initial targets:

- Account index: fixed query count independent of rows per page, excluding pagination count variance.
- No per-row role/profile queries.
- Import reference loading is bounded by sheet type, not row count.
- Export memory does not scale by materializing all rows.

Use:

- Query-count assertions.
- Representative factories.
- MySQL `EXPLAIN` review for search and filter indexes.
- Performance smoke fixtures at 100, 1,000, and 10,000 accounts.

## 10.10 Security Tests

- MIME spoofing rejected.
- Oversized upload rejected.
- Private document path cannot be guessed/downloaded.
- Traversal-like identifiers rejected.
- Export/import operation UUID cannot access another user's file.
- Raw exception details absent from responses.
- Passwords/tokens never appear in workbooks or logs.
- Workbook cannot create permissions or role definitions.
- API Account routes are absent in version 1.

## 10.11 CI Gates

Account implementation is mergeable only when:

- Account unit and feature tests pass.
- MySQL migration suite passes.
- Laravel Pint passes.
- Static analysis passes at the repository-approved level.
- Frontend build passes when Blade/assets change.
- No new duplicate concrete `users` model is introduced.
- Query-count regression tests pass.
- Import/export fixture tests pass.

## Acceptance Criteria

The Account rebuild is complete when:

1. `App\Models\User` is the sole canonical user model.
2. All Account routes and Livewire actions enforce permissions and policies.
3. Ordinary imports cannot create roles, assign Super Admin, or set passwords.
4. Identity files and Account workbooks are private and lifecycle-managed.
5. Employee, customer, and identity schemas match form/import/export contracts.
6. Account type and identity cardinality are represented consistently in schema, models, services, and UI.
7. Report and round-trip exports are distinct and documented.
8. Round-trip export passes an automated export/import test.
9. List, select, import, export, and bulk operations are bounded or queued.
10. Archive, restore, and purge semantics are explicit and tested.
11. Migrations are tested on MySQL-compatible infrastructure.
12. Security, transaction, query-count, and workload regression tests gate merges.

## Planned File Map

Existing files to retain and rebuild:

```text
app/Models/User.php
Modules/Account/config/module.php
Modules/Account/routes/web.php
Modules/Account/Http/Controllers/AccountController.php
Modules/Account/Livewire/Accounts/Index.php
Modules/Account/Livewire/Accounts/Form.php
Modules/Account/Models/EmployeeProfile.php
Modules/Account/Models/CustomerProfile.php
Modules/Account/Models/UserIdentityProfile.php
Modules/Account/Models/UserMeta.php
Modules/Account/resources/views/pages/index.blade.php
Modules/Account/resources/views/pages/create.blade.php
Modules/Account/resources/views/pages/edit.blade.php
Modules/Account/resources/views/livewire/accounts/index.blade.php
Modules/Account/resources/views/livewire/accounts/form.blade.php
```

New target files/directories:

```text
Modules/Account/Actions/*
Modules/Account/Data/*
Modules/Account/Enums/*
Modules/Account/Exceptions/*
Modules/Account/Jobs/*
Modules/Account/Policies/*
Modules/Account/Queries/AccountQuery.php
Modules/Account/Services/AccountReadService.php
Modules/Account/Services/AccountImportService.php
Modules/Account/Services/AccountExportService.php
Modules/Account/Services/AccountRoleService.php
Modules/Account/Services/IdentityDocumentService.php
Modules/Account/Http/Controllers/IdentityDocumentController.php
Modules/Account/Http/Controllers/AccountImportDownloadController.php
Modules/Account/Http/Controllers/AccountExportDownloadController.php
Modules/Account/Livewire/Accounts/ImportPanel.php
Modules/Account/Livewire/Accounts/ExportPanel.php
Modules/Account/Livewire/Accounts/OperationStatus.php
Modules/Account/resources/views/livewire/accounts/import-panel.blade.php
Modules/Account/resources/views/livewire/accounts/export-panel.blade.php
Modules/Account/resources/views/livewire/accounts/operation-status.blade.php
tests/Unit/Account/*
tests/Feature/Account/*
tests/Fixtures/Account/*
```

Files to retire after migration and tests:

```text
Modules/Account/Models/User.php
Modules/Account/Models/Account.php
Modules/Account/Http/Controllers/Api/AccountController.php
Modules/Account/routes/api.php contents
Modules/Account/resources/views/account.blade.php
Modules/Account/resources/views/components/placeholder.blade.php
Modules/Account/resources/views/livewire/placeholder.blade.php
```

Legacy methods to retire:

```text
Modules/Account/Services/AccountService.php::paginate()
Modules/Account/Services/AccountService.php::paginateForAdmin()
Modules/Account/Services/AccountService.php::importFromExcel()
Modules/Account/Services/AccountService.php::toggleActive()
```

They are replaced by the query/read service, canonical import service, and explicit activation actions defined in this specification.

## Change Control

Changes to these decisions require updating this specification before implementation:

- Canonical user model.
- Supported account types.
- Identity-profile cardinality.
- Identity/tax uniqueness.
- Import schema and version.
- Password or role import behavior.
- File visibility/retention.
- Permission catalog.
- Archive/purge semantics.
- Transaction boundaries.

Implementation details that preserve these contracts may evolve without changing the specification.
