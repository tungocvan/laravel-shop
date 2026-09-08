# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Previous Drive/PDF/Partner reporting scope: **MERGED** through PR #171.
- Current follow-up branch: `feat/invoices-dashboard-backup-restore`.
- Base: `main` at merge commit `685dd1c898108ba76cb27c6599ec6f59889b505f`.
- Current branch state at closeout preparation: ahead of `main`, behind by `0` commits.
- Current scope: **Invoices Operations Dashboard + module-scoped Backup/Restore + Restore Readiness + Safety Rollback + Google Drive/PDF health**.
- Merge authorization: **NOT YET GIVEN**.

## Canonical Ownership Contract

Invoices remains the canonical owner of:

```text
invoices
invoice_files
invoice_backup_runs
```

Invoices owns invoice ingestion, local invoice persistence, invoice PDF metadata, GDT synchronization, invoice reporting and module-level recovery of its own persistence.

Partner master remains outside the restore boundary. Backup/restore does not create, update, delete or rollback Partner master data. PDF binaries are also excluded from module snapshots because the existing PDF ↔ Google Drive workflow is the binary protection path.

## Operations Dashboard

Canonical route:

```text
/admin/invoices/dashboard
admin.invoices.dashboard
```

The Dashboard is now an operations center rather than a duplicate navigation page. Its hierarchy is:

```text
Operational KPIs
Quick Actions
Operational Health
Backup & Recovery
Recent Activity
```

Current KPI wording:

```text
Tổng hóa đơn
Hóa đơn bán ra
Hóa đơn mua vào
PDF đã lưu
PDF chưa hoàn tất
```

PDF metrics are derived from `invoice_files.status`:

```text
available -> PDF đã lưu
error     -> PDF lỗi
missing   -> tổng hóa đơn - available - error
```

The production verification performed during this scope confirmed `invoice_files` exists and currently contains 156 rows, all with `status = available`; the Dashboard PDF aggregate was adjusted to use portable bounded count queries.

Operational Health intentionally avoids pretending to know a global queue state. It exposes actionable health for:

- GDT configuration/server session;
- Google Drive stored connection status;
- per-workspace queue guidance;
- PDF stored/missing/error counts.

Google Drive Dashboard status uses the existing `GoogleDriveConnectionService::status()` read path and does not call the external Google API on every Dashboard render. The Dashboard shows connected account/folder/last-check metadata when available, or the existing System Google Drive connect action when disconnected.

## Module Backup / Restore

Canonical route:

```text
/admin/invoices/backup-restore
admin.invoices.backup-restore
permission: invoices-configure
```

Module snapshots contain only:

```text
invoices
invoice_files metadata
manifest.json
payload SHA-256 checksums
```

They explicitly exclude:

```text
Partner master
Partner candidate state
PDF binary files
system/global database state
```

Snapshot storage root:

```text
storage/app/private/invoices/module-backups/*
```

(Resolved through Laravel's local disk; exact filesystem prefix remains disk-configuration dependent.)

Snapshot paths are guarded so deletion/restore cannot escape the Invoices backup root.

## Restore Readiness Gate

Restore requires an explicit readiness check before the normal UI enables the restore action.

States:

```text
READY
WARNING
BLOCKED
```

The readiness foundation verifies snapshot manifest/integrity/version support and current Invoices schema availability. Blocked snapshots cannot be restored. Impact Preview is shown before mutation and always reports the Partner boundary explicitly as `0 thay đổi`.

Safe Merge is the default restore behavior:

- insert missing snapshot invoices;
- preserve current matching invoices rather than overwriting newer current data;
- restore missing `invoice_files` metadata with invoice ID remapping;
- never delete current-only invoices;
- never mutate Partner master.

Before any restore mutation, the service must successfully create a `safety-before-restore` snapshot. Failure to create the Safety Backup blocks the restore.

## Safety Backup / Exact Rollback

`SAFETY-BEFORE-RESTORE` snapshots are protected from the normal delete action and expose a dedicated Rollback action.

Normal manual snapshots:

- may be deleted after confirmation;
- use guarded module-root deletion.

Safety snapshots:

- are not deletable through the normal UI;
- can be used for exact module rollback;
- trigger creation of another Safety Backup before rollback starts;
- restore exact `invoices + invoice_files` snapshot state;
- run post-rollback verification;
- keep Partner master unchanged.

The exact rollback behavior is covered by automated tests; production UI smoke intentionally did not require destructive rollback execution against live data.

## UI / UX Acceptance

Admin UI follows `.codex/standards/ADMIN_UI_STANDARD.md`.

User-reported manual UI acceptance in this branch:

```text
Invoices Operations Dashboard                  PASS
Backup & Restore workspace                     PASS
Snapshot MANUAL delete UI                      PASS
Safety snapshot protection / Rollback UI       PASS
Restore Readiness / Impact Preview              PASS
Google Drive Operational Health                 PASS
PDF KPI/Operational Health final display        PASS
```

A prior runtime issue where Livewire attempted to render the Safety Backup result array directly was fixed by normalizing restore output into scalar UI state before rendering.

## Automated Validation Recorded

User-reported validation during this branch includes:

```text
Restore readiness/snapshot/impact foundation   7 passed / 22 assertions
Restore focused gate                            9 passed / 35 assertions
Invoices regression                             44 passed / 264 assertions
Backup/Restore workspace focused                7 passed / 26 assertions
Invoices regression                             46 passed / 271 assertions
Snapshot lifecycle focused                      7 passed / 25 assertions
```

The exact Safety Rollback tests were added after the initial UI acceptance and were reported PASS together with the related focused gate.

Because the final PDF Dashboard aggregation adjustment happened after the last recorded full Invoices regression, one final `tests/Feature/Invoices*.php` regression is still required before PR creation/merge readiness is declared.

Full-project regression is **NOT APPLICABLE — module-scoped regression strategy**. The current change remains inside Invoices plus read-only use of the existing System Google Drive connection service/route contract.

## Compatibility / Non-Goals Preserved

This follow-up does not:

- rename canonical `/admin/invoices/*` routes;
- remove existing compatibility routes;
- change invoice permissions;
- expose protected invoice PDFs publicly;
- restore Partner master or Partner candidate state;
- treat module snapshots as full MySQL disaster-recovery backups;
- automatically call the Google Drive API whenever Dashboard loads;
- replace the existing PDF ↔ Google Drive synchronization workflow.

## Deferred Follow-up

Still deferred unless separately approved:

- persistence/retention metadata for module snapshots beyond the current filesystem manifest model;
- automatic scheduled upload of module snapshots to Google Drive;
- advanced Replace restore mode;
- cross-module Partner candidate recovery contract;
- database unique constraint for invoice business identity pending production proof;
- global queue registry/health aggregation;
- ClientPortal/PWA presentation.

## Final Closeout Gate

Before creating/reviewing the PR:

1. pull the latest branch state;
2. run focused Dashboard/Backup-Restore tests;
3. run `php artisan test tests/Feature/Invoices*.php`;
4. verify `git status -sb` is clean;
5. retain the recorded Dashboard + Backup/Restore UI PASS;
6. create/review the PR against `main`;
7. merge only after explicit user authorization under `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
