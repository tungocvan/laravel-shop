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
