# Invoices Collaboration Handoff

## Final status — Structured Lot / Expiry Canonical Mapping

- Module: `Modules\Invoices`, integrated with `Modules\Inventory` through contract `1.0`.
- Implementation branch: `fix/invoices-structured-lot-expiry`.
- PR: `#182 — fix: preserve invoice lot expiry through inventory receiving`.
- Merge commit on `main`: `8a41e22a08c0ee01cef038296b5680b5a8d1bb88`.
- Status: **MERGED / VERIFIED / UI PASS / CLOSEOUT COMPLETE**.
- No schema migration was required.
- `InvoiceLineNormalizer::VERSION = deterministic-v4`.

## Canonical ownership boundary

Invoices owns raw/canonical invoice source detail, extraction and normalization of invoice-supplied lot/expiry/manufacture metadata, Source Data presentation, reconstructed PDF presentation and publication through the versioned Inventory contract.

Inventory owns item matching, warehouse receiving, InventoryItem, lot instances, Receipt, StockMovement and StockBalance.

The reconstructed PDF is presentation only. It must never be parsed back into canonical data. Inventory must never call GDT directly.

## Lot / expiry priority

Canonical priority is:

```text
structured GDT metadata (`ttkhac`)
  -> legacy/top-level source fields
  -> deterministic text fallback
  -> null
```

Structured values always win over conflicting text. Missing values remain null; no lot or expiry may be guessed or fabricated.

`Modules\Invoices\Support\GdtInvoiceLineMetadata` extracts structured/top-level GDT metadata. `InvoiceLineNormalizer` applies the complete priority chain, including deterministic text fallback. The Inventory contract factory uses the normalizer so both structured and fallback values reach Inventory.

Optional line fields remain backward-compatible under contract version `1.0`:

```text
lot_number
expiry_date
manufacture_date
```

## Accepted runtime cases

### SINVICO / CAMZITOL

```text
Invoice id:      2477
Source id:       525
Symbol:          1/C26TSV
Invoice number:  123
Supplier:        CÔNG TY TNHH DƯỢC PHẨM SINVICO
Product:         CAMZITOL
Structured lot:  G0846
Structured HSD:  2028-03-08
```

Raw GDT detail contains `LotNo = G0846` and `ExpiryDate = 2028-03-08`. The accepted UI/PDF/Inventory representation preserves `G0846 / 08/03/2028` without OCR, GDT refetch or manual re-entry.

### KHANG PHÁT / Tharodas invoice #287

```text
Invoice id:      2530
Source id:       516
Symbol:          1/C26TKP
Invoice number:  287
Supplier:        CÔNG TY TNHH THƯƠNG MẠI DƯỢC PHẨM KHANG PHÁT
Description:     Tharodas ... Lô: 020526, HSD: 04/05/2029 ...
Structured lot/HSD: null / null
Normalized:      020526 / 2029-05-04
```

This case proved that text fallback must be carried through `InvoiceForInventoryV1Factory`; direct use of the structured-only extractor at that boundary would lose fallback values. The factory now consumes `InvoiceLineNormalizer`, and Inventory refresh was accepted with `020526 / 2029-05-04`.

## Source Data and reconstructed PDF

`/admin/invoices/source-data` displays line-level lot number and expiry date from canonical data. Expiry is presented as `d/m/Y` in UI/PDF where applicable. Optional lot/HSD columns remain conditional so invoices without those values stay valid.

Preserve existing Source Data filters, supplier-wide classification, expense classification, Dashboard classification, Excel detail export, GDT automatic recovery/retry/backoff and stable Livewire row/checkbox/label keys.

## Stock safety

Invoice synchronization/import does not post stock. Lot/expiry values are proposed receiving evidence and do not define product identity or automatically create products.

```text
Invoices canonical source
  -> Inventory contract/staging
  -> Inventory Inbox
  -> Receipt DRAFT
  -> explicit operator confirmation
  -> StockMovement / StockBalance
```

Only confirmed Inventory receipts change stock.

## Acceptance evidence

- Structured GDT metadata path: PASS.
- Text fallback path: PASS.
- Structured-over-text conflict priority: PASS.
- Missing metadata remains null: PASS.
- Source Data lot/HSD: UI PASS.
- Reconstructed PDF lot/HSD: UI PASS.
- Inventory contract propagation: PASS.
- Inventory source refresh and DRAFT receiving flow: UI PASS.
- Focused tests: PASS.
- Pint on changed scoped files: PASS.
- PR #182: MERGED.
- `main` synchronized at merge commit `8a41e22a`.

## Closeout

This scope is complete. Do not reopen `fix/invoices-structured-lot-expiry` for new work. Future work starts from current `main` and must preserve the canonical ownership and stock-posting invariants above.


## Current handoff — GDT authentication request contract diagnostics

Detailed reusable runbook: `docs/modules/Invoices/GDT_AUTHENTICATION_TROUBLESHOOTING.md`. Future GDT login incidents must consult and append to that runbook before introducing new request-contract changes.

- Branch: `fix/invoices-gdt-auth-session-diagnostics`.
- Scope: `Modules\\Invoices` GDT authentication/session diagnostics for `/admin/invoices/hoadon`.
- Status: **IMPLEMENTED / FOCUSED TEST PASS / CLI AUTH PASS / UI PASS**.
- No schema migration.
- Inventory ownership boundary above remains unchanged; Inventory does not call GDT directly.

### Root-cause evidence

The failure was isolated outside Livewire/UI. Captcha initialization succeeded from the local application session, while authenticate returned HTTP 403 with the upstream blocked-request response. Transport diagnostics showed the captcha and authenticate requests reaching the same GDT endpoint/IP over HTTP/2 with successful SSL verification and the same persisted captcha-session cookie jar.

The GDT authenticate request now sends a newly generated UUID `request-id` for each authentication request. The application does not copy/replay a browser request ID and does not add browser fingerprint headers. Diagnostic logs record only `generated-per-auth-request`, never the UUID value.

Controlled CLI verification changed from authenticate HTTP 403 without `request-id` to authenticate HTTP 200 with a fresh `request-id`. The successful response returned the expected login action and the token was cached. The real `/admin/invoices/hoadon` connection flow was then manually verified: **UI PASS**.


### Runtime request-context acceptance — 18/09/2026

Authentication alone was not the complete incident. A fresh token was successfully written to the database cache, but the invoice-list endpoint returned HTTP 403 while using the old query request context. Legacy handling then deleted the token because it treated 401 and 403 identically, causing secondary missing-token/expired-session messages.

The list and detail paths now use a fresh per-request UUID `request-id` plus the safe application-level request context. HTTP 401 clears the token; HTTP 403 is diagnosed as a rejected request and does not clear the token solely because of the status.

After queue worker reload and a fresh manual-captcha login, runtime acceptance completed successfully: 14/14 missing local details recovered, invoice list 20/20 received, 20 headers remained idempotent, detail pass reused 19 and fetched 1 with 0 errors, Excel was generated and the job completed.

The reusable failure signatures, misleading secondary-error explanation, 401/403 recovery rules, worker-reload procedure and Google Drive separation are recorded in `GDT_AUTHENTICATION_TROUBLESHOOTING.md`.

### Security / diagnostics invariants

Diagnostic logging excludes GDT username/password, captcha value, token, cookie values and the generated request-id value. Cookie diagnostics contain metadata only. The local diagnostic command remains local-environment-only and requires the operator to read/enter the captcha manually.

Do not copy browser cookies/tokens/request IDs into Laravel, emulate `sec-*` browser headers, spoof a Chrome identity, or introduce automatic retries for captcha/authenticate POST.

### Test evidence and known baseline drift

`tests/Feature/Invoices/GdtAuthenticationSafetyContractTest.php`: **7 passed / 73 assertions** after authenticate/list/detail request-context coverage.

Invoices module regression after the fix: **49 passed (459 assertions), 4 failed**. The four failures are pre-existing contract/baseline drift outside this GDT authentication scope:

- `InvoiceInventoryBulkIntakeContractTest`: missing expected `GdtPdfService::fetchAndStoreDetail(Invoices $invoice): array`.
- `InvoiceInventoryBulkIntakeContractTest`: missing expected `'_gdt_raw_payload' => $item` persistence contract.
- `InvoiceInventoryBulkIntakeContractTest`: missing expected supplier/invoice `classification_scope` contract string.
- `InvoiceInventoryHandoffContractTest`: missing expected `->ingest($this->factory->build($invoice))` contract string.

These failures must not be repaired by changing Inventory/Source Data/GDT detail/handoff behavior as part of this authentication branch. They require separate reconciliation against current `main` ownership/contracts.

### Closeout checkpoint

GDT authentication fix is accepted at focused-test, CLI and UI levels. Before PR/merge, preserve the baseline-failure evidence above, run scoped formatting/checks for changed files as required by the collaboration workflow, confirm the working tree is clean after pull, and merge only after explicit approval.


## 2026-09-18 — GDT canonical identity + duplicate recovery

- Branch: `fix/invoices-gdt-canonical-dedup-recovery`.
- Production audit before implementation: sold 2026 had 951 rows / 111,124,775,809 VND; 468 duplicate pairs were proven identical by business fingerprint, provider, header hash and detail hash. Expected canonical baseline after recovery: 483 rows / 55,926,358,092 VND.
- Root cause: legacy GDT rows could use `cttkhac.TransactionID` (observed 12-character lookup codes), while current canonical mapping prefers GDT `mtdiep/mhdon/ma/id`; lookup-code-only persistence therefore inserted a second row.
- Prevention: `GdtInvoiceService` now resolves exact canonical lookup first, then identical GDT header hash, legacy TransactionID, and finally a conservative unique business fingerprint including totals/VAT.
- Recovery: `invoices:recover-gdt-duplicates` now audits all years and both `sold`/`purchase` directions by default; `--year` and `--type` are optional scope filters. It is dry-run by default. `--apply` is explicit and transaction guarded. A pair is eligible only when the two GDT rows share business fingerprint + header/detail hashes and exactly one row matches the canonical GDT identity.
- Reference safety: legacy `invoice_files` and inventory snapshot references are repointed only when the canonical side has no conflicting row; conflicts BLOCK the pair. Duplicate source rows are hash-verified, user/business metadata is merged conservatively, then the legacy source/invoice is removed.
- Idempotency: after a successful apply, rerunning the command should report zero eligible duplicate pairs.
- Checkpoint validation requested locally: focused contract test + Pint, then an all-period/all-direction dry-run must report every eligible pair/reference before any `--apply`. The known 2026 sold subset must still reconcile to 468 pairs and, after apply, 483 rows / 55,926,358,092 VND.
- No UI templates changed; manual smoke remains `/admin/invoices/hoadon-list` after recovery.


### Final acceptance — 2026-09-18

- All-period/all-direction dry-run found 469 verified GDT duplicate pairs: 468 sold + 1 purchase, all in 2026; blocked = 0.
- Recovery apply completed 469/469; 68 invoice_file references preserved/repointed; 0 inventory snapshots required movement; blocked = 0.
- Idempotency re-run: pairs = 0.
- Post-recovery 2026 sold: 483 invoices / 55,926,358,092 VND.
- Post-recovery 2026 purchase: 564 invoices / 46,114,902,421 VND.
- Post-recovery total: 1,047 invoices; GDT source records: 1,047.
- Focused GDT authentication + duplicate identity/recovery tests: PASS.
- Manual UI acceptance: PASS.
- Invoices regression baseline outside this scope remains the previously documented Inventory contract drift; do not repair it in this recovery branch.


### Source-data duplicate boundary audit — 2026-09-18

- Audited /admin/invoices/source-data and InvoiceSourceRecord write paths after canonical GDT dedup recovery.
- SourceDataManager remains annotation/read-management only: it does not acquire GDT data and does not create/upsert source records.
- Canonical RAW writers key GDT source records by (invoice_id, provider), backed by the database unique constraint on (invoice_id, provider).
- Duplicate prevention therefore remains owned by GdtInvoiceService ingestion for both purchase/sold and all years; source-data cannot independently create a second GDT source for the same invoice.
- Added contract coverage to lock this boundary.
- Focused duplicate/source-data contract: 4 passed, 29 assertions.


## 2026-09-18 — Net revenue reporting + dashboard period semantics

- Branch: `refactor/invoices-net-revenue-reporting`.
- Financial reporting semantics are now explicit: sales revenue and purchase value use `amount_before_vat`; VAT remains a separate metric. Invoice-level payable/total amount remains `total_amount` where the actual document total is required.
- Applied consistently to `/admin/invoices/hoadon-list`, `/apps/invoices`, `/admin/invoices/reports/partners`, and `/apps/invoices/partners`. Partner sold/purchase/net-difference reporting is before VAT.
- ClientPortal `/apps/invoices/list` now displays and summarizes pre-VAT values. Its high/low amount sorting is mapped to `amount_before_vat` without changing the Admin invoice-list sort contract.
- Admin `/admin/invoices/dashboard` purchase-classification analysis is year-scoped by `issued_date`, defaults to the current year, exposes an explicit year selector, shows the exact 01/01–31/12 period, and preserves that year in source-data drill-down links.
- Years with no classified purchase data render an explicit empty state instead of a grid of zero-value KPIs. Expense category rows remain structurally available for years that do have data.
- Acceptance evidence: focused tests PASS; targeted Pint PASS; Admin dashboard 2026 data UI PASS; 2025 empty-state UI PASS; ClientPortal invoice-list pre-VAT UI PASS. Earlier admin/client revenue and partner-report surfaces were also UI PASS in this batch.
- Focused regression files: `InvoiceDashboardClassificationContractTest.php`, `InvoiceNetRevenueReportingContractTest.php`, and `ClientPortal/InvoicesApplicationContractTest.php`.
- Closeout: scope is ready for PR/merge after confirming the branch is clean and up to date. Do not fold unrelated Inventory baseline drift into this refactor.


## 2026-09-19 — Invoice → Partner candidate synchronization dashboard

- Branch: `feat/invoices-partner-sync-dashboard`.
- Scope: add a Partner synchronization workspace to `/admin/invoices/dashboard` without moving Partner master-data ownership into Invoices.
- Invoices aggregates invoice evidence by non-empty tax code before handoff. Sold invoices contribute `customer`; purchase invoices contribute `supplier`; the same tax code may carry both roles.
- Invoice rows without a tax code are counted as missing identity and are deliberately excluded from batch intake. Bulk synchronization never falls back to normalized-name matching.
- `InvoicePartnerCandidateService` sends one normalized candidate per tax code through the existing `PartnerCandidateIntakeService::intake('invoices', ...)` boundary. It does not create/update `partners` directly.
- Candidate metadata preserves first/last invoice date, sold/purchase invoice counts and the latest invoice id for provenance.
- Dashboard shows unique invoice partners, customer/supplier counts, pending/conflict state and matched state. The POST action requires `invoices-create` and returns a centered result modal.
- Review/create/merge/ignore remains owned by Partner at `/admin/partners/sync/invoices`; Partner permissions continue to protect those mutations.
- No schema migration is required.
- Added focused coverage: `InvoicePartnerSyncContractTest.php` and `InvoicePartnerSyncDashboardContractTest.php`.
- Checkpoint: implementation pushed; local focused tests, targeted Pint and manual Dashboard/Partner UI acceptance are required before PR/merge.


### Partner review bulk UX refinement — 2026-09-19

- The existing Partner review workspace now defaults to the actionable queue (`pending + conflict`), while matched/ignored candidates remain available through the status filter.
- Added role filter: customer, supplier, or both. Reset is shown only when the filter state differs from the default workspace.
- Added page-scoped checkbox selection. Only eligible `pending` candidates can be bulk-created; selecting the header never means all matching rows across pagination.
- Bulk create rechecks tax-code ownership at execution time. If the MST already exists, no duplicate Partner is created; the candidate is linked/finalized against the existing Partner without applying invoice fields.
- Conflict candidates remain individual-review only; bulk processing does not overwrite Partner master data.
- Added centered bulk-result feedback with selected/created/existing/skipped-or-failed counts.
- Replaced generic Livewire pagination with `partner::vendor.pagination.admin-partner`, explicitly following `.codex/standards/ADMIN_UI_STANDARD.md`: white inactive controls, indigo active page, quiet disabled controls, bounded page sizes.
- Added `tests/Feature/Partner/InvoiceCandidateBulkReviewContractTest.php`.
- Local checkpoint should run the Invoices partner-sync tests plus this Partner contract test and targeted Pint before UI acceptance.


### Missing-tax review + workspace width refinement — 2026-09-19

- Partner invoice review now uses the Admin shell's available content width instead of adding a second large horizontal padding layer.
- Status and action columns have explicit room and no-wrap treatment so badges/actions do not break awkwardly.
- Added `Định danh MST` filter with `Có MST` (default) and `Không có MST`.
- Missing-tax mode reads the original Invoices rows because such rows are intentionally excluded from `partner_sync_candidates`; it is review-only and exposes no checkbox or Partner-create action.
- This preserves the safety rule: Partner creation/synchronization still requires a tax-code identity.
