# Category Refactor Plan

Generated: 2026-06-15

Source: `docs/modules/Category/ANALYSIS.md`

Scope: planning only. No application code is included or changed by this document.

## 1. Executive Summary

`Modules/Category` should become the canonical owner of the shared category taxonomy, but the current implementation is not ready for broad consolidation. Immediate work must first close authorization, upload, file-path, and destructive foreign-key risks.

The target flow is:

`Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Service -> Model -> Database`

The refactor should proceed in three controlled phases:

1. Secure all entry points and destructive operations.
2. move queries, validation invariants, transactions, and hierarchy behavior into Services; then reconcile Models and migrations.
3. Bound tree queries, migrate cross-module callers, and remove only confirmed dead or duplicate artifacts.

Key decisions that must be confirmed before implementation:

- Whether `Modules/Category/routes/api.php` should be public, authenticated, or removed.
- Whether deleting a category rejects deletion, reparents children, or recursively deletes a subtree.
- Whether category trees have a fixed maximum depth.
- Whether slug uniqueness is global or scoped by category type.
- Whether `categories.type` is mandatory for every category.
- Whether import/export is required.

No migration rewrite, duplicate Model deletion, route rename, API exposure, subtree deletion behavior, or import/export implementation should begin before the relevant decision and regression coverage exist.

## 2. P0 Critical Fixes

### P0-1 Enforce Category Authorization

**Issue**

`Modules/Category/routes/web.php`, `Modules/Category/Livewire/Categories/CategoryTable.php`, and `Modules/Category/Livewire/Categories/CategoryForm.php` allow authenticated admins to access or mutate Category data without enforcing the permissions declared in `Modules/Category/config/module.php`. The Blade views also expose every action.

**Root Cause**

Authentication through `auth:admin` is treated as sufficient authorization, and no route middleware, policy/Gate call, or Livewire action authorization is present.

**Business Impact**

Any authenticated admin can create, edit, activate, deactivate, or delete categories and category types, potentially altering product, post, menu, and website taxonomy.

**Technical Impact**

The module violates deny-by-default security requirements and has no tested denial path.

**Proposed Solution**

- Apply `view_category`, `create_category`, `edit_category`, and `delete_category` to the corresponding routes in `Modules/Category/routes/web.php`.
- Reauthorize inside every mutating method in `Modules/Category/Livewire/Categories/CategoryTable.php` and `Modules/Category/Livewire/Categories/CategoryForm.php`; do not rely only on route access or hidden buttons.
- Add permission-aware rendering to `Modules/Category/resources/views/livewire/categories/category-table.blade.php` and `Modules/Category/resources/views/livewire/categories/category-form.blade.php`.
- Add allowed and denied route/Livewire tests under `Modules/Category/tests/Feature/`.

**Files To Change**

- `Modules/Category/routes/web.php`
- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/resources/views/livewire/categories/category-table.blade.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
- `Modules/Category/config/module.php` only if permission naming must be reconciled with the project permission registrar
- New tests under `Modules/Category/tests/Feature/`

**Risk Level**

Critical

**Complexity**

Medium

**Estimated Effort**

2-3 days, including permission fixtures and denial-path tests.

**Acceptance Criteria**

- Anonymous and non-admin users cannot access Category admin routes.
- Authenticated admins without the named permission receive a denial response.
- Direct Livewire calls to every mutating method are denied without permission.
- Authorized admins retain the intended behavior.
- Create, edit, toggle, category delete, and CategoryType mutations have positive and negative tests.

### P0-2 Remove Client-Controlled File Deletion

**Issue**

`Modules/Category/Livewire/Categories/CategoryForm.php::$oldImage` is public Livewire state, and `Modules/Category/Services/CategoryService.php::save()` trusts it as the public-disk path to delete.

**Root Cause**

The Service accepts persisted state from the browser instead of reloading the authorized Category record and resolving its current image path server-side.

**Business Impact**

A tampered Livewire payload may delete another public file, causing data or content loss.

**Technical Impact**

The storage boundary trusts client state and cannot prove that the deleted path belongs to the category being updated.

**Proposed Solution**

- Treat `oldImage` in `Modules/Category/Livewire/Categories/CategoryForm.php` as display-only state or remove it from the Service input.
- In `Modules/Category/Services/CategoryService.php`, resolve the current Category by the authorized ID and read the existing image path from the database.
- Restrict deletion to the expected Category storage namespace and perform it only after the replacement and database update succeed.
- Add tampered-path tests under `Modules/Category/tests/Feature/` and storage behavior tests under `Modules/Category/tests/Unit/` or `Feature/`.

**Files To Change**

- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/Services/CategoryService.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php` if display state changes
- New tests under `Modules/Category/tests/Feature/`

**Risk Level**

Critical

**Complexity**

Medium

**Estimated Effort**

1-2 days.

**Acceptance Criteria**

- No caller-provided storage path is used for deletion.
- Tampering with Livewire image state cannot delete an unrelated file.
- Updating without a new image preserves the existing file.
- Successful replacement deletes only the previous image belonging to the persisted Category.
- Failed replacement preserves the previous database value and file.

### P0-3 Harden Category Image Uploads

**Issue**

`Modules/Category/Livewire/Categories/CategoryForm.php` accepts `$newImage` without image, MIME, extension, or size validation. `Modules/Admin/resources/views/components/image-upload.blade.php` has no `accept` hint.

**Root Cause**

The upload component provides presentation only, while the owning Livewire component has no strict upload rules.

**Business Impact**

Invalid or malicious files may be stored on the public disk, creating security, storage, and content-integrity risks.

**Technical Impact**

The module does not enforce Laravel upload validation and exposes untrusted files through public storage.

**Proposed Solution**

- Add explicit temporary upload validation in `Modules/Category/Livewire/Categories/CategoryForm.php`, including confirmed image MIME types and a bounded size.
- Revalidate required file invariants before storage in `Modules/Category/Services/CategoryService.php` where appropriate for non-Livewire callers.
- Add an `accept` hint to the shared component only as browser guidance, never as the security control.
- Move shared component ownership as a separate P1 task; do not block upload validation on that migration.
- Add valid, invalid MIME, oversized, and fake-extension tests.

**Files To Change**

- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/Services/CategoryService.php`
- `Modules/Admin/resources/views/components/image-upload.blade.php` temporarily, or its canonical replacement created under `Modules/Shared/resources/views/components/`
- New tests under `Modules/Category/tests/Feature/`

**Risk Level**

Critical

**Complexity**

Medium

**Estimated Effort**

1-2 days.

**Acceptance Criteria**

- Unsupported content, fake image extensions, and oversized files are rejected.
- Validation errors appear beside the upload control.
- Rejected uploads are not persisted to public storage.
- Valid images remain supported on create and update.
- Browser `accept` configuration matches, but does not replace, server validation.

### P0-4 Prevent Cascading Category Tree Deletion

**Issue**

`Modules/Category/database/migrations/-0001_11_30_000016_create_categories_table.php` defines `category_types.type -> categories.type` with cascade delete. Deleting a CategoryType can therefore delete all categories of that type.

**Root Cause**

The database constraint conflicts with the UI's intended fail-closed existence check and permits destructive behavior from alternate callers or races.

**Business Impact**

A single CategoryType deletion can remove complete category trees and break product, post, menu, or website associations.

**Technical Impact**

Application checks cannot guarantee safety because the database itself authorizes cascading deletion.

**Proposed Solution**

- Confirm that CategoryType deletion must be rejected while categories exist.
- Add a forward-only corrective migration under `Modules/Category/database/migrations/` that replaces cascade deletion with a restrictive foreign-key policy; do not edit an already-deployed migration in place.
- Enforce the same invariant transactionally in the CategoryType Service introduced in P1.
- Require explicit confirmation in `Modules/Category/resources/views/livewire/categories/category-form.blade.php`.
- Add database and concurrency-oriented regression tests for dependent CategoryType deletion.

**Files To Change**

- New corrective migration under `Modules/Category/database/migrations/`
- `Modules/Category/Services/CategoryTypeService.php` or the chosen Category Service boundary
- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
- New tests under `Modules/Category/tests/Feature/`

**Risk Level**

Critical

**Complexity**

High

**Estimated Effort**

3-5 days, including production migration and rollback planning.

**Acceptance Criteria**

- The database rejects CategoryType deletion while dependent categories exist.
- The Service returns a safe domain error before attempting destructive deletion.
- No test path deletes dependent categories implicitly.
- The corrective migration succeeds on a representative MySQL schema with existing data.
- Rollback behavior is documented and does not silently delete data.

### P0-5 Resolve API Exposure

**Issue**

`Modules/Category/routes/api.php` registers an API endpoint without explicit authentication, while `Modules/Category/Http/Controllers/Api/CategoryController.php` has no `index()` method.

**Root Cause**

A scaffold route was activated without a confirmed API contract or security decision.

**Business Impact**

The endpoint is currently broken and may become an unintended public taxonomy endpoint if implemented casually.

**Technical Impact**

Route boot/runtime behavior is inconsistent, and authorization expectations are undefined.

**Proposed Solution**

- Confirm whether the API is public, authenticated with `auth:sanctum`, admin-only, or unnecessary.
- If unnecessary, remove the route and empty controller.
- If required, define the response fields, active/type filters, pagination, authorization, and Service method before implementation.
- Add route boot, access, response-shape, and query-bound tests.

**Files To Change**

- `Modules/Category/routes/api.php`
- `Modules/Category/Http/Controllers/Api/CategoryController.php`
- `Modules/Category/Services/CategoryService.php` if an API is retained
- New tests under `Modules/Category/tests/Feature/`

**Risk Level**

High until the intended exposure is confirmed.

**Complexity**

Low if removed; Medium if retained.

**Estimated Effort**

0.5-2 days after the API decision.

**Acceptance Criteria**

- No active Category API route points to a missing method.
- The endpoint is absent if no API is required.
- If retained, access control and response scope match the approved contract.
- API tests prove both allowed and denied/public behavior as applicable.

## 3. P1 Important Refactors

### P1-1 Establish Service-Owned Category Operations

**Issue**

`Modules/Category/Livewire/Categories/CategoryTable.php` and `Modules/Category/Livewire/Categories/CategoryForm.php` query and mutate `Category` and `CategoryType` Models directly. `CategoryService` covers only partial save/tree behavior.

**Root Cause**

The module grew around Livewire actions without a complete Service contract.

**Business Impact**

Business rules differ by entry point and are easy to bypass.

**Technical Impact**

The required architecture flow is broken, actions are difficult to test, and duplicate queries/logic accumulate.

**Proposed Solution**

- Expand `Modules/Category/Services/CategoryService.php` with explicit methods for category listing, loading, valid-parent retrieval, save, delete, and status changes.
- Create `Modules/Category/Services/CategoryTypeService.php` if separating CategoryType responsibilities keeps each Service focused.
- Move all Model and storage calls out of both Livewire classes.
- Keep Livewire responsible for UI state, validation, authorization, confirmations, and Service invocation only.
- Use validated arrays/scalars; do not introduce DTOs.

**Files To Change**

- `Modules/Category/Services/CategoryService.php`
- New `Modules/Category/Services/CategoryTypeService.php` if the separate boundary is selected
- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/Livewire/Categories/CategoryForm.php`
- New tests under `Modules/Category/tests/Unit/` and `Modules/Category/tests/Feature/`

**Risk Level**

High

**Complexity**

High

**Estimated Effort**

4-6 days.

**Acceptance Criteria**

- No Category or CategoryType Model query remains in the Livewire classes.
- No storage write/delete remains in the Livewire classes.
- Service methods accept validated arrays/scalars and return Models, collections, paginators, or arrays.
- Service and Livewire tests cover every migrated operation.

### P1-2 Correct Hierarchy Invariants and Deletion Policy

**Issue**

Cycle detection in `Modules/Category/Services/CategoryService.php` is reversed. Parent selection does not enforce parent existence, same type, or exclusion of all descendants. Category deletion currently promotes children through `nullOnDelete()` without a confirmed business rule.

**Root Cause**

Hierarchy validation is split between a direct Livewire query, recursive Model helper, and incomplete Service logic.

**Business Impact**

Invalid cycles can corrupt navigation and taxonomy trees; deletion may unexpectedly restructure or remove business classifications.

**Technical Impact**

Recursive rendering and traversal may become unbounded, while parent options and stored data can disagree.

**Proposed Solution**

- Confirm maximum hierarchy depth and category deletion behavior.
- Centralize parent validation in `Modules/Category/Services/CategoryService.php`.
- Validate that `parent_id` exists, has the same type, is not the category itself, is not a descendant, and does not exceed the approved depth.
- Generate valid parent choices through the Service using the same invariants.
- Replace hierarchy business logic in `Modules/Category/Models/Category.php::getAllChildrenIds()` with Service-owned traversal.
- Add cycle, cross-type, missing-parent, depth, and deletion-policy tests.

**Files To Change**

- `Modules/Category/Services/CategoryService.php`
- `Modules/Category/Models/Category.php`
- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
- `Modules/Category/database/migrations/-0001_11_30_000016_create_categories_table.php` only as historical evidence; use a new corrective migration if the confirmed deletion policy changes the constraint
- New tests under `Modules/Category/tests/Unit/` and `Modules/Category/tests/Feature/`

**Risk Level**

High

**Complexity**

High

**Estimated Effort**

4-6 days after business confirmation.

**Acceptance Criteria**

- Self-parent, descendant-parent, missing-parent, and cross-type parent assignments are rejected.
- Valid ancestor/root assignments succeed.
- Existing cycles are detected safely without unbounded recursion.
- Category deletion follows the explicitly approved child policy.
- Parent option lists cannot offer invalid choices.

### P1-3 Make Category and CategoryType Writes Transactionally Safe

**Issue**

`Modules/Category/Services/CategoryService.php` performs file and database changes without transaction or compensation. `CategoryTable::delete()` removes files before rows. `CategoryForm::deleteType()` uses a race-prone check-then-delete flow.

**Root Cause**

Write orchestration is distributed across Livewire and Service layers with no atomicity design.

**Business Impact**

Failed updates can lose existing images, create orphan files, or produce partial category/type changes.

**Technical Impact**

Database rollback cannot restore files, and concurrent requests can invalidate pre-delete checks.

**Proposed Solution**

- Move all writes into Services.
- Use database transactions for category/category-type writes.
- Store replacement files first, update the database transactionally, delete old files only after successful commit, and remove newly stored files on failure.
- Lock or rely on restrictive constraints for CategoryType dependency checks.
- Return safe domain errors instead of raw generic exceptions.

**Files To Change**

- `Modules/Category/Services/CategoryService.php`
- `Modules/Category/Services/CategoryTypeService.php` if introduced
- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/Livewire/Categories/CategoryForm.php`
- New tests under `Modules/Category/tests/Unit/` and `Modules/Category/tests/Feature/`

**Risk Level**

High

**Complexity**

High

**Estimated Effort**

3-5 days.

**Acceptance Criteria**

- Database failures do not delete the previous image.
- Failed writes remove newly stored orphan files.
- Category deletion and status changes occur through transactional Service methods.
- Concurrent/dependent CategoryType deletion fails safely.
- User-facing errors do not expose stack traces or raw database exceptions.

### P1-4 Standardize Validation and Livewire State

**Issue**

`Modules/Category/Livewire/Categories/CategoryForm.php` validates only a subset of fields. CategoryType update/delete are largely unvalidated, public IDs are trusted, and the Blade view renders errors that do not correspond to rules.

**Root Cause**

Validation was added per action without a complete form and domain contract.

**Business Impact**

Invalid slugs, parent IDs, sort orders, status values, type metadata, and record IDs can reach persistence or fail unpredictably.

**Technical Impact**

Database exceptions replace user-friendly validation, and non-Livewire callers can bypass invariants.

**Proposed Solution**

- Define action-specific Livewire rules for category save and CategoryType create/update/delete.
- Validate strings, maximum lengths, slug format/uniqueness, nullable IDs, integer ranges, booleans, selected type, and upload constraints.
- Use edit-aware unique validation.
- Type `mount(?int $id = null)` and Service scalar parameters.
- Re-resolve public IDs in authorized Services rather than trusting hydrated client state.
- Mirror all field errors in `category-form.blade.php`.
- Keep hierarchy and other domain invariants in Services as well as UI validation.

**Files To Change**

- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
- `Modules/Category/Services/CategoryService.php`
- `Modules/Category/Services/CategoryTypeService.php` if introduced
- New tests under `Modules/Category/tests/Feature/`

**Risk Level**

Medium

**Complexity**

Medium

**Estimated Effort**

2-4 days.

**Acceptance Criteria**

- Every editable field has a matching validation rule and field-level error.
- Edit uniqueness ignores only the current authorized record.
- Invalid/tampered IDs and type keys are rejected.
- Services reject invalid hierarchy/business states from non-Livewire callers.
- Validation failures do not partially persist data.

### P1-5 Align Models With the Confirmed Schema

**Issue**

`Modules/Category/Models/Category.php` omits migration fields from `$fillable`, contains hierarchy traversal logic, and lacks explicit table/relationship return types. `Modules/Category/Models/CategoryType.php` omits `sort_order` from `$fillable` and lacks casts.

**Root Cause**

Models and migrations evolved independently, while duplicate Models in other modules expose different field sets.

**Business Impact**

Expected values such as CategoryType sort order may be silently discarded, and different callers may persist different category shapes.

**Technical Impact**

Mass-assignment behavior is inconsistent, canonical ownership is unclear, and Models contain logic that belongs in Services.

**Proposed Solution**

- Confirm which `categories` columns are part of the canonical write contract.
- Align `$fillable`, `$casts`, explicit `$table`, primary-key settings, and relationship return types in both canonical Models.
- Move recursive hierarchy business logic from `Category.php` into `CategoryService.php`.
- Remove stale imports and commented relationship code after callers are migrated.

**Files To Change**

- `Modules/Category/Models/Category.php`
- `Modules/Category/Models/CategoryType.php`
- `Modules/Category/Services/CategoryService.php`
- New tests under `Modules/Category/tests/Unit/`

**Risk Level**

Medium

**Complexity**

Medium

**Estimated Effort**

1-3 days after schema-field confirmation.

**Acceptance Criteria**

- Canonical Models match the approved schema contract.
- `sort_order` persists and casts correctly for CategoryType.
- Boolean/integer fields cast consistently.
- Models contain ORM configuration, relationships, and approved scopes only.
- Model tests cover fillable fields, casts, and relationships.

### P1-6 Repair Migration and Constraint Hygiene

**Issue**

Category migrations use malformed negative-year filenames, nullable `type` conflicts with required UI behavior, slug uniqueness is duplicated/global, schema comments are absent, and duplicate Models obscure table ownership.

**Root Cause**

The schema was created without a confirmed canonical contract or production migration strategy.

**Business Impact**

Fresh installations may order migrations unpredictably, and slug/type behavior can reject valid data or permit invalid null taxonomy records.

**Technical Impact**

Changing deployed migrations in place risks production drift; redundant indexes increase schema complexity.

**Proposed Solution**

- Inventory production migration state before changing filenames or constraints.
- Confirm global versus per-type slug uniqueness and whether `type` is mandatory.
- Use forward-only corrective migrations for deployed systems.
- For fresh-install hygiene, define a safe migration replacement/renaming strategy only after migration history is known.
- Add meaningful comments and indexes aligned with actual Service queries.
- Add MySQL migration smoke tests, constraint tests, and rollback tests.

**Files To Change**

- `Modules/Category/database/migrations/-0001_11_30_000015_category_types.php`
- `Modules/Category/database/migrations/-0001_11_30_000016_create_categories_table.php`
- New corrective migrations under `Modules/Category/database/migrations/`
- `Modules/Category/Models/Category.php`
- `Modules/Category/Models/CategoryType.php`
- New tests under `Modules/Category/tests/Feature/`

**Risk Level**

High

**Complexity**

High

**Estimated Effort**

4-7 days, including production-state verification.

**Acceptance Criteria**

- Fresh MySQL migration order is deterministic.
- Existing deployed databases can migrate forward without data loss.
- Slug uniqueness matches the approved business scope.
- `type` nullability matches the approved contract.
- Redundant/unsafe constraints are removed through forward migrations.
- Migration up/down and constraint tests pass.

### P1-7 Bound and Optimize Category Tree Queries

**Issue**

`CategoryTable::render()` and `CategoryForm::getParentsProperty()` load unbounded collections. `CategoryService::buildTree()` is approximately O(n²), and recursive relationships can issue depth-dependent queries or recurse indefinitely.

**Root Cause**

The UI assumes a small taxonomy and has no pagination, maximum depth, or bounded tree-query contract.

**Business Impact**

Category administration can become slow or fail as taxonomy data grows.

**Technical Impact**

Memory use, query count, and recursion depth are uncontrolled; grandchildren are loaded but not rendered.

**Proposed Solution**

- Define the supported hierarchy depth before query redesign.
- Build list and parent-option queries in `CategoryService`.
- Use server-side pagination or an explicitly bounded tree strategy for the admin list.
- Group records by `parent_id` for linear-time flattening and add visited-node cycle detection.
- Load only relationships/columns rendered by the view.
- Add query-count and large-fixture tests.
- Do not introduce caching until write-side invalidation and acceptable staleness are defined.

**Files To Change**

- `Modules/Category/Services/CategoryService.php`
- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/resources/views/livewire/categories/category-table.blade.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
- New tests under `Modules/Category/tests/Feature/` and `Modules/Category/tests/Unit/`

**Risk Level**

Medium

**Complexity**

High

**Estimated Effort**

3-5 days.

**Acceptance Criteria**

- List queries are bounded for production-sized data.
- Parent-option construction is linear or otherwise demonstrably bounded.
- No unused nested relation is eager-loaded.
- Cyclic/corrupt data cannot cause infinite recursion.
- Query-count and memory-oriented tests establish an explicit budget.

### P1-8 Consolidate Canonical Category Ownership

**Issue**

The same `categories` table is represented by Models and behavior in Category, Admin, Product, Post, and Website modules.

**Root Cause**

Each module implemented local Category access instead of depending on one canonical domain owner.

**Business Impact**

Field definitions, relationships, caches, and business rules can diverge between product, post, website, and admin workflows.

**Technical Impact**

Duplicate Models and Services prevent reliable schema evolution and create module-boundary violations.

**Proposed Solution**

- Declare `Modules/Category` as canonical owner.
- Inventory callers of:
  - `Modules/Admin/Models/Category.php`
  - `Modules/Product/Models/Category.php`
  - `Modules/Post/Models/Category.php`
  - `Modules/Website/Models/Category.php`
  - `Modules/Website/Services/CategoryService.php`
  - `Modules/Admin/Livewire/Categories/CategoryTable.php`
  - `Modules/Admin/Livewire/Categories/CategoryForm.php`
  - `Modules/Admin/Http/Controllers/CategoryController.php`
- Add canonical Category Service methods/relationships before migrating callers.
- Migrate one module at a time with compatibility tests.
- Remove duplicates only after repository/runtime references and tests prove they are unused.
- Add architecture tests preventing new duplicate Category Models.

**Files To Change**

- `Modules/Category/Models/Category.php`
- `Modules/Category/Services/CategoryService.php`
- `Modules/Admin/Models/Category.php`
- `Modules/Product/Models/Category.php`
- `Modules/Post/Models/Category.php`
- `Modules/Website/Models/Category.php`
- `Modules/Website/Services/CategoryService.php`
- `Modules/Admin/Livewire/Categories/CategoryTable.php`
- `Modules/Admin/Livewire/Categories/CategoryForm.php`
- `Modules/Admin/Http/Controllers/CategoryController.php`
- All verified callers discovered during implementation
- New architecture and compatibility tests

**Risk Level**

High

**Complexity**

Critical

**Estimated Effort**

1-2 weeks, delivered incrementally after P0 and canonical Service coverage.

**Acceptance Criteria**

- `Modules/Category` is the only canonical owner of Category persistence.
- All migrated callers preserve approved behavior.
- No duplicate file is deleted while a caller remains.
- Architecture tests reject new non-canonical Category Models.
- Product, Post, Website, and Admin regression tests pass after each migration slice.

### P1-9 Move Shared Image Upload UI Out of Admin

**Issue**

`Modules/Category/resources/views/livewire/categories/category-form.blade.php` uses `<x-image-upload>`, while the only located implementation is `Modules/Admin/resources/views/components/image-upload.blade.php`.

**Root Cause**

A generic reusable component is owned by the Admin presentation module rather than Shared/global UI infrastructure, and its registration mechanism is unclear.

**Business Impact**

Category and Product forms can break if Admin internals change or component discovery differs by environment.

**Technical Impact**

The dependency direction is incorrect, component ownership is ambiguous, and duplicate component implementations are likely.

**Proposed Solution**

- Verify current component registration and all callers.
- Move the generic component to a canonical shared Blade component path, preferably under `Modules/Shared/resources/views/components/` if supported by existing module registration.
- Update Category, Product, Website, and Admin callers to the canonical alias.
- Preserve upload behavior while security validation remains in each owning Livewire component.
- Remove the Admin-owned copy only after all callers and rendering tests pass.

**Files To Change**

- `Modules/Admin/resources/views/components/image-upload.blade.php`
- New canonical component under `Modules/Shared/resources/views/components/`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
- `Modules/Product/resources/views/livewire/products/product-form.blade.php`
- `Modules/Website/resources/views/livewire/admin/home/home-settings.blade.php`
- Relevant Admin Blade callers identified in `docs/modules/Category/ANALYSIS.md`
- Shared/Admin service provider or component registration file discovered during implementation
- Component rendering tests

**Risk Level**

Medium

**Complexity**

Medium

**Estimated Effort**

2-4 days.

**Acceptance Criteria**

- One canonical image-upload component serves all verified callers.
- Category no longer depends on an Admin-owned generic component.
- Component registration is explicit and tested.
- Existing previews, removal state, validation errors, and loading state still render.

### P1-10 Establish Category Test Coverage

**Issue**

No tests exist under `Modules/Category`, despite authorization, hierarchy, upload, migration, transaction, and query risks.

**Root Cause**

The module was implemented without a module-level regression harness.

**Business Impact**

Security and taxonomy regressions can reach production undetected.

**Technical Impact**

Broad refactoring and caller migration cannot be performed safely.

**Proposed Solution**

- Create focused test groups for route boot/access, Livewire actions, Services, Models, migrations, storage rollback, hierarchy invariants, query counts, and API behavior.
- Add negative tests first for P0 risks.
- Use representative multi-level and malformed/cyclic fixtures where technically possible.
- Integrate Category tests into the project's CI/test command.

**Files To Change**

- New files under `Modules/Category/tests/Feature/`
- New files under `Modules/Category/tests/Unit/`
- Project test configuration only if module tests are not currently discovered
- CI workflow files only if required to run module tests

**Risk Level**

High

**Complexity**

High

**Estimated Effort**

4-7 days initially, then continuous with each refactor slice.

**Acceptance Criteria**

- P0 denial, path tampering, upload rejection, and destructive-delete tests exist.
- CRUD, validation, hierarchy, transaction rollback, migration, and query-bound tests exist.
- Tests are discovered by the standard project test command.
- Cross-module migration work does not begin until relevant Category regression tests pass.

## 4. P2 Nice To Have Improvements

### P2-1 Remove Confirmed Dead Scaffolding

**Issue**

`Modules/Category/resources/views/category.blade.php`, `Modules/Category/resources/views/pages/index.blade.php`, `Modules/Category/resources/views/components/placeholder.blade.php`, `CategoryTable::refreshTypes()`, empty API artifacts, and stale imports/comments appear unused.

**Root Cause**

Scaffold and legacy artifacts remained after the active category screens were introduced.

**Business Impact**

Low; dead files increase maintenance ambiguity.

**Technical Impact**

Search results and module structure overstate active behavior.

**Proposed Solution**

- Verify route boot, dynamic view references, Livewire listeners, and runtime component discovery.
- Remove only artifacts proven unused after P1 tests exist.
- Clean stale imports and commented code in the files retained.

**Files To Change**

- `Modules/Category/resources/views/category.blade.php`
- `Modules/Category/resources/views/pages/index.blade.php`
- `Modules/Category/resources/views/components/placeholder.blade.php`
- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/Http/Controllers/Api/CategoryController.php`
- `Modules/Category/Models/Category.php`

**Risk Level**

Low

**Complexity**

Low

**Estimated Effort**

0.5-1 day.

**Acceptance Criteria**

- Removed files/methods have no static or runtime references.
- Category routes and Livewire components still boot.
- Tests pass after cleanup.

### P2-2 Normalize Routes, Controller Signatures, and Page Metadata

**Issue**

The route slug is singular, the edit parameter is unconstrained, controller methods lack return types, and create/edit page Blades lack titles.

**Root Cause**

Initial routing and page shells were created with minimal metadata and typing.

**Business Impact**

Low, except route renaming can break bookmarks or callers.

**Technical Impact**

Route conventions and static clarity are inconsistent.

**Proposed Solution**

- Add numeric route constraints and typed scalar/controller return signatures as part of P1 validation work.
- Add titles to create/edit page shells.
- Treat changing `/admin/category` or `admin.category.*` as a compatibility change; inventory callers and add redirects/aliases if the plural convention is adopted.

**Files To Change**

- `Modules/Category/routes/web.php`
- `Modules/Category/Http/Controllers/CategoryController.php`
- `Modules/Category/resources/views/pages/categories/create.blade.php`
- `Modules/Category/resources/views/pages/categories/edit.blade.php`
- All verified route callers if names/URLs change

**Risk Level**

Low for typing/titles; Medium for route renaming.

**Complexity**

Low

**Estimated Effort**

0.5-2 days depending on route compatibility.

**Acceptance Criteria**

- Route parameters are constrained and controller signatures are typed.
- All active page shells define titles.
- No route name/URL changes occur without caller inventory and compatibility coverage.

### P2-3 Improve Livewire UI Consistency and Safety

**Issue**

Category views lack delete confirmation, complete loading/disabled states, consistent `wire:model.live`, child status controls, and a focused separation between Category and CategoryType management.

**Root Cause**

UI behavior was implemented incrementally around one large form/modal.

**Business Impact**

Admins can accidentally repeat or trigger destructive operations and receive inconsistent feedback.

**Technical Impact**

Livewire requests can be duplicated, and UI state does not consistently reflect validation/action progress.

**Proposed Solution**

- Add explicit confirmation state for category and CategoryType deletion.
- Add action-specific `wire:loading`, `wire:target`, and disabled states.
- Use `wire:model.live` by default where live behavior is intended.
- Align root and child status actions with the approved authorization and hierarchy behavior.
- Consider a separate CategoryType screen/component only if the modal remains too complex after Service extraction.

**Files To Change**

- `Modules/Category/Livewire/Categories/CategoryTable.php`
- `Modules/Category/Livewire/Categories/CategoryForm.php`
- `Modules/Category/resources/views/livewire/categories/category-table.blade.php`
- `Modules/Category/resources/views/livewire/categories/category-form.blade.php`
- Page/controller/route files only if CategoryType management becomes a separate screen

**Risk Level**

Low

**Complexity**

Medium

**Estimated Effort**

2-3 days.

**Acceptance Criteria**

- Destructive actions require explicit confirmation.
- Buttons disable during their own requests.
- Field bindings follow Livewire 3 project conventions.
- Root and child actions are consistent with permissions and approved behavior.

### P2-4 Improve Image and Model Presentation Details

**Issue**

`category-table.blade.php` uses an external placeholder and images lack alt text. Models lack explicit `$table`, and migrations lack concise comments.

**Root Cause**

Presentation fallbacks and schema documentation were treated as nonessential.

**Business Impact**

Low; external placeholder availability and accessibility are avoidable dependencies.

**Technical Impact**

The UI performs third-party requests, accessibility is weaker, and canonical schema intent is less explicit.

**Proposed Solution**

- Replace `https://placehold.co/100` with a local/shared fallback asset.
- Add meaningful image alt text.
- Declare explicit table names in canonical Models.
- Add concise table/column comments through safe migration strategy rather than rewriting deployed history.

**Files To Change**

- `Modules/Category/resources/views/livewire/categories/category-table.blade.php`
- Local/shared asset path selected by the project
- `Modules/Category/Models/Category.php`
- `Modules/Category/Models/CategoryType.php`
- New corrective migration under `Modules/Category/database/migrations/` if comments are added to deployed tables

**Risk Level**

Low

**Complexity**

Low

**Estimated Effort**

0.5-1.5 days.

**Acceptance Criteria**

- Category list rendering has no external placeholder dependency.
- Images include useful alt text.
- Canonical Models explicitly declare table ownership.
- Schema comments are added only through a migration compatible with deployed environments.

### P2-5 Defer Import/Export Until Requirements Exist

**Issue**

The module has no import/export support, but the project standard defines a shared architecture if the feature is later required.

**Root Cause**

No confirmed Category spreadsheet contract, unique key, hierarchy mapping, or destructive-mode behavior exists.

**Business Impact**

None unless stakeholders require bulk taxonomy exchange.

**Technical Impact**

Premature implementation would risk hierarchy corruption and duplicate category types.

**Proposed Solution**

- Do not add import/export during the current refactor.
- If requested later, first obtain a representative workbook and confirm header/position mapping, unique key, hierarchy reference strategy, type mapping, duplicate mode, null overwrite behavior, dry-run, partial/all-or-nothing transactions, and export columns.
- Then use `Modules/Category/Services/ImportExport.php` with `Modules/Shared/Services/ImportExport` and `shared.import-export.panel`.

**Files To Change**

- None now.
- Future, only after confirmation:
  - `Modules/Category/Services/ImportExport.php`
  - Optional `Modules/Category/Import/`
  - Optional `Modules/Category/Export/`
  - Relevant Category page Blade
  - Import/export tests

**Risk Level**

Low now; High if implemented without confirmed mapping.

**Complexity**

Needs verification.

**Estimated Effort**

Not estimable until a sample workbook and behavior contract are approved.

**Acceptance Criteria**

- No import/export code is introduced by the core refactor.
- Any future implementation passes the project confirmation gate and shared v1.5 architecture.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. Add Category test discovery and P0 regression fixtures from P1-10.
2. Implement P0-1 authorization for routes, Livewire actions, and controls.
3. Implement P0-2 server-owned image path resolution.
4. Implement P0-3 strict upload validation.
5. Confirm API exposure and complete P0-5.
6. Confirm CategoryType deletion policy, deploy P0-4 restrictive constraint, and test production migration behavior.

Release gate: do not perform broad Service extraction or cross-module consolidation until P0 tests pass.

### Phase 2: Correctness and Maintainability

1. Implement P1-1 canonical Category and CategoryType Service contracts.
2. Implement P1-4 complete Livewire and Service validation.
3. Confirm hierarchy depth/deletion rules and implement P1-2.
4. Implement P1-3 transactional database/file behavior.
5. Align canonical Models through P1-5.
6. Confirm slug/type schema rules and implement P1-6 with forward migrations.
7. Move the shared upload component through P1-9.
8. Expand P1-10 tests continuously after each slice.
9. Begin P1-8 caller migration one module at a time; remove no duplicate yet.

### Phase 3: Performance and Cleanup

1. Implement P1-7 bounded queries, tree construction, and query-count budgets.
2. Complete P1-8 cross-module caller migration and remove duplicates only after verification.
3. Implement P2-3 UI confirmations, loading states, and binding consistency.
4. Implement P2-2 route/page metadata improvements without unapproved route breakage.
5. Implement P2-4 local image fallback and schema/model clarity.
6. Implement P2-1 dead-artifact cleanup.
7. Keep P2-5 import/export deferred until a separate confirmed requirement exists.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/Category/config/module.php` | Verify/modify | P0 | Reconcile declared permissions with enforced permission names |
| `Modules/Category/routes/web.php` | Modify | P0/P2 | Add permission middleware and parameter constraints; defer route renaming unless compatible |
| `Modules/Category/routes/api.php` | Remove or modify | P0/P1 | Resolve broken and potentially unauthenticated API contract |
| `Modules/Category/Http/Controllers/CategoryController.php` | Modify | P1/P2 | Type scalar/return values and preserve thin controller behavior |
| `Modules/Category/Http/Controllers/Api/CategoryController.php` | Remove or implement | P0/P1 | Match the confirmed API decision and Service boundary |
| `Modules/Category/Livewire/Categories/CategoryTable.php` | Major modify | P0/P1/P2 | Authorization, Service calls, bounded list state, confirmations, remove direct Model/storage access |
| `Modules/Category/Livewire/Categories/CategoryForm.php` | Major modify | P0/P1/P2 | Authorization, upload validation, typed state, complete rules, Service-only operations |
| `Modules/Category/resources/views/pages/categories/index.blade.php` | Minor modify | P2 | Page metadata only if required |
| `Modules/Category/resources/views/pages/categories/create.blade.php` | Modify | P2 | Add title and preserve shell-only role |
| `Modules/Category/resources/views/pages/categories/edit.blade.php` | Modify | P1/P2 | Add title and pass validated scalar ID |
| `Modules/Category/resources/views/livewire/categories/category-table.blade.php` | Modify | P0/P1/P2 | Permission visibility, tree consistency, pagination, confirmation, accessibility |
| `Modules/Category/resources/views/livewire/categories/category-form.blade.php` | Modify | P0/P1/P2 | Validation errors, loading/disabled states, confirmation, shared component alias |
| `Modules/Category/resources/views/category.blade.php` | Remove after verification | P2 | Unreferenced scaffold view |
| `Modules/Category/resources/views/pages/index.blade.php` | Remove after verification | P2 | Unreferenced scaffold view |
| `Modules/Category/resources/views/components/placeholder.blade.php` | Remove after verification | P2 | Used only by scaffold views |
| `Modules/Category/Services/CategoryService.php` | Major modify | P0/P1 | Canonical queries, hierarchy invariants, transactions, storage compensation |
| `Modules/Category/Services/CategoryTypeService.php` | Create if selected | P1 | Focused CategoryType query/write boundary |
| `Modules/Category/Models/Category.php` | Modify | P1/P2 | Align schema, casts/relations/table, remove business traversal |
| `Modules/Category/Models/CategoryType.php` | Modify | P1/P2 | Add `sort_order`, casts, explicit table, relationship typing |
| `Modules/Category/database/migrations/-0001_11_30_000015_category_types.php` | Historical review | P1/P2 | Plan deterministic fresh-install migration strategy |
| `Modules/Category/database/migrations/-0001_11_30_000016_create_categories_table.php` | Historical review | P0/P1/P2 | Evidence for unsafe cascade, uniqueness, nullability, and comments |
| `Modules/Category/database/migrations/<new-corrective-migration>.php` | Create | P0/P1 | Restrictive CategoryType delete and confirmed schema corrections |
| `Modules/Shared/resources/views/components/<image-upload>.blade.php` | Create | P1 | Canonical shared ownership for generic upload UI |
| `Modules/Admin/resources/views/components/image-upload.blade.php` | Migrate/remove after callers | P1 | Eliminate generic shared UI ownership in Admin |
| `Modules/Product/resources/views/livewire/products/product-form.blade.php` | Modify during component migration | P1 | Switch to canonical shared upload component |
| `Modules/Website/resources/views/livewire/admin/home/home-settings.blade.php` | Modify during component migration | P1 | Switch to canonical shared upload component |
| `Modules/Admin/Models/Category.php` | Migrate/remove after verification | P1 | Duplicate canonical Category Model |
| `Modules/Product/Models/Category.php` | Migrate/remove after verification | P1 | Duplicate canonical Category Model |
| `Modules/Post/Models/Category.php` | Migrate/remove after verification | P1 | Duplicate canonical Category Model |
| `Modules/Website/Models/Category.php` | Migrate/remove after verification | P1 | Duplicate canonical Category Model |
| `Modules/Website/Services/CategoryService.php` | Migrate or narrow | P1 | Depend on canonical Category domain contract |
| `Modules/Admin/Livewire/Categories/CategoryTable.php` | Migrate/remove after verification | P1 | Duplicate Category administration UI |
| `Modules/Admin/Livewire/Categories/CategoryForm.php` | Migrate/remove after verification | P1 | Duplicate Category administration UI |
| `Modules/Admin/Http/Controllers/CategoryController.php` | Migrate/remove after verification | P1 | Duplicate Category controller |
| `Modules/Category/tests/Feature/*` | Create | P0/P1 | Route, permission, Livewire, migration, transaction, upload, API tests |
| `Modules/Category/tests/Unit/*` | Create | P1 | Service hierarchy/tree and Model behavior tests |
| Project test/CI configuration | Modify only if needed | P1 | Ensure module tests are discovered and executed |

## 7. Risk Control

The following must not be changed yet:

1. Do not rename or rewrite the two deployed negative-year migration files until production migration history is verified. Prefer forward-only corrective migrations.
2. Do not delete `Modules/Admin/Models/Category.php`, `Modules/Product/Models/Category.php`, `Modules/Post/Models/Category.php`, or `Modules/Website/Models/Category.php` before every caller is migrated and tested.
3. Do not remove `Modules/Admin/Livewire/Categories/*`, `Modules/Admin/Http/Controllers/CategoryController.php`, or `Modules/Website/Services/CategoryService.php` based only on static similarity.
4. Do not change category deletion behavior until stakeholders confirm reject, reparent, or recursive-delete semantics.
5. Do not impose a maximum tree depth until current production data and business requirements are inspected.
6. Do not change slug uniqueness from global to per type, or vice versa, until existing duplicates/callers and URL behavior are verified.
7. Do not make `categories.type` non-null until all existing rows and category use cases are audited.
8. Do not rename `/admin/category` or `admin.category.*` without a caller inventory and compatibility plan.
9. Do not expose or implement the API until its public/private contract is approved.
10. Do not move or remove `Modules/Admin/resources/views/components/image-upload.blade.php` until component registration and all callers are identified.
11. Do not introduce caching to hide unbounded queries; first establish bounded Service queries and explicit invalidation.
12. Do not add import/export without a sample workbook and confirmed mapping, unique key, hierarchy behavior, modes, and transaction strategy.
13. Do not introduce DTOs; Services must accept validated arrays/scalars.
14. Do not combine P0 containment with broad cross-module cleanup in one release. Security/data-loss fixes require small reviewable changes and regression tests first.

Rollback and release controls:

- Back up Category and CategoryType tables before applying constraint migrations.
- Test corrective migrations against a production-like MySQL copy.
- Deploy authorization changes with verified permission seeding/assignment to avoid locking out intended administrators.
- Keep old cross-module implementations until each migrated caller passes regression tests.
- Use feature flags or compatibility adapters only when necessary to migrate callers incrementally; remove them after consolidation.
- Verify storage rollback behavior with fake disks before production deployment.
- Require focused Category tests, migration smoke tests, and relevant Product/Post/Website/Admin tests at every phase gate.
