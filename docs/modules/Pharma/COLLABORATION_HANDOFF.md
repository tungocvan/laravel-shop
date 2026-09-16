# Pharma Collaboration Handoff

## Current checkpoint — Production medicine migration-order hotfix

- Module: `Pharma`
- Implementation branch: `fix/pharma-medicine-import-migration-order`
- Parent checkpoint: Price List v2 + Excel Designer v3.2 accepted and merged to `main`
- Status: **HOTFIX IMPLEMENTED — TARGETED TEST/PINT VERIFICATION REQUIRED BEFORE MERGE**
- Date: 2026-09-16
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- UI standard: `.codex/standards/ADMIN_UI_STANDARD.md`
- Reusable Excel standard: `.codex/standards/EXCEL_EXPORT_CONFIGURATION_STANDARD.md`

## Production incident and root cause

Production deployment failed while running `2026_09_14_020000_create_medicine_import_staging_tables.php`. The staging migration declares `matched_variant_id` as a foreign key to `pharma_medicine_variants`, but the referenced table is created later by `2026_09_14_100000_create_canonical_medicine_catalog_tables.php`.

MariaDB therefore rejected the foreign key before the canonical catalog migration could run. The failed DDL left `pharma_medicine_import_batches` and `pharma_medicine_import_rows` as partial tables while the migration itself was not recorded in the `migrations` table.

Production verification confirmed both partial staging tables contained zero rows. They were explicitly dropped before this source hotfix; no staging data was lost.

## Hotfix contract

The import staging migration is renamed to:

`2026_09_14_120000_create_medicine_import_staging_tables.php`

The intended order is now:

1. `2026_09_14_100000_create_canonical_medicine_catalog_tables.php`
2. `2026_09_14_110000_create_medicine_profiles_table.php`
3. `2026_09_14_120000_create_medicine_import_staging_tables.php`
4. `2026_09_14_130000_add_therapeutic_group_to_medicines_table.php`
5. `2026_09_14_140000_create_price_lists_v2_tables.php`

The `matched_variant_id -> pharma_medicine_variants.id` foreign key remains intact. The fix corrects migration dependency ordering rather than weakening referential integrity.

`MedicineMigrationOrderContractTest` guards the ordering and verifies that the canonical migration creates `pharma_medicine_variants` while the staging migration retains the variant foreign key.

## Required verification before merge/deploy

Run only the focused Pharma checks for this hotfix:

```bash
php artisan test Modules/Pharma/Tests/Unit/MedicineMigrationOrderContractTest.php
php artisan test Modules/Pharma/Tests/Unit/MedicineCatalogContractTest.php Modules/Pharma/Tests/Unit/MedicineCanonicalIdentityTest.php Modules/Pharma/Tests/Unit/MedicineCatalogImportStagerTest.php
./vendor/bin/pint Modules/Pharma/Tests/Unit/MedicineMigrationOrderContractTest.php Modules/Pharma/database/migrations/2026_09_14_120000_create_medicine_import_staging_tables.php
```

Do not rerun production migration until this branch is merged and production source is synchronized. Do not restore the two partial staging tables; the corrected migration will recreate them after the canonical catalog exists.

## Production deploy note

Production currently has untracked `compose.queue.yaml`, `compose.scheduler.yaml`, and `compose.socket.yaml`. Preserve them until their ownership/purpose is verified. `deploy.sh --pull` requires a clean working tree, so do not delete or overwrite these files merely to satisfy the pull guard.

The production queue workers were also observed restarting while the application container remained healthy. Treat that as a separate runtime/bootstrap investigation after the migration-order hotfix is verified; do not mix it into this schema fix.

## Accepted Price List v2 + Excel Designer v3.2 baseline

The canonical product identity remains `Medicine -> MedicineVariant / SKU -> MedicinePackage`. Price List resolver precedence remains ACTIVE Customer price, then ACTIVE Global price, otherwise `NO_PRICE` / null. Bid prices remain reference evidence only and never silently mutate commercial price fields.

Price List v2 is database-backed through `pharma_price_lists`, `pharma_price_list_items`, reusable purposes and bid-evidence persistence. Customer lists use Partner as canonical customer master and may initialize from an ACTIVE Global list. Persisted Draft items remain authoritative after save.

The accepted Excel Designer v3.2 supports persistent per-admin profiles, server/local JSON, branding, logo/signature, signing identity/date, selected columns and ordering, per-column Inspector state, typography, page setup, selected/all export scope, and responsive administrative UX. Signature recovery remains identity-sensitive by normalized `signatory_title + signatory_name` and must never fall back to another person's signature.

The reusable implementation standard remains `.codex/standards/EXCEL_EXPORT_CONFIGURATION_STANDARD.md`; other modules may use Pharma v3.2 as a reference without introducing a hard dependency on Pharma.

## Previous acceptance — 2026-09-16

Before this production migration incident, the Price List v2 + Excel Designer v3.2 implementation had operator verification:

```text
Tests: 14 passed (126 assertions)
Pint: PASS — 6 files
UI: PASS
```

Frontend note: an earlier `npm run build` attempt failed because Vite/Rollup could not resolve an import. This hotfix does not claim frontend build PASS and does not change frontend assets.

## Local stash safety

Historical stashes remain on operator machines from earlier synchronization checkpoints. Do not bulk-pop or bulk-drop them. Invoices stashes remain unrelated and untouched. Inspect a stash individually only when explicitly needed.
