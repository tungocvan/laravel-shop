# Website Module Analysis

> Post-refactor update (Phase 8): the original findings below are retained as
> historical baseline. Phases 1–7 are closed and the Phase 8 CLI release gate
> passes. Duplicate cross-domain models/services, checkout integrity defects,
> authorization gaps, CMS limitations and production optimization items described
> in the original analysis have been addressed. See `PHASE_8_COMPLETION.md` for
> current release evidence and explicitly deferred compatibility items.

## Executive Summary

`Modules/Website` is an enabled `domain` module responsible for the public storefront plus a substantial set of account, checkout, marketing, affiliate and admin-facing website features. Its manifest explicitly depends on `User`, `Product`, `Category`, `Post`, and `Order`.

The module is functional but remains architecturally overloaded. It contains duplicate/cross-domain models and services for Product, Order, Post, User/account, Chat, Auth, database administration and environment configuration. This conflicts with the repository roadmap direction that each business concept should have one canonical domain owner.

Current recommendation: **Major Refactor**. A full rebuild is not justified from the inspected evidence because large parts of the storefront and service structure are reusable, but ownership boundaries, authorization, checkout integrity, duplicate services, and tests need substantial correction.

No application source code was modified by this analysis.

## Module Purpose and Overview

Main responsibilities currently observed:

- Public home/help pages.
- Product listing and product detail.
- Blog listing/detail.
- Cart and coupon handling.
- Checkout and order creation.
- Login/register/logout presentation.
- Customer account/profile/address/order/wishlist pages.
- Affiliate dashboard and commissions.
- Website header/footer/banner/home/flash-sale settings.
- Coupon and customer administration.
- Website-facing API endpoint.

## Bootstrap / Standards Context

Repository baseline:

- Laravel 12.
- PHP 8.3.
- Livewire 3.x.
- First-party modular monolith under `Modules/`.
- Module registration is controlled by `Modules/ModuleServiceProvider.php`.
- Website manifest: `type=domain`, `enabled=true`, dependencies `User`, `Product`, `Category`, `Post`, `Order`.
- Canonical architecture prefers `Route -> Controller -> Page Blade -> Livewire -> Service -> Model -> Database`.
- Mutating admin actions require capability-specific authorization in addition to authentication.
- Business workflows and multi-record writes belong in services and transactions.

## Dependency Graph

Primary runtime path:

```text
Website routes
-> Website controllers
-> Website page Blade
-> Website Livewire components
-> Website services
-> Website models
-> database
```

Declared cross-module dependencies:

```text
Website
├── User
├── Product
├── Category
├── Post
└── Order
```

Observed ownership problem:

```text
Website
├── owns WpProduct / Category / Post / Order-like models
├── owns Auth/Chat helpers and services
├── owns database/env administration services
└── also depends on canonical Product/Category/Post/Order/User modules
```

This is a P1 module-integrity concern because the roadmap explicitly targets duplicate implementations across Website and the canonical domain modules.

## Route / Controller / Blade / Livewire Analysis

### Routes

Main file: `Modules/Website/routes/web.php`.

Public routes include login, register, home, help, product list/detail, blog list/detail, cart, checkout, checkout success, MoMo callback and authenticated account pages.

Admin routes live under `/admin` with `web` + `auth:admin` and include affiliate, homepage, header, footer, banners, flash sales, coupons and customers.

### Route findings

**P0 - Broken checkout callback contract**

- Priority: P0
- File: `Modules/Website/routes/web.php`, `Modules/Website/Http/Controllers/CheckoutController.php`
- Evidence: route `checkout.momo.callback` points to `CheckoutController@momoCallback`, but the controller currently exposes only `index()` and `success()`.
- Problem: a payment-provider callback URL can resolve to a missing controller method.
- Impact: payment callback failure and incorrect payment/order state.
- Recommendation: restore a verified callback handler or remove/re-route the endpoint through the canonical payment/order service with signature verification and idempotency.

**P0 - Admin route authorization is too broad**

- Priority: P0
- File: `Modules/Website/routes/web.php`
- Evidence: Website admin route group uses `auth:admin`, without capability middleware at route-group level.
- Problem: authentication alone is not sufficient for privileged mutations performed by mounted Livewire components.
- Impact: an authenticated admin may reach actions outside their intended capability.
- Recommendation: enforce named permissions/policies at route and mutation boundaries.

Documentation drift resolved: older Website analysis stated `/blog` used a route closure. Current source routes `/blog` through `PostController@index`.

### Controllers

Controllers are generally page-oriented, but several directly query models.

**P1 - Thin-controller rule violations**

- Files: `CheckoutController.php`, `AccountController.php`, `ProductController.php`.
- Evidence: controllers directly query Website models for cart/order/product/account data.
- Impact: duplicated query logic and weaker testability/service boundaries.
- Recommendation: move workflow/query responsibilities to canonical module services while preserving current routes.

### Livewire

Website contains Livewire groups for account, admin, auth, cart, checkout, chat, dashboard, home, post, products and wishlist.

**P0 - Missing mutation authorization in customer administration**

- Priority: P0
- File: `Modules/Website/Livewire/Admin/Customers/CustomerTable.php`
- Evidence: `toggleStatus()`, `deleteSelected()`, and `delete()` mutate `App\Models\User` directly without method-level authorization.
- Impact: unauthorized account disable/delete operations if a user reaches the component.
- Recommendation: require capability checks inside every mutation and delegate persistence to the canonical User/Account service.

**P1 - Unbounded customer selection/list option**

- Priority: P1
- File: `Modules/Website/Livewire/Admin/Customers/CustomerTable.php`
- Evidence: select-all plucks all matching IDs and the `all` page option maps to `paginate(9999)`.
- Impact: memory/query growth on production datasets.
- Recommendation: remove pseudo-unbounded pagination and implement bounded bulk-selection semantics.

**Resolved prior finding - cart item ownership**

Current `CartService` resolves item mutations through `getCart()->items()->whereKey(...)`, so browser-supplied item IDs are scoped to the current cart. The earlier finding that `removeItem()` deleted arbitrary `CartItem` IDs is no longer valid.

## Service Analysis

Primary services include cart, checkout, product/catalog presentation, content, wishlist, settings, banners, marketing, footer/header, affiliate and account/profile behavior.

A second nested `Modules/Website/Services/Services` tree still exists and contains duplicate or unrelated infrastructure such as:

- duplicate affiliate/banner/flash-sale services;
- Auth service;
- Chat service;
- database administration service;
- database connection service;
- environment/configuration services;
- home setting service.

**P1 - Duplicate service tree and cross-domain ownership**

- Priority: P1
- File: `Modules/Website/Services/Services/**`
- Evidence: parallel service implementations exist alongside root Website services and responsibilities owned by System/Auth/Chat domains.
- Impact: ambiguous source of truth, duplicated fixes, circular dependency risk and difficult refactoring.
- Recommendation: migrate callers toward canonical domain owners, then remove duplicates in a separate refactor task.

### Checkout integrity

**P0 - Checkout stock race condition**

- Priority: P0
- File: `Modules/Website/Services/CheckoutService.php`
- Evidence: stock is checked before entering `DB::transaction`; product rows are not locked before decrement.
- Problem: two concurrent checkouts can pass the pre-check using the same stock.
- Impact: overselling and incorrect inventory.
- Recommendation: re-read/lock inventory rows inside the transaction and enforce a canonical inventory/order invariant.

**P1 - Deleted cart is saved again**

- Priority: P1
- File: `Modules/Website/Services/CheckoutService.php`
- Evidence: the service deletes the cart, then assigns `coupon_id = null` and calls `save()` on the deleted model.
- Impact: confusing/non-effective cleanup semantics and potential behavior differences if model events/scopes change.
- Recommendation: clear required fields before deletion or simply delete after item/coupon processing.

**P1 - Order ownership duplication**

- Priority: P1
- File: `Modules/Website/Services/CheckoutService.php`, `Modules/Website/Models/Order*.php`
- Evidence: Website creates and mutates order-domain records while the manifest also depends on the `Order` module.
- Impact: duplicate business rules and inconsistent order state handling.
- Recommendation: Website should orchestrate storefront UX and delegate canonical order creation to `Modules/Order`.

## Import / Export Analysis

Not present as a canonical Website feature in the inspected module tree.

Any existing coupon/customer import/export behavior embedded in admin Livewire should be migrated to the shared import/export foundation if retained. This requires targeted verification before implementation.

## Shared Dependencies

No evidence that Website consistently uses `Modules/Shared/Services/ImportExport` for its admin data utilities.

Website also includes references crossing into Admin-owned views/components in existing documentation; these should be verified during refactor because module view ownership should point to canonical providers rather than duplicate UI trees.

## Model / Migration / Database Analysis

Website currently contains models for affiliate, banners, cart, category, coupons, flash sales, footer/header menus, newsletters, order/order items/history, posts, reviews, settings, tags, addresses, wishlist and products.

The migration tree includes numerous old malformed names beginning with `-0001_11_30_...`, including carts, coupons, settings, banners, flash sales, footer, social links, wishlist and affiliate structures.

**P1 - Migration hygiene**

- Priority: P1
- File: `Modules/Website/database/migrations/-0001_11_30_*.php`
- Evidence: multiple migrations have malformed negative-year timestamps.
- Impact: migration ordering/fresh-install reliability is difficult to reason about and is already a repository roadmap concern.
- Recommendation: repair migration history through a dedicated migration-hygiene task with fresh-install verification; do not rewrite applied production migrations casually.

**P1 - Duplicate database ownership**

Website owns models/tables for Product, Post and Order concepts while depending on those domain modules. Canonical ownership must be established before schema refactoring.

## Security

Primary current risks:

1. Missing capability-level authorization on admin Livewire mutations.
2. Checkout payment callback route targets a missing method.
3. Database/environment administration code exists inside Website's nested service tree and should not be considered a storefront responsibility.
4. Livewire catches generic exceptions in multiple areas; raw exception messages can reach UI in some flows.

No new P0 finding is asserted for cart item ID ownership because the current service scopes item resolution to the active cart.

## Performance

Key risks:

- Customer select-all loads all matching IDs.
- `paginate(9999)` simulates an unbounded `All` option.
- Home/product/post Livewire components historically contain direct model queries; targeted profiling is still required.
- Duplicate services make caching/query optimization inconsistent.

## Validation and Authorization

Positive observations:

- A dedicated `CheckoutRequest.php` exists.
- Cart item mutation ownership is now scoped in `CartService`.

Gaps:

- Admin Livewire mutations require named permissions/policies.
- Service-level invariants should not depend exclusively on UI validation.
- Payment callback inputs/signatures require dedicated validation once the missing callback implementation is resolved.

## Transactions, Concurrency and Data Integrity

Positive observation: checkout order creation is wrapped in `DB::transaction`.

Material gaps:

- stock validation occurs before the transaction;
- product rows are not locked during stock decrement;
- order creation remains in Website instead of the canonical Order domain;
- cart cleanup saves a deleted model;
- idempotency for payment/order callback processing is not demonstrated.

## Admin UI / UX Standard Review

Admin UI exists, so `ADMIN_UI_STANDARD.md` applies.

Observed concerns:

- large Livewire admin screens and duplicated module UI increase maintainability cost;
- unbounded `All` behavior conflicts with bounded-list guidance;
- dangerous actions require loading/disabled/confirmation and permission checks; targeted Blade verification is still required for each screen.

## Cross-Module Dependencies

Declared: User, Product, Category, Post, Order.

Observed conceptual dependencies additionally include Admin/Auth/Chat/System-like responsibilities.

The core architectural problem is not the existence of dependencies; it is Website retaining duplicate ownership of concepts for which canonical domain modules already exist.

## Technical Debt

Major debt areas:

- `Services/Services` parallel tree.
- duplicate Product/Post/Order/User models and workflows.
- admin functionality mixed into storefront domain.
- database/env/system utilities in Website.
- malformed migration timestamps.
- large Livewire components with direct persistence/query responsibilities.
- stale documentation from prior source state.

## Test Coverage

No Website-specific automated test suite was identified from the repository context inspected during this analysis. The repository roadmap previously recorded only default example tests.

Required future coverage should include:

- Website route boot tests;
- denied admin mutations;
- cart ownership isolation;
- checkout concurrency/stock behavior;
- coupon application;
- order ownership/authorization;
- payment callback signature and idempotency;
- account order ownership;
- migration smoke tests.

## Documentation Drift

Existing `ANALYSIS.md` was stale in at least these areas:

- `/blog` no longer uses a route closure.
- Cart item deletion/update now scopes item IDs to the current cart service query.

Still valid after verification:

- missing `CheckoutController@momoCallback`.
- broad `auth:admin` route boundary.
- missing method-level authorization in `CustomerTable`.
- duplicate `Services/Services` architecture.
- checkout concurrency and deleted-cart cleanup issues.
- duplicate domain ownership remains visible in module manifest/model/service structure.

`INFORMATION.md` and `README.md` were absent before this analysis and are generated by this task.

## Issue List (P0/P1/P2)

| Priority | Issue | Primary file(s) |
|---|---|---|
| P0 | Payment callback route points to missing method | `routes/web.php`, `CheckoutController.php` |
| P0 | Admin mutations lack capability authorization | `Livewire/Admin/Customers/CustomerTable.php` and other admin components to verify |
| P0 | Checkout stock race can oversell | `Services/CheckoutService.php` |
| P1 | Website duplicates canonical Product/Post/Order/User ownership | `Models/**`, `Services/**`, manifest |
| P1 | Parallel `Services/Services` tree contains duplicate/cross-domain logic | `Services/Services/**` |
| P1 | Deleted cart is saved after delete | `Services/CheckoutService.php` |
| P1 | Customer `All`/select-all operations are effectively unbounded | `CustomerTable.php` |
| P1 | Malformed migration timestamps | `database/migrations/-0001_11_30_*.php` |
| P1 | Thin-controller/service-boundary violations remain | selected controllers/Livewire |
| P2 | Large admin UI components need decomposition/reuse review | admin Livewire/Blade |
| P2 | Documentation had drifted from source | `docs/modules/Website/**` |

## Module Health Summary

- Functional coverage: High.
- Architectural clarity: Low-Medium.
- Authorization maturity: Low for admin mutations.
- Data-integrity maturity: Medium-Low around checkout concurrency.
- Performance safety: Medium-Low due to unbounded admin options and direct queries.
- Testability: Low until module-specific tests are added.
- Documentation: refreshed by this analysis.

## Final Recommendation

**Major Refactor**

Refactor in staged, behavior-preserving slices:

1. close authorization and payment/checkout correctness risks;
2. establish Order/Product/Post/User canonical ownership;
3. migrate Website callers to canonical services/models;
4. remove `Services/Services` duplicates and unrelated System/Auth/Chat/env/database utilities;
5. bound list/bulk operations and profile queries;
6. add targeted tests before deleting legacy code.

Do not start a full rebuild unless dependency migration proves the existing storefront presentation layer cannot be preserved economically.

## Open Questions / Unknowns

- Exact production payment provider flow and expected MoMo callback contract.
- Which Website duplicate models/tables are currently authoritative in production versus the canonical Product/Post/Order modules.
- Exact permission names intended for Website admin capabilities.
- Runtime route collisions with Admin/Product/Post/Order modules were not executed via `artisan route:list` in this connector-only analysis.
- Current database schema and indexes were inferred from repository migrations/models; live production schema was not inspected.
- Full query-count/N+1 behavior requires runtime profiling.
