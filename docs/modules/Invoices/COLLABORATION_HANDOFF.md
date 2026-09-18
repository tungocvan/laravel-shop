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
- Recovery: `invoices:recover-gdt-duplicates --year=2026 --type=sold` is dry-run by default. `--apply` is explicit and transaction guarded. A pair is eligible only when the two GDT rows share business fingerprint + header/detail hashes and exactly one row matches the canonical GDT identity.
- Reference safety: legacy `invoice_files` and inventory snapshot references are repointed only when the canonical side has no conflicting row; conflicts BLOCK the pair. Duplicate source rows are hash-verified, user/business metadata is merged conservatively, then the legacy source/invoice is removed.
- Idempotency: after a successful apply, rerunning the command should report zero eligible duplicate pairs.
- Checkpoint validation requested locally: focused contract test + Pint, dry-run must report the audited pair/reference counts before any `--apply`; only after dry-run acceptance should apply be executed and 483 rows / 55,926,358,092 VND be verified.
- No UI templates changed; manual smoke remains `/admin/invoices/hoadon-list` after recovery.
