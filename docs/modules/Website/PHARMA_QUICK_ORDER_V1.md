# Pharma Quick Order V1 — implementation checkpoint

Base: main at `0c0cafe54887f7b76cf16458a0d3dfbd1bad4550` (PR #286).
Branch: `feat/website-pharma-quick-order`.

## Read-only audit findings

- `Modules/Website/routes/web.php` uses the public `web` group, `auth` for account pages and `auth:admin` plus permission middleware for admin pages.
- `Modules/Pharma/Models/PriceList` defines global/customer scopes, active date range and user-restricted global lists.
- `PriceListItem` stores medicine/variant/package identity and several distinct price fields; selecting a sale price requires an explicit business decision.
- `UserOrderAuthoringService` provides `priceLists()` and `createDraft()` for the existing inventory issue order workflow, but its manager-centric access is not automatically Website customer authorization.

## Initial implementation

`PharmaQuickOrderPriceListService::eligibleForCustomer()` provides a fail-closed query for an explicitly selected Admin allowlist, active dates, global-user restrictions and partner-scoped customer lists.

This is an internal building block only: **no public route, price endpoint, cart, admin settings, or order submission has been enabled**. In particular, a caller must prove that the authenticated Website user is authorized to act for the supplied customer partner before using this query. Do not accept customerPartnerId from an untrusted request without this check.

## Gates before exposing Quick Order

1. Audit actual Website user-to-customer relation, permission registration and Pharma-only template selection.
2. Resolve `official_facility` customer lists explicitly (not included in the initial partner-only query).
3. Decide which price field is authoritative and VAT treatment; never return cost fields.
4. Confirm selection/priority when multiple eligible price lists contain the same medicine variant/package.
5. Bind Admin settings to the existing settings service, with server-side permission checks.
6. Reuse Pharma order draft and approval workflow with server-side repricing and idempotency.
7. Add database feature tests for tenant isolation, authorization, validity, price selection, cart and order lifecycle before exposing the route.

No test PASS or UI PASS is claimed for this checkpoint.


## P3 Admin Settings checkpoint (2026-10-14)

- Admin GET/PUT `/admin/website/pharma-quick-order`, guarded by `auth:admin` and `permission:website.settings.manage,admin`.
- Settings key `website.pharma_quick_order` stores `enabled` (default false) and `price_list_ids` (default empty); empty selection fails closed.
- Save accepts only existing price lists in `active` status; effective dates and per-user/per-partner eligibility MUST be rechecked when serving products and submitting orders.
- No public quick-order endpoint or order creation exists at this checkpoint. UI/test acceptance is pending.


## P4 gated frontend shell

- `/quick-order` is authenticated, Pharma-template-only, settings-enabled and requires the dedicated `website.pharma.quick_order.view` permission on the web guard.
- Until a canonical Website user -> Pharma partner/customer mapping is audited and implemented, the page displays a safe unlinked-customer state and never queries or exposes price-list items.
- P4 product cards, customer-scoped pricing, search and quantity editing remain blocked pending this relationship; P5 submission remains unimplemented.
