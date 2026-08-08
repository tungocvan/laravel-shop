# Module Category Analysis

Generated: 2026-06-15

Scope: static analysis of `Modules/Category` only, following:

`Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared Components -> Service -> Import/Export -> Model -> Migration`

No application code was changed.

## 1. Module Purpose

`Modules/Category` is intended to be the canonical owner of the shared category taxonomy used by products, posts, menus, and other configurable object types.

Current responsibilities:

- Manage category types from `category_types`.
- Manage hierarchical categories from `categories`.
- Create and edit category name, slug, type, parent, image, sort order, and active state.
- Display category trees grouped by active category type.
- Prevent some invalid parent selections.

Module declaration:

- `Modules/Category/config/module.php`
  - Type: `support`
  - Enabled: `true`
  - Declared permissions:
    - `view_category`
    - `create_category`
    - `edit_category`
    - `delete_category`

The module has no `module.json`, provider, seeder, job, test, import class, or export class in its current directory. Whether the project intentionally boots this module through custom discovery rather than a normal `nwidart/laravel-modules` manifest is **Needs verification**.

## 2. Current Architecture Flow

### Category list

1. `Modules/Category/routes/web.php`
2. `Modules/Category/Http/Controllers/CategoryController.php::index()`
3. `Modules/Category/resources/views/pages/categories/index.blade.php`
4. `Modules/Category/Livewire/Categories/CategoryTable.php`
5. `Modules/Category/resources/views/livewire/categories/category-table.blade.php`
6. Direct `Category` and `CategoryType` Model queries
7. `categories` and `category_types` tables

This flow bypasses `Modules/Category/Services/CategoryService.php` entirely.

### Category create/edit

1. `Modules/Category/routes/web.php`
2. `Modules/Category/Http/Controllers/CategoryController.php::create()` or `edit()`
3. `Modules/Category/resources/views/pages/categories/create.blade.php` or `edit.blade.php`
4. `Modules/Category/Livewire/Categories/CategoryForm.php`
5. `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
6. `Modules/Category/Services/CategoryService.php::save()` for the final category save only
7. `Modules/Category/Models/Category.php`
8. `categories` table

The form still queries `Category` and `CategoryType` directly. Category-type create, update, and delete operations bypass the Service.

### API

1. `Modules/Category/routes/api.php`
2. `Modules/Category/Http/Controllers/Api/CategoryController.php::index()`

The route targets an `index()` method that does not exist, so the API flow stops at the controller.

**Recommendation (P1):** Move all Category and CategoryType reads/writes into module Services so both Livewire classes follow the required architecture flow.

## 3. Route List

### Web routes

File: `Modules/Category/routes/web.php`

Common middleware:

- `web`
- `auth:admin`

Common prefix: `/admin/category`

Common name prefix: `admin.category.`

| Method | URI | Name | Controller | Result |
|---|---|---|---|---|
| GET | `/admin/category` | `admin.category.index` | `CategoryController@index` | Category list |
| GET | `/admin/category/create` | `admin.category.create` | `CategoryController@create` | Category create form |
| GET | `/admin/category/{id}/edit` | `admin.category.edit` | `CategoryController@edit` | Category edit form |

Issues:

- **P0:** `Modules/Category/routes/web.php` enforces authentication only; none of the four permissions declared in `Modules/Category/config/module.php` are applied.
- **P1:** `Modules/Category/routes/web.php` does not constrain `{id}` to a numeric value and does not use model binding.
- **P2:** The singular URL/name prefix `category` is inconsistent with the plural resource represented by the module. This is not a functional defect but should be standardized during refactoring.

**Recommendation (P0):** Enforce `view_category`, `create_category`, `edit_category`, and `delete_category` at the route and Livewire action boundaries.

**Recommendation (P1):** Add a numeric route constraint or authorized model binding for the edit route.

### API route

File: `Modules/Category/routes/api.php`

| Method | Effective URI | Controller | Middleware | Status |
|---|---|---|---|---|
| GET | `/api/category` under normal module API registration | `Modules\Category\Http\Controllers\Api\CategoryController@index` | API stack only | Broken: `index()` is missing |

Issues:

- **P1:** `Modules/Category/routes/api.php` calls a missing controller method.
- **P0 / Needs verification:** The active API route has no `auth:sanctum` or explicit authorization. Confirm whether category data is intentionally public before adding or removing access controls.

**Recommendation (P1):** Remove the unused API route or implement it through an authorized, Service-backed controller contract.

## 4. Controllers

### Web controller

File: `Modules/Category/Http/Controllers/CategoryController.php`

Public methods:

- `index()` returns `Category::pages.categories.index`.
- `create()` returns `Category::pages.categories.create`.
- `edit($id)` returns `Category::pages.categories.edit` with the raw ID.

The controller is thin and does not query Models, which matches the target architecture.

Issues:

- **P1:** `edit($id)` has no scalar type, no route constraint, and no record-level authorization.
- **P2:** Methods do not declare `View` return types.

**Recommendation (P1):** Pass only a validated integer ID after route-level authorization.

### API controller

File: `Modules/Category/Http/Controllers/Api/CategoryController.php`

- Contains no endpoint methods.
- Imports `Illuminate\Http\Request` without using it.
- Cannot satisfy the active API route.

**Recommendation (P1):** Remove the dead endpoint or implement `index()` through `CategoryService`; do not query the Model in the controller.

## 5. Page Blade Files

### Active page shells

- `Modules/Category/resources/views/pages/categories/index.blade.php`
  - Extends `Admin::layouts.master`.
  - Mounts `category.categories.category-table`.

- `Modules/Category/resources/views/pages/categories/create.blade.php`
  - Extends `Admin::layouts.master`.
  - Mounts `category.categories.category-form`.

- `Modules/Category/resources/views/pages/categories/edit.blade.php`
  - Extends `Admin::layouts.master`.
  - Mounts `category.categories.category-form` with `$id`.

These files correctly act as thin page shells.

Issues:

- **P2:** Create and edit pages do not declare page titles.
- **P1:** The edit page passes an unverified raw ID into Livewire.

### Scaffold pages

- `Modules/Category/resources/views/category.blade.php`
- `Modules/Category/resources/views/pages/index.blade.php`

No route or repository reference to these two views was found. Both render the local placeholder component.

**Recommendation (P2):** Remove these scaffold pages after route/view-reference verification.

## 6. Livewire PHP Classes

### Category table

File: `Modules/Category/Livewire/Categories/CategoryTable.php`

Public state:

- `$type`
- `$types`

Public methods:

- `mount()`
- `refreshTypes()`
- `setType($type)`
- `delete($id)`
- `toggleStatus($id)`
- `render()`

Behavior:

- Loads active category types directly through `CategoryType`.
- Loads root categories and two nested `children` relations directly through `Category`.
- Deletes public-disk images and category records directly.
- Toggles status directly on the Model.

Issues:

- **P0:** `delete()` and `toggleStatus()` have no permission or policy checks.
- **P1:** Every query and write bypasses `CategoryService`.
- **P1:** `setType()` trusts arbitrary client state. It does not verify that the type exists or is active.
- **P1:** `delete()` silently relies on the migration's `nullOnDelete()` behavior, promoting children to roots. Intended child behavior is **Needs verification**.
- **P1:** `delete()` deletes the image before the database row. A failed row deletion can leave the record without its file.
- **P1:** The list loads every matching category with `get()` and has no pagination or bounded tree strategy.
- **P2:** `refreshTypes()` has no listener or view caller and appears unused.
- **P2:** Imports of `Category`, `CategoryType`, and `Storage` are symptoms of Service bypass rather than UI-layer dependencies.

**Recommendation (P0):** Authorize every mutating method server-side.

**Recommendation (P1):** Replace direct Model/storage operations with Service methods that define child handling, file cleanup, and failure behavior.

### Category form

File: `Modules/Category/Livewire/Categories/CategoryForm.php`

Injected service:

- `Modules\Category\Services\CategoryService`

Public methods:

- `boot(CategoryService $service)`
- `mount($id = null)`
- `getTypesProperty()`
- `getParentsProperty()`
- `openTypeModal()`
- `updatedSelectedType($value)`
- `createType()`
- `updateType()`
- `deleteType()`
- `updatedName()`
- `updatedType()`
- `save()`
- `render()`

Behavior:

- Uses the Service only for category save and tree flattening.
- Loads the edited category, category types, and parent candidates directly from Models.
- Creates, updates, and deletes CategoryType records directly.
- Generates a slug in Livewire for new records.

Issues:

- **P0:** All create/update/delete methods lack authorization.
- **P0:** `$oldImage` is public Livewire state and is trusted by `CategoryService::save()` as the path to delete. Client tampering can target another file on the public disk.
- **P1:** `$categoryId` is public client state and is passed to `updateOrCreate()` without ownership/authorization or server-side re-resolution.
- **P1:** Direct Model queries and CategoryType writes violate the Service boundary.
- **P1:** `mount()` is untyped and directly calls `Category::findOrFail()`.
- **P1:** `createType()` calculates `max(sort_order) + 1` without locking and passes `sort_order` to a Model that does not allow mass assignment of that field.
- **P1:** `updateType()` performs no validation.
- **P1:** `deleteType()` implements a check-then-delete sequence without a transaction or lock.
- **P1:** Parent candidates exclude only the current category, not its descendants.
- **P2:** Slug generation is duplicated between `updatedName()` and `CategoryService::save()`.

**Recommendation (P0):** Never accept the old storage path from Livewire; resolve the current image path from the persisted category inside the authorized Service.

**Recommendation (P1):** Add dedicated CategoryType Service methods and keep Livewire limited to validated UI state and Service calls.

## 7. Livewire Blade Views

### Category table view

File: `Modules/Category/resources/views/livewire/categories/category-table.blade.php`

Features:

- Dynamic category-type tabs.
- Root and direct-child rows.
- Root status toggle.
- Edit and delete actions.
- Loading overlay and empty state.

Issues:

- **P0:** Create, edit, toggle, and delete controls have no `@can` or permission visibility checks. UI checks would not replace server authorization but are also absent.
- **P1:** Delete buttons do not require confirmation.
- **P1:** The PHP class eager-loads grandchildren, but this view renders only roots and direct children. Categories deeper than level two are invisible.
- **P1:** Child categories cannot toggle status from this table while roots can.
- **P2:** The fallback image calls `https://placehold.co/100`, creating an unnecessary external dependency and browser request.
- **P2:** Images have no `alt` attributes.

**Recommendation (P0):** Hide unauthorized controls and enforce the same permissions in Livewire.

**Recommendation (P1):** Define the supported tree depth and render it consistently, or explicitly reject deeper nesting.

### Category form view

File: `Modules/Category/resources/views/livewire/categories/category-form.blade.php`

Features:

- Name and slug inputs.
- Type and parent selection.
- Sort order and active status.
- Shared image upload UI.
- Inline modal for CategoryType create/update/delete.

Issues:

- **P1:** Validation errors are rendered for `slug` and `parent_id`, but neither field is validated by `save()`.
- **P1:** No field-level errors are shown for type, sort order, active state, or CategoryType modal fields.
- **P1:** CategoryType delete has no confirmation.
- **P1:** Modal action buttons have no loading/disabled state and can be submitted repeatedly.
- **P1:** Several fields still use `wire:model` instead of the project default `wire:model.live`.
- **P2:** The large CategoryType management modal duplicates responsibility inside the category form rather than presenting a focused category form.

**Recommendation (P1):** Align rendered errors and loading states with complete Livewire validation rules.

## 8. Shared Components Used

### Admin layout

`Admin::layouts.master` is used by:

- `Modules/Category/resources/views/pages/categories/index.blade.php`
- `Modules/Category/resources/views/pages/categories/create.blade.php`
- `Modules/Category/resources/views/pages/categories/edit.blade.php`
- `Modules/Category/resources/views/category.blade.php`

This is an allowed presentation dependency under the project architecture.

### Image upload component

Usage:

- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`

Physical implementation found at:

- `Modules/Admin/resources/views/components/image-upload.blade.php`

Issues:

- **P1 / Needs verification:** Category uses `<x-image-upload>` while the only implementation found is owned by Admin. Confirm how it is globally registered; standard anonymous module components normally require an explicit namespace.
- **P1:** A reusable cross-module upload component is owned by `Modules/Admin`, although Admin should be a presentation shell and shared infrastructure belongs in `Modules/Shared` or the global component layer.
- **P0:** The component does not set an `accept` restriction, and `CategoryForm` does not validate MIME type, image content, or size.

**Recommendation (P1):** Move the generic component to canonical shared ownership and preserve a single implementation.

### Local placeholder component

File:

- `Modules/Category/resources/views/components/placeholder.blade.php`

It is used only by the two scaffold views that appear unreferenced.

## 9. Services and Public Methods

File: `Modules/Category/Services/CategoryService.php`

### `save(array $data, $id = null)`

Responsibilities:

- Attempts to prevent invalid parent assignment.
- Generates a fallback slug.
- Deletes the old image and stores the new image.
- Creates or updates a category.

Issues:

- **P1:** The cycle check is reversed. When category A selects its descendant B as parent, the code loads B and checks whether A is below B; it can allow the cycle. It can also reject selecting a valid ancestor that already contains A.
- **P1:** The Service does not validate that the parent exists, belongs to the same type, or is not inside the current category's subtree.
- **P0:** It trusts `oldImage` from caller data and deletes that path.
- **P1:** It deletes the old image before the new file and database update are safely completed.
- **P1:** If file storage succeeds and the database write fails, the new file can become orphaned.
- **P1:** There is no database transaction or compensating file cleanup.
- **P1:** It accepts an untyped `$id` and generic array values without Service-level invariants.
- **P1:** It throws generic `\Exception` instances that are not converted into field-level validation errors.

**Recommendation (P0):** Resolve the existing record and image path server-side before any file deletion.

**Recommendation (P1):** Correct cycle detection by resolving the current category subtree and validating the selected parent against it.

**Recommendation (P1):** Use a transaction for database state plus explicit storage compensation so failed writes do not lose or orphan files.

### `buildTree($items, $parent = null, $prefix = '')`

Responsibilities:

- Flattens hierarchical categories for the parent dropdown.
- Adds a dynamic `view_name` property to each Model.

Issues:

- **P1:** The recursive implementation scans the full collection at every level, producing approximately O(n²) work.
- **P1:** Existing cycles can cause unbounded recursion.
- **P2:** Mutating Eloquent Models with a presentation-only `view_name` property mixes query/domain data with UI formatting.

**Recommendation (P1):** Build the tree from records grouped by `parent_id`, with cycle detection and a defined maximum depth.

### Missing Service capabilities

No Service methods exist for:

- Listing/paginating category trees.
- Listing active category types.
- Loading a category for edit.
- Loading valid parent options.
- Deleting a category.
- Toggling status.
- Creating, updating, or deleting CategoryType records.

**Recommendation (P1):** Make these operations explicit public Service methods before removing direct Model calls from Livewire.

## 10. Models and Database Tables

### Category model

File: `Modules/Category/Models/Category.php`

Table by convention: `categories`

Fillable fields:

- `name`
- `slug`
- `type`
- `parent_id`
- `image`
- `is_active`
- `sort_order`
- `meta_title`
- `meta_description`

Relationships:

- `typeInfo()`
- `parent()`
- `children()`
- `childrenRecursive()`

Other methods:

- `scopeOfType()`
- `getAllChildrenIds()`

Issues:

- **P1:** Migration columns `url`, `icon`, `can`, `type_title`, and `description` are not fillable in this canonical Model.
- **P1:** The Model has no `$table` declaration; convention works, but explicit canonical ownership would improve clarity.
- **P1:** Recursive model behavior can recurse indefinitely if a cycle is stored.
- **P1:** `getAllChildrenIds()` contains hierarchy traversal business logic in the Model.
- **P2:** `BelongsTo`, `HasMany`, and `Builder` imports are not used as return types; a commented `BelongsToMany` import is stale.

### CategoryType model

File: `Modules/Category/Models/CategoryType.php`

Table by convention: `category_types`

Primary key:

- String `type`
- Non-incrementing

Fillable fields:

- `type`
- `title`
- `icon`
- `is_active`

Relationships and scopes:

- `categories()`
- `scopeActive()`

Issues:

- **P1:** `sort_order` exists in the migration and is written by `CategoryForm::createType()`, but is absent from `$fillable`; the provided value is discarded under normal mass-assignment behavior.
- **P1:** No casts exist for `is_active` and `sort_order`.
- **P2:** No explicit `$table` is declared.

**Recommendation (P1):** Align Model fillable/casts with the confirmed schema and actual write contract.

### Tables

#### `category_types`

Created by:

- `Modules/Category/database/migrations/-0001_11_30_000015_category_types.php`

Columns:

- `type` string primary key
- `title`
- `icon` nullable
- `sort_order` default `0`
- `is_active` default `true`
- timestamps

#### `categories`

Created by:

- `Modules/Category/database/migrations/-0001_11_30_000016_create_categories_table.php`

Important constraints:

- Nullable globally unique `slug`
- Nullable indexed `type`
- `type` foreign key to `category_types.type` with cascade delete
- Nullable self-reference `parent_id` with null-on-delete
- Composite unique index on `type` and `slug`
- Index on `is_active`

Migration issues:

- **P1:** Both migration filenames begin with the malformed negative year `-0001`, matching the roadmap migration-hygiene risk.
- **P0:** Deleting a CategoryType at the database level cascades deletion to all categories of that type. The UI's existence check is not a reliable protection boundary.
- **P1:** `slug` is globally unique and also unique with `type`; the composite index is redundant, and the global index prevents the same slug across different types.
- **P1:** `type` is nullable in the database but required by the form and relationship design.
- **P1:** The schema permits hierarchy cycles because a self-referencing foreign key cannot enforce acyclic trees.
- **P1:** Schema fields and canonical Model fillable fields are inconsistent.
- **P2:** Important columns have no database comments.
- **P2:** Formatting/indentation in the categories migration is malformed and obscures review.

**Recommendation (P0):** Replace destructive CategoryType cascade behavior with a confirmed deletion policy that cannot remove category trees implicitly.

**Recommendation (P1):** Confirm whether slug uniqueness is global or per type, then keep only the matching unique constraint.

**Recommendation (P1):** Rename/rebuild migration ordering safely and verify fresh MySQL installation and rollback behavior.

## 11. Import/Export Classes

No import/export classes, module `Services/ImportExport.php`, or shared import/export panel usage exists under `Modules/Category`.

This is not automatically a defect because import/export is not exposed by the current UI.

**Recommendation (P2 / Needs verification):** If Category requires spreadsheet exchange, define unique keys, hierarchy mapping, type mapping, duplicate mode, null-overwrite behavior, and transaction strategy before implementing the shared v1.5 flow.

## 12. Authorization and Security Risks

1. **P0:** `Modules/Category/routes/web.php` uses only `auth:admin`; declared permissions are not enforced.
2. **P0:** `Modules/Category/Livewire/Categories/CategoryTable.php` exposes delete and status mutation to every authenticated admin.
3. **P0:** `Modules/Category/Livewire/Categories/CategoryForm.php` exposes category and CategoryType creation, update, and deletion without authorization.
4. **P0:** `Modules/Category/Livewire/Categories/CategoryForm.php::$oldImage` is client-controlled state used by `Modules/Category/Services/CategoryService.php` as a delete path.
5. **P0:** `Modules/Category/Livewire/Categories/CategoryForm.php` accepts public uploads without image MIME/content/size validation before `Modules/Category/Services/CategoryService.php` stores them on the public disk.
6. **P0 / Needs verification:** `Modules/Category/routes/api.php` exposes an unauthenticated route if module API routes are active.
7. **P1:** `Modules/Category/resources/views/livewire/categories/category-table.blade.php` and `category-form.blade.php` expose all controls without permission-aware rendering.
8. **P1:** Client-supplied category IDs, type keys, parent IDs, and selected type state are trusted without server-side authorization or full invariant validation.

**Recommendation (P0):** Add permission denial paths for routes and every mutating Livewire action, and cover them with tests.

**Recommendation (P0):** Validate uploads as actual images with an explicit MIME/extension/size policy and never trust browser metadata or caller-provided storage paths.

## 13. Validation Problems

File: `Modules/Category/Livewire/Categories/CategoryForm.php`

- **P1:** `name` lacks `string` and maximum length validation.
- **P1:** `slug` has no string, format, maximum length, or uniqueness validation.
- **P1:** `parent_id` has no nullable/integer/exists/same-type/acyclic validation.
- **P1:** `sort_order` has no integer/minimum/range validation.
- **P1:** `is_active` has no boolean validation.
- **P0:** `newImage` has no image/MIME/size validation.
- **P1:** `updateType()` validates none of `selectedType`, `editTitle`, `editIcon`, or `editActive`.
- **P1:** `deleteType()` does not validate the selected type before querying.
- **P1:** `createType()` lacks maximum lengths and icon validation.
- **P1:** Business invariants are not revalidated in `CategoryService`, so non-Livewire callers can bypass UI rules.

File: `Modules/Category/Services/CategoryService.php`

- **P1:** Parent existence, same-type membership, and acyclic hierarchy are not correctly enforced.
- **P1:** Slug normalization and uniqueness are not guaranteed.
- **P1:** The Service accepts arbitrary array keys/types and relies on database failures.

**Recommendation (P1):** Use complete Livewire rules for UI input and repeat domain invariants in the Service.

## 14. Transaction Risks

1. **P1:** `Modules/Category/Services/CategoryService.php::save()` deletes the old file, stores a new file, and writes the database without transaction/compensation.
2. **P1:** A failed database write can leave an orphaned new image.
3. **P1:** A failed storage or database write after deleting the old image can leave the existing category without its image.
4. **P1:** `Modules/Category/Livewire/Categories/CategoryTable.php::delete()` deletes the image before deleting the row.
5. **P1:** `Modules/Category/Livewire/Categories/CategoryForm.php::deleteType()` checks for categories and then deletes without transaction or locking.
6. **P0:** The `category_types` foreign-key cascade in `Modules/Category/database/migrations/-0001_11_30_000016_create_categories_table.php` can turn a CategoryType deletion race or alternate caller into bulk category deletion.
7. **P1 / Needs verification:** Deleting a category promotes children to roots through `nullOnDelete()`; confirm whether reparenting, rejection, or recursive deletion is the intended business rule.

**Recommendation (P1):** Put database writes in Service transactions and add explicit file rollback/cleanup behavior.

**Recommendation (P0):** Make CategoryType deletion fail closed while dependent categories exist at both Service and database levels.

## 15. N+1 / Query Performance Risks

- No direct per-row N+1 query was observed in `category-table.blade.php`; `CategoryTable::render()` eager-loads children.
- **P1:** `CategoryTable::render()` loads an unbounded category set with `get()`.
- **P1:** It eager-loads grandchildren that the Blade view never renders.
- **P1:** The UI renders only two levels while the model/service permit arbitrary depth, creating hidden records and inconsistent query cost.
- **P1:** `CategoryForm::getParentsProperty()` loads every category of the selected type.
- **P1:** `CategoryService::buildTree()` repeatedly scans the full collection and is approximately O(n²).
- **P1:** `childrenRecursive()` issues queries by tree depth and can become unbounded for deep or cyclic data.
- **P2:** Category types are repeatedly queried by separate list/form paths with no explicit cache policy. Caching is not recommended until invalidation ownership is defined.

**Recommendation (P1):** Define a bounded hierarchy contract, paginate or otherwise bound large category lists, and replace O(n²) tree construction.

## 16. Duplicate Logic

### Inside Category

- Slug generation exists in:
  - `Modules/Category/Livewire/Categories/CategoryForm.php::updatedName()`
  - `Modules/Category/Services/CategoryService.php::save()`

- CategoryType loading exists in:
  - `Modules/Category/Livewire/Categories/CategoryTable.php::mount()`
  - `Modules/Category/Livewire/Categories/CategoryTable.php::refreshTypes()`
  - `Modules/Category/Livewire/Categories/CategoryForm.php::mount()`
  - `Modules/Category/Livewire/Categories/CategoryForm.php::getTypesProperty()`

### Across modules

Multiple Models map the same `categories` table:

- `Modules/Category/Models/Category.php`
- `Modules/Admin/Models/Category.php`
- `Modules/Product/Models/Category.php`
- `Modules/Post/Models/Category.php`
- `Modules/Website/Models/Category.php`

Parallel category UI/domain implementations also exist:

- `Modules/Admin/Livewire/Categories/CategoryTable.php`
- `Modules/Admin/Livewire/Categories/CategoryForm.php`
- `Modules/Admin/Http/Controllers/CategoryController.php`
- `Modules/Website/Services/CategoryService.php`

**Recommendation (P1):** Establish `Modules/Category` as canonical owner, migrate callers incrementally, and delete duplicate implementations only after usage and behavior tests pass.

## 17. Files That Look Unused

The following have no active route or repository reference found:

- `Modules/Category/resources/views/category.blade.php`
- `Modules/Category/resources/views/pages/index.blade.php`
- `Modules/Category/resources/views/components/placeholder.blade.php` except from the two scaffold views above
- `Modules/Category/Livewire/Categories/CategoryTable.php::refreshTypes()`
- `Modules/Category/Http/Controllers/Api/CategoryController.php` as an implementation; the route references it, but it has no method

Unused imports:

- `Illuminate\Http\Request` in `Modules/Category/Http/Controllers/Api/CategoryController.php`
- Relationship/Builder imports and commented imports in `Modules/Category/Models/Category.php`

All deletion candidates are **Needs verification** against runtime route/component discovery and dynamic string references.

**Recommendation (P2):** Remove confirmed scaffold/dead artifacts after route boot and view-reference tests exist.

## 18. Module Boundary Violations

1. **P1:** `Modules/Category/Livewire/Categories/CategoryTable.php` and `CategoryForm.php` bypass the module Service and query/write Models directly.
2. **P1:** Generic image-upload presentation is physically owned by `Modules/Admin/resources/views/components/image-upload.blade.php` but consumed by Category and Product.
3. **P1:** Admin, Product, Post, and Website each define their own Category Model for the same `categories` table:
   - `Modules/Admin/Models/Category.php`
   - `Modules/Product/Models/Category.php`
   - `Modules/Post/Models/Category.php`
   - `Modules/Website/Models/Category.php`
4. **P1:** `Modules/Admin/Livewire/Categories/*` and `Modules/Admin/Http/Controllers/CategoryController.php` duplicate a domain that should be owned by Category.
5. **P1:** `Modules/Website/Services/CategoryService.php` depends on `Modules/Website/Models/Category.php` rather than a canonical Category contract.
6. **P1 / Needs verification:** The absence of a Category module manifest/provider may mean registration is controlled outside the module.

**Recommendation (P1):** Treat Category as the canonical taxonomy module, Admin as presentation only, and Shared as owner of genuinely reusable UI/infrastructure.

## 19. Refactor Summary by Priority

### P0 Critical

1. Enforce declared permissions on `Modules/Category/routes/web.php` and every mutating method in both Livewire classes.
2. Stop trusting `CategoryForm::$oldImage`; resolve deletion paths from the persisted record inside `CategoryService`.
3. Add strict image validation before storing public uploads.
4. Replace CategoryType cascade deletion with a fail-closed policy that cannot implicitly delete category trees.
5. Decide and enforce authentication/authorization for `Modules/Category/routes/api.php`; its intended public/private status is **Needs verification**.

### P1 Important

1. Move all Category and CategoryType reads/writes from Livewire into Services.
2. Correct the reversed cycle check and enforce same-type, existing, non-descendant parents.
3. Add transaction and file-compensation behavior for save/delete operations.
4. Complete validation for slug, parent, sort order, active state, type fields, and Service invariants.
5. Fix the broken API controller/route pair.
6. Define the supported tree depth and make queries, parent selection, and rendering consistent.
7. Bound list/parent queries and replace O(n²) tree construction.
8. Align `CategoryType::$fillable`/casts and `Category::$fillable` with the confirmed schema.
9. Resolve malformed migration names, slug-index policy, nullable type mismatch, and deletion constraints.
10. Consolidate duplicate Category Models/UI/Services across Admin, Product, Post, and Website after caller migration.
11. Move the reusable image-upload component out of Admin ownership.
12. Add route, authorization, Livewire, Service, model, migration, transaction, upload, hierarchy, and query-count tests. No Category tests currently exist.

### P2 Nice to have

1. Remove confirmed scaffold views, placeholder component, unused method, imports, and comments.
2. Add page titles, image alt text, consistent `wire:model.live`, and complete loading/disabled states.
3. Replace the external placeholder image with a local asset or shared fallback.
4. Add explicit Model table declarations and concise schema comments.
5. Consider import/export only after business mapping and hierarchy rules are confirmed.
