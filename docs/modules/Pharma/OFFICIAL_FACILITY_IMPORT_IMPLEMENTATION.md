# Official Facility Import — Implementation Summary

Branch: `feat/pharma-official-facility-import`

## Delivered

- Partner remains canonical organization master.
- Added nullable `partners.province_code` for source-independent canonical province data.
- Added Partner-owned `partner_source_references` with unique `(source, external_id)` identity.
- Added Pharma staging/audit tables for import batches and rows.
- Added XLSX/CSV parser, normalization, validation, deterministic matching, conflict resolution, selected-only Partner importer and reconciled batch summary.
- Added SHA-256 duplicate-file warning and duplicate-row skip outcome.
- Added `/admin/pharma/official-facilities/import` workspace with upload, preview, filtering, bounded pagination, page-scoped selection, conflict resolution, import and history.
- Added Dashboard entry and repo-style permissions.
- Synchronized `Modules/Pharma/config/module.php` and `docs/modules/Pharma/MODULE.md` with current ownership/dependency/persistence boundaries.
- Added focused tests and a local verification plan.

## Safety invariants

- Upload never writes Partner.
- No captcha bypass, runtime scraping or private API auto-sync.
- No source-specific BHXH/MOH IDs are added to `partners`.
- No province inference from facility name/address/code.
- `LIKELY_MATCH` and `CONFLICT` never auto-merge.
- Only explicitly checked rows import.
- Existing Partner name/phone/email/contact person are protected from automatic overwrite.
- Re-import of the same source identity is idempotent through `partner_source_references`.
