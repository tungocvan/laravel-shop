## 2026-11-05 — POS HTTP diagnostics checkpoint

- Branch: `feat/invoices-gdt-independent-sources-20261105`; additive diagnostics only.
- `GdtInvoiceService::fetchInvoicesByMonth()` logs allowlisted metadata for non-2xx/non-401/403 HTTP responses: source, relative endpoint, direction, page, status, response content type classification (JSON/HTML/other), presence of POS filter, page size and pagination-state presence. No response body, response error message, full query/search, invoice payload, request URL, credentials, or token is logged.
- Preserves POS endpoint `/sco-query/invoices/`, `ttxly==8`, existing 401/403 handling, Regular checkpoint, retry, failure propagation and incomplete-Excel guard. HTTP 500 root cause remains unverified.
- New HTTP-fake test `tests/Feature/Invoices/GdtPosHttpDiagnosticsTest.php` covers JSON/HTML failure responses, allowlisted log context and HTTP 500 propagation.
- Focused: `php artisan test tests/Feature/Invoices/GdtPosHttpDiagnosticsTest.php tests/Feature/Invoices/GdtSourceRetryHttpIntegrationTest.php`. Only after PASS run module regression `php artisan test tests/Feature/Invoices`.
- Tests not executed by GitHub connector; UI NOT VERIFIED. No PR, merge, production change or worker restart.

## 2026-11-05 — Integration/Regression checkpoint (HTTP fake)

- Added `tests/Feature/Invoices/GdtSourceRetryHttpIntegrationTest.php` to execute real `GdtInvoiceService::processRange` control flow with Laravel `Http::fake` and `Cache`: empty Regular completes, POS first responds HTTP 500, retry reuses Regular checkpoint, POS succeeds, no incomplete Excel produced.
- This is an HTTP/cache integration test, **not a database persistence integration test**; non-empty canonical insert/dedup and Excel export remain unverified.
- Run focused first: `php artisan test tests/Feature/Invoices/GdtRegularCanonicalCheckpointContractTest.php tests/Feature/Invoices/GdtSourceProgressContractTest.php tests/Feature/Invoices/GdtSourceChunkRetryContractTest.php tests/Feature/Invoices/GdtSourceRetryHttpIntegrationTest.php`.
- Only if focused PASS, run module regression appropriate to existing Invoices tests, then impacted cross-module tests as needed. Stop on failure. UI requires user verification.
- Test execution NOT RUN in GitHub environment; no PR, merge or production deployment.

## 2026-11-05 — Batch 3: request-scoped monthly source retry checkpoint

- For `source=all` with a `syncId`, each fully fetched month/source payload is cached for 24 hours, keyed by sync id, direction, requested range and monthly chunk.
- Regular canonical headers persist before its completed monthly payload is cached. On POS HTTP 500, retry reuses completed Regular monthly payload without re-querying it. POS is cached only after a fully successful paginated fetch.
- Combined Excel remains gated on successful completion of every month and both sources; cached payloads are reused for final deduplication and export.
- For `syncId=null`, no cross-attempt payload reuse; existing behavior remains. Cache expiry can cause a safe refetch. The job retains existing three-attempt policy.
- Limitations: this is a cache-backed retry checkpoint, not a durable audit ledger; cache loss causes refetch, and it does not provide integration-tested exactly-once semantics. The POS HTTP 500 upstream cause is still unresolved. No database schema change.
- Tests NOT RUN: `php artisan test tests/Feature/Invoices/GdtRegularCanonicalCheckpointContractTest.php tests/Feature/Invoices/GdtSourceProgressContractTest.php tests/Feature/Invoices/GdtSourceChunkRetryContractTest.php`. Regression and UI NOT VERIFIED. No PR, merge or deploy.

## 2026-11-05 — GDT independent-source rollout, batch 2 checkpoint

- Adds cache status `source_progress.regular=persisted` after regular canonical checkpoint, `source_progress.pos=failed` on POS failure and `source_progress.pos=fetched` after a completed monthly POS fetch.
- All-source job records `partial` during retries, retains exception propagation and final `failed` state after retries exhausted. These statuses describe progress, **not full-range source completion**.
- No change to GDT POS query contract (`ttxly==8`), retry policy, database schema, or combined Excel safety.
- **Outstanding:** full-range source completion markers, source-only retry without fetching regular again, end-to-end DB integration test, and source-aware combined export. Do not mark option B complete.
- Focused tests NOT RUN: `php artisan test tests/Feature/Invoices/GdtRegularCanonicalCheckpointContractTest.php tests/Feature/Invoices/GdtSourceProgressContractTest.php`.
- UI NOT VERIFIED; module regression NOT RUN; no PR/merge/deploy.

## 2026-11-05 — GDT independent-source rollout, batch 1 (checkpoint)

- Branch: `feat/invoices-gdt-independent-sources-20261105`.
- Verified regular GDT pages are persisted as canonical headers before requesting POS within each monthly chunk in `source=all`.
- If POS returns HTTP 500, the all-source job still fails, no incomplete Excel is emitted, and regular headers from that chunk are retained.
- Existing canonical persistence is reused (idempotent invoice identity). No schema, environment, or production changes.
- **Not yet implemented:** skip successful regular on retries, source-specific job status, full cross-month atomicity, and a source-complete combined export. These are follow-up architecture batches; do not describe this checkpoint as full option B.
- Focused test: `php artisan test tests/Feature/Invoices/GdtRegularCanonicalCheckpointContractTest.php`.
- Gates: focused tests NOT RUN; module regression NOT RUN; UI NOT VERIFIED; PR NOT CREATED; do not merge/deploy.

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


### Acceptance checkpoint — 2026-09-19

- User acceptance: UI PASS.
- Focused/impacted tests: PASS on the user's environment.
- Targeted Pint completed; the only local changes reported were import ordering in `Modules/Invoices/routes/web.php` and quote normalization in `InvoicePartnerSyncDashboardContractTest.php`. Those exact formatting changes are committed to this branch.
- No additional regression run is required for the formatting-only synchronization.
- Feature scope is ready for PR/merge closeout.


## 2026-10-20 — GDT database-owned settings (implementation checkpoint)

- Branch: `feat/invoices-gdt-db-settings-20261020`, based on `eab2def58`.
- Added module-owned `invoice_gdt_settings` migration and DB-backed `GdtConfigService`; encrypted GDT password, no .env writes.
- Provider hydrates `invoices.gdt.*` for existing API, invoice, PDF and recovery consumers; legacy config remains a transitional fallback until separately approved cleanup.
- Admin GdtLogin save no longer runs `config:clear` or modifies .env.
- Added `invoices:gdt-settings-import --dry-run` for non-overwriting legacy import; no values printed.
- Rollout and rollback: `docs/modules/Invoices/GDT_SETTINGS_MIGRATION_CHECKPOINT.md`.
- **Gate:** Local focused tests NOT RUN, module regression NOT RUN, impacted tests NOT RUN, UI NOT VERIFIED. Do not merge or deploy. Database migration/import and .env cleanup require a separate approval checkpoint.

- Follow-up commit: preserve password when Livewire nullable password field is absent; four advanced GDT tuning defaults no longer read GDT_* env variables. Await fresh focused/module tests after pulling.


## 2026-11-05 — Integrate GDT configuration input reload fix into POS diagnostics branch

- Branch: `feat/invoices-gdt-independent-sources-20261105` (existing feature branch; no merge to main).
- Ported the already-reviewed deferred bindings from `fix/invoices-gdt-config-input-reload-20261105` into `Modules/Invoices/resources/views/livewire/gdt-login.blade.php`: seven GDT configuration fields use `wire:model` instead of `wire:model.live`, so typing no longer triggers per-keystroke Livewire updates; captcha retains its existing live binding.
- Ported the focused binding regression contract to `tests/Feature/Invoices/GdtDatabaseSettingsTest.php`.
- No changes to GDT password persistence, API endpoints, Regular canonical checkpoint, POS retry/HTTP diagnostics, database schema, .env or production configuration.
- Local gate pending: `GdtDatabaseSettingsTest`, `GdtPosHttpDiagnosticsTest`, `GdtSourceRetryHttpIntegrationTest`; only after focused PASS run `tests/Feature/Invoices`; then manual UI verification on `/admin/invoices/create-token` for typing without reload, explicit save, password preservation, captcha/login and responsive layout.
- Prior POS-focused and Invoices regression PASS was reported before this integration; rerun required. UI PASS not yet confirmed. No PR, merge or deploy authorized.


### 2026-11-05 — POS HTTP request-profile read-only audit and fake-based regression

- Compared canonical GdtInvoiceService with GdtExcelSandboxService: both use Bearer authentication and the same Accept, Origin, Referer, Action, End-Point and request-id headers; sandbox additionally sets a connect timeout. Both send POS `/sco-query/invoices/purchase` with `ttxly==8`, `sort=tdlap:desc`, `size=50`.
- No evidence that changing headers, endpoint, search filter, or timeout resolves upstream POS HTTP 500. No live API probe or production configuration change authorized.
- Extended `GdtPosHttpDiagnosticsTest` with Laravel HTTP-fake assertions for POS query shape, first-page state absence and presence of expected request headers. Tests do not log token, query values, response body or invoice identifiers.
- Checkpoint pending: focused `GdtPosHttpDiagnosticsTest`, `GdtSourceRetryHttpIntegrationTest`, `GdtDatabaseSettingsTest`; if PASS, run Invoices module regression. Manual UI PASS remains user-confirmed only. No PR/merge/deploy.


### 2026-11-05 — Opt-in POS query diagnostics checkpoint

- Added `invoices:gdt-pos-diagnose` (registered in InvoicesServiceProvider). Default is dry-run; `--execute` explicitly authorizes exactly one authenticated GET to `/sco-query/invoices/{purchase|sold}` with `size=1`, date range max 31 days, `ttxly==8`, no pagination, no retries.
- Uses existing cached token, HTTPS-only base URL, bounded connect/total timeouts. Prints only status and response format; never prints response body, token, invoice IDs, query values or raw exception messages. Does not persist invoices, export Excel, alter config, or update cache.
- Focused fake-HTTP tests: `tests/Feature/Invoices/GdtPosQueryDiagnosticCommandTest.php`. Tests pending; no real GDT request has been sent.
- Checkpoint: run focused test and prior GDT diagnostics tests; only after PASS run `tests/Feature/Invoices`. Keep `--execute` off until separately approved for the specific environment. No PR/merge/deploy.

### 2026-11-05 — POS endpoint/filter evidence and comparison

- Public community implementations reference `/sco-query/invoices/purchase` for POS purchase, so an endpoint typo is not established. Sources: https://www.giaiphapexcel.com/diendan/threads/t%E1%BA%A3i-h%C3%B3a-%C4%91%C6%A1n-%C4%91i%E1%BB%87n-t%E1%BB%AD-https-hoadondientu-gdt-gov-vn-excel-vba.171723/page-7 and https://gist.github.com/lesslucifer/168f0fd84a1efbed4a0c102043ef1d31 . These are community references, not official API contracts.
- Existing POS HTTP 500 reproduced with size=1, date range 2026-10-12..2026-10-31, filter `ttxly==8`. Root cause still unverified.
- Diagnostic command now supports `--without-pos-filter` as a comparison option only. Default remains filtered and dry-run. Never enable automatic fallback in production sync based on this test alone; compare response status and schema first. No changes to GdtInvoiceService or Excel sandbox.
- Fake-HTTP test covers one GET and absent filter; awaiting DELL focused/module tests. No live comparison request performed, no PR/merge/deploy.

### 2026-11-05 — Safe POS JSON error-shape diagnostics

- After POS HTTP 500 persisted with size=1 both with and without `ttxly==8`, extended `invoices:gdt-pos-diagnose` to report only whether top-level `code`, `status`, `error`, `message` fields exist and their primitive type. Does not print ANY remote field values, nested data, response body, identifiers, or token. No request changes and no runtime sync changes.
- Added fake HTTP regression with sensitive-looking response content; focused tests and module regression pending DELL. Existing UI login PASS previously reported. Keep explicit `--execute` separate from dry-run; no automatic live request, PR, merge or deploy.

### 2026-11-05 — Fixed-label POS error message classification

- GDT POS diagnostic returned HTTP 500 JSON with top-level string `message`; actual text remains private.
- Extended diagnostic command to classify up to 2048 bytes of that string into fixed labels: `ENDPOINT_NOT_FOUND`, `AUTHORIZATION_ERROR`, `INVALID_QUERY`, `UPSTREAM_ERROR`, or `UNKNOWN`. Labels are heuristics, not verified root cause. No raw message output or persistence; dry-run and single-request `--execute` unchanged.
- Fake HTTP tests cover recognized invalid query, sensitive unknown message, and structured message. Await DELL focused and module regression; no production API request or merge.

### 2026-11-05 — POS human CAPTCHA integration (checkpoint pending)

- Browser evidence: GET `/api/captcha` returns JSON `key` and SVG `content`; manual POS purchase search uses `/api/sco-query/invoices/purchase`, `ttxly==5`, `ckey` and `cvalue`, and search-specific headers. Captcha is shown only after Search. Browser HTTP 200 does not prove Laravel success.
- Added isolated POS CAPTCHA lifecycle to GdtApiService: 5-minute per-admin cache of key and temporary cookie jar, image rendered as SVG data URI in `img` (never raw DOM markup), one-time consume, no persisted answer. SearchHoadon displays challenge only for POS-inclusive sources; requires manual non-queue sync, consumes challenge and clears answer before processing. Existing Regular-only queue remains unchanged.
- GdtInvoiceService POS requests require explicit challenge, use observed `ttxly==5` plus `ckey`/`cvalue`, search-specific Action/End-Point headers and challenge cookies. Regular endpoint and query remain unchanged. All-source synchronous path persists Regular before POS as before. Queue POS/all is deliberately blocked in UI pending a secure cross-worker verification design; direct legacy jobs without challenge fail closed rather than silently query without verification.
- Added `GdtPosCaptchaFlowTest`, adjusted existing POS HTTP fake diagnostics/retry tests. Checkpoint DELL: focused tests first, module regression only after PASS, UI verification required before merge. No live GDT request, no PR/merge/deploy. Verify whether GDT requires additional session context, CAPTCHA reuse across pages, and 200 schema with manual UI; if not, stop and investigate, do not auto-bypass CAPTCHA.

- Checkpoint follow-up: focused tests initially 3 FAIL / 3 PASS. Root cause in tests: POS fake URL matched overlapping regular substring first; retry test omitted fresh POS CAPTCHA. Fixed fake matching order and second-attempt challenge in commits `3ff19cf34` and `c3c6e80fe`. Await DELL focused retest; no PASS claimed.

- Second focused checkpoint: 2 FAIL / 4 PASS. Root cause confirmed in service diagnostics: `search_has_pos_filter` still checked obsolete `ttxly==8` after POS query switched to `ttxly==5`; Mockery log assertion threw before expected HTTP 500 exception. Fixed service metadata predicate (commit `7e69b20df2`). Await retest; no TEST PASS claimed.

- Module regression checkpoint: 95 PASS, 3 FAIL (795 assertions), all stale source-text contracts in `GdtCanonicalPosSyncContractTest`, `GdtCanonicalSourceSelectionContractTest`, and `GdtRegularCanonicalCheckpointContractTest`. Updated expected POS call signature to include `$posCaptcha` and filter to `ttxly==5`; kept checkpoint ordering and canonical persistence assertions. Await focused retest of three contract tests and module regression, then UI confirmation. No merge/deploy.

### 2026-11-05 — Source-aware GDT sync dashboard (UI checkpoint pending)

- Approved UI redesign: `SearchHoadon` now displays separate Regular/POS cards with GDT-reported counts (or em dash when not verified), explicit state per source, and aggregate RAW detail results. No fabricated counts, no database migration.
- `GdtInvoiceService::lastSourceStats()` tracks per-source query results, zero-result success, failure, skip, and post-processing state; counts accumulate across month chunks. Counts are API rows, not new DB inserts, and can overlap across sources. The queue's supported Regular-only source branch publishes source/detail results in cache for Livewire polling.
- Changing source to POS/all switches to direct mode automatically; queue checkbox is disabled for POS-inclusive sources, preserving manual CAPTCHA gate. Regular retains queue. Existing file management/import/backup actions unchanged.
- Added `GdtSyncDashboardContractTest`. Checkpoint: run focused tests (new contract + existing POS/canonical tests), then `tests/Feature/Invoices` only after focused PASS; manual desktop/mobile CAPTCHA and source-count UI acceptance required. No local execution or UI PASS claim, no PR/merge/deploy.

### 2026-11-05 — GDT Regular/POS tab search-first UI (checkpoint pending)

- Approved replacement of source dropdown by two accessible tab buttons (Regular / POS). Search inputs: direction, date range, manual POS CAPTCHA; `Tìm kiếm hóa đơn` calls read-only `GdtInvoiceService::previewSource()` with existing paginated GDT fetch logic and returns count + up to 20 safe summary rows; no canonical persistence/export during preview.
- `SearchHoadon::searchInvoices()` captures success/empty/failure explicitly; only a successful preview matching current source/date/direction enables canonical sync. Switching tabs or filters clears preview. POS CAPTCHA is consumed for the search; a **new CAPTCHA is required for canonical sync** because no CAPTCHA credentials are cached in the component or queued. Regular queue remains supported; POS queue remains disabled.
- Existing canonical processRange, RAW header/detail, exports and file/backup UI are preserved. Preview counts are API results, not persisted row counts. Search does not imply sync PASS. Preview query may be expensive for large date ranges; test on short ranges first.
- Added `GdtTabSearchContractTest`, updated `GdtSyncDashboardContractTest`. Checkpoint: focused tests → module regression → desktop/tablet/iPhone UI, manual CAPTCHA search + fresh CAPTCHA sync and 0-result state. No TEST/UI PASS asserted; no PR/merge/deploy.

### 2026-11-05 — POS rate-limit safe search checkpoint

- Evidence: POS September 2026 preview returned HTTP 200 / 0 invoices; multi-month Jan–Aug 2026 preview returned HTTP 429. Root cause (rate limit scope, threshold, pagination) remains unproven; no GDT live request executed by assistant.
- Read-only audit: `previewSource()` loops monthly and each month can paginate; `fetchInvoicesByMonth()` previously surfaced generic HTTP 429. Implemented conservative POS **preview-only** one-calendar-month guard before GDT call and before Livewire consumes the one-time CAPTCHA; Regular preview unchanged. POS canonical sync continues to use its existing service path, without any new rate-limit bypass.
- On HTTP 429, service stops immediately, preserves fail-closed/no partial export behavior, and displays a safe message plus numeric Retry-After seconds if present; no automatic retries, CAPTCHA bypass, token/cookie logging or infrastructure change.
- New `GdtPosRateLimitSafeSearchTest` verifies multi-month POS guard makes zero HTTP requests, Regular multi-month remains permitted, and HTTP 429 sends one request with safe user message. Await focused tests and module regression; then UI validation on POS single-month / multi-month, 429 and Regular. No TEST/UI PASS claimed, no merge/deploy.

### 2026-11-05 — POS purchase filter parity and empty-preview safety (pending checkpoint)

- User-provided GDT browser evidence: July 2026 POS purchase returned 10 invoices with `search=tdlap=ge=01/07/2026T00:00:00;tdlap=le=31/07/2026T23:59:59;ttxly==8`, sort `tdlap:desc`, size 15; Laravel preview with same month returned 0 while source used `ttxly==5`. This is strong evidence for filter mismatch, not yet a verified post-fix GDT response. CAPTCHA keys/answers are not recorded.
- Changed shared POS fetch filter to `ttxly==8` (Regular unchanged); retained endpoint, size 50, CAPTCHA one-time verification, month guard for preview, HTTP 429 fail-closed and no partial export. Existing older notes referring to `ttxly==5` describe historical code only.
- Empty successful preview now shows `Không tìm thấy hóa đơn`, disables canonical button and rejects forged/direct Livewire `run()` with empty count. No persistence or queue action on empty preview.
- Updated focused contracts: `GdtPosCaptchaFlowTest`, `GdtPosHttpDiagnosticsTest`, `GdtTabSearchContractTest`. Checkpoint pending: focused tests, then `tests/Feature/Invoices`, then user-confirmed desktop/tablet/mobile UI on July POS purchase with fresh CAPTCHA and zero-result state. No tests or UI PASS claimed, no PR/merge/deploy.

### 2026-11-05 — POS CAPTCHA two-step UX (checkpoint pending)

- User observed GDT browser flow: click Search → CAPTCHA prompt → enter human answer and confirm → invoice list. Implemented equivalent POS-only UI flow: search opens CAPTCHA dialog, confirmation invokes existing preview; canonical sync opens a fresh CAPTCHA dialog and confirmation invokes existing run. Regular search/queue behavior preserved. POS month guard applies before CAPTCHA request. Switching source/date/direction closes dialog and clears UI challenge; CAPTCHA answers never go to queue. Backend GDT CAPTCHA cookie/session behavior and HTTP 429 handling unchanged.
- UI change alone does **not** establish why POS July 2026 returned 0: GDT browser returns 10 with `ttxly==8`, parser shape matches, but server-side GDT response still unobserved. Do not claim data integration fixed.
- Focused tests: `GdtTabSearchContractTest`, `GdtPosCaptchaFlowTest`, `GdtPosRateLimitSafeSearchTest`, `GdtCanonicalPosSyncContractTest`; then module regression `tests/Feature/Invoices` only if focused PASS. User must confirm UI on desktop/mobile. No tests executed by assistant, no UI PASS, PR/merge/deploy.

### 2026-11-05 — POS HTTP 200 metadata diagnostic checkpoint

- POS HTTP 200 now emits one allowlisted `GDT POS invoice query response metadata.` log per page: source, action, page, HTTP status, content-type category, numeric response total, datas count/type, page size, POS filter presence, cookie presence, pagination-state presence. No raw payload, token, URL, CAPTCHA, cookie or invoice data. Regular unchanged.
- Tests added to `GdtPosRateLimitSafeSearchTest` using HTTP fakes and log spy for populated and empty POS responses, asserting no sensitive fixture values in metadata. Focused and module regression pending actual DELL execution; UI still returns 0 vs GDT browser 10, root cause not yet confirmed. Do not merge or deploy.
- Diagnostic checkpoint: after successful focused/module tests, make at most one user-initiated POS query and inspect only the allowlisted log line. Do not paste full application logs or secret values. Compare response_total/datas_count/page_size/cookie boolean to GDT browser result before changing query parameters.

### 2026-11-05 — POS malformed-success fail-closed fix

- User confirmed target is POS **purchase**. Recent `purchase` metadata with `has_captcha_cookie=false` may be from HTTP fake tests; do not infer live GDT behavior. The latest `sold` record is not proof of purchase behavior.
- POS-only HTTP 200 guard now requires `datas` to be a list and `total` to be a nonnegative integer >= current page count. Otherwise throw a clear exception, never return false 0 or persist canonical. Regular behavior untouched. HTTP 429 and CAPTCHA flow unchanged.
- HTTP fake tests added for malformed success and ten-row purchase; existing empty-list test covers legitimate zero. Focused tests and module regression pending DELL; no UI PASS or merge.

### 2026-11-05 — POS response shape diagnostics

- Added allowlisted metadata `response_shape`, `datas_type`, `total_type`, normalized `content_type`, and `request_action` to existing POS HTTP 200 diagnostic. MIME is normalized to json/html/other, not logged raw. No response body, token, CAPTCHA, cookie or invoice fields. No request/page-size behavior changes.
- Added HTTP fake test for HTML 200 and extended JSON 200 assertions; focused/module regression and UI pending local execution. Investigate **purchase** after tests; do not merge/deploy.

### 2026-11-05 — POS cookie lifecycle investigation

- Added safe count-only diagnostics at CAPTCHA loaded, consumed, and outgoing request jar stages. No cookie names/values, CAPTCHA key/answer, token or raw HTTP body logged. Cookie jar now constructed consistently for POS even when no cookies are supplied; does not invent session cookies. No changes to size=50, search filter, canonical, or Regular.
- Added fake test asserting cached cookie snapshot survives one-time consume and reaches Guzzle CookieJar. HTTP fakes cannot prove a live upstream Set-Cookie or actual wire-level Cookie header; interpret logs accordingly.
- User target is POS purchase July 2026 (official GDT browser 10, Laravel 0); previous zero-count log had has_captcha_cookie=false. Investigate whether GDT CAPTCHA GET emits Set-Cookie at all before concluding cookie loss. Focused/module tests pending; no UI PASS/merge/deploy.

### 2026-11-05 — POS page-size parity experiment

- Observed official GDT POS purchase request uses `size=15` while Laravel used `size=50`; POS only now uses 15, Regular retains 50. No change to endpoint, search, CAPTCHA, cookies, canonical or 429 handling.
- Updated POS HTTP diagnostic tests and positive purchase query contract to assert 15. This is a controlled comparison, not proof that page size caused the observed 0-invoice discrepancy.
- Focused tests, module regression and manual UI verification pending on local checkout. No merge/deploy.

### 2026-11-05 — POS purchase trace correlation (diagnostic-only)

- POS fetch now generates one random trace_id per monthly request and logs request metadata, cookie-jar lifecycle and HTTP response metadata with the same trace_id. Logs mark runtime as test/application to distinguish HTTP fake fixtures from manual UI runs. Response Location is reduced to an allowlisted path (no host/query), when available; this is not a complete redirect history or proof of wire-level cookies.
- POS page size remains 15; no changes to API query, CAPTCHA, canonical, retry policy or UI. HTTP 200 malformed payload remains fail-closed.
- Added focused purchase correlation/security test; adapted HTTP 500 diagnostics mock to accept new safe fields. Focused tests and module regression pending local execution; no UI PASS, merge or deploy.

### 2026-11-05 — POS purchase default-direction alignment

- Read-only GitHub audit found `SearchHoadon::$vatIn = false` (sold) while Blade `wire:model.live="vatIn"` lists `<option value="1">Mua vào</option>` first. This mismatched initial UI selection and server-side request direction; the observed application trace went to `sold` and returned HTML.
- Set default `$vatIn = true` to align first visible option with purchase endpoint. Explicitly selecting Bán ra remains supported; source tabs Regular/POS, CAPTCHA, parser, API query and canonical persistence remain unchanged.
- Added default-direction/option-order contract regression. Focused tests, module regression and manual UI verification pending on DELL. Do not claim 10 POS purchase invoices or UI PASS until observed. No PR/merge/deploy.

### 2026-11-05 — POS HTTP 200 HTML redirect-count diagnostics (checkpoint pending)

- Verified application purchase trace at 22:40:55: HTTP 200, HTML, no datas/total; cookie jar contained 4 items. Correct purchase mapping does not resolve HTML upstream response.
- POS requests now enable Guzzle redirect-history tracking and log only `redirect_count` (bounded 0..20) and `html_response_class` (`HTML_AFTER_REDIRECT`, `HTML_DIRECT_OR_UNTRACKED`, `NOT_HTML`). No redirect URL, HTML body, token, CAPTCHA, cookie value, query string or headers are logged. Existing safe Location-path diagnostic remains.
- Redirect tracking does not change redirect-follow behavior and cannot prove a cookie was sent or accepted. HTTP fake tests cover direct HTML and synthetic tracked redirects, with fail-closed schema guard. No live request executed by assistant.
- Checkpoint: focused `GdtPosRateLimitSafeSearchTest`, `GdtPosHttpDiagnosticsTest`, `GdtTabSearchContractTest`; then module regression only after focused PASS; user UI verification required. Investigate authenticated session/cookie/header differences only after observing a fresh application purchase trace. No PR/merge/deploy.

### 2026-11-05 — POS CAPTCHA/search HTTP client parity checkpoint

- Application trace `dc26b3af-...` showed POS purchase HTTP 200 HTML without tracked redirects. Read-only source audit found CAPTCHA `GdtApiService::baseClient()` explicitly sets `invoices.gdt.user_agent`, while POS `GdtInvoiceService::client()` did not; request behavior differed even when Origin/Referer/Accept matched.
- POS only now uses the same configured User-Agent as the CAPTCHA client. Added safe `cookie_eligible_for_endpoint` boolean using Guzzle CookieJar matching against the POS URL; it checks whether a cookie header could be assembled, not actual wire delivery or upstream acceptance. Log also marks User-Agent parity without printing the value. Regular client untouched.
- Focused fake test verifies configured POS User-Agent, cookie eligibility, and no cookie/CAPTCHA values in metadata. No HTML body parsing, CAPTCHA bypass, retry, canonical changes, or live GDT request. Pending focused and module regression and user UI PASS; no PR/merge/deploy.

### 2026-11-05 — Fix unsupported CookieJar API (focused tests pending)

- Local focused output: 12 failed, 5 passed (49 assertions), all failures at `GdtInvoiceService.php:396`: `CookieJar::getCookieHeader()` does not exist.
- Replaced with supported `CookieJar::withCookieHeader(new Request('GET', ...))` and checked `hasHeader('Cookie')` on returned PSR-7 request. No cookie values logged, no HTTP request added, no canonical changes.
- Await local focused tests before module regression or UI. No TEST PASS asserted, no PR/merge/deploy.

### 2026-11-05 — POS HTML header-only diagnostics checkpoint

- Actual application request at 22:47:33: purchase, cookie eligible, 4 jar cookies, User-Agent parity; HTTP 200 HTML without tracked redirect. Authentication/session acceptance remains unproven.
- Added fixed-label `html_response_kind` and allowlisted booleans for Set-Cookie, WWW-Authenticate, Cache-Control, Content-Length presence in POS response metadata and HTTP failure metadata. No raw header values, HTML body, CAPTCHA values, tokens, cookies, URLs or invoice data logged. Classification is header-only; `HTML_UNCLASSIFIED` does **not** mean login page or access denial.
- Added fake HTTP regression for HTML fail-closed behavior and safe diagnostics. No CAPTCHA bypass, retry, parsing relaxation, persistence or production changes. Focused tests pending user checkpoint, then module regression and UI verification.

### 2026-11-05 — Header absence regression correction

- Focused checkpoint: 17 passed, 1 failed (211 assertions); HTML fixture expected `HTML_UNCLASSIFIED` but received `HTML_AUTH_CHALLENGE_HEADER` because Laravel response header accessor can yield empty string for absent header.
- Updated safe response-header presence checks (Set-Cookie, WWW-Authenticate, Cache-Control, Content-Length) and authentication-challenge classification to require nonempty trimmed values, not merely non-null. No sensitive values logged. Re-run focused tests before module regression; no TEST/UI PASS claimed.

### 2026-11-06 — Browser POS request parity checkpoint

- User-supplied successful July 2026 purchase request confirms GET `/api/sco-query/invoices/purchase`, `size=15`, `sort=tdlap:desc`, `ttxly==8`. Browser additionally sends search-page Referer, URL-encoded Action, End-Point, ckey/cvalue headers and Accept-Language=vi. Browser includes multiple session cookies; these were **not** copied or stored in repository.
- Scoped POS-only adjustment: send search-page Referer, encoded Action, ckey/cvalue request headers and Accept-Language; preserve query parameters, CookieJar, bearer token, fail-closed validation, and canonical behavior. Existing configured User-Agent unchanged. Header presence and values are asserted only in HTTP fake tests; secrets never logged.
- No claim that headers alone resolve HTTP 200 HTML. Need local focused tests, then module regression, then UI verification with fresh CAPTCHA. No PR/merge/deploy.

### 2026-11-06 — POS authentication-to-CAPTCHA cookie continuity

- Read-only audit found `GdtApiService::login()` persists authenticated cookies under `<cache_key>:auth-session-cookies`, but `loadPosCaptcha()` previously started with a fresh CookieJar, severing the authenticated cookie lineage. POS search only inherited the CAPTCHA snapshot.
- Changed `loadPosCaptcha()` to start from `restoreSessionCookies()`; GDT /captcha response may update that jar, and its snapshot is still cached for the existing single-use `consumePosCaptcha()` flow. Added safe count `auth_cookie_count_before_captcha` to loaded-stage metadata (no names/values). No forced retries, no CAPTCHA bypass, no change to canonical or regular invoice path.
- Added focused fake test `GdtPosAuthSessionContinuityTest` for authenticated snapshot continuity and log secrecy. This is a plausible session-break correction, not proof GDT will return JSON. Await local focused tests before module regression and UI. No merge/deploy.

### 2026-11-06 — Authentication cookie snapshot deletion root cause

- Audited `GdtApiService::login()`: it persisted the authentication CookieJar before parsing the response, but on successful token cache write it immediately called `Cache::forget($this->sessionCacheKey())`. This unconditionally deleted the very cookie snapshot later consumed by `loadPosCaptcha()`. The observed `auth_cookie_count_before_captcha=0` is consistent with this concrete code defect.
- Removed that destructive cache forget. Successful login now logs only safe `auth_cookie_count_after_login` and `auth_cookie_snapshot_present_after_login` diagnostics. Token and cookie values remain excluded from new logs. Added fake HTTP regression test verifying login retains the snapshot. No changes to regular search, CAPTCHA validation, POS fail-closed canonical, or production.
- Local focused tests and UI are pending. Preserving cookies fixes the confirmed cache deletion bug, but GDT JSON response remains unverified until a fresh end-to-end UI test. No PR/merge/deploy.

### 2026-11-06 — Read-only cookie restoration diagnostics checkpoint

- `GdtApiService::restoreSessionCookies()` now emits `GDT authentication cookie restore diagnostics.` with only five safe fields: `cache_entry_present`, `snapshot_is_array`, `snapshot_cookie_count`, `restored_cookie_count`, `restore_failure_count`. No cookie names/values, credentials, CAPTCHA, raw HTML or cache key are logged by this new event. Existing behavior for malformed entries is unchanged.
- Added focused test with one valid and one malformed snapshot entry; verifies restoration counts and secret exclusion. Pending local test execution and UI validation. Keep POS HTTP 200 HTML fail-closed; do not merge or deploy until gates pass and user approves.

### 2026-11-06 — POS HTML fingerprint diagnostic checkpoint

- After confirming 4/4 authentication cookies restored and POS still returning HTTP 200 HTML, added `safePosHtmlFingerprint()` to the POS success-response metadata only when Content-Type is HTML. It emits bounded, fixed-label fields: body-size band, HTML doctype/tag presence, simple title category, coarse Server category, meta-refresh presence. No raw title, body, hash, URL, header value, token, cookie or CAPTCHA is logged; classifier is heuristic, not evidence of a specific upstream blocking mechanism.
- Extended `GdtPosRateLimitSafeSearchTest` to assert fingerprint and secret exclusion. The existing HTML fail-closed behavior and canonical protection are unchanged. Focused tests, module regression and UI verification remain pending; no PR/merge/deploy.

### 2026-11-06 — POS HTTP HTML diagnostic classification follow-up

- UI evidence: 05:58:22 POS purchase HTTP 200 HTML, 1KB–10KB, doctype, title class ERROR, server class UNSPECIFIED. Cookies were restored 4/4 in the preceding session; upstream reason remains unknown.
- Extended `safePosHtmlFingerprint()` with `error_category` (GATEWAY_OR_UPSTREAM, ACCESS_OR_CHALLENGE, SESSION_OR_LOGIN, APPLICATION_OR_SERVER, UNCLASSIFIED) and `gateway_header_class` (fixed-label header-presence classification). No HTML/body/header values, credentials, cookies or CAPTCHA are logged. Existing fail-closed canonical behavior unchanged.
- Added focused regression for synthetic 502 HTML served with HTTP 200 and sensitive marker strings; tests must be run locally before module regression. No PR, merge or deployment; UI PASS pending.


### 2026-11-06 — POS request transport contract focused checkpoint

- User-approved diagnostic-only batch after read-only audit of Chrome successful JSON vs Laravel HTTP 200 HTML ERROR. Local PHP 8.3.6, libcurl 8.5.0, OpenSSL 3.0.13, HTTP/2 capability and Guzzle 7.15.3 verified by user; actual on-wire HTTP protocol and upstream reason remain unknown.
- Added `tests/Feature/Invoices/GdtPosRequestTransportContractTest.php` with no-network checks for Guzzle cookie eligibility/domain isolation, POS purchase query/header contract, safe metadata and HTTP 200 HTML fail-closed. This is a Laravel HTTP-fake contract, not a proof of actual wire-level cookie delivery or browser transport equivalence.
- No changes to production, GDT credentials, service behavior, User-Agent, CAPTCHA, canonical persistence or regular invoice source. No live GDT request from assistant.
- Checkpoint pending user local focused test results. If focused PASS, run Invoices module regression; UI PASS requires explicit user confirmation. No PR/merge/deploy.

### 2026-11-06 — POS request contract assertion isolation

- User focused checkpoint: 24 passed, 1 failed (299 assertions); new `GdtPosRequestTransportContractTest` failed at a compound `Http::assertSent()` predicate. This does not prove the HTTP request was absent; the predicate combined many conditions.
- Changed only the new test to select the fake POS request and assert each query/header separately, so the next failure identifies the mismatched field without dumping request headers or secrets. No service or production changes. Await focused rerun; module regression remains blocked until PASS.

### 2026-11-06 — POS End-Point header override focused fix

- Local focused checkpoint: 24 passed, 1 failed (308 assertions). Isolated fake-request assertion proved POS `End-Point` resolved to generic `/` instead of browser-observed `/tra-cuu/tra-cuu-hoa-don` despite POS `withHeaders()` call. This is a concrete Laravel request-contract mismatch, not yet proof of the upstream HTML cause.
- POS-only request now drops generic `Referer`, `Action`, `End-Point` before applying POS search-page values. Regular request client is unchanged. Existing focused contract checks all three headers and query, cookie eligibility, HTML fail-closed, and safe logs.
- Pending local focused rerun, then module regression only on PASS; no UI PASS, PR, merge or deploy.

### 2026-11-06 — Fix unsupported PendingRequest::withoutHeader

- User focused result: 16 failed, 9 passed (74 assertions); all 16 failures are BadMethodCallException because Laravel PendingRequest does not expose withoutHeader(). This is a code regression from the prior diagnostic fix, not evidence of upstream GDT behavior.
- Replaced unsupported header-removal call with POS-only PendingRequest factory (clientForPos) that starts without generic Referer/Action/End-Point and retains token, SSL, timeout, Origin, Accept, request-id, configured User-Agent, CookieJar and redirect tracking. The POS-specific headers are then applied by the existing request flow. Regular invoice client unchanged.
- No live API call, CAPTCHA bypass, canonical changes, PR, merge or deploy. Focused tests must rerun; module regression only after focused PASS. UI remains unverified.

### 2026-11-06 — POS canonical admission focused regression checkpoint

- User confirmed two-tier test PASS and POS UI preview displayed 10 purchase invoices for July 2026; canonical synchronization itself remains unverified.
- Added two HTTP-fake focused tests to existing `GdtPosRequestTransportContractTest`: preview of 10 POS rows does not enter DB transaction; incomplete POS pagination fails before canonical DB transaction. Tests do not contact GDT or use real credentials.
- Read-only audit confirms `processRange` deduplicates before `persistInvoices`, and persistence tracks created/updated/unchanged in a transaction; this is source behavior, not evidence of successful repeated sync. Idempotency/database integration tests remain outstanding.
- Await local focused tests then Invoices module regression if PASS. Manual canonical UI and repeat-sync verification required before PR/merge. No production actions, no merge/deploy.

### 2026-11-06 — POS canonical idempotency SQLite focused checkpoint

- User confirmed two-tier test PASS and clean tracked working tree at bb4e16135. This new batch has not been tested yet.
- Added isolated in-memory SQLite regression in `GdtPosRequestTransportContractTest` exercising private `persistInvoices` through reflection with synthetic 10-row POS fixture: first insert, identical replay unchanged/no duplicates, GDT RAW source record linkage, one amount correction, and purchase/sold identity isolation. No live GDT call and no production database use.
- SQLite PDO absence marks test skipped, which is NOT idempotency PASS; report skip explicitly. Test uses minimal SQLite schema and may need adjustment to match model hooks; stop on focused failure. Run focused tests before Invoices module regression. UI canonical sync still requires user confirmation. No PR/merge/deploy.

### 2026-11-06 — October POS cross-month canonical regression checkpoint

- DELL local MySQL read-only baseline for October 2026 purchase invoices: 117 canonical, 117 GDT RAW links, 0 duplicate legal-identity groups. User's POS preview for 2026-10-01 through 2026-10-31 returned six rows; one has source-issued date 2026-09-30. A symbol+number-only read-only lookup found four existing and two missing; this is not a complete legal-identity match.
- Added isolated SQLite test `test_pos_october_replay_preserves_september_invoice_date_boundary`: two synthetic POS headers (October and September) persist to their respective issued-date months, replay unchanged with stable invoice IDs and RAW GDT linkage. Test does not contact GDT or write local MySQL; skips if SQLite PDO is absent.
- No production action or source behavior change. Await local focused test PASS, then Invoices module regression. User-authorized manual POS canonical sync on DELL development/test still requires fresh CAPTCHA and UI execution; compare before/after October and September counts, RAW links, and repeat-sync idempotency. UI canonical PASS not yet claimed; no PR/merge/deploy.


### 2026-11-06 — POS verified canonical recovery, idempotency and debug runbook

- User-confirmed two-tier focused + Invoices module regression PASS after SQLite date assertion fix `dd9b9452b`; DELL local branch clean at that commit. New documentation commits need local pull; no tests were run by assistant.
- Manual POS purchase canonical UI sync for 2026-10-01 through 2026-10-30: 6/6 returned, created 2, updated 0, unchanged 4, RAW header 6, details reused 4/fetched 2/failed 0. DB verified 2026-10 canonical/RAW 119/119 (previously 117/117); 2026-09 111/111 unchanged; two previously missing symbol+number records each matched once.
- Second sync of same range: created 0, updated 0, unchanged 6, RAW detail reused 6/fetched 0/failed 0. POS idempotency verified by UI log. POS preview is read-only; POS requires fresh CAPTCHA for sync and does not use queue.
- Added permanent root-cause investigation and operational troubleshooting reference: `docs/modules/Invoices/GDT_POS_DEBUG_RUNBOOK.md`. Includes HTTP 200 HTML fail-closed, POS End-Point header mismatch, unsupported PendingRequest::withoutHeader regression and POS-only client fix, safe session diagnostics, cross-month issued-date test, real-world sync evidence and no-secret logging rules.
- PR review/GitHub checks and any applicable impacted regression remain gates; no production deployment or merge authorization.


### 2026-11-06 — PR #362 pre-merge review: queued POS fail-closed checkpoint

- PR #362 is Draft, GitHub CLI reports MERGEABLE/CLEAN, no checks reported; DELL was clean at `686cf7a1a`. The branch is large (183+ commits), so merge is not authorized.
- Review found a mismatch: POS UI requires a fresh interactive CAPTCHA and disables queue, but `ProcessGdtInvoicesJob` still accepted `source=pos` and invoked `processRange` without CAPTCHA. Such a queued invocation cannot complete the approved POS flow. Added explicit fail-closed guard in the job for `source=pos`, preserving regular/all job paths, and a focused regression test asserting the POS job does not call GDT canonical service.
- This is a new untested checkpoint. User must pull and run focused test first, then Invoices regression only on PASS; assess impacted regression and UI confirmation as needed. No CI checks, no merge or production deployment.


### 2026-11-06 — POS queue guard focused and module regression verified by user

- User reported **two-tier TEST PASS** after fix commit `e212d9ee8`: `tests/Feature/Invoices/GdtCanonicalSourceSelectionContractTest.php` focused PASS, followed by `tests/Feature/Invoices` module regression PASS. Test output totals were not supplied; do not invent counts.
- Prior focused failure was a PHPUnit `ClassIsFinalException` from mocking `final InvoiceSourceCoverageService`, not a POS service failure. Fixed by constructing the real stateless coverage service in the test; job guard remains unchanged.
- GitHub compare at this checkpoint: feature ahead of main 188 commits, behind 0, 26 changed files confined to Invoices implementation/tests/docs. PR #362 remains Draft. GitHub CLI earlier reported MERGEABLE/CLEAN and no checks reported. Review and any applicable impacted/manual UI gates remain before merge; no production action.


### 2026-11-06 — PR #362 review batch: canonical POS month guard

- Read-only review of `GdtInvoiceService::processRange`, `SearchHoadon::run`, `ProcessGdtInvoicesJob`, and POS preview tests found that preview POS rejects multi-month requests, while canonical `processRange(source=pos)` lacked the same validation. A direct service caller could otherwise submit multiple POS monthly requests using one CAPTCHA challenge.
- Added source-scoped guard in `processRange` for POS date ranges spanning different calendar months; `regular` and `all` remain unaffected. Added HTTP-faked regression `test_multi_month_pos_canonical_sync_fails_before_any_gdt_request` in `GdtPosRateLimitSafeSearchTest`.
- **Checkpoint pending**: code committed on feature branch; focused tests and Invoices module regression must run on Admin and report results. Do not mark TEST PASS or UI PASS for this batch yet. PR remains Draft; no merge or production operations.


### 2026-11-06 — POS canonical month guard: user-verified two-tier TEST PASS (Admin)

- User confirmed focused tests PASS for `GdtPosRateLimitSafeSearchTest`, `GdtCanonicalSourceSelectionContractTest`, and `GdtSourceChunkRetryContractTest`, followed by `tests/Feature/Invoices` module regression PASS on Admin after checkpoint `1b0c0bcb7`.
- Guard applies only to `processRange(source=pos)` across calendar months; regular/all paths unchanged by this batch. Test counts/output were not provided, so do not invent totals.
- Next gate: final PR review, assess any impacted dependencies, confirm applicable UI acceptance, and obtain explicit user authorization before merging. No production action.

### 2026-11-06 — Admin UI acceptance for PR #362

- User explicitly confirmed **UI PASS** for the Admin Invoices workflow after the two-tier focused and module regression PASS at feature commit `482cc6203`.
- UI acceptance is user-reported, not independently reproduced by the GitHub connector. No CI status or impacted/cross-module regression is implied by this confirmation.
- Preserve Draft PR #362 until final review gates and explicit merge authorization; no production deployment.


## 2026-11-06 — ClientPortal Invoices PWA regular/POS sync checkpoint (PENDING TEST/UI)

- Branch: `feat/client-invoices-pwa-pos-sync-20261106`, created from latest `main` after PR #362 merge. No production action.
- Route `/apps/invoices/sync` reworked as a focused mobile-first PWA workspace with local back navigation, responsive direction/source/date controls, status, GDT login CAPTCHA and separate POS challenge. Uses shared `<x-pwa-date>` and ClientPortal layout; no new module/database tables.
- ClientPortal remains the web-guard authorization/presentation adapter; canonical POS preview/persistence still belongs to `GdtInvoiceService`. Regular sync now explicitly dispatches `ProcessGdtInvoicesJob(source=regular)`; legacy `source=all` queue behavior is not modified in the domain job.
- POS flow: web-user+session scoped ephemeral challenge; one-month guard; preview count in server session; fingerprint checked before sync; fresh CAPTCHA for preview and for direct canonical sync; no POS CAPTCHA/answer in queued jobs or browser persistent storage. The existing `GdtApiService` CAPTCHA key API accepts string scope while preserving admin integer callers.
- PWA sync feature title/eyebrow/description flow through manifest + `ClientPortalSettingsService::featurePresentation()`; editable from `/admin/client-apps`. Existing application/feature permission remains `client.invoices.sync`.
- Added `tests/Feature/ClientApps/InvoicesPosPwaSyncContractTest.php` for web middleware, canonical source, queue guard, managed presentation and PWA date control.
- **Known limitations**: POS direct synchronous request can hit PHP/web-server timeout on large upstream responses; keep range narrow and monitor GDT rate limiting. POS-generated Excel remains server-side; no new PWA download action is exposed in this batch. Existing regular queue status/download behavior otherwise remains unchanged. Do not infer UI PASS from source checks.
- **Checkpoint**: first `php artisan test tests/Feature/ClientApps/InvoicesPosPwaSyncContractTest.php tests/Feature/Invoices/GdtCanonicalSourceSelectionContractTest.php tests/Feature/Invoices/GdtPosRateLimitSafeSearchTest.php`. Only after PASS run `php artisan test tests/Feature/Invoices` and relevant ClientPortal/ClientApps regression. Manual UI acceptance on desktop/tablet/iPhone installed PWA: GDT login, regular sync, POS preview CAPTCHA, POS sync new CAPTCHA, month guard, permission denial, refresh, errors and navigation. No TEST PASS or UI PASS claimed; PR Draft until gates.


### 2026-11-06 — POS result UI follow-up checkpoint (PENDING TEST/UI)
- User screenshot confirmed POS preview displayed six invoices and subsequent POS sync returned `completed`; no independent DB deduplication check was provided.
- Root cause: after `submitPosCaptcha()` consumes server preview state, the redirected PWA view previously defaulted source to `regular` and dates to readiness suggestions, despite the completed POS status.
- Fix: `ClientInvoiceWorkspaceService::syncPos()` records `start`/`end` alongside existing `source=pos` and `direction` in server-side sync status. PWA Blade derives completed POS filters from `syncStatus` after redirect/refresh and displays a POS source/direction/date label in result panel. No new browser storage or CAPTCHA persistence.
- Added focused contract assertion in `InvoicesPosPwaSyncContractTest`. **TEST PASS and UI PASS not claimed** for this follow-up until Admin verification. No production action, no merge.


### 2026-11-06 — GDT POS pagination CAPTCHA 401 checkpoint (PENDING TEST/UI)
- Evidence: Admin local log 11:59–12:02, login HTTP 200 and 4 cookies restored; POS page 1 HTTP 200, 15/17 rows; page 2 HTTP 401 with GDT JSON message `Mã captcha không đúng.`. Repeated across requests. This is not proof of expired auth token.
- Root cause in source: `fetchInvoicesByMonth()` rebuilt a new POS `CookieJar` from the original CAPTCHA snapshot inside each pagination iteration, dropping any upstream `Set-Cookie` updates between pages. Also classified all HTTP 401 as expired auth and forgot the token.
- Fix on PR #363: initialize one mutable POS CookieJar before the pagination loop and pass it to each page request; preserve existing CAPTCHA headers, state cursor, page size 15, `ttxly==8`, fail-closed partial-data behavior. For HTTP 401 whose structured GDT message contains `captcha`, report a specific POS CAPTCHA error and keep the login token; genuine auth 401 still invalidates token. No secrets logged or stored in browser.
- New focused HTTP-fake/contract regression `tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php`: page 1 15/17 then page 2 CAPTCHA 401 must fail without partial success and preserve token; genuine auth 401 must invalidate token; cookie jar must be reused across pages. HTTP fake does **not** prove real upstream cookie behavior; verify on Admin UI after tests.
- Focused first: `php artisan test tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php tests/Feature/Invoices/GdtPosRateLimitSafeSearchTest.php tests/Feature/ClientApps/InvoicesPosPwaSyncContractTest.php`. Only after PASS run `php artisan test tests/Feature/Invoices` and impacted ClientPortal/ClientApps tests. **TEST/UI NOT VERIFIED** for this batch. No merge, no deploy, no environment changes.


## 2026-11-06 — POS page 2 CAPTCHA 401 follow-up (chưa xác nhận UI)

- Audit: GDT Admin logs show POS page 1 HTTP 200 (15/17), page 2 HTTP 401 `Mã captcha không đúng.`. The previous `End-Point` mismatch fix remains present; do not revert `clientForPos()`.
- New working hypothesis: the `ckey`/`cvalue` CAPTCHA pair belongs to the **initial** search, while subsequent pages should use GDT's opaque `state` cursor without replaying CAPTCHA. This is **not yet proven against live GDT**; it is a controlled request-contract change, not a confirmed upstream specification.
- On PR #363, page 1 still sends CAPTCHA as headers and query; page 2+ sends the opaque `state` only (with normal search/sort/size and POS headers), preserving the same cookie jar, authentication and fail-closed validation. No bypass of the first-page CAPTCHA, no partial canonical persistence.
- HTTP-fake regression asserts page 1 CAPTCHA present, page 2 CAPTCHA absent, `state` preserved, 17/17 complete result; separate page-2 401 test requires fail-closed and login token retained. HTTP-fake cannot validate live GDT behavior.
- Focused checkpoint: `php artisan test tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php tests/Feature/Invoices/GdtPosRateLimitSafeSearchTest.php tests/Feature/Invoices/GdtPosRequestTransportContractTest.php tests/Feature/ClientApps/InvoicesPosPwaSyncContractTest.php`. If PASS, proceed to Invoices regression, impacted ClientPortal tests and Admin UI >15 invoices with a fresh CAPTCHA. If 401 persists, stop and collect redacted page 1/page 2 metadata; do not auto-retry or relax fail-closed. **TEST/UI NOT VERIFIED.**


### 2026-11-06 — Invoices regression gate: auth safety contract alignment
- Admin reports Invoices module regression: 133 PASS, 1 FAIL (1201 assertions), `GdtAuthenticationSafetyContractTest::invoice_query_rejection_diagnostics_distinguish_401_from_403_without_logging_token`. Failure was a literal-source assertion expecting `if ($res->status() === 401)`, which changed intentionally to `if ($res->status() === 401 && ! $isPosCaptchaRejection)`.
- Updated the test to assert the conditional token invalidation, explicit POS CAPTCHA rejection and unchanged logging safeguards. No production service logic changed in this batch.
- Re-run focused: `php artisan test tests/Feature/Invoices/GdtAuthenticationSafetyContractTest.php tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php`; only after PASS re-run `php artisan test tests/Feature/Invoices`. Previous 133 PASS does not establish a passing module gate; **TEST/UI NOT YET VERIFIED** after this commit.


### 2026-11-06 — Live POS page-two HTTP 500 diagnostic checkpoint
- User confirmed focused + Invoices module regression PASS on preceding batch; Admin UI remains FAIL. Live trace `2d536258-5aba-43a8-b75d-a8f3aad1a2a6`: purchase page 1 HTTP 200 JSON, page 2 HTTP 500 JSON, page 2 has pagination state, CAPTCHA only submitted page 1, cookie jar 4/4 on both pages with endpoint eligibility true. Cookie eligibility does not prove upstream acceptance; upstream pagination contract remains unknown.
- No evidence yet for changing page 2 CAPTCHA, state, sort, size or search. Preserve the current fail-closed behavior and avoid automated retries.
- Added allowlisted POS 5xx diagnostic shape/category/field-presence metadata only; no raw JSON message, payload, token, cookies, CAPTCHA or invoice data. Added HTTP-fake regression for 15/17 then HTTP 500 page 2 failing closed and preserving token, plus source-level logging guard.
- Checkpoint: `php artisan test tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php tests/Feature/Invoices/GdtPosRateLimitSafeSearchTest.php`; after PASS run `php artisan test tests/Feature/Invoices`. **New batch tests NOT YET RUN; UI FAIL.** Next step is a single authorized live UI attempt with fresh CAPTCHA, capture only safe POS failure metadata, compare against an authorized GDT browser pagination contract. No merge or deploy.


### 2026-11-06 — POS pagination 15: actionable failure classification
- Real UI evidence: 01–10/09 preview returned 6 POS purchase invoices, 11–30/09 returned 12; full 01–30/09 failed. A separate full-month attempt had a page-1 CAPTCHA rejection; earlier trace had page 1 HTTP 200 then page 2 HTTP 500 JSON. Neither proves CAPTCHA TTL expiry; 6+12 is not independently verified as the exact full-month canonical count.
- Keep POS `size=15`, opaque `state`, CAPTCHA first-page behavior and one mutable cookie jar unchanged until a verified GDT browser request contract exists. No automated splitting/reuse of CAPTCHA or retries.
- For POS page >1 HTTP 5xx, return explicit page/HTTP/processed-vs-total diagnostics and suggest **manual** shorter same-month date windows with fresh CAPTCHA; preserve fail-closed. Regression asserts 15/17 -> page 2 HTTP 500 and no false CAPTCHA-expiry diagnosis. Previous 5xx allowlisted logging remains.
- New batch test status **NOT RUN**; Admin full-month UI **FAIL**. Checkpoint focused: `php artisan test tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php tests/Feature/Invoices/GdtPosRateLimitSafeSearchTest.php`; if PASS module: `php artisan test tests/Feature/Invoices`. Do not merge/deploy. Full solution needs authorized, sanitized browser-vs-Laravel page-two request evidence.


### 2026-11-06 — Interactive POS pagination batch 1 (foundation only)
- User observed official GDT website prompts for **new CAPTCHA when moving to page 2**, then displays the remaining two invoices (15/17 on page 1). This is behavioral evidence, **not** a sanitized successful page-2 HTTP contract. Do not assume whether page 2 submits `state`, `ckey`, `cvalue`, or other fields.
- Added `GdtPosPaginationProgress` immutable credential-free progress metadata and `GdtPosPaginationProgressTest` covering 15/17 next page, completion, invalid counts and fixed page size 15. This does **not** implement the HTTP continuation, temporary invoice storage, CAPTCHA refresh UI, or canonical commit; current full-month UI remains FAIL. Existing service requests remain unchanged.
- Do not store/replay browser JWT, cookies, CAPTCHA answers, or opaque state in source/logs. Sensitive values were shared in conversation; user should invalidate browser session. A sanitized successful page-2 request shape is required before wiring the next batch.
- Tests for this batch NOT RUN. Checkpoint: `php -l Modules/Invoices/Services/GdtPosPaginationProgress.php`; `php -l tests/Feature/Invoices/GdtPosPaginationProgressTest.php`; focused `php artisan test tests/Feature/Invoices/GdtPosPaginationProgressTest.php tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php tests/Feature/Invoices/GdtPosRateLimitSafeSearchTest.php`; only after PASS `php artisan test tests/Feature/Invoices`. No merge or production deploy.


### 2026-11-06 — POS Interactive Pagination batch 2 safety integration
- User confirms successful official GDT page-2 search after entering fresh CAPTCHA, with query parameter **names** `sort,size,search,ckey,cvalue` and no `state` in that list. This does **not** establish the mechanism selecting the second page: the sanitized response structure and browser UI's page selection mechanism remain unknown. Never infer that a second identical first-page query advances the cursor.
- Integrated `GdtPosPaginationProgress` into `GdtInvoiceService::fetchInvoicesByMonth` to reject overcount (e.g. 18/17) instead of treating it as complete. Added HTTP-fake regression. No changes to the upstream request shape or Regular behavior. **Interactive CAPTCHA page 2 remains NOT IMPLEMENTED** pending verified continuation protocol; no temporary invoice persistence or UI flow is claimed.
- Focused tests NOT RUN in GitHub connector. Checkpoint: `php -l Modules/Invoices/Services/GdtInvoiceService.php`, `php -l Modules/Invoices/Services/GdtPosPaginationProgress.php`, `php -l tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php`, then `php artisan test tests/Feature/Invoices/GdtPosPaginationProgressTest.php tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php`; if PASS, `php artisan test tests/Feature/Invoices`. UI full-month remains FAIL until verified otherwise; no merge or production deploy.


### 2026-11-06 — POS fresh CAPTCHA page-two request (opt-in service support)
- User confirms successful official page-2 response contains exactly 2 `datas` entries. Browser page-2 query has `sort,size,search,ckey,cvalue`; cursor mechanism not verified.
- Service now accepts an **optional** second CAPTCHA under `$posCaptcha['page2'] = ['ckey' => ..., 'cvalue' => ...]` and sends that fresh pair in both page-two query and headers, while preserving existing `state` cursor. No CAPTCHA replay; default callers and Regular behavior unchanged. HTTP fake regression added. This is a controlled opt-in integration seam, **not** proof that the upstream accepts this combination.
- **Not yet end-to-end interactive:** current UI does not collect page-two CAPTCHA; request still runs synchronously; browser continuation mechanism unverified; no persistent temporary results. Do not mark 17/17 UI PASS or merge.
- Tests NOT RUN: `php -l Modules/Invoices/Services/GdtInvoiceService.php`, `php -l tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php`; focused `php artisan test tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php tests/Feature/Invoices/GdtPosPaginationProgressTest.php`; module regression only after focused PASS.


### 2026-11-06 — POS interactive checkpoint foundation (batch 4, incomplete)
- Added `GdtPosPaginationCheckpoint` for session-bound, 5-minute cache of allowlisted **progress metadata only** (received/total/next page/date range), UUID lookup and discard. No invoice payload, cursor, cookies, tokens or CAPTCHA are persisted; cross-session access fails. Added focused tests.
- **This is NOT yet an interactive/resumable UI**: no controller, Blade/Livewire flow, secure pending invoice payload, or confirmed upstream page-selection protocol has been wired. Existing POS full-month UI 15/17 HTTP 500 remains FAIL; do not claim this fixes it or enables canonical. A metadata checkpoint alone cannot resume the upstream request.
- Tests NOT RUN. Checkpoint: `php -l Modules/Invoices/Services/GdtPosPaginationCheckpoint.php`; `php -l tests/Feature/Invoices/GdtPosPaginationCheckpointTest.php`; `php artisan test tests/Feature/Invoices/GdtPosPaginationCheckpointTest.php tests/Feature/Invoices/GdtPosPaginationProgressTest.php tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php`; module regression only if focused PASS. No merge/deploy.

### 2026-11-06 — Checkpoint regression fix
- User ran focused tests: 14 PASS, 1 FAIL (74 assertions). Failure: `GdtPosPaginationCheckpointTest` compares `create()` result including `id` against `find()` result omitting `id`.
- Fixed `find()` to reconstruct `['id' => $id] + $value` after session-scoped cache lookup. Cache continues to hold metadata only; no CAPTCHA/token/cookie/invoice content. No GDT HTTP logic modified.
- Fix NOT YET TESTED. Re-run focused checkpoint test first, then remaining focused tests, then Invoices module regression if focused PASS. UI still FAIL; do not merge.


### 2026-11-06 — Batch 5: failed POS preview UI recovery (limited scope)
- Audited actual `Modules/Invoices/Livewire/SearchHoadon.php` and `Modules/Invoices/resources/views/livewire/search-hoadon.blade.php`: the current flow is synchronous, consumes one CAPTCHA and calls `previewSource()`; on page-2 HTTP 500 it marks `searchState=failed`, with no resumable invoice payload. Existing canonical action is disabled unless `searchState=completed`.
- Added an explicit failed-POS warning and button to open **fresh CAPTCHA search** in the existing modal, with text stating that this is a **new search**, NOT a resume from page 2. Existing permission, fingerprint validation and canonical lock remain unchanged. Added source-level UI regression contract.
- **Not implemented:** partial-result persistence, page-2 continuation UI, or upstream page-selection mechanism. Full-month 17/17 UI remains FAIL until a real verified continuation exists. No false UI PASS claim.
- User reported previous checkpoint tests PASS (exact focused/module breakdown unspecified). New batch tests NOT RUN. Focused `php artisan test tests/Feature/Invoices/GdtPosFailedPreviewUiContractTest.php tests/Feature/Invoices/GdtPosCaptchaPaginationRegressionTest.php`; only if PASS `php artisan test tests/Feature/Invoices`. Manual UI check on desktop/mobile: failed POS shows retry and canonical disabled; retry opens fresh CAPTCHA. Do not merge/deploy.

### 2026-11-06 — Batch 5 UI contract test interpolation fix
- User reported focused test 8 PASS / 1 FAIL (58 assertions): `GdtPosFailedPreviewUiContractTest` evaluated Blade `$invoiceSource` and `$searchState` inside a PHP double-quoted assertion string, causing `Undefined variable $invoiceSource`.
- Fixed the assertion to use a single-quoted PHP literal with escaped inner quotes; also corrected canonical-lock assertion to include literal `$searchState` (escaped in double-quoted PHP). No production source or UI behavior changed.
- Tests NOT YET RE-RUN after this commit. Re-run the failing test first; then focused POS regression; then Invoices module regression if focused PASS. UI 17/17 remains FAIL; no merge.
