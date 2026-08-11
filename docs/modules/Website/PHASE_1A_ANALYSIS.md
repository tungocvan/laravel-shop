# Website Phase 1A — Checkout Stabilization Analysis

## Status

- Phase: `1A — Checkout Stabilization`
- Analysis: `COMPLETE`
- Implementation: `NOT STARTED`
- Tests: `NOT STARTED`
- Approval to implement: `PENDING`
- Scope locked to: `COD`, `Manual Bank Transfer`, `MoMo`

## Goal

Stabilize checkout/payment correctness without redesigning the whole Website architecture or database. Preserve the Phase 0 frontend/admin behavior while fixing hidden checkout defects and defining clear payment contracts.

## Payment Contract

### 1. COD

Business meaning:

- Customer places the order immediately.
- No external gateway callback is required.
- Order begins in a normal processing/pending state.
- Payment remains unpaid/pending until operational fulfillment confirms payment.

### 2. Manual Bank Transfer

Business meaning:

- Customer places the order immediately.
- Website shows configured bank information and a transfer reference based on the order code.
- Customer transfers manually outside the application.
- No automatic bank API reconciliation is assumed in Phase 1A.
- Order remains waiting for payment until staff/manual workflow confirms receipt.

### 3. MoMo

Business meaning:

- Customer creates an order/payment attempt and is redirected to MoMo.
- The application must verify MoMo response/callback server-side.
- Signature/result data must not be trusted from the browser alone.
- Callback processing must be idempotent: repeated valid callbacks must not create a second order or repeat stock/coupon effects.

## Current Source Findings

### F1A-01 — MoMo route points to a missing controller method

`Modules/Website/routes/web.php` registers:

```text
GET /checkout/momo-callback
checkout.momo.callback
CheckoutController@momoCallback
```

but `CheckoutController` currently defines only `index()` and `success()`.

Classification: `BROKEN BEFORE REFACTOR`.

### F1A-02 — MoMo service currently contains hard-coded test credentials

`MomoService::createPayment()` reads config properties in its constructor but then overrides them with hard-coded endpoint/partner/access/secret values inside the method.

This must not remain in production-oriented payment code.

### F1A-03 — MoMo service uses `withoutVerifying()`

The current HTTP request disables TLS certificate verification.

This is unacceptable for production payment traffic and must be removed from the production flow.

### F1A-04 — MoMo service contains `dd()` in failure paths

Gateway/network failures call `dd(...)`, which can expose payment/debug information and terminate the request unexpectedly.

Payment failures must instead return/throw controlled domain/application errors and be logged safely.

### F1A-05 — MoMo callback route name used by service does not match registered route

The current service calls:

```text
route('website.checkout.momo.callback')
```

while the registered route name is:

```text
checkout.momo.callback
```

This is another reason the current MoMo flow is not production-complete.

### F1A-06 — Checkout payment method is not server-side allowlisted

`CheckoutForm` exposes `payment_method`, but `CheckoutRequest::rules()` currently does not validate it.

A crafted Livewire/browser request can submit an arbitrary value.

Phase 1A must allow only:

```text
cod
bank_transfer
momo
```

### F1A-07 — Bank transfer is not present in the current checkout UI

Current payment UI exposes only COD and MoMo.

Phase 1A must add `bank_transfer` as the third supported option while preserving the current UI behavior.

### F1A-08 — Checkout maps unsupported legacy methods

`CheckoutService` currently maps `momo`, `vnpay`, and `bank_transfer` to `pending_payment`.

`vnpay` is outside the approved Phase 1A payment scope and must not remain an accidentally accepted hidden method.

### F1A-09 — Final stock validation occurs before the transaction

Current flow loads/checks products before entering `DB::transaction()`.

Another checkout can change stock between that check and the decrement.

### F1A-10 — Stock mutation does not use row locking

Current code decrements through `$item->product` without a final locked product read.

Two concurrent orders can potentially oversell.

### F1A-11 — Cart is saved after being deleted

Current cleanup sequence:

```text
cart items delete
cart delete
cart coupon_id = null
cart save
```

This is a correctness defect and must be removed.

### F1A-12 — Coupon usage update is inside the transaction but not guarded against duplicate checkout semantics

`usage_count` is incremented during order creation. The transaction protects rollback, but duplicate submissions/payment retries can still repeat business effects if the checkout workflow is invoked twice successfully.

### F1A-13 — Order code generation is application-level uniqueness only

`generateOrderCode()` loops until a code does not currently exist. This reduces normal collisions but cannot by itself guarantee uniqueness under concurrency unless the database also has an appropriate unique contract.

Phase 1A should inspect the real schema before changing it. Large schema redesign remains Phase 3.

### F1A-14 — Checkout success lookup uses session order code and direct Website Order model

This works in the current baseline but remains architectural debt. Phase 1A should avoid broad ownership migration; Phase 2 will move Order ownership to the canonical Order domain.

## Order Status vs Payment Status

The current schema/service visibly uses an `orders.status` value and `payment_method`; a dedicated `payment_status` field has not been confirmed by this analysis.

Target semantic model:

```text
Order status
- pending
- processing
- completed
- cancelled
...

Payment status
- pending
- paid
- failed
- cancelled
- refunded
...
```

Phase 1A rule:

- Do not perform a large database redesign merely to achieve this target.
- If the current schema lacks payment status/transaction storage required for safe MoMo behavior, introduce only the smallest corrective migration/contracts necessary for payment correctness.
- The professional final database ownership/model remains Phase 3.

## Target Checkout Flow

### Shared order validation

```text
Livewire CheckoutForm
    -> validate customer fields
    -> validate payment_method allowlist
    -> CheckoutService
    -> load current cart
    -> begin DB transaction
    -> lock final product rows
    -> recheck active state + stock
    -> calculate/freeze order totals
    -> create order + item snapshots
    -> decrement stock + increment sold count
    -> consume coupon atomically
    -> create order history
    -> clear/delete cart once
    -> commit
```

### COD

```text
create order
-> status pending
-> redirect checkout.success
```

No gateway call.

### Manual bank transfer

```text
create order
-> status pending_payment
-> redirect checkout.success
-> display bank information
-> display transfer reference = order code
```

No automatic callback in Phase 1A.

### MoMo

Recommended safe sequence for current architecture:

```text
validate checkout
-> create/freeze order safely
-> initialize MoMo payment request through MomoService
-> redirect customer to MoMo payUrl
-> MoMo returns/calls callback endpoint
-> verify signature + partner/order/amount/result
-> idempotently update payment/order state
-> redirect/show result
```

Implementation must avoid repeating stock/coupon/order creation when MoMo callback is retried.

## MoMo Security Requirements

- Use values from configuration/environment only; no hard-coded secret/access credentials in source.
- Never expose secret key to client-side code.
- Do not use `withoutVerifying()` in production flow.
- No `dd()` in gateway integration.
- Validate/verify signature according to the selected MoMo API contract.
- Verify returned order identifier belongs to an existing MoMo order attempt.
- Verify amount matches the frozen order total.
- Verify result/status before marking paid.
- Repeated callback must be safe/idempotent.
- Log sanitized gateway failures; do not log secret keys or raw sensitive credentials.

## Manual Bank Transfer Configuration

Phase 1A should support configuration fields equivalent to:

```text
bank_name
account_number
account_name
branch (optional)
transfer_instructions (optional)
```

The success page should show these only when the order payment method is `bank_transfer`, along with:

```text
Transfer content/reference: <order_code>
Amount: <order.total>
```

Do not hard-code real banking data into Blade/source if the project already has a settings/config mechanism suitable for runtime configuration.

## Implementation Checklist

### A. Validation

- [ ] Add server-side allowlist for `payment_method`: `cod,bank_transfer,momo`.
- [ ] Reject unsupported `vnpay` or arbitrary values.
- [ ] Keep existing customer/contact validation behavior.

### B. Checkout transaction

- [ ] Move final product reads/validation into the transaction.
- [ ] Lock product rows before stock check/decrement.
- [ ] Use locked product instances for stock mutation.
- [ ] Keep order/item/coupon/history/cart effects atomic.
- [ ] Remove cart save-after-delete.
- [ ] Ensure rollback restores all transactional effects.

### C. Duplicate submission protection

- [ ] Ensure one logical checkout cannot create repeated successful orders from double-submit/retry.
- [ ] Retain Livewire loading/disabled UI but do not rely on UI alone.
- [ ] Define a server-side idempotency/checkout guard compatible with current schema.

### D. COD

- [ ] Preserve current working COD behavior.
- [ ] Initial order state remains normal pending workflow.
- [ ] Redirect to success page after successful creation.

### E. Bank transfer

- [ ] Add checkout option `bank_transfer`.
- [ ] Initial order state = waiting for payment.
- [ ] Show configurable bank details on success page.
- [ ] Show exact order code as transfer reference.
- [ ] No fake automatic payment confirmation.

### F. MoMo

- [ ] Implement real `CheckoutController@momoCallback` contract or a focused callback controller while preserving public route compatibility.
- [ ] Fix route name usage.
- [ ] Remove hard-coded credentials.
- [ ] Use config/env endpoint/credentials.
- [ ] Remove TLS bypass.
- [ ] Remove `dd()` failure paths.
- [ ] Handle API/network errors safely.
- [ ] Generate MoMo payment request after a valid order/payment intent exists.
- [ ] Redirect to returned MoMo `payUrl` only after response validation.
- [ ] Verify callback signature/result/order/amount.
- [ ] Make callback idempotent.
- [ ] Do not repeat stock/coupon/order side effects in callback.

### G. Compatibility boundaries

- [ ] Do not migrate Product/Post/Order ownership in Phase 1A.
- [ ] Do not rebuild the Website database in Phase 1A.
- [ ] Do not redesign Website admin/frontend styling.
- [ ] Do not rewrite malformed legacy migrations.
- [ ] Preserve existing route names where practical.

## Focused Automated Test Plan

Create Website checkout tests sufficient to protect the active code changes.

### Core checkout

- [ ] COD valid checkout creates one order.
- [ ] Empty cart rejected.
- [ ] Inactive product rejected.
- [ ] Insufficient stock rejected.
- [ ] Successful checkout decrements stock exactly once.
- [ ] Successful checkout increments sold count exactly once.
- [ ] OrderItem stores snapshot name/price/quantity/total.
- [ ] Coupon usage increments exactly once.
- [ ] Cart/items are removed exactly once.
- [ ] Transaction failure leaves order/items/stock/coupon/cart consistent.

### Payment method validation

- [ ] `cod` accepted.
- [ ] `bank_transfer` accepted.
- [ ] `momo` accepted.
- [ ] arbitrary payment method rejected.
- [ ] `vnpay` rejected unless separately approved in a future phase.

### Concurrency/idempotency

- [ ] Two competing checkouts cannot both consume stock=1.
- [ ] Double-submit/retry cannot create two logical orders for the same checkout action.

### Bank transfer

- [ ] Bank-transfer order starts waiting for payment.
- [ ] Success page shows configured bank details only for bank transfer.
- [ ] Transfer content includes exact order code.

### MoMo

- [ ] Payment create request uses config/env values.
- [ ] Gateway success produces/uses `payUrl`.
- [ ] Gateway/network failure is handled without `dd()`.
- [ ] Valid callback marks payment/order appropriately.
- [ ] Invalid signature rejected.
- [ ] Amount mismatch rejected.
- [ ] Unknown order rejected.
- [ ] Failed MoMo result does not mark paid.
- [ ] Duplicate valid callback is idempotent.

### Regression

- [ ] Existing `WebsiteRouteConfigurationTest` remains green.
- [ ] Phase 0 manual frontend/admin baseline remains functional after deployment to test runtime.

## Phase 1A Gate

Phase 1A analysis is considered complete when this document is reviewed.

Implementation may start only within the checklist above. After implementation:

1. Run focused checkout/payment tests.
2. Run existing Website route tests.
3. Run relevant broader tests and distinguish unrelated baseline failures.
4. Run manual checkout smoke for COD, bank transfer and MoMo test environment.
5. Report `PASS / FAIL / REMAINING`.
6. Wait for explicit user approval before entering Phase 1B.

## Decision

**Recommended: IMPLEMENT Phase 1A using this locked scope.**

The highest-priority fixes are transaction/stock correctness, payment-method validation, removal of unsafe MoMo implementation details, and a real idempotent MoMo callback. Manual bank transfer should be added as a first-class supported payment option without pretending that it is automatically reconciled.
