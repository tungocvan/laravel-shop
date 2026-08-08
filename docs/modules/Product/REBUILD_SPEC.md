# Product Rebuild Specification

This specification is for `Modules/Product`. The prompt mentioned the Category module, but this document follows the existing Product analysis and refactor plan: `docs/modules/Product/ANALYSIS.md` and `docs/modules/Product/REFACTOR_PLAN.md`.

No implementation code is included. All uncertain business decisions are marked **Needs confirmation before coding**.

## 1. Goal

Rebuild/refactor the Product module into the canonical product catalog owner for admin product management, product/category assignment, pricing, inventory, gallery/tags, affiliate commission configuration, and safe import/export.

The rebuilt module must:

- Close undefined public route and missing authorization risks. References: `ANALYSIS.md` P0 route/API issues, `REFACTOR_PLAN.md` P0-01 through P0-04.
- Move product business logic, queries, transactions, duplication, category sync, and import/export orchestration out of Livewire into services. References: `ANALYSIS.md` sections 5, 7, 12, 14; `REFACTOR_PLAN.md` P1-01, P1-04, P1-10, P1-12.
- Keep controllers thin and page Blade files as shells. References: `ANALYSIS.md` sections 3-4; `REFACTOR_PLAN.md` P1-02, P1-03.
- Use Livewire 3 only for UI state, validation, action dispatch, pagination state, and service calls. References: `REFACTOR_PLAN.md` P1-01, P1-06, P1-08, P1-11.
- Use the shared import/export foundation instead of Product-specific unbounded Excel logic. References: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P1-12 through P1-15.
- Resolve module boundary drift for Category, Wishlist, Review, Product/Admin UI, and shared components. References: `ANALYSIS.md` sections 8, 14; `REFACTOR_PLAN.md` P1-16 through P1-22.
- Preserve existing behavior only where it is safe, tested, and consistent with Laravel 12, Livewire 3, and the repository standards.

## 2. Target Architecture

Target request flow:

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

### Route

Routes stay in `Modules/Product/routes/web.php` and `Modules/Product/routes/api.php`.

Design decisions:

- Web admin routes remain under `/admin/products` with `web` and `auth:admin`. Reference: `ANALYSIS.md` route list, `REFACTOR_PLAN.md` P0-02/P0-03.
- Product API route in `Modules/Product/routes/api.php` must be disabled until an API contract is confirmed, or implemented as a service-backed read-only endpoint. Reference: `REFACTOR_PLAN.md` P0-01.
- Commission route must be protected by a named permission or removed until implemented. Reference: `REFACTOR_PLAN.md` P0-04 and P1-03.
- **Needs confirmation before coding:** whether public `GET /product` is required, and if required whether it is public, authenticated, rate-limited, paginated, and which fields it returns. Reference: `REFACTOR_PLAN.md` P0-01.

### Controller

Controllers remain thin adapters:

- `Modules/Product/Http/Controllers/ProductController.php`
- `Modules/Product/Http/Controllers/ProductCommissionController.php`
- `Modules/Product/Http/Controllers/Api/ProductController.php`, only if API remains enabled.

Design decisions:

- Controllers return views or redirects and pass scalar IDs only. Reference: `REFACTOR_PLAN.md` P1-02.
- Controllers must not query `Product::findOrFail()` directly after service boundaries are introduced. Reference: `ANALYSIS.md` ProductCommissionController note and `REFACTOR_PLAN.md` P1-01/P1-03.
- Permission middleware in `ProductController` must reference real controller methods only. Reference: `REFACTOR_PLAN.md` P1-02.

### Page Blade

Page Blade files are shells:

- `Modules/Product/resources/views/pages/products/index.blade.php`
- `Modules/Product/resources/views/pages/products/create.blade.php`
- `Modules/Product/resources/views/pages/products/edit.blade.php`
- Optional `Modules/Product/resources/views/pages/affiliate/product-commissions.blade.php` if commission feature remains.

Design decisions:

- Page Blade extends `Admin::layouts.master` and mounts Livewire components only. Reference: `ANALYSIS.md` section 4, `REFACTOR_PLAN.md` P1-03.
- Remove duplicate Product list heading by assigning page heading ownership to either page Blade or Livewire, not both. Reference: `REFACTOR_PLAN.md` P2-02.
- Placeholder pages are not part of target architecture. Reference: `REFACTOR_PLAN.md` P2-01.

### Livewire PHP

Target Livewire components:

- `Modules/Product/Livewire/Products/ProductTable.php`
- `Modules/Product/Livewire/Products/ProductForm.php`
- Optional `Modules/Product/Livewire/Products/ProductCommissionForm.php`

Design decisions:

- Livewire calls services and does not run product queries or transactions. Reference: `ANALYSIS.md` sections 5, 7, 12; `REFACTOR_PLAN.md` P1-01.
- Livewire action authorization is mandatory for every mutating method. Reference: `REFACTOR_PLAN.md` P0-02/P0-03.
- Livewire sorting uses an allowlist. Reference: `REFACTOR_PLAN.md` P1-06.
- `All` pagination is guarded, capped, or disabled for large datasets. Reference: `REFACTOR_PLAN.md` P1-11.

### Livewire Blade

Target files:

- `Modules/Product/resources/views/livewire/products/product-table.blade.php`
- `Modules/Product/resources/views/livewire/products/product-form.blade.php`
- Optional `Modules/Product/resources/views/livewire/products/product-commission-form.blade.php`

Design decisions:

- Table uses real schema field `quantity`, not `stock_qty`. Reference: `ANALYSIS.md` section 6; `REFACTOR_PLAN.md` P1-07.
- Rich text fields must follow the confirmed sanitization/output policy. Reference: `REFACTOR_PLAN.md` P0-05.
- Import/export UI should move to the shared panel where possible. Reference: `REFACTOR_PLAN.md` P1-12.

### Shared Components

Design decisions:

- Reusable UI components should live in `Modules/Shared` or a documented stable presentation namespace. Reference: `REFACTOR_PLAN.md` P1-22.
- Existing Admin-owned components used by Product must not be treated as a long-term Product dependency unless Admin is explicitly documented as the presentation shell. Reference: `ANALYSIS.md` section 6.
- **Needs confirmation before coding:** whether shared components are moved now or only stabilized after P0/P1 Product behavior is fixed. Reference: `REFACTOR_PLAN.md` P1-22.

### Service

Target services:

- `Modules/Product/Services/ProductService.php`
- `Modules/Product/Services/ImportExport.php`
- Optional `Modules/Product/Services/ProductCommissionService.php` if commission behavior grows beyond simple ProductService methods.
- Optional `Modules/Product/Services/ProductMetaService.php` only if `product_meta` is confirmed as active.

Design decisions:

- `ProductService` owns queries, filters, sorting, pagination, CRUD persistence, duplication, deletion, category sync, transactions, and invariants. Reference: `REFACTOR_PLAN.md` P1-01.
- `ImportExport` extends the shared import/export foundation and becomes the module import/export entry point. Reference: `REFACTOR_PLAN.md` P1-12.
- Services accept arrays/scalars, not DTOs. Reference: `docs/AI_PROJECT_CONTEXT.md` and `docs/CODEX_BOOTSTRAP.md`.

### Import

Import must flow through:

```text
shared.import-export.panel
→ Modules\Product\Services\ImportExport
→ Product import mapper/normalizer/validator if needed
→ ProductService persistence
→ Product model/database
```

Design decisions:

- Do not keep direct `Excel::import(new ProductsImport, ...)` in `ProductTable`. Reference: `REFACTOR_PLAN.md` P1-12.
- Import mapping, unique key, mode, null handling, and transaction behavior require confirmation. Reference: `REFACTOR_PLAN.md` P1-13/P1-14.

### Export

Export must flow through:

```text
shared.import-export.panel
→ Modules\Product\Services\ImportExport
→ Product export query/mapper if needed
→ ProductService query
→ bounded export storage/download
```

Design decisions:

- Do not load all products with `get()` for export. Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P1-15.
- Export supports selected IDs and active filters only through bounded service queries. Reference: `REFACTOR_PLAN.md` P1-12/P1-15.

### Model

Target model ownership:

- `Modules/Product/Models/Product.php` remains canonical for `wp_products`.
- Category model ownership should be canonicalized to `Modules/Category\Models\Category`. **Needs confirmation before coding.** Reference: `REFACTOR_PLAN.md` P1-16.
- Wishlist ownership must be confirmed before changing Product relationship. **Needs confirmation before coding.** Reference: `REFACTOR_PLAN.md` P1-17.
- Review ownership must be confirmed before moving or renaming `Modules/Product/Models/Review.php`. **Needs confirmation before coding.** Reference: `REFACTOR_PLAN.md` P1-18.

### Migration

Target migrations:

- `wp_products`
- `category_product`
- `product_meta`, only if confirmed active

Design decisions:

- Negative-year migration filenames must be repaired in a migration hygiene pass. Reference: `REFACTOR_PLAN.md` P1-23.
- `product_meta` must be confirmed as active schema or marked for safe removal. Reference: `REFACTOR_PLAN.md` P1-24.

## 3. Database Design

### Tables

#### `wp_products`

Source migration: `Modules/Product/database/migrations/-0001_11_30_000015_create_wp_products_table.php`.

Columns:

| Column | Target Design | References |
|---|---|---|
| `id` | Primary key. | `ANALYSIS.md` section 8 |
| `title` | Required string, indexed, validated min/max. | `REFACTOR_PLAN.md` P1-08 |
| `slug` | Required unique string, generated from title when absent, unique on create/update. | `REFACTOR_PLAN.md` P1-08/P1-13 |
| `short_description` | Nullable sanitized rich text or escaped text depending on confirmed policy. | `REFACTOR_PLAN.md` P0-05 |
| `description` | Nullable sanitized rich text or escaped text depending on confirmed policy. | `REFACTOR_PLAN.md` P0-05 |
| `regular_price` | Nullable decimal money value; UI/service validates clean numeric value. | `REFACTOR_PLAN.md` P1-08 |
| `sale_price` | Nullable decimal money value; must not exceed `regular_price` unless confirmed. | `REFACTOR_PLAN.md` P1-08 |
| `quantity` | Integer stock quantity; table UI must use this instead of `stock_qty`. | `REFACTOR_PLAN.md` P1-07 |
| `sold_count` | Integer sales counter; service owns future updates. | `ANALYSIS.md` section 8 |
| `image` | Nullable validated local storage path or trusted URL. | `REFACTOR_PLAN.md` P0-05/P1-13 |
| `gallery` | Nullable JSON array of validated image paths/URLs. | `REFACTOR_PLAN.md` P1-08/P1-13 |
| `tags` | Nullable JSON array of validated strings. | `REFACTOR_PLAN.md` P1-08 |
| `is_active` | Boolean, indexed; toggled only through authorized service action. | `REFACTOR_PLAN.md` P0-03/P1-08 |
| `is_featured` | Boolean; validate if exposed in UI/import. | `ANALYSIS.md` section 8 |
| `user_id` | Nullable FK to `users`, null on delete. | `ANALYSIS.md` section 8 |
| `views` | Integer view counter; service owns future updates. | `ANALYSIS.md` section 8 |
| `affiliate_commission_rate` | Nullable decimal percent, 0-100. | `REFACTOR_PLAN.md` P0-04/P1-08 |
| `created_at`, `updated_at` | Timestamps. | `ANALYSIS.md` section 8 |

Indexes:

- `title` index for search. Reference: current migration and `REFACTOR_PLAN.md` P1-01/P1-11.
- `slug` unique. Reference: current migration and `REFACTOR_PLAN.md` P1-08/P1-13.
- `is_active` index for filters. Reference: current migration and `REFACTOR_PLAN.md` P1-01.
- **Needs confirmation before coding:** add or verify indexes for `quantity`, `created_at`, and `affiliate_commission_rate` only if real query patterns need them. Reference: roadmap P1-07.

Foreign keys:

- `user_id` references `users.id` with null-on-delete. Reference: current migration.

Constraints:

- Database unique constraint on `slug`.
- Service-level invariant: `sale_price` should be null or less than/equal to `regular_price`, unless business confirms sale price can exceed regular price. Reference: `REFACTOR_PLAN.md` P1-08.
- Service-level invariant: `affiliate_commission_rate` is null or between 0 and 100. Reference: `ANALYSIS.md` ProductForm rules, `REFACTOR_PLAN.md` P1-08.

#### `category_product`

Source migration: `Modules/Product/database/migrations/-0001_11_30_000017_create_category_product_table.php`.

Columns:

| Column | Target Design | References |
|---|---|---|
| `category_id` | FK to canonical `categories.id`. | `REFACTOR_PLAN.md` P1-16 |
| `product_id` | FK to `wp_products.id`. | `ANALYSIS.md` section 8 |
| `created_at`, `updated_at` | Timestamps. | current migration |

Indexes and constraints:

- Composite primary key: `category_id`, `product_id`.
- Cascade delete from product/category.
- Validate category IDs before sync. Reference: `REFACTOR_PLAN.md` P1-09.

Foreign keys:

- `category_id` references `categories.id`.
- `product_id` references `wp_products.id`.

#### `product_meta`

Source migration: `Modules/Product/database/migrations/2026_05_08_111511_product_meta.php`.

Current columns:

- `id`
- `key`
- `value`
- `group_name`
- `type`
- `label`
- `created_at`
- `updated_at`

Target decision:

- **Needs confirmation before coding:** keep only if Product metadata is an active feature. If kept, add `ProductMeta` model and service. If not, plan safe removal after data audit. Reference: `ANALYSIS.md` unused files section, `REFACTOR_PLAN.md` P1-24.

### Migration Notes

- Rename malformed negative-year Product migrations only in a controlled migration hygiene pass. Reference: `REFACTOR_PLAN.md` P1-23.
- Verify fresh install order: `categories` table must exist before `category_product`; `users` must exist before `wp_products.user_id`. Reference: `REFACTOR_PLAN.md` P1-23 and roadmap P1-08.
- Add comments to important schema fields only during migration hygiene if the project accepts migration changes. Reference: `docs/AI_PROJECT_CONTEXT.md` Database Standard.
- Do not remove `product_meta` without production data review. Reference: `REFACTOR_PLAN.md` P1-24.

## 4. Model Design

### `Modules/Product/Models/Product.php`

Fillable fields:

- `title`
- `slug`
- `short_description`
- `description`
- `regular_price`
- `sale_price`
- `quantity`
- `sold_count`
- `image`
- `gallery`
- `tags`
- `is_active`
- `is_featured`
- `user_id`
- `views`
- `affiliate_commission_rate`

References: `ANALYSIS.md` section 8, `REFACTOR_PLAN.md` P1-08.

Casts:

- `is_active`: boolean
- `is_featured`: boolean, should be added if not present. Reference: `REFACTOR_PLAN.md` P1-08.
- `gallery`: array
- `tags`: array
- `regular_price`: decimal:2
- `sale_price`: decimal:2
- `affiliate_commission_rate`: decimal:2
- `quantity`, `sold_count`, `views`: integer, should be added if not present. Reference: `REFACTOR_PLAN.md` P1-08.

Relationships:

- `categories()`: belongsToMany canonical Category model through `category_product`. **Needs confirmation before coding** on final Category owner. Reference: `REFACTOR_PLAN.md` P1-16.
- `user()`: belongsTo `App\Models\User` or canonical admin/user owner. **Needs confirmation before coding** if user ownership changes. Reference: `ANALYSIS.md` section 8.
- `reviews()`: hasMany canonical Review model. **Needs confirmation before coding** because current `Review.php` namespace is wrong. Reference: `REFACTOR_PLAN.md` P1-18.
- `wishlists()`: hasMany canonical Wishlist model. **Needs confirmation before coding** because Product imports Website Wishlist but also has local Wishlist. Reference: `REFACTOR_PLAN.md` P1-17.

Scopes:

- `scopeActive()` remains.
- Search/filter/sort query scopes are optional; primary query orchestration belongs in `ProductService`. Reference: `REFACTOR_PLAN.md` P1-01.

Accessors / mutators:

- `final_price` and `discount_percent` may remain as simple computed accessors. Reference: `ANALYSIS.md` section 8.
- `image_url` and `gallery_urls` may remain presentation helpers if they do not hide business rules. Reference: `ANALYSIS.md` section 8.
- `average_rating` and `review_count` accessors should not be used in lists; service should use eager aggregate loading. Reference: `REFACTOR_PLAN.md` P1-19.

### `Modules/Product/Models/Category.php`

Target design:

- **Needs confirmation before coding:** remove or stop using after canonical Category owner is confirmed. Reference: `REFACTOR_PLAN.md` P1-16.
- Product should not duplicate category tree behavior long-term. Reference: `REFACTOR_PLAN.md` P1-20.

### `Modules/Product/Models/Review.php`

Target design:

- **Needs confirmation before coding:** either correct namespace to Product or move/delete after deciding Review owner. Reference: `REFACTOR_PLAN.md` P1-18.

### `Modules/Product/Models/Wishlist.php`

Target design:

- **Needs confirmation before coding:** keep only if Product owns Wishlist. Otherwise migrate callers to canonical owner and remove later. Reference: `REFACTOR_PLAN.md` P1-17.

### Optional `Modules/Product/Models/ProductMeta.php`

Target design:

- Create only if `product_meta` is confirmed active. Reference: `REFACTOR_PLAN.md` P1-24.

## 5. Service Design

### `Modules/Product/Services/ProductService.php`

Responsibilities:

- Build product list queries with search/filter/sort/pagination. Reference: `REFACTOR_PLAN.md` P1-01/P1-06/P1-11.
- Enforce sort allowlist. Reference: `REFACTOR_PLAN.md` P1-06.
- Load product form data and category option data through canonical ownership. Reference: `REFACTOR_PLAN.md` P1-16/P1-20.
- Create product with validated arrays. Reference: `REFACTOR_PLAN.md` P1-01/P1-08.
- Update product with validated arrays and safe not-found behavior. Reference: `REFACTOR_PLAN.md` P1-05.
- Sync product categories transactionally. Reference: `REFACTOR_PLAN.md` P1-04/P1-09.
- Duplicate product transactionally with unique slug and category copy. Reference: `REFACTOR_PLAN.md` P1-10.
- Toggle status with permission already checked at Livewire boundary. Reference: `REFACTOR_PLAN.md` P0-03.
- Delete and bulk delete with explicit service methods and transactions where relationships require it. Reference: `REFACTOR_PLAN.md` P0-03/P1-01.
- Provide product export query builders for shared export. Reference: `REFACTOR_PLAN.md` P1-15.
- Persist import rows through one validated path. Reference: `REFACTOR_PLAN.md` P1-13.

Public methods:

- `paginate(array $filters, string $sortColumn, string $sortDirection, int|string $perPage)`
- `findForEdit(int $id)`
- `create(array $data)`
- `update(int $id, array $data)`
- `duplicate(int $id)`
- `delete(int $id)`
- `deleteMany(array $ids)`
- `toggleStatus(int $id)`
- `syncCategories(int $productId, array $categoryIds)`
- `addCategoriesToProducts(array $productIds, array $categoryIds)`
- `removeCategory(int $productId, int $categoryId)`
- `categoryOptions(array $filters = [])`
- `exportQuery(array $filters = [], array $selectedIds = [])`
- `persistImportRow(array $normalizedRow, string $mode)`

Transaction boundaries:

- `create()` and `update()` wrap product write plus category sync. Reference: `REFACTOR_PLAN.md` P1-04.
- `duplicate()` wraps replicate/save/category sync. Reference: `REFACTOR_PLAN.md` P1-10.
- `deleteMany()` wraps bulk delete if side effects or relationship cleanup are required. Reference: `REFACTOR_PLAN.md` P0-03.
- `persistImportRow()` wraps product create/update plus category sync per confirmed import transaction mode. Reference: `REFACTOR_PLAN.md` P1-13.

Business rules:

- Product slug is unique and edit ignores current record. Reference: `REFACTOR_PLAN.md` P1-08.
- Sale price must be a clean decimal and not exceed regular price unless confirmed. Reference: `REFACTOR_PLAN.md` P1-08.
- Category IDs must exist in canonical category table. Reference: `REFACTOR_PLAN.md` P1-09/P1-16.
- Rich text is sanitized or safely escaped according to confirmed policy. Reference: `REFACTOR_PLAN.md` P0-05.
- Import must not overwrite fields with null unless confirmed. Reference: `REFACTOR_PLAN.md` P1-13.
- Replace/destructive import mode is forbidden without explicit confirmation. Reference: `REFACTOR_PLAN.md` P1-13.

### `Modules/Product/Services/ImportExport.php`

Responsibilities:

- Extend shared base import/export service. Reference: `REFACTOR_PLAN.md` P1-12.
- Declare model class, required headers, header aliases, unique keys, row rules, normalization, and export mapping. Reference: `REFACTOR_PLAN.md` P1-13/P1-14.
- Delegate persistence to `ProductService`. Reference: `REFACTOR_PLAN.md` P1-13.
- Use shared reporting/storage/download behavior. Reference: `REFACTOR_PLAN.md` P1-12/P1-15.

Public methods:

- Follow shared base public contract.
- Add Product-specific helpers only if they do not duplicate shared concerns.

### Optional Commission Service

**Needs confirmation before coding:** if product commission becomes more than editing `affiliate_commission_rate`, create `Modules/Product/Services/ProductCommissionService.php`. Reference: `REFACTOR_PLAN.md` P0-04/P1-03.

## 6. Livewire Design

### Component List

- `ProductTable`: list/search/filter/sort/pagination, selection, action dispatch, import/export panel integration.
- `ProductForm`: create/edit product UI state and validation.
- Optional `ProductCommissionForm`: commission configuration if route remains.

### `ProductTable` State Properties

- `search`: string
- `category_id`: nullable scalar
- `perPage`: `10`, `25`, `50`, `100`, or guarded `All`
- `sortColumn`: allowlisted string
- `sortDirection`: `asc` or `desc`
- `selected`: array of current page IDs or selected IDs within safe bounds
- `selectAll`: boolean for current page only unless a queued selection model is confirmed
- Import/export state should move to `shared.import-export.panel`

References: `REFACTOR_PLAN.md` P1-06, P1-11, P1-12.

Validation rules:

- `sortColumn`: in allowlist
- `sortDirection`: in `asc,desc`
- `perPage`: in allowed options and guarded for `All`
- `selected.*`: existing product IDs when used in actions
- `bulkCategoryIds.*`: existing category IDs

References: `REFACTOR_PLAN.md` P1-06/P1-09/P1-11.

Events:

- Use browser/UI events only for user feedback after successful save/delete/import/export where existing app conventions support them.
- **Needs confirmation before coding:** exact event names and toast mechanism. Reference: `REFACTOR_PLAN.md` P1-01.

Pagination:

- Server-side pagination through `ProductService`.
- Reset page when search, category filter, or perPage changes.
- Guard or cap `All`. Reference: `REFACTOR_PLAN.md` P1-11.

Search/filter/sort behavior:

- Search title/slug only unless additional fields are confirmed.
- Category filter uses canonical category relationship.
- Sort only by allowlisted columns such as `title`, `regular_price`, `quantity`, `is_active`, `created_at`.
- **Needs confirmation before coding:** final searchable/sortable field list. Reference: `REFACTOR_PLAN.md` P1-06/P1-07.

### `ProductForm` State Properties

- `productId`
- `title`
- `slug`
- `short_description`
- `description`
- `regular_price`
- `sale_price`
- `is_active`
- `is_featured`, if exposed
- `quantity`, if exposed
- `category_ids`
- `affiliate_commission_rate`
- `newImage`
- `oldImage`
- `gallery`
- `newGallery`
- `tags`
- `tagInput`

References: `ANALYSIS.md` section 5, `REFACTOR_PLAN.md` P1-08.

Validation rules:

- `title`: required string min/max
- `slug`: required string unique on `wp_products.slug`, ignore current product
- `regular_price`: nullable/required per business confirmation, numeric decimal min 0
- `sale_price`: nullable numeric decimal min 0, not greater than regular price unless confirmed
- `quantity`: integer min 0 if exposed
- `is_active`: boolean
- `is_featured`: boolean if exposed
- `category_ids`: array
- `category_ids.*`: exists in canonical categories table
- `newImage`: nullable image with max size
- `newGallery.*`: image with max size
- `tags`: array
- `tags.*`: string max length
- `affiliate_commission_rate`: nullable numeric min 0 max 100
- `short_description` and `description`: length and HTML policy validation

References: `REFACTOR_PLAN.md` P0-05, P1-08, P1-09.

## 7. Blade/UI Design

### Page Blade Files

- `Modules/Product/resources/views/pages/products/index.blade.php`
- `Modules/Product/resources/views/pages/products/create.blade.php`
- `Modules/Product/resources/views/pages/products/edit.blade.php`
- Optional `Modules/Product/resources/views/pages/affiliate/product-commissions.blade.php`

Design decisions:

- Page Blade files extend `Admin::layouts.master` and mount Livewire. Reference: `ANALYSIS.md` section 4.
- No database calls or business logic in Blade. Reference: `REFACTOR_PLAN.md` P1-01.

### Livewire Blade Files

- `Modules/Product/resources/views/livewire/products/product-table.blade.php`
- `Modules/Product/resources/views/livewire/products/product-form.blade.php`
- Optional `Modules/Product/resources/views/livewire/products/product-commission-form.blade.php`

### Shared Components

Current Product dependencies:

- `x-editor`
- `x-gallery`
- `x-currency-input`
- `x-category-select`
- `x-image-upload`

Target design:

- Move/stabilize reusable components under a shared namespace or document Admin as the presentation shell. Reference: `REFACTOR_PLAN.md` P1-22.
- Use `x-select-search` for category selection if it satisfies the relationship selector need. Reference: `docs/AI_PROJECT_CONTEXT.md` Admin UI Standard and `REFACTOR_PLAN.md` P1-20/P1-22.

### AdminLTE/Bootstrap Layout Rules

The user prompt asks for AdminLTE/Bootstrap layout rules, but project standards override that for new work:

- Use `Admin::layouts.master`.
- Use Tailwind CSS 4/Admin UI v1.1 style.
- Do not introduce Bootstrap or jQuery in new Product work.
- Isolate unavoidable legacy compatibility if existing Admin layout still includes AdminLTE/Bootstrap.

References: `docs/AI_PROJECT_CONTEXT.md`, `docs/CODEX_BOOTSTRAP.md`, roadmap stack inventory.

### Table Design

- Server-side pagination with 10/25/50/100 and guarded `All`.
- Search input, category filter, sort controls with allowlisted fields.
- Columns: product image/title, regular/sale price, `quantity`, active status, categories, actions.
- Row actions: duplicate, edit, delete, commission configuration if enabled.
- Bulk actions: delete selected, add categories, export selected if authorized.
- Danger actions require confirmation and disabled/loading states.

References: `REFACTOR_PLAN.md` P0-03, P1-06, P1-07, P1-11, P2-02.

### Form Design

- Product identity section: title, slug, status.
- Content section: short description, description with confirmed sanitization.
- Sales section: regular price, sale price, quantity, affiliate commission.
- Media section: image and gallery uploads.
- Classification section: categories and tags.
- Buttons: save, cancel, disabled/loading states.

References: `REFACTOR_PLAN.md` P0-05, P1-08, P1-22.

## 8. Import Design

### Import Classes

Target:

- `Modules/Product/Services/ImportExport.php`
- Optional Product import helper classes only if service exceeds reasonable size:
  - `Modules/Product/Import/ProductsImport.php`
  - `Modules/Product/Import/RowNormalizer.php`
  - `Modules/Product/Import/RowValidator.php`
  - `Modules/Product/Import/RowMapper.php`

Existing legacy:

- `Modules/Product/Imports/ProductsImport.php`

References: `REFACTOR_PLAN.md` P1-12/P1-13.

### Header Mapping

Known current headers:

- `Tên sản phẩm`
- `Slug`
- `Giá gốc`
- `Giá sale`
- `Mô tả ngắn`
- `Chi tiết`
- `Ảnh đại diện`
- `Album ảnh (JSON)`
- `Tags (JSON)`
- `Danh mục (IDs)`
- `Trạng thái`
- `Ngày tạo`

Target aliases should map to canonical fields:

- `title`
- `slug`
- `regular_price`
- `sale_price`
- `short_description`
- `description`
- `image`
- `gallery`
- `tags`
- `category_ids`
- `is_active`

**Needs confirmation before coding:** exact import template, required headers, optional headers, and whether Vietnamese labels remain canonical. Reference: `REFACTOR_PLAN.md` P1-14.

### Column Mapping

Default target: header-based mapping.

**Needs confirmation before coding:** whether positional A/B/C mapping is required for real Product spreadsheets. Reference: `docs/AI_PROJECT_CONTEXT.md` Import Export Standard and `REFACTOR_PLAN.md` P1-14.

### Row Normalization

- Trim strings.
- Convert empty strings to null where allowed.
- Normalize money to decimal values.
- Normalize booleans for `is_active`.
- Decode/validate `gallery` JSON.
- Decode/validate `tags` JSON.
- Normalize category IDs into an integer array.
- Reject or report invalid image paths/URLs.

References: `REFACTOR_PLAN.md` P1-13/P1-14.

### Row Validation

- Required `title`.
- Unique or duplicate-handled `slug`.
- Numeric money fields.
- Sale price invariant.
- Category IDs exist in canonical categories table.
- Valid JSON arrays for gallery/tags.
- Valid boolean status.
- Safe rich text policy for descriptions.

References: `REFACTOR_PLAN.md` P0-05, P1-08, P1-09, P1-13.

### Duplicate Handling

**Needs confirmation before coding:**

- Unique key: likely `slug`, but must be confirmed.
- Import mode: `create_only`, `update_or_create`, `skip_duplicate`, or explicitly confirmed `replace`.
- Null overwrite behavior.

Reference: `REFACTOR_PLAN.md` P1-13.

### Error Reporting

Use shared import report format:

- total rows
- success rows
- skipped rows
- error rows
- sheet, row, column, value, reason
- debug metadata

References: `docs/AI_PROJECT_CONTEXT.md` Import Export Standard, `REFACTOR_PLAN.md` P1-12/P1-13.

## 9. Export Design

### Export Classes

Target:

- `Modules/Product/Services/ImportExport.php`
- Optional:
  - `Modules/Product/Export/ProductsExport.php`
  - `Modules/Product/Export/ExportQuery.php`
  - `Modules/Product/Export/ExportMapper.php`
  - `Modules/Product/Export/TemplateBuilder.php`

Existing legacy:

- `Modules/Product/Exports/ProductsExport.php`

References: `REFACTOR_PLAN.md` P1-12/P1-15.

### Query Design

- Use `ProductService::exportQuery()` or equivalent.
- Support selected IDs.
- Support active filters where confirmed.
- Eager load categories for category ID export.
- Do not call `get()` on all products for large exports.

References: `ANALYSIS.md` section 9, `REFACTOR_PLAN.md` P1-15.

### Export Mapping

Target fields:

- `id`, if confirmed safe for export
- `title`
- `slug`
- `regular_price`
- `sale_price`
- `short_description`
- `description`
- `image`
- `gallery`
- `tags`
- `category_ids`
- `is_active`
- `created_at`, if useful

**Needs confirmation before coding:** whether `id`, descriptions, image paths, and timestamps should be exported. Reference: `REFACTOR_PLAN.md` P1-12/P1-15.

### Template Generation

Provide an import template with:

- canonical headers
- sample product row
- notes for required fields
- notes for JSON fields
- valid values for booleans
- category ID guidance
- warning that formula/derived values are not imported

References: `docs/AI_PROJECT_CONTEXT.md` Export architecture, `REFACTOR_PLAN.md` P1-14.

### Large Export Strategy

- Chunk or lazy export for medium data.
- Queue export when dataset size exceeds safe request limits.
- Store through shared export storage.
- Provide progress/error reporting if queued.

References: `REFACTOR_PLAN.md` P1-11/P1-15.

## 10. Permissions and Authorization

### Required Permissions

Existing:

- `view_product`
- `create_product`
- `edit_product`
- `delete_product`

Proposed:

- `import_product`, if import should be separate from create/edit.
- `export_product`, if export should be separate from view.
- `manage_product_commission`, if commission management is sensitive beyond edit permission.

**Needs confirmation before coding:** exact permission names and seeding/update strategy. Reference: `REFACTOR_PLAN.md` P0-03/P0-04.

### Policy/Gate Checks

- Use existing permission middleware/check conventions.
- Add policy or gate methods if the project has a standard policy layer.
- Record-level ownership checks are not currently defined for Product. **Needs confirmation before coding** if products can be owned by different admins/users. Reference: roadmap P0-05 and `REFACTOR_PLAN.md` P0-02/P0-03.

### Livewire Action Protection

Required checks:

- `ProductForm::save()`: create or edit permission based on mode.
- `ProductTable::toggleStatus()`: edit permission.
- `ProductTable::duplicate()`: create permission and possibly view original.
- `ProductTable::delete()`: delete permission.
- `ProductTable::deleteSelected()`: delete permission.
- `ProductTable::applyCategories()`: edit permission.
- `ProductTable::removeCategory()`: edit permission.
- Import action/shared panel: import permission.
- Export action/shared panel: export/view permission.

References: `REFACTOR_PLAN.md` P0-02/P0-03.

### Route Middleware

- Admin routes: `web`, `auth:admin`, named permission middleware for page access.
- API route: disabled or explicit API middleware/rate limiting after confirmation.
- Commission route: `auth:admin` plus commission permission.

References: `REFACTOR_PLAN.md` P0-01/P0-04.

## 11. Transactions and Data Integrity

### Actions Requiring DB Transactions

- Product create plus category sync. Reference: `REFACTOR_PLAN.md` P1-04.
- Product update plus category sync. Reference: `REFACTOR_PLAN.md` P1-04.
- Product duplication plus category copy. Reference: `REFACTOR_PLAN.md` P1-10.
- Bulk category assignment. Reference: `REFACTOR_PLAN.md` P1-09.
- Import row create/update plus category sync. Reference: `REFACTOR_PLAN.md` P1-13.
- Bulk delete if related cleanup or audit side effects are added. Reference: `REFACTOR_PLAN.md` P0-03/P1-01.
- Commission update if it affects more than one table. **Needs confirmation before coding.** Reference: `REFACTOR_PLAN.md` P0-04/P1-03.

### Rollback Conditions

- Product validation failure before transaction.
- Product save failure.
- Category sync failure.
- Duplicate slug failure after retry.
- Invalid category ID found during service invariant check.
- Import row validation failure in all-or-nothing mode.
- File persistence cleanup must be defined if DB transaction fails after upload. **Needs confirmation before coding.** Reference: `REFACTOR_PLAN.md` P1-04.

### Idempotency Concerns

- Duplicate product action must generate deterministic unique slug safely under retries. Reference: `REFACTOR_PLAN.md` P1-10.
- Import should use confirmed unique key and mode, not spreadsheet `id` by default. Reference: `REFACTOR_PLAN.md` P1-13.
- Queued export/import jobs need stable job identifiers and retry-safe persistence if implemented. Reference: `REFACTOR_PLAN.md` P1-15.

## 12. Performance Strategy

### Eager Loading

- Product list eager loads categories only when displayed. Reference: `REFACTOR_PLAN.md` P1-01/P1-11.
- Rating/review aggregates use `withAvg()` and `withCount()` only when needed. Reference: `REFACTOR_PLAN.md` P1-19.
- Export eager loads categories for category ID mapping. Reference: `REFACTOR_PLAN.md` P1-15.

### Query Optimization

- Product search/filter/sort/pagination belongs in `ProductService`. Reference: `REFACTOR_PLAN.md` P1-01.
- Sort allowlist prevents invalid columns. Reference: `REFACTOR_PLAN.md` P1-06.
- `quantity` replaces invalid `stock_qty`. Reference: `REFACTOR_PLAN.md` P1-07.
- Avoid query-in-loop category assignment by validating and syncing in service. Reference: `REFACTOR_PLAN.md` P1-09/P1-20.

### Pagination

- Server-side pagination default 10.
- Options: 10, 25, 50, 100, guarded `All`.
- `All` capped/disabled for unsafe dataset sizes. Reference: `REFACTOR_PLAN.md` P1-11.
- Select-all applies to current page unless queued bulk selection is confirmed. Reference: `REFACTOR_PLAN.md` P1-11.

### Caching

- Category option caching is optional after canonical category owner is fixed.
- Cache only with explicit invalidation on category create/update/delete.
- Do not cache to hide inefficient queries.

References: `REFACTOR_PLAN.md` P2-03.

## 13. Test Strategy

### Route Tests

- Admin product index/create/edit require `auth:admin`.
- Required permissions allow/deny page access.
- API `/product` is disabled or returns a defined response.
- Commission route is disabled or protected.

References: `REFACTOR_PLAN.md` P0-01, P0-04, P1-03.

### Livewire Tests

- `ProductForm::save()` create/update allowed and denied.
- Product form validation for title, slug, prices, category IDs, rich text, images, tags, commission rate.
- `ProductTable` mutation actions allowed and denied.
- Sorting rejects invalid columns.
- `All` pagination guard works.
- Bulk category assignment validates category IDs.

References: `REFACTOR_PLAN.md` P0-02/P0-03, P1-06/P1-08/P1-09/P1-11.

### Service Tests

- Product pagination/search/filter/sort.
- Product create/update transaction rollback.
- Duplicate transaction and slug uniqueness.
- Delete/bulk delete behavior.
- Category sync behavior.
- Rating aggregate query behavior if implemented.

References: `REFACTOR_PLAN.md` P1-01, P1-04, P1-10, P1-19.

### Import Tests

- Header aliases and unknown header reporting.
- Row normalization for money, booleans, JSON, category IDs.
- Row validation failures include row/column/value/reason.
- Duplicate modes after confirmation.
- Null-overwrite behavior after confirmation.
- Transaction rollback or partial success behavior after confirmation.

References: `REFACTOR_PLAN.md` P1-12/P1-13/P1-14.

### Export Tests

- Selected ID export.
- Filtered export if enabled.
- Export mapping excludes unconfirmed/sensitive fields.
- Large export uses bounded/queued strategy.
- Template generation includes headers/sample/notes.

References: `REFACTOR_PLAN.md` P1-12/P1-15.

### Authorization Tests

- Denied Livewire actions for create, edit, delete, import, export, commission.
- Authorized users can perform allowed operations.
- Hidden UI state or selected IDs cannot bypass authorization.

References: `REFACTOR_PLAN.md` P0-02/P0-03/P0-04.

## 14. Implementation Checklist

### P0

- [ ] Disable or implement `Modules/Product/routes/api.php` and `Modules/Product/Http/Controllers/Api/ProductController.php`. Reference: `REFACTOR_PLAN.md` P0-01.
- [ ] Add action-level authorization to `Modules/Product/Livewire/Products/ProductForm.php`. Reference: `REFACTOR_PLAN.md` P0-02.
- [ ] Add action-level authorization to `Modules/Product/Livewire/Products/ProductTable.php`. Reference: `REFACTOR_PLAN.md` P0-03.
- [ ] Protect or remove commission route/controller flow. Reference: `REFACTOR_PLAN.md` P0-04/P1-03.
- [ ] Confirm and enforce rich text sanitization/output policy. Reference: `REFACTOR_PLAN.md` P0-05.
- [ ] Add P0 authorization/security tests. Reference: roadmap P0-06 and `REFACTOR_PLAN.md` P0 items.

### P1

- [ ] Create `Modules/Product/Services/ProductService.php` and migrate product queries/writes out of Livewire. Reference: `REFACTOR_PLAN.md` P1-01.
- [ ] Align controller middleware with actual controller actions. Reference: `REFACTOR_PLAN.md` P1-02.
- [ ] Add or remove commission page/Livewire flow. Reference: `REFACTOR_PLAN.md` P1-03.
- [ ] Make product save/update/category sync transactional. Reference: `REFACTOR_PLAN.md` P1-04.
- [ ] Add safe missing-product behavior. Reference: `REFACTOR_PLAN.md` P1-05.
- [ ] Add sort allowlist. Reference: `REFACTOR_PLAN.md` P1-06.
- [ ] Replace `stock_qty` with `quantity`. Reference: `REFACTOR_PLAN.md` P1-07.
- [ ] Complete Product form validation. Reference: `REFACTOR_PLAN.md` P1-08.
- [ ] Validate bulk category IDs. Reference: `REFACTOR_PLAN.md` P1-09.
- [ ] Make product duplication transactional. Reference: `REFACTOR_PLAN.md` P1-10.
- [ ] Guard `All` pagination and select-all. Reference: `REFACTOR_PLAN.md` P1-11.
- [ ] Move import/export to shared v1.5 foundation. Reference: `REFACTOR_PLAN.md` P1-12.
- [ ] Confirm import unique key, mode, null overwrite, and transaction strategy. Reference: `REFACTOR_PLAN.md` P1-13.
- [ ] Stabilize import header mapping/template. Reference: `REFACTOR_PLAN.md` P1-14.
- [ ] Bound export memory usage. Reference: `REFACTOR_PLAN.md` P1-15.
- [ ] Canonicalize Category, Wishlist, and Review ownership. Reference: `REFACTOR_PLAN.md` P1-16/P1-17/P1-18.
- [ ] Replace rating accessor query hotspots with eager aggregates. Reference: `REFACTOR_PLAN.md` P1-19.
- [ ] Consolidate category option/tree logic. Reference: `REFACTOR_PLAN.md` P1-20.
- [ ] Resolve Product/Admin UI duplication and shared component ownership. Reference: `REFACTOR_PLAN.md` P1-21/P1-22.
- [ ] Repair migration hygiene in controlled pass. Reference: `REFACTOR_PLAN.md` P1-23.
- [ ] Confirm `product_meta` ownership. Reference: `REFACTOR_PLAN.md` P1-24.

### P2

- [ ] Remove placeholder Product views after tests confirm no references. Reference: `REFACTOR_PLAN.md` P2-01.
- [ ] Remove duplicate Product list heading. Reference: `REFACTOR_PLAN.md` P2-02.
- [ ] Add category option cache only with explicit invalidation. Reference: `REFACTOR_PLAN.md` P2-03.
- [ ] Add Product architecture/catalog tests. Reference: `REFACTOR_PLAN.md` P2-04.

## Confirmation Gates Before Coding

The following decisions must be confirmed before implementation:

- Whether the public Product API route is required. Reference: `REFACTOR_PLAN.md` P0-01.
- Whether commission configuration remains a Product feature and which permission controls it. Reference: `REFACTOR_PLAN.md` P0-04/P1-03.
- Rich text sanitization/output policy and whether existing website display paths are in scope. Reference: `REFACTOR_PLAN.md` P0-05.
- Canonical ownership for Category, Wishlist, and Review. Reference: `REFACTOR_PLAN.md` P1-16/P1-17/P1-18.
- Import spreadsheet sample, headers, unique key, mode, null overwrite behavior, and transaction strategy. Reference: `REFACTOR_PLAN.md` P1-13/P1-14.
- Whether `product_meta` is active schema or dead schema. Reference: `REFACTOR_PLAN.md` P1-24.
- Whether shared UI component migration is part of Product refactor or a later cross-module task. Reference: `REFACTOR_PLAN.md` P1-22.
