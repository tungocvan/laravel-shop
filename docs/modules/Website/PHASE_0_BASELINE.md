# Website Phase 0 — Baseline & Safety Net

## Status

- Phase: `0 — Baseline & Safety Net`
- Static analysis: `PASS`
- Automated runtime baseline: `EXECUTED — PARTIAL PASS`
- Website route regression test: `PASS — 3 tests / 30 assertions`
- Full repository test suite: `FAIL — 44 failed, 51 passed / 340 assertions` (repository-wide baseline debt recorded)
- Frontend manual smoke test: `PASS — user verified`
- Admin/backend manual smoke test: `PASS — user verified`
- Approval gate: `APPROVED`
- Next phase: `Phase 1A — Checkout Stabilization`

## Purpose

This document freezes the current behavior and known defects of `Modules/Website` before refactoring. It is a regression reference, not an implementation plan. Known defects documented here must not later be misclassified as refactor regressions.

## Baseline Scope

### Frontend

- Homepage / Help
- Product listing / detail
- Blog listing / detail
- Login / Register / Logout
- Cart / Checkout / Checkout success
- Account dashboard / profile / orders / order detail / wishlist / affiliate

### Admin Website

- Homepage settings
- Header settings
- Footer settings
- Banner management
- Flash sale management
- Coupon management
- Customer management
- Affiliate management

## Current Module Dependencies

`Modules/Website/Config/module.php` declares Website as a domain module with dependencies on User, Product, Category, Post and Order. Later ownership cleanup should make Website consume canonical domain owners rather than duplicate them.

## Current Architecture Baseline

Observed flow patterns include `Route -> Controller -> Page Blade -> Livewire -> Service -> Model -> Database`, but current source also contains bypasses: controllers/Livewire query models directly, Livewire can use `DB::table()`, Blade layout reads `Setting` directly, Website duplicates Product/Post/Order/Category/account models, and system-like Database/Env/Auth/Chat services exist under `Services/Services`.

These are architectural debt for later phases, not Phase 0 changes.

## Known Broken Before Refactor

### B0-01 — MoMo callback route/controller mismatch

Status: `BROKEN BEFORE REFACTOR`

`checkout.momo.callback` is registered to `CheckoutController@momoCallback`; static inspection found no matching method.

Expected Phase: `1A — Checkout Stabilization`.

### B0-02 — Checkout cart lifecycle defect

Status: `BROKEN BEFORE REFACTOR`

Current checkout workflow deletes the cart and then mutates/saves that deleted model.

Expected Phase: `1A — Checkout Stabilization`.

### B0-03 — Checkout concurrency risk

Status: `KNOWN CORRECTNESS RISK`

Stock is validated before the transaction and decremented later without an observed row-locking strategy. Concurrent checkout may oversell.

Expected Phase: `1A — Checkout Stabilization`.

### B0-04 — Admin authorization is incomplete

Status: `KNOWN SECURITY RISK`

Website admin routes generally require `auth:admin`, but capability-specific authorization is not consistently enforced at route and Livewire mutation boundaries.

Expected Phase: `1B — Authorization`.

### B0-05 — Settings cache can become stale

Status: `KNOWN CORRECTNESS RISK`

`Setting::getValue()` uses long-lived cache, while some persistence paths can bypass centralized invalidation.

Expected Phase: `1C — Settings / Cache / XSS`.

### B0-06 — Raw configurable header script

Status: `KNOWN SECURITY / GOVERNANCE RISK`

Frontend layout renders stored `header_script` as raw HTML. Treat as privileged configuration and review authorization/sanitization policy.

Expected Phase: `1C — Settings / Cache / XSS`.

### B0-07 — Homepage automated test cannot boot against clean test database

Status: `RUNTIME BASELINE DEFECT / TEST INFRASTRUCTURE`

The repository `Tests\Feature\ExampleTest` requests `/` and receives HTTP 500 because `Modules\Website\Models\Setting::getValue()` queries `wp_settings`, but that table is absent in the test database (`SQLSTATE ... no such table: wp_settings`). This means the homepage currently lacks an isolated clean-database test baseline. It does not prove the deployed homepage is broken: the manual frontend smoke test passed against the user's current runtime environment.

Expected handling: establish Website database/test fixtures before risky frontend/settings refactors; database ownership itself remains Phase 3.

## Confirmed Improvements Already Present

### C0-01 — Blog route closure

`blog.index` currently resolves to `PostController@index`; the Website route regression test protects this behavior.

### C0-02 — Cart item ownership lookup

Current `CartService` resolves update/remove operations through the current cart relationship via `getCartItemForCurrentCart()`.

## Domain Ownership Debt Baseline

Website duplicates Product/Category/Post/Order concepts and contains account/customer behavior. Target canonical owners to verify in Phase 2 are Product, Category, Post, Order and User/Account. System-like `Database`, `Env`, `AuthService` and `ChatService` code under Website must also be reassigned only after ownership contracts are confirmed. No file was deleted in Phase 0.

## Database Baseline

Website currently contains persistence for CMS/settings/banner/header/footer/social data, commerce/marketing data such as carts/coupons/flash sales, and engagement/account data such as newsletters/reviews/wishlists/affiliate. Legacy `-0001_11_30_*` migration filenames remain untouched until an explicit Phase 3 migration strategy exists.

## Automated Runtime Baseline — Executed

### Cache/bootstrap clear

`php artisan optimize:clear`: `PASS`.

### Route registration

`php artisan route:list`: `PASS`, showing 123 routes. Relevant Website routes are registered, including homepage, account, Website admin pages, blog, cart, checkout, MoMo callback, help, login, product, register and Website logout.

Important: route registration only proves the route/action string is registered; it does not prove the target controller method exists or the request succeeds.

### Dedicated Website regression test

```text
PASS Tests\Feature\Website\WebsiteRouteConfigurationTest
✓ blog index route uses controller action
✓ website admin routes keep admin auth middleware
✓ registered website admin pages use module livewire aliases

Tests: 3 passed (30 assertions)
```

Classification: `PASS`.

### Full repository suite

```text
Tests: 44 failed, 51 passed (340 assertions)
Duration: 11.91s
```

Classification: `FAIL — BASELINE REPOSITORY DEBT RECORDED`.

The failures are not all Website failures and must not block Website work indiscriminately. Observed failing groups include PromptEngine, Admin route permission expectations, Admission, Invoices, Muasamcong, Pharma and the generic homepage ExampleTest.

For Website Phase 0, the directly relevant automated runtime failure is the homepage test's missing `wp_settings` table described as B0-07. The dedicated Website route suite itself passes, and the deployed/manual frontend smoke test passed.

## Manual Smoke Baseline — Completed

The user re-tested the current Website in the runtime environment and confirmed:

```text
Frontend: PASS
Admin/backend: PASS
```

This manual result establishes the user-visible pre-refactor behavior baseline. It does not invalidate source-confirmed hidden risks B0-01 through B0-07; those remain registered work items for their planned phases.

No additional manual runtime defect was reported during this smoke pass.

## Repository-Wide Failures Outside Website Scope

The following are recorded so they are not later attributed to Website refactoring unless a causal diff proves otherwise:

- PromptEngine unit/feature failures.
- Admin route permission expectation failures.
- Admission route/API/seeder/permission failures.
- Invoices feature failures.
- Muasamcong feature failures.
- Pharma import/export fixture failures.

These belong to their owning modules/projects and are not Phase 0 Website repair tasks.

## Phase 0 Exit Gate

- [x] Static inventory completed.
- [x] Routes/controllers/Livewire/services/models/database reviewed.
- [x] Cross-module debt mapped.
- [x] Known pre-refactor defects recorded.
- [x] Existing Website tests inventoried.
- [x] Website-specific route test executed successfully: `3 passed / 30 assertions`.
- [x] Full current test suite executed: `44 failed / 51 passed`; blockers/debt recorded.
- [x] Runtime-only automated homepage defect added as B0-07.
- [x] Frontend manual smoke test completed: `PASS`.
- [x] Admin/backend manual smoke test completed: `PASS`.
- [x] No additional manual runtime defects reported.
- [x] User explicitly approved continuing after successful smoke verification.

## Phase 0 Decision

**PHASE 0: APPROVED / CLOSED**

The baseline is now frozen. Phase 1 may begin, starting with `Phase 1A — Checkout Stabilization`.

Any later regression must be compared against this baseline. B0-01 through B0-07 are pre-existing findings and must not be attributed to the refactor unless a later change worsens them.

## Change Rules After Phase 0

- Phase 0 documentation is the regression baseline.
- Do not silently expand an active phase into another phase.
- Preserve working frontend/admin behavior unless an approved phase explicitly changes it.
- Add focused regression tests before or together with risky Phase 1 changes.
- Do not rewrite legacy migrations during Phase 1.
