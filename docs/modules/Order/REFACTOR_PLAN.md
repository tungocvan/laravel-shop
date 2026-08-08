# Order Refactor Plan

## 1. Executive Summary

The `Order` module currently handles sensitive admin order workflows, invoice downloads, destructive deletes, status changes, and affiliate commission actions without the permission, validation, service-layer, and transaction boundaries required by the Laravel 12 and Livewire 3 project standards.

The highest-risk work is to add named authorization and record-level checks to `Modules/Order/routes/web.php`, `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`, and `Modules/Order/Livewire/Orders/OrderDetailModal.php`; close or secure the broken public API route in `Modules/Order/routes/api.php`; and move all write operations into service methods with transactions.

The important refactor work is to restore the required Route -> Controller -> Page Blade -> Livewire -> Service -> Model -> Database flow, define canonical ownership for duplicated Order/Product/Affiliate concepts, fix query loading, remove model presentation/business logic, and add regression coverage before deleting duplicate or placeholder files.

## 2. P0 Critical Fixes

### P0-01: Missing named permissions for admin order features

* Issue: `Modules/Order/routes/web.php` uses only `web` and `auth:admin` for order list, detail, print, and PDF routes.
* Root Cause: Authentication was treated as sufficient authorization for sensitive order operations.
* Business Impact: Any authenticated admin may view customer data, print invoices, download invoices, and reach screens that expose destructive actions.
* Technical Impact: Violates roadmap P0-05 and project security rules requiring named permissions and server-side authorization.
* Proposed Solution: Add capability-level permissions for order view, invoice export/print, status management, delete, and affiliate commission management using the existing project permission convention.
* Files To Change: `Modules/Order/routes/web.php`, `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`, `Modules/Order/Livewire/Orders/OrderDetailModal.php`, relevant permission seeder/config file after confirmed ownership.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Unauthorized admins receive denial for every order route and mutating Livewire action; allowed admins retain current access; tests cover allowed and denied paths.

### P0-02: Public and broken API route

* Issue: `Modules/Order/routes/api.php` exposes `GET /order` without authentication in this route file and maps to missing `Api\OrderController@index`.
* Root Cause: Scaffolded API route was left active while the authenticated route block stayed commented.
* Business Impact: Depending on route registration, the endpoint can expose an unauthenticated attack surface or cause runtime failures.
* Technical Impact: Broken route boot/dispatch behavior and security ambiguity.
* Proposed Solution: Remove the API route if unused, or protect it with the correct auth middleware and implement a thin `index()` action backed by an authorized service method.
* Files To Change: `Modules/Order/routes/api.php`, `Modules/Order/Http/Controllers/Api/OrderController.php`, route tests under `tests/Feature/Order`.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.25-0.5 day.
* Acceptance Criteria: No unauthenticated `/order` API access exists; route dispatch does not call a missing method; route tests document the intended behavior.

### P0-03: Destructive Livewire deletes lack authorization

* Issue: `Modules/Order/Livewire/Orders/OrderTable.php` exposes `deleteSelected()` and `delete($id)` without server-side permission checks.
* Root Cause: UI confirmation and status filtering were used instead of permission enforcement.
* Business Impact: Unauthorized admin users can permanently delete eligible orders.
* Technical Impact: Destructive action relies on client state and bypasses service authorization/transaction rules.
* Proposed Solution: Require an order delete permission before deleting and re-check each selected order in the service.
* Files To Change: `Modules/Order/Livewire/Orders/OrderTable.php`, new or existing `Modules/Order/Services/OrderService.php`, tests under `tests/Feature/Order` or `tests/Unit/Order`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Users without delete permission cannot delete single or selected orders; selected IDs are reloaded and validated server-side; denied paths are tested.

### P0-04: Detail status update and delete lack authorization

* Issue: `Modules/Order/Livewire/Orders/OrderDetail.php` exposes `updateStatus()` and `deleteOrder()` without server-side permission checks.
* Root Cause: Mutating detail actions were implemented directly in Livewire.
* Business Impact: Any authenticated admin with page access can change order lifecycle state or permanently delete eligible orders.
* Technical Impact: Violates capability-level authorization and thin Livewire rules.
* Proposed Solution: Gate status changes and delete actions with named permissions, then call service methods that enforce action eligibility.
* Files To Change: `Modules/Order/Livewire/Orders/OrderDetail.php`, new or existing `Modules/Order/Services/OrderService.php`, `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`, tests under `tests/Feature/Order`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Unauthorized users cannot update status or delete from the detail page; authorized behavior remains compatible; tests cover invalid and denied actions.

### P0-05: Affiliate commission actions lack authorization

* Issue: `Modules/Order/Livewire/Orders/OrderDetailModal.php` allows `approve()` and `confirmReject()` without server-side permission checks.
* Root Cause: The modal delegates to a service but does not authorize the current admin before calling it.
* Business Impact: Unauthorized commission approval or rejection can affect partner payouts and accounting.
* Technical Impact: Mutating Livewire action lacks a permission boundary and audit clarity.
* Proposed Solution: Add named commission-management permission checks in the Livewire action boundary and enforce state invariants in `AdminAffiliateService`.
* Files To Change: `Modules/Order/Livewire/Orders/OrderDetailModal.php`, `Modules/Order/Services/AdminAffiliateService.php`, tests under `tests/Feature/Order`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Unauthorized users cannot approve or reject commission; invalid state transitions are denied; tests cover pending, approved, rejected, and unauthorized cases.

### P0-06: Invoice print/PDF access lacks record and export authorization

* Issue: `Modules/Order/Http/Controllers/OrderController.php` lets any authenticated admin print or download invoices.
* Root Cause: `print()` and `exportPdf()` query orders directly without permission or ownership checks.
* Business Impact: Customer personal data and invoice documents can be accessed beyond intended roles.
* Technical Impact: Direct model query in controller and missing export authorization.
* Proposed Solution: Authorize invoice access and use a service method to load invoice data for print/PDF.
* Files To Change: `Modules/Order/Http/Controllers/OrderController.php`, new or existing `Modules/Order/Services/OrderService.php`, `Modules/Order/resources/views/pages/orders/invoice.blade.php`, tests under `tests/Feature/Order`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Invoice print/PDF requires explicit permission; unauthorized access is denied; PDF download still works for authorized users.

### P0-07: Order status values are not strictly validated

* Issue: `Modules/Order/Livewire/Orders/OrderDetail.php` updates `newStatus` without a strict allowed-value rule.
* Root Cause: Status values are controlled by the UI select but trusted in the server action.
* Business Impact: Invalid states can corrupt order workflows and reporting.
* Technical Impact: Database may store undocumented statuses, breaking filters and business rules.
* Proposed Solution: Validate `newStatus` in Livewire and enforce allowed transitions in the service before persistence.
* Files To Change: `Modules/Order/Livewire/Orders/OrderDetail.php`, new or existing `Modules/Order/Services/OrderService.php`, `Modules/Order/Models/Order.php`, tests under `tests/Feature/Order`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Only `pending`, `processing`, `shipping`, `completed`, and `cancelled` are accepted; invalid values fail validation and do not persist.

### P0-08: Order table status filter accepts arbitrary input

* Issue: `Modules/Order/Livewire/Orders/OrderTable.php` accepts arbitrary `$status` from `setStatus($status)` and query string state.
* Root Cause: Filter state is not normalized or validated before query construction.
* Business Impact: Broken filters and unexpected admin views can lead to operational mistakes.
* Technical Impact: Query behavior depends on untrusted Livewire/query-string input.
* Proposed Solution: Validate and normalize status filter input against the same allowed status list plus `all`.
* Files To Change: `Modules/Order/Livewire/Orders/OrderTable.php`, new or existing `Modules/Order/Services/OrderService.php`, tests under `tests/Feature/Order`.
* Risk Level: High.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Unknown statuses reset to `all` or fail validation; query tests prove only allowed filters are used.

### P0-09: Status update plus history write is not transactional

* Issue: `Modules/Order/Livewire/Orders/OrderDetail.php` saves order status and creates history in separate writes.
* Root Cause: Multi-write business workflow lives in Livewire instead of a service transaction.
* Business Impact: A status can change without audit history if the second write fails.
* Technical Impact: Data integrity and audit trails are inconsistent.
* Proposed Solution: Move status transition and history creation into a service transaction.
* Files To Change: `Modules/Order/Livewire/Orders/OrderDetail.php`, new or existing `Modules/Order/Services/OrderService.php`, `Modules/Order/Models/OrderHistory.php`, tests under `tests/Unit/Order`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Status and history commit or roll back together; tests simulate failure and prove rollback.

### P0-10: Bulk and detail deletes are not transactional or audited

* Issue: `Modules/Order/Livewire/Orders/OrderTable.php` and `Modules/Order/Livewire/Orders/OrderDetail.php` call `forceDelete()` directly.
* Root Cause: Destructive persistence is implemented in Livewire without service-level transaction and audit policy.
* Business Impact: Permanent deletion can remove records without consistent audit evidence.
* Technical Impact: Partial bulk deletes can occur if a loop fails midway.
* Proposed Solution: Move single and bulk delete into service methods with transactions, eligibility checks, and audit records according to project convention.
* Files To Change: `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`, new or existing `Modules/Order/Services/OrderService.php`, `Modules/Order/Models/OrderHistory.php`, tests under `tests/Unit/Order`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Bulk delete is all-or-nothing or explicitly documented; audit behavior exists; unauthorized and invalid delete attempts leave data unchanged.

## 3. P1 Important Refactors

### P1-01: Controllers and Livewire bypass the service layer

* Issue: `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, and `Modules/Order/Livewire/Orders/OrderDetail.php` query and mutate Eloquent models directly.
* Root Cause: Order domain service is missing.
* Business Impact: Business rules are scattered and hard to verify.
* Technical Impact: Violates mandatory Route -> Controller -> Page Blade -> Livewire -> Service -> Model flow.
* Proposed Solution: Introduce an `OrderService` as the single entry point for list, detail, invoice lookup, status transitions, and deletion.
* Files To Change: `Modules/Order/Services/OrderService.php`, `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Controllers only return views/download responses with service-provided data; Livewire validates UI state and calls services; no direct `Order::query()` remains in these classes.

### P1-02: Affiliate service mixes Order and Website models

* Issue: `Modules/Order/Services/AdminAffiliateService.php` uses both `Modules\Order\Models\Order` and `Modules\Website\Models\Order`.
* Root Cause: Canonical order ownership is not enforced across modules.
* Business Impact: Commission actions may read/update a different model implementation than the Order admin screens.
* Technical Impact: Duplicate model ownership and inconsistent relationships/casts.
* Proposed Solution: Confirm canonical owner, then make `AdminAffiliateService` use the canonical Order model only; migrate callers before removing duplicates.
* Files To Change: `Modules/Order/Services/AdminAffiliateService.php`, `Modules/Order/Models/Order.php`, `Modules/Website/Models/Order.php`, callers in `Modules/Website/Livewire/Admin/Affiliate/CommissionList.php` and `Modules/Admin/Livewire/Affiliate/CommissionList.php` after scoped confirmation.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days.
* Acceptance Criteria: One canonical Order model/service is documented; affiliate commission reads/writes use the same order table and relationships; affected callers pass tests.

### P1-03: Affiliate service duplication across modules

* Issue: `AdminAffiliateService` and `AffiliateRankService` exist in `Modules/Order`, `Modules/Admin`, and `Modules/Website`.
* Root Cause: Domain logic was copied into presentation and website modules.
* Business Impact: Commission rules can diverge between admin screens.
* Technical Impact: Bugs must be fixed in multiple places and module boundaries are unclear.
* Proposed Solution: Establish the canonical affiliate commission/rank service owner, migrate callers, then remove duplicates only after tests prove no references remain.
* Files To Change: `Modules/Order/Services/AdminAffiliateService.php`, `Modules/Order/Services/AffiliateRankService.php`, `Modules/Admin/Services/AdminAffiliateService.php`, `Modules/Admin/Services/AffiliateRankService.php`, `Modules/Website/Services/AdminAffiliateService.php`, `Modules/Website/Services/AffiliateRankService.php`, duplicate `Modules/Website/Services/Services/*` files after confirmation.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 3-5 days.
* Acceptance Criteria: One canonical commission approval path remains; all callers use it; duplicate removal is backed by search and tests.

### P1-04: Namespace typo in AffiliateRankService

* Issue: `Modules/Order/Services/AffiliateRankService.php` declares `namespace Modules\order\Services`.
* Root Cause: Lowercase module segment was used accidentally.
* Business Impact: Service resolution can fail on case-sensitive filesystems or autoloading paths.
* Technical Impact: Violates required module namespace convention.
* Proposed Solution: Correct namespace after confirming all references and duplicate-service strategy.
* Files To Change: `Modules/Order/Services/AffiliateRankService.php`, `Modules/Order/Services/AdminAffiliateService.php`, tests under `tests/Unit/Order`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Class autoloads through the standard `Modules\Order\Services` namespace; service tests instantiate it successfully.

### P1-05: Commission rejection lacks transaction and audit consistency

* Issue: `Modules/Order/Services/AdminAffiliateService.php` wraps `approve()` in a transaction but not `reject()`.
* Root Cause: Rejection was treated as a single update without an audit policy.
* Business Impact: Rejection state and reason can change without consistent operational logging.
* Technical Impact: Approval/rejection actions have inconsistent integrity guarantees.
* Proposed Solution: Add service-level transaction and audit/event behavior for rejection, matching approval semantics.
* Files To Change: `Modules/Order/Services/AdminAffiliateService.php`, `Modules/Order/Livewire/Orders/OrderDetailModal.php`, audit model/table chosen by project convention, tests under `tests/Unit/Order`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Rejection state, reason, and audit commit atomically; invalid rejection is rejected without data changes.

### P1-06: Raw exception messages are sent to UI

* Issue: `Modules/Order/Livewire/Orders/OrderDetailModal.php` dispatches `$e->getMessage()` to users.
* Root Cause: Domain and system exceptions are not separated.
* Business Impact: Internal error details may leak into admin UI.
* Technical Impact: Violates project error-handling and redaction rules.
* Proposed Solution: Return safe user messages for expected domain failures and log internal exceptions with correlation context.
* Files To Change: `Modules/Order/Livewire/Orders/OrderDetailModal.php`, `Modules/Order/Services/AdminAffiliateService.php`, tests under `tests/Feature/Order`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Expected validation/domain errors show safe messages; unexpected exceptions are logged and return generic UI errors.

### P1-07: Per-page and selected IDs are not validated

* Issue: `Modules/Order/Livewire/Orders/OrderTable.php` accepts arbitrary `perPage` and `selected` values.
* Root Cause: Livewire public state is trusted as if it came from the UI only.
* Business Impact: Admin list performance and delete targeting can be manipulated.
* Technical Impact: Pagination and destructive actions are driven by untrusted state.
* Proposed Solution: Validate `perPage` against allowed options and validate `selected` as existing integer IDs before service calls.
* Files To Change: `Modules/Order/Livewire/Orders/OrderTable.php`, new or existing `Modules/Order/Services/OrderService.php`, tests under `tests/Feature/Order`.
* Risk Level: High.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Invalid `perPage` and selected IDs are rejected or normalized; delete service reloads and verifies records.

### P1-08: Route IDs are untyped and looked up directly

* Issue: `Modules/Order/Http/Controllers/OrderController.php` accepts untyped `$id` and performs direct model lookup for invoice actions.
* Root Cause: Controller is doing data access instead of delegating to service methods with scalar contracts.
* Business Impact: Invalid route input creates inconsistent failure handling.
* Technical Impact: Controller violates thin adapter rule.
* Proposed Solution: Type route parameters where compatible, validate scalar IDs at the boundary, and delegate lookup to `OrderService`.
* Files To Change: `Modules/Order/Http/Controllers/OrderController.php`, new or existing `Modules/Order/Services/OrderService.php`, `Modules/Order/routes/web.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.25-0.5 day.
* Acceptance Criteria: Controller contains no model query; invalid IDs return standard not-found or denied behavior.

### P1-09: List screen missing item count eager loading

* Issue: `Modules/Order/resources/views/livewire/orders/order-table.blade.php` reads `$order->items_count`, while `Modules/Order/Livewire/Orders/OrderTable.php` does not call `withCount('items')`.
* Root Cause: Query construction lives beside UI state and missed view data requirements.
* Business Impact: Admin list can show missing or incorrect item counts.
* Technical Impact: Potential performance and correctness regression.
* Proposed Solution: Move list query to service and include `withCount('items')` in the paginated query.
* Files To Change: `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/resources/views/livewire/orders/order-table.blade.php`, new or existing `Modules/Order/Services/OrderService.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Every listed order has correct `items_count`; query count remains bounded.

### P1-10: Detail screen does not eager load histories

* Issue: `Modules/Order/Livewire/Orders/OrderDetail.php` loads `items.product`, while `Modules/Order/resources/views/livewire/orders/order-detail.blade.php` reads `$order->histories`.
* Root Cause: Detail query does not fully match view data needs.
* Business Impact: Order history timeline can trigger extra queries and slow detail pages.
* Technical Impact: N+1 risk in relationship-heavy screen.
* Proposed Solution: Load `histories` and any required history user relationship in the service detail query.
* Files To Change: `Modules/Order/Livewire/Orders/OrderDetail.php`, `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`, new or existing `Modules/Order/Services/OrderService.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Detail screen uses a bounded query plan; histories render without lazy-loading surprises.

### P1-11: Invoice Blade contains normalization/business formatting logic

* Issue: `Modules/Order/resources/views/pages/orders/invoice.blade.php` decodes and normalizes item options inline.
* Root Cause: Display-ready invoice data is not prepared by a service/view model array.
* Business Impact: Invoice rendering can diverge from admin detail rendering.
* Technical Impact: Blade owns data transformation and inline CSS/presentation logic.
* Proposed Solution: Prepare normalized invoice data in `OrderService`; keep Blade focused on rendering.
* Files To Change: `Modules/Order/resources/views/pages/orders/invoice.blade.php`, `Modules/Order/Http/Controllers/OrderController.php`, new or existing `Modules/Order/Services/OrderService.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Invoice Blade has no inline option decoding; print and PDF render the same normalized data.

### P1-12: Product model duplicated inside Order

* Issue: `Modules/Order/Models/Product.php` duplicates product catalog behavior owned by `Modules/Product/Models/Product.php`.
* Root Cause: Order item product relationship points at an Order-local product model.
* Business Impact: Product fields, image accessors, reviews, and wishlist behavior may diverge.
* Technical Impact: Violates canonical module ownership and increases maintenance cost.
* Proposed Solution: Confirm Product module as canonical owner, update Order relationships to use canonical product model, then remove duplicate after caller migration.
* Files To Change: `Modules/Order/Models/Product.php`, `Modules/Order/Models/OrderItem.php`, `Modules/Product/Models/Product.php`, tests under `tests/Feature/Order`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Order item product relation uses the canonical Product model; duplicate Order product model has no references before removal.

### P1-13: Model contains HTML and persistence logic

* Issue: `Modules/Order/Models/Order.php` includes `getStatusBadgeAttribute()` HTML and `recalculateTotalCommission()` persistence.
* Root Cause: Presentation and business behavior were placed in the model for convenience.
* Business Impact: Status rendering and commission calculation are hard to reuse and test safely.
* Technical Impact: Violates model responsibility rules.
* Proposed Solution: Move badge rendering to a Blade component/helper and commission recalculation to `OrderService` or a commission service.
* Files To Change: `Modules/Order/Models/Order.php`, `Modules/Order/resources/views/livewire/orders/order-table.blade.php`, `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`, new or existing `Modules/Order/Services/OrderService.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Order model contains relationships/casts/configuration only; UI still renders identical badges; commission recalculation is service-owned.

### P1-14: Raw HTML badge rendering in Livewire Blade

* Issue: `Modules/Order/resources/views/livewire/orders/order-table.blade.php` renders `{!! $order->status_badge !!}`.
* Root Cause: Status badge HTML is returned by model accessor.
* Business Impact: Unsafe rendering pattern can become an XSS risk if status labels change or become user-controlled.
* Technical Impact: Coupling between model and Tailwind HTML.
* Proposed Solution: Replace raw model HTML with a Blade component fed by validated status values.
* Files To Change: `Modules/Order/resources/views/livewire/orders/order-table.blade.php`, `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`, `Modules/Order/Models/Order.php`, new shared/module Blade component path after convention check.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: No raw status badge HTML is emitted from the model; rendered output remains escaped/component-controlled.

### P1-15: Missing migrations for affiliate tables

* Issue: `Modules/Order/Models/AffiliateLevel.php` and `Modules/Order/Models/AffiliateScheme.php` reference `affiliate_levels` and `wp_affiliate_schemes`, but no Order migrations define them.
* Root Cause: Affiliate schema ownership is unclear or migrations live elsewhere.
* Business Impact: Fresh installs may fail if affiliate services use missing tables.
* Technical Impact: Migration ownership and module boot reliability are uncertain.
* Proposed Solution: Locate existing migrations, confirm canonical module ownership, then either move/add migrations or relocate/remove models.
* Files To Change: `Modules/Order/Models/AffiliateLevel.php`, `Modules/Order/Models/AffiliateScheme.php`, `Modules/Order/database/migrations`, possible owning module migrations after confirmation.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Fresh migration path creates required affiliate tables once; models live in the canonical owning module.

### P1-16: Malformed negative-year migration names

* Issue: Order migrations use filenames like `Modules/Order/database/migrations/-0001_11_30_000021_create_wp_orders_table.php`.
* Root Cause: Migration generation/import created invalid chronological prefixes.
* Business Impact: Fresh install ordering and CI migration reliability are risky.
* Technical Impact: Violates migration hygiene and deterministic ordering requirements.
* Proposed Solution: Rename migrations in a coordinated migration hygiene task, preserving order and production compatibility.
* Files To Change: `Modules/Order/database/migrations/-0001_11_30_000021_create_wp_orders_table.php`, `Modules/Order/database/migrations/-0001_11_30_000022_create_order_items_table.php`, `Modules/Order/database/migrations/-0001_11_30_000023_create_order_histories_table.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Fresh migrations run in deterministic order; existing deployed migration history is handled safely.

### P1-17: OrderItem has an invalid self-relationship

* Issue: `Modules/Order/Models/OrderItem.php` defines `items()` as `hasMany(OrderItem::class, 'order_id')`.
* Root Cause: Relationship appears copied from `Order` into `OrderItem`.
* Business Impact: Callers may accidentally use a nonsensical relationship and receive unrelated items.
* Technical Impact: Model API is misleading and can generate bad queries.
* Proposed Solution: Remove the relationship after confirming no callers use it.
* Files To Change: `Modules/Order/Models/OrderItem.php`, tests under `tests/Unit/Order`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: No references to `OrderItem::items()` remain; valid `order()` and `product()` relationships still pass tests.

### P1-18: Missing automated tests for Order module

* Issue: No Order-specific tests were found during analysis.
* Root Cause: Module behavior was implemented without regression coverage.
* Business Impact: Security and data-integrity fixes can regress silently.
* Technical Impact: Refactoring direct queries and duplicate services is risky.
* Proposed Solution: Add route, authorization, Livewire action, service transaction, relationship, and invoice access tests before broad cleanup.
* Files To Change: `tests/Feature/Order/*`, `tests/Unit/Order/*`, affected module files under `Modules/Order`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days.
* Acceptance Criteria: Tests cover P0 denial paths, allowed paths, validation failures, transactions, and query expectations.

## 4. P2 Nice To Have Improvements

### P2-01: Placeholder pages and views appear unused

* Issue: `Modules/Order/resources/views/pages/index.blade.php`, `Modules/Order/resources/views/order.blade.php`, `Modules/Order/resources/views/components/placeholder.blade.php`, and `Modules/Order/resources/views/livewire/placeholder.blade.php` appear unused.
* Root Cause: Scaffold placeholders were left after real order pages were added.
* Business Impact: Low direct impact, but developers can confuse placeholder pages with real entry points.
* Technical Impact: Dead files increase search noise.
* Proposed Solution: Remove after route and reference tests confirm no usage.
* Files To Change: `Modules/Order/resources/views/pages/index.blade.php`, `Modules/Order/resources/views/order.blade.php`, `Modules/Order/resources/views/components/placeholder.blade.php`, `Modules/Order/resources/views/livewire/placeholder.blade.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Files are deleted only after no route/reference usage is proven.

### P2-02: Old commented route blocks remain

* Issue: `Modules/Order/routes/web.php` and `Modules/Order/routes/api.php` contain obsolete commented route definitions.
* Root Cause: Earlier scaffold/routes were disabled by comments instead of removed.
* Business Impact: Low, but comments can mislead future route changes.
* Technical Impact: Route files are harder to audit.
* Proposed Solution: Remove obsolete comments after route behavior is covered by tests.
* Files To Change: `Modules/Order/routes/web.php`, `Modules/Order/routes/api.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.1 day.
* Acceptance Criteria: Route files contain only active, intentional route definitions.

### P2-03: Invoice view has inline CSS and hard-coded store identity

* Issue: `Modules/Order/resources/views/pages/orders/invoice.blade.php` contains inline CSS and hard-coded `FLEXBIZ STORE` sender details.
* Root Cause: Invoice was built as a standalone template without configuration integration.
* Business Impact: Incorrect branding/contact details can appear on customer-facing invoices.
* Technical Impact: Presentation is difficult to maintain and cannot adapt per environment.
* Proposed Solution: Move store identity to configuration/settings and isolate PDF-compatible styling in a maintainable invoice template approach.
* Files To Change: `Modules/Order/resources/views/pages/orders/invoice.blade.php`, relevant config/settings file after confirmation, `Modules/Order/Http/Controllers/OrderController.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Invoice displays configured business identity; PDF rendering remains stable.

### P2-04: Status labels and badges are duplicated

* Issue: Status labels exist in `Modules/Order/Models/Order.php`, `Modules/Order/resources/views/livewire/orders/order-table.blade.php`, and `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`.
* Root Cause: Status display rules were repeated in model and views.
* Business Impact: Labels can drift across list/detail/invoice screens.
* Technical Impact: More files must change for every status presentation update.
* Proposed Solution: Consolidate status labels and badge rendering after P1 service/model cleanup.
* Files To Change: `Modules/Order/Models/Order.php`, `Modules/Order/resources/views/livewire/orders/order-table.blade.php`, `Modules/Order/resources/views/livewire/orders/order-detail.blade.php`, possible module/shared badge component.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Status labels come from one controlled presentation path.

### P2-05: Invoice option normalization duplicates model cast behavior

* Issue: `Modules/Order/resources/views/pages/orders/invoice.blade.php` manually decodes `options` even though `Modules/Order/Models/OrderItem.php` casts `options` to array.
* Root Cause: View defensive logic was added instead of normalizing data before rendering.
* Business Impact: Low, but inconsistent option rendering can appear between screens.
* Technical Impact: Blade contains transformation logic.
* Proposed Solution: Remove duplicated normalization after `OrderService` prepares invoice data.
* Files To Change: `Modules/Order/resources/views/pages/orders/invoice.blade.php`, `Modules/Order/Models/OrderItem.php`, new or existing `Modules/Order/Services/OrderService.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25-0.5 day.
* Acceptance Criteria: Invoice options render correctly without inline JSON decode logic.

### P2-06: OrderDetailModal appears unused inside Order module

* Issue: `Modules/Order/Livewire/Orders/OrderDetailModal.php` has no in-module mount/reference found during analysis.
* Root Cause: Component may be copied from Admin/Website affiliate screens or reserved for future use.
* Business Impact: Low unless external modules rely on Livewire alias discovery.
* Technical Impact: Unused component duplicates commission modal behavior.
* Proposed Solution: Confirm all cross-module references and Livewire aliases before removing or migrating it.
* Files To Change: `Modules/Order/Livewire/Orders/OrderDetailModal.php`, `Modules/Order/resources/views/livewire/orders/order-detail-modal.blade.php`, possible callers in `Modules/Admin` or `Modules/Website` after confirmation.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Component is either confirmed used and secured, or removed only after no references remain.

### P2-07: Missing observability for sensitive order actions

* Issue: Invoice exports, status changes, deletes, and commission decisions lack explicit observability/audit events in the Order module.
* Root Cause: Actions were implemented directly without a consistent operation logging policy.
* Business Impact: Incident review and accounting reconciliation are harder.
* Technical Impact: Operational tracing is incomplete.
* Proposed Solution: Add audit/log events in service methods after P0 authorization and transaction boundaries are in place.
* Files To Change: new or existing `Modules/Order/Services/OrderService.php`, `Modules/Order/Services/AdminAffiliateService.php`, `Modules/Order/Models/OrderHistory.php`, logging/audit infrastructure after convention check.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Sensitive actions emit structured, non-sensitive audit/log records with actor, order ID, action, and outcome.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. Secure or remove `Modules/Order/routes/api.php` and fix `Modules/Order/Http/Controllers/Api/OrderController.php`.
2. Add named permissions and denial tests for `Modules/Order/routes/web.php`, `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, `Modules/Order/Livewire/Orders/OrderDetail.php`, and `Modules/Order/Livewire/Orders/OrderDetailModal.php`.
3. Validate status, per-page, and selected ID inputs in `Modules/Order/Livewire/Orders/OrderTable.php` and `Modules/Order/Livewire/Orders/OrderDetail.php`.
4. Create service-backed transactional paths for status update/history creation and destructive deletes.
5. Protect invoice print/PDF access with explicit export permission.

### Phase 2: Correctness and Maintainability

1. Introduce or complete `Modules/Order/Services/OrderService.php`.
2. Move direct controller and Livewire queries from `Modules/Order/Http/Controllers/OrderController.php`, `Modules/Order/Livewire/Orders/OrderTable.php`, and `Modules/Order/Livewire/Orders/OrderDetail.php` into the service.
3. Fix `Modules/Order/Services/AffiliateRankService.php` namespace after confirming duplicate service ownership.
4. Resolve canonical ownership for `Modules/Order/Models/Product.php`, `Modules/Website/Models/Order.php`, and duplicate affiliate services.
5. Add eager loading/counts for order list/detail queries.
6. Move model HTML and commission persistence from `Modules/Order/Models/Order.php` into service/component layers.
7. Repair migration ownership and malformed migration names in `Modules/Order/database/migrations`.

### Phase 3: Performance and Cleanup

1. Add query-count or focused performance tests for order list/detail screens.
2. Normalize invoice rendering and remove inline option decoding from `Modules/Order/resources/views/pages/orders/invoice.blade.php`.
3. Consolidate status badge/label rendering into one component or helper.
4. Remove confirmed unused placeholders and obsolete commented route blocks.
5. Add structured audit/observability for invoice exports, deletes, status changes, and commission decisions.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/Order/routes/web.php` | Modify | P0 | Add named permissions and remove obsolete commented route block later. |
| `Modules/Order/routes/api.php` | Modify or remove route | P0 | Close unauthenticated broken API route. |
| `Modules/Order/Http/Controllers/OrderController.php` | Modify | P0/P1 | Remove direct queries, authorize invoice access, delegate to service. |
| `Modules/Order/Http/Controllers/Api/OrderController.php` | Modify or remove endpoint | P0 | Implement or remove missing `index()` behavior. |
| `Modules/Order/Livewire/Orders/OrderTable.php` | Modify | P0/P1 | Add authorization, validation, and service-backed list/delete actions. |
| `Modules/Order/Livewire/Orders/OrderDetail.php` | Modify | P0/P1 | Add authorization, validation, transactional service calls, eager-loaded detail data. |
| `Modules/Order/Livewire/Orders/OrderDetailModal.php` | Modify or remove after confirmation | P0/P2 | Secure commission actions or remove unused duplicate component. |
| `Modules/Order/resources/views/livewire/orders/order-table.blade.php` | Modify | P1/P2 | Replace raw badge rendering, align with validated service data. |
| `Modules/Order/resources/views/livewire/orders/order-detail.blade.php` | Modify | P1/P2 | Replace raw badge/status duplication and keep view rendering-only. |
| `Modules/Order/resources/views/livewire/orders/order-detail-modal.blade.php` | Modify or remove after confirmation | P0/P2 | Keep UI aligned with secured commission actions or remove unused view. |
| `Modules/Order/resources/views/pages/orders/invoice.blade.php` | Modify | P1/P2 | Remove inline data normalization, configure branding, keep PDF-compatible presentation. |
| `Modules/Order/Services/OrderService.php` | Create | P0/P1 | Centralize order queries, validation invariants, transactions, and invoice data loading. |
| `Modules/Order/Services/AdminAffiliateService.php` | Modify | P0/P1 | Add authorization-compatible invariants, safe errors, transactions, canonical model usage. |
| `Modules/Order/Services/AffiliateRankService.php` | Modify or migrate | P1 | Fix namespace and resolve duplicate ownership. |
| `Modules/Order/Models/Order.php` | Modify | P1/P2 | Remove HTML/accessor persistence logic after service/component replacement. |
| `Modules/Order/Models/OrderItem.php` | Modify | P1 | Use canonical Product relation and remove invalid self-relationship. |
| `Modules/Order/Models/Product.php` | Remove after migration | P1 | Duplicate of canonical Product module model. |
| `Modules/Order/Models/AffiliateLevel.php` | Modify, move, or remove after ownership decision | P1 | Missing migration and unclear ownership. |
| `Modules/Order/Models/AffiliateScheme.php` | Modify, move, or remove after ownership decision | P1 | Missing migration and unclear ownership. |
| `Modules/Order/Models/OrderHistory.php` | Modify if audit fields needed | P0/P2 | Support transactional history/audit for sensitive actions. |
| `Modules/Order/database/migrations/-0001_11_30_000021_create_wp_orders_table.php` | Rename/modify in migration hygiene task | P1 | Malformed filename and schema/index review. |
| `Modules/Order/database/migrations/-0001_11_30_000022_create_order_items_table.php` | Rename/modify in migration hygiene task | P1 | Malformed filename and relationship/index review. |
| `Modules/Order/database/migrations/-0001_11_30_000023_create_order_histories_table.php` | Rename/modify in migration hygiene task | P1 | Malformed filename and audit relationship review. |
| `Modules/Order/resources/views/pages/index.blade.php` | Delete after confirmation | P2 | Unused placeholder. |
| `Modules/Order/resources/views/order.blade.php` | Delete after confirmation | P2 | Unused scaffold page. |
| `Modules/Order/resources/views/components/placeholder.blade.php` | Delete after confirmation | P2 | Placeholder only. |
| `Modules/Order/resources/views/livewire/placeholder.blade.php` | Delete after confirmation | P2 | Unused placeholder. |
| `tests/Feature/Order/*` | Create | P0/P1 | Route, authorization, Livewire, invoice, and denied-path regression coverage. |
| `tests/Unit/Order/*` | Create | P0/P1 | Service transaction, validation invariant, relationship, and query tests. |

## 7. Risk Control

Do not change production behavior until P0 tests exist for authorization denial, invalid inputs, destructive action denial, and transaction rollback.

Do not delete `Modules/Order/Models/Product.php`, `Modules/Order/Livewire/Orders/OrderDetailModal.php`, affiliate models, duplicate Admin/Website services, or placeholder files until references are checked across modules and route/Livewire alias behavior is covered.

Do not rename malformed migrations in `Modules/Order/database/migrations` without a deployment-safe migration history strategy. Fresh-install hygiene and already-run production migrations must be handled separately.

Do not move affiliate ownership or product ownership based only on file names. Confirm canonical module ownership and migrate callers incrementally.

Do not introduce DTOs, controller queries, model transactions, Blade queries, or Livewire-owned business logic. New behavior should use validated arrays/scalars into services, Laravel authorization at entry points, and service-owned transactions.

Do not implement import/export features for Order in this refactor unless a separate confirmed import/export scope provides sample files, mapping mode, unique keys, dry-run behavior, and shared `Modules/Shared/Services/ImportExport` integration requirements.
