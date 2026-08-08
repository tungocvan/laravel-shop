# Order Rebuild Specification

## 1. Goal

The rebuilt/refactored `Order` module must provide secure, service-driven admin order management for listing orders, viewing details, changing statuses, deleting eligible orders, printing/downloading invoices, and managing affiliate commission decisions.

Design decisions:

- Rebuild around the mandatory Route -> Controller -> Page Blade -> Livewire -> Service -> Model -> Database flow to resolve direct controller/Livewire Eloquent usage. Reference: `ANALYSIS.md` sections 3, 5, 12; `REFACTOR_PLAN.md` P1-01.
- Require named permissions and denial tests for all sensitive routes and mutating Livewire actions. Reference: `ANALYSIS.md` section 10; `REFACTOR_PLAN.md` P0-01 through P0-06.
- Move status transitions, order deletion, invoice lookup, commission actions, and derived commission recalculation into services with transactions where data can become inconsistent. Reference: `ANALYSIS.md` sections 12, 14; `REFACTOR_PLAN.md` P0-09, P0-10, P1-13.
- Preserve current admin order screens while replacing unsafe internals incrementally. Reference: `REFACTOR_PLAN.md` section 7 Risk Control.
- Needs confirmation before coding: whether the `Order` module is the canonical owner of affiliate commission/rank behavior and affiliate tables. Reference: `ANALYSIS.md` sections 8, 14, 15; `REFACTOR_PLAN.md` P1-02, P1-03, P1-15.

## 2. Target Architecture

Target flow:

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

Layer decisions:

- Route: `Modules/Order/routes/web.php` defines admin order URLs, names, middleware, and controller actions only. It must include `web`, `auth:admin`, and named permission middleware where the project convention supports route-level permission checks. Reference: `REFACTOR_PLAN.md` P0-01.
- API Route: `Modules/Order/routes/api.php` must be removed if unused, or protected and implemented if needed. Needs confirmation before coding. Reference: `ANALYSIS.md` section 2; `REFACTOR_PLAN.md` P0-02.
- Controller: `Modules/Order/Http/Controllers/OrderController.php` returns page views or PDF/download responses only, using service-provided data for invoice routes. Reference: `ANALYSIS.md` section 3; `REFACTOR_PLAN.md` P1-01, P1-08.
- Page Blade: `Modules/Order/resources/views/pages/orders/index.blade.php` and `Modules/Order/resources/views/pages/orders/show.blade.php` stay layout shells that mount Livewire components. Reference: `ANALYSIS.md` section 4.
- Livewire PHP: `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`, and any retained commission modal validate UI state, authorize actions, and call services. They do not query models directly. Reference: `ANALYSIS.md` section 5; `REFACTOR_PLAN.md` P1-01.
- Livewire Blade: order views render service-prepared/paginated data and use shared/module components for status badges and dangerous action controls. Reference: `ANALYSIS.md` sections 6, 10, 14; `REFACTOR_PLAN.md` P1-14, P2-04.
- Shared Components: use `Admin::layouts.master`, Tailwind/Admin UI v1.1 components, and a shared/module status badge component. Do not introduce Bootstrap or jQuery in new work. Reference: `docs/CODEX_BOOTSTRAP.md`; `REFACTOR_PLAN.md` P1-14.
- Service: create/complete `Modules/Order/Services/OrderService.php` as the canonical order workflow service. Reference: `REFACTOR_PLAN.md` P1-01.
- Import: no Order import should be implemented until sample files, mapping mode, unique key, null-overwrite, dry-run, and transaction behavior are confirmed. Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` section 7.
- Export: invoice PDF remains a document export path owned by order services; tabular import/export through `Modules/Shared/Services/ImportExport` is out of scope unless confirmed. Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P0-06, section 7.
- Model: models keep ORM configuration, casts, and relationships only. Move HTML and persistence logic out of `Modules/Order/Models/Order.php`. Reference: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P1-13.
- Migration: repair malformed migration names and missing affiliate-table ownership only through a deployment-safe migration task. Reference: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P1-15, P1-16.

## 3. Database Design

### Tables

- `wp_orders`: canonical order header table for customer snapshot, status, money, user, affiliate, and commission fields. Reference: `ANALYSIS.md` section 8.
- `order_items`: order line table for product snapshot, quantity, totals, options, and commission snapshots. Reference: `ANALYSIS.md` section 8.
- `order_histories`: order audit/history table for status and administrative actions. Reference: `ANALYSIS.md` section 8.
- `affiliate_levels`: referenced by `Modules/Order/Models/AffiliateLevel.php`, but ownership/migration location is unresolved. Needs confirmation before coding. Reference: `ANALYSIS.md` sections 8, 15; `REFACTOR_PLAN.md` P1-15.
- `wp_affiliate_schemes`: referenced by `Modules/Order/Models/AffiliateScheme.php`, but ownership/migration location is unresolved. Needs confirmation before coding. Reference: `ANALYSIS.md` sections 8, 15; `REFACTOR_PLAN.md` P1-15.
- `wp_products`: referenced by `order_items.product_id`, but product model ownership should belong to `Modules/Product`. Needs confirmation before coding. Reference: `ANALYSIS.md` section 14; `REFACTOR_PLAN.md` P1-12.

### Columns

`wp_orders` target columns:

- `id`: primary key.
- `user_id`: nullable FK to `users`.
- `affiliate_id`: nullable FK to `users`.
- `commission_status`: enum-like string with allowed values `pending`, `approved`, `rejected`.
- `rejection_reason`: nullable text.
- `commission_amount`: decimal money value.
- `order_code`: unique business order code.
- `customer_name`, `customer_phone`, `customer_email`, `customer_address`: customer snapshot fields.
- `note`: nullable customer/admin note until admin-note separation is confirmed.
- `subtotal`, `shipping_fee`, `discount`, `total`: decimal money fields.
- `payment_method`: enum-like string; current values include `cod`, `bank_transfer`, `vnpay`.
- `status`: enum-like string with allowed values `pending`, `processing`, `shipping`, `completed`, `cancelled`.
- `created_at`, `updated_at`.

Reference: `ANALYSIS.md` sections 8, 11; `REFACTOR_PLAN.md` P0-07.

`order_items` target columns:

- `id`: primary key.
- `order_id`: FK to `wp_orders`.
- `product_id`: nullable FK to `wp_products`, with canonical Product model ownership confirmed before coding.
- `product_name`: product snapshot.
- `price`, `total`: decimal money fields.
- `quantity`: integer.
- `commission_rate`, `commission_amount`, `commission_fixed_amount`: commission snapshot values.
- `affiliate_level_snapshot`: nullable string snapshot.
- `options`: nullable JSON.
- `created_at`, `updated_at`.

Reference: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P1-12, P2-05.

`order_histories` target columns:

- `id`: primary key.
- `order_id`: FK to `wp_orders`.
- `user_id`: nullable actor ID.
- `action`: short action label.
- `description`: nullable details.
- `created_at`, `updated_at`.

Needs confirmation before coding: whether `user_id` should constrain `users`, `admins`, or a guard-specific admin table. Reference: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P0-09, P2-07.

### Indexes

- `wp_orders.order_code`: unique index retained. Reference: `ANALYSIS.md` section 8.
- `wp_orders.status`: index retained for status tabs and filtering. Reference: `ANALYSIS.md` section 13; `REFACTOR_PLAN.md` P1-09.
- `wp_orders.commission_status`: index retained for commission workflows. Reference: `ANALYSIS.md` section 8.
- `wp_orders.created_at`: add/confirm index for default latest ordering. Reference: `ANALYSIS.md` section 13; `REFACTOR_PLAN.md` P1-09.
- `wp_orders.customer_phone`: consider index if phone search is frequent. Needs confirmation before coding. Reference: `ANALYSIS.md` section 13.
- `order_items.order_id`: FK/index retained for detail and item counts. Reference: `REFACTOR_PLAN.md` P1-09.
- `order_items.product_id`: FK/index retained after canonical Product ownership is confirmed. Reference: `REFACTOR_PLAN.md` P1-12.
- `order_histories.order_id`: FK/index retained for detail timeline eager loading. Reference: `REFACTOR_PLAN.md` P1-10.

### Foreign keys

- `wp_orders.user_id` -> `users.id`, nullable with null-on-delete.
- `wp_orders.affiliate_id` -> `users.id`, nullable with null-on-delete.
- `order_items.order_id` -> `wp_orders.id`, cascade-on-delete.
- `order_items.product_id` -> `wp_products.id`, nullable with null-on-delete.
- `order_histories.order_id` -> `wp_orders.id`, cascade-on-delete.
- `order_histories.user_id`: Needs confirmation before coding because current migration has no FK and actor guard ownership is unclear. Reference: `REFACTOR_PLAN.md` P2-07.

### Constraints

- Validate `wp_orders.status` in Livewire and service; database-level enum/check constraint is optional and needs MySQL compatibility confirmation. Reference: `REFACTOR_PLAN.md` P0-07.
- Validate `wp_orders.commission_status` in service; database-level enum/check constraint needs compatibility confirmation. Reference: `REFACTOR_PLAN.md` P0-05.
- Money fields remain decimal; no floats. Reference: `docs/CODEX_BOOTSTRAP.md`; `ANALYSIS.md` section 8.
- `order_code` must remain unique. Reference: `ANALYSIS.md` section 8.

### Migration notes

- Rename malformed negative-year migrations only with a deployment-safe strategy. Reference: `REFACTOR_PLAN.md` P1-16 and Risk Control.
- Add comments to important status, money, JSON, and relationship columns during migration hygiene. Reference: `docs/AI_PROJECT_CONTEXT.md`; `REFACTOR_PLAN.md` P1-16.
- Do not create duplicate affiliate/product tables from Order until canonical ownership is confirmed. Reference: `REFACTOR_PLAN.md` P1-12, P1-15.

## 4. Model Design

### `Modules/Order/Models/Order.php`

- Fillable: `user_id`, `affiliate_id`, `commission_status`, `commission_amount`, `order_code`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `note`, `subtotal`, `shipping_fee`, `discount`, `total`, `payment_method`, `rejection_reason`, `status`.
- Casts: add/confirm decimal casts for money fields and date casts for timestamps where needed. Reference: `ANALYSIS.md` section 8.
- Relationships: `items()`, `user()`, `affiliate()`, `histories()`.
- Scopes: add simple query scopes only if they mirror repeated service filters, such as `status()` or `commissionStatus()`. Needs confirmation before coding. Reference: `REFACTOR_PLAN.md` P1-01.
- Accessors/mutators: remove HTML `status_badge` accessor; keep only non-HTML labels if absolutely needed and safe. Reference: `REFACTOR_PLAN.md` P1-13, P1-14.
- Business logic: move `recalculateTotalCommission()` out to service. Reference: `ANALYSIS.md` section 12; `REFACTOR_PLAN.md` P1-13.

### `Modules/Order/Models/OrderItem.php`

- Fillable: `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total`, `options`, `commission_rate`, `commission_amount`, `commission_fixed_amount`, `affiliate_level_snapshot`.
- Casts: `options` as array; commission and money fields as decimal.
- Relationships: `order()` and `product()`.
- Remove invalid `items()` self-relationship after reference check. Reference: `REFACTOR_PLAN.md` P1-17.
- Product relation should target canonical Product model after ownership confirmation. Reference: `REFACTOR_PLAN.md` P1-12.

### `Modules/Order/Models/OrderHistory.php`

- Fillable: `order_id`, `user_id`, `action`, `description`.
- Casts: timestamps as dates if needed.
- Relationships: `order()` and actor relation.
- Needs confirmation before coding: actor relation should point to the correct admin/user guard model. Reference: `REFACTOR_PLAN.md` P2-07.

### Affiliate models

- `Modules/Order/Models/AffiliateLevel.php` and `Modules/Order/Models/AffiliateScheme.php` are provisional until canonical ownership and migrations are confirmed. Reference: `ANALYSIS.md` sections 8, 15; `REFACTOR_PLAN.md` P1-15.
- Do not expand these models in Order until ownership is confirmed. Reference: `REFACTOR_PLAN.md` Risk Control.

### Product model

- `Modules/Order/Models/Product.php` should not remain canonical product behavior. Migrate to `Modules/Product/Models/Product.php` after reference checks. Reference: `REFACTOR_PLAN.md` P1-12.

## 5. Service Design

### `Modules/Order/Services/OrderService.php`

Responsibilities:

- Build paginated order list queries with search, status filters, sorting, `withCount('items')`, and bounded per-page behavior. Reference: `REFACTOR_PLAN.md` P1-01, P1-09.
- Load order detail with `items.product`, `histories`, and any required actor relation. Reference: `REFACTOR_PLAN.md` P1-10.
- Load invoice data for print/PDF with authorization-compatible lookup and normalized item options. Reference: `REFACTOR_PLAN.md` P0-06, P1-11.
- Update status with allowed status validation, transition rules, and order history creation inside a transaction. Reference: `REFACTOR_PLAN.md` P0-07, P0-09.
- Delete single and selected orders with permission-compatible eligibility checks, transaction handling, and audit/history behavior. Reference: `REFACTOR_PLAN.md` P0-03, P0-04, P0-10.
- Recalculate total commission if this behavior remains in Order. Reference: `REFACTOR_PLAN.md` P1-13.

Public method contract:

- `paginateOrders(array $filters, int|string $perPage)`: returns paginator or safe collection for guarded `All`.
- `getOrderDetail(int $orderId)`: returns an eager-loaded `Order`.
- `getInvoiceData(int $orderId)`: returns order/invoice data suitable for Blade/PDF rendering.
- `updateStatus(int $orderId, string $status, int|string|null $actorId)`: returns updated `Order`.
- `deleteOrder(int $orderId, int|string|null $actorId)`: returns deletion result.
- `deleteOrders(array $orderIds, int|string|null $actorId)`: returns deletion result summary.
- `recalculateTotalCommission(int $orderId)`: returns recalculated decimal amount if retained.

Needs confirmation before coding: exact return shape for delete summaries and invoice data should match existing test expectations once tests are added. Reference: `REFACTOR_PLAN.md` P1-18.

### `Modules/Order/Services/AdminAffiliateService.php`

Responsibilities:

- Provide commission list/detail operations if Order is confirmed as canonical owner.
- Approve commission inside a transaction and call rank recalculation only after state validation.
- Reject commission inside a transaction, persist safe reason, and audit/log the action.
- Use the canonical Order model only; remove `Modules\Website\Models\Order` usage. Reference: `REFACTOR_PLAN.md` P1-02.
- Return safe domain errors; do not expose raw exception messages through Livewire. Reference: `REFACTOR_PLAN.md` P1-06.

Public method contract:

- `getCommissions(array $filters, int $perPage)`.
- `getOrderDetail(int $orderId)`.
- `approve(int $orderId, int|string|null $actorId)`.
- `reject(int $orderId, string $reason, int|string|null $actorId)`.

Needs confirmation before coding: whether this service remains in `Modules/Order` or is migrated from duplicate Admin/Website services. Reference: `REFACTOR_PLAN.md` P1-03.

### `Modules/Order/Services/AffiliateRankService.php`

Responsibilities:

- Recalculate affiliate rank from approved commission revenue if Order owns this behavior.
- Use correct namespace `Modules\Order\Services` if retained. Reference: `REFACTOR_PLAN.md` P1-04.

Needs confirmation before coding: canonical service owner and `users.affiliate_level_id` ownership. Reference: `REFACTOR_PLAN.md` P1-03, P1-15.

### Transaction boundaries

- `updateStatus()` transaction includes order update and history insert. Reference: `REFACTOR_PLAN.md` P0-09.
- `deleteOrder()` and `deleteOrders()` transactions include eligibility checks, audit/history records, and deletes. Reference: `REFACTOR_PLAN.md` P0-10.
- `approve()` transaction includes commission status update and rank recalculation. Reference: `ANALYSIS.md` section 7; `REFACTOR_PLAN.md` P0-05.
- `reject()` transaction includes commission status update, reason, and audit/log. Reference: `REFACTOR_PLAN.md` P1-05.

### Business rules

- Valid order statuses: `pending`, `processing`, `shipping`, `completed`, `cancelled`. Reference: `REFACTOR_PLAN.md` P0-07.
- Valid table filter statuses: valid order statuses plus `all`. Reference: `REFACTOR_PLAN.md` P0-08.
- Deletion eligibility: preserve current behavior for `pending` and `cancelled`; current list UI also selects `completed`, which conflicts with service delete conditions. Needs confirmation before coding. Reference: `ANALYSIS.md` sections 5, 6; `REFACTOR_PLAN.md` P0-03, P0-10.
- Commission approval/rejection only from `pending`. Reference: `ANALYSIS.md` section 7; `REFACTOR_PLAN.md` P0-05.
- Cancelled orders cannot transition back to non-cancelled unless business explicitly approves it. Needs confirmation before coding. Reference: `ANALYSIS.md` section 5; `REFACTOR_PLAN.md` P0-07.

## 6. Livewire Design

### Components

- `Modules/Order/Livewire/Orders/OrderTable.php`: order list, search, filters, pagination, selection, delete actions. Reference: `ANALYSIS.md` section 5.
- `Modules/Order/Livewire/Orders/OrderDetail.php`: detail screen state, status form, delete action, invoice action links. Reference: `ANALYSIS.md` section 5.
- `Modules/Order/Livewire/Orders/OrderDetailModal.php`: commission detail/approve/reject modal only if confirmed used. Needs confirmation before coding. Reference: `ANALYSIS.md` section 15; `REFACTOR_PLAN.md` P2-06.

### State properties

`OrderTable`:

- `search`: string, debounced.
- `status`: string, allowed values only.
- `perPage`: `10`, `25`, `50`, `100`, or guarded `All`.
- `selected`: array of integer IDs.
- `selectAll`: boolean page-level select state.
- Optional `sortField` and `sortDirection`: Needs confirmation before coding because current UI does not expose sorting. Reference: `REFACTOR_PLAN.md` P1-01.

`OrderDetail`:

- `orderId`: integer.
- `order`: service-loaded order.
- `newStatus`: validated status.
- `adminNote`: remove or separate only after admin-note schema is confirmed. Needs confirmation before coding. Reference: `ANALYSIS.md` section 5.

`OrderDetailModal`:

- `order`: service-loaded commission order.
- `isOpen`, `isRejecting`: booleans.
- `rejectReason`: string validated before service call.

### Validation rules

- `status`: allowed order statuses plus `all` for list filters. Reference: `REFACTOR_PLAN.md` P0-08.
- `newStatus`: allowed order statuses only. Reference: `REFACTOR_PLAN.md` P0-07.
- `perPage`: allowed pagination options only. Reference: `REFACTOR_PLAN.md` P1-07.
- `selected`: array of existing order IDs, revalidated in service before delete. Reference: `REFACTOR_PLAN.md` P1-07.
- `rejectReason`: required string, min/max aligned with current UI and service boundary. Reference: `ANALYSIS.md` section 5; `REFACTOR_PLAN.md` P1-05.

### Events

- Keep `open-order-modal` only if `OrderDetailModal` remains used. Needs confirmation before coding. Reference: `REFACTOR_PLAN.md` P2-06.
- Keep `refresh-commission-list` only if a retained commission list listens to it. Needs confirmation before coding. Reference: `ANALYSIS.md` section 5.
- Dispatch safe notification messages; never dispatch raw exception messages. Reference: `REFACTOR_PLAN.md` P1-06.

### Pagination

- Server-side pagination through `OrderService::paginateOrders`.
- Reset page when `search`, `status`, or `perPage` changes.
- `All` must be capped, disabled, or guarded for large datasets. Reference: `docs/AI_PROJECT_CONTEXT.md`; `REFACTOR_PLAN.md` P1-07.

### Search/filter/sort behavior

- Search fields: `order_code`, `customer_name`, `customer_phone`. Reference: `ANALYSIS.md` section 5.
- Filter: `status`.
- Sort: default latest created order. Reference: `ANALYSIS.md` section 5.
- Needs confirmation before coding: additional sort UI or filters such as payment method, date range, commission status.

## 7. Blade/UI Design

### Page Blade files

- Keep `Modules/Order/resources/views/pages/orders/index.blade.php` as the list shell.
- Keep `Modules/Order/resources/views/pages/orders/show.blade.php` as the detail shell.
- Keep/refactor `Modules/Order/resources/views/pages/orders/invoice.blade.php` for print/PDF output, but remove inline data normalization and hard-coded identity after configuration confirmation. Reference: `REFACTOR_PLAN.md` P1-11, P2-03.
- Delete placeholder page files only after confirmation. Reference: `REFACTOR_PLAN.md` P2-01.

### Livewire Blade files

- `Modules/Order/resources/views/livewire/orders/order-table.blade.php`: render filters, search, table, pagination, selection state, and destructive confirmation.
- `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`: render order detail, item table, status form, history, customer/payment panels, invoice links.
- `Modules/Order/resources/views/livewire/orders/order-detail-modal.blade.php`: retain only if commission modal is confirmed used.

### Shared components

- Use a shared/module status badge component instead of raw `{!! $order->status_badge !!}`. Reference: `REFACTOR_PLAN.md` P1-14.
- Use shared confirmation/danger button patterns if available. Reference: `REFACTOR_PLAN.md` P0-10.
- Use shared import/export panel only if Order tabular import/export is confirmed. Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` Risk Control.

### AdminLTE/Bootstrap layout rules

- New/refactored UI must follow `Admin::layouts.master`, Tailwind CSS 4, and Admin UI v1.1.
- Do not introduce Bootstrap, jQuery, or new AdminLTE-specific markup in refactored Order UI unless preserving existing layout compatibility requires it.
- The request mentions AdminLTE/Bootstrap, but project standards supersede it for new work. Reference: `docs/CODEX_BOOTSTRAP.md`; `docs/AI_PROJECT_CONTEXT.md`.

### Table design

- Responsive `overflow-x-auto` table.
- Columns: select checkbox, order code, customer, total, status, date, actions.
- Show `items_count` from service query. Reference: `REFACTOR_PLAN.md` P1-09.
- Empty state and loading states for search/filter/delete.
- Dangerous bulk delete must show confirmation and disabled/loading state. Reference: `REFACTOR_PLAN.md` P0-03, P0-10.

### Form design

- Status update is a small form with validated `newStatus`, disabled/loading save button, and field-level error display.
- Reject commission form validates `rejectReason` with field-level error display.
- Needs confirmation before coding: admin note editing and separate `admin_note` persistence. Reference: `ANALYSIS.md` section 5.

## 8. Import Design

No Order import should be implemented in this rebuild unless explicitly confirmed.

Design decisions:

- If Order import is later required, create `Modules/Order/Services/ImportExport.php` as the module entry point using `Modules/Shared/Services/ImportExport`. Reference: `docs/CODEX_BOOTSTRAP.md`; `ANALYSIS.md` section 9.
- Import classes under `Modules/Order/Import/*` are optional only after sample files prove the service would become too large. Reference: `docs/AI_PROJECT_CONTEXT.md`.
- Header mapping: Needs confirmation before coding from a real/sample Excel file.
- Column mapping: Needs confirmation before coding if headers are unstable.
- Row normalization: must normalize strings, money, dates, booleans, statuses, and JSON options after mapping.
- Row validation: must validate required customer/order fields, allowed statuses, decimal money, quantities, product references, and confirmed unique key.
- Duplicate handling: Needs confirmation before coding; do not assume spreadsheet `id`; likely unique key is `order_code`, but this must be confirmed. Reference: `docs/CODEX_BOOTSTRAP.md`.
- Error reporting: use shared report structure with totals, successes, skipped rows, row-level errors, and debug metadata.
- Destructive modes such as replace/truncate are forbidden without explicit confirmation. Reference: `REFACTOR_PLAN.md` Risk Control.

## 9. Export Design

### Invoice PDF/print export

- `Modules/Order/Http/Controllers/OrderController.php` may return PDF/download responses but must receive data from `OrderService`. Reference: `REFACTOR_PLAN.md` P0-06, P1-11.
- `Modules/Order/resources/views/pages/orders/invoice.blade.php` renders service-normalized invoice data.
- Invoice access requires explicit permission. Reference: `REFACTOR_PLAN.md` P0-06.
- Needs confirmation before coding: configured store identity source for invoice sender details. Reference: `REFACTOR_PLAN.md` P2-03.

### Tabular export

- Needs confirmation before coding: whether Order needs Excel/CSV export beyond invoice PDF.
- If confirmed, implement through `Modules/Order/Services/ImportExport.php` and the shared import/export foundation, not a direct controller export. Reference: `docs/CODEX_BOOTSTRAP.md`; `ANALYSIS.md` section 9.

### Query design

- Export queries must reuse service filters for status, search, selected IDs, and date ranges if added.
- Large exports must use bounded iteration or queues. Reference: `ROADMAP.md` P1-06.

### Export mapping

- Default to safe `$fillable` fields minus sensitive/internal exclusions.
- Exclude internal notes, actor IDs, and audit-only metadata unless explicitly required. Needs confirmation before coding.

### Template generation

- If tabular import/export is confirmed, generate a professional template with canonical headers, sample rows, status/payment value lists, and notes that totals/commissions are system-calculated. Reference: `docs/AI_PROJECT_CONTEXT.md`.

### Large export strategy

- Use queueable/chunked export for large datasets.
- Store generated files through the shared storage foundation and do not expose temporary/private files without authorization. Reference: `docs/CODEX_BOOTSTRAP.md`; `ROADMAP.md` P1-06.

## 10. Permissions and Authorization

### Required permissions

Minimum permissions:

- `orders.view`: access list/detail. Reference: `REFACTOR_PLAN.md` P0-01.
- `orders.export_invoice`: print/download invoice. Reference: `REFACTOR_PLAN.md` P0-06.
- `orders.update_status`: change order status. Reference: `REFACTOR_PLAN.md` P0-04.
- `orders.delete`: single/bulk force delete. Reference: `REFACTOR_PLAN.md` P0-03, P0-10.
- `orders.manage_commission`: approve/reject affiliate commission if commission behavior remains in Order. Reference: `REFACTOR_PLAN.md` P0-05.

Needs confirmation before coding: exact permission naming must match the project permission convention/seeders. Reference: `REFACTOR_PLAN.md` P0-01.

### Policy/Gate checks

- Add route/controller/Livewire boundary checks using project conventions.
- Service methods must re-check business invariants and record eligibility even after authorization.
- Record-level ownership constraints are required if admin roles are scoped to subset ownership. Needs confirmation before coding. Reference: `ROADMAP.md` P0-05.

### Livewire action protection

- Protect `deleteSelected()`, `delete($id)`, `updateStatus()`, `deleteOrder()`, `approve()`, and `confirmReject()`.
- Do not rely on hidden/disabled UI controls or `wire:confirm` as security. Reference: `docs/CODEX_BOOTSTRAP.md`; `REFACTOR_PLAN.md` P0-03 through P0-05.

### Route middleware

- Web routes require `web`, `auth:admin`, and named permission middleware where appropriate.
- API route is removed or protected. Needs confirmation before coding. Reference: `REFACTOR_PLAN.md` P0-02.

## 11. Transactions and Data Integrity

Actions requiring DB transactions:

- Status update plus history creation. Reference: `REFACTOR_PLAN.md` P0-09.
- Single order delete with audit/history. Reference: `REFACTOR_PLAN.md` P0-10.
- Bulk order delete with all-or-nothing semantics unless partial behavior is explicitly confirmed. Reference: `REFACTOR_PLAN.md` P0-10.
- Commission approval plus rank recalculation. Reference: `REFACTOR_PLAN.md` P0-05.
- Commission rejection plus reason/audit. Reference: `REFACTOR_PLAN.md` P1-05.
- Commission total recalculation if retained. Reference: `REFACTOR_PLAN.md` P1-13.

Rollback conditions:

- Invalid permission or unauthorized actor.
- Invalid order status or invalid transition.
- Order not in delete-eligible state.
- Commission not pending for approve/reject.
- History/audit write failure.
- Rank recalculation failure when part of approval transaction.

Idempotency concerns:

- Repeated commission approval should fail safely if already approved. Reference: `ANALYSIS.md` section 7.
- Repeated rejection should fail safely if no longer pending.
- Repeated delete should return a safe not-found/invalid-state response without partial side effects.
- PDF download should be read-only and idempotent after authorization.

Needs confirmation before coding: whether bulk delete is all-or-nothing or partial success with report. The current plan prefers transaction safety. Reference: `REFACTOR_PLAN.md` P0-10.

## 12. Performance Strategy

### Eager loading

- List: load `items_count` through `withCount('items')`. Reference: `REFACTOR_PLAN.md` P1-09.
- Detail: eager load `items.product`, `histories`, and history actor if used. Reference: `REFACTOR_PLAN.md` P1-10.
- Invoice: eager load `items` and only relationships required for invoice display. Reference: `REFACTOR_PLAN.md` P1-11.
- Commission modal/list: eager load `affiliate`, `user`, and `items` only as needed. Reference: `ANALYSIS.md` section 7.

### Query optimization

- Move all search/filter/sort query construction into `OrderService`.
- Index/filter by `status`, `commission_status`, `order_code`, and `created_at`.
- Avoid aggregate accessors on duplicate `Product` model by migrating product ownership. Reference: `ANALYSIS.md` section 13; `REFACTOR_PLAN.md` P1-12.

### Pagination

- Default to 10 rows.
- Support `10`, `25`, `50`, `100`, and guarded `All`.
- Reset pagination on search/status/perPage changes. Reference: `docs/AI_PROJECT_CONTEXT.md`; `REFACTOR_PLAN.md` P1-07.

### Caching

- No caching required for order list/detail initially because data changes frequently.
- Needs confirmation before coding: cache stable status/payment label maps only if they are centralized and invalidation is not needed. Reference: `ROADMAP.md` P2-03.

## 13. Test Strategy

### Route tests

- `Modules/Order/routes/web.php`: route names boot and require `auth:admin` plus permissions. Reference: `REFACTOR_PLAN.md` P0-01.
- `Modules/Order/routes/api.php`: removed route returns not found, or protected route denies unauthenticated users. Reference: `REFACTOR_PLAN.md` P0-02.
- Invoice routes deny unauthorized users and work for authorized users. Reference: `REFACTOR_PLAN.md` P0-06.

### Livewire tests

- `OrderTable`: validates status filter, search, perPage, selected IDs, delete permission, and bulk delete behavior. Reference: `REFACTOR_PLAN.md` P0-03, P0-08, P1-07.
- `OrderDetail`: validates `newStatus`, permission denial, valid transition, invalid transition, and delete behavior. Reference: `REFACTOR_PLAN.md` P0-04, P0-07.
- `OrderDetailModal`: if retained, validates approve/reject permission, reject reason, safe errors, and event behavior. Reference: `REFACTOR_PLAN.md` P0-05, P1-06, P2-06.

### Service tests

- `OrderService`: pagination filters, eager loading/counts, invoice data normalization, status transaction rollback, delete transaction rollback. Reference: `REFACTOR_PLAN.md` P0-09, P0-10, P1-09, P1-11.
- `AdminAffiliateService`: canonical model use, approve/reject state validation, transactions, safe failures. Reference: `REFACTOR_PLAN.md` P1-02, P1-05.
- `AffiliateRankService`: only after ownership confirmed. Reference: `REFACTOR_PLAN.md` P1-03, P1-04.

### Import tests

- No import tests until Order import is confirmed. If confirmed, test header mapping, column mapping, normalization, validation, duplicate handling, dry-run, and rollback. Reference: `ANALYSIS.md` section 9; `docs/CODEX_BOOTSTRAP.md`.

### Export tests

- Invoice PDF/print authorization and data rendering tests.
- If tabular export is confirmed, test filters, selected IDs, mapping, template generation, and large export strategy. Reference: `REFACTOR_PLAN.md` P0-06; `docs/CODEX_BOOTSTRAP.md`.

### Authorization tests

- Denied tests for list/detail/invoice/status/delete/commission actions.
- Allowed tests for each permission.
- Record-level tests if scoped admin ownership is confirmed. Reference: `ROADMAP.md` P0-05.

## 14. Implementation Checklist

### P0

- [ ] Secure or remove `Modules/Order/routes/api.php`; implement or delete `Modules/Order/Http/Controllers/Api/OrderController.php` route behavior. Reference: `REFACTOR_PLAN.md` P0-02.
- [ ] Add named permission checks to `Modules/Order/routes/web.php`, `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`, and `Modules/Order/Livewire/Orders/OrderDetailModal.php`. Reference: `REFACTOR_PLAN.md` P0-01 through P0-06.
- [ ] Create/complete `Modules/Order/Services/OrderService.php` for status, delete, invoice lookup, and list/detail queries. Reference: `REFACTOR_PLAN.md` P1-01.
- [ ] Move status update/history into a service transaction. Reference: `REFACTOR_PLAN.md` P0-09.
- [ ] Move single and bulk delete into service transactions with eligibility checks and audit/history behavior. Reference: `REFACTOR_PLAN.md` P0-10.
- [ ] Validate `newStatus`, list `status`, `perPage`, and `selected` IDs. Reference: `REFACTOR_PLAN.md` P0-07, P0-08, P1-07.
- [ ] Add P0 authorization and transaction regression tests under `tests/Feature/Order` and `tests/Unit/Order`. Reference: `REFACTOR_PLAN.md` P1-18.

### P1

- [ ] Replace direct Eloquent calls in `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, and `Modules/Order/Livewire/Orders/OrderDetail.php` with service calls. Reference: `REFACTOR_PLAN.md` P1-01.
- [ ] Add `withCount('items')` to list query and eager load detail histories. Reference: `REFACTOR_PLAN.md` P1-09, P1-10.
- [ ] Remove model HTML and persistence behavior from `Modules/Order/Models/Order.php`. Reference: `REFACTOR_PLAN.md` P1-13, P1-14.
- [ ] Confirm canonical owner for `Modules/Order/Models/Product.php` and migrate `OrderItem::product()` to canonical Product model. Reference: `REFACTOR_PLAN.md` P1-12.
- [ ] Confirm canonical owner for affiliate services and remove `Modules\Website\Models\Order` usage from `Modules/Order/Services/AdminAffiliateService.php`. Reference: `REFACTOR_PLAN.md` P1-02, P1-03.
- [ ] Fix `Modules/Order/Services/AffiliateRankService.php` namespace if retained. Reference: `REFACTOR_PLAN.md` P1-04.
- [ ] Add transactional rejection/audit behavior in `Modules/Order/Services/AdminAffiliateService.php`. Reference: `REFACTOR_PLAN.md` P1-05.
- [ ] Replace raw exception UI messages with safe domain messages. Reference: `REFACTOR_PLAN.md` P1-06.
- [ ] Confirm affiliate table ownership before adding/moving/removing `AffiliateLevel` and `AffiliateScheme`. Reference: `REFACTOR_PLAN.md` P1-15.
- [ ] Plan migration filename repair with deployment-safe strategy. Reference: `REFACTOR_PLAN.md` P1-16.
- [ ] Remove invalid `OrderItem::items()` after reference check. Reference: `REFACTOR_PLAN.md` P1-17.

### P2

- [ ] Remove confirmed unused placeholder files. Reference: `REFACTOR_PLAN.md` P2-01.
- [ ] Remove obsolete commented route blocks. Reference: `REFACTOR_PLAN.md` P2-02.
- [ ] Move invoice store identity to confirmed config/settings source and reduce inline presentation complexity. Reference: `REFACTOR_PLAN.md` P2-03.
- [ ] Consolidate status labels/badges into a safe component/helper. Reference: `REFACTOR_PLAN.md` P2-04.
- [ ] Remove invoice inline option normalization after service normalization exists. Reference: `REFACTOR_PLAN.md` P2-05.
- [ ] Confirm whether `OrderDetailModal` is used; secure or remove it accordingly. Reference: `REFACTOR_PLAN.md` P2-06.
- [ ] Add structured audit/observability for invoice exports, status changes, deletes, and commission decisions. Reference: `REFACTOR_PLAN.md` P2-07.
