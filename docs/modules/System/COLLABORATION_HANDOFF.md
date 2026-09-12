# System Collaboration Handoff

## Current Status — Database Backup / Module Snapshot Recovery

- Module: `System`
- Mode: Capability / maintenance enhancement
- Delivery branch: `feat/system-database-backup-bulk-manage`
- Base checkpoint: `7f61465585601c5c0d4bdc61a5662b9c4a8b5d99`
- Routes validated: `/admin/system/database` and `/admin/system/database/backup-restore`
- Status: **IMPLEMENTATION COMPLETE — PR/MERGE GATE READY**
- Manual UI smoke: **PASS** on 2026-09-12

This phase upgrades System database recovery in two related areas: operational management of local/Google Drive SQL backups, and production-safe per-Module snapshot backup/restore so operators do not need to restore the full database for Module-scoped recovery.

## Delivered Scope

### Backup / Restore catalog management

- Added checkbox selection and bulk delete for local SQL backups and Google Drive SQL backups.
- Added safe rename for local and Google Drive backups while preserving `.sql` naming rules.
- Local rename/delete remains constrained to trusted backup roots and opaque references; arbitrary filesystem paths are not accepted from Livewire state.
- Google Drive rename updates metadata in place; it does not download/re-upload SQL content.
- Local and Drive destructive operations remain independent; deleting one side never implicitly deletes the other.
- Existing Download, Upload Drive, email and Restore actions remain available.

### Generic Module Snapshot workflow

- Added Module Snapshot workspace to `/admin/system/database` when a concrete Module filter is selected.
- Module ownership is resolved server-side from the existing System table-to-module mapping; browser checkbox state is not trusted as the snapshot ownership source.
- Snapshot packages contain `manifest.json`, `module.sql` and `checksums.json`.
- Manifest records Module scope, table set, row counts and schema fingerprint; package verification includes SHA-256 checksum and ownership/table validation.
- Schema fingerprint normalization ignores changing `AUTO_INCREMENT` counters so normal data growth does not create false incompatibility.
- Snapshot compatibility is checked before restore; incompatible packages are blocked.
- Module restore creates a local `SAFETY` snapshot before changing data.
- Module restore uses a Module-scoped lock and automatic rollback from the safety snapshot when import fails.
- Module SQL dump uses transactional dump options appropriate to the current MySQL workflow (`--single-transaction`, `--skip-lock-tables`).
- Snapshot restore changes only tables owned by the selected Module; external Module dependencies are not pulled into the package.

### Local <-> Google Drive Module Snapshot synchronization

- Local namespace: `storage/app/private/backups/modules/<Module>/YYYY/MM/`.
- Google Drive namespace: `Laravel-Backup/database/modules/<Module>/YYYY/MM/`.
- Supports `Backup Module`, `Backup & Upload Drive`, `Upload Drive`, and `Tải về Local`.
- Restore is intentionally local-only: a `DRIVE ONLY` snapshot must first download to local and pass manifest/checksum/schema verification before Restore Module is offered.
- Added independent `Xóa Local` and `Xóa Drive` actions.
- UI exposes `LOCAL ONLY`, `DRIVE ONLY`, `LOCAL + DRIVE`, `COMPATIBLE`, `BLOCKED`, and `SAFETY` states.
- No filesystem-style destructive auto-sync is implemented: local deletion never propagates to Drive and Drive deletion never propagates to local.

## Verification Completed

Focused Module Snapshot / backup-management contract tests: **PASS**.

Final System Feature regression reported by operator:

```text
Tests:      201 passed (1163 assertions)
Duration:   14.89s
```

Manual UI smoke: **PASS**.

Local <-> Google Drive synchronization smoke: **PASS**:

1. `LOCAL + DRIVE` established.
2. Delete Local left the Drive copy intact and produced `DRIVE ONLY`.
3. Download to Local restored `LOCAL + DRIVE`.
4. Delete Drive left the local copy intact and produced `LOCAL ONLY`.
5. Upload Drive restored `LOCAL + DRIVE`.

### Restore proof — Invoices

Generic Module Snapshot restore was exercised against `Invoices` (7 owned tables).

Controlled marker test:

```text
BEFORE:  invoice_expense_categories.id=1 name="Dịch vụ"
CHANGED: name="TEST_RESTORE_INVOICES_20260912"
RESTORED: name="Dịch vụ"
```

`updated_at` also returned to the snapshot value. Post-restore table counts returned to the captured baseline:

```text
invoice_backup_runs              0
invoice_expense_categories       6
invoice_files                    157
invoice_inventory_snapshots      57
invoice_inventory_staging_lines  75
invoice_source_records           1482
invoices                         3014
```

A new `safety_invoices_*.zip` snapshot was created and remained available as `SAFETY + COMPATIBLE` after restore.

### Restore proof — Inventory

The same generic engine was exercised against `Inventory` (16 owned tables), demonstrating that the implementation is not hard-coded to Invoices.

Controlled marker test:

```text
BEFORE:  inventory_warehouses.id=1 name="KHO C1"
CHANGED: name="TEST_RESTORE_INVENTORY_20260912"
RESTORED: name="KHO C1"
```

`updated_at` returned to the snapshot value. Post-restore Inventory baseline:

```text
inventory_balances              2
inventory_invoice_inbox         8
inventory_invoice_inbox_lines   8
inventory_issue_lines           0
inventory_issues                0
inventory_item_aliases          4
inventory_items                 4
inventory_lots                  2
inventory_movements             2
inventory_receipt_lines         4
inventory_receipts              4
inventory_stocktake_lines       0
inventory_stocktakes            0
inventory_transfer_lines        0
inventory_transfers             0
inventory_warehouses            1
```

`inventory_receipt_lines` correctly retained `max_id=6` with `count=4`, confirming restoration of the captured database state rather than artificial sequence normalization.

## Boundary / Safety Decisions

| Concern | Decision |
|---|---|
| Full database disaster recovery | Existing Full Backup / Restore remains available |
| Module recovery | Use Module Snapshot; do not require Full Database Restore |
| Ad-hoc table movement | Existing Table Export / Import remains separate from Module Snapshot |
| Module table ownership | Resolve server-side from System mapping |
| Cross-Module dependencies | Reference only; never silently overwrite another Module |
| Drive restore | Download to local, verify, then restore; no direct Drive-to-database restore |
| Restore protection | Compatibility verification + safety snapshot + Module lock + automatic rollback |
| Local/Drive deletion | Explicit and independent; never destructive auto-sync |
| Client-provided paths | Prohibited; use trusted roots and opaque references |

## Final Diff Review

Compared with `main` at the final pre-closeout review, delivery branch was ahead 16 commits and behind 0 before this handoff commit. The implementation diff was limited to System database backup/snapshot services, Livewire/UI and focused System tests; no migration or route change was required.

Implementation files reviewed:

- `Modules/System/Livewire/Database/BackupManager.php`
- `Modules/System/Livewire/Database/TableList.php`
- `Modules/System/Services/Cloud/GoogleDriveBackupBrowserService.php`
- `Modules/System/Services/Cloud/GoogleDriveModuleSnapshotService.php`
- `Modules/System/Services/Database/DatabaseBackupCatalogService.php`
- `Modules/System/Services/Database/ModuleSnapshotDeletionService.php`
- `Modules/System/Services/Database/ModuleSnapshotService.php`
- `Modules/System/resources/views/livewire/database/backup-manager.blade.php`
- `Modules/System/resources/views/livewire/database/table-list.blade.php`
- `tests/Feature/System/DatabaseBackupBulkManageTest.php`
- `tests/Feature/System/ModuleSnapshotContractTest.php`

No unrelated Module implementation was included in the reviewed diff.

## PR / Merge Gate

1. **COMPLETE** — SQL backup checkbox/bulk delete implemented for local and Drive.
2. **COMPLETE** — safe local/Drive rename implemented.
3. **COMPLETE** — generic Module Snapshot package and ownership boundary implemented.
4. **COMPLETE** — manifest/checksum/schema compatibility verification implemented.
5. **COMPLETE** — safety snapshot, Module lock and automatic rollback implemented.
6. **COMPLETE** — explicit Local <-> Drive Module Snapshot synchronization implemented.
7. **COMPLETE** — independent Local/Drive snapshot deletion implemented.
8. **COMPLETE** — focused tests passed.
9. **COMPLETE** — final System regression passed: 201 tests, 1163 assertions.
10. **COMPLETE** — Local <-> Drive functional synchronization smoke passed.
11. **COMPLETE** — Invoices Module restore proof passed.
12. **COMPLETE** — Inventory Module restore proof passed.
13. **COMPLETE** — manual UI smoke passed.
14. **COMPLETE** — final implementation diff reviewed; branch was based directly on current merge base with no unrelated implementation changes.
15. **READY** — create PR from `feat/system-database-backup-bulk-manage` to `main` and merge after normal repository PR checks.

A full-project regression remains outside the approved scope. Per project policy, validation was limited to System and directly impacted recovery workflows.

---

## Previous Closeout — Architecture Boundaries Refactor

- Delivery branch: `refactor/system-architecture-boundaries`
- Closeout branch: `docs/system-architecture-boundaries-closeout`
- Architecture contract: `docs/modules/System/MODULE.md`
- Pull request: #141 — **MERGED**
- Main merge checkpoint: `124f640f1bd5d6efba25da4fa1160e4728f7cc77`
- Status: **COMPLETE — MERGED TO MAIN**
- UI smoke: **PASS** on 2026-09-02

That phase established the System ownership contract, removed Admin menu ownership from System settings, hardened Admin post-login landing behavior, and removed runtime dependence on Website for Admin/root fallback and ClientPortal PWA manifest delivery.

### Previous Quarantine / Deferred Debt

- `LegacySettingsAuditService` / `LegacySettingsMigrationService` remain quarantined pending historical-data proof.
- Overlapping settings/env service naming remains deferred until caller imports are completely mapped.
- Dependency-topological module boot ordering remains deferred to root module runtime work.
- Distributed locking for concurrent module transitions remains deferred.
- Physical migration ownership/provenance of the `settings` table remains to be proven before schema cleanup.

---

## Previous Closeout — Module Catalog & Runtime Boundaries

- Delivery branch: `refactor/system-module-catalog-runtime-boundaries`
- Closeout branch: `docs/system-module-catalog-runtime-boundaries-closeout`
- Main merge checkpoint: `f2dd9ca6565d12b2931b9aa0a844742e0fec23b4`
- Pull request: #82 — merged

That phase separated filesystem catalog discovery, graph validation and current-request registry projection while preserving the existing `config('modules.registry')` consumer contract. It also retired browser-driven module source archival and kept runtime module state file-backed and atomic.

### Corrective Closeout — Account Migration Recovery

After that phase, enabling `Account` exposed an existing schema/migration-ledger mismatch. Recovery was handled through ownership-verified `module:migration-recover`, restoring exactly five verified migration ledger records without replaying migrations. The Account runtime toggle and permission synchronization subsequently passed. Manual ledger insertion remains prohibited.
