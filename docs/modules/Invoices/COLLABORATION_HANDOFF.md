# Invoices Collaboration Handoff

## Current Status — Structured Lot / Expiry Canonical Mapping

- Module: `Invoices`, with versioned integration into `Inventory`.
- Active branch: `fix/invoices-structured-lot-expiry`.
- Current scope: preserve structured pharmaceutical lot number and expiry date from canonical GDT detail through Source Data, reconstructed PDF, Inventory contract/staging and Draft Receipt.
- Merge authorization: **NOT YET GIVEN**.
- No schema migration is required.
- Invoices → Inventory contract remains `1.0`; the lot/expiry/manufacture fields are optional backward-compatible line fields.

## Confirmed Root Cause and Source Evidence

The GDT raw detail was proven to contain the missing SINVICO data. For invoice id `2477`, source id `525`, supplier `CÔNG TY TNHH DƯỢC PHẨM SINVICO`, symbol `1/C26TSV`, invoice number `123`, product `CAMZITOL`, the raw line contains structured metadata under `hdhhdvu[].ttkhac`:

```text
ExpiryDate = 2028-03-08
LotNo      = G0846
```

Therefore GDT acquisition/retry/recovery is not the loss point. Do not OCR or parse the reconstructed PDF back into canonical data, and do not refetch GDT merely to recover these already-stored fields.

The loss occurred downstream because consumers only inspected legacy top-level keys such as `solo`, `lot`, `hsd` and `expiry_date`, while ignoring `ttkhac[].ttruong/dlieu`.

## Canonical Structured Field Rule

`Modules\Invoices\Support\GdtInvoiceLineMetadata` is the shared extractor for GDT line metadata.

Priority is mandatory:

```text
structured GDT metadata (`ttkhac`)
    -> legacy top-level field
    -> text parser fallback
    -> null
```

Structured values must never be overwritten by text parsing. No lot number or expiry date may be guessed or fabricated.

Recognized structured fields include:

```text
LotNo
ExpiryDate
ManufactureDate (when upstream provides it)
```

The existing text fallback remains supported for descriptions such as:

```text
Cefmetazol 2g ...; Lô: C60D001; HSD: 07/06/2027
```

`InvoiceLineNormalizer::VERSION` is now `deterministic-v4`, because normalization semantics changed and existing v3 snapshots may need local re-normalization to acquire structured metadata from stored raw lines.

## Invoices → Inventory Boundary

Invoices owns:

- raw/canonical invoice source detail;
- extraction and normalization of invoice-supplied structured lot/expiry/manufacture values;
- transmission of those optional values through integration contract `1.0`.

Inventory owns:

- item/product matching;
- warehouses and receipts;
- Inventory Lot entities;
- stock movements and balances.

Lot/expiry are proposed receipt-line values only. They do not alter product identity and do not cause automatic product creation. A Draft Receipt still does not increase stock; stock changes only on receipt confirmation.

## Source Data and Reconstructed PDF

Admin workspace remains:

```text
/admin/invoices/source-data
```

`SourceDataManager::normalizeDetailItem()` now uses the shared structured extractor and exposes line-level `lot_number` and `expiry_date`.

The Source Data detail modal displays:

- product/service name;
- lot number (`Số lô`);
- expiry date (`Hạn sử dụng`, UI format `d/m/Y`);
- unit, quantity, unit price, amount and tax rate.

The reconstructed GDT PDF also reads the same canonical structured extractor. `Số lô` and `Hạn sử dụng` columns are shown when the invoice contains these optional values; invoices without lot/expiry remain valid and must not receive fabricated values.

The PDF is a presentation of canonical data only. It is never a source to parse back into the system.

## SINVICO Acceptance Case

Acceptance invoice:

```text
Invoice id:      2477
Source id:       525
Symbol:          1/C26TSV
Invoice number:  123
Issued date:     2026-09-07
Supplier:        CÔNG TY TNHH DƯỢC PHẨM SINVICO
Product:         CAMZITOL
Expected lot:    G0846
Expected expiry: 2028-03-08 (UI/PDF: 08/03/2028)
```

Required end-to-end acceptance:

```text
stored GDT detail
-> canonical extractor
-> Source Data detail modal
-> reconstructed PDF
-> Inventory contract/staging
-> Draft Receipt proposal
```

Every downstream representation must preserve `G0846 / 2028-03-08`. Admin must not need to re-enter these values.

## Regression Contract

Required regression cases:

1. Structured metadata: `G0846 / 2028-03-08` is preserved independently of product-name regex.
2. Text fallback: `C60D001 / 2027-06-07` remains supported.
3. Conflict: structured `G0846 / 2028-03-08` wins over text `WRONG123 / 2030-01-01`.
4. Missing metadata: remains `null / null`; no fabrication.
5. Source Data and reconstructed PDF both use the shared extractor.
6. Inventory integration and Draft Receipt continue carrying lot/expiry.

Focused structured mapping, Inventory integration, bulk publication, receiving UI and Pint were user-reported **PASS** before the final Source Data contract checkpoint.

## Existing Canonical Source Data Behavior — Preserve

The Source Data workspace continues to read canonical source data already persisted in `invoice_source_records`. It must not make a GDT recovery/fetch call during classification.

Primary business classifications remain:

```text
UNCLASSIFIED
GOODS
SERVICE_EXPENSE
MIXED
```

Preserve existing Source Data filters, supplier-wide classification, expense classification, Dashboard classification, Excel detail export, GDT automatic recovery/retry/backoff and stable Livewire row/checkbox/label keys.

Existing baseline string-contract debt around older GDT acquisition/annotation implementation is outside this branch and must not be fixed by reverting production code to legacy implementation details.

## Next Step

1. Pull `fix/invoices-structured-lot-expiry`.
2. Run the final focused structured mapping test and Pint checkpoint.
3. Perform read-only runtime acceptance against SINVICO source id `525` / invoice id `2477`.
4. UI PASS on `/admin/invoices/source-data` and reconstructed PDF.
5. Update this handoff with final PASS evidence if needed.
6. Do **not merge** until explicit user authorization.
