# Website Phase 1A — Implementation & Test Gate

## Status

- Phase: `1A — Checkout Stabilization`
- Analysis: `COMPLETE`
- Implementation: `COMPLETE`
- Automated runtime test: `PASS — 8 tests / 50 assertions`
- Manual payment smoke: `PENDING USER RUNTIME`
- Approval: `NOT APPROVED`
- Rule: do not start Phase 1B until this gate is tested and approved.

## Implemented Scope

### Payment allowlist

Server-side checkout accepts only:

```text
cod
bank_transfer
momo
```

`vnpay` and arbitrary payment methods are rejected.

### Transaction / stock correctness

`CheckoutService` now:

- locks the current cart row inside the transaction;
- locks all affected product rows before final stock validation;
- rechecks active state and stock after the locks are acquired;
- locks the coupon row before validating/incrementing usage;
- creates order, items, history, stock/coupon effects and cart cleanup inside one DB transaction;
- removes the old save-after-cart-delete defect;
- uses cart-row locking as the current-schema server-side double-submit guard;
- retries a transaction up to 3 times when Laravel detects retryable transactional conflicts.

### COD

- order starts as `pending`;
- no external gateway call;
- existing success flow is preserved.

### Manual bank transfer

- checkout UI now exposes `bank_transfer`;
- order starts as `pending_payment`;
- success page displays bank name, account number, account name, optional branch, amount and exact order code as transfer reference;
- bank information comes from environment/config, not hard-coded real account data;
- no automatic bank confirmation is claimed.

Environment keys:

```text
BANK_NAME=
BANK_ACCOUNT_NUMBER=
BANK_ACCOUNT_NAME=
BANK_BRANCH=
BANK_TRANSFER_INSTRUCTIONS=
```

### MoMo

The previous unsafe implementation was replaced.

Implemented:

- no hard-coded partner/access/secret credentials;
- no `withoutVerifying()` TLS bypass;
- no `dd()` payment failure path;
- config/env-driven endpoint and credentials;
- payment request uses the approved `captureWallet` request shape;
- customer is redirected only to a successful MoMo `payUrl`;
- browser redirect callback: `GET /checkout/momo-callback`;
- server-to-server IPN: `POST /checkout/momo-ipn`;
- CSRF exclusion is scoped only to the MoMo IPN route;
- response signature is verified with HMAC-SHA256;
- partner code, order identifier and amount are verified;
- successful callback changes a MoMo order from `pending_payment` to `pending` exactly once;
- repeated successful callbacks are idempotent;
- callback/IPN never recreates order, decrements stock, or increments coupon usage;
- failed/invalid results never mark an order paid;
- MoMo initialization failure keeps the already-created order as `pending_payment` and redirects to the order success/result screen rather than pretending checkout never happened.

Environment keys:

```text
MOMO_ENDPOINT=https://test-payment.momo.vn/v2/gateway/api/create
MOMO_PARTNER_CODE=
MOMO_ACCESS_KEY=
MOMO_SECRET_KEY=
MOMO_TIMEOUT=30
```

## Compatibility Decision

The current `wp_orders` schema has `payment_method` and `status` but no dedicated `payment_status` or payment transaction table.

Phase 1A deliberately does not redesign the database. Compatibility mapping is:

```text
COD                  -> status=pending
Bank transfer        -> status=pending_payment
MoMo before payment  -> status=pending_payment
MoMo verified paid   -> status=pending
```

A dedicated payment status/transaction model remains a Phase 3 database-design item.

## Files Changed

```text
Modules/Website/Config/website.php
Modules/Website/Http/Controllers/CheckoutController.php
Modules/Website/Http/Requests/CheckoutRequest.php
Modules/Website/Livewire/Checkout/CheckoutForm.php
Modules/Website/Models/Order.php
Modules/Website/Services/CheckoutService.php
Modules/Website/Services/MomoService.php
Modules/Website/resources/views/checkout/success.blade.php
Modules/Website/resources/views/livewire/checkout/checkout-form.blade.php
Modules/Website/routes/web.php
tests/Feature/Website/WebsiteCheckoutConfigurationTest.php
```

## Automated Runtime Results

User executed the Phase 1A runtime commands after pulling the implementation.

### Bootstrap/cache clear

```text
php artisan optimize:clear
PASS
```

### Checkout routes

```text
GET|HEAD checkout                    checkout.index
GET|HEAD checkout/momo-callback      checkout.momo.callback
POST     checkout/momo-ipn           checkout.momo.ipn
GET|HEAD checkout/success            checkout.success
```

Classification: `PASS — 4 expected checkout routes registered`.

### Focused checkout configuration tests

```text
PASS Tests\Feature\Website\WebsiteCheckoutConfigurationTest
✓ checkout accepts only approved payment methods
✓ momo routes map to real controller actions
✓ momo create payment uses config and returns pay url
✓ momo gateway failure throws controlled exception
✓ momo result signature is verified

Tests: 5 passed (20 assertions)
```

Classification: `PASS`.

### Existing Website route regression tests

```text
PASS Tests\Feature\Website\WebsiteRouteConfigurationTest
✓ blog index route uses controller action
✓ website admin routes keep admin auth middleware
✓ registered website admin pages use module livewire aliases

Tests: 3 passed (30 assertions)
```

Classification: `PASS`.

### Combined Website feature scope

```text
Tests: 8 passed (50 assertions)
```

Classification: `PASS`.

Automated Phase 1A regression status is therefore `PASS`. Manual payment/runtime behavior remains the only Phase 1A gate still open.

## Focused Automated Test Commands

For future regression runs:

```bash
php artisan optimize:clear
php artisan route:list --name=checkout
php artisan test tests/Feature/Website/WebsiteCheckoutConfigurationTest.php
php artisan test tests/Feature/Website/WebsiteRouteConfigurationTest.php
php artisan test tests/Feature/Website
```

The full repository suite remains a regression reference only; Phase 0 already recorded unrelated baseline failures.

## Manual Phase 1A Smoke Gate

### COD

- [ ] add product to cart
- [ ] checkout using COD
- [ ] exactly one order is created
- [ ] order status is `pending`
- [ ] stock decreases exactly once
- [ ] cart is empty after success
- [ ] refreshing/double-submit does not create a second order

### Bank transfer

- [ ] configure BANK_* environment values
- [ ] select bank transfer at checkout
- [ ] order status is `pending_payment`
- [ ] success page shows correct bank details
- [ ] amount equals order total
- [ ] transfer content equals exact order code
- [ ] no text claims automatic bank reconciliation

### MoMo test environment

- [ ] configure valid MoMo test credentials
- [ ] select MoMo
- [ ] app creates one `pending_payment` order
- [ ] browser redirects to MoMo `payUrl`
- [ ] successful MoMo result returns to Website
- [ ] valid result changes order to `pending`
- [ ] invalid/tampered callback is rejected
- [ ] amount mismatch is rejected
- [ ] duplicate valid callback does not repeat stock/coupon/order effects
- [ ] gateway initialization failure leaves one `pending_payment` order and displays controlled payment error

### Regression

- [ ] product/cart frontend still works
- [ ] account order list/detail still works
- [ ] Website admin Phase 0 smoke still passes

## Phase 1A Exit Gate

Phase 1A may be marked `TESTED / APPROVED` only after:

- [x] focused checkout configuration test passes: `5 tests / 20 assertions`;
- [x] existing Website route test passes: `3 tests / 30 assertions`;
- [x] combined Website feature scope passes: `8 tests / 50 assertions`;
- [ ] COD manual smoke passes;
- [ ] bank-transfer manual smoke passes;
- [ ] MoMo test-environment smoke is completed or an explicit external-credential blocker is recorded;
- [ ] no Phase 0 working behavior regresses;
- [ ] user explicitly approves Phase 1A.

## Current Decision

**Automated gate: PASS. Manual payment gate: PENDING. Phase 1A remains open.**
