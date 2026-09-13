# System Collaboration Handoff

## Current Status — Dashboard Information Architecture Refactor

- Module: `System`
- Mode: Refactor Module / information architecture cleanup
- Delivery branch: `refactor/system-dashboard-information-architecture`
- Base branch: `main`
- Routes validated: `/admin/system/dashboard`, `/admin/system`, `/admin/system/settings/env`, `/admin/system/settings/login-theme`
- Status: **IMPLEMENTATION COMPLETE — PR/MERGE GATE READY**
- Focused tests: **PASS** on 2026-09-13
- Manual UI smoke: **PASS** on 2026-09-13

This phase simplifies System administration by separating navigation, runtime operations, infrastructure configuration, data recovery, and login experience into clearer ownership boundaries. It removes duplicated entry points and misleading labels without deleting legacy backend services or migrating stored data.

### Delivered Scope

#### `/admin/system/dashboard` — professional navigation hub

- Reorganized the dashboard into clearer operational groups instead of exposing overlapping workspace cards.
- Removed `System workspace` and `Cấu hình chung` as redundant intermediate entry points.
- Removed Theme / image / generic configuration concepts from the dashboard navigation.
- Dashboard now emphasizes:
  - Module operations.
  - Queue Manager.
  - Database Manager.
  - Backup / Restore.
  - Environment & Integrations.
  - Login Experience.
  - Developer tools when authorized.
- Existing dashboard health/warning boundaries remain read-only and permission-aware.

#### `/admin/system` — System Operations only

- Removed the `Giao diện Sidebar` tab from System runtime operations.
- Kept `/admin/system` focused on runtime/infrastructure concerns:
  - Queue Manager.
  - Email.
  - Database connection.
- Updated the workspace language to represent System Operations rather than a mixed environment/theme screen.
- No PM2/Docker process commands were introduced; Queue Manager remains Laravel/runtime focused.

#### Environment & Integrations

- Renamed the ambiguous `Hệ thống & Queue` / custom configuration area to `Runtime & Bridge`.
- Renamed `SEO & Social` to `Web & Analytics` because the component currently owns OAuth credentials, TinyMCE API configuration, and Google Analytics rather than canonical website SEO metadata.
- Preserved Database, Email, payment and Storage / Cloud configuration in the infrastructure configuration workspace.
- Added query-backed tab selection so dashboard deep links can open a specific Environment tab without creating new routes.

#### Login Experience consolidation

- Consolidated login appearance and redirect/navigation management into the `Giao diện & Đăng nhập` workspace.
- Existing login branding, logo/background upload, theme settings and login redirect/root fallback configuration now live under one operator-facing page.
- Existing backend redirect services, validation rules and permission checks remain unchanged.
- No redirect ownership or route-security boundary was weakened.

#### Configuration Hub cleanup

- `/admin/system/settings` now acts as a lightweight ownership hub instead of mounting the legacy mixed tab collection.
- It points operators to:
  - `Giao diện & Đăng nhập` for branding/login/navigation.
  - `Môi trường & Tích hợp` for infrastructure/runtime integrations.
- Legacy partials/services were intentionally not deleted in this phase to avoid accidental data loss or breaking unknown callers before a dedicated dead-code audit.

### Verification Completed

Operator reported the approved focused batch as PASS:

```text
php artisan test \
tests/Feature/System/SystemDashboardInformationArchitectureContractTest.php \
tests/Feature/System/AdminLoginRedirectSettingTest.php \
tests/Feature/System/ApplicationRootRedirectSettingTest.php
```

Manual UI smoke: **PASS** for:

```text
/admin/system/dashboard
/admin/system
/admin/system/settings/env
/admin/system/settings/login-theme
```

The UI smoke included desktop/tablet/mobile layout checks and confirmed the removed/renamed navigation concepts behave as intended.

### Boundary / Safety Decisions

| Concern | Decision |
|---|---|
| Dashboard role | Read-only overview + direct navigation |
| Runtime operations | `/admin/system` |
| Infrastructure configuration | Environment & Integrations |
| Login branding/navigation | Login Experience |
| Website SEO metadata | Not moved into System; remains a future Website ownership concern |
| OAuth / Analytics / TinyMCE | `Web & Analytics` under System environment integrations |
| Sidebar/Admin theme | Removed from System Operations |
| Legacy settings partials/services | Retained for now; no destructive cleanup in this phase |
| Permissions | Preserve existing authorization boundaries |
| Stored data | No migration or deletion |

### PR / Merge Gate

1. **COMPLETE** — dashboard information architecture simplified.
2. **COMPLETE** — redundant `System workspace` / `Cấu hình chung` navigation removed.
3. **COMPLETE** — Sidebar appearance removed from `/admin/system`.
4. **COMPLETE** — `/admin/system` narrowed to System Operations.
5. **COMPLETE** — custom runtime configuration renamed `Runtime & Bridge`.
6. **COMPLETE** — `SEO & Social` renamed `Web & Analytics` to match actual ownership.
7. **COMPLETE** — login appearance and redirect/navigation consolidated into one workspace.
8. **COMPLETE** — legacy components retained to avoid destructive cleanup.
9. **COMPLETE** — focused tests passed.
10. **COMPLETE** — manual UI smoke passed.
11. **READY** — create PR from `refactor/system-dashboard-information-architecture` to `main` and merge after normal repository PR checks.

A full-project regression remains outside the approved scope. Validation is limited to System and directly impacted login/navigation boundaries.

---

## Previous Closeout — Modules Runtime Separation / Queue Manager / Lifecycle Control

- Module: `System`
- Mode: Refactor Module / runtime operations hardening
- Delivery branch: `refactor/system-modules-runtime-separation`
- Base branch: `main`
- Routes validated: `/admin/system/modules` and `/admin/system`
- Status: **IMPLEMENTATION COMPLETE — PR/MERGE GATE READY**
- Focused tests: **PASS** on 2026-09-13
- Manual UI smoke: **PASS** on 2026-09-13

This phase separates Module lifecycle management from runtime queue/realtime operations, hardens Module enable/disable preflight and error reporting, and aligns queue ownership with enabled Module state without coupling the web UI to PM2 or Docker process commands.

### Delivered Scope

#### `/admin/system/modules` — lifecycle only

- Removed Realtime / Socket.IO controls from the Modules workspace.
- Removed Module GET Routes management from the Modules workspace.
- Kept the page focused on Module enable/disable lifecycle only.
- Added `Module Lifecycle Control` modal before execution.
- Preflight now reports dependency state, database/migration readiness, permission readiness, and queue impact.
- Missing-but-safe migrations are shown as planned migration work instead of a generic failure.
- Migration/schema ledger mismatch is treated as blocking and requires recovery before enable.
- Permission manifest/discovery problems are surfaced as a blocking preflight condition when appropriate.
- Required Shell Modules remain non-disableable.
- Enabled dependent Modules block disabling their dependency.
- Runtime state is written only after dependency validation, migration and permission sync complete successfully.
- Structured lifecycle failures expose safe stage-specific guidance without rendering raw internal exception messages.

#### Queue ownership integration

- Module-owned queues are read from each Module manifest.
- Queue `default` remains System-owned and is explicitly excluded from Module lifecycle queue control.
- Disabling a Module hides its owned queues from Queue Manager even when historical `jobs` / `failed_jobs` rows still exist.
- Pending and failed jobs are never deleted automatically when a Module is disabled.
- Enabling/disabling a Module with owned queues sends Laravel's `queue:restart` signal so workers can recycle safely.
- Browser lifecycle actions do not call PM2, Docker, Supervisor, `exec`, `shell_exec`, or sudo commands.

#### `/admin/system` — Queue Manager

- Realtime / Socket.IO controls moved into the Queue Manager tab.
- Queue Manager is process-manager agnostic and works with Laravel queue state rather than PM2-specific controls.
- Added global pending / processing / failed-history metrics.
- Added per-queue pending age and stale backlog warning.
- Added worker probe health (`unknown`, waiting, confirmed, unresponsive).
- Added failed-job history viewer with retry selected, delete selected, and clear history.
- Added Laravel-native `queue:restart` action.
- Adaptive polling now uses 5 seconds while queue work/probe activity is present and 30 seconds while idle.
- Manual refresh remains available.

#### Local queue runner

- `run-queue.sh` now discovers queues from enabled Module manifests through `ModuleRegistry`.
- The general local PM2 worker is recreated when needed so queue arguments are actually updated.
- Request-specific queues remain on the dedicated Request worker.
- Disabled Module queues drop out of the dynamically generated general worker queue list when the script is rerun.
- This script is a local runtime helper only; the web UI remains PM2-agnostic.

### Verification Completed

Operator reported the following focused tests as PASS:

```text
php artisan test tests/Feature/System/SystemModulesControlTest.php \
  tests/Feature/System/SystemModulesRuntimeSeparationContractTest.php \
  tests/Feature/System/SystemQueueRunnerContractTest.php
```

Manual UI smoke: **PASS**.

The UI smoke included Module enable/disable behavior and Queue Manager verification, including disabling `Admission` and confirming Module-owned queue behavior remained aligned with runtime state.

### Boundary / Safety Decisions

| Concern | Decision |
|---|---|
| Modules page scope | Lifecycle only |
| Queue / Realtime ownership | Queue Manager under `/admin/system` |
| Module enable order | Dependency → migration → permission sync → runtime state |
| Failed migration/permission | Do not persist enabled runtime state |
| Browser error details | Structured safe stage + guidance; no raw exception text |
| Module queue ownership | Manifest declarations + runtime Module enabled state |
| `default` queue | System-owned; never disabled by Module lifecycle |
| Pending / failed jobs on disable | Preserve; never auto-delete |
| Worker recycle | Laravel `queue:restart` signal only |
| PM2 / Docker process management | Outside browser UI |
| Local PM2 helper | `run-queue.sh` dynamically follows enabled Module queues |
| Queue polling | 5s when active; 30s when idle |

### PR / Merge Gate

1. **COMPLETE** — `/admin/system/modules` reduced to Module lifecycle responsibilities.
2. **COMPLETE** — Queue/Reatime operations moved to Queue Manager.
3. **COMPLETE** — lifecycle preflight modal added.
4. **COMPLETE** — migration and permission failure stages provide safe remediation guidance.
5. **COMPLETE** — runtime state is not persisted after migration/permission failure.
6. **COMPLETE** — Module-owned queues follow Module enabled state.
7. **COMPLETE** — `default` queue remains protected and System-owned.
8. **COMPLETE** — pending/failed history is preserved on Module disable.
9. **COMPLETE** — Queue Manager stale/probe/failed-history operations implemented.
10. **COMPLETE** — adaptive 5s/30s polling implemented.
11. **COMPLETE** — local `run-queue.sh` dynamically discovers enabled Module queues.
12. **COMPLETE** — focused tests passed.
13. **COMPLETE** — manual UI smoke passed.
14. **READY** — create PR from `refactor/system-modules-runtime-separation` to `main` and merge after normal repository PR checks.

A full-project regression remains outside the approved scope. Validation is limited to System and directly impacted queue/module lifecycle behavior.

---

## Previous Closeout — Module Snapshot Dependency Metadata

- Module: `System`
- Mode: Capability hardening / recovery metadata
- Delivery branch: `feat/system-module-snapshot-dependencies-v2`
- Base checkpoint: `b4f1dfb6901420d3dd0c529c55504f914cd63cb6`
- Route validated: `/admin/system/database`
- Status: **IMPLEMENTATION COMPLETE — PR/MERGE GATE READY**
- Focused tests: **PASS** on 2026-09-12
- System regression: **PASS** on 2026-09-12
- Manual UI smoke: **PASS** on 2026-09-12

This phase hardens per-Module snapshot recovery by making declared Module dependencies visible to operators and recording them in snapshot metadata without changing ownership or restore scope.

### Delivered Scope

- Added `ModuleDependencyService` backed by the existing `App\Modules\ModuleRegistry`; no second dependency source of truth was introduced.
- Dependency metadata is advisory recovery context, not an inferred database foreign-key graph.
- New snapshots record a normalized `dependencies` array in `manifest.json`.
- Snapshot validation remains backward compatible: packages created before dependency metadata existed are accepted with `dependencies = []`.
- `TableList` refreshes dependency state when the selected Module changes and clears it when Module snapshot state resets.
- Added `DEPENDENCY WARNING` UI for Modules that declare dependencies.
- Warning text explicitly states that a Module Snapshot contains only tables owned by the selected Module and does not automatically backup or restore dependency Modules.
- Restore behavior, ownership boundaries, safety snapshot, schema compatibility checks, local/Drive synchronization and deletion semantics remain unchanged.
- No automatic `Backup Module + Dependencies`, dependency restore, cross-Module table inclusion or new permission was introduced in this phase.

### Verification Completed

Operator reported:

```text
Focused tests: PASS
System regression: PASS
UI smoke: PASS
```

UI verification confirmed the dependency warning appears on `/admin/system/database` for a Module with declared dependencies and correctly explains the recovery boundary.

### Boundary / Safety Decisions

| Concern | Decision |
|---|---|
| Dependency source | Existing `ModuleRegistry` metadata |
| Database FK discovery | Not part of this phase; declared dependencies are advisory metadata |
| Snapshot ownership | Selected Module-owned tables only |
| Dependency tables | Never silently included |
| Dependency backup/restore | Never automatic |
| Existing snapshots | Remain valid when `dependencies` is absent |
| Future recovery set | Deferred; may explicitly package Module + dependencies in a separate phase |

### PR / Merge Gate

1. **COMPLETE** — dependency resolver uses `ModuleRegistry`.
2. **COMPLETE** — `dependencies` recorded in new snapshot manifests.
3. **COMPLETE** — backward compatibility retained for legacy snapshots.
4. **COMPLETE** — Livewire dependency state added without exposing raw exception messages.
5. **COMPLETE** — `DEPENDENCY WARNING` UI implemented.
6. **COMPLETE** — no automatic dependency backup/restore introduced.
7. **COMPLETE** — focused tests passed.
8. **COMPLETE** — System regression passed.
9. **COMPLETE** — manual UI smoke passed.
10. **READY** — create PR from `feat/system-module-snapshot-dependencies-v2` to `main` and merge after normal repository PR checks.

A full-project regression remains outside the approved scope. Validation is limited to System and directly impacted recovery workflows.

---

## Previous Closeout — Database Backup / Module Snapshot Recovery

- Module: `System`
- Mode: Capability / maintenance enhancement
- Delivery branch: `feat/system-database-backup-bulk-manage`
- Base checkpoint: `7f61465585601c5c0d4bdc61a5662b9c4a8b5d99`
- Routes validated: `/admin/system/database` and `/admin/system/database/backup-restore`
- Status: **COMPLETE — MERGED TO MAIN**
- Manual UI smoke: **PASS** on 2026-09-12

This phase upgraded System database recovery in two related areas: operational management of local/Google Drive SQL backups, and production-safe per-Module snapshot backup/restore so operators do not need to restore the full database for Module-scoped recovery.

### Delivered Scope

#### Backup / Restore catalog management

- Added checkbox selection and bulk delete for local SQL backups and Google Drive SQL backups.
- Added safe rename for local and Google Drive backups while preserving `.sql` naming rules.
- Local rename/delete remains constrained to trusted backup roots and opaque references; arbitrary filesystem paths are not accepted from Livewire state.
- Google Drive rename updates metadata in place; it does not download/re-upload SQL content.
- Local and Drive destructive operations remain independent; deleting one side never implicitly deletes the other.
- Existing Download, Upload Drive, email and Restore actions remain available.

#### Generic Module Snapshot workflow

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

#### Local <-> Google Drive Module Snapshot synchronization

- Local namespace: `storage/app/private/backups/modules/<Module>/YYYY/MM/`.
- Google Drive namespace: `Laravel-Backup/database/modules/<Module>/YYYY/MM/`.
- Supports `Backup Module`, `Backup & Upload Drive`, `Upload Drive`, and `Tải về Local`.
- Restore is intentionally local-only: a `DRIVE ONLY` snapshot must first download to local and pass manifest/checksum/schema verification before Restore Module is offered.
- Added independent `Xóa Local` and `Xóa Drive` actions.
- UI exposes `LOCAL ONLY`, `DRIVE ONLY`, `LOCAL + DRIVE`, `COMPATIBLE`, `BLOCKED`, and `SAFETY` states.
- No filesystem-style destructive auto-sync is implemented: local deletion never propagates to Drive and Drive deletion never propagates to local.

### Verification Completed

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

#### Restore proof — Invoices

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

#### Restore proof — Inventory

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

### Boundary / Safety Decisions

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
