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


## Revised Website account and approval flow

- Any authenticated active Website account may browse the Pharma quick-order catalog when Pharma is published and the feature is enabled; no pre-linked Partner or dedicated view permission required.
- The initial catalog displays only active, Admin-allowed, actor-eligible **global** price-list medicines which also have a published Pharma storefront product; no private customer prices are disclosed.
- This is a browse-only checkpoint: no cart, order submission, or inventory issue is created.
- Next checkpoints: buyer tax/recipient/shipping profile and request persistence, Admin verification and acceptance/rejection, canonical Pharma inventory issue creation with idempotent linkage and stock posting only via existing workflow.
- Regulatory buyer eligibility and controlled-product restrictions must be validated before accepting any request.


## P4.2-A UI checkpoint

- Quick-order search is debounced 300ms with abortable same-origin JSON GET requests, and server-side authentication/feature/price-list scope is reused for JSON responses.
- Product rows have quantity decrement, numeric input, increment, a page-local selected quantity summary, and native 'Xem thêm' load-more pagination.
- No prices, buyer form, submission, order persistence, inventory issue creation or stock changes are enabled in this checkpoint. Next: validated persisted cart and buyer-review workflow.


## P4.2-A follow-up: displayed price and draft total

- Quick-order UI label changed from `Giá bán CT` to `Đơn giá (Có VAT)` at user request. The value still comes from `company_sale_price`; **VAT-inclusiveness must be verified against canonical Pharma price calculations before accepting orders**.
- The browser-only cart summary displays a temporary quantity-weighted subtotal of known unit prices. When any selected line has no unique price, the UI marks the subtotal incomplete; it is not an order total.
- No request submission or inventory issue creation is enabled.


## P4.3 buyer draft checkpoint

- Selected product IDs/quantities and buyer contact/shipping fields are kept only in browser sessionStorage scoped by authenticated user ID. This is a convenience draft, not a trusted server cart. No price is stored in the draft.
- Buyer panel supports company/individual, company name, tax code, recipient, phone, address, and notes. The continue action only opens the form; submit remains disabled.
- No personal details or orders are sent to the server yet. Server-side request validation, buyer eligibility, anti-duplication, verified price/VAT computation, review queue, and inventory issue creation remain P5 tasks.
- VAT audit note: PriceListItem casts company_sale_price to decimal:2 but does not itself specify VAT semantics. The label requested by UI is not proof of tax-inclusiveness; verify the canonical calculation before enabling checkout.


## P5.1 — Pending-review submission checkpoint

- Authenticated active Website accounts can POST `/quick-order/requests` with CSRF, UUID idempotency key, 1–200 product IDs/positive integer quantities, and optional buyer contact/delivery details.
- Server checks Pharma template, Admin enablement, actor-scoped allowed active global lists, medicine category and active storefront products before persisting. Submission stores only product IDs and quantities; **browser prices are never trusted or stored**.
- Record is created as `pending_review` in `website_pharma_quick_order_requests`. No Partner is automatically assigned; no Pharma inventory issue, approval, posting, or stock movement occurs.
- UI displays confirmation and leaves buyer review collapsed by default on each transition. Submission requires a browser supporting crypto.randomUUID; unsupported browsers must not submit without an idempotency key.
- Next checkpoint P5.2: admin review queue, buyer eligibility checks, verified price/VAT calculation and rejection/acceptance audit. P5.3: idempotent issue creation via canonical Pharma service. This checkpoint does not expose Admin approval controls.


## P5.1 follow-up — Success modal and email recipient

- Successful request submission opens a modal with request ID and pending-review status. The confirmation action navigates back to the Quick Order catalog; the browser draft was cleared after successful persistence.
- Admin Website Quick Order settings include optional single notification email (`notification_email`, RFC format, max 254 characters), stored in the existing settings payload. Empty means no email.
- A plain-text email with request ID, buyer type and item count is sent only when a new request record is created; idempotent replays do not send again. Mail failures are logged and do not change the persisted pending request. SMTP/mail configuration must be verified in the target environment.
- This is notification only, not an Admin review queue, buyer approval, price validation, or inventory issue creation. Those remain future checkpoints.


## Multi-recipient queued email checkpoint

- Admin notification recipients accept up to 10 valid email addresses separated by semicolons or commas; whitespace and duplicate addresses are normalized, invalid addresses are rejected on save.
- New requests dispatch `SendPharmaQuickOrderNotification` with `afterCommit()`, only for first creation (idempotent replays do not enqueue). Job implements `ShouldQueue` with three attempts and fetches request by ID. No buyer PII is embedded in the job payload.
- Queue worker must be running and `QUEUE_CONNECTION` must not be `sync` to gain asynchronous behavior. Check `php artisan queue:failed` for delivery failures. Email is notification only, not approval or inventory posting.


## Admin recipient list UX

- Email recipient configuration now uses individual `notification_emails[]` inputs with Add/Remove controls (max 10). Each row uses native email input validation. Empty rows are ignored and duplicate addresses are removed server-side.
- Existing semicolon/comma-delimited `notification_email` settings are parsed into rows when loading; the canonical saved setting remains a semicolon-delimited string for backward compatibility with the queued notification job.
- Removing every row and saving clears the recipient list. Queue behavior is unchanged.
