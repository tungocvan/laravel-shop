# Product Module Analysis

Read after `ROADMAP.md` on 2026-06-17. Scope: `Modules/Product` only. No refactor performed.

## 1. Module purpose

`Modules/Product` is intended to be the domain module for product catalog administration: product CRUD screens, product/category assignment, gallery/tags/prices/status, affiliate commission rate configuration, and Excel import/export.

The module currently looks incomplete as a standalone canonical owner. Several Product routes reference missing views or controller methods, Product UI depends on shared/Admin/Category Blade components, and Product model relationships cross into Category, Website, Post, and app user namespaces.

## 2. Route list

### Web routes

File: `Modules/Product/routes/web.php`

| Method | URI | Name | Target |
|---|---|---|---|
| GET | `/admin/products` | `admin.products.index` | `Modules\Product\Http\Controllers\ProductController@index` |
| GET | `/admin/products/create` | `admin.products.create` | `Modules\Product\Http\Controllers\ProductController@create` |
| GET | `/admin/products/{id}/edit` | `admin.products.edit` | `Modules\Product\Http\Controllers\ProductController@edit` |
| GET | `/admin/products/{productId}/commissions` | `admin.products.commissions` | `Modules\Product\Http\Controllers\ProductCommissionController@index` |

Middleware: `web`, `auth:admin`.

### API routes

File: `Modules/Product/routes/api.php`

| Method | URI | Target |
|---|---|---|
| GET | `/product` | `Modules\Product\Http\Controllers\Api\ProductController@index` |

Issue P0: `Modules/Product/routes/api.php` exposes `GET /product` without auth/rate-limit middleware, while `Modules/Product/Http/Controllers/Api/ProductController.php` does not implement `index()`. This is both an availability bug and an undefined public API surface.

Recommendation P0: Either remove/disable the API route until implemented, or add an explicit read-only API contract, auth/rate limiting as appropriate, and a real `index()` method.

Issue P1: `Modules/Product/routes/web.php` protects only entry routes with `auth:admin`; many mutating operations happen inside Livewire methods, not controller methods.

Recommendation P1: Add capability checks to Livewire actions in `Modules/Product/Livewire/Products/ProductTable.php` and `Modules/Product/Livewire/Products/ProductForm.php`, not only to `Modules/Product/Http/Controllers/ProductController.php`.

## 3. Controllers

File: `Modules/Product/Http/Controllers/ProductController.php`

- `__construct()` applies permission middleware: `view_product`, `create_product`, `edit_product`, `delete_product`.
- `index()` returns `Product::pages.products.index`.
- `create()` returns `Product::pages.products.create`.
- `edit($id)` returns `Product::pages.products.edit`.

Issue P1: Permission middleware references `show`, `store`, `update`, and `destroy` methods that do not exist in `Modules/Product/Http/Controllers/ProductController.php`.

Recommendation P1: Align controller permissions with actual controller actions and move create/update/delete authorization into the Livewire methods that perform the writes.

File: `Modules/Product/Http/Controllers/ProductCommissionController.php`

- `index($productId)` loads `Product::findOrFail($productId)` and returns `Product::pages.affiliate.product-commissions`.

Issue P0: `Modules/Product/Http/Controllers/ProductCommissionController.php` has no authorization middleware or policy check for viewing/editing affiliate commission settings.

Recommendation P0: Require a dedicated permission such as `edit_product` or `manage_product_commission` before loading commission configuration.

Issue P1: `Modules/Product/Http/Controllers/ProductCommissionController.php` returns missing view `Product::pages.affiliate.product-commissions`; no matching file exists under `Modules/Product/resources/views`.

Recommendation P1: Add the missing page or remove the route until the commission page is implemented.

File: `Modules/Product/Http/Controllers/Api/ProductController.php`

- Empty class. No `index()` method.

Issue P0: `Modules/Product/routes/api.php` calls `index()` on an empty controller.

Recommendation P0: Implement or remove `index()` before exposing the API route.

## 4. Page Blade files

Existing files:

- `Modules/Product/resources/views/pages/products/index.blade.php`: Admin layout wrapper; mounts `@livewire('product.products.product-table')`.
- `Modules/Product/resources/views/pages/products/create.blade.php`: Admin layout wrapper; mounts `@livewire('product.products.product-form')`.
- `Modules/Product/resources/views/pages/products/edit.blade.php`: Admin layout wrapper; mounts `@livewire('product.products.product-form', ['id' => $id])`.
- `Modules/Product/resources/views/pages/index.blade.php`: scaffold placeholder.
- `Modules/Product/resources/views/product.blade.php`: scaffold placeholder page.

Issue P1: `Modules/Product/resources/views/pages/index.blade.php` and `Modules/Product/resources/views/product.blade.php` are placeholders and are not referenced by `Modules/Product/routes/web.php`.

Recommendation P2: Remove confirmed unused scaffold placeholder pages after route/module boot tests exist.

Issue P1: `Modules/Product/resources/views/pages/products/index.blade.php` repeats the product list heading while `Modules/Product/resources/views/livewire/products/product-table.blade.php` also renders a product list heading.

Recommendation P2: Keep the page wrapper minimal and let one layer own the screen heading.

## 5. Livewire PHP classes

File: `Modules/Product/Livewire/Products/ProductForm.php`

Public actions/properties:

- `mount($id = null)`
- `getCategoriesProperty()`
- `addTag()`
- `removeTag($index)`
- `removeOldGallery($index)`
- `removeNewGallery($index)`
- `save()`
- `updatedTitle($value)`
- `render()`

Issue P0: `save()` in `Modules/Product/Livewire/Products/ProductForm.php` creates/updates products, stores uploaded files, and syncs categories with no explicit permission check inside the Livewire action.

Recommendation P0: Authorize create/update inside `save()` based on whether `$productId` exists.

Issue P1: `save()` in `Modules/Product/Livewire/Products/ProductForm.php` writes product data and then syncs categories without a database transaction.

Recommendation P1: Wrap product create/update and category sync in a transaction; decide how to handle uploaded files if the database write fails.

Issue P1: `save()` uses `Product::find($this->productId)` and then `$product->update($data)`; a missing product can cause a null method call.

Recommendation P1: Use `findOrFail()` or validated existence handling before update.

File: `Modules/Product/Livewire/Products/ProductTable.php`

Public actions/properties:

- Filtering/sorting: `updatedSearch()`, `updatedCategoryId()`, `updatedPerPage()`, `clearSearch()`, `clearCategory()`, `sortBy($column)`
- Selection: `updatedSelectAll($value)`
- Mutations: `toggleStatus($id)`, `duplicate($id)`, `delete($id)`, `deleteSelected()`, `applyCategories()`, `removeCategory($productId, $categoryId)`
- Import/export: `export()`, `import()`
- Rendering/querying: `render()`

Issue P0: Mutating methods in `Modules/Product/Livewire/Products/ProductTable.php` have no explicit authorization: `toggleStatus()`, `duplicate()`, `delete()`, `deleteSelected()`, `applyCategories()`, `removeCategory()`, and `import()`.

Recommendation P0: Add permission/policy checks to every mutating Livewire action.

Issue P1: `sortBy($column)` accepts a browser-provided column name and passes it to `orderBy()` through `getProductsQuery()`.

Recommendation P1: Restrict sorting to an allowlist of real columns before calling `orderBy()`.

Issue P1: `updatedSelectAll()` uses `pluck('id')` over the full filtered query, and `render()` uses `paginate(999999)` when `$perPage === 'all'`.

Recommendation P1: Remove unbounded "all" loading and replace select-all/export/import workflows with bounded chunks or queued jobs.

## 6. Livewire Blade views

Files:

- `Modules/Product/resources/views/livewire/products/product-form.blade.php`
- `Modules/Product/resources/views/livewire/products/product-table.blade.php`
- `Modules/Product/resources/views/livewire/placeholder.blade.php`

Shared components used by Product Blade:

- `x-editor` from `Modules/Admin/resources/views/components/editor.blade.php`
- `x-gallery` from `Modules/Admin/resources/views/components/gallery.blade.php`
- `x-currency-input` from `Modules/Admin/resources/views/components/currency-input.blade.php`
- `x-category-select` from `Modules/Admin/resources/views/components/category-select.blade.php`
- `x-image-upload` exists in both `Modules/Admin/resources/views/components/image-upload.blade.php` and `Modules/Category/resources/views/components/image-upload.blade.php`

Issue P1: `Modules/Product/resources/views/livewire/products/product-table.blade.php` displays/sorts `stock_qty`, but the Product migration and model use `quantity`, not `stock_qty`.

Recommendation P1: Align the table view and sorting fields with `wp_products.quantity`.

Issue P1: `Modules/Product/resources/views/livewire/products/product-form.blade.php` uses Admin-owned components directly, increasing Product/Admin coupling.

Recommendation P1: Move generic form components to a shared component namespace or document Admin as the presentation owner.

Issue P2: `Modules/Product/resources/views/livewire/placeholder.blade.php` appears to be scaffold-only and is not referenced by active Product routes.

Recommendation P2: Remove after confirming no module discovery fallback depends on it.

## 7. Services and public methods

No service classes exist under `Modules/Product`.

Issue P1: Product creation/update/import/export logic lives directly in Livewire and import/export classes: `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, `Modules/Product/Imports/ProductsImport.php`, and `Modules/Product/Exports/ProductsExport.php`.

Recommendation P1: Introduce a Product application service for product persistence, category sync, duplication, bulk category assignment, and import row handling after authorization/validation boundaries are defined.

## 8. Models and database tables

File: `Modules/Product/Models/Product.php`

- Table: `wp_products`.
- Fillable: title, slug, descriptions, prices, quantity, sold count, image, gallery, tags, status, featured flag, user, views, affiliate commission.
- Relations: `categories()`, `user()`, `reviews()`, `wishlists()`.
- Accessors/scopes: `final_price`, `discount_percent`, `image_url`, `gallery_urls`, `average_rating`, `review_count`, `scopeActive()`.

Issue P1: `Modules/Product/Models/Product.php` imports `Modules\Category\Models\Category`, while `Modules/Product/Livewire/Products/ProductForm.php` and `Modules/Product/Livewire/Products/ProductTable.php` import `Modules\Product\Models\Category`.

Recommendation P1: Choose one canonical Category model owner and update Product relationships/imports consistently.

Issue P1: `Modules/Product/Models/Product.php` imports `Modules\Website\Models\Wishlist`, while `Modules/Product/Models/Wishlist.php` also exists.

Recommendation P1: Choose the canonical Wishlist owner and remove or migrate the duplicate model.

Issue P1: `Modules/Product/Models/Product.php` references `Review::class`, but `Modules/Product/Models/Review.php` declares namespace `Modules\Post\Models`.

Recommendation P1: Fix namespace/model ownership so Product review relationships resolve predictably.

Issue P1: `getAverageRatingAttribute()` and `getReviewCountAttribute()` in `Modules/Product/Models/Product.php` run aggregate queries per product when accessed in lists.

Recommendation P1: Use `withAvg()`/`withCount()` or eager-loaded aggregates in listing/detail queries.

File: `Modules/Product/Models/Category.php`

- No explicit `$table`, so uses `categories`.
- Tree helpers/scopes for taxonomy data.

Issue P1: `Modules/Product/Models/Category.php` duplicates category-domain behavior inside Product.

Recommendation P1: Consolidate category ownership under the canonical Category module per roadmap P1-01/P1-02.

File: `Modules/Product/Models/Review.php`

- Declares namespace `Modules\Post\Models` despite Product path.
- Table: `reviews`.

Issue P1: `Modules/Product/Models/Review.php` is in the wrong namespace for its path and likely cannot satisfy `Modules\Product\Models\Product::reviews()`.

Recommendation P1: Move it to its canonical module or correct namespace and imports.

File: `Modules/Product/Models/Wishlist.php`

- Fillable: `user_id`, `product_id`.
- No explicit `$table`, so uses `wishlists`.

Issue P1: `Modules/Product/Models/Wishlist.php` appears unused by `Modules/Product/Models/Product.php`, which imports `Modules\Website\Models\Wishlist` instead.

Recommendation P1: Remove or canonicalize after checking Website usage.

## 9. Import/Export classes

File: `Modules/Product/Exports/ProductsExport.php`

- `__construct($ids = null)`
- `collection()`
- `headings()`
- `map($product)`

Issue P1: `collection()` uses `get()` for all products when no IDs are selected.

Recommendation P1: Use chunked or queued export for production-sized catalogs.

File: `Modules/Product/Imports/ProductsImport.php`

- `model(array $row)`

Issue P1: `model()` creates products without row validation, duplicate slug handling, category ID validation, JSON error handling, or transaction protection around product create plus category sync.

Recommendation P1: Add row validation, duplicate policy, valid category checks, JSON validation, and transactional row processing.

Issue P1: Import/export headings are Vietnamese display labels; `WithHeadingRow` depends on transformed headings such as `ten_san_pham`, `album_anh_json`, and `danh_muc_ids`.

Recommendation P1: Define a stable import schema with explicit header mapping and fixture tests.

## 10. Authorization/security risks

Issue P0: `Modules/Product/Livewire/Products/ProductTable.php` exposes destructive methods without method-level authorization.

Recommendation P0: Guard `delete()`, `deleteSelected()`, `toggleStatus()`, `duplicate()`, `applyCategories()`, `removeCategory()`, `import()`, and `export()` with policies/permissions.

Issue P0: `Modules/Product/Livewire/Products/ProductForm.php` accepts rich text fields and image paths that are later displayed by product views; validation does not define allowed HTML policy.

Recommendation P0: Sanitize or strictly control rich text output/input policy for `short_description` and `description`.

Issue P1: `Modules/Product/Livewire/Products/ProductTable.php` accepts arbitrary sort columns.

Recommendation P1: Add a sort allowlist.

Issue P1: `Modules/Product/Imports/ProductsImport.php` accepts image paths from spreadsheets and persists them directly.

Recommendation P1: Validate imported image references and prevent untrusted external/internal path injection.

## 11. Validation problems

Issue P1: `Modules/Product/Livewire/Products/ProductForm.php` does not validate `sale_price`, `short_description`, `description`, `is_active`, `category_ids.*`, `gallery`, `tags.*`, or sale price less than regular price.

Recommendation P1: Add complete Livewire validation rules and service-level invariants.

Issue P1: `Modules/Product/Livewire/Products/ProductTable.php` validates `bulkCategoryIds` as an array but does not validate `bulkCategoryIds.*` exists in `categories`.

Recommendation P1: Validate every category ID before `syncWithoutDetaching()`.

Issue P1: `Modules/Product/Imports/ProductsImport.php` reads required columns directly, so malformed rows can produce notices/errors or partial imports.

Recommendation P1: Use `WithValidation`, `SkipsOnFailure`, and an error report contract.

## 12. Transaction risks

Issue P1: `Modules/Product/Livewire/Products/ProductForm.php` updates product data and category pivot rows without a transaction.

Recommendation P1: Wrap product write and category sync in a transaction.

Issue P1: `Modules/Product/Livewire/Products/ProductTable.php` duplicates a product and syncs categories without a transaction.

Recommendation P1: Wrap duplication and category copy in a transaction.

Issue P1: `Modules/Product/Imports/ProductsImport.php` creates a product and then syncs categories without a transaction.

Recommendation P1: Make each row atomic and make bulk import resumable/idempotent.

## 13. N+1/query performance risks

Issue P1: `Modules/Product/Livewire/Products/ProductTable.php` uses `paginate(999999)` for "all" and `updatedSelectAll()` plucks all IDs.

Recommendation P1: Replace with chunked background operations.

Issue P1: `Modules/Product/Models/Product.php` rating/count accessors query the database each time they are accessed.

Recommendation P1: Use eager aggregate loading.

Issue P2: `Modules/Product/Livewire/Products/ProductForm.php` and `Modules/Product/Livewire/Products/ProductTable.php` each rebuild category trees independently.

Recommendation P2: Extract a shared category query/tree presenter or cache stable category lists.

## 14. Duplicate logic

Issue P1: Category tree/query logic is duplicated across `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, and `Modules/Product/Models/Category.php`.

Recommendation P1: Move category tree preparation to the canonical Category service or a shared presenter.

Issue P1: Product form/table Blade files under `Modules/Product/resources/views/livewire/products` are duplicated by Admin views with the same component usage patterns.

Recommendation P1: Decide whether Product or Admin owns product administration UI and remove duplicate implementation after migration.

Issue P1: Wishlist and Review model ownership is duplicated/confused across `Modules/Product/Models/Wishlist.php`, `Modules/Product/Models/Review.php`, and imports in `Modules/Product/Models/Product.php`.

Recommendation P1: Canonicalize models according to module ownership rules.

## 15. Files that look unused

- `Modules/Product/resources/views/components/placeholder.blade.php`: only included by placeholder pages.
- `Modules/Product/resources/views/livewire/placeholder.blade.php`: no active reference found in Product routes.
- `Modules/Product/resources/views/pages/index.blade.php`: no active Product route points to it.
- `Modules/Product/resources/views/product.blade.php`: no active Product route points to it.
- `Modules/Product/Models/Wishlist.php`: Product model imports Website Wishlist instead.
- `Modules/Product/Models/Review.php`: namespace mismatch suggests it is not usable as a Product model.
- `Modules/Product/database/migrations/2026_05_08_111511_product_meta.php`: creates `product_meta`, but no Product model/service/controller references `product_meta`.

Recommendation P2: Remove confirmed unused placeholders and orphaned files only after route/module boot tests and cross-module reference checks.

## 16. Refactor plan

### P0 Critical

- P0: Disable or implement `Modules/Product/routes/api.php` and `Modules/Product/Http/Controllers/Api/ProductController.php` so `/product` is not an undefined public route.
- P0: Add method-level authorization to mutating Livewire methods in `Modules/Product/Livewire/Products/ProductTable.php`.
- P0: Add create/update authorization to `save()` in `Modules/Product/Livewire/Products/ProductForm.php`.
- P0: Add authorization to `Modules/Product/Http/Controllers/ProductCommissionController.php`.
- P0: Define/sanitize rich text policy for `short_description` and `description` handled by `Modules/Product/Livewire/Products/ProductForm.php`.

### P1 Important

- P1: Fix missing commission page referenced by `Modules/Product/Http/Controllers/ProductCommissionController.php`.
- P1: Add sort allowlists and fix `stock_qty` versus `quantity` mismatch in `Modules/Product/Livewire/Products/ProductTable.php` and `Modules/Product/resources/views/livewire/products/product-table.blade.php`.
- P1: Add complete validation rules in `Modules/Product/Livewire/Products/ProductForm.php`, `Modules/Product/Livewire/Products/ProductTable.php`, and `Modules/Product/Imports/ProductsImport.php`.
- P1: Wrap create/update, duplicate, category sync, and import row writes in database transactions.
- P1: Replace unbounded `get()`, `pluck()`, and `paginate(999999)` paths with chunked/queued workflows.
- P1: Canonicalize model ownership for Category, Wishlist, and Review across `Modules/Product/Models/Product.php`, `Modules/Product/Models/Category.php`, `Modules/Product/Models/Wishlist.php`, and `Modules/Product/Models/Review.php`.
- P1: Introduce a Product service layer for product writes, duplication, bulk category updates, and import row processing.
- P1: Repair negative-year migration filenames in `Modules/Product/database/migrations` as part of migration hygiene.

### P2 Nice to have

- P2: Remove scaffold placeholders in `Modules/Product/resources/views/components/placeholder.blade.php`, `Modules/Product/resources/views/livewire/placeholder.blade.php`, `Modules/Product/resources/views/pages/index.blade.php`, and `Modules/Product/resources/views/product.blade.php` after tests confirm they are unused.
- P2: Consolidate duplicated category tree presentation in Product Livewire classes.
- P2: Move generic form components used by Product Blade into a shared component namespace.
- P2: Add module architecture tests/catalog output for Product routes, Livewire aliases, models, migrations, and import/export classes.
