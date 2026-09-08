# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Previous Drive/PDF/Partner reporting scope: **MERGED** through PR #171.
- Current follow-up branch: `feat/invoices-dashboard-backup-restore`.
- Base: `main` at merge commit `685dd1c898108ba76cb27c6599ec6f59889b505f`.
- Current scope: **Invoices Operations Dashboard + module-scoped Backup/Restore + Restore Readiness + Safety Rollback + Google Drive module snapshot protection/recovery**.
- User-reported CLI regression and UI disaster-recovery smoke: **PASS**.
- Merge authorization: **NOT YET GIVEN**.

## Canonical Ownership Contract

Invoices remains the canonical owner of:

```text
invoices
invoice_files
invoice_backup_runs
```

Partner master remains outside the restore boundary. Backup/restore does not create, update, delete or rollback Partner master data. PDF binaries are excluded from module snapshots because the existing PDF ↔ Google Drive workflow is the binary protection path.

## Operations Dashboard

Canonical route:

```text
/admin/invoices/dashboard
admin.invoices.dashboard
```

The Dashboard is an operations center rather than a duplicate navigation page:

```text
Operational KPIs
Quick Actions
Operational Health
Backup & Recovery
Recent Activity
```

Primary KPI wording:

```text
Tổng hóa đơn
Hóa đơn bán ra
Hóa đơn mua vào
PDF đã lưu
PDF chưa hoàn tất
```

Google Drive Dashboard status uses the existing stored `GoogleDriveConnectionService::status()` read path and does not call the external API on every render.

## Module Backup / Restore

Canonical route:

```text
/admin/invoices/backup-restore
admin.invoices.backup-restore
permission: invoices-configure
```

Module snapshots contain only:

```text
manifest.json
database/invoices.json
database/invoice_files.json
```

Each payload is covered by the snapshot manifest checksum contract. Partner master, Partner candidate state, PDF binaries and global/system database state are excluded.

Local logical root:

```text
invoices/module-backups/*
```

Google Drive protection root:

```text
Laravel-Backup/Invoices/Module-Backups
```

Manual module snapshots are transported as one ZIP artifact with SHA-256 stored in Google Drive `appProperties`. New uploads use canonical names:

```text
Invoices-Module-<snapshot>.zip
```

A compatibility reader accepts the earlier temporary-prefix artifact form:

```text
.transport-Invoices-Module-<snapshot>.zip
```

The compatibility prefix is normalized in the UI and on download; new uploads no longer use it.

Deleting a MANUAL Local snapshot does not delete the Google Drive artifact. Remote delete is intentionally not exposed.

## Production / New-Server Recovery Flow

Approved recovery sequence:

```text
Deploy application source
→ configure environment/database
→ run migrations
→ connect Google Drive
→ open /admin/invoices/backup-restore
→ Tìm backup trên Drive
→ Tải về & kiểm tra
→ verify archive SHA-256
→ validate ZIP contract + snapshot manifest
→ extract into guarded Local snapshot root
→ Restore Readiness
→ Impact Preview
→ mandatory Safety Backup
→ Safe Merge Restore
→ Post-Restore Verification
```

PDF binaries are restored separately through the existing PDF ↔ Drive workflow.

## Restore Readiness Gate

States:

```text
READY
WARNING
BLOCKED
```

Safe Merge is the default restore behavior:

- insert missing snapshot invoices;
- preserve current matching invoices;
- restore missing `invoice_files` metadata with safe invoice mapping;
- never delete current-only invoices;
- never mutate Partner master.

A successful Safety Backup is mandatory before any restore mutation.

## Safety Backup / Exact Rollback

`SAFETY-BEFORE-RESTORE` snapshots are protected from normal deletion and expose a dedicated Rollback action.

Exact rollback:

- creates another Safety Backup first;
- restores exact Invoices-owned `invoices + invoice_files` state from the selected safety snapshot;
- runs verification;
- leaves Partner master unchanged.

Production UI smoke intentionally did not execute destructive rollback or unnecessary restore against already-matching live data.

## Google Drive Disaster-Recovery Acceptance

User-validated real Google Drive flow on 2026-09-08:

```text
Google Drive connection                        PASS
Backup artifact created on Drive               PASS
Local MANUAL snapshot deleted                  PASS
Drive artifact discovery                       PASS
Legacy .transport-* artifact compatibility     PASS
Drive SHA-256 metadata visible                 PASS
Tải về & kiểm tra                              PASS
Snapshot restored back to Local                PASS
Restore Readiness                              READY
Impact: thêm mới                               0
Impact: đã tồn tại                             2,471
Impact: có khác biệt                           0
Impact: sẽ xóa                                 0
Partner master                                 0 thay đổi
```

This proves the key disaster-recovery scenario: the Local module snapshot can be removed while the Google Drive artifact remains available, discoverable, downloadable, integrity-checked and accepted by Restore Readiness on the server.

## Automated Validation Recorded

Latest user-reported final gates:

```text
Focused Dashboard/Backup-Restore gate          20 passed / 142 assertions
Invoices module regression                     55 passed / 303 assertions
```

Earlier Drive transport gate:

```text
InvoicesGoogleDriveModuleBackupTest             3 passed / 14 assertions
```

Earlier combined backup/restore gate:

```text
11 passed / 52 assertions
```

Full-project regression is **NOT APPLICABLE — module-scoped regression strategy**. Scope remains inside Invoices plus use of the existing System Google Drive connection service.

## UI / UX Acceptance

Admin UI follows `.codex/standards/ADMIN_UI_STANDARD.md`.

User-reported manual acceptance:

```text
Invoices Operations Dashboard                  PASS
Backup & Restore workspace                     PASS
Snapshot MANUAL delete UI                      PASS
Safety snapshot protection / Rollback UI       PASS
Restore Readiness / Impact Preview             PASS
Google Drive Operational Health                PASS
PDF KPI/Operational Health final display       PASS
Google Drive module backup discovery            PASS
Google Drive download + readiness               PASS
```

## Compatibility / Non-Goals Preserved

This follow-up does not:

- rename canonical `/admin/invoices/*` routes;
- change invoice permissions;
- expose protected invoice PDFs publicly;
- restore Partner master or Partner candidate state;
- treat module snapshots as full MySQL disaster-recovery backups;
- automatically call Google Drive on Dashboard render;
- replace the existing PDF ↔ Google Drive synchronization workflow;
- delete Google Drive module backups from the Invoices workspace.

## Deferred Follow-up

Still deferred unless separately approved:

- scheduled automatic module snapshot creation/upload and retention;
- advanced Replace restore mode;
- cross-module Partner candidate recovery contract;
- database unique constraint for invoice business identity pending production proof;
- global queue registry/health aggregation;
- ClientPortal/PWA presentation.

## Final Closeout Gate

Before merge:

1. verify branch remains clean/aligned with its remote;
2. compare against current `main` and resolve any base drift if present;
3. create/review the PR against `main`;
4. preserve the recorded CLI + UI PASS evidence;
5. merge only after explicit user authorization under `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
