# Post Refactor Plan

## 1. Executive Summary

`Modules/Post` currently owns the admin blog/news workflow, but the implementation is still legacy-shaped: Livewire components query models directly, mutations and import/export behavior bypass services, authorization depends mostly on `auth:admin`, and several non-Post domain models live inside the module. The first refactor pass should not change user-facing behavior broadly. It should contain security exposure, introduce service-owned transactions, and stop raw errors and client-controlled IDs from becoming production risks.

The safest direction is:

- Make `Modules/Post` the canonical owner for Post admin behavior before deleting duplicates in `Modules/Admin` or `Modules/Website`.
- Keep controllers and page Blade files thin.
- Move all queries, persistence, import/export orchestration, and transactions into `Modules/Post/Services/PostService.php` and `Modules/Post/Services/ImportExport.php`.
- Add permission checks around every route and Livewire action using the declared permissions in `Modules/Post/config/module.php`.
- Defer cleanup of unused-looking files until caller tests prove they are not used.

## 2. P0 Critical Fixes

### P0-01: Secure or remove broken unauthenticated API route

* Issue: `Modules/Post/routes/api.php` exposes unauthenticated `GET /post` and points to `Modules\Post\Http\Controllers\Api\PostController@index`, but `Modules/Post/Http/Controllers/Api/PostController.php` has no `index()` method.
* Root Cause: Scaffolded API route was left enabled while the controller stayed empty and the `auth:sanctum` middleware block was commented out.
* Business Impact: Public callers can hit a broken endpoint, creating availability noise and possible accidental data exposure if an `index()` is later added without guard rules.
* Technical Impact: Route resolution can fail at runtime, and the API boundary has no authentication, authorization, response contract, or tests.
* Proposed Solution: Either disable the API route until a real API contract is approved, or implement a guarded read-only endpoint with explicit middleware and permission policy. Prefer disabling for the first safety pass because no API behavior is documented.
* Files To Change: `Modules/Post/routes/api.php`, `Modules/Post/Http/Controllers/Api/PostController.php`, `tests/Feature/Post/PostRouteConfigurationTest.php`.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: `GET /post` is not publicly reachable unless an approved auth-protected contract exists; route boot tests cover the final route state; `Modules/Post/Http/Controllers/Api/PostController.php` no longer contains unused imports or missing routed methods.

### P0-02: Enforce named permissions on Post web routes

* Issue: `Modules/Post/routes/web.php` uses only `web` and `auth:admin`; it does not enforce `view_post`, `create_post`, `edit_post`, or `delete_post` from `Modules/Post/config/module.php`.
* Root Cause: Authentication was treated as enough authorization for admin post management.
* Business Impact: Any authenticated admin can access post management screens regardless of role, increasing risk of unauthorized publishing or content tampering.
* Technical Impact: Route access is not aligned with roadmap P0 authorization requirements and cannot be regression-tested at capability level.
* Proposed Solution: Add route-level permission middleware or policy checks matching the project permission convention: `view_post` for index, `create_post` for create, and `edit_post` for edit.
* Files To Change: `Modules/Post/routes/web.php`, `Modules/Post/config/module.php`, `tests/Feature/Post/PostRouteAuthorizationTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Admins lacking the required permission receive a denied response; admins with the required permission can access the page; tests cover index/create/edit authorization.

### P0-03: Enforce permissions inside Post form actions

* Issue: `Modules/Post/Livewire/Posts/PostForm.php` allows create and update once the component is mounted, without checking `create_post` or `edit_post`.
* Root Cause: Authorization is not enforced at the Livewire action boundary.
* Business Impact: A user who can reach or trigger the Livewire component can create or edit posts beyond their role.
* Technical Impact: Route checks alone would not protect direct Livewire action calls.
* Proposed Solution: Authorize in `mount()` and `save()` using the project permission/policy convention; enforce `create_post` for new records and `edit_post` for existing records.
* Files To Change: `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Services/PostService.php`, `tests/Feature/Post/PostLivewireAuthorizationTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Unauthorized Livewire create/update calls are denied server-side; authorized users can save; tests cover both create and edit denial paths.

### P0-04: Enforce permissions inside Post table actions

* Issue: `Modules/Post/Livewire/Posts/PostTable.php` allows delete, bulk delete, clone, import, and export without action-specific permission checks.
* Root Cause: UI visibility and `auth:admin` are doing the work that server-side capability checks should do.
* Business Impact: Unauthorized users may delete, duplicate, import, export, or view post data.
* Technical Impact: Livewire action methods are unsafe independent entry points.
* Proposed Solution: Add explicit checks in `delete()`, `deleteSelected()`, `clone()`, `import()`, and `export()`. Use `view_post` for list/export, `create_post` for clone/import when they create posts, and `delete_post` for destructive actions.
* Files To Change: `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/Services/PostService.php`, `Modules/Post/Services/ImportExport.php`, `tests/Feature/Post/PostLivewireAuthorizationTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Every mutating/export Livewire method denies users without the required permission; tests cover delete, bulk delete, clone, import, and export denial.

### P0-05: Validate and authorize client-provided selected IDs

* Issue: `Modules/Post/Livewire/Posts/PostTable.php` trusts `$selected` IDs in `deleteSelected()` and deletes matching posts directly.
* Root Cause: Client-side selection state is treated as authoritative server input.
* Business Impact: A crafted request could delete records outside the visible page or outside a user's allowed scope.
* Technical Impact: Bulk actions bypass record-level authorization, selection bounds, and audit-ready service semantics.
* Proposed Solution: Move bulk delete into `Modules/Post/Services/PostService.php`; reload selected IDs server-side, authorize each record or enforce a scoped query, reject invalid IDs, and confirm destructive intent.
* Files To Change: `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/Services/PostService.php`, `tests/Feature/Post/PostBulkActionAuthorizationTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Invalid, unauthorized, or out-of-scope IDs are not deleted; authorized bulk delete works; selection is reset after completion; tests cover tampered ID input.

### P0-06: Make post save transactionally consistent

* Issue: `Modules/Post/Livewire/Posts/PostForm.php` creates/updates the post, syncs categories, creates tags, and syncs tags without a transaction.
* Root Cause: Multi-write persistence lives in Livewire instead of a service-owned transaction.
* Business Impact: A failure after the post write can leave partial post data, missing category links, or missing tag links.
* Technical Impact: Persistence is not rollback-safe and violates the service-layer transaction rule.
* Proposed Solution: Add `Modules/Post/Services/PostService.php` and move create/update, thumbnail path assignment, category sync, tag normalization/creation, and tag sync into a single `DB::transaction()` owned by the service.
* Files To Change: `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Services/PostService.php`, `Modules/Post/Models/Post.php`, `tests/Unit/Post/PostServiceTest.php`, `tests/Feature/Post/PostLivewireCrudTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Create/update either fully succeeds or rolls back; Livewire passes only validated arrays/scalars to the service; tests cover rollback on relation-sync failure.

### P0-07: Stop exposing raw import exception messages

* Issue: `Modules/Post/Livewire/Posts/PostTable.php` displays raw exception text with `$this->addError('importFile', 'Lỗi: ' . $e->getMessage())`.
* Root Cause: Import error handling is implemented directly in Livewire without structured logging or safe domain exceptions.
* Business Impact: Internal paths, SQL details, malformed payload details, or stack-adjacent messages can leak to admin users.
* Technical Impact: Error behavior is inconsistent with roadmap P1-12 and makes support/debugging harder.
* Proposed Solution: Log internal exceptions with context, return a generic user-facing error, and move row-level import errors to `Modules/Post/Services/ImportExport.php`.
* Files To Change: `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/Services/ImportExport.php`, `tests/Feature/Post/PostImportSecurityTest.php`.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Unexpected import failures produce safe UI text; detailed errors are logged, not rendered; row validation errors remain structured and non-sensitive.

## 3. P1 Important Refactors

### P1-01: Introduce a Post service layer

* Issue: `Modules/Post/Livewire/Posts/PostForm.php` and `Modules/Post/Livewire/Posts/PostTable.php` query and mutate Eloquent models directly.
* Root Cause: The module predates the active architecture rule that services own business logic, queries, and persistence.
* Business Impact: Changes to post behavior are harder to validate and can regress admin content workflows.
* Technical Impact: Livewire components are too large, difficult to test, and contain duplicated query/persistence logic.
* Proposed Solution: Create `Modules/Post/Services/PostService.php` with methods for pagination, edit loading, category options, create, update, delete, bulk delete, clone, and tag/category sync.
* Files To Change: `Modules/Post/Services/PostService.php`, `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Livewire/Posts/PostTable.php`, `tests/Unit/Post/PostServiceTest.php`, `tests/Feature/Post/PostLivewireCrudTest.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-3 days.
* Acceptance Criteria: Livewire no longer calls `Post::`, `Category::`, `Tag::`, or `DB::transaction()` directly; behavior remains equivalent; unit tests cover service methods.

### P1-02: Replace local JSON import/export with shared import/export architecture

* Issue: `Modules/Post/Livewire/Posts/PostTable.php` owns JSON import/export, and no `Modules/Post/Services/ImportExport.php` exists.
* Root Cause: Import/export was implemented as local Livewire convenience logic instead of the shared v1.5 foundation.
* Business Impact: Imports can create inconsistent content and exports may fail on production-sized datasets.
* Technical Impact: Missing dry-run, row-level errors, duplicate mode, null-overwrite policy, bounded iteration, shared storage, and template behavior.
* Proposed Solution: Add `Modules/Post/Services/ImportExport.php` using `Modules/Shared/Services/ImportExport`; mount `shared.import-export.panel` from `Modules/Post/resources/views/livewire/posts/post-table.blade.php` or a dedicated page; preserve JSON only if explicitly confirmed as a supported format.
* Files To Change: `Modules/Post/Services/ImportExport.php`, `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/resources/views/livewire/posts/post-table.blade.php`, `tests/Unit/Post/PostImportExportTest.php`, `tests/Feature/Post/PostImportExportPanelTest.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days after mapping confirmation.
* Acceptance Criteria: Import/export flows use the shared service contract; dry-run and row-level errors exist; exports are bounded; no import/export mapping remains in Livewire.

### P1-03: Confirm canonical ownership and migrate duplicate Post implementations

* Issue: Post admin logic is duplicated in `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/resources/views/pages/posts/*.blade.php`, `Modules/Post/resources/views/livewire/posts/*.blade.php`, and corresponding `Modules/Admin/...` files; Website models also overlap table ownership.
* Root Cause: Admin, Website, and Post modules each carry versions of the same domain behavior.
* Business Impact: Fixes can be applied to one module but not another, causing inconsistent admin and public content behavior.
* Technical Impact: Duplicate models and components increase regression surface and block architecture enforcement.
* Proposed Solution: Declare `Modules/Post` as canonical for post admin domain behavior, then migrate `Modules/Admin` callers to Post-owned routes/components or thin wrappers; keep Website read models only if explicitly needed for presentation and document dependency rules.
* Files To Change: `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/resources/views/pages/posts/index.blade.php`, `Modules/Post/resources/views/pages/posts/create.blade.php`, `Modules/Post/resources/views/pages/posts/edit.blade.php`, `Modules/Post/resources/views/livewire/posts/post-form.blade.php`, `Modules/Post/resources/views/livewire/posts/post-table.blade.php`, `Modules/Admin/Livewire/Posts/PostForm.php`, `Modules/Admin/Livewire/Posts/PostTable.php`, `Modules/Admin/resources/views/pages/posts/index.blade.php`, `Modules/Admin/resources/views/pages/posts/create.blade.php`, `Modules/Admin/resources/views/pages/posts/edit.blade.php`, `Modules/Admin/resources/views/livewire/posts/post-form.blade.php`, `Modules/Admin/resources/views/livewire/posts/post-table.blade.php`, `Modules/Website/Models/Post.php`, `Modules/Website/Models/Category.php`, `Modules/Website/Models/Tag.php`.
* Risk Level: High.
* Complexity: Critical.
* Estimated Effort: 3-5 days.
* Acceptance Criteria: One canonical write path exists for post admin behavior; duplicate Admin write components are unused or converted to wrappers; route tests prove existing admin URLs still work or redirect intentionally.

### P1-04: Strengthen Post form validation

* Issue: `Modules/Post/Livewire/Posts/PostForm.php` validates only `name`, `slug`, `status`, and `new_thumbnail`; it does not fully validate text fields, booleans, categories, SEO fields, or tag input.
* Root Cause: Validation is minimal and UI-oriented only.
* Business Impact: Invalid content metadata, broken relationships, or overly long tag/SEO input can enter production content.
* Technical Impact: Database writes depend on implicit database limits and later failures instead of clear field-level validation.
* Proposed Solution: Define Livewire rules for every editable field and service-level invariants for category ownership, tag normalization, slug uniqueness, and supported statuses.
* Files To Change: `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Services/PostService.php`, `tests/Feature/Post/PostLivewireValidationTest.php`, `tests/Unit/Post/PostServiceTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Every editable input has validation; invalid category IDs are rejected; tags are trimmed, deduplicated, length-limited, and slug-safe; tests cover valid and invalid saves.

### P1-05: Replace string-based unique slug validation with explicit Rule validation

* Issue: `Modules/Post/Livewire/Posts/PostForm.php` uses `unique:wp_posts,slug,{id}` instead of an explicit rule object with ignored key.
* Root Cause: Legacy string validation is concise but less clear and more error-prone during edit flows.
* Business Impact: Slug collisions can break public content URLs or cause confusing validation behavior.
* Technical Impact: Future soft-delete or scope rules are harder to express safely.
* Proposed Solution: Use Laravel validation rule semantics in Livewire and enforce uniqueness again in `Modules/Post/Services/PostService.php` where needed.
* Files To Change: `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Services/PostService.php`, `tests/Feature/Post/PostLivewireValidationTest.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Edit ignores the current post ID only; duplicate slugs fail; tests cover create and edit slug uniqueness.

### P1-06: Validate import rows before persistence

* Issue: `Modules/Post/Livewire/Posts/PostTable.php` validates only upload MIME/size and then reads `$item['name']` and `$item['status']` directly.
* Root Cause: Import mapping, normalization, validation, and persistence are all mixed together in Livewire.
* Business Impact: Bad rows can partially block imports, create malformed posts, or show unhelpful errors.
* Technical Impact: No row-level diagnostics, no safe duplicate mode, no explicit null-overwrite rule, and no import contract.
* Proposed Solution: Move import handling to `Modules/Post/Services/ImportExport.php` with normalized row validation for required fields, status enum, arrays, lengths, thumbnail path/URL, duplicate handling, dry-run, and error reports.
* Files To Change: `Modules/Post/Services/ImportExport.php`, `Modules/Post/Livewire/Posts/PostTable.php`, `tests/Unit/Post/PostImportExportTest.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-3 days after mapping confirmation.
* Acceptance Criteria: Malformed rows produce structured row errors; no notices from missing keys; duplicate and null handling are explicit; dry-run performs no writes.

### P1-07: Move clone, delete, and bulk delete transactions into service

* Issue: `Modules/Post/Livewire/Posts/PostTable.php` owns clone transaction logic, bulk delete logic, and single delete logic.
* Root Cause: Livewire owns persistence and transactions instead of delegating to service methods.
* Business Impact: Clone/delete behavior is hard to audit and can diverge from future policy rules.
* Technical Impact: `DB::transaction()` in Livewire violates architecture; `Post::find($id)->delete()` can fail on missing records.
* Proposed Solution: Move `clone()`, `delete()`, and `bulkDelete()` behavior into `Modules/Post/Services/PostService.php`; make missing records safe and return explicit results for UI messages.
* Files To Change: `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/Services/PostService.php`, `tests/Unit/Post/PostServiceTest.php`, `tests/Feature/Post/PostLivewireCrudTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Livewire contains no transaction calls; missing post delete is handled safely; clone copies intended relationships only; tests cover success and not-found cases.

### P1-08: Bound export memory and eager-load all exported relationships

* Issue: `Modules/Post/Livewire/Posts/PostTable.php` export calls `$this->getQuery()->get()` and maps tags without eager-loading them.
* Root Cause: Export was implemented for small data and did not use chunking or full relationship eager loading.
* Business Impact: Large exports can exhaust memory or time out; tag data can make exports unexpectedly slow.
* Technical Impact: N+1 query risk and unbounded collection loading violate performance standards.
* Proposed Solution: Move export query to `Modules/Post/Services/ImportExport.php` or `Modules/Post/Services/PostService.php`; use chunk/lazy iteration and eager-load `author`, `categories`, and `tags`.
* Files To Change: `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/Services/ImportExport.php`, `Modules/Post/Services/PostService.php`, `tests/Unit/Post/PostImportExportTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Export does not call unbounded `get()` from Livewire; tags are eager-loaded; test or query-count check covers exported relationships.

### P1-09: Fix select-all semantics and pagination controls

* Issue: `Modules/Post/Livewire/Posts/PostTable.php` `updatedSelectAll()` claims it selects current page rows but calls `$this->getQuery()->pluck('id')`, and `render()` hard-codes `paginate(10)`.
* Root Cause: Pagination and selection state are not modeled through the standard Livewire table pattern.
* Business Impact: Admins can accidentally bulk-delete more posts than the visible page suggests.
* Technical Impact: Selection state is inconsistent with UI messaging and cannot support guarded `All` behavior.
* Proposed Solution: Add `perPage`, `perPageOptions`, reset behavior, and server-side current-page selection through `Modules/Post/Services/PostService.php`; make all-filtered selection explicit only if implemented.
* Files To Change: `Modules/Post/Livewire/Posts/PostTable.php`, `Modules/Post/resources/views/livewire/posts/post-table.blade.php`, `Modules/Post/Services/PostService.php`, `tests/Feature/Post/PostLivewireTableTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Select-all affects only visible page or is clearly labeled as all filtered records; pagination supports `10`, `25`, `50`, `100`, and guarded `All`; tests cover selection after filter/page changes.

### P1-10: Avoid category query on every form render

* Issue: `Modules/Post/Livewire/Posts/PostForm.php` loads all post categories with `Category::where('type', 'post')->get()` in `render()`.
* Root Cause: Option loading lives in Livewire render instead of a service/cached option method.
* Business Impact: Form responsiveness degrades as category count grows.
* Technical Impact: Repeated queries on every render add unnecessary database load.
* Proposed Solution: Load category options through `Modules/Post/Services/PostService.php`, cache only if invalidation is defined, and avoid requerying on unrelated component updates where possible.
* Files To Change: `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Services/PostService.php`, `tests/Unit/Post/PostServiceTest.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Form category loading is service-owned; repeated render behavior is bounded; tests cover category option filtering by `type = post`.

### P1-11: Keep presentation out of Post model

* Issue: `Modules/Post/Models/Post.php` `getStatusBadgeAttribute()` returns HTML.
* Root Cause: UI badge rendering was placed in the ORM model.
* Business Impact: Model output may be reused in API/export contexts where embedded HTML is incorrect or unsafe.
* Technical Impact: Model violates ORM-only rule and couples Tailwind/UI markup to persistence.
* Proposed Solution: Move badge rendering to Blade or a dedicated view/component; keep `Modules/Post/Models/Post.php` limited to casts, fillable fields, relationships, and simple non-UI accessors.
* Files To Change: `Modules/Post/Models/Post.php`, `Modules/Post/resources/views/livewire/posts/post-table.blade.php`, `tests/Unit/Post/PostModelTest.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: `Modules/Post/Models/Post.php` no longer returns HTML; table still displays equivalent status badges; tests or view assertions cover status display.

### P1-12: Resolve duplicate `user()` and `author()` relationships

* Issue: `Modules/Post/Models/Post.php` defines both `user()` and `author()` as the same `belongsTo(User::class, 'user_id')` relationship.
* Root Cause: Different naming conventions were added without choosing a canonical relationship name.
* Business Impact: Developers may use inconsistent relationship names in views/services.
* Technical Impact: Duplicate relationship methods complicate eager-loading and future ownership decisions.
* Proposed Solution: Choose one canonical relationship name, preferably `author()` for post semantics, then preserve or deprecate `user()` only if callers still require it.
* Files To Change: `Modules/Post/Models/Post.php`, `Modules/Post/Services/PostService.php`, `Modules/Post/resources/views/livewire/posts/post-table.blade.php`, `tests/Unit/Post/PostModelTest.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: One canonical relationship is used by Post services/views; any retained alias is documented and tested.

### P1-13: Remove non-Post domain responsibilities from Post module

* Issue: `Modules/Post/Models/Product.php`, `Modules/Post/Models/Review.php`, and `Modules/Post/Models/Wishlist.php` are unrelated to the Post admin flow and duplicate Product/Website concerns.
* Root Cause: Models were copied into the module without strict domain ownership.
* Business Impact: Product, review, and wishlist behavior can diverge across modules.
* Technical Impact: Cross-module coupling from `Modules/Post/Models/Product.php` to `Modules\Category\Models\Category`, `Modules\Website\Models\Wishlist`, and `Modules\Post\Models\Review` makes architecture brittle.
* Proposed Solution: Audit callers, migrate usage to canonical Product/Website/Category modules, then remove or quarantine these models only after tests prove no Post dependency remains.
* Files To Change: `Modules/Post/Models/Product.php`, `Modules/Post/Models/Review.php`, `Modules/Post/Models/Wishlist.php`, `Modules/Product/Models/Product.php`, `Modules/Product/Models/Review.php`, `Modules/Website/Models/Wishlist.php`, `tests/Feature/Post/PostArchitectureTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days after caller audit.
* Acceptance Criteria: Post module no longer owns Product/Review/Wishlist models; all callers use canonical models; architecture tests prevent reintroduction.

### P1-14: Normalize Category and Tag ownership

* Issue: `Modules/Post/Models/Category.php` duplicates category-domain behavior, includes product relationships, and `Modules/Post/Models/Tag.php` has no local `wp_tags` migration.
* Root Cause: Shared taxonomy tables are represented by multiple modules without an ownership contract.
* Business Impact: Category/tag changes can behave differently in Post, Category, Product, and Website flows.
* Technical Impact: Duplicate models obscure which module owns `categories` and `wp_tags`; product relationships leak into Post.
* Proposed Solution: Define whether `Modules/Category` owns `categories` and whether `Modules/Post` owns `wp_tags`; remove product relationships from Post-local category model or replace with canonical category model after dependency rules are confirmed.
* Files To Change: `Modules/Post/Models/Category.php`, `Modules/Post/Models/Tag.php`, `Modules/Category/Models/Category.php`, `Modules/Post/Services/PostService.php`, `tests/Feature/Post/PostArchitectureTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days after ownership decision.
* Acceptance Criteria: Post services use a documented canonical category/tag model; Post category model no longer contains product concerns; tag table ownership is documented.

### P1-15: Repair migration hygiene

* Issue: `Modules/Post/database/migrations/-0001_11_30_000025_create_wp_posts_table.php`, `Modules/Post/database/migrations/-0001_11_30_000027_create_wp_post_tag_table.php`, and `Modules/Post/database/migrations/-0001_11_30_000028_create_category_post_table.php` use malformed negative-year timestamps.
* Root Cause: Legacy imported migrations were not normalized to Laravel migration naming conventions.
* Business Impact: Fresh installs and migration ordering can be fragile.
* Technical Impact: Migration smoke tests and CI may behave differently across environments; roadmap P1-08 flags this as a broader repository risk.
* Proposed Solution: Rename migrations only in a coordinated migration hygiene task, verify deterministic order against dependencies (`wp_posts`, `wp_tags`, `categories`), and add migration tests.
* Files To Change: `Modules/Post/database/migrations/-0001_11_30_000025_create_wp_posts_table.php`, `Modules/Post/database/migrations/-0001_11_30_000027_create_wp_post_tag_table.php`, `Modules/Post/database/migrations/-0001_11_30_000028_create_category_post_table.php`, `tests/Feature/Post/PostMigrationTest.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 1-2 days as part of repository migration cleanup.
* Acceptance Criteria: Fresh migration order is deterministic; pivot tables run after their referenced tables; tests pass on the supported database path.

### P1-16: Clean thumbnail lifecycle

* Issue: `Modules/Post/Livewire/Posts/PostForm.php` stores uploaded thumbnails but does not delete or clean up replaced files.
* Root Cause: File persistence is handled inline without service-level lifecycle rules.
* Business Impact: Storage can grow indefinitely and stale media can remain accessible.
* Technical Impact: Upload behavior lacks cleanup policy, rollback coordination, and tests.
* Proposed Solution: Move thumbnail persistence and replacement cleanup into `Modules/Post/Services/PostService.php`; delete old local files only after successful transaction and never delete remote URL thumbnails.
* Files To Change: `Modules/Post/Livewire/Posts/PostForm.php`, `Modules/Post/Services/PostService.php`, `tests/Unit/Post/PostServiceTest.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Replaced local thumbnails are cleaned up safely; remote URLs are preserved; failed saves do not delete current files.

### P1-17: Add tests for service, routes, Livewire, imports, and performance-sensitive behavior

* Issue: The analysis identifies missing route, authorization, Livewire, service, import/export, transaction, and N+1 coverage for `Modules/Post`.
* Root Cause: The module has feature behavior without proportional automated tests.
* Business Impact: Security and content regressions can ship unnoticed.
* Technical Impact: Refactors cannot be safely staged without tests around current behavior and target behavior.
* Proposed Solution: Add focused tests as each refactor slice lands, starting with route and authorization tests, then service CRUD/transactions, then Livewire validation, import/export, and query-count checks where feasible.
* Files To Change: `tests/Feature/Post/PostRouteAuthorizationTest.php`, `tests/Feature/Post/PostLivewireAuthorizationTest.php`, `tests/Feature/Post/PostLivewireCrudTest.php`, `tests/Feature/Post/PostLivewireTableTest.php`, `tests/Unit/Post/PostServiceTest.php`, `tests/Unit/Post/PostImportExportTest.php`, `tests/Feature/Post/PostArchitectureTest.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days across phases.
* Acceptance Criteria: P0 denial paths are covered; CRUD service behavior is covered; import/export edge cases are covered; table query behavior has regression coverage where tooling supports it.

## 4. P2 Nice To Have Improvements

### P2-01: Remove or repurpose scaffold placeholder views

* Issue: `Modules/Post/resources/views/post.blade.php`, `Modules/Post/resources/views/pages/index.blade.php`, `Modules/Post/resources/views/components/placeholder.blade.php`, and `Modules/Post/resources/views/livewire/placeholder.blade.php` are not referenced by routed Post admin pages.
* Root Cause: Module scaffold files were left after real post pages were added.
* Business Impact: Low direct impact, but developers can mistake placeholders for active UI.
* Technical Impact: Dead files add noise to module scans and architecture catalogs.
* Proposed Solution: After route/caller verification, delete unused placeholders or repurpose them only if a real module landing page is approved.
* Files To Change: `Modules/Post/resources/views/post.blade.php`, `Modules/Post/resources/views/pages/index.blade.php`, `Modules/Post/resources/views/components/placeholder.blade.php`, `Modules/Post/resources/views/livewire/placeholder.blade.php`, `tests/Feature/Post/PostRouteConfigurationTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: No route or view include references removed files; route tests pass; architecture catalog no longer lists stale placeholders.

### P2-02: Replace inline CSS in create page

* Issue: `Modules/Post/resources/views/pages/posts/create.blade.php` contains inline CSS for `.custom-scrollbar`.
* Root Cause: Page-specific styling was embedded directly in Blade.
* Business Impact: Minor UI maintainability issue.
* Technical Impact: Conflicts with the Tailwind/Admin UI rule to avoid inline CSS where Tailwind or shared styling can express the design.
* Proposed Solution: Move scrollbar behavior to a shared class only if it is genuinely needed, or remove it and rely on standard overflow styling.
* Files To Change: `Modules/Post/resources/views/pages/posts/create.blade.php`, `Modules/Post/resources/views/livewire/posts/post-form.blade.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: No inline CSS remains in the create page; form scrolling remains acceptable on desktop and mobile.

### P2-03: Align Livewire bindings with project default

* Issue: `Modules/Post/resources/views/livewire/posts/post-form.blade.php` uses several plain `wire:model` bindings where `wire:model.live` is the project default.
* Root Cause: Legacy Livewire binding style was retained.
* Business Impact: Mostly consistency; some fields may update less predictably than expected by the active standard.
* Technical Impact: Component behavior differs from the documented Livewire 3 pattern.
* Proposed Solution: Convert appropriate bindings to `wire:model.live`; keep non-live behavior only where editor integration or performance requires it and document the exception.
* Files To Change: `Modules/Post/resources/views/livewire/posts/post-form.blade.php`, `Modules/Post/Livewire/Posts/PostForm.php`, `tests/Feature/Post/PostLivewireCrudTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Standard fields use `wire:model.live`; editor fields preserve stable behavior; Livewire save tests pass.

### P2-04: Add responsive table overflow wrapper

* Issue: `Modules/Post/resources/views/livewire/posts/post-table.blade.php` table wrapper lacks an explicit `overflow-x-auto` inner wrapper.
* Root Cause: Table layout was built without the full Admin UI v1.1 responsive pattern.
* Business Impact: Table usability can degrade on smaller screens.
* Technical Impact: UI does not fully match the project table standard.
* Proposed Solution: Add the standard responsive wrapper while preserving current table markup and actions.
* Files To Change: `Modules/Post/resources/views/livewire/posts/post-table.blade.php`, `tests/Feature/Post/PostLivewireTableTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Table scrolls horizontally on narrow viewports; no action buttons or pagination are hidden or overlapped.

### P2-05: Add explicit table/casts metadata where retained

* Issue: `Modules/Post/Models/Category.php` does not explicitly set `$table = 'categories'`, and `Modules/Post/Models/Tag.php` has minimal casts metadata.
* Root Cause: Models rely on convention even though they map shared/cross-module tables.
* Business Impact: Low, but clarity matters while ownership is being resolved.
* Technical Impact: Static analysis and architecture review have less explicit model metadata.
* Proposed Solution: If these models remain in `Modules/Post`, add explicit table metadata and useful casts; otherwise remove them after canonical ownership migration.
* Files To Change: `Modules/Post/Models/Category.php`, `Modules/Post/Models/Tag.php`, `tests/Unit/Post/PostModelTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25-0.5 day.
* Acceptance Criteria: Retained models declare clear table/cast metadata; tests verify basic model configuration.

### P2-06: Decide fate of unused post meta migration

* Issue: `Modules/Post/database/migrations/2026_05_08_111335_post_meta.php` creates `post_meta`, but no Post route, controller, model, Livewire class, or view uses it.
* Root Cause: A metadata table was added without a completed feature.
* Business Impact: Unused schema can confuse admins and developers about supported Post settings.
* Technical Impact: Extra table ownership is unclear and may conflict with future settings/meta features.
* Proposed Solution: Keep the migration untouched until a schema ownership review confirms whether `post_meta` belongs to Post, Website settings, or should be deprecated.
* Files To Change: `Modules/Post/database/migrations/2026_05_08_111335_post_meta.php`, `Modules/Post/Models/PostMeta.php` if a real feature is approved, `tests/Feature/Post/PostMigrationTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day for decision and tests; implementation deferred.
* Acceptance Criteria: The table has a documented owner or deprecation path; no feature code is added without approved requirements.

### P2-07: Clean empty API controller after API decision

* Issue: `Modules/Post/Http/Controllers/Api/PostController.php` is empty while `Modules/Post/routes/api.php` references `index()`.
* Root Cause: Scaffolded API controller was not completed.
* Business Impact: Low after P0 route containment, but stale API files cause confusion.
* Technical Impact: Static analysis may flag unused imports and missing method references.
* Proposed Solution: Remove the empty controller if the API route is disabled, or implement the approved method only after a documented API contract exists.
* Files To Change: `Modules/Post/Http/Controllers/Api/PostController.php`, `Modules/Post/routes/api.php`, `tests/Feature/Post/PostRouteConfigurationTest.php`.
* Risk Level: Low after P0.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: No route points to a missing method; no empty API controller remains unless intentionally reserved and documented.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. P0-01: Secure or remove `Modules/Post/routes/api.php`.
2. P0-02: Add route-level permissions in `Modules/Post/routes/web.php`.
3. P0-03 and P0-04: Add Livewire action permission checks in `Modules/Post/Livewire/Posts/PostForm.php` and `Modules/Post/Livewire/Posts/PostTable.php`.
4. P0-05: Validate and authorize selected IDs before bulk actions.
5. P0-07: Replace raw import exception output with safe errors.
6. Add the first route and Livewire authorization tests under `tests/Feature/Post/`.

### Phase 2: Correctness and Maintainability

1. P1-01: Introduce `Modules/Post/Services/PostService.php`.
2. P0-06 and P1-07: Move save, clone, delete, and bulk delete persistence into service-owned transactions.
3. P1-04 and P1-05: Strengthen Livewire validation and slug uniqueness.
4. P1-02 and P1-06: Add `Modules/Post/Services/ImportExport.php` after import/export mapping decisions are confirmed.
5. P1-03, P1-13, and P1-14: Decide canonical ownership and migrate duplicate Admin/Website/Product/Category concerns carefully.
6. P1-15: Repair migration hygiene as part of the broader repository migration cleanup, not as an isolated Post-only rename.

### Phase 3: Performance and Cleanup

1. P1-08: Bound exports and eager-load exported relationships.
2. P1-09: Fix select-all semantics and add standard pagination controls.
3. P1-10: Move category option loading to service and bound repeated queries.
4. P1-11 and P1-12: Clean model presentation and duplicate relationship naming.
5. P2-01 through P2-07: Remove scaffold leftovers, clean UI consistency, and finalize unused API/meta decisions after tests prove safety.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/Post/routes/api.php` | Modify or disable route | P0 | Public broken API route points to missing `index()` without auth. |
| `Modules/Post/Http/Controllers/Api/PostController.php` | Modify or remove after route decision | P0/P2 | Empty controller is referenced by API route. |
| `Modules/Post/routes/web.php` | Modify | P0 | Add named permission middleware for admin Post pages. |
| `Modules/Post/config/module.php` | Review/possibly modify | P0 | Declared permissions must align with route/action enforcement. |
| `Modules/Post/Livewire/Posts/PostForm.php` | Modify | P0/P1/P2 | Add authorization, validation, service calls, and remove direct model writes. |
| `Modules/Post/Livewire/Posts/PostTable.php` | Modify | P0/P1 | Add authorization, safe errors, service calls, bounded import/export, pagination, and selection fixes. |
| `Modules/Post/Services/PostService.php` | Create | P0/P1 | Own CRUD, queries, transactions, clone, delete, bulk delete, options, and media lifecycle. |
| `Modules/Post/Services/ImportExport.php` | Create | P1 | Use shared import/export foundation for import/export behavior. |
| `Modules/Post/resources/views/livewire/posts/post-form.blade.php` | Modify | P1/P2 | Show validation consistently and align Livewire bindings. |
| `Modules/Post/resources/views/livewire/posts/post-table.blade.php` | Modify | P1/P2 | Use shared import/export panel, responsive table wrapper, pagination controls, and clear selection semantics. |
| `Modules/Post/resources/views/pages/posts/create.blade.php` | Modify | P2 | Remove inline CSS. |
| `Modules/Post/resources/views/pages/posts/index.blade.php` | Review/possibly modify | P1 | Confirm canonical Post page wrapper after duplicate Admin migration. |
| `Modules/Post/resources/views/pages/posts/edit.blade.php` | Review/possibly modify | P1 | Confirm canonical Post page wrapper after duplicate Admin migration. |
| `Modules/Post/Models/Post.php` | Modify | P1 | Remove HTML accessor and resolve duplicate user/author relationship. |
| `Modules/Post/Models/Category.php` | Modify or remove after ownership decision | P1/P2 | Remove product concerns and clarify table ownership. |
| `Modules/Post/Models/Tag.php` | Modify or retain after ownership decision | P1/P2 | Clarify `wp_tags` ownership and metadata. |
| `Modules/Post/Models/Product.php` | Remove after caller audit | P1/P2 | Non-Post duplicate model. |
| `Modules/Post/Models/Review.php` | Remove after caller audit | P1/P2 | Non-Post duplicate model. |
| `Modules/Post/Models/Wishlist.php` | Remove after caller audit | P1/P2 | Non-Post duplicate model. |
| `Modules/Post/database/migrations/-0001_11_30_000025_create_wp_posts_table.php` | Rename/review in migration hygiene task | P1 | Malformed migration timestamp and fresh-install ordering risk. |
| `Modules/Post/database/migrations/-0001_11_30_000027_create_wp_post_tag_table.php` | Rename/review in migration hygiene task | P1 | Malformed migration timestamp and dependency order risk. |
| `Modules/Post/database/migrations/-0001_11_30_000028_create_category_post_table.php` | Rename/review in migration hygiene task | P1 | Malformed migration timestamp and dependency order risk. |
| `Modules/Post/database/migrations/2026_05_08_111335_post_meta.php` | Review only until requirements confirmed | P2 | Creates unused `post_meta` table. |
| `Modules/Post/resources/views/post.blade.php` | Remove after caller audit | P2 | Unused scaffold placeholder. |
| `Modules/Post/resources/views/pages/index.blade.php` | Remove after caller audit | P2 | Unused scaffold placeholder. |
| `Modules/Post/resources/views/components/placeholder.blade.php` | Remove after caller audit | P2 | Unused scaffold placeholder. |
| `Modules/Post/resources/views/livewire/placeholder.blade.php` | Remove after caller audit | P2 | Unused scaffold placeholder. |
| `Modules/Admin/Livewire/Posts/PostForm.php` | Migrate or convert to wrapper | P1 | Duplicate Post admin form logic. |
| `Modules/Admin/Livewire/Posts/PostTable.php` | Migrate or convert to wrapper | P1 | Duplicate Post admin table logic. |
| `Modules/Admin/resources/views/pages/posts/index.blade.php` | Migrate or convert to wrapper | P1 | Duplicate page wrapper. |
| `Modules/Admin/resources/views/pages/posts/create.blade.php` | Migrate or convert to wrapper | P1 | Duplicate page wrapper. |
| `Modules/Admin/resources/views/pages/posts/edit.blade.php` | Migrate or convert to wrapper | P1 | Duplicate page wrapper. |
| `Modules/Admin/resources/views/livewire/posts/post-form.blade.php` | Migrate or convert to wrapper | P1 | Duplicate Livewire view. |
| `Modules/Admin/resources/views/livewire/posts/post-table.blade.php` | Migrate or convert to wrapper | P1 | Duplicate Livewire view. |
| `Modules/Website/Models/Post.php` | Review ownership | P1 | Overlaps same `wp_posts` table. |
| `Modules/Website/Models/Category.php` | Review ownership | P1 | Overlaps same `categories` table. |
| `Modules/Website/Models/Tag.php` | Review ownership | P1 | Overlaps same `wp_tags` table. |
| `Modules/Product/Models/Product.php` | Review canonical ownership | P1 | Target owner for product model duplicated in Post. |
| `Modules/Product/Models/Review.php` | Review canonical ownership | P1 | Target owner for review model duplicated in Post. |
| `Modules/Website/Models/Wishlist.php` | Review canonical ownership | P1 | Target owner for wishlist model referenced by Post duplicate product model. |
| `Modules/Category/Models/Category.php` | Review canonical ownership | P1 | Likely canonical owner for category model used by Post. |
| `tests/Feature/Post/PostRouteConfigurationTest.php` | Create | P0/P2 | Verify route state and missing API route behavior. |
| `tests/Feature/Post/PostRouteAuthorizationTest.php` | Create | P0 | Verify named permission route access. |
| `tests/Feature/Post/PostLivewireAuthorizationTest.php` | Create | P0 | Verify Livewire action denial paths. |
| `tests/Feature/Post/PostBulkActionAuthorizationTest.php` | Create | P0 | Verify tampered selected IDs cannot delete records. |
| `tests/Feature/Post/PostImportSecurityTest.php` | Create | P0 | Verify safe import error output. |
| `tests/Feature/Post/PostLivewireCrudTest.php` | Create | P1/P2 | Verify create/edit/delete UI behavior. |
| `tests/Feature/Post/PostLivewireValidationTest.php` | Create | P1 | Verify field validation and slug uniqueness. |
| `tests/Feature/Post/PostLivewireTableTest.php` | Create | P1/P2 | Verify filters, pagination, selection, and responsive table behavior. |
| `tests/Feature/Post/PostImportExportPanelTest.php` | Create | P1 | Verify shared import/export panel integration. |
| `tests/Feature/Post/PostArchitectureTest.php` | Create | P1 | Verify module ownership and duplicate dependency rules. |
| `tests/Feature/Post/PostMigrationTest.php` | Create | P1/P2 | Verify migration order and optional `post_meta` decision. |
| `tests/Unit/Post/PostServiceTest.php` | Create | P0/P1 | Verify service-owned CRUD, transactions, options, media cleanup. |
| `tests/Unit/Post/PostImportExportTest.php` | Create | P1 | Verify import/export mapping, validation, dry-run, and bounded export. |
| `tests/Unit/Post/PostModelTest.php` | Create | P1/P2 | Verify model configuration and relationship decisions. |

## 7. Risk Control

Do not change production behavior broadly until P0 authorization and API containment are covered by tests. In particular:

- Do not delete `Modules/Post/Models/Product.php`, `Modules/Post/Models/Review.php`, `Modules/Post/Models/Wishlist.php`, placeholder views, or duplicate Admin files until caller audits and route tests prove they are unused or safely migrated.
- Do not rename `Modules/Post/database/migrations/-0001_11_30_000025_create_wp_posts_table.php`, `Modules/Post/database/migrations/-0001_11_30_000027_create_wp_post_tag_table.php`, or `Modules/Post/database/migrations/-0001_11_30_000028_create_category_post_table.php` as an isolated change; coordinate with the repository-wide migration hygiene task.
- Do not implement import/export changes until the supported file format, unique key, duplicate mode, dry-run behavior, null-overwrite policy, and row error contract are confirmed.
- Do not move Website public read behavior to Post models without checking frontend routes and public content assumptions.
- Do not remove `post_meta` from `Modules/Post/database/migrations/2026_05_08_111335_post_meta.php` until its intended owner is confirmed.
- Do not introduce DTOs, direct model queries in Livewire, Bootstrap/jQuery UI patterns, or new shared abstractions that are not required by the Post refactor.
- Do not rely on hidden buttons, disabled UI, or selected IDs as authorization. Every action must fail closed server-side.
