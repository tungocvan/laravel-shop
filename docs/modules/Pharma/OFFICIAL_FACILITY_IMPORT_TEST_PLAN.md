# Official Facility Import — Verification Plan

Run locally on `feat/pharma-official-facility-import`.

## Focused first

```bash
php artisan test \
  Modules/Pharma/Tests/Unit/OfficialFacilityNormalizerValidatorTest.php \
  Modules/Pharma/Tests/Unit/OfficialFacilityMatcherTest.php \
  Modules/Pharma/Tests/Unit/OfficialFacilityPartnerImporterTest.php \
  Modules/Pharma/Tests/Unit/OfficialFacilitySelectionContractTest.php
```

If focused tests fail, stop and report the full output before running broader regression.

## Focused style

```bash
./vendor/bin/pint --test \
  Modules/Partner/Models/Partner.php \
  Modules/Partner/Models/PartnerSourceReference.php \
  Modules/Partner/database/migrations/2026_09_05_200000_add_province_code_to_partners_table.php \
  Modules/Partner/database/migrations/2026_09_05_201000_create_partner_source_references_table.php \
  Modules/Pharma/Http/Controllers/OfficialFacilityImportController.php \
  Modules/Pharma/Models/OfficialFacilityImportBatch.php \
  Modules/Pharma/Models/OfficialFacilityImportRow.php \
  Modules/Pharma/Services/OfficialFacilityImport \
  Modules/Pharma/Tests/Unit/OfficialFacilityNormalizerValidatorTest.php \
  Modules/Pharma/Tests/Unit/OfficialFacilityMatcherTest.php \
  Modules/Pharma/Tests/Unit/OfficialFacilityPartnerImporterTest.php \
  Modules/Pharma/Tests/Unit/OfficialFacilitySelectionContractTest.php
```

## Directly impacted regression

Use the repository's existing Pharma and Partner regression locations. Do not run unrelated full-suite regression unless a focused failure proves a broader dependency.

## Routes and build

```bash
php artisan route:list --path=admin/pharma/official-facilities
npm run build
```

## Manual UI gate

Open `/admin/pharma/official-facilities/import` and verify:

- upload fields have visible borders;
- XLSX/CSV upload stages without creating Partner;
- KPI and classification preview render;
- pagination offers only `10/25/50/100`;
- header checkbox selects current-page importable rows only;
- saving one page preserves selections on other pages;
- `INVALID` cannot be selected;
- unresolved `LIKELY_MATCH`/`CONFLICT` cannot be selected/imported;
- conflict resolution supports link/create/skip;
- selected-only import creates/links only checked rows;
- manual Partner name/contact fields are preserved;
- Dashboard card navigates correctly;
- history shows batch outcomes.

Report the manual gate separately as `UI PASS` or with screenshots/error details.
