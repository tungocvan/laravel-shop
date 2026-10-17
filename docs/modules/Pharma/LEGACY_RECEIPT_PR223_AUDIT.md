# Legacy PR #223 — Receipt document parity audit

This audit compares the old receipt-document workspace PR #223 with current `main`. It does **not** authorize merging or closing that PR.

## Confirmed replacements on main

- Receipt document settings routes and `InventoryReceiptDocumentSetting` exist.
- Invoice and cost PDF export/download/print use separate routes, instead of the old generic receipt PDF/print routes.
- Cost PDF routes require `view_pharma_inventory_costs`.
- Receipt document actions in the Admin listing are gated on the posted status.
- The current settings model contains separate invoice/cost titles and display controls, unlike the old PR model.

## Why the old PR should not be cherry-picked

PR #223 uses generic `receiptPdf`/`receiptPrint` endpoints, a single price presentation, and older document UI. Restoring these endpoints and templates would bypass or regress the newer separation and lifecycle. Keep its historical branch intact pending review.

## Still to verify before closing #223

- Locate the **current** migration that creates or modifies `pharma_inventory_receipt_document_settings` and verify all columns used by the current model and views. The original PR migration filename is not present on `main`; that alone does not establish a missing table.
- Run the targeted unit contract test and existing receipt document integration tests; check both permission-denied and posted-only cases.
- Review invoice and cost PDF outputs on the actual UI with a posted receipt; verify no cost fields leak into the invoice document.

## Checkpoint

Run `php artisan test Modules/Pharma/Tests/Unit/InventoryReceiptLegacyPrParityContractTest.php` on a development machine, not production. Do not merge this branch until TEST PASS and UI/review acceptance. PR #290 is a separate documentation follow-up.
