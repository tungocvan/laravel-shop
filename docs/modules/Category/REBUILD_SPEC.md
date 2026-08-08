# Category Rebuild Specification

Generated: 2026-06-15

Status: implementation specification with explicit confirmation gates.

Source documents:

- `ROADMAP.md`
- `docs/CODEX_BOOTSTRAP.md`
- `docs/AI_PROJECT_CONTEXT.md`
- `docs/modules/Category/ANALYSIS.md`
- `docs/modules/Category/REFACTOR_PLAN.md`

Reference notation:

- `Analysis §N` means section N of `docs/modules/Category/ANALYSIS.md`.
- `Refactor P0-N`, `P1-N`, or `P2-N` means the corresponding item in `docs/modules/Category/REFACTOR_PLAN.md`.

## 1. Goal

The rebuilt Category module must:

1. Make `Modules/Category` the canonical owner of category types, category hierarchy, Category Models, queries, business rules, and persistence. This addresses duplicate ownership in Admin, Product, Post, and Website. `[Analysis §16, §18; Refactor P1-8]`
2. Enforce the mandatory application flow and remove all direct Model/storage access from Livewire. `[Analysis §2, §6, §18; Refactor P1-1]`
3. Deny Category access and mutations unless the authenticated admin has the required named permission. `[Analysis §3, §12; Refactor P0-1]`
4. Prevent browser-controlled paths, IDs, type keys, and parent IDs from being trusted as authoritative persisted state. `[Analysis §6, §12; Refactor P0-2, P1-4]`
5. Persist valid acyclic category trees only and apply one confirmed child-handling rule on deletion. `[Analysis §9, §13, §14; Refactor P1-2]`
6. Make category, CategoryType, image, and migration operations transactionally safe or compensating where filesystem operations cannot join a database transaction. `[Analysis §14; Refactor P0-4, P1-3]`
7. Bound category list, parent-option, and recursive tree work for production-sized datasets. `[Analysis §15; Refactor P1-7]`
8. Preserve Laravel 12, Livewire 3.1, Tailwind CSS 4, and module boundaries without DTOs. `[Analysis §18; Refactor P1-1, P1-9]`
9. Provide focused tests before duplicate callers or deployed constraints are migrated. `[Analysis §19; Refactor P1-10]`
10. Defer import/export until a real workbook and behavior contract are approved. `[Analysis §11; Refactor P2-5]`

Implementation must stop before coding if any decision marked **Needs confirmation before coding** affects the selected delivery slice.

## 2. Target Architecture

### Required flow

```text
Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import
→ Export
→ Model
→ Migration
```

For normal CRUD, the Import and Export stages are skipped:

```text
Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ CategoryService / CategoryTypeService
→ Category / CategoryType
→ categories / category_types
```

For future import/export:

```text
Page Blade
→ shared.import-export.panel
→ Modules\Category\Services\ImportExport
→ Category Import/Export classes when justified
→ Modules\Shared\Services\ImportExport\BaseImportExportService
→ CategoryService domain invariants
→ Category / CategoryType
→ Database
```

This design resolves direct Model access and incomplete Service coverage. `[Analysis §2, §6, §9; Refactor P1-1]`

### Layer ownership

| Layer | Target responsibility | Prohibited behavior | Reference |
|---|---|---|---|
| Route | URL, name, middleware, scalar constraints, controller action | Model queries, business rules | `Analysis §3`; `Refactor P0-1, P2-2` |
| Controller | Return Page Blade and pass a scalar ID | Queries, validation, transactions | `Analysis §4`; `Refactor P2-2` |
| Page Blade | Extend `Admin::layouts.master`, define title, mount Livewire | Queries and feature logic | `Analysis §5`; `Refactor P2-2` |
| Livewire PHP | UI state, validation, authorization, confirmation, Service calls | Direct Model/storage queries, transactions | `Analysis §6, §18`; `Refactor P1-1, P1-4` |
| Livewire Blade | Render state, field errors, loading/disabled/empty states | Database/service calls | `Analysis §7`; `Refactor P2-3` |
| Shared Components | Reusable presentation behavior only | Category persistence or business rules | `Analysis §8`; `Refactor P1-9` |
| Service | Queries, hierarchy rules, slug normalization, transactions, file compensation, CRUD | Views, Livewire state, `request()` | `Analysis §9`; `Refactor P1-1, P1-2, P1-3` |
| Import | Future row mapping, normalization, row validation | UI and export behavior | `Analysis §11`; `Refactor P2-5` |
| Export | Future bounded query/mapping/template behavior | UI and import persistence | `Analysis §11`; `Refactor P2-5` |
| Model | ORM table, fillable, casts, relationships, simple scopes | Recursive business traversal and transactions | `Analysis §10`; `Refactor P1-5` |
| Migration | Schema, indexes, constraints, comments, forward corrections | Unconfirmed destructive changes | `Analysis §10, §14`; `Refactor P0-4, P1-6` |

### Target module files

Core target:

- `Modules/Category/routes/web.php`
- `Modules/Category/Http/Controllers/CategoryController.php`
- `Modules/Category/resources/views/pages/categories/index.blade.php`
- `Modules/Category/resources/views/pages/categories/create.blade.php`
- `Modules/Category/resources/views/pages/categories/edit.blade.php`
- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/resources/views/livewire/categories/category-table.blade.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
- `Modules/Category/Services/CategoryService.php`
- `Modules/Category/Services/CategoryTypeService.php`
- `Modules/Category/Models/Category.php`
- `Modules/Category/Models/CategoryType.php`
- Corrective migrations under `Modules/Category/database/migrations/`
- Tests under `Modules/Category/tests/Feature/` and `Modules/Category/tests/Unit/`

`CategoryTypeService` is selected as a separate Service because CategoryType deletion constraints, ordering, and lifecycle form a distinct responsibility. This converts the optional split in `Refactor P1-1` into the target design while avoiding a single broad Service.

Future optional files, blocked pending confirmation:

- `Modules/Category/Services/ImportExport.php`
- `Modules/Category/Import/`
- `Modules/Category/Export/`

### Module registration

The final module must have registration consistent with the project's `nwidart/laravel-modules` boot strategy. The current absence of `module.json` and a provider is unresolved. **Needs confirmation before coding.** `[Analysis §1, §18; Refactor risk control]`

## 3. Database Design

### Table: `category_types`

Purpose: dynamic taxonomy namespaces such as product, post, or menu.

| Column | Type | Null | Default | Constraint/meaning | Reference |
|---|---|---:|---|---|---|
| `type` | `varchar` | No | None | Primary immutable business key | `Analysis §10`; `Refactor P1-5` |
| `title` | `varchar` | No | None | Human-readable type title | `Analysis §10`; `Refactor P1-4` |
| `icon` | `varchar` or current compatible type | Yes | `null` | Presentation identifier only | `Analysis §10`; `Refactor P1-5` |
| `sort_order` | unsigned integer | No | `0` | Stable display order | `Analysis §6, §10`; `Refactor P1-5` |
| `is_active` | boolean | No | `true` | Whether the type can be selected/displayed | `Analysis §6, §10`; `Refactor P1-5` |
| `created_at` | timestamp | Yes | framework | Laravel timestamp | Existing schema |
| `updated_at` | timestamp | Yes | framework | Laravel timestamp | Existing schema |

Indexes and constraints:

- Primary key on `type`.
- Optional index on `is_active, sort_order` only if query plans justify it; do not add blindly. `[Analysis §15; Refactor P1-6, P1-7]`
- CategoryType deletion must be restricted while dependent categories exist. Cascade delete is prohibited. `[Analysis §10, §14; Refactor P0-4]`
- `type` should be treated as immutable once referenced. Renaming behavior is **Needs confirmation before coding** because it can affect all dependent categories and external callers.

### Table: `categories`

Purpose: hierarchical categories scoped by a CategoryType.

| Column | Type | Null | Default | Constraint/meaning | Reference |
|---|---|---:|---|---|---|
| `id` | unsigned big integer | No | auto | Primary key | Existing schema |
| `name` | `varchar` | No | None | Display name | `Analysis §10, §13`; `Refactor P1-4` |
| `slug` | `varchar` | Conditional | `null` currently | Normalized URL/business slug | `Analysis §10, §13`; `Refactor P1-4, P1-6` |
| `url` | `varchar` | Yes | `null` | Legacy/custom URL | `Analysis §10`; see confirmation gate |
| `icon` | text/current compatible type | Yes | `null` | Category icon metadata | `Analysis §10`; see confirmation gate |
| `can` | `varchar` | Yes | `null` | Legacy permission/menu metadata | `Analysis §10`; see confirmation gate |
| `type` | `varchar` | Conditional | Current `null` | FK to `category_types.type` | `Analysis §10`; `Refactor P1-6` |
| `type_title` | `varchar` | Yes | `null` | Denormalized legacy field | `Analysis §10`; see confirmation gate |
| `parent_id` | unsigned big integer | Yes | `null` | Self-reference for hierarchy | `Analysis §10`; `Refactor P1-2` |
| `description` | text | Yes | `null` | Category description | `Analysis §10`; `Refactor P1-5` |
| `image` | `varchar` | Yes | `null` | Server-owned path below Category storage namespace | `Analysis §12, §14`; `Refactor P0-2, P1-3` |
| `is_active` | boolean | No | `true` | Visibility state | `Analysis §10, §13`; `Refactor P1-4` |
| `sort_order` | unsigned integer | No | `0` | Sibling display order | `Analysis §10, §13`; `Refactor P1-4` |
| `meta_title` | `varchar` | Yes | `null` | SEO title | Existing canonical Model/schema |
| `meta_description` | `varchar` | Yes | `null` | SEO description | Existing canonical Model/schema |
| `created_at` | timestamp | Yes | framework | Laravel timestamp | Existing schema |
| `updated_at` | timestamp | Yes | framework | Laravel timestamp | Existing schema |

### Pending column decisions

The canonical write/read contract for `url`, `icon`, `can`, `type_title`, and `description` must be established by caller inventory before changing `$fillable` or dropping columns. **Needs confirmation before coding.** `[Analysis §10, §16, §18; Refactor P1-5, P1-8]`

`slug` nullability and uniqueness scope are unresolved:

- Option A: globally unique slug.
- Option B: unique per `type`.
- Option C: another confirmed business key.

No implementation may retain both redundant global and composite unique constraints. **Needs confirmation before coding.** `[Analysis §10; Refactor P1-6]`

`type` is required by current forms and relationships but nullable in the schema. Audit existing rows and category use cases before making it non-null. **Needs confirmation before coding.** `[Analysis §10; Refactor P1-6]`

### Indexes

Required or candidate indexes:

- Primary key: `categories.id`.
- Foreign-key index: `categories.type`.
- Foreign-key index: `categories.parent_id`.
- Filter index: `categories.is_active`.
- Tree/list candidate composite index: `(type, parent_id, sort_order)` if confirmed by query-plan tests.
- Slug unique index: exactly one approved uniqueness strategy.

Index decisions must match `CategoryService` query patterns and MySQL query plans. `[Analysis §15; Refactor P1-6, P1-7]`

### Foreign keys

- `categories.type -> category_types.type`
  - Delete behavior: `RESTRICT`/equivalent fail-closed behavior.
  - Update behavior: no cascade unless type renaming is explicitly approved.
  - `[Analysis §10, §14; Refactor P0-4]`

- `categories.parent_id -> categories.id`
  - Current `nullOnDelete()` must not be preserved by assumption.
  - Delete behavior depends on confirmed category child policy.
  - **Needs confirmation before coding.**
  - `[Analysis §6, §14; Refactor P1-2]`

### Constraints

Database constraints cannot enforce an acyclic tree. `CategoryService` must enforce:

- Parent exists.
- Parent belongs to the same type.
- Parent is not the category itself.
- Parent is not any descendant of the category.
- Resulting depth does not exceed the confirmed maximum.

Maximum hierarchy depth is **Needs confirmation before coding.** `[Analysis §9, §13, §15; Refactor P1-2]`

### Migration notes

1. Do not rewrite or rename deployed negative-year migrations until production migration history is verified. `[Analysis §10; Refactor P1-6 and Risk Control]`
2. Use forward-only corrective migrations for:
   - CategoryType delete restriction.
   - Approved slug unique constraint.
   - Approved `type` nullability.
   - Approved parent delete behavior.
   - Schema comments and necessary indexes.
3. Test all corrections on a production-like MySQL copy before deployment. `[Refactor P0-4, P1-6]`
4. Back up `categories` and `category_types` before constraint migrations. `[Refactor §7 Risk Control]`
5. Fresh-install ordering and rollback must be tested separately from upgrade migrations. `[Analysis §10; Refactor P1-6]`
6. Migration comments should describe type keys, status, hierarchy, storage path, and SEO fields. `[Analysis §10; Refactor P2-4]`

## 4. Model Design

### `Modules\Category\Models\Category`

Target ORM responsibilities:

- Explicit `$table = 'categories'`.
- Fillable fields must match the approved canonical write contract.
- Casts:
  - `id` integer where needed by project conventions.
  - `parent_id` integer/null.
  - `is_active` boolean.
  - `sort_order` integer.
- Relationships:
  - `typeInfo(): BelongsTo`
  - `parent(): BelongsTo`
  - `children(): HasMany`
- Simple scopes:
  - `scopeOfType(Builder $query, string $type)`
  - `scopeActive(Builder $query)`
  - `scopeRoot(Builder $query)`
  - `scopeSorted(Builder $query)`

No recursive traversal helper or business cycle detection belongs in the Model. `getAllChildrenIds()` must move to `CategoryService`. `[Analysis §10; Refactor P1-2, P1-5]`

Target fillable baseline:

- `name`
- `slug`
- `type`
- `parent_id`
- `description`
- `image`
- `is_active`
- `sort_order`
- `meta_title`
- `meta_description`

The inclusion of `url`, `icon`, `can`, and `type_title` is **Needs confirmation before coding** after cross-module caller inventory. `[Analysis §10, §16; Refactor P1-5, P1-8]`

Accessors/mutators:

- Do not add hierarchy/presentation accessors.
- Slug normalization belongs in `CategoryService`, not a Model mutator, so imports and all other callers use the same explicit invariant. `[Analysis §16; Refactor P1-1, P1-4]`
- Image URL presentation should be handled by a shared presentation helper/component if needed; the Model stores only the relative server-owned path. `[Analysis §8, §12; Refactor P0-2, P1-9]`

### `Modules\Category\Models\CategoryType`

Target ORM responsibilities:

- Explicit `$table = 'category_types'`.
- `$primaryKey = 'type'`.
- `$incrementing = false`.
- `$keyType = 'string'`.
- Fillable:
  - `type`
  - `title`
  - `icon`
  - `sort_order`
  - `is_active`
- Casts:
  - `sort_order` integer.
  - `is_active` boolean.
- Relationships:
  - `categories(): HasMany`
- Simple scopes:
  - `scopeActive(Builder $query)`
  - `scopeSorted(Builder $query)`

This corrects discarded `sort_order` and missing casts. `[Analysis §6, §10; Refactor P1-5]`

### Canonical ownership

The following duplicate Models must not remain canonical:

- `Modules/Admin/Models/Category.php`
- `Modules/Product/Models/Category.php`
- `Modules/Post/Models/Category.php`
- `Modules/Website/Models/Category.php`

They may be removed only after every caller is migrated and tested. `[Analysis §16, §18; Refactor P1-8]`

## 5. Service Design

### `Modules\Category\Services\CategoryService`

Responsibilities:

- Category list/search/filter/sort/pagination queries.
- Load one authorized category for editing.
- Return valid parent options.
- Normalize slug and persistence values.
- Enforce hierarchy and type invariants.
- Create/update/delete/toggle categories.
- Own database transactions.
- Own image storage coordination and compensation.
- Produce tree/list arrays without mutating Eloquent Models.

Public method contract:

| Method | Purpose | Transaction | Reference |
|---|---|---:|---|
| `paginateForAdmin(array $filters)` | Bounded list for admin table | No | `Analysis §15`; `Refactor P1-7` |
| `findForEdit(int $id)` | Resolve persisted edit record | No | `Analysis §6`; `Refactor P1-1, P1-4` |
| `parentOptions(string $type, ?int $excludeId)` | Return valid flattened parent choices | No | `Analysis §6, §9`; `Refactor P1-2, P1-7` |
| `create(array $data)` | Normalize, validate invariants, store image, create category | Yes plus storage compensation | `Analysis §9, §14`; `Refactor P1-3` |
| `update(int $id, array $data)` | Re-resolve record, validate, replace image safely, update | Yes plus storage compensation | `Analysis §6, §9, §14`; `Refactor P0-2, P1-3` |
| `delete(int $id)` | Delete using confirmed child policy and safe image cleanup | Yes plus post-commit cleanup | `Analysis §6, §14`; `Refactor P1-2, P1-3` |
| `setActive(int $id, bool $active)` | Explicit idempotent status assignment | Yes | `Analysis §6`; `Refactor P1-1, §11 integrity` |
| `normalizeSlug(?string $slug, string $name)` | Produce canonical slug | No | `Analysis §9, §16`; `Refactor P1-4` |

Method names are design contracts, not code. Return types should be Models, paginators, collections, or arrays already supported by project standards; no DTOs.

Business rules:

1. Re-resolve IDs from the database in every write method. Public Livewire state is not authoritative. `[Analysis §6, §12; Refactor P0-2, P1-4]`
2. `type` must reference a permitted CategoryType according to active/edit rules. Whether inactive types remain valid for existing records is **Needs confirmation before coding**.
3. Parent must exist, match type, not be self/descendant, and satisfy maximum depth. `[Analysis §9, §13; Refactor P1-2]`
4. Slug must be normalized and unique under the confirmed scope. `[Analysis §10, §13; Refactor P1-4, P1-6]`
5. Only relative image paths under the Category namespace are persisted/deleted. Caller-supplied old paths are ignored. `[Analysis §12; Refactor P0-2]`
6. Category deletion follows the confirmed reject/reparent/recursive policy. **Needs confirmation before coding.**
7. Generic internal exceptions must be logged and mapped to safe domain/validation errors. `[Analysis §9; Refactor P1-3]`

### `Modules\Category\Services\CategoryTypeService`

Responsibilities:

- List active/all CategoryTypes in stable order.
- Load a type by key.
- Create CategoryTypes with safe sort ordering.
- Update title/icon/active state.
- Delete only when no dependent categories exist.
- Keep CategoryType persistence out of Livewire.

Public method contract:

| Method | Purpose | Transaction | Reference |
|---|---|---:|---|
| `listForAdmin(bool $activeOnly = false)` | Ordered type options/tabs | No | `Analysis §6, §16`; `Refactor P1-1` |
| `find(string $type)` | Resolve type for editing | No | `Analysis §6`; `Refactor P1-4` |
| `create(array $data)` | Create with collision-safe sort order | Yes | `Analysis §6, §10`; `Refactor P1-3, P1-5` |
| `update(string $type, array $data)` | Validate and update metadata/status | Yes | `Analysis §6, §13`; `Refactor P1-4` |
| `delete(string $type)` | Fail closed if dependent categories exist | Yes | `Analysis §10, §14`; `Refactor P0-4` |

`type` renaming is not part of the initial contract. **Needs confirmation before coding.**

### Import/export orchestration

If later approved, `Modules\Category\Services\ImportExport` remains the module entry point and must delegate category invariants to the canonical Services. It must not create a parallel persistence rule set. `[Analysis §11; Refactor P2-5]`

## 6. Livewire Design

### `CategoryTable`

File: `Modules/Category/Livewire/Categories/CategoryTable.php`

Traits:

- `WithPagination`.

Injected Services:

- `CategoryService`.
- `CategoryTypeService`.

State:

- `string $search = ''`
- `?string $type = null`
- `string $status = ''`
- `string $sortBy = 'sort_order'`
- `string $sortDirection = 'asc'`
- `int|string $perPage = 10`
- `array $perPageOptions = [10, 25, 50, 100, 'All']`
- `?int $pendingDeleteId = null`
- Optional expanded-node state only if the confirmed tree UX requires it.

Validation:

- `type`: nullable string and must resolve through `CategoryTypeService`.
- `status`: allowed values only: empty/active/inactive.
- `sortBy`: explicit allowlist.
- `sortDirection`: `asc` or `desc`.
- `perPage`: allowlisted and guarded.
- IDs: positive integers, then re-resolved in Service.

Actions:

- `setType(?string $type)`
- `updatedSearch()`, `updatedType()`, `updatedStatus()`, `updatedPerPage()` reset pagination.
- `requestDelete(int $id)`
- `cancelDelete()`
- `confirmDelete()`
- `setActive(int $id, bool $active)`
- `render()`

Every mutation must call authorization before invoking a Service. `[Analysis §6, §12; Refactor P0-1]`

Pagination:

- Default 10.
- Guarded `All` only below a confirmed safe cap; otherwise disabled or rejected.
- Query and pagination execution belong in `CategoryService`.
- Tree pagination semantics are **Needs confirmation before coding**:
  - Recommended baseline: paginate root categories and eager-load only the confirmed child depth.
  - Alternative: flat paginated list with parent/type columns.
  - `[Analysis §15; Refactor P1-7]`

Search/filter/sort:

- Search normalized name and slug.
- Filter by type and active state.
- Sort only by allowlisted columns such as `sort_order`, `name`, `created_at`.
- Hierarchical display must not silently hide unsupported depths. `[Analysis §7, §15; Refactor P1-2, P1-7]`

Events:

- Use a project-standard safe notification event after success/failure.
- Do not expose raw exception messages.
- Type refresh should occur through computed/render state or a specific event from a separate CategoryType component; remove unused `refreshTypes()`. `[Analysis §6, §17; Refactor P2-1]`

### `CategoryForm`

File: `Modules/Category/Livewire/Categories/CategoryForm.php`

Injected Services:

- `CategoryService`.
- `CategoryTypeService`.

State:

- `?int $categoryId = null`
- `string $name = ''`
- `?string $slug = null`
- `?string $type = null`
- `?int $parentId = null`
- `int $sortOrder = 0`
- `bool $isActive = true`
- `newImage` as a Livewire temporary upload
- Existing image display value supplied from the persisted record, never sent to Service as a deletion path
- CategoryType modal state only if the combined modal is retained

Lifecycle:

- `mount(?int $id = null)`.
- Existing record loads through `CategoryService::findForEdit()`.
- Types load through `CategoryTypeService`.
- Changing type clears the selected parent.

Category validation baseline:

- `name`: required, string, bounded maximum.
- `slug`: nullable, string, valid slug pattern, bounded maximum, edit-aware uniqueness under confirmed scope.
- `type`: required or nullable according to confirmed schema; must exist.
- `parentId`: nullable, integer, exists; domain hierarchy validation remains in Service.
- `sortOrder`: integer, minimum 0, confirmed upper bound.
- `isActive`: boolean.
- `newImage`: nullable, actual image, confirmed MIME allowlist, confirmed maximum size.

Image MIME types and maximum size are **Needs confirmation before coding**, but implementation cannot proceed without explicit bounded values. `[Analysis §12, §13; Refactor P0-3]`

Actions:

- `save()` authorizes create/edit, validates, sends a validated array to `CategoryService::create()` or `update()`, and redirects.
- CategoryType actions delegate exclusively to `CategoryTypeService`.
- Destructive CategoryType action uses explicit confirmation state.

Slug behavior:

- Live preview may derive a slug for user feedback.
- `CategoryService` remains authoritative and normalizes again.
- This removes duplicated authoritative slug logic. `[Analysis §16; Refactor P1-4]`

### CategoryType UI component decision

Recommended initial implementation: retain CategoryType management in `CategoryForm` only long enough to preserve behavior while extracting Service logic. Split it into a dedicated class-based Livewire component/page if the modal remains complex. **Needs confirmation before coding for the UI restructuring slice.** `[Analysis §7; Refactor P2-3]`

## 7. Blade/UI Design

### Page Blade files

- `Modules/Category/resources/views/pages/categories/index.blade.php`
- `Modules/Category/resources/views/pages/categories/create.blade.php`
- `Modules/Category/resources/views/pages/categories/edit.blade.php`

Rules:

- Extend `Admin::layouts.master`.
- Define a meaningful page title.
- Mount exactly one primary Livewire feature component.
- Pass only a validated scalar ID to edit.
- No query, Service call, or business logic.

This addresses missing titles and raw ID concerns. `[Analysis §5; Refactor P2-2]`

### Livewire Blade files

- `Modules/Category/resources/views/livewire/categories/category-table.blade.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`

Rules:

- Tailwind CSS 4 only for new/refactored markup.
- No new Bootstrap classes, Bootstrap JS, jQuery, or inline CSS.
- Use permission-aware visibility for controls, while server-side checks remain mandatory.
- Use field-level validation errors.
- Use action-specific loading and disabled states.
- Use explicit confirmation for category and CategoryType deletion.
- Use `wire:model.live` by default where immediate synchronization is intended.

### AdminLTE/Bootstrap layout rules

The repository inventory mentions AdminLTE 4 RC and Bootstrap 5.3, but the higher-priority project standard requires Tailwind CSS 4 and prohibits Bootstrap for new work. Therefore:

- Keep `Admin::layouts.master` as the shell.
- Do not add new AdminLTE/Bootstrap widgets or classes.
- Isolate any unavoidable compatibility supplied by the existing layout.
- Do not rewrite the global Admin layout as part of Category.

This is a precedence decision from `docs/CODEX_BOOTSTRAP.md` and `docs/AI_PROJECT_CONTEXT.md`, consistent with `Refactor P2-3`.

### Shared components

Required/candidate shared components:

- Canonical image upload component under Shared/global presentation ownership.
- `x-select-search` for parent selection when the category list is long.
- Shared confirmation modal only if one already exists and supports required authorization/intent behavior.
- Shared import/export panel only after import/export approval.

The generic image upload must move out of `Modules/Admin` only after registration and caller inventory are verified. `[Analysis §8, §18; Refactor P1-9]`

### Table design

The Category table must include:

- Search input.
- CategoryType filter/tabs.
- Active-state filter.
- Per-page selector.
- Columns: name/image, slug, type, parent/depth indicator, sort order, status, actions.
- Responsive horizontal overflow.
- Empty state and loading state.
- Permission-aware create/edit/delete/status controls.
- Local/shared fallback image and meaningful `alt`.
- Pagination links when not using guarded `All`.

Tree rendering:

- Must render all supported levels consistently or use a flat list.
- Must not eager-load levels that are not rendered.
- Final tree presentation is tied to the hierarchy-depth confirmation. `[Analysis §7, §15; Refactor P1-2, P1-7]`

### Form design

The Category form must include:

- Name.
- Slug.
- CategoryType.
- Parent selector.
- Sort order.
- Active state.
- Image upload.
- Approved optional fields from the canonical schema contract.
- Field-level errors for every editable field.
- Save/cancel actions with loading/disabled states.

The form must not pass the old image path back as deletion authority. `[Analysis §6, §12; Refactor P0-2]`

## 8. Import Design

### Current decision

Import is not part of the core rebuild. **Needs confirmation before coding.** `[Analysis §11; Refactor P2-5]`

Required inputs before implementation:

- Representative real/sample workbook.
- Confirmed sheet names and header row.
- Confirmed header-based or positional mapping.
- Confirmed Category unique key.
- Confirmed CategoryType creation policy.
- Confirmed parent reference strategy.
- Confirmed duplicate mode.
- Confirmed blank/null overwrite behavior.
- Confirmed dry-run behavior.
- Confirmed all-or-nothing or partial-row transaction strategy.

### Target classes if approved

- `Modules/Category/Services/ImportExport.php`
- Optional `Modules/Category/Import/CategoryImport.php`
- Optional `Modules/Category/Import/RowMapper.php`
- Optional `Modules/Category/Import/RowNormalizer.php`
- Optional `Modules/Category/Import/RowValidator.php`

Do not split classes unless responsibilities justify it.

### Provisional header mapping

No mapping is approved. A future proposal may consider:

- `name`
- `slug`
- `type`
- Parent reference column
- `description`
- `image`
- `is_active`
- `sort_order`
- `meta_title`
- `meta_description`

Every alias and Vietnamese label is **Needs confirmation before coding**.

### Column mapping

No positional mapping is approved. If positional mode is required, explicit Excel column letters must be confirmed. `$columnMapping` takes precedence over header aliases.

### Row normalization

If approved:

- Trim strings.
- Convert confirmed blank fields to `null`.
- Normalize slug in Category domain Service.
- Normalize booleans from confirmed values.
- Normalize sort order to a non-negative integer.
- Resolve type and parent references before persistence.
- Reject formula-derived hierarchy or identifiers.

### Row validation

If approved:

- Required name.
- Confirmed type rule.
- Confirmed slug uniqueness rule.
- Parent exists, same type, non-self, non-descendant.
- Maximum depth.
- Valid boolean and sort order.
- No arbitrary public image/file path import.

### Duplicate handling

Supported modes are `create_only`, `update_or_create`, and `skip_duplicate`. `replace` is prohibited until separately confirmed. The unique key must not default to spreadsheet `id`. **Needs confirmation before coding.**

### Error reporting

Result must include:

- `success`
- `total_rows`
- `success_rows`
- `error_rows`
- `skipped_rows`
- Row errors with sheet, row, column, value, and reason
- Debug metadata with mode, dry-run, sheets, counts, and headers

Errors must be safe for users and internal failures logged without stack traces.

## 9. Export Design

### Current decision

Export is not part of the core rebuild. **Needs confirmation before coding.** `[Analysis §11; Refactor P2-5]`

### Target classes if approved

- `Modules/Category/Services/ImportExport.php`
- Optional `Modules/Category/Export/CategoryExport.php`
- Optional `Modules/Category/Export/ExportQuery.php`
- Optional `Modules/Category/Export/ExportMapper.php`
- Optional `Modules/Category/Export/TemplateBuilder.php`

### Query design

If approved:

- Query through the module ImportExport/Category Service boundary.
- Support active filters and selected IDs only if required.
- Select explicit columns.
- Eager-load only required type/parent data.
- Use lazy/chunked iteration for large exports.
- Enforce `view_category` plus any dedicated export permission approved by the project.

### Export mapping

Default to approved Category `$fillable` minus excluded/sensitive/internal fields.

Candidate human-readable columns:

- Name
- Slug
- Type key/title
- Parent business reference
- Description
- Active state
- Sort order
- SEO fields

Image storage paths, internal permission metadata, timestamps, and IDs require explicit approval before export.

### Template generation

If import is approved, the template must include:

- Canonical headers.
- Sample rows.
- Required/optional notes.
- Valid type and boolean values.
- Parent-reference instructions.
- Warning that hierarchy and slugs are validated by the system.

### Large export strategy

- Small bounded exports may run synchronously.
- Large exports must be queued and use bounded iteration.
- Store through the shared export storage foundation.
- Include progress, failure reporting, authorization context, and retention cleanup.
- Queue threshold and retention are **Needs confirmation before coding**.

## 10. Permissions and Authorization

### Required permissions

Existing permissions:

- `view_category`
- `create_category`
- `edit_category`
- `delete_category`

CategoryType mutations currently share the same UI and should map to these permissions initially:

- View types: `view_category`
- Create type: `create_category`
- Update type: `edit_category`
- Delete type: `delete_category`

Dedicated CategoryType permissions are **Needs confirmation before coding** if finer separation is required. `[Analysis §1, §12; Refactor P0-1]`

Future import/export permissions are **Needs confirmation before coding**. Do not assume view permission is sufficient for bulk data operations.

### Route middleware

Web routes:

- `web`
- `auth:admin`
- Named permission middleware appropriate to each route.
- Numeric constraint for edit ID.

Current route names/URLs should remain for the first security/correctness slices to avoid compatibility breakage. Plural renaming is deferred. `[Analysis §3; Refactor P2-2]`

API:

- Public/authenticated/removed status is **Needs confirmation before coding**.
- No active route may point to a missing method. `[Analysis §3, §4; Refactor P0-5]`

### Policy/Gate checks

- Use the project's established Gate/permission convention.
- Route checks protect page access.
- Livewire checks protect direct action invocation.
- Service methods re-resolve records and enforce domain invariants but do not depend on hidden UI state.
- Record ownership is not currently a Category concept; if tenant/domain ownership exists, it is **Needs confirmation before coding**.

### Livewire action protection

| Action | Permission |
|---|---|
| Render/list/load types | `view_category` |
| Open create/save new category | `create_category` |
| Load/update category | `edit_category` |
| Set active state | `edit_category` |
| Delete category | `delete_category` |
| Create CategoryType | `create_category` |
| Update CategoryType | `edit_category` |
| Delete CategoryType | `delete_category` |

UI visibility is supplemental and never replaces server checks. `[Analysis §7, §12; Refactor P0-1]`

## 11. Transactions and Data Integrity

### Actions requiring database transactions

- Create category when image state and hierarchy are involved.
- Update category.
- Delete category.
- Set category active state if related invalidation/audit writes are added.
- Create CategoryType with calculated sort order.
- Update CategoryType.
- Delete CategoryType.
- Future import persistence.

`CategoryService` and `CategoryTypeService` own these transactions. `[Analysis §14; Refactor P1-3]`

### Image transaction pattern

Filesystem operations cannot be rolled back by the database. Required sequence:

1. Validate and authorize.
2. Re-resolve current Category and current image path.
3. Store the new image under the Category namespace.
4. Execute database update in a transaction.
5. After successful commit, delete the previous image.
6. On database/storage failure, delete the newly stored file and preserve the old record/file.

No client-provided old path may be trusted. `[Analysis §9, §12, §14; Refactor P0-2, P1-3]`

### Rollback conditions

- Database exception.
- Constraint violation.
- Invalid type or parent.
- Hierarchy cycle/depth violation.
- Slug conflict.
- Unauthorized record/action.
- Storage failure.
- Dependent CategoryType deletion.
- Future import row/transaction failure according to approved mode.

### Category deletion

Child handling is **Needs confirmation before coding**:

- Reject deletion when children exist.
- Reparent children.
- Recursively delete subtree.

No implicit `nullOnDelete()` promotion or recursive deletion may be retained by assumption. `[Analysis §6, §14; Refactor P1-2]`

### CategoryType deletion

- Must fail if any Category references the type.
- Database constraint and Service check must agree.
- Explicit confirmation is required.
- No cascade delete. `[Analysis §10, §14; Refactor P0-4]`

### Idempotency concerns

- `setActive(id, active)` should assign an explicit target state, not toggle blindly, making retries idempotent.
- Repeated delete requests should return a safe not-found/already-deleted outcome without deleting unrelated files.
- Repeated save submissions must be blocked in UI; true create-request idempotency keys are not required unless duplicate submissions remain a production risk. **Needs confirmation before coding.**
- Future queued import/export must define retry/idempotency semantics.

## 12. Performance Strategy

### Eager loading

- Load only relationships rendered by the current list mode.
- Do not eager-load grandchildren when only direct children are displayed.
- For flat lists, eager-load `typeInfo` and `parent` with selected columns.
- For bounded tree lists, eager-load only the confirmed maximum display depth.

This addresses hidden levels and unused eager loading. `[Analysis §7, §15; Refactor P1-7]`

### Query optimization

- All list/filter/sort queries belong in `CategoryService`.
- Select explicit columns for tables and parent options.
- Allowlist sortable columns.
- Use `(type, parent_id, sort_order)` only if query-plan evidence supports it.
- Build flattened parent options by grouping records by `parent_id`, not rescanning the full collection recursively.
- Track visited IDs to stop corrupt cycles.

### Pagination

- Server-side pagination with 10, 25, 50, 100, and guarded `All`.
- `All` must be capped/disabled beyond a safe threshold.
- Reset page on search/filter/per-page changes.
- Root-tree versus flat pagination is **Needs confirmation before coding**. `[Analysis §15; Refactor P1-7]`

### Search/filter/sort

- Search: `name`, `slug`.
- Filters: `type`, `is_active`.
- Sort: `sort_order`, `name`, `created_at`; exact allowlist must match UI.
- Parent selector should use `x-select-search` when option count is large.

### Caching

No cache is required in the first rebuild.

Caching CategoryTypes or public category trees may be considered only after:

- Canonical ownership is complete.
- Query count is measured.
- Invalidation occurs from Category/CategoryType Service writes.
- Acceptable stale behavior is documented.

This avoids using cache to conceal unbounded queries. `[Analysis §15; Refactor P1-7 and Risk Control]`

## 13. Test Strategy

### Route tests

- Admin authentication required.
- Named permission required for index/create/edit.
- Numeric edit constraint.
- Authorized pages return expected Page Blades.
- API route absent or secured according to confirmed decision.
- Module routes and aliases boot successfully.

References: `Analysis §3, §4`; `Refactor P0-1, P0-5, P1-10`.

### Livewire tests

`CategoryTable`:

- List/filter/search/sort/pagination.
- Reset pagination on filter changes.
- Reject invalid type/sort/per-page state.
- Authorized/denied status changes.
- Delete confirmation flow.
- Authorized/denied delete.
- Child/root behavior under confirmed policy.

`CategoryForm`:

- Typed mount and record load through Service.
- Create/update validation.
- Slug uniqueness.
- Parent/type/hierarchy errors.
- Valid/invalid/oversized/fake image upload.
- Tampered `categoryId`, parent, type, and old-image state.
- CategoryType create/update/delete validation and permissions.
- Loading/disabled outcomes where testable.

References: `Analysis §6, §7, §12, §13`; `Refactor P0-1, P0-2, P0-3, P1-4, P1-10`.

### Service tests

`CategoryService`:

- List query filters, sorting, pagination, safe `All`.
- Create/update normalization.
- Parent exists and same type.
- Self-parent and descendant-parent rejection.
- Maximum-depth behavior after confirmation.
- Slug scope after confirmation.
- Image replacement success and compensation failure paths.
- Delete child policy after confirmation.
- Explicit idempotent active-state assignment.
- Safe domain exceptions.

`CategoryTypeService`:

- Ordered active/all listing.
- Collision-safe sort order.
- Cast/persistence behavior.
- Update validation.
- Dependent deletion rejection.
- Concurrent deletion protection where feasible.

References: `Analysis §9, §14, §15`; `Refactor P1-1, P1-2, P1-3, P1-7, P1-10`.

### Model tests

- Explicit tables.
- Fillable contract.
- Boolean/integer casts.
- `CategoryType` string primary key.
- Parent/children/type relationships.
- Approved simple scopes.
- Confirm no business traversal is required from Models.

References: `Analysis §10`; `Refactor P1-5`.

### Migration tests

- Fresh MySQL migration order.
- Upgrade corrective migration with representative existing data.
- CategoryType restrictive delete.
- Approved slug uniqueness.
- Approved `type` nullability.
- Approved parent delete behavior.
- Index existence.
- Rollback safety.

References: `Analysis §10, §14`; `Refactor P0-4, P1-6`.

### Import tests

Not required for the core rebuild.

If import is approved:

- Header aliases and positional mapping.
- Normalization.
- Unique-key and duplicate modes.
- Type/parent resolution.
- Cycle/depth rejection.
- Dry run.
- Null overwrite rules.
- All-or-nothing/partial behavior.
- Row-level reports.
- Large-file bounds.

**Needs confirmation before coding.** `[Analysis §11; Refactor P2-5]`

### Export tests

Not required for the core rebuild.

If export is approved:

- Permission checks.
- Filters and selected IDs.
- Explicit column mapping.
- Sensitive/internal field exclusion.
- Template content.
- Chunked/queued large export.
- Storage and retention.

**Needs confirmation before coding.** `[Analysis §11; Refactor P2-5]`

### Authorization tests

- Each permission allows only its intended action.
- Direct Livewire invocation is denied without permission.
- Hidden controls do not grant authorization.
- Tampered IDs and file paths fail closed.
- CategoryType destructive actions require confirmation and permission.

References: `Analysis §12`; `Refactor P0-1, P0-2`.

### Cross-module compatibility tests

Before deleting duplicates:

- Product category relationships/workflows.
- Post category relationships/workflows.
- Website category display/filter services.
- Admin category navigation/screens.
- Architecture test preventing new duplicate Category Models.

References: `Analysis §16, §18`; `Refactor P1-8`.

## 14. Implementation Checklist

### P0

- [ ] Add initial Category test discovery and security regression fixtures. `[Refactor P1-10 prerequisite]`
- [ ] Enforce `auth:admin` plus named permissions on Category routes. `[Analysis §3, §12; Refactor P0-1]`
- [ ] Authorize every mutating Livewire action. `[Analysis §6, §12; Refactor P0-1]`
- [ ] Hide unauthorized controls without treating UI checks as sufficient. `[Analysis §7, §12; Refactor P0-1]`
- [ ] Remove caller-provided old image paths from Service input. `[Analysis §6, §12; Refactor P0-2]`
- [ ] Restrict image deletion to persisted Category-owned paths. `[Analysis §9, §12; Refactor P0-2]`
- [ ] Confirm image MIME allowlist and maximum size before implementation. **Needs confirmation before coding.** `[Analysis §13; Refactor P0-3]`
- [ ] Add strict upload validation and rejection tests. `[Analysis §12, §13; Refactor P0-3]`
- [ ] Confirm CategoryType deletion must fail while categories exist. **Needs confirmation before coding.** `[Analysis §10, §14; Refactor P0-4]`
- [ ] Add a forward corrective migration replacing cascade delete with restriction. `[Analysis §10, §14; Refactor P0-4]`
- [ ] Confirm whether Category API is removed, public, or authenticated. **Needs confirmation before coding.** `[Analysis §3, §4; Refactor P0-5]`
- [ ] Remove or implement the API according to the approved contract. `[Refactor P0-5]`

### P1

- [ ] Establish `CategoryService` as owner of all Category queries/writes. `[Analysis §2, §6, §9; Refactor P1-1]`
- [ ] Create `CategoryTypeService` and remove CategoryType Model calls from Livewire. `[Analysis §6; Refactor P1-1]`
- [ ] Type Livewire mount/IDs and re-resolve records in Services. `[Analysis §4, §6; Refactor P1-4]`
- [ ] Complete category and CategoryType validation rules and field errors. `[Analysis §7, §13; Refactor P1-4]`
- [ ] Confirm maximum hierarchy depth. **Needs confirmation before coding.** `[Analysis §7, §15; Refactor P1-2]`
- [ ] Confirm category child-handling on delete. **Needs confirmation before coding.** `[Analysis §6, §14; Refactor P1-2]`
- [ ] Enforce same-type, non-self, non-descendant hierarchy rules. `[Analysis §9, §13; Refactor P1-2]`
- [ ] Move recursive business traversal out of `Category` Model. `[Analysis §10; Refactor P1-2, P1-5]`
- [ ] Implement database transactions and filesystem compensation. `[Analysis §14; Refactor P1-3]`
- [ ] Use explicit idempotent active-state assignment. `[Refactor P1-3]`
- [ ] Confirm canonical Category columns including `url`, `icon`, `can`, `type_title`, `description`. **Needs confirmation before coding.** `[Analysis §10, §16; Refactor P1-5]`
- [ ] Align canonical Model fillable/casts/relationships/table names. `[Analysis §10; Refactor P1-5]`
- [ ] Confirm slug uniqueness scope. **Needs confirmation before coding.** `[Analysis §10, §13; Refactor P1-6]`
- [ ] Confirm whether `categories.type` is mandatory. **Needs confirmation before coding.** `[Analysis §10; Refactor P1-6]`
- [ ] Add forward-only migration corrections and MySQL smoke tests. `[Analysis §10; Refactor P1-6]`
- [ ] Implement bounded list/parent queries and linear tree construction. `[Analysis §15; Refactor P1-7]`
- [ ] Confirm root-tree or flat pagination semantics. **Needs confirmation before coding.** `[Analysis §15; Refactor P1-7]`
- [ ] Move generic image upload component to canonical Shared ownership after caller inventory. `[Analysis §8, §18; Refactor P1-9]`
- [ ] Verify/add module manifest/provider according to project boot strategy. **Needs confirmation before coding.** `[Analysis §1, §18]`
- [ ] Migrate Admin/Product/Post/Website callers one module at a time. `[Analysis §16, §18; Refactor P1-8]`
- [ ] Remove no duplicate until references and regression tests prove it unused. `[Refactor P1-8]`
- [ ] Add route, Livewire, Service, Model, migration, transaction, hierarchy, upload, query-count, and authorization tests. `[Analysis §19; Refactor P1-10]`

### P2

- [ ] Add page titles and typed controller return values. `[Analysis §4, §5; Refactor P2-2]`
- [ ] Keep route names/URLs stable unless a compatibility plan approves plural renaming. `[Analysis §3; Refactor P2-2]`
- [ ] Add delete confirmations and action-specific loading/disabled states. `[Analysis §7; Refactor P2-3]`
- [ ] Standardize intended bindings on `wire:model.live`. `[Analysis §7; Refactor P2-3]`
- [ ] Replace external placeholder image with a local/shared asset. `[Analysis §7; Refactor P2-4]`
- [ ] Add meaningful image alt text. `[Analysis §7; Refactor P2-4]`
- [ ] Add explicit Model table declarations and schema comments through safe migrations. `[Analysis §10; Refactor P2-4]`
- [ ] Verify and remove scaffold views, placeholder component, unused method, imports, and comments. `[Analysis §17; Refactor P2-1]`
- [ ] Do not add caching until measured need and invalidation rules exist. `[Analysis §15; Refactor P1-7]`
- [ ] Confirm whether import/export is required. **Needs confirmation before coding.** `[Analysis §11; Refactor P2-5]`
- [ ] If required, obtain workbook and approve mapping/unique key/modes/transactions before implementation. `[Refactor P2-5]`
