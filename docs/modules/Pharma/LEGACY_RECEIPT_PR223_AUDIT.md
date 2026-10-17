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

- **Verified on main:** `2026_10_04_150000_create_pharma_inventory_receipt_document_settings_table.php` creates the table and its core fields; `2026_10_04_151000_upgrade_pharma_inventory_receipt_document_settings_table.php` adds compatibility columns; `2026_10_04_152000_add_receipt_document_profiles.php` adds separate invoice/cost profile settings and the cost-view permission. The older PR's 24 September migration is superseded, not required by filename. Verify actual migration execution on the target environment separately.
- **DELL contract checkpoint passed:** 3 tests / 22 assertions at commit `b0e56c654` (reported by developer). Still run existing receipt-document integration tests, including permission-denied and posted-only cases.
- Review invoice and cost PDF outputs on the actual UI with a posted receipt; verify no cost fields leak into the invoice document.

## Checkpoint

Run `php artisan test Modules/Pharma/Tests/Unit/InventoryReceiptLegacyPrParityContractTest.php` on a development machine, not production. Do not merge this branch until TEST PASS and UI/review acceptance. PR #290 is a separate documentation follow-up.
