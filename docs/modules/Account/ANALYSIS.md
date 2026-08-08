# Modules/Account - Analysis

Generated: 2026-06-14

Scope: static analysis of `Modules/Account` only, following:

`Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared Components -> Service -> Import/Export -> Model -> Migration`

No application code was changed.

## Executive Summary

`Modules/Account` is intended to own administrative account management for employee and customer accounts, including profile data, identity/tax documents, roles, import/export, activation, and deletion.

The normal web flow is small and easy to follow:

1. `Modules/Account/routes/web.php`
2. `Modules/Account/Http/Controllers/AccountController.php`
3. `Modules/Account/resources/views/pages/*.blade.php`
4. `Modules/Account/Livewire/Accounts/Index.php` or `Form.php`
5. `Modules/Account/resources/views/livewire/accounts/*.blade.php`
6. `Modules/Account/Services/AccountService.php` and `AccountImportService.php`
7. Account models and five migrations

The module is not production-safe in its current form. The highest-risk findings are:

- **P0:** Account permissions are declared but not enforced on routes or Livewire actions.
- **P0:** Import can create arbitrary roles, including `Super Admin`, and assign them to imported users.
- **P0:** Authentication uses `App\Models\User`, while Account CRUD uses `Modules\Account\Models\User`; role morph types and Super Admin protections are inconsistent.
- **P0:** Identity documents are stored on the public disk without server-side file validation.
- **P1:** The multi-sheet importer writes field names that do not exist in the models/migrations.
- **P1:** The module's exported workbook cannot be imported by its active importer.
- **P1:** The public API route targets an empty controller with no `index()` method.
- **P1:** Unbounded "All", export, selection, and import paths can load full datasets into memory.

## 1. Module Purpose

The module provides:

- Administrative listing, searching, filtering, activation, deletion, bulk deletion, import, and export of accounts.
- Creation and editing of `employee` and `customer` account types.
- Employee profile data such as employee code, department, position, and joined date.
- Customer profile data such as customer code, gender, birthday, and address.
- Identity and tax data, including uploaded identity document images.
- Role display and role import.

Module manifest:

- `Modules/Account/config/module.php`
  - Type: `domain`
  - Enabled: `true`
  - Declared permissions:
    - `view_account`
    - `create_account`
    - `edit_account`
    - `delete_account`

## 2. Route List

### Web Routes

All web routes are declared in `Modules/Account/routes/web.php`.

Common middleware:

- `web`
- `auth:admin`

Common prefix: `/admin/accounts`

Common name prefix: `admin.accounts.`

| Method | URI | Name | Controller | Result |
|---|---|---|---|---|
| GET | `/admin/accounts` | `admin.accounts.index` | `AccountController@index` | Account list page |
| GET | `/admin/accounts/create` | `admin.accounts.create` | `AccountController@create` | Account creation page |
| GET | `/admin/accounts/{id}/edit` | `admin.accounts.edit` | `AccountController@edit` | Account edit page |

The `{id}` edit parameter is constrained to a number, but no model binding or authorization occurs in the controller.

### API Route

Declared in `Modules/Account/routes/api.php`:

| Method | Effective URI | Controller | Middleware | Status |
|---|---|---|---|---|
| GET | `/api/account` | `Modules\Account\Http\Controllers\Api\AccountController@index` | `api` only | Broken: controller has no `index()` method |

The commented route block would have used `auth:sanctum`, but the active route is public.

## 3. Controllers

### Web Controller

File: `Modules/Account/Http/Controllers/AccountController.php`

Public methods:

- `index(): View`
  - Returns `Account::pages.index`.
- `create(): View`
  - Returns `Account::pages.create`.
- `edit(int $id): View`
  - Returns `Account::pages.edit` and passes the raw numeric ID.

The controller is presentation-only. It performs no model query, validation, policy check, or permission check.

### API Controller

File: `Modules/Account/Http/Controllers/Api/AccountController.php`

- Contains no public endpoint method.
- Imports `Illuminate\Http\Request` but does not use it.
- The active GET route calls a missing `index()` method.

## 4. Page Blade Files

### Active Pages

- `Modules/Account/resources/views/pages/index.blade.php`
  - Extends `Admin::layouts.master`.
  - Mounts `@livewire('account.accounts.index')`.

- `Modules/Account/resources/views/pages/create.blade.php`
  - Extends `Admin::layouts.master`.
  - Mounts `@livewire('account.accounts.form')`.

- `Modules/Account/resources/views/pages/edit.blade.php`
  - Extends `Admin::layouts.master`.
  - Mounts `@livewire('account.accounts.form', ['id' => $id])`.

### Scaffold Page

- `Modules/Account/resources/views/account.blade.php`
  - Generic module scaffold page.
  - Includes `Account::components.placeholder`.
  - No route or other repository reference was found.

## 5. Livewire PHP Classes

### Account List

File: `Modules/Account/Livewire/Accounts/Index.php`

Traits:

- `WithPagination`
- `WithFileUploads`

Injected services:

- `Modules\Account\Services\AccountService`
- `Modules\Account\Services\AccountImportService`

Public state:

- Search/filter/pagination: `$search`, `$accountType`, `$isActive`, `$perPage`, `$perPageOptions`
- Selection: `$selectedIds`, `$selectAll`
- Import: `$importFile`, `$importReport`

Public methods:

- `boot(AccountService, AccountImportService): void`
- `updatedSearch(): void`
- `updatedAccountType(): void`
- `updatedIsActive(): void`
- `updatedPerPage(): void`
- `updatedSelectAll(bool $value): void`
- `updatedSelectedIds(): void`
- `toggleActive(int $id): void`
- `delete(int $id): void`
- `bulkDelete(): void`
- `import(): void`
- `importAccounts(): void`
- `clearImportReport(): void`
- `export(): BinaryFileResponse`
- `render(): View`

Protected methods:

- `rules(): array`
- `messages(): array`
- `filters(): array`
- `resetSelection(): void`

Behavior:

- Lists accounts through `AccountService::paginateForAdmin()`.
- Select-all obtains every matching deletable ID.
- Mutating actions call service methods directly.
- Stores uploaded import files under `storage/app/imports/account`.
- Displays raw caught exception messages to the user.

### Account Form

File: `Modules/Account/Livewire/Accounts/Form.php`

Trait:

- `WithFileUploads`

Injected service:

- `Modules\Account\Services\AccountService`

Public methods:

- `boot(AccountService): void`
- `mount(?int $id = null): void`
- `updatedAccountType(): void`
- `save(): void`
- `render()`

Protected methods:

- `rules(): array`
- `messages(): array`

Private methods:

- `storeIdentityImage(?TemporaryUploadedFile, ?string, string): ?string`
- `fillForm(User): void`

Behavior:

- Loads an account by numeric ID through `AccountService::find()`.
- Creates or updates the user and corresponding employee/customer profile.
- Stores identity images before the database service transaction runs.
- Redirects newly created accounts to their edit page.

## 6. Livewire Blade Views

### Account Index View

File: `Modules/Account/resources/views/livewire/accounts/index.blade.php`

Features:

- Search by name, email, or phone.
- Account-type and active-status filters.
- Per-page selection including `All`.
- Bulk deletion.
- Excel import and export.
- Account role, employee/customer profile, and status display.
- Active-status toggle.
- Edit and delete actions.

No `@can`, `@role`, or equivalent visibility checks exist around create, import, export, toggle, edit, delete, or bulk-delete controls.

### Account Form View

File: `Modules/Account/resources/views/livewire/accounts/form.blade.php`

Sections:

- Basic account fields.
- Employee profile fields.
- Customer profile fields.
- Identity/tax profile fields.
- Three identity image uploads.

The view renders validation errors for identity and upload fields, but those fields are absent from the Livewire validation rules.

### Placeholder View

File: `Modules/Account/resources/views/livewire/placeholder.blade.php`

- Contains only a scaffold comment.
- No reference was found.

## 7. Shared Components

External shared presentation dependency:

- `Admin::layouts.master`, used by:
  - `Modules/Account/resources/views/pages/index.blade.php`
  - `Modules/Account/resources/views/pages/create.blade.php`
  - `Modules/Account/resources/views/pages/edit.blade.php`
  - `Modules/Account/resources/views/account.blade.php`

Module-local component:

- `Modules/Account/resources/views/components/placeholder.blade.php`
  - Used only by the apparently unused `Modules/Account/resources/views/account.blade.php`.

Pagination:

- `Modules/Account/resources/views/livewire/accounts/index.blade.php` calls `$accounts->links()`, relying on the application's configured pagination view.

Import/export:

- The active Account UI does not use `Modules/Shared/Livewire/ImportExport/Panel.php`.
- `Modules/Account/Services/AccountImportService.php` does not extend `Modules/Shared/Services/ImportExport/BaseImportExportService.php`.

## 8. Services and Public Methods

### AccountService

File: `Modules/Account/Services/AccountService.php`

Public methods:

- `paginate(array $filters = [], int|string $perPage = 10): LengthAwarePaginator|Collection`
  - Filters and eagerly loads roles/profiles.
  - Returns an unbounded collection when `$perPage === 'All'`.
  - No direct caller was found in the current module flow.

- `find(int $id): User`
  - Loads roles, employee/customer/identity profiles, and metas.

- `create(array $data): User`
  - Transactionally creates a user and synchronizes profile/identity data.

- `update(int $id, array $data): User`
  - Transactionally updates the user and synchronizes profile/identity data.

- `delete(int $id): void`
  - Attempts to protect Super Admin.
  - Soft-deletes profiles, manually deletes role/permission pivots, then force-deletes the user.

- `bulkDelete(array $ids): array`
  - Repeats the single-delete logic inside one transaction.

- `getDeletableIds(array $filters = []): Collection`
  - Loads all matching users, filters Super Admin in PHP, and returns all IDs.

- `toggleActive(int $id): User`
  - Toggles account activation without a Super Admin check.

- `exportRows(array $filters = []): Collection`
  - Loads all matching users and maps them to Vietnamese column headers.

- `exportToExcel(array $filters = []): string`
  - Writes an XLSX file to `storage/app/public/exports`.

- `importFromExcel(string $filePath): int`
  - Legacy single-sheet importer.
  - No caller was found in the active Account flow.

- `paginateForAdmin(array $filters = [])`
  - Active list query used by the Livewire index.

- `export(array $filters = [])`
  - Downloads the generated XLSX file.
  - Uses `deleteFileAfterSend(false)`, retaining exported files.

Private methods:

- `userPayload(array $data, bool $isUpdate = false): array`
- `syncProfile(User $user, array $data): void`
- `syncIdentityProfile(User $user, array $data): void`

### AccountImportService

File: `Modules/Account/Services/AccountImportService.php`

Public method:

- `import(string $filePath): array`
  - Requires five worksheets.
  - Validates all sheets before writing.
  - Imports all records inside one database transaction.
  - Returns a structured report.

Required sheets:

- `users`
- `employee_profiles`
- `customer_profiles`
- `user_identity_profiles`
- `user_roles`

The service also contains protected validation, persistence, lookup, normalization, and reporting methods.

## 9. Models and Database Tables

### User

File: `Modules/Account/Models/User.php`

Table: `users`

Traits:

- `Notifiable`
- `SoftDeletes`
- `HasRoles`

Guard: `admin`

Relationships:

- `accountRoles()` -> belongs-to-many `Spatie\Permission\Models\Role`
- `metas()` -> has-many `UserMeta`
- `identityProfile()` -> has-one `UserIdentityProfile`
- `employeeProfile()` -> has-one `EmployeeProfile`
- `customerProfile()` -> has-one `CustomerProfile`
- `identityProfiles()` -> has-many `UserIdentityProfile`

Helper:

- `isSuperAdmin(): bool`

Important ownership conflict:

- Authentication provider: `config/auth.php` defaults to `App\Models\User`.
- Account services: `Modules/Account/Models/User.php`.
- Both models operate on `users`.

### EmployeeProfile

File: `Modules/Account/Models/EmployeeProfile.php`

Table: `employee_profiles`

Relationship:

- `user()` -> belongs-to `Modules\Account\Models\User`

Soft deletes: yes.

### CustomerProfile

File: `Modules/Account/Models/CustomerProfile.php`

Table: `customer_profiles`

Relationship:

- `user()` -> belongs-to `Modules\Account\Models\User`

Soft deletes: yes.

### UserIdentityProfile

File: `Modules/Account/Models/UserIdentityProfile.php`

Table: `user_identity_profiles`

Relationship:

- `user()` -> belongs-to `Modules\Account\Models\User`

Soft deletes: yes.

### UserMeta

File: `Modules/Account/Models/UserMeta.php`

Table: `user_metas`

Relationship:

- `user()` -> belongs-to `Modules\Account\Models\User`

### Account

File: `Modules/Account/Models/Account.php`

- Empty scaffold model.
- Defaults to an `accounts` table, but this module has no `accounts` migration.
- No reference was found.

## 10. Import/Export Classes

### Active Multi-Sheet Import

File: `Modules/Account/Services/AccountImportService.php`

Used by:

- `Modules/Account/Livewire/Accounts/Index.php`

Format:

- XLSX/XLS with five required sheets and English snake-case headers.

### Active Export

File: `Modules/Account/Services/AccountService.php`

Methods:

- `exportRows()`
- `exportToExcel()`
- `export()`

Format:

- One worksheet with Vietnamese display headers.

### Legacy Single-Sheet Import

File: `Modules/Account/Services/AccountService.php`

Method:

- `importFromExcel()`

Format:

- One worksheet using the Vietnamese headers produced by `exportRows()`.

No active caller was found.

### Shared Framework Not Used

Relevant shared files:

- `Modules/Shared/Services/ImportExport/BaseImportExportService.php`
- `Modules/Shared/Livewire/ImportExport/Panel.php`

The Account module has its own parallel import/export implementation rather than using the shared contract identified in `ROADMAP.md`.

## 11. Migrations

### Update Users

File: `Modules/Account/database/migrations/2026_05_26_143653_update_users_for_account.php`

- Adds `users.account_type`.
- Default: `customer`.
- Intended values: `employee` and `customer`.
- `down()` is empty.

### Employee Profiles

File: `Modules/Account/database/migrations/2026_05_26_143725_employee_profiles.php`

Table: `employee_profiles`

Notable constraints:

- Unique `user_id`.
- Unique nullable `employee_code`.
- Foreign key to `users` with cascade delete.
- Soft deletes.

### Customer Profiles

File: `Modules/Account/database/migrations/2026_05_26_143744_customer_profiles.php`

Table: `customer_profiles`

Notable constraints:

- Unique `user_id`.
- Unique nullable `customer_code`.
- Foreign key to `users` with cascade delete.
- Soft deletes.

### User Metas

File: `Modules/Account/database/migrations/2026_05_26_143758_user_metas.php`

Table: `user_metas`

Notable constraints:

- Unique pair: `user_id`, `key`.
- Foreign key to `users` with cascade delete.

### User Identity Profiles

File: `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php`

Table: `user_identity_profiles`

Notable constraints:

- Unique `user_id`, enforcing one identity profile per user.
- Foreign key to `users` with cascade delete.
- Soft deletes.
- Non-unique index on `identity_type`, `identity_number`.
- Non-unique index on `tax_code`.

## 12. Authorization and Security Risks

| Priority | Finding | Exact file path |
|---|---|---|
| **P0** | The module declares account permissions but web routes enforce only `auth:admin`. Any user authenticated through the admin guard can reach list, create, and edit pages. | `Modules/Account/config/module.php`; `Modules/Account/routes/web.php` |
| **P0** | Livewire actions have no policy, gate, or permission checks. Direct Livewire requests can invoke activation, deletion, bulk deletion, import, export, create, and update regardless of whether UI links are hidden elsewhere. | `Modules/Account/Livewire/Accounts/Index.php`; `Modules/Account/Livewire/Accounts/Form.php` |
| **P0** | The importer accepts arbitrary `role_name` and `guard_name`, creates missing roles, and assigns them. An account importer can create/assign `Super Admin` or another privileged role. | `Modules/Account/Services/AccountImportService.php` |
| **P0** | Authentication uses `App\Models\User`, while Account uses `Modules\Account\Models\User`. Role morph records depend on the model class. `accountRoles()` hard-codes `App\Models\User`, Spatie methods on the Account model use its own morph class, and pivot deletion filters `Modules\Account\Models\User`. Super Admin detection, role assignment, and pivot cleanup can therefore disagree. | `Modules/Account/Models/User.php`; `Modules/Account/Services/AccountService.php`; `Modules/Account/Services/AccountImportService.php`; `config/auth.php`; `app/Models/User.php` |
| **P0** | `toggleActive()` does not protect Super Admin. Even if delete protection works, an authorized caller can deactivate a Super Admin account. | `Modules/Account/Services/AccountService.php`; `Modules/Account/Livewire/Accounts/Index.php` |
| **P0** | Identity document uploads have no Livewire rules for file type, MIME, image validity, or size. They are stored on the public disk and rendered through public URLs. These files contain sensitive identity data. | `Modules/Account/Livewire/Accounts/Form.php`; `Modules/Account/resources/views/livewire/accounts/form.blade.php` |
| **P1** | Exported account PII is written under the public disk and retained because `deleteFileAfterSend(false)` is used. Timestamp-based filenames accumulate and may be web-accessible. | `Modules/Account/Services/AccountService.php` |
| **P1** | Imported source workbooks are stored under `storage/app/imports/account` and are not deleted after processing. | `Modules/Account/Livewire/Accounts/Index.php` |
| **P1** | Raw exception messages are returned in the import report and session flash, potentially exposing SQL, paths, schema, or package details. | `Modules/Account/Livewire/Accounts/Index.php`; `Modules/Account/Services/AccountImportService.php` |
| **P1** | The active API route is public because `auth:sanctum` is commented out. It currently fails because the method is missing, but it is unsafe scaffolding to leave enabled. | `Modules/Account/routes/api.php`; `Modules/Account/Http/Controllers/Api/AccountController.php` |
| **P1** | Identity numbers and tax codes have indexes but no uniqueness policy. Duplicate sensitive identifiers can be attached to different users. | `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php` |

## 13. Validation Problems

| Priority | Finding | Exact file path |
|---|---|---|
| **P0** | Upload fields `front_image_upload`, `back_image_upload`, and `portrait_4x6_image_upload` are not validated despite being accepted and stored. | `Modules/Account/Livewire/Accounts/Form.php`; `Modules/Account/resources/views/livewire/accounts/form.blade.php` |
| **P1** | All identity/tax fields are absent from `rules()`: `identity_type`, `identity_number`, `issued_date`, `issued_place`, `tax_code`, `tax_registered_name`, `tax_address`, and `identity_note`. The Blade view displays error slots that can never be populated by current rules. | `Modules/Account/Livewire/Accounts/Form.php`; `Modules/Account/resources/views/livewire/accounts/form.blade.php` |
| **P1** | `employee_code` and `customer_code` are required by account type but are not validated as unique. Duplicate values fail at the database layer instead of producing predictable validation errors. | `Modules/Account/Livewire/Accounts/Form.php`; `Modules/Account/database/migrations/2026_05_26_143725_employee_profiles.php`; `Modules/Account/database/migrations/2026_05_26_143744_customer_profiles.php` |
| **P1** | Password validation is only `min:8`; it does not use Laravel's configurable `Password` rule, compromised-password checks, or application policy. | `Modules/Account/Livewire/Accounts/Form.php` |
| **P1** | The list filter sends `is_active`, but `paginateForAdmin()` checks `status`. The active-status filter therefore does not affect the list query. | `Modules/Account/Livewire/Accounts/Index.php`; `Modules/Account/Services/AccountService.php` |
| **P1** | Import UI accepts `.xlsx,.csv`, while validation allows `.xlsx,.xls` and rejects CSV. The accepted client extensions and server rules disagree. | `Modules/Account/resources/views/livewire/accounts/index.blade.php`; `Modules/Account/Livewire/Accounts/Index.php` |
| **P1** | Import allows account type `collaborator`, while the form and account migration define only `employee` and `customer`. Such users receive neither supported profile type. | `Modules/Account/Services/AccountImportService.php`; `Modules/Account/Livewire/Accounts/Form.php`; `Modules/Account/database/migrations/2026_05_26_143653_update_users_for_account.php` |
| **P1** | Employee import validates/writes `hire_date` and `avatar_4x6_path`, but the model/migration use `joined_date` and contain no `avatar_4x6_path` column. Values are discarded or fail depending on mass-assignment settings. | `Modules/Account/Services/AccountImportService.php`; `Modules/Account/Models/EmployeeProfile.php`; `Modules/Account/database/migrations/2026_05_26_143725_employee_profiles.php` |
| **P1** | Customer import validates/writes `customer_group`, `company_name`, and `tax_code`, but these columns are absent from `customer_profiles` and its model fillable list. | `Modules/Account/Services/AccountImportService.php`; `Modules/Account/Models/CustomerProfile.php`; `Modules/Account/database/migrations/2026_05_26_143744_customer_profiles.php` |
| **P1** | Identity import validates/writes `full_name`, `front_image_path`, and `back_image_path`, while the schema/model use `front_image` and `back_image` and have no `full_name`. | `Modules/Account/Services/AccountImportService.php`; `Modules/Account/Models/UserIdentityProfile.php`; `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php` |
| **P1** | User import includes `note`, but `Modules\Account\Models\User` has no fillable `note` and the Account user migration does not add that column. | `Modules/Account/Services/AccountImportService.php`; `Modules/Account/Models/User.php`; `Modules/Account/database/migrations/2026_05_26_143653_update_users_for_account.php` |
| **P1** | `AccountService::importFromExcel()` has almost no row-level validation and silently skips blank-email rows. It accepts malformed dates, duplicate profile codes, and weak state values until persistence fails. | `Modules/Account/Services/AccountService.php` |
| **P2** | Joined date, birthday, and issued date have no domain bounds, such as preventing unreasonable future dates. | `Modules/Account/Livewire/Accounts/Form.php` |

## 14. Transaction and Data Integrity Risks

| Priority | Finding | Exact file path |
|---|---|---|
| **P0** | Role cleanup uses `model_type = Modules\Account\Models\User`, while the custom role relation reads `model_type = App\Models\User`. A force-deleted account can leave orphaned role/permission pivots or remove a different morph set than the one displayed. | `Modules/Account/Services/AccountService.php`; `Modules/Account/Models/User.php` |
| **P1** | Identity images are stored before `AccountService::create()` or `update()` begins its database transaction. A later database failure leaves orphaned public files. | `Modules/Account/Livewire/Accounts/Form.php`; `Modules/Account/Services/AccountService.php` |
| **P1** | Replacing identity images does not delete the old file. Deleting an account also does not delete identity image files. Sensitive files can remain after records are changed or removed. | `Modules/Account/Livewire/Accounts/Form.php`; `Modules/Account/Services/AccountService.php` |
| **P1** | `delete()` and `bulkDelete()` soft-delete profile rows and then force-delete the user. Database cascade deletion then permanently removes related rows, defeating the apparent soft-delete recovery model. | `Modules/Account/Services/AccountService.php`; `Modules/Account/Models/User.php`; `Modules/Account/Models/EmployeeProfile.php`; `Modules/Account/Models/CustomerProfile.php`; `Modules/Account/Models/UserIdentityProfile.php` |
| **P1** | `bulkDelete()` performs the entire selected set in one transaction with repeated per-user queries. A single failure rolls back all deletions, but large selections create a long-running transaction and lock window. | `Modules/Account/Services/AccountService.php` |
| **P1** | Identity import uses `updateOrCreate` with `user_id`, `identity_type`, and `identity_number` as lookup keys, while the database permits only one row per `user_id`. Changing an existing identifier can attempt a second insert and violate the unique user constraint. | `Modules/Account/Services/AccountImportService.php`; `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php` |
| **P1** | The active multi-sheet import is transactionally all-or-nothing, which is good, but it loads and validates the entire workbook before starting the transaction. Very large files can exhaust memory before rollback protection matters. | `Modules/Account/Services/AccountImportService.php` |
| **P1** | The `account_type` migration cannot roll back because `down()` is empty. | `Modules/Account/database/migrations/2026_05_26_143653_update_users_for_account.php` |
| **P2** | `toggleActive()` is a standalone read then update with no optimistic locking. Concurrent toggles can overwrite each other. | `Modules/Account/Services/AccountService.php` |

Positive transaction behavior:

- `create()`, `update()`, `delete()`, and `bulkDelete()` use database transactions in `Modules/Account/Services/AccountService.php`.
- The five-sheet write phase uses one database transaction in `Modules/Account/Services/AccountImportService.php`.

## 15. N+1 and Query Performance Risks

| Priority | Finding | Exact file path |
|---|---|---|
| **P1** | The index query eager-loads `roles`, but the view calls `accountRoles` and `isSuperAdmin()`. `isSuperAdmin()` invokes `accountRoles()` as a query, producing an extra role query per row. The eager-loaded `roles` relation does not satisfy the custom `accountRoles` relation. | `Modules/Account/Services/AccountService.php`; `Modules/Account/Models/User.php`; `Modules/Account/resources/views/livewire/accounts/index.blade.php` |
| **P1** | `paginateForAdmin()` eager-loads `identityProfiles`, but the index view does not use identity data. This adds unnecessary queries and hydration. | `Modules/Account/Services/AccountService.php`; `Modules/Account/resources/views/livewire/accounts/index.blade.php` |
| **P1** | Selecting `All` returns every matching user and all eager-loaded relationships in one Livewire render. | `Modules/Account/Services/AccountService.php`; `Modules/Account/Livewire/Accounts/Index.php` |
| **P1** | Select-all loads every matching user and role relation, then calls `isSuperAdmin()` for each user before returning IDs. | `Modules/Account/Services/AccountService.php`; `Modules/Account/Livewire/Accounts/Index.php` |
| **P1** | Export loads every matching user and profile into a collection before FastExcel writes the file. It does not use a generator, lazy collection, queue, or chunking. | `Modules/Account/Services/AccountService.php` |
| **P1** | Import validation repeatedly queries users and roles per row through `emailExistsInFileOrDatabase()`, `getAccountType()`, `findUserByEmail()`, and `isSuperAdmin()`. Persistence then queries users again. | `Modules/Account/Services/AccountImportService.php` |
| **P1** | Role import executes `firstOrCreate()` and role checks for every row instead of preloading allowed roles. | `Modules/Account/Services/AccountImportService.php` |
| **P2** | Search uses `%term%` across name, email, and phone, preventing ordinary B-tree indexes from helping with leading-wildcard searches. | `Modules/Account/Services/AccountService.php` |

## 16. Duplicate Logic and Boundary Problems

| Priority | Finding | Exact file path |
|---|---|---|
| **P0** | Two user models own the same `users` table. Authentication and most Admin code use `App\Models\User`; Account CRUD uses `Modules\Account\Models\User`. | `app/Models/User.php`; `Modules/Account/Models/User.php`; `config/auth.php` |
| **P1** | `paginate()` and `paginateForAdmin()` are parallel list implementations with different eager loads and filter names. | `Modules/Account/Services/AccountService.php` |
| **P1** | Search/account-type/active filter construction is repeated across `paginate()`, `getDeletableIds()`, `exportRows()`, and `paginateForAdmin()`. The duplication has already caused the `status` versus `is_active` bug. | `Modules/Account/Services/AccountService.php` |
| **P1** | There are two incompatible Account import paths: the active five-sheet `AccountImportService::import()` and the unused single-sheet `AccountService::importFromExcel()`. | `Modules/Account/Services/AccountImportService.php`; `Modules/Account/Services/AccountService.php` |
| **P1** | Export produces one Vietnamese-header worksheet, while the active importer requires five English snake-case worksheets. Exported data cannot be round-tripped through the active UI import. | `Modules/Account/Services/AccountService.php`; `Modules/Account/Services/AccountImportService.php`; `Modules/Account/Livewire/Accounts/Index.php` |
| **P1** | Account import/export duplicates the shared framework instead of extending it or defining a compatible multi-sheet adapter. | `Modules/Account/Services/AccountImportService.php`; `Modules/Account/Services/AccountService.php`; `Modules/Shared/Services/ImportExport/BaseImportExportService.php`; `Modules/Shared/Livewire/ImportExport/Panel.php` |
| **P1** | Single and bulk delete repeat profile and pivot deletion logic. | `Modules/Account/Services/AccountService.php` |
| **P1** | `identityProfile()` and `identityProfiles()` describe the same table as both one-to-one and one-to-many, while the migration enforces unique `user_id`. | `Modules/Account/Models/User.php`; `Modules/Account/database/migrations/2026_05_27_000005_create_user_identity_profiles_table.php` |
| **P2** | `userPayload()` accepts `$isUpdate`, but the parameter is never used. | `Modules/Account/Services/AccountService.php` |

## 17. Files That Look Unused or Suspicious

These are static-reference findings. Dynamic resolution can hide usage, so removal should follow route/component tests.

| Priority | File | Reason |
|---|---|---|
| **P1** | `Modules/Account/Http/Controllers/Api/AccountController.php` | Routed but empty; active route calls missing `index()`. Remove the route/controller or implement and secure it. |
| **P2** | `Modules/Account/Models/Account.php` | Empty scaffold model, no references found, and no `accounts` table migration. |
| **P2** | `Modules/Account/resources/views/account.blade.php` | Generic scaffold page with no route/reference found. |
| **P2** | `Modules/Account/resources/views/components/placeholder.blade.php` | Used only by the apparently unused scaffold page. |
| **P2** | `Modules/Account/resources/views/livewire/placeholder.blade.php` | No component class or view reference found. |
| **P2** | `Modules/Account/readme.md` | Empty file. |
| **P2** | `Modules/Account/Services/AccountService.php::paginate()` | No active caller found; overlaps `paginateForAdmin()`. |
| **P2** | `Modules/Account/Services/AccountService.php::importFromExcel()` | No active caller found; conflicts with the active multi-sheet importer. |

## 18. Refactor Plan

### P0 Critical

1. **P0 - Enforce Account permissions at route and action level.**
   - Add `view_account`, `create_account`, `edit_account`, and `delete_account` middleware/policies to `Modules/Account/routes/web.php`.
   - Authorize every public mutation/download method in `Modules/Account/Livewire/Accounts/Index.php` and `Modules/Account/Livewire/Accounts/Form.php`.
   - Do not rely on menu visibility in `Modules/Admin/data/menus.json`.

2. **P0 - Remove arbitrary role creation and privilege assignment from import.**
   - In `Modules/Account/Services/AccountImportService.php`, accept only a server-side allowlist of existing roles.
   - Require a separate privileged permission for role assignment.
   - Explicitly forbid assigning `Super Admin` through ordinary account import.

3. **P0 - Establish one canonical user model and role morph type.**
   - Reconcile `Modules/Account/Models/User.php` with `app/Models/User.php` and `config/auth.php`.
   - Ensure authentication, Spatie roles, Super Admin checks, relationships, and pivot cleanup use the same model morph class.
   - Add regression tests covering Super Admin display, activation, deletion, role import, and pivot cleanup.

4. **P0 - Protect Super Admin from all destructive state changes.**
   - Add protection to `toggleActive()` as well as deletion in `Modules/Account/Services/AccountService.php`.
   - Enforce protection in a policy/domain invariant rather than only in the Livewire UI.

5. **P0 - Move identity documents to private storage and validate uploads.**
   - Add image/MIME/extension/size rules for upload properties in `Modules/Account/Livewire/Accounts/Form.php`.
   - Store identity files on a private disk.
   - Serve them through an authorized download/preview controller.
   - Add cleanup for replacement, failed transactions, and account deletion.

### P1 Important

1. **P1 - Repair the active Account import contract.**
   - Align every field in `Modules/Account/Services/AccountImportService.php` with:
     - `Modules/Account/Models/EmployeeProfile.php`
     - `Modules/Account/Models/CustomerProfile.php`
     - `Modules/Account/Models/UserIdentityProfile.php`
     - Account migrations under `Modules/Account/database/migrations`
   - Resolve `hire_date`/`joined_date`, image path names, customer fields, and identity fields.

2. **P1 - Standardize import/export into one round-trip format.**
   - Choose one supported workbook contract.
   - Make exports from `Modules/Account/Services/AccountService.php` importable by the active importer.
   - Remove or migrate `AccountService::importFromExcel()`.
   - Integrate with `Modules/Shared/Services/ImportExport/BaseImportExportService.php` where practical, or add a documented multi-sheet extension.

3. **P1 - Complete form validation.**
   - Add identity, tax, and upload rules in `Modules/Account/Livewire/Accounts/Form.php`.
   - Add unique rules for employee/customer codes.
   - Use Laravel's `Password` rule.
   - Add service-level validation/invariants for non-Livewire callers.

4. **P1 - Fix list filters and centralize query construction.**
   - Fix `is_active` handling in `AccountService::paginateForAdmin()`.
   - Extract one filtered account query used by list, select-all, and export.
   - Remove duplicate `paginate()`/`paginateForAdmin()` behavior.

5. **P1 - Remove N+1 role checks and unused eager loads.**
   - Use one canonical `roles` relation.
   - Evaluate Super Admin from the eager-loaded collection.
   - Remove `identityProfiles` from list queries unless displayed.

6. **P1 - Bound and queue heavy operations.**
   - Replace `All`, full-ID selection, full-collection export, and full-workbook processing in:
     - `Modules/Account/Livewire/Accounts/Index.php`
     - `Modules/Account/Services/AccountService.php`
     - `Modules/Account/Services/AccountImportService.php`
   - Use caps, lazy collections/chunks, queued jobs, progress reporting, and private temporary files.

7. **P1 - Define deletion and recovery semantics.**
   - Decide between soft deletion and permanent deletion.
   - Stop mixing profile soft deletes with user force deletion in `Modules/Account/Services/AccountService.php`.
   - Centralize single/bulk deletion logic and clean related private files.

8. **P1 - Secure and repair the API surface.**
   - Remove the active route in `Modules/Account/routes/api.php` if no API is required.
   - Otherwise implement `index()` in `Modules/Account/Http/Controllers/Api/AccountController.php`, add Sanctum authorization, permissions, pagination, and a resource serializer.

9. **P1 - Repair migration reversibility and identity constraints.**
   - Implement `down()` in `Modules/Account/database/migrations/2026_05_26_143653_update_users_for_account.php`.
   - Define whether identity number/tax code must be unique and enforce the chosen rule.
   - Keep model cardinality consistent with unique `user_id`.

10. **P1 - Sanitize errors and manage temporary files.**
    - Log full exceptions but return generic user messages from `Modules/Account/Livewire/Accounts/Index.php`.
    - Delete source imports and generated exports after use.
    - Do not store PII exports under the public disk.

11. **P1 - Add Account integration tests.**
    - Cover routes, permissions, Livewire actions, canonical user/role behavior, Super Admin protections, transactions, import validation, export round trips, private identity files, and migration rollback.

### P2 Nice to Have

1. **P2 - Remove confirmed scaffold artifacts.**
   - Review and remove:
     - `Modules/Account/Models/Account.php`
     - `Modules/Account/resources/views/account.blade.php`
     - `Modules/Account/resources/views/components/placeholder.blade.php`
     - `Modules/Account/resources/views/livewire/placeholder.blade.php`
     - `Modules/Account/readme.md`

2. **P2 - Add typed return values and relationship types.**
   - Add return types to model relationships and currently untyped service methods in:
     - `Modules/Account/Models/User.php`
     - `Modules/Account/Models/EmployeeProfile.php`
     - `Modules/Account/Models/CustomerProfile.php`
     - `Modules/Account/Models/UserIdentityProfile.php`
     - `Modules/Account/Models/UserMeta.php`
     - `Modules/Account/Services/AccountService.php`

3. **P2 - Improve search design.**
   - Replace leading-wildcard search in `Modules/Account/Services/AccountService.php` with an indexed search strategy appropriate to expected account volume.

4. **P2 - Remove dead parameters and duplicated mapping code.**
   - Remove the unused `$isUpdate` argument from `AccountService::userPayload()`.
   - Introduce explicit DTOs/mappers for account, employee, customer, identity, and import payloads.

## Recommended Implementation Order

1. **P0:** Lock routes and every Livewire action with Account permissions.
2. **P0:** Disable imported role creation/assignment until an allowlisted design is implemented.
3. **P0:** Consolidate the canonical user model and verify role morph data.
4. **P0:** Protect Super Admin activation/deletion and add tests.
5. **P0:** Make identity uploads private, validated, and lifecycle-managed.
6. **P1:** Align importer fields with the actual schema.
7. **P1:** Define one import/export round-trip contract.
8. **P1:** Complete validation and service invariants.
9. **P1:** Fix filtering, N+1 queries, and unbounded operations.
10. **P1:** Define deletion semantics, migration rollback, and API disposition.
11. **P2:** Remove confirmed scaffold/dead files and improve typing.

## Verification Notes

- This report is based on static source inspection.
- No code was refactored.
- No application files outside this analysis document were edited.
- Runtime route boot, migrations, and tests were not executed because PHP was unavailable in the current PowerShell environment.
