# Website Refactor Plan

## Purpose

This document is the canonical staged refactor roadmap for `Modules/Website`.

The Website module will be improved in controlled phases. Each phase must be analyzed, implemented, tested, and approved before moving to the next phase.

Application source must not be changed outside the active phase scope.

## Status Legend

- `[ ] NOT STARTED`
- `[x] ANALYZED`
- `[ ] IMPLEMENTED`
- `[ ] TESTED`
- `[ ] APPROVED`

## Current Overall Status

- Phase 0 — Baseline & Safety Net: `[x] ANALYZED` / runtime test pending
- Phase 1 — Stabilize & Security: `[ ] NOT STARTED`
- Phase 2 — Domain Ownership: `[ ] NOT STARTED`
- Phase 3 — Database Restructure: `[ ] NOT STARTED`
- Phase 4 — Service Layer: `[ ] NOT STARTED`
- Phase 5 — Website Admin CMS: `[ ] NOT STARTED`
- Phase 6 — Frontend Professionalization: `[ ] NOT STARTED`
- Phase 7 — Production Optimization: `[ ] NOT STARTED`
- Phase 8 — Release Gate: `[ ] NOT STARTED`

---

# Phase 0 — Baseline & Safety Net

## Goal

Create a reliable baseline of current Website behavior before code changes.

## Status

- [x] ANALYZED
- [ ] IMPLEMENTED — not applicable; documentation/baseline phase only
- [ ] TESTED — runtime smoke test pending
- [ ] APPROVED

## Baseline Snapshot

Repository: `vhdtshop-ux/source-laravel-12`

Branch: `main`

Baseline analysis was performed against the current `main` source after Website module analysis documentation was refreshed.

## Inventory Checklist

- [x] Resolve `Modules/Website`
- [x] Inventory Website routes
- [x] Inventory controllers
- [x] Inventory frontend Livewire components
- [x] Inventory admin Livewire components
- [x] Inventory services
- [x] Inventory models
- [x] Inventory migrations/database ownership
- [x] Inventory frontend/admin views
- [x] Map declared cross-module dependencies
- [x] Inspect existing Website tests
- [x] Record known broken behavior
- [x] Record known architectural debt
- [ ] Run Website runtime smoke test
- [ ] Run Website feature test
- [ ] Run full project test suite
- [ ] Approve baseline

## Declared Module Dependencies

`Modules/Website/Config/module.php` currently declares Website as a domain module depending on:

- User
- Product
- Category
- Post
- Order

This dependency direction is useful for the target architecture: Website should consume canonical domain owners rather than duplicate those domains.

## Frontend Baseline

Main public areas currently include:

- Homepage
- Help
- Product list
- Product detail
- Blog list
- Blog detail
- Login
- Register
- Logout
- Cart
- Checkout
- Checkout success
- Account dashboard
- Profile
- Addresses
- Orders
- Order detail
- Wishlist
- Affiliate dashboard
- Chat/supporting widgets

## Admin Baseline

Website currently provides admin UI for:

- Homepage settings
- Header/menu settings
- Footer settings
- Banner management
- Flash sales
- Coupons
- Customers
- Affiliate administration

Some of these capabilities are likely owned by other canonical business modules and will be addressed in Phase 2.

## Existing Automated Website Test

Current repository contains:

`tests/Feature/Website/WebsiteRouteConfigurationTest.php`

It currently verifies:

- `blog.index` routes through `PostController@index`
- Website admin routes retain `auth:admin`
- selected Website admin page blades do not mount components through incorrect `admin.*` aliases

This is useful but is not a sufficient regression safety net for the planned refactor.

## Source-Confirmed Baseline

### Confirmed fixed relative to older analysis

- `blog.index` now routes to `PostController@index`; the old closure finding is stale.
- Cart item increment/decrement/remove now resolve items through the current cart in `CartService`; the older direct foreign-cart-item ownership finding is stale.

### Broken before refactor

These defects already exist before Phase 1 and must not be misclassified as regressions introduced by refactoring.

1. **MoMo callback route mismatch**
   - `checkout.momo.callback` points to `CheckoutController@momoCallback`.
   - The current controller does not define `momoCallback()`.

2. **Checkout transaction defect**
   - Current checkout workflow deletes the cart and subsequently modifies/saves the deleted model.

3. **Checkout concurrency risk**
   - Stock validation occurs before the transaction.
   - Stock decrement does not use row locking.
   - Concurrent checkout can potentially oversell.

4. **Admin authorization gaps**
   - Website admin routes mainly rely on `auth:admin`.
   - Several mutating Livewire actions lack capability-specific method-level authorization.

5. **Website settings coupling**
   - Frontend layout directly reads `Setting` from Blade.
   - Raw configured header script can be rendered into the page.
   - Some settings mutations bypass a centralized settings service/cache invalidation path.

## Known Architectural Debt

These are not automatically classified as broken runtime behavior but must be addressed in later phases.

### Duplicate/cross-domain models in Website

Examples include:

- `WpProduct`
- `Category`
- `Post`
- `Order`
- `OrderItem`
- `OrderHistory`
- `UserAddress`

Canonical ownership will be resolved in Phase 2.

### Cross-domain/system services inside Website

Examples include:

- `Services/Services/DatabaseService.php`
- `Services/Services/Database/*`
- `Services/Services/Env/*`
- `Services/Services/AuthService.php`
- `Services/Services/ChatService.php`

These do not fit Website storefront/CMS ownership and must be migrated only after callers are verified.

### Database debt

Website currently owns or migrates data for multiple concerns, including:

- website settings
- banners
- footer structures
- carts/cart items
- coupons
- flash sales
- newsletters
- reviews
- wishlists
- affiliate structures

Several migration files use malformed `-0001_11_30_*` names. Existing applied migrations must not be rewritten casually.

## Phase 0 Runtime Smoke Test

### Frontend

- [ ] `/` — homepage loads
- [ ] `/product` — product list loads
- [ ] `/product/{slug}` — product detail loads
- [ ] `/blog` — blog list loads
- [ ] `/blog/{slug}` — blog detail loads
- [ ] `/login`
- [ ] `/register`
- [ ] `/cart`
- [ ] Cart add
- [ ] Cart increment
- [ ] Cart decrement
- [ ] Cart remove
- [ ] Coupon apply/remove
- [ ] `/checkout`
- [ ] Checkout validation
- [ ] COD checkout when supported
- [ ] `/checkout/success`
- [ ] `/account`
- [ ] `/account/profile`
- [ ] `/account/orders`
- [ ] `/account/orders/{code}`
- [ ] `/account/wishlist`
- [ ] `/account/affiliate`

### Admin

- [ ] `/admin/homepage-settings`
- [ ] `/admin/header-settings`
- [ ] `/admin/footer-settings`
- [ ] `/admin/banners`
- [ ] `/admin/flash-sales`
- [ ] `/admin/coupons`
- [ ] `/admin/customers`
- [ ] `/admin/affiliate`

Classify every runtime item as:

- `PASS`
- `BROKEN BEFORE REFACTOR`
- `PARTIAL`
- `UNKNOWN`

## Phase 0 Automated Checks

Run in the Laravel runtime environment:

```bash
php artisan optimize:clear
php artisan route:list
php artisan test tests/Feature/Website/WebsiteRouteConfigurationTest.php
php artisan test
```

When Docker is used, run the equivalent commands inside the PHP/application container.

## Phase 0 Approval Gate

Phase 0 is approved only when:

- runtime smoke test results are recorded
- existing broken behavior is clearly separated from regressions
- Website route test result is known
- full test-suite result is known or its blockers are documented
- the baseline is accepted before Phase 1 implementation

---

# Phase 1 — Stabilize & Security

## Goal

Fix current high-risk correctness/security defects before architectural migration or UI rebuilding.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Phase 1A — Checkout Stabilization

- [ ] Resolve broken MoMo callback contract
- [ ] Ensure callback maps to a real controller/service flow
- [ ] Verify payment callback/signature server-side
- [ ] Move stock verification inside transaction
- [ ] Lock product/inventory rows before final stock check
- [ ] Prevent overselling
- [ ] Make order creation atomic
- [ ] Make coupon usage atomic
- [ ] Remove save-after-cart-delete defect
- [ ] Prevent duplicate orders/double submit
- [ ] Define retry/idempotency behavior
- [ ] Add checkout regression tests

### Phase 1A Test Gate

- [ ] normal COD checkout
- [ ] empty cart
- [ ] inactive product
- [ ] insufficient stock
- [ ] concurrent checkout with stock=1
- [ ] valid coupon
- [ ] invalid/expired coupon
- [ ] double submit
- [ ] valid payment callback
- [ ] tampered callback
- [ ] duplicate callback
- [ ] rollback consistency

## Phase 1B — Admin Authorization

- [ ] Define capability permission matrix
- [ ] Keep `auth:admin`
- [ ] Add named capability checks
- [ ] Homepage mutation authorization
- [ ] Header/menu mutation authorization
- [ ] Footer mutation authorization
- [ ] Banner mutation authorization
- [ ] Coupon mutation authorization
- [ ] Flash-sale mutation authorization
- [ ] Customer mutation authorization
- [ ] Affiliate mutation authorization
- [ ] Authorize inside sensitive Livewire methods
- [ ] Add allowed/denied tests

Suggested capability direction:

```text
website.view
website.home.manage
website.menu.manage
website.banner.manage
website.footer.manage
website.settings.manage
website.seo.manage
```

Other domains should eventually own their own capabilities, such as customer, marketing, and affiliate permissions.

## Phase 1C — Settings, Script Security, Cache

- [ ] Centralize Website settings mutations
- [ ] Fix cache invalidation consistency
- [ ] Remove direct DB/model queries from frontend Blade
- [ ] Restrict custom script editing to privileged capability
- [ ] Review raw HTML/script rendering
- [ ] Validate image upload MIME/extension/size
- [ ] Define old-file cleanup policy
- [ ] Add settings/cache/security tests

## Phase 1 Approval Gate

Do not enter Phase 2 until:

- checkout tests pass
- authorization tests pass
- settings/cache tests pass
- baseline Phase 0 behavior remains intact except explicitly fixed baseline defects

---

# Phase 2 — Domain Ownership

## Goal

Make every business concept have one canonical module owner.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Ownership Checklist

### Product

- [ ] canonical owner = `Modules/Product`
- [ ] identify every caller of Website product model/service
- [ ] migrate Website storefront to Product contracts
- [ ] remove duplicate Product ownership only after callers migrate

### Category

- [ ] canonical owner = `Modules/Category`
- [ ] remove direct `DB::table('categories')` access from Website UI
- [ ] consume Category query/service contract

### Post

- [ ] canonical owner = `Modules/Post`
- [ ] Website blog becomes presentation/composition layer
- [ ] migrate duplicate model/query ownership

### Order

- [ ] canonical owner = `Modules/Order`
- [ ] order creation belongs to canonical Order workflow
- [ ] account order queries use Order owner
- [ ] migrate Website Order/OrderItem/OrderHistory callers

### User / Account

- [ ] canonical owner for customer/profile/address behavior identified
- [ ] customer administration moved out of Website ownership
- [ ] address/profile workflows use canonical account/user services

### System / Auth / Chat

- [ ] verify callers of Website `DatabaseService`
- [ ] verify callers of Website `Env/*`
- [ ] verify callers of Website `AuthService`
- [ ] verify callers of Website `ChatService`
- [ ] migrate callers to canonical owners
- [ ] remove duplicates only after tests pass

## Phase 2 Test Gate

- [ ] product list/detail unchanged
- [ ] homepage product/category sections unchanged
- [ ] blog unchanged
- [ ] cart unchanged
- [ ] checkout Phase 1 tests remain green
- [ ] account orders unchanged
- [ ] admin behavior/permissions unchanged

## Phase 2 Approval Gate

Every business entity must have one documented canonical owner before database restructuring begins.

---

# Phase 3 — Database Restructure

## Goal

Give Website a professional CMS/storefront database model while preserving production data and compatibility.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Global Settings

Use key-value settings only for true global/simple configuration:

- [ ] site identity
- [ ] logo/favicon
- [ ] contact
- [ ] social links/config
- [ ] theme configuration
- [ ] analytics identifiers/configuration
- [ ] default SEO

Do not continue using settings as a substitute for structured collections/relations.

## Website Pages

Design/verify `website_pages` or repository-consistent equivalent:

- [ ] id
- [ ] slug unique
- [ ] title
- [ ] status
- [ ] template
- [ ] SEO metadata strategy
- [ ] publishing state
- [ ] timestamps
- [ ] soft delete only when justified

## Website Sections

Design/verify structured page sections:

- [ ] page reference
- [ ] type
- [ ] position
- [ ] enabled state
- [ ] variant
- [ ] validated section configuration

Target: replace growing `home_show_*` / `home_*_ids` setting sprawl.

## Section Items

- [ ] section reference
- [ ] canonical referenced entity
- [ ] position
- [ ] item configuration
- [ ] referential integrity strategy
- [ ] eliminate JSON arrays of Product/Category IDs where relational ownership is required

## Menus

Design/verify:

- [ ] website menus
- [ ] nested menu items
- [ ] parent relationship
- [ ] ordering
- [ ] enabled state
- [ ] link/reference type
- [ ] external/internal URL handling
- [ ] Product/Category/Post/Page reference strategy
- [ ] target/mobile behavior

## Banners

- [ ] desktop image
- [ ] mobile image
- [ ] location
- [ ] CTA
- [ ] alt text
- [ ] schedule start/end
- [ ] ordering/priority
- [ ] active state

## Footer

- [ ] columns
- [ ] links
- [ ] social links
- [ ] ordering
- [ ] active state
- [ ] avoid duplicating menu engine unnecessarily

## Migration Strategy

- [ ] determine real production migration state
- [ ] do not rewrite applied migrations casually
- [ ] create corrective migrations
- [ ] design data backfill
- [ ] dual-read if required
- [ ] switch reads
- [ ] switch writes
- [ ] remove legacy usage
- [ ] remove legacy columns/tables only after approval
- [ ] document malformed `-0001_*` migration handling

## Phase 3 Test Gate

- [ ] migrate fresh in test environment
- [ ] upgrade existing database
- [ ] targeted rollback where supported
- [ ] foreign keys/constraints verified
- [ ] unique constraints verified
- [ ] section reorder verified
- [ ] referenced entity deletion behavior verified
- [ ] menu hierarchy verified
- [ ] seed/backfill verified
- [ ] no orphan records

---

# Phase 4 — Service Layer

## Goal

Make controllers and Livewire components thin and move workflows/queries to explicit services.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Target Website Services

Evaluate/standardize around services such as:

- `WebsiteSettingsService`
- `HomepageService`
- `WebsitePageService`
- `NavigationService`
- `BannerService`
- `FooterService`
- `SeoService`

Use canonical domain services for Product, Category, Post, Order, User/Account instead of recreating those workflows in Website.

## Checklist

- [ ] controllers contain no domain queries
- [ ] Blade contains no database queries
- [ ] Livewire contains no direct business persistence
- [ ] remove direct `DB::table()` usage from Website Livewire
- [ ] cross-domain queries go through canonical owners
- [ ] multi-record writes are transactional
- [ ] validation boundaries are explicit
- [ ] authorization boundaries are explicit
- [ ] cache invalidation is centralized
- [ ] return shapes are documented/typed where practical

## Target Public Flow

```text
Route
-> Controller
-> Website composition service
-> canonical domain services
-> Blade / Livewire presentation
```

## Target Admin Flow

```text
Admin Route
-> Controller/Page Blade
-> Livewire
-> Website Service
-> Website Models
-> Database
```

## Phase 4 Test Gate

- [ ] service tests
- [ ] critical Livewire tests
- [ ] controllers remain thin
- [ ] Blade has no DB access
- [ ] query behavior compared with baseline
- [ ] all previous phase regression tests stay green

---

# Phase 5 — Website Admin CMS

## Goal

Rebuild Website administration into a professional CMS/storefront management experience after backend/domain/database foundations are stable.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## 5A Website Dashboard

- [ ] website health/status summary
- [ ] active sections
- [ ] published pages
- [ ] active banners
- [ ] useful shortcuts
- [ ] avoid unrelated heavy analytics

## 5B Homepage Builder

- [ ] section list
- [ ] add section
- [ ] enable/disable
- [ ] edit
- [ ] reorder
- [ ] duplicate
- [ ] safe delete/confirmation
- [ ] preview
- [ ] responsive admin UI
- [ ] loading state
- [ ] empty state

Section editors to support as applicable:

- [ ] Hero
- [ ] Categories
- [ ] Featured Products
- [ ] New Arrivals
- [ ] Best Sellers
- [ ] Flash Sale
- [ ] Promo Banner
- [ ] Blog
- [ ] Trust Badges
- [ ] Newsletter

## 5C Menu Manager

- [ ] menu list
- [ ] nested items
- [ ] ordering/drag-drop
- [ ] URL/reference selector
- [ ] validation
- [ ] mobile behavior

## 5D Banner Manager

- [ ] desktop preview
- [ ] mobile preview
- [ ] scheduling
- [ ] active/inactive
- [ ] ordering
- [ ] secure image validation

## 5E Footer Manager

- [ ] column builder
- [ ] links
- [ ] social links
- [ ] reorder

## 5F SEO

- [ ] global SEO
- [ ] page SEO
- [ ] OpenGraph preview
- [ ] canonical URL
- [ ] robots policy

## 5G Theme / Settings

- [ ] logo
- [ ] favicon
- [ ] contact information
- [ ] brand configuration
- [ ] analytics
- [ ] restricted advanced scripts

## Admin Screen Quality Gate

Every completed admin screen must pass:

- [ ] CRUD behavior
- [ ] authorization
- [ ] validation
- [ ] responsive behavior
- [ ] loading/disabled state
- [ ] empty state
- [ ] error state

Complete and approve one admin area before moving to the next.

---

# Phase 6 — Frontend Professionalization

## Goal

Professionalize the storefront after architecture/database/admin foundations are approved.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## 6A Global Layout

- [ ] header
- [ ] desktop navigation
- [ ] mobile navigation
- [ ] footer
- [ ] breadcrumb strategy
- [ ] notifications
- [ ] search
- [ ] account/cart indicators
- [ ] consistent spacing
- [ ] typography

## 6B Homepage

For each section verify:

- [ ] desktop
- [ ] tablet
- [ ] mobile
- [ ] empty data
- [ ] loading
- [ ] image ratios
- [ ] link behavior
- [ ] accessibility

## 6C Product Listing

- [ ] filters
- [ ] search
- [ ] sort
- [ ] pagination
- [ ] product cards
- [ ] empty state
- [ ] URL query persistence
- [ ] mobile filter UX

## 6D Product Detail

- [ ] gallery
- [ ] pricing
- [ ] stock
- [ ] quantity
- [ ] cart
- [ ] wishlist
- [ ] description/content
- [ ] related products
- [ ] SEO structured data

## 6E Cart

- [ ] quantity update
- [ ] remove
- [ ] coupon
- [ ] totals
- [ ] stock-change handling
- [ ] empty state
- [ ] mobile usability

## 6F Checkout

- [ ] address/customer data
- [ ] payment method
- [ ] order summary
- [ ] loading/disabled submit
- [ ] double-submit prevention
- [ ] validation UX
- [ ] success state
- [ ] payment failure state

## 6G Account

- [ ] dashboard
- [ ] profile
- [ ] addresses
- [ ] orders
- [ ] order detail
- [ ] wishlist
- [ ] affiliate presentation if retained

## Phase 6 Test Gate

At minimum test current supported desktop browser plus mobile viewport. Frontend approval requires responsive behavior, not desktop-only success.

---

# Phase 7 — Production Optimization

## Goal

Measure and optimize production performance, SEO, cache, assets, and observability after functionality is stable.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Checklist

- [ ] homepage query profile
- [ ] product list query profile
- [ ] product detail query profile
- [ ] header/menu query profile
- [ ] footer query profile
- [ ] eliminate N+1
- [ ] verify indexes against real filters/sorts
- [ ] eliminate unbounded production queries
- [ ] cache homepage composition where justified
- [ ] cache navigation where justified
- [ ] cache global settings with explicit invalidation
- [ ] image optimization
- [ ] lazy loading
- [ ] frontend build verification
- [ ] metadata/SEO review
- [ ] sitemap
- [ ] structured data
- [ ] canonical URLs
- [ ] 404 behavior
- [ ] cache headers where appropriate

## Performance Gate

Use measured baseline numbers rather than arbitrary targets.

Requirements:

- [ ] no known N+1
- [ ] no unbounded production collection for user-facing lists
- [ ] no stale settings/navigation cache
- [ ] critical page query counts are measured and documented

---

# Phase 8 — Release Gate

## Goal

Remove verified legacy code only after migrations/callers/tests are complete and prepare Website for production release.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Cleanup Checklist

- [ ] remove duplicate Website models with zero callers
- [ ] remove duplicate services with zero callers
- [ ] remove obsolete `Services/Services` tree after migration
- [ ] remove dead controllers
- [ ] remove dead views
- [ ] remove dead routes
- [ ] remove legacy setting keys after data migration
- [ ] remove legacy tables/columns only when approved and safe

## Verification Checklist

- [ ] Laravel Pint
- [ ] targeted PHPUnit tests
- [ ] full PHPUnit suite
- [ ] frontend build
- [ ] migration fresh test
- [ ] existing database upgrade test
- [ ] security regression
- [ ] payment regression
- [ ] checkout regression
- [ ] admin authorization regression
- [ ] responsive smoke test

## Documentation Gate

Update at minimum:

- `docs/modules/Website/ANALYSIS.md`
- `docs/modules/Website/INFORMATION.md`
- `docs/modules/Website/README.md`
- `docs/modules/Website/REFACTOR_PLAN.md`

---

# Working Protocol

For every phase:

```text
1. Analyze active phase
2. Freeze checklist
3. Implement only active phase scope
4. Run targeted tests
5. Report PASS / FAIL / REMAINING
6. User reviews results
7. User explicitly approves phase
8. Only then start the next phase
```

Rules:

- Do not skip phases without explicit approval.
- Do not opportunistically refactor unrelated areas.
- Do not delete legacy code before all callers migrate and tests pass.
- Do not redesign UI before the relevant backend/database foundation is stable.
- Preserve route names, Livewire aliases, database contracts, and user-facing behavior unless a phase explicitly authorizes a migration.
- Source code is the source of truth for current behavior.
- Tests and this document form the stage-gate record for the refactor.

# Next Action

Complete the Phase 0 runtime smoke test and automated checks. After the user approves Phase 0, begin **Phase 1A — Checkout Stabilization** only.