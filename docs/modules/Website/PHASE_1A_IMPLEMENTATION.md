# Website Phase 1A — Implementation & Test Gate

## Status

- Phase: `1A — Checkout Stabilization`
- Analysis: `COMPLETE`
- Implementation: `COMPLETE`
- Automated runtime test: `PASS — 8 tests / 50 assertions`
- Manual payment smoke: `PASS — user verified`
- Regression smoke: `PASS — user verified`
- Approval: `APPROVED`
- Decision: `CLOSED`
- Next: `Phase 1B — Admin Authorization`

## Implemented Scope

### Payment allowlist
Server-side checkout accepts only `cod`, `bank_transfer`, and `momo`. Unsupported values including `vnpay` are rejected.

### Transaction / stock correctness
`CheckoutService` now locks the cart row, product rows, and coupon row inside the transaction; rechecks stock after lock acquisition; keeps order/items/history/stock/coupon/cart cleanup atomic; removes save-after-delete; and uses cart-row locking as the current-schema double-submit guard.

### COD
- Order starts as `pending`.
- No external gateway call.
- Existing success flow preserved.

### Manual bank transfer
- Checkout option added.
- Order starts as `pending_payment`.
- Success page shows configured bank details, amount, and exact order code as transfer reference.
- No automatic bank reconciliation is claimed.

### MoMo
- Removed hard-coded credentials, TLS bypass, and `dd()` failure paths.
- Uses environment/config endpoint and credentials.
- Uses gateway `payUrl`.
- Browser callback: `GET /checkout/momo-callback`.
- Server IPN: `POST /checkout/momo-ipn`.
- IPN CSRF exclusion is scoped only to that route.
- Verifies HMAC signature, partner code, order identifier, amount, and result.
- Successful repeated callbacks are idempotent.
- Callback/IPN never repeats order creation, stock decrement, or coupon usage.

## Compatibility Decision

Current `wp_orders` has `payment_method` and `status`, but no dedicated `payment_status`/payment transaction table. Phase 1A intentionally keeps the compatibility mapping:

```text
COD                  -> status=pending
Bank transfer        -> status=pending_payment
MoMo before payment  -> status=pending_payment
MoMo verified paid   -> status=pending
```

Dedicated payment persistence remains a Phase 3 database-design item.

## Automated Runtime Evidence

```text
WebsiteCheckoutConfigurationTest
5 passed / 20 assertions

WebsiteRouteConfigurationTest
3 passed / 30 assertions

Combined Website Feature scope
8 passed / 50 assertions
```

Checkout routes verified:

```text
GET  checkout
GET  checkout/momo-callback
POST checkout/momo-ipn
GET  checkout/success
```

## Manual Runtime Evidence

The user executed the Phase 1A manual checkout/payment smoke and reported `PASS`.

Accepted runtime gate result:

- COD: `PASS`
- Manual bank transfer: `PASS`
- Payment/checkout frontend behavior: `PASS`
- Regression behavior required to continue: `PASS`

No new Phase 1A runtime defect was reported.

## Exit Gate

- [x] Analysis complete.
- [x] Implementation complete.
- [x] Payment-method validation protected.
- [x] Transaction/stock/cart correctness implementation complete.
- [x] Focused checkout configuration test: PASS.
- [x] Existing Website route test: PASS.
- [x] Combined Website feature tests: PASS.
- [x] Manual payment smoke: PASS, user verified.
- [x] No regression reported against Phase 0 working behavior.
- [x] User approved continuing.

## Decision

**PHASE 1A: TESTED / APPROVED / CLOSED**

Phase 1B may now begin. Phase 1B scope is Admin Authorization only; it must not expand into Phase 2 domain ownership or Phase 3 database redesign.
