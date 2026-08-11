# Website Phase 2C — Order Compatibility and Runtime Ownership

## Status

- Slice: `2C — Order compatibility and workflow ownership`
- Implementation: `COMPLETE`
- Automated tests: `PASS`
- Manual checkout/account smoke: `PENDING USER`
- Decision: `READY FOR USER TEST`

## Implemented

- Ported Phase 1A `pending_payment` status presentation to canonical Order.
- Ported the approved COD, bank-transfer and MoMo payment labels; removed VNPAY from the canonical Website-facing contract.
- Canonical OrderItem now relates to canonical Product rather than the duplicate Order Product model.
- Migrated Website CheckoutService order/item/history creation and locked product reads to canonical Order/Product models.
- Migrated MoMo service/callback, checkout success, account order pages, dashboard and active affiliate services to canonical Order.
- Updated checkout tests to construct canonical Order.
- Preserved the Phase 1A transaction, row locking, stock checks, cart guard, coupon update and MoMo idempotency logic without database changes.

## Remaining Compatibility Boundary

- Website Order seeders still reference duplicate models and will migrate before zero-caller cleanup.
- Dead/duplicate services under `Website/Services/Services` retain legacy references and are reserved for Slice 2E zero-caller cleanup.
- Canonical Order workflow currently consumes the existing Website CheckoutService orchestration. Moving the whole service class into Order is deferred until dependency direction can be changed without breaking Website-owned cart/coupon inputs.
- No duplicate model is deleted in this slice.

## Automated Gate

- Canonical Order table, relationships and Phase 1A status/payment accessor contracts.
- CheckoutService canonical namespace assertions.
- Canonical OrderItem-to-Product relationship assertion.
- Phase 1A checkout and MoMo regression.
- Full Website feature regression.

## Manual Test Required

- COD checkout and success page.
- Bank-transfer checkout, bank details and exact transfer reference.
- MoMo test payment redirect/callback where credentials are available.
- Account order list/detail and status/payment labels.
- Admin/dashboard recent orders and revenue summary.
- Affiliate order/commission screens.
