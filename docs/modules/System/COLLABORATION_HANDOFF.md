# System Collaboration Handoff

## Current Status

- Module: `System`
- Feature: P0 Google Drive and database-backup boundary hardening
- Delivery branch: `fix/system-drive-backup-boundaries`
- Base/source checkpoint: `main@d1c080e6a3e90478bde3830c9760686307af1835`
- Verified feature checkpoint: `11da525cdba355f4ac0a1abf41f0a2563773a159`
- Implementation status: **VERIFIED — READY FOR PR**
- Pull request: not opened
- Merge status: not merged

This PR is the first deferred refactor phase after the read-only System Dashboard. The Dashboard feature was completed in PR #76 and remains outside this branch. The operator confirmed all approved automated gates and desktop/mobile UI acceptance as PASS on 2026-08-29.

## Approved Scope

This phase corrects only the P0 boundary around System-owned database backups and Google Drive:

- replace browser-supplied local filenames/paths and remote Drive IDs with opaque server-issued references;
- validate every local/remote action against the bounded server-owned backup catalog;
- require backup and destructive-retention permissions for automation management;
- prevent raw exception, external payload, path and persisted error text from reaching UI/status/log output;
- bound Google Drive traversal and remove three-second remote polling;
- stream Drive uploads instead of loading the full SQL file into PHP memory;
- retire the legacy public Drive URL import;
- require remote download to local storage and a separate explicit restore confirmation;
- create database dumps through a temporary file and publish them with an atomic rename;
- return the newly created backup descriptor directly instead of identifying it with before/after full-directory scans.

## Security and Data Contract

### Local backup references

`DatabaseBackupCatalogService` owns local discovery and resolution. UI and download routes receive a deterministic 64-character HMAC reference, never a trusted filename or filesystem path. The service resolves the reference only against allowlisted `.sql`/`.zip` files in the existing private and legacy-compatible backup directories.

Public descriptors may contain:

- opaque reference;
- display filename;
- size and modification time;
- full-database classification.

Absolute and relative paths remain service-internal. Queue jobs keep their existing filename payload for serialized-job compatibility, but resolve it through the explicit trusted internal adapter before reading a file.

### Remote backup references

`GoogleDriveBackupBrowserService` returns an HMAC reference bound to the Drive file ID, filename, year and month. Download and delete resolve that reference only inside the application-owned `database/YYYY/MM` namespace. A raw Drive ID is rejected before network access.

Remote discovery is capped at:

- 10 year folders;
- 12 month folders per year;
- 100 files for UI actions;
- 1,000 files for retention.

The UI does not poll the remote API. The operator explicitly refreshes the list.

### Restore boundary

There is no direct remote download-and-restore operation. A remote file must first pass the 500 MB download bound and SQL/full-backup validation while being imported into local backup storage. Restore is then a separate local action protected by `database.restore` and its own confirmation.

The legacy public Google Drive URL importer is removed.

## Permission Contract

| Capability | Behavior in this phase |
|---|---|
| `database.backup` | Create local backups and queue an existing local SQL backup for Drive upload |
| `database.download` | Download an opaque local reference, download a permitted remote backup to local, or queue a small local backup email |
| `database.restore` | Upload a local SQL file and explicitly restore a validated full local backup |
| `database.destroy` | Delete local/remote backups and authorize retention policy execution |
| `system.env.update` | Configure OAuth and scheduling; automation save/manual run additionally require `database.backup` and `database.destroy` |

No permission is added or renamed. Route middleware and server-side Livewire authorization remain authoritative; capability-aware UI only mirrors those boundaries.

## Compatibility Boundary

Preserved:

- existing Google OAuth routes and names;
- Google Drive configuration, token and settings keys;
- `Laravel-Backup/database/YYYY/MM` folder layout;
- `system:cloud-backup` command and scheduler registration;
- upload and email queue job class names and serialized filename payloads;
- existing permission names;
- private and legacy-compatible local backup directories;
- existing Admin route names, including the database download route.

Intentionally retired:

- public Google Drive URL import;
- direct Drive download-and-restore;
- browser-supplied local filename/path and Drive file ID actions;
- automatic remote list polling.

No migration, seeder, configuration key, ClientPortal/PWA change or business-Module change is included.

## Files

### Added

```text
Modules/System/Services/Database/DatabaseBackupCatalogService.php
tests/Feature/System/SystemDriveBackupBoundaryTest.php
```

### Updated

```text
Modules/System/Console/CloudBackupCommand.php
Modules/System/Http/Controllers/GoogleDriveOAuthController.php
Modules/System/Jobs/SendDatabaseBackupEmail.php
Modules/System/Jobs/UploadDatabaseBackupToGoogleDrive.php
Modules/System/Livewire/Database/BackupManager.php
Modules/System/Livewire/Database/TableList.php
Modules/System/Livewire/Settings/StorageConfig.php
Modules/System/Services/Cloud/CloudBackupAutomationService.php
Modules/System/Services/Cloud/GoogleDriveBackupBrowserService.php
Modules/System/Services/Cloud/GoogleDriveConnectionService.php
Modules/System/Services/DatabaseService.php
Modules/System/Services/Env/SystemGoogleDriveConfigService.php
Modules/System/resources/views/livewire/database/backup-manager.blade.php
Modules/System/resources/views/livewire/database/table-list.blade.php
Modules/System/resources/views/livewire/settings/storage-automation.blade.php
Modules/System/resources/views/livewire/settings/storage-config.blade.php
Modules/System/routes/web.php
docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md
docs/modules/System/COLLABORATION_HANDOFF.md
tests/Feature/System/SystemBackupManagerTest.php
tests/Feature/System/SystemGoogleDriveSchedulerTest.php
```

## Verification Gate

The operator confirmed the approved impacted scope on 2026-08-29. A full-project regression was not required.

```text
Pint all changed PHP files                    PASS (17 files)
Focused backup-boundary tests                 PASS (6 tests, 78 assertions)
System module regression                      PASS (166 tests, 924 assertions)
Admin Feature regression                      PASS (133 tests, 1265 assertions)
Route inspection                              PASS (3 database routes)
Frontend production build                     PASS (Vite 7.3.6, 34 modules, 1.90s)
Desktop UI acceptance                         PASS
Mobile UI acceptance                          PASS
Working tree clean                            PASS
```

The route inspection confirmed:

```text
GET|HEAD admin/system/database
GET|HEAD admin/system/database/backup-restore
GET|HEAD admin/system/database/download/{filename}
```

The operator also confirmed every manual acceptance item:

1. A user without `database.backup` or `database.destroy` cannot save/run automation.
2. Local download, restore, delete, Drive upload and email actions work with rendered opaque references; a filename/path returns 404 or a safe error.
3. A raw Drive file ID cannot download or delete a file.
4. Remote download creates a local backup but never restores automatically.
5. Legacy public URL import and direct remote restore are absent.
6. Remote list refresh is explicit and the page does not poll every three seconds.
7. Upload failure shows only the generic safe message; logs do not contain external response bodies or raw exception text.
8. Existing queued upload/email jobs with filename payloads still resolve a trusted local backup.

## Deferred Work

- Consolidate broader settings ownership while retaining compatibility adapters.
- Separate Module registry discovery from runtime mutation.
- Improve scheduler idempotency/distributed locking and persisted health heartbeat behavior.
- Split database operations behind smaller services beyond this catalog boundary.
- Consolidate historical System analysis documents after implementation phases settle.

## PR and Merge Gate

1. **COMPLETE** — Operator pulled implementation checkpoint `481a4211387440657aa9df845daba2f1ed6c051c` and style checkpoint `11da525cdba355f4ac0a1abf41f0a2563773a159`.
2. **COMPLETE** — Focused, System, Admin, route, build and desktop/mobile UI gates passed.
3. **COMPLETE** — Verification results and the verified feature checkpoint are recorded in this handoff.
4. **NEXT** — Open a PR for manual user review.
5. The user merges manually; no automatic merge is allowed.
6. Post-merge closeout records the `main` checkpoint if requested.
