# Order Module Analysis

## 1. Module purpose

`Modules/Order` is a domain module for admin order management: listing orders, viewing order detail, updating order status, deleting eligible orders, printing/downloading invoices, and handling affiliate commission approval/rejection.

The module is currently disabled in `Modules/Order/config/module.php`, but its route files define admin and API routes.

## 2. Route list

### Web routes

File: `Modules/Order/routes/web.php`

| Method | URI | Name | Middleware | Controller |
|---|---|---|---|---|
| GET | `/admin/orders` | `admin.orders.index` | `web`, `auth:admin` | `OrderController@index` |
| GET | `/admin/orders/{id}` | `admin.orders.show` | `web`, `auth:admin` | `OrderController@show` |
| GET | `/admin/orders/{id}/print` | `admin.orders.print` | `web`, `auth:admin` | `OrderController@print` |
| GET | `/admin/orders/{id}/pdf` | `admin.orders.pdf` | `web`, `auth:admin` | `OrderController@exportPdf` |

Notes:

- `Modules/Order/routes/web.php` contains an old commented `/order` route block.
- Routes are guarded only by `auth:admin`; there are no named permissions for viewing orders, exporting invoices, changing status, or deleting records.

### API routes

File: `Modules/Order/routes/api.php`

| Method | URI | Middleware | Controller |
|---|---|---|---|
| GET | `/order` | none in this file | `Api\OrderController@index` |

Notes:

- `Modules/Order/routes/api.php` maps to `index`, but `Modules/Order/Http/Controllers/Api/OrderController.php` does not implement `index`.
- The `auth:sanctum` middleware block is commented out.

## 3. Controllers

File: `Modules/Order/Http/Controllers/OrderController.php`

- `index()`: returns `Order::pages.orders.index`.
- `show($id)`: returns `Order::pages.orders.show` with scalar `$id`.
- `exportPdf($id)`: queries `Order::with('items')->findOrFail($id)`, renders `Order::pages.orders.invoice`, downloads a PDF.
- `print($id)`: queries `Order::with('items')->findOrFail($id)`, renders `Order::pages.orders.invoice`.

File: `Modules/Order/Http/Controllers/Api/OrderController.php`

- Empty controller; no `index()` method exists.

## 4. Page Blade files

- `Modules/Order/resources/views/pages/orders/index.blade.php`: extends `Admin::layouts.master` and mounts `order.orders.order-table`.
- `Modules/Order/resources/views/pages/orders/show.blade.php`: extends `Admin::layouts.master` and mounts `order.orders.order-detail`.
- `Modules/Order/resources/views/pages/orders/invoice.blade.php`: standalone printable/PDF invoice view with inline CSS, hard-coded store identity, inline option decoding, and direct rendering of order/item values.
- `Modules/Order/resources/views/pages/index.blade.php`: placeholder page wrapper; no route references found.
- `Modules/Order/resources/views/order.blade.php`: scaffold placeholder page; no route references found.

## 5. Livewire PHP classes

File: `Modules/Order/Livewire/Orders/OrderTable.php`

- State: `search`, `perPage`, `selected`, `selectAll`, `status`.
- Actions: `setStatus()`, `updatedSearch()`, `updatedStatus()`, `updatedPage()`, `updatedSelectAll()`, `deleteSelected()`, `delete()`.
- Render: builds a direct Eloquent query and paginates it.

File: `Modules/Order/Livewire/Orders/OrderDetail.php`

- State: `orderId`, `order`, `newStatus`, `adminNote`.
- Lifecycle: `mount($id)` loads `Order::with('items.product')->findOrFail($id)`.
- Actions: `updateStatus()`, `deleteOrder()`.
- Render: returns `Order::livewire.orders.order-detail`.

File: `Modules/Order/Livewire/Orders/OrderDetailModal.php`

- State: `order`, `isOpen`, `isRejecting`, `rejectReason`.
- Event listener: `open-order-modal`.
- Actions: `open()`, `close()`, `approve()`, `startReject()`, `cancelReject()`, `confirmReject()`.
- Uses `AdminAffiliateService` through `app()` calls.

## 6. Livewire Blade views

- `Modules/Order/resources/views/livewire/orders/order-table.blade.php`: order list, status tabs, search, per-page selector, checkboxes, bulk delete button, links to detail.
- `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`: detail page, product table, status selector, status update button, history timeline, customer/payment panels, invoice links.
- `Modules/Order/resources/views/livewire/orders/order-detail-modal.blade.php`: affiliate commission modal with approve/reject controls.
- `Modules/Order/resources/views/livewire/placeholder.blade.php`: scaffold placeholder; no references found.

## 7. Services and public methods

File: `Modules/Order/Services/AdminAffiliateService.php`

- `getCommissions(array $filters = [], int $perPage = 10)`: queries `Modules\Order\Models\Order` with affiliate/user eager loading.
- `reject($orderId, $reason)`: rejects pending commission on `Modules\Order\Models\Order`.
- `getOrderDetail($orderId)`: queries `Modules\Website\Models\Order`.
- `approve($orderId)`: approves commission on `Modules\Website\Models\Order` inside a transaction and calls `AffiliateRankService`.

File: `Modules/Order/Services/AffiliateRankService.php`

- Namespace is `Modules\order\Services` with lowercase `order`, which does not match the module namespace standard.
- `checkAndUpdateRank(int $userId)`: loads `App\Models\User`, sums approved affiliate order totals, finds an eligible `AffiliateLevel`, updates `affiliate_level_id`.

## 8. Models and database tables

File: `Modules/Order/Models/Order.php`

- Table: `wp_orders`.
- Fillable: user, affiliate, commission, customer snapshot, totals, payment, status fields.
- Relationships: `items()`, `user()`, `affiliate()`, `histories()`.
- Accessors/helpers: `status_badge`, `payment_method_label`.
- Persistence method: `recalculateTotalCommission()` updates `commission_amount`.

File: `Modules/Order/Models/OrderItem.php`

- Table by convention: `order_items`.
- Fillable: order/product IDs, product snapshot, money, options, commission fields.
- Casts: `options`, `commission_rate`, `commission_amount`.
- Relationships: `order()`, `items()`, `product()`.

File: `Modules/Order/Models/OrderHistory.php`

- Table by convention: `order_histories`.
- Fillable: `order_id`, `user_id`, `action`, `description`.
- Relationships: `order()`, `user()`.

File: `Modules/Order/Models/Product.php`

- Table: `wp_products`.
- This duplicates product ownership from `Modules/Product/Models/Product.php`.
- Contains product catalog relationships/accessors/scopes unrelated to the Order module.

File: `Modules/Order/Models/AffiliateLevel.php`

- Table by convention: `affiliate_levels`.
- No migration for this table exists in `Modules/Order/database/migrations`.

File: `Modules/Order/Models/AffiliateScheme.php`

- Table: `wp_affiliate_schemes`.
- No migration for this table exists in `Modules/Order/database/migrations`.

## 9. Import/Export classes

No files were found under `Modules/Order/Imports` or `Modules/Order/Exports`.

Invoice PDF export is implemented directly in `Modules/Order/Http/Controllers/OrderController.php`, not through the shared import/export foundation.

## 10. Authorization/security risks

- P0: `Modules/Order/routes/web.php` only uses `auth:admin`; order list, detail, invoice print/PDF, status update, deletion, and affiliate commission approval/rejection lack named permission checks.
- P0: `Modules/Order/routes/api.php` exposes `GET /order` without authentication in this route file and points to a missing `index()` method.
- P0: `Modules/Order/Livewire/Orders/OrderTable.php` allows `deleteSelected()` and `delete($id)` with no server-side permission check.
- P0: `Modules/Order/Livewire/Orders/OrderDetail.php` allows `updateStatus()` and `deleteOrder()` with no server-side permission check.
- P0: `Modules/Order/Livewire/Orders/OrderDetailModal.php` allows commission `approve()` and `confirmReject()` with no server-side permission check.
- P0: `Modules/Order/Http/Controllers/OrderController.php` allows invoice print/PDF downloads for any authenticated admin without record-level authorization or named export permission.
- P1: `Modules/Order/Livewire/Orders/OrderDetailModal.php` catches exceptions and dispatches `$e->getMessage()` to the UI, which can expose internal failure details.
- P1: `Modules/Order/resources/views/livewire/orders/order-table.blade.php` renders `{!! $order->status_badge !!}` from a model accessor, creating an avoidable raw HTML rendering pattern.

## 11. Validation problems

- P0: `Modules/Order/Livewire/Orders/OrderDetail.php` updates `newStatus` without a strict validation rule such as `in:pending,processing,shipping,completed,cancelled`.
- P0: `Modules/Order/Livewire/Orders/OrderTable.php` accepts arbitrary `$status` values from `setStatus($status)` and query string state without validation.
- P1: `Modules/Order/Livewire/Orders/OrderTable.php` accepts arbitrary `perPage` values from Livewire state; expected values should be constrained to `10`, `25`, `50`, `100`, and any guarded `All` behavior.
- P1: `Modules/Order/Livewire/Orders/OrderTable.php` does not validate `selected` IDs before using them in destructive operations.
- P1: `Modules/Order/Services/AdminAffiliateService.php` validates commission state in `reject()`/`approve()` but does not validate reason shape at the service boundary.
- P1: `Modules/Order/Http/Controllers/OrderController.php` accepts route `$id` as an untyped value and performs direct lookup rather than using a service method with explicit scalar contract.

## 12. Transaction risks

- P0: `Modules/Order/Livewire/Orders/OrderDetail.php` changes order status and creates an order history in separate writes with no transaction; failure can leave status changed without audit history.
- P0: `Modules/Order/Livewire/Orders/OrderTable.php` bulk deletes orders in a loop without a transaction or audit record.
- P0: `Modules/Order/Livewire/Orders/OrderDetail.php` force deletes an order directly from Livewire with no transaction or audit record.
- P1: `Modules/Order/Services/AdminAffiliateService.php` wraps `approve()` in a transaction, but `reject()` performs a state change without a transaction/audit path.
- P1: `Modules/Order/Models/Order.php` contains `recalculateTotalCommission()`, which sums item commissions and updates the order from the model layer without an explicit service transaction.

## 13. N+1/query performance risks

- P1: `Modules/Order/Livewire/Orders/OrderTable.php` paginates `Order` without `withCount('items')`, while `Modules/Order/resources/views/livewire/orders/order-table.blade.php` reads `$order->items_count`; counts may be missing or trigger incorrect UI data depending on caller state.
- P1: `Modules/Order/Livewire/Orders/OrderDetail.php` eager loads `items.product`, but not `histories`; `Modules/Order/resources/views/livewire/orders/order-detail.blade.php` reads `$order->histories`.
- P1: `Modules/Order/Http/Controllers/OrderController.php` loads `items` for invoice views, but `invoice.blade.php` contains option normalization logic in Blade instead of preparing display-safe data in a service.
- P1: `Modules/Order/Models/Product.php` has `getAverageRatingAttribute()` and `getReviewCountAttribute()` that run aggregate queries per product if used in lists.

## 14. Duplicate logic

- P1: `Modules/Order/Models/Product.php` duplicates product model behavior already owned by `Modules/Product/Models/Product.php`.
- P1: `Modules/Order/Services/AdminAffiliateService.php` and `Modules/Order/Services/AffiliateRankService.php` duplicate affiliate service names also present in `Modules/Admin/Services` and `Modules/Website/Services`.
- P1: `Modules/Order/Livewire/Orders/OrderDetailModal.php` duplicates modal behavior also present in `Modules/Admin/Livewire/Orders/OrderDetailModal.php`.
- P1: `Modules/Order/Services/AdminAffiliateService.php` mixes `Modules\Order\Models\Order` and `Modules\Website\Models\Order`, causing inconsistent canonical model ownership.
- P1: Status labels are duplicated across `Modules/Order/Models/Order.php`, `Modules/Order/resources/views/livewire/orders/order-table.blade.php`, and `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`.
- P2: Invoice product option normalization appears in `Modules/Order/resources/views/pages/orders/invoice.blade.php` even though `OrderItem` already casts `options` to array.

## 15. Files that look unused

- `Modules/Order/resources/views/pages/index.blade.php`: no route/reference found.
- `Modules/Order/resources/views/order.blade.php`: no route/reference found.
- `Modules/Order/resources/views/components/placeholder.blade.php`: only referenced by placeholder pages.
- `Modules/Order/resources/views/livewire/placeholder.blade.php`: no reference found.
- `Modules/Order/Livewire/Orders/OrderDetailModal.php`: no in-module mount/reference found; related events appear only inside this component during local search.
- `Modules/Order/Models/AffiliateLevel.php`: no module migration for `affiliate_levels`.
- `Modules/Order/Models/AffiliateScheme.php`: no module migration for `wp_affiliate_schemes`.

## 16. Refactor plan

### P0 Critical

- P0: Add named permissions and server-side authorization for all admin order routes and Livewire actions in `Modules/Order/routes/web.php`, `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`, and `Modules/Order/Livewire/Orders/OrderDetailModal.php`.
- P0: Protect or remove the unauthenticated API route in `Modules/Order/routes/api.php`, and either implement or delete the missing `index()` endpoint in `Modules/Order/Http/Controllers/Api/OrderController.php`.
- P0: Move status updates, deletes, invoice lookup/export lookup, and order history writes into an Order service under `Modules/Order/Services`.
- P0: Wrap status update plus history creation and destructive deletes in service-level transactions.
- P0: Add strict validation for order status, per-page values, selected IDs, and destructive-action eligibility in `Modules/Order/Livewire/Orders/OrderTable.php` and `Modules/Order/Livewire/Orders/OrderDetail.php`.

### P1 Important

- P1: Define canonical ownership for `Order`, `Product`, affiliate commission, affiliate rank, `AffiliateLevel`, and `AffiliateScheme`; remove cross-use of `Modules\Website\Models\Order` from `Modules/Order/Services/AdminAffiliateService.php`.
- P1: Fix the namespace typo in `Modules/Order/Services/AffiliateRankService.php`.
- P1: Replace direct Eloquent queries in Livewire and controllers with service calls in `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`, and `Modules/Order/Http/Controllers/OrderController.php`.
- P1: Add eager loading/counts for list and detail screens, especially `items_count` and `histories`.
- P1: Move model HTML helpers and persistence logic out of `Modules/Order/Models/Order.php` into view components/service methods.
- P1: Add module tests for route booting, authorization denial, Livewire delete/status actions, service transactions, and invoice access.
- P1: Repair migration hygiene: rename malformed negative-year migrations and confirm ownership/order for `wp_orders`, `order_items`, and `order_histories`.
- P1: Add or relocate migrations for `affiliate_levels` and `wp_affiliate_schemes`, or remove the unused models from this module after ownership is confirmed.

### P2 Nice to have

- P2: Remove confirmed placeholder files in `Modules/Order/resources/views/pages/index.blade.php`, `Modules/Order/resources/views/order.blade.php`, `Modules/Order/resources/views/components/placeholder.blade.php`, and `Modules/Order/resources/views/livewire/placeholder.blade.php`.
- P2: Remove old commented route blocks from `Modules/Order/routes/web.php` and `Modules/Order/routes/api.php`.
- P2: Replace inline invoice CSS and hard-coded store identity in `Modules/Order/resources/views/pages/orders/invoice.blade.php` with a maintainable invoice view/config path.
- P2: Consolidate repeated status labels/badges into one presentation component or helper after service-layer cleanup.
- P2: Add observability/audit events for invoice exports, status changes, deletes, and commission approval/rejection.
