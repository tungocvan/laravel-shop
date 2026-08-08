# Product Refactor Plan

## 1. Executive Summary

The Product module should become the canonical owner of product catalog behavior, but the current implementation mixes domain logic into Livewire, exposes incomplete routes, depends on Admin/Category/Website/Post implementation details, and performs risky imports, exports, mutations, and multi-write operations without enough authorization, validation, transactions, or memory bounds.

This plan converts the findings in `docs/modules/Product/ANALYSIS.md` into a phased Laravel 12 and Livewire 3 refactor path. The priority is to close P0 security and undefined-route risks first, then move Product behavior into service boundaries, repair validation and transactions, align model ownership, and finally clean up placeholders and developer-experience issues. No implementation code is included here.

## 2. P0 Critical Fixes

### P0-01 Undefined Public Product API Route

* Issue: `Modules/Product/routes/api.php` exposes `GET /product` to `Modules\Product\Http\Controllers\Api\ProductController@index`, but `Modules/Product/Http/Controllers/Api/ProductController.php` has no `index()` method and no explicit API contract.
* Root Cause: A scaffolded or unfinished API route was enabled while its controller remained empty.
* Business Impact: Public consumers can hit an unstable endpoint, producing errors and making the product API surface unclear.
* Technical Impact: Route dispatch can fail at runtime; auth, throttling, serialization, filtering, and response shape are undefined.
* Proposed Solution: Disable the route until an approved Product API spec exists, or implement a read-only API endpoint with explicit middleware, validation, pagination, and service-backed query behavior.
* Files To Change: `Modules/Product/routes/api.php`, `Modules/Product/Http/Controllers/Api/ProductController.php`, optional tests under `Modules/Product/tests` or the repository's existing test location.
* Risk Level: Critical.
* Complexity: Low if disabled; Medium if implemented.
* Estimated Effort: 0.5 day to disable and test; 1-2 days to implement safely.
* Acceptance Criteria: `/product` no longer resolves to a missing method; any enabled API route has defined middleware, response shape, pagination, and passing route tests.

### P0-02 Missing Authorization On Product Form Save

* Issue: `Modules/Product/Livewire/Products/ProductForm.php` creates and updates products, stores images, and syncs categories in `save()` without explicit Livewire action authorization.
* Root Cause: Authorization was applied at the controller entry route, but the actual write happens later in a Livewire action.
* Business Impact: An authenticated admin without the correct capability may create or modify catalog records if they can trigger the Livewire action.
* Technical Impact: The module relies on UI visibility and route middleware instead of server-side action enforcement.
* Proposed Solution: Authorize inside `save()` based on operation type: create requires `create_product`, update requires `edit_product`; preserve existing `auth:admin` route protection.
* Files To Change: `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/config/module.php`, related authorization tests.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day including tests.
* Acceptance Criteria: Unauthorized admins receive a denied response from `save()` for create and update; authorized admins can still create and update; tests cover both paths.

### P0-03 Missing Authorization On Product Table Mutations

* Issue: `Modules/Product/Livewire/Products/ProductTable.php` mutates records through `toggleStatus()`, `duplicate()`, `delete()`, `deleteSelected()`, `applyCategories()`, `removeCategory()`, `import()`, and potentially `export()` without method-level authorization.
* Root Cause: Mutating Livewire actions were treated as protected by the page route instead of requiring capability checks per action.
* Business Impact: Unauthorized product changes, deletions, imports, exports, and relationship changes can affect catalog integrity and sensitive business data.
* Technical Impact: Security behavior is inconsistent with roadmap P0 and Livewire 3 best practice.
* Proposed Solution: Add explicit permission checks to every ProductTable action, mapping reads to `view_product`, writes to `create_product`, `edit_product`, or `delete_product`, and import/export to dedicated capabilities if added.
* Files To Change: `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/config/module.php`, related authorization tests.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day including negative tests.
* Acceptance Criteria: Every mutating ProductTable action denies unauthorized admins server-side; authorized users retain expected behavior; tests cover delete, bulk delete, status toggle, category mutation, import, and export permissions.

### P0-04 Missing Authorization On Product Commission Page

* Issue: `Modules/Product/Http/Controllers/ProductCommissionController.php` loads product commission configuration without a permission or policy check.
* Root Cause: The commission controller was added outside the permission middleware used by `Modules/Product/Http/Controllers/ProductController.php`.
* Business Impact: Affiliate commission settings can expose or affect revenue logic and should not be visible or mutable to every authenticated admin.
* Technical Impact: The route violates named-permission enforcement for sensitive business actions.
* Proposed Solution: Add route/controller authorization for commission viewing and editing, preferably with a dedicated `manage_product_commission` permission or a clear mapping to `edit_product`.
* Files To Change: `Modules/Product/routes/web.php`, `Modules/Product/Http/Controllers/ProductCommissionController.php`, `Modules/Product/config/module.php`, related route/authorization tests.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Unauthorized admins cannot access `/admin/products/{productId}/commissions`; authorized admins can; tests cover allowed and denied access.

### P0-05 Undefined Rich Text Sanitization Policy

* Issue: `Modules/Product/Livewire/Products/ProductForm.php` accepts `short_description` and `description` from rich text components without a documented HTML allowlist or sanitization strategy.
* Root Cause: Rich text fields were stored directly from Livewire state, and the module does not define whether HTML is trusted, sanitized, or escaped.
* Business Impact: Product pages may display unsafe or malformed content, creating XSS and brand/content integrity risks.
* Technical Impact: Validation and rendering behavior is ambiguous across admin and website surfaces.
* Proposed Solution: Define a Product rich-text policy, validate maximum length and allowed content, sanitize on persistence or output according to the chosen standard, and ensure Blade output uses the correct escaped or sanitized rendering.
* Files To Change: `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/resources/views/livewire/products/product-form.blade.php`, Product display views that render these fields if in scope, service file proposed as `Modules/Product/Services/ProductService.php`, related validation/security tests.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1-2 days depending on sanitizer already available in the project.
* Acceptance Criteria: Unsafe script/event-handler content cannot be persisted or rendered unsafely; allowed formatting still works; tests cover malicious and valid rich text.

## 3. P1 Important Refactors

### P1-01 Add Product Service Layer

* Issue: `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Imports/ProductsImport.php`, and `Modules/Product/Exports/ProductsExport.php` contain direct queries, persistence, duplication, category sync, import, and export logic.
* Root Cause: The module bypasses the mandatory service layer.
* Business Impact: Product behavior is harder to audit, test, secure, and reuse across UI/API/import flows.
* Technical Impact: Livewire owns queries and transactions that should belong to services under the project architecture.
* Proposed Solution: Create `Modules/Product/Services/ProductService.php` for CRUD, listing, sorting, filtering, duplication, category assignment, delete/bulk delete, and transaction orchestration. Livewire should validate UI state and call service methods with validated arrays/scalars.
* Files To Change: `Modules/Product/Services/ProductService.php`, `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Models/Product.php`, related service and Livewire tests.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days.
* Acceptance Criteria: Product Livewire classes no longer query models directly for business operations; product writes and list queries go through `ProductService`; tests verify service behavior and Livewire integration.

### P1-02 Align Controller Permissions With Actual Actions

* Issue: `Modules/Product/Http/Controllers/ProductController.php` applies permission middleware to `show`, `store`, `update`, and `destroy`, but those methods do not exist.
* Root Cause: Controller permissions were copied from a resource-controller pattern while actual writes moved to Livewire.
* Business Impact: Reviewers may believe actions are protected when the protection is not attached to the real write path.
* Technical Impact: Authorization is misleading and incomplete.
* Proposed Solution: Keep controller middleware only for actual page routes and move write authorization to Livewire/service boundaries.
* Files To Change: `Modules/Product/Http/Controllers/ProductController.php`, `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, related route tests.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Controller middleware references only existing methods; Livewire write actions are covered by explicit tests.

### P1-03 Fix Or Remove Missing Commission View Flow

* Issue: `Modules/Product/Http/Controllers/ProductCommissionController.php` returns `Product::pages.affiliate.product-commissions`, but no matching file exists under `Modules/Product/resources/views`.
* Root Cause: The commission route was added before its page Blade and Livewire flow were completed.
* Business Impact: Admins navigating to commission settings receive a broken page instead of a usable workflow.
* Technical Impact: Route boot/page response tests would fail.
* Proposed Solution: Either remove/disable `admin.products.commissions` until the feature is specified, or add a thin page Blade and Livewire/service-backed commission editor.
* Files To Change: `Modules/Product/routes/web.php`, `Modules/Product/Http/Controllers/ProductCommissionController.php`, `Modules/Product/resources/views/pages/affiliate/product-commissions.blade.php`, possible `Modules/Product/Livewire/Products/ProductCommissionForm.php`, `Modules/Product/Services/ProductService.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 0.5 day to disable; 1-2 days to implement.
* Acceptance Criteria: The commission route either returns a working authorized page or is absent; no route references a missing Blade file.

### P1-04 Add Transactional Product Save

* Issue: `Modules/Product/Livewire/Products/ProductForm.php` updates product data and category pivot rows without a transaction.
* Root Cause: Persistence is performed inline in Livewire instead of a transaction-owning service.
* Business Impact: A product can be saved without its intended categories, or categories can be modified after a partial failure.
* Technical Impact: Multi-write operations are not atomic.
* Proposed Solution: Move save behavior into `Modules/Product/Services/ProductService.php`, use a database transaction for product create/update plus category sync, and define file-upload rollback/cleanup behavior.
* Files To Change: `Modules/Product/Services/ProductService.php`, `Modules/Product/Livewire/Products/ProductForm.php`, related transaction tests.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Product data and category sync commit or roll back together; tests cover failure rollback.

### P1-05 Handle Missing Product On Edit Save

* Issue: `Modules/Product/Livewire/Products/ProductForm.php` uses `Product::find($this->productId)` then calls `$product->update($data)`, which can fail on null.
* Root Cause: The update path does not use `findOrFail()` or service-level existence validation.
* Business Impact: Editing a deleted or invalid product can crash the admin workflow.
* Technical Impact: Null dereference and inconsistent error handling.
* Proposed Solution: Validate product existence during mount/save, return a safe not-found response, and centralize update lookup in `ProductService`.
* Files To Change: `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Services/ProductService.php`, related Livewire tests.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Saving a missing product shows a safe failure or 404; no null method call occurs.

### P1-06 Add Sort Column Allowlist

* Issue: `Modules/Product/Livewire/Products/ProductTable.php` accepts browser-provided sort columns and passes them into `orderBy()`.
* Root Cause: UI sort state is trusted without server-side allowlisting.
* Business Impact: Bad input can break product list pages and create a query abuse surface.
* Technical Impact: Query builder receives untrusted column identifiers.
* Proposed Solution: Define an explicit Product sort allowlist in `ProductService` or Livewire state and ignore or reject unknown sort columns.
* Files To Change: `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Services/ProductService.php`, related query tests.
* Risk Level: High.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Unknown sort fields are rejected or reset; known fields sort correctly; tests cover both.

### P1-07 Fix `stock_qty` Versus `quantity`

* Issue: `Modules/Product/resources/views/livewire/products/product-table.blade.php` displays and sorts `stock_qty`, while `Modules/Product/database/migrations/-0001_11_30_000015_create_wp_products_table.php` and `Modules/Product/Models/Product.php` define `quantity`.
* Root Cause: UI and schema names drifted.
* Business Impact: Inventory display can be wrong or broken, confusing admins.
* Technical Impact: Sorting by a non-existent column can produce SQL errors.
* Proposed Solution: Align the table column, sort field, and display logic to `quantity`, unless a confirmed migration introduces `stock_qty`.
* Files To Change: `Modules/Product/resources/views/livewire/products/product-table.blade.php`, `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Services/ProductService.php`, related list tests.
* Risk Level: High.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Product list displays the actual `quantity` value and sorting uses a valid database column.

### P1-08 Complete Product Form Validation

* Issue: `Modules/Product/Livewire/Products/ProductForm.php` does not fully validate `sale_price`, descriptions, boolean state, category IDs, gallery state, tags, or sale price less than regular price.
* Root Cause: Validation focuses on a small subset of fields and omits business invariants.
* Business Impact: Invalid prices, categories, rich text, and tag data can enter the catalog.
* Technical Impact: Database casts may receive inconsistent values; UI and import behavior diverge.
* Proposed Solution: Add Livewire UI validation and service-level invariants using validated arrays, including clean numeric money values, category existence, boolean rules, string/array limits, image rules, and price relationship rules.
* Files To Change: `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Services/ProductService.php`, `Modules/Product/Models/Product.php`, related validation tests.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Invalid product fields are rejected with user-friendly messages; valid create/edit flows still pass.

### P1-09 Validate Bulk Category IDs

* Issue: `Modules/Product/Livewire/Products/ProductTable.php` validates `bulkCategoryIds` as an array but does not validate each ID exists in `categories`.
* Root Cause: Relationship IDs from UI state are trusted.
* Business Impact: Bulk assignment can reference invalid or unintended categories.
* Technical Impact: Pivot writes may fail or create inconsistent category assignments if constraints differ across environments.
* Proposed Solution: Validate `bulkCategoryIds.*` against the canonical categories table and enforce the rule again in `ProductService`.
* Files To Change: `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Services/ProductService.php`, related validation tests.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Invalid category IDs are rejected before sync; valid category IDs sync successfully.

### P1-10 Transactional Product Duplication

* Issue: `Modules/Product/Livewire/Products/ProductTable.php` duplicates a product and syncs categories without a transaction.
* Root Cause: Duplication is implemented inline in Livewire instead of a service transaction.
* Business Impact: Duplicate products may be created without their category assignments.
* Technical Impact: Multi-write consistency is not guaranteed.
* Proposed Solution: Move duplication into `ProductService` and wrap replicate/save/category sync in a transaction with unique slug generation.
* Files To Change: `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Services/ProductService.php`, related transaction tests.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Duplicate product and copied categories commit atomically; slug uniqueness is preserved.

### P1-11 Bound Product List Select-All And All Pagination

* Issue: `Modules/Product/Livewire/Products/ProductTable.php` plucks all filtered IDs in `updatedSelectAll()` and uses `paginate(999999)` for `$perPage === 'all'`.
* Root Cause: The UI offers full-dataset operations without memory or row-count guards.
* Business Impact: Large catalogs can slow or crash admin pages.
* Technical Impact: Requests can load too many models/IDs into memory.
* Proposed Solution: Keep pagination server-side through `ProductService`, cap or disable `All` above a safe threshold, and replace full select-all with page-level selection or a queued bulk-operation model.
* Files To Change: `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/resources/views/livewire/products/product-table.blade.php`, `Modules/Product/Services/ProductService.php`, related performance tests.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Large datasets do not load all products or IDs into a single request; `All` is guarded; pagination tests cover safe and unsafe sizes.

### P1-12 Move Import/Export To Shared v1.5 Architecture

* Issue: `Modules/Product/Imports/ProductsImport.php`, `Modules/Product/Exports/ProductsExport.php`, and `Modules/Product/Livewire/Products/ProductTable.php` use custom import/export wiring instead of `Modules/Shared/Services/ImportExport`.
* Root Cause: Legacy module-specific Excel logic predates the shared import/export standard.
* Business Impact: Product imports/exports lack consistent validation, reporting, dry-run, storage, and operational safety.
* Technical Impact: Mapping, normalization, file handling, and reports are duplicated or missing.
* Proposed Solution: Add `Modules/Product/Services/ImportExport.php` extending the shared base, use `shared.import-export.panel`, and keep module-specific mapping/validation/export behavior in Product import/export classes only where needed.
* Files To Change: `Modules/Product/Services/ImportExport.php`, `Modules/Product/Imports/ProductsImport.php`, `Modules/Product/Exports/ProductsExport.php`, `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/resources/views/livewire/products/product-table.blade.php`, Product page Blade where the shared panel is mounted.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 3-5 days after import mapping is confirmed.
* Acceptance Criteria: Product import/export uses the shared panel and serviceClass flow; dry-run/reporting works; no Product-specific duplicate of shared normalization/storage/report logic remains.

### P1-13 Add Import Row Validation And Duplicate Policy

* Issue: `Modules/Product/Imports/ProductsImport.php` creates products without row validation, duplicate slug handling, category ID validation, JSON error handling, null-overwrite policy, or transaction protection.
* Root Cause: Import rows are trusted after header transformation.
* Business Impact: A malformed spreadsheet can corrupt product data or partially import broken records.
* Technical Impact: Import failures are hard to diagnose and roll back.
* Proposed Solution: Confirm header mapping, unique key, import mode, null handling, and transaction strategy; then validate normalized rows in the Product import service with structured error reports.
* Files To Change: `Modules/Product/Services/ImportExport.php`, `Modules/Product/Imports/ProductsImport.php`, `Modules/Product/Services/ProductService.php`, related import fixture tests.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days after sample file confirmation.
* Acceptance Criteria: Invalid rows produce row-level errors; duplicate behavior follows a confirmed mode; category IDs and JSON fields are validated; partial writes are controlled by the confirmed transaction strategy.

### P1-14 Stabilize Import Header Mapping

* Issue: `Modules/Product/Imports/ProductsImport.php` relies on transformed Vietnamese headings such as `ten_san_pham`, `album_anh_json`, and `danh_muc_ids`.
* Root Cause: Display labels are doubling as a data contract without documented aliases or templates.
* Business Impact: Small header changes in spreadsheets can break imports.
* Technical Impact: Mapping is fragile and lacks diagnostics.
* Proposed Solution: Define canonical headers, Vietnamese aliases, template export, and required fields in `Modules/Product/Services/ImportExport.php`; require confirmation before implementation if no sample file exists.
* Files To Change: `Modules/Product/Services/ImportExport.php`, `Modules/Product/Imports/ProductsImport.php`, `Modules/Product/Exports/ProductsExport.php`, related import/export tests.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Supported headers and aliases are documented and tested; unknown or ambiguous headers are reported clearly.

### P1-15 Bound Product Export Memory Usage

* Issue: `Modules/Product/Exports/ProductsExport.php` uses `get()` for all products when no IDs are selected.
* Root Cause: Export is collection-based and not chunked or queued.
* Business Impact: Large catalogs can exhaust memory or time out during export.
* Technical Impact: Export work is request-bound and unbounded.
* Proposed Solution: Use the shared export foundation with chunk/lazy iteration or queued export for unsafe dataset sizes; support selected IDs and current filters safely.
* Files To Change: `Modules/Product/Exports/ProductsExport.php`, `Modules/Product/Services/ImportExport.php`, `Modules/Product/Services/ProductService.php`, related export tests.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Exports do not load the full catalog into memory; selected and filtered exports work; large exports are queued or bounded.

### P1-16 Canonicalize Category Model Ownership

* Issue: `Modules/Product/Models/Product.php` imports `Modules\Category\Models\Category`, while `Modules/Product/Livewire/Products/ProductForm.php` and `Modules/Product/Livewire/Products/ProductTable.php` import `Modules\Product\Models\Category`.
* Root Cause: Category behavior is duplicated across modules without a canonical owner decision.
* Business Impact: Product-category assignments can behave inconsistently and future changes may update the wrong model.
* Technical Impact: Module boundaries are unclear and can create duplicate relationship definitions.
* Proposed Solution: Treat Category as the canonical owner for category records, remove Product-local category querying after callers migrate, and access category data through an approved Category service or Product service integration.
* Files To Change: `Modules/Product/Models/Product.php`, `Modules/Product/Models/Category.php`, `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Services/ProductService.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days with cross-module caller checks.
* Acceptance Criteria: Product uses one Category model owner consistently; no Product code imports two different Category classes for the same table.

### P1-17 Canonicalize Wishlist Model Ownership

* Issue: `Modules/Product/Models/Product.php` imports `Modules\Website\Models\Wishlist`, while `Modules/Product/Models/Wishlist.php` also exists.
* Root Cause: Wishlist ownership is duplicated between Product and Website.
* Business Impact: Wishlist features can drift or break when Product relationships are changed.
* Technical Impact: Duplicate model classes for the same concept make relationships and table ownership ambiguous.
* Proposed Solution: Decide whether Wishlist belongs to Product or Website, migrate callers to the canonical model, then remove the duplicate only after references are cleared.
* Files To Change: `Modules/Product/Models/Product.php`, `Modules/Product/Models/Wishlist.php`, Website wishlist callers as separately scoped follow-up, related model tests.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1-2 days after caller inventory.
* Acceptance Criteria: Product wishlist relationship references the canonical model; duplicate Product wishlist file is either used intentionally or marked for removal after migration.

### P1-18 Fix Review Namespace And Ownership

* Issue: `Modules/Product/Models/Review.php` lives under Product but declares namespace `Modules\Post\Models`, while `Modules/Product/Models/Product.php` references `Review::class`.
* Root Cause: Review code was copied or moved without updating namespace and ownership.
* Business Impact: Product reviews may fail to load or use an unintended model.
* Technical Impact: Autoloading and relationship resolution are unreliable.
* Proposed Solution: Decide canonical Review ownership, then correct namespace/imports or move the model; update `Product::reviews()` to reference the canonical Review model explicitly.
* Files To Change: `Modules/Product/Models/Review.php`, `Modules/Product/Models/Product.php`, related model relationship tests.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1 day after ownership confirmation.
* Acceptance Criteria: `Product::reviews()` resolves to the intended Review model; namespace matches file ownership; tests cover product review relationship.

### P1-19 Replace Rating Accessor Query Hotspots

* Issue: `Modules/Product/Models/Product.php` computes average rating and review count by running aggregate queries in accessors.
* Root Cause: Aggregates are model-level convenience methods instead of query-level eager aggregates.
* Business Impact: Product list/detail screens can slow as product count grows.
* Technical Impact: N+1 aggregate queries occur when rating/count are displayed for multiple products.
* Proposed Solution: Use `withAvg()` and `withCount()` in service queries where ratings are needed, and avoid invoking aggregate accessors in loops.
* Files To Change: `Modules/Product/Models/Product.php`, `Modules/Product/Services/ProductService.php`, Product listing/detail views that display ratings if in scope, query-count tests.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Product listing can display rating/count without per-row aggregate queries; query-count tests guard regression.

### P1-20 Remove Category Tree Duplication From Product Layers

* Issue: Category tree/query logic is duplicated across `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, and `Modules/Product/Models/Category.php`.
* Root Cause: UI components each build their own category list instead of using a canonical service/presenter.
* Business Impact: Category display/order can differ between form and table flows.
* Technical Impact: Duplicated recursive/tree logic increases maintenance cost.
* Proposed Solution: Put category option retrieval behind the canonical category owner or `ProductService`, return simple arrays/collections for Livewire, and remove duplicate tree builders after migration.
* Files To Change: `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Models/Category.php`, `Modules/Product/Services/ProductService.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Product form/table use one category option source; category order and filtering are consistent.

### P1-21 Resolve Product Admin UI Duplication

* Issue: Product form/table Blade files under `Modules/Product/resources/views/livewire/products` duplicate Admin views with the same component patterns.
* Root Cause: Product administration UI exists in both domain and Admin presentation areas.
* Business Impact: UI fixes may be made in one place but not the other.
* Technical Impact: Duplicate Livewire Blade increases drift and regression risk.
* Proposed Solution: Decide that Product owns product admin Livewire views while Admin provides layout/shared presentation, or intentionally move product admin UI to Admin and keep Product as domain only. Migrate callers before deleting duplicates.
* Files To Change: `Modules/Product/resources/views/livewire/products/product-form.blade.php`, `Modules/Product/resources/views/livewire/products/product-table.blade.php`, Admin duplicate product views as separately scoped follow-up, `Modules/Product/resources/views/pages/products/*.blade.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1-3 days with caller checks.
* Acceptance Criteria: Only one active Product admin UI implementation remains after migration; route/page wrappers point to the canonical implementation.

### P1-22 Decouple Product Blade From Admin-Owned Components

* Issue: `Modules/Product/resources/views/livewire/products/product-form.blade.php` uses `x-editor`, `x-gallery`, `x-currency-input`, and `x-category-select` from Admin component paths, and `x-image-upload` exists in multiple modules.
* Root Cause: Generic components live in presentation modules instead of a shared component namespace.
* Business Impact: Product UI depends on Admin internals and can break if Admin components change.
* Technical Impact: Cross-module presentation coupling violates clean module boundaries.
* Proposed Solution: Move genuinely reusable components to `Modules/Shared` or document Admin as the presentation shell and reference components through a stable shared namespace.
* Files To Change: `Modules/Product/resources/views/livewire/products/product-form.blade.php`, `Modules/Admin/resources/views/components/editor.blade.php`, `Modules/Admin/resources/views/components/gallery.blade.php`, `Modules/Admin/resources/views/components/currency-input.blade.php`, `Modules/Admin/resources/views/components/category-select.blade.php`, `Modules/Admin/resources/views/components/image-upload.blade.php`, `Modules/Category/resources/views/components/image-upload.blade.php`, new shared component paths if approved.
* Risk Level: Medium.
* Complexity: High.
* Estimated Effort: 2-4 days as a shared UI refactor.
* Acceptance Criteria: Product references stable shared or intentionally documented presentation components; duplicate `image-upload` component ownership is resolved.

### P1-23 Repair Product Migration Hygiene

* Issue: `Modules/Product/database/migrations/-0001_11_30_000015_create_wp_products_table.php` and `Modules/Product/database/migrations/-0001_11_30_000017_create_category_product_table.php` have malformed negative-year filenames.
* Root Cause: Migration files appear generated or imported with invalid timestamps.
* Business Impact: Fresh installs and migration ordering can be unreliable.
* Technical Impact: CI migration smoke tests can fail or run migrations in unexpected order.
* Proposed Solution: Rename migrations to valid deterministic timestamps in a coordinated migration hygiene pass, preserving migration class behavior and production migration history considerations.
* Files To Change: `Modules/Product/database/migrations/-0001_11_30_000015_create_wp_products_table.php`, `Modules/Product/database/migrations/-0001_11_30_000017_create_category_product_table.php`, possible migration status documentation/tests.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1 day plus migration verification.
* Acceptance Criteria: Product migrations have valid names, deterministic order, and fresh migration tests pass.

### P1-24 Investigate Orphan `product_meta`

* Issue: `Modules/Product/database/migrations/2026_05_08_111511_product_meta.php` creates `product_meta`, but no Product model, service, controller, or Livewire code references it.
* Root Cause: Metadata schema was added without an implemented feature or was left behind after refactor.
* Business Impact: Unused schema increases confusion about product configuration ownership.
* Technical Impact: Extra table has no model/service contract and may be dead schema.
* Proposed Solution: Confirm whether `product_meta` is required. If required, add an explicit model/service/use case; if not, mark for removal only through a safe migration plan after data audit.
* Files To Change: `Modules/Product/database/migrations/2026_05_08_111511_product_meta.php`, potential `Modules/Product/Models/ProductMeta.php`, potential `Modules/Product/Services/ProductMetaService.php`, migration/data audit notes.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day analysis; implementation depends on decision.
* Acceptance Criteria: `product_meta` has a documented owner and use case, or a safe removal decision is recorded.

## 4. P2 Nice To Have Improvements

### P2-01 Remove Placeholder Product Views

* Issue: `Modules/Product/resources/views/components/placeholder.blade.php`, `Modules/Product/resources/views/livewire/placeholder.blade.php`, `Modules/Product/resources/views/pages/index.blade.php`, and `Modules/Product/resources/views/product.blade.php` appear scaffold-only or unused.
* Root Cause: Module scaffold artifacts were kept after real product pages were added.
* Business Impact: Developers may mistake placeholders for active Product screens.
* Technical Impact: Dead files make route and view ownership less clear.
* Proposed Solution: Remove placeholders after route boot tests and reference searches confirm they are unused.
* Files To Change: `Modules/Product/resources/views/components/placeholder.blade.php`, `Modules/Product/resources/views/livewire/placeholder.blade.php`, `Modules/Product/resources/views/pages/index.blade.php`, `Modules/Product/resources/views/product.blade.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Placeholder files are gone or documented as intentionally retained; no route/view reference breaks.

### P2-02 Remove Duplicate Page Heading

* Issue: `Modules/Product/resources/views/pages/products/index.blade.php` and `Modules/Product/resources/views/livewire/products/product-table.blade.php` both render product list headings.
* Root Cause: The page shell and Livewire component both own screen-level presentation.
* Business Impact: The admin page can look repetitive or inconsistent.
* Technical Impact: Page-level layout responsibility is blurred.
* Proposed Solution: Let the page Blade own the page heading or let the Livewire component own the feature header, but not both.
* Files To Change: `Modules/Product/resources/views/pages/products/index.blade.php`, `Modules/Product/resources/views/livewire/products/product-table.blade.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Product list screen has one clear heading and no duplicate title block.

### P2-03 Cache Or Reuse Stable Category Options

* Issue: `Modules/Product/Livewire/Products/ProductForm.php` and `Modules/Product/Livewire/Products/ProductTable.php` rebuild category options independently.
* Root Cause: Category option retrieval is repeated in Livewire computed properties.
* Business Impact: Minor admin latency as category data grows.
* Technical Impact: Repeated queries and formatting work across components.
* Proposed Solution: After canonical category ownership is fixed, reuse one service/presenter and consider cache only with explicit invalidation when categories change.
* Files To Change: `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Services/ProductService.php`, possible canonical Category service.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day after P1 category consolidation.
* Acceptance Criteria: Category option generation has one reusable path; cache is added only if invalidation is explicit.

### P2-04 Add Product Architecture Catalog Tests

* Issue: Product lacks route/module architecture tests covering routes, Livewire aliases, service boundaries, models, migrations, and import/export classes.
* Root Cause: The repository currently has minimal automated coverage.
* Business Impact: Regressions in module boot, authorization, and file references may go unnoticed.
* Technical Impact: Missing safety net slows future refactors.
* Proposed Solution: Add lightweight Product module tests/catalog checks after P0/P1 behavior is stable.
* Files To Change: Product-related tests under the repository's chosen test path, `Modules/Product/routes/web.php`, `Modules/Product/routes/api.php`, `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Tests verify Product routes boot, Livewire components resolve, service boundaries are used, and migrations are included in CI.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. P0-01: Disable or implement the undefined API route in `Modules/Product/routes/api.php` and `Modules/Product/Http/Controllers/Api/ProductController.php`.
2. P0-02 and P0-03: Add Livewire action-level authorization in `Modules/Product/Livewire/Products/ProductForm.php` and `Modules/Product/Livewire/Products/ProductTable.php`.
3. P0-04: Protect `Modules/Product/Http/Controllers/ProductCommissionController.php` and its route.
4. P0-05: Define and enforce the rich text safety policy for Product descriptions.
5. P1-02: Clean up misleading controller permission middleware after the real action checks are in place.

### Phase 2: Correctness and Maintainability

1. P1-01: Introduce `Modules/Product/Services/ProductService.php` and migrate queries/writes out of Livewire.
2. P1-04, P1-05, and P1-10: Make save/update/duplicate/category sync transactional and null-safe.
3. P1-06, P1-07, P1-08, and P1-09: Fix sorting, stock field mismatch, validation, and category ID validation.
4. P1-03: Complete or remove the commission page flow.
5. P1-16, P1-17, P1-18, P1-20, and P1-21: Resolve canonical ownership for Category, Wishlist, Review, category tree logic, and duplicated Product/Admin UI.
6. P1-23 and P1-24: Repair migration hygiene and decide `product_meta` ownership.

### Phase 3: Performance and Cleanup

1. P1-11, P1-15, and P1-19: Bound list/select-all/export behavior and remove aggregate query hotspots.
2. P1-12, P1-13, and P1-14: Move Product import/export to the shared v1.5 foundation after mapping, unique key, null handling, and transaction mode are confirmed.
3. P1-22: Move or stabilize shared UI components used by Product.
4. P2-01 and P2-02: Remove placeholders and duplicate heading once tests confirm active routes.
5. P2-03 and P2-04: Add category option reuse/cache where safe and architecture/catalog tests.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/Product/routes/api.php` | Modify or disable route | P0 | Remove undefined public API route or add safe API contract. |
| `Modules/Product/Http/Controllers/Api/ProductController.php` | Modify | P0 | Implement or intentionally leave unused after route removal. |
| `Modules/Product/Livewire/Products/ProductForm.php` | Modify | P0/P1/P2 | Add authorization, validation, service calls, transaction-safe save flow, category option reuse. |
| `Modules/Product/Livewire/Products/ProductTable.php` | Modify | P0/P1/P2 | Add authorization, sort allowlist, bounded pagination/select-all, service calls, import/export migration. |
| `Modules/Product/Http/Controllers/ProductCommissionController.php` | Modify | P0/P1 | Add authorization and fix/remove missing view flow. |
| `Modules/Product/routes/web.php` | Modify | P0/P1 | Protect or remove commission route; keep route definitions thin and explicit. |
| `Modules/Product/config/module.php` | Modify | P0/P1 | Add/align permissions such as import/export or commission management. |
| `Modules/Product/Http/Controllers/ProductController.php` | Modify | P1 | Remove misleading middleware references to nonexistent methods. |
| `Modules/Product/Services/ProductService.php` | Create | P1 | Centralize product queries, writes, transactions, duplication, category sync, and invariants. |
| `Modules/Product/Services/ImportExport.php` | Create | P1 | Adopt shared import/export v1.5 architecture. |
| `Modules/Product/Imports/ProductsImport.php` | Modify or replace | P1 | Add confirmed mapping, validation, duplicate policy, and transaction-safe import behavior. |
| `Modules/Product/Exports/ProductsExport.php` | Modify or replace | P1 | Replace unbounded collection export with shared bounded export flow. |
| `Modules/Product/resources/views/livewire/products/product-form.blade.php` | Modify | P0/P1 | Support validated/sanitized rich text policy and shared component boundary decisions. |
| `Modules/Product/resources/views/livewire/products/product-table.blade.php` | Modify | P1/P2 | Fix `stock_qty`, bounded `All`, import/export UI, and duplicate heading responsibility. |
| `Modules/Product/resources/views/pages/products/index.blade.php` | Modify | P2 | Remove duplicate screen heading or keep as sole page heading. |
| `Modules/Product/resources/views/pages/products/create.blade.php` | Review/possible modify | P1 | Keep page Blade as thin Livewire shell. |
| `Modules/Product/resources/views/pages/products/edit.blade.php` | Review/possible modify | P1 | Keep scalar ID pass-through and thin page shell. |
| `Modules/Product/resources/views/pages/affiliate/product-commissions.blade.php` | Create or avoid | P1 | Required only if commission feature remains enabled. |
| `Modules/Product/Livewire/Products/ProductCommissionForm.php` | Create if needed | P1 | Required only if commission feature remains enabled as Livewire 3 flow. |
| `Modules/Product/Models/Product.php` | Modify | P1 | Canonicalize relationships, remove aggregate query hotspots, align model imports. |
| `Modules/Product/Models/Category.php` | Modify or remove later | P1 | Resolve duplicate Category ownership after caller migration. |
| `Modules/Product/Models/Wishlist.php` | Modify or remove later | P1 | Resolve duplicate Wishlist ownership after caller migration. |
| `Modules/Product/Models/Review.php` | Modify, move, or remove later | P1 | Fix namespace/ownership mismatch. |
| `Modules/Product/database/migrations/-0001_11_30_000015_create_wp_products_table.php` | Rename/review | P1 | Repair malformed migration timestamp and verify fresh install order. |
| `Modules/Product/database/migrations/-0001_11_30_000017_create_category_product_table.php` | Rename/review | P1 | Repair malformed migration timestamp and verify pivot order. |
| `Modules/Product/database/migrations/2026_05_08_111511_product_meta.php` | Review/possibly replace later | P1 | Confirm or remove unused `product_meta` ownership safely. |
| `Modules/Product/resources/views/components/placeholder.blade.php` | Remove after verification | P2 | Scaffold artifact appears unused. |
| `Modules/Product/resources/views/livewire/placeholder.blade.php` | Remove after verification | P2 | Scaffold artifact appears unused. |
| `Modules/Product/resources/views/pages/index.blade.php` | Remove after verification | P2 | Placeholder page appears unused. |
| `Modules/Product/resources/views/product.blade.php` | Remove after verification | P2 | Placeholder page appears unused. |
| `Modules/Admin/resources/views/components/editor.blade.php` | Move or stabilize later | P1 | Product currently depends on Admin-owned generic component. |
| `Modules/Admin/resources/views/components/gallery.blade.php` | Move or stabilize later | P1 | Product currently depends on Admin-owned generic component. |
| `Modules/Admin/resources/views/components/currency-input.blade.php` | Move or stabilize later | P1 | Product currently depends on Admin-owned generic component. |
| `Modules/Admin/resources/views/components/category-select.blade.php` | Move or stabilize later | P1 | Product currently depends on Admin-owned generic component. |
| `Modules/Admin/resources/views/components/image-upload.blade.php` | Move or stabilize later | P1 | Duplicate image upload component ownership. |
| `Modules/Category/resources/views/components/image-upload.blade.php` | Move or stabilize later | P1 | Duplicate image upload component ownership. |
| Product-related tests | Create/modify | P0/P1/P2 | Cover authorization, validation, transactions, imports, exports, query bounds, routes, and architecture. |

## 7. Risk Control

Do not change unrelated modules during the first Product safety pass. `Modules/Admin`, `Modules/Category`, `Modules/Website`, `Modules/Post`, and `Modules/Shared` should only be modified when the specific Product dependency has been confirmed and the caller migration plan is clear.

Do not delete `Modules/Product/Models/Category.php`, `Modules/Product/Models/Wishlist.php`, `Modules/Product/Models/Review.php`, placeholder views, or duplicate Admin/Product UI files until reference searches, route tests, and Livewire alias checks prove they are unused or safely migrated.

Do not implement Product import/export changes until a real or sample spreadsheet, header mapping mode, unique key, import mode, dry-run behavior, null-overwrite policy, transaction strategy, and category mapping rules are confirmed.

Do not rename or remove Product migrations without checking migration history and fresh-install behavior. Migration hygiene should be done in a controlled pass with rollback and production compatibility considered.

Do not introduce DTOs, business logic in Livewire, direct model queries in controllers/Blade/Livewire, Bootstrap, jQuery, or new cross-module duplicates. Product refactoring should follow validated-array input into services, service-owned transactions, clean model relationships, and shared import/export infrastructure.

Do not broaden the scope into full Admin UI redesign or Website product display unless required by a specific Product risk, such as rich text rendering safety or canonical model ownership.
