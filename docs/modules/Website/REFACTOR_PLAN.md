# Website Refactor Plan

## Purpose

This document is the canonical staged refactor roadmap for `Modules/Website`.

The Website module will be improved in controlled phases. Each phase must be analyzed, implemented, tested, and approved before moving to the next phase.

Application source must not be changed outside the active phase scope.

## Status Legend

- `[ ] NOT STARTED`
- `[x] ANALYZED`
- `[x] IMPLEMENTED`
- `[x] TESTED`
- `[x] APPROVED`

## Current Overall Status

- Phase 0 — Baseline & Safety Net: `[x] ANALYZED / TESTED / APPROVED — CLOSED`
- Phase 1 — Stabilize & Security: `[x] IMPLEMENTED / TESTED / APPROVED — CLOSED`
- Phase 2 — Domain Ownership: `[x] ANALYZED — implementation approval pending`
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
- [x] IMPLEMENTED — documentation/baseline artifacts completed
- [x] TESTED
- [x] APPROVED
- Decision: `CLOSED`

## Exit Evidence

- Static Website inventory completed.
- Routes/controllers/Livewire/services/models/database reviewed.
- Cross-module and database debt recorded.
- Known pre-refactor defects registered in `PHASE_0_BASELINE.md`.
- `WebsiteRouteConfigurationTest`: `PASS — 3 tests / 30 assertions`.
- Full repository suite executed: `44 failed / 51 passed`; unrelated repository-wide debt recorded rather than attributed to Website.
- Frontend manual smoke test: `PASS` (user verified).
- Admin/backend manual smoke test: `PASS` (user verified).
- No additional manual runtime defects reported.

Canonical Phase 0 evidence:

- `docs/modules/Website/PHASE_0_BASELINE.md`
- `docs/modules/Website/PHASE_0_SMOKE_TEST.md`

The working frontend/admin behavior observed in Phase 0 is now the regression baseline for all later phases.

---

# Phase 1 — Stabilize & Security

## Goal

Fix current high-risk correctness/security defects before architectural migration or UI rebuilding.

## Status

- [x] ANALYZED
- [x] IMPLEMENTED
- [x] TESTED
- [x] APPROVED
- Active sub-phase: `None — Phase 1 closed`

## Phase 1A — Checkout Stabilization

### Scope

Only checkout/payment correctness and the focused tests required to protect those changes. Do not perform Phase 2 domain migration, Phase 3 database redesign, or UI redesign in Phase 1A.

### Checklist

- [ ] Re-inspect checkout routes, controller, Livewire checkout flow, CheckoutService, CartService, Order/OrderItem/OrderHistory usage, Coupon usage, product stock mutation, and payment configuration.
- [ ] Resolve broken MoMo callback contract.
- [ ] Ensure callback maps to a real controller/service flow.
- [ ] Verify payment callback/signature server-side when MoMo is an active supported payment method; otherwise explicitly disable/remove the broken public contract rather than leaving a dead route.
- [ ] Move final stock verification inside transaction.
- [ ] Lock product/inventory rows before final stock check.
- [ ] Prevent overselling.
- [ ] Make order creation atomic.
- [ ] Make coupon usage atomic.
- [ ] Remove save-after-cart-delete defect.
- [ ] Prevent duplicate orders/double submit.
- [ ] Define retry/idempotency behavior appropriate to the current schema/contracts.
- [ ] Preserve existing working COD behavior.
- [ ] Add focused checkout regression tests before/together with risky changes.
- [ ] Do not rewrite legacy migrations in this phase.

### Phase 1A Test Gate

- [ ] normal COD checkout
- [ ] empty cart
- [ ] inactive product
- [ ] insufficient stock
- [ ] concurrent checkout with stock=1
- [ ] valid coupon
- [ ] invalid/expired coupon
- [ ] double submit
- [ ] valid payment callback when MoMo is supported
- [ ] tampered callback when MoMo is supported
- [ ] duplicate callback when MoMo is supported
- [ ] rollback consistency
- [ ] Phase 0 Website route regression test remains green
- [ ] frontend/admin Phase 0 smoke baseline remains intact

### Phase 1A Approval Gate

Do not start Phase 1B until Phase 1A implementation has been tested and explicitly approved by the user.

## Phase 1B — Admin Authorization

- [ ] Define capability permission matrix.
- [ ] Keep `auth:admin`.
- [ ] Add named capability checks.
- [ ] Homepage mutation authorization.
- [ ] Header/menu mutation authorization.
- [ ] Footer mutation authorization.
- [ ] Banner mutation authorization.
- [ ] Coupon mutation authorization.
- [ ] Flash-sale mutation authorization.
- [ ] Customer mutation authorization.
- [ ] Affiliate mutation authorization.
- [ ] Authorize inside sensitive Livewire methods.
- [ ] Add allowed/denied tests.

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

- [x] Centralize production Website settings mutations.
- [x] Fix cache invalidation consistency.
- [x] Remove direct DB/model queries from frontend Blade.
- [x] Restrict custom script editing to privileged capability.
- [x] Review raw HTML/script rendering.
- [x] Validate image upload MIME/extension/size.
- [x] Define old-file cleanup policy.
- [x] Add settings/cache/security tests (runtime DB checks require PDO SQLite).

Phase 1C implementation evidence: `docs/modules/Website/PHASE_1C_IMPLEMENTATION.md`.

## Phase 1 Approval Gate

Do not enter Phase 2 until:

- checkout tests pass;
- authorization tests pass;
- settings/cache tests pass;
- Phase 0 working behavior remains intact except explicitly repaired baseline defects;
- user explicitly approves Phase 1.

---

# Phase 2 — Domain Ownership

## Goal

Make every business concept have one canonical module owner.

## Status

- [x] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

Canonical analysis and locked slice sequence: `docs/modules/Website/PHASE_2_ANALYSIS.md`.

## Ownership Checklist

### Product

- [ ] canonical owner = `Modules/Product`
- [x] identify every caller of Website product model/service
- [ ] migrate Website storefront to Product contracts
- [ ] remove duplicate Product ownership only after callers migrate

### Category

- [ ] canonical owner = `Modules/Category`
- [x] remove direct `DB::table('categories')` access from Website UI
- [x] consume Category query/service contract for read-only Website callers

Slice 2A implementation evidence: `docs/modules/Website/PHASE_2A_IMPLEMENTATION.md`.

### Post

- [x] canonical owner = `Modules/Post`
- [x] Website blog becomes presentation/composition layer
- [x] migrate runtime storefront model/query ownership

Slice 2B implementation evidence: `docs/modules/Website/PHASE_2B_IMPLEMENTATION.md`.

### Order

- [x] canonical owner = `Modules/Order`
- [ ] order creation service class belongs to canonical Order workflow
- [x] account order queries use Order owner
- [x] migrate active Website Order/OrderItem/OrderHistory runtime callers

Slice 2C implementation evidence: `docs/modules/Website/PHASE_2C_IMPLEMENTATION.md`.

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
-> Website Service
-> canonical domain services
-> Blade
```

## Target Admin Flow

```text
Admin route
-> page/controller
-> Livewire
-> Website service
-> canonical owner/model
-> database
```

## Phase 4 Test Gate

- [ ] focused service tests
- [ ] Livewire contains no business transaction
- [ ] controllers remain thin
- [ ] Blade performs no database access
- [ ] query-count regression reviewed
- [ ] previous phase tests remain green

---

# Phase 5 — Website Admin CMS

## Goal

Rebuild Website administration into a coherent professional CMS/storefront management surface.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Admin Information Architecture

Website admin should focus on Website-owned concepts:

```text
Website
├── Dashboard
├── Homepage
├── Menus
├── Banners
├── Footer
├── SEO
├── Theme
└── Settings
```

Product, Order, Customer, marketing and other canonical domains should appear under their own owning admin areas even if rendered in the same admin shell.

## Homepage Builder

- [ ] section list
- [ ] add section
- [ ] edit section
- [ ] enable/disable
- [ ] reorder
- [ ] duplicate
- [ ] delete confirmation
- [ ] preview
- [ ] loading state
- [ ] empty state
- [ ] validation state
- [ ] responsive admin behavior

Candidate section editors:

- Hero
- Categories
- Featured Products
- New Arrivals
- Best Sellers
- Flash Sale
- Promo Banner
- Blog
- Trust Badges
- Newsletter

## Menu Manager

- [ ] menu list
- [ ] nested menu items
- [ ] reorder
- [ ] internal/external reference selector
- [ ] validation
- [ ] mobile behavior

## Banner Manager

- [ ] desktop preview
- [ ] mobile preview
- [ ] scheduling
- [ ] active state
- [ ] ordering
- [ ] image validation

## Footer Manager

- [ ] column builder
- [ ] links
- [ ] social links
- [ ] reorder

## SEO Manager

- [ ] global SEO
- [ ] page SEO
- [ ] OpenGraph preview
- [ ] canonical URL
- [ ] robots configuration

## Theme / Settings

- [ ] logo
- [ ] favicon
- [ ] contact/brand configuration
- [ ] analytics
- [ ] restricted advanced scripts

## Per-Screen Gate

Every admin screen must pass:

```text
CRUD PASS
Authorization PASS
Validation PASS
Responsive PASS
Loading PASS
Empty state PASS
Error state PASS
```

---

# Phase 6 — Frontend Professionalization

## Goal

Professionalize storefront UX only after architecture/database/admin foundations are stable.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Global Layout

- [ ] header
- [ ] desktop navigation
- [ ] mobile navigation
- [ ] footer
- [ ] breadcrumbs
- [ ] notifications
- [ ] search
- [ ] account/cart indicators
- [ ] spacing system
- [ ] typography

## Homepage

For every section:

- [ ] desktop
- [ ] tablet
- [ ] mobile
- [ ] empty state
- [ ] loading state
- [ ] image aspect ratio
- [ ] links/actions
- [ ] accessibility

## Product Listing

- [ ] filters
- [ ] search
- [ ] sorting
- [ ] pagination
- [ ] product card
- [ ] empty state
- [ ] URL query persistence
- [ ] mobile filters

## Product Detail

- [ ] gallery
- [ ] pricing
- [ ] stock display
- [ ] quantity
- [ ] cart
- [ ] wishlist
- [ ] description
- [ ] related products
- [ ] structured data

## Cart

- [ ] quantity update
- [ ] remove
- [ ] coupon
- [ ] totals
- [ ] stock changes
- [ ] empty state
- [ ] mobile

## Checkout

- [ ] address/customer information
- [ ] payment method
- [ ] order summary
- [ ] disabled/loading submit
- [ ] double-submit UX protection
- [ ] validation UX
- [ ] success
- [ ] payment failure

## Account

- [ ] dashboard
- [ ] profile
- [ ] addresses
- [ ] orders
- [ ] order detail
- [ ] wishlist
- [ ] affiliate UI if retained

## Phase 6 Test Gate

Test at minimum desktop Chrome plus representative mobile viewport. Working desktop behavior alone is not sufficient.

---

# Phase 7 — Production Optimization

## Goal

Optimize only after behavior and architecture are stable.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Checklist

- [ ] homepage query profile
- [ ] product-list query profile
- [ ] product-detail query profile
- [ ] header/menu query profile
- [ ] footer query profile
- [ ] remove N+1 queries
- [ ] verify indexes
- [ ] bounded queries/pagination
- [ ] homepage composition caching
- [ ] navigation caching
- [ ] global settings caching
- [ ] explicit cache invalidation
- [ ] image optimization
- [ ] lazy image loading
- [ ] asset build review
- [ ] sitemap
- [ ] structured data
- [ ] canonical URLs
- [ ] 404 behavior
- [ ] cache headers where appropriate

## Performance Gate

Set measurable budgets only after collecting a real baseline. Do not invent arbitrary performance numbers before measurement.

---

# Phase 8 — Release Gate

## Goal

Remove obsolete compatibility code only after all callers have migrated and complete the production release checklist.

## Status

- [ ] NOT STARTED
- [ ] ANALYZED
- [ ] IMPLEMENTED
- [ ] TESTED
- [ ] APPROVED

## Cleanup

- [ ] remove duplicate Website models with zero callers
- [ ] remove duplicate services with zero callers
- [ ] remove `Services/Services` after ownership migration
- [ ] remove dead controllers
- [ ] remove dead views
- [ ] remove dead routes
- [ ] remove legacy settings keys after migration
- [ ] remove obsolete columns/tables only when safe

## Release Verification

- [ ] Pint
- [ ] focused Website tests
- [ ] full PHPUnit suite with unrelated baseline failures distinguished
- [ ] frontend build
- [ ] migrate fresh test
- [ ] existing database upgrade test
- [ ] security regression
- [ ] payment regression
- [ ] checkout regression
- [ ] documentation refresh

## Documentation

Final Website documentation must keep these synchronized:

- `docs/modules/Website/ANALYSIS.md`
- `docs/modules/Website/INFORMATION.md`
- `docs/modules/Website/README.md`
- `docs/modules/Website/REFACTOR_PLAN.md`

---

# Stage-Gate Working Rule

For every phase:

```text
ANALYZE
   ↓
LOCK CHECKLIST
   ↓
IMPLEMENT ONLY ACTIVE PHASE
   ↓
TEST
   ↓
REPORT PASS / FAIL / REMAINING
   ↓
USER APPROVAL
   ↓
NEXT PHASE
```

Rules:

1. Do not silently move into another phase.
2. Do not delete legacy code before callers migrate and tests pass.
3. Do not redesign database during an unrelated phase.
4. Do not treat an old baseline defect as a new refactor regression.
5. Preserve backward compatibility unless a breaking change is explicitly approved.
6. When a phase fails its gate, fix that phase before proceeding.
