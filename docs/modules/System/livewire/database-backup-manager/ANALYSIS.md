# Database/BackupManager Livewire Analysis

## Executive Summary

`Modules/System/Livewire/Database/BackupManager.php` is the operational UI for full-database backup history, restore, backup upload, Google Drive ingestion, deletion, download/email delivery, and restore execution. Unlike older System components, it already uses `AuthorizesSystemActions` and enforces capability-specific authorization on sensitive mutations.

Overall direction: **targeted refactor / hardening**, not rebuild.

## Component Purpose

- List SQL backup files through `DatabaseService::getAllBackupFiles()`.
- Upload full database SQL backups from the browser.
- Import a full backup from Google Drive.
- Restore a selected full backup.
- Delete backup files.
- Dispatch backup delivery by email.

## Dependency Flow

`/admin/system/database/backup-restore`
→ database page Blade
→ `system.database.backup-manager`
→ `BackupManager.php`
↔ `backup-manager.blade.php`
→ `DatabaseService`
→ local backup storage / MySQL tools

Email flow:

`BackupManager::sendBackupEmail()`
→ `SendDatabaseBackupEmail`
→ `DatabaseService::getDownloadPath()`
→ Laravel Mail

Google Drive flow:

`BackupManager::importFromGoogleDrive()`
→ fixed Google Drive download endpoint
→ temporary file
→ `DatabaseService::importBackupFile()`
→ private backup storage

## Livewire PHP Analysis

### State

Public state:

- `$sqlFile`
- `$googleDriveUrl`
- `$showEmailModal`
- `$emailBackupFile`
- `$backupEmail`

The component listens for `backup-updated` using `#[On('backup-updated')]`.

### Authorization

Evidence:

- restore → `database.restore`
- delete → `database.destroy`
- local SQL upload → `database.restore`
- Google Drive import → `database.restore`
- open/send email → `database.download`

This is aligned with the module permission model and is materially better than legacy System components.

### Validation

Local upload validates a file with a 20 MB Livewire limit. `DatabaseService::importBackupFile()` performs the important second-stage validation: `.sql` extension, readable file, maximum size, and full-backup structure checks.

Google Drive input validates `url|max:2048`, then extracts a Drive file ID and always calls the fixed `https://drive.usercontent.google.com/download` host. This significantly limits generic SSRF risk because the user does not control the final host.

Email validates recipient format and re-resolves the backup file immediately before queue dispatch.

## Livewire Blade Analysis

Strengths:

- Empty state exists.
- Upload/import actions expose loading states.
- Restore has an explicit destructive confirmation.
- Delete has an explicit confirmation.
- Restore button is shown only for files classified as full backups.
- Email action is hidden for files larger than the job limit.
- Backup filename output is escaped by Blade.

Issues:

- Backup history is rendered as one potentially unbounded collection inside a scroll container. This is bounded only by filesystem reality, not by pagination or a configured limit.
- Actions are hidden until hover using opacity. This can reduce discoverability and may be weak on touch/mobile devices.
- Direct download is a normal anchor and relies on controller/route permission rather than Livewire action authorization, which is acceptable if the route remains protected.

## Service / Job Dependencies

`DatabaseService` owns backup validation, path resolution, restore safety backup, locking, and recovery. That is the correct architecture boundary.

`SendDatabaseBackupEmail` is queued, limited to 10 MB attachments, resolves the path again at execution time, and retries up to three times. This avoids serializing the file itself into the queue payload.

## Performance

P2: `getAllBackupFiles()` is executed during every render and the entire result is rendered. If the backup directory grows significantly, filesystem scanning and DOM size can become costly.

Recommended: bounded history, pagination-like slicing, or configurable recent-backup limit.

## Security / Data Integrity

Strengths:

- Capability authorization at action boundaries.
- Restore safety logic is delegated to `DatabaseService`.
- Restore/delete identifiers are resolved through service validation rather than direct arbitrary paths.
- Google Drive download host is fixed.
- Temporary Google Drive file is removed in `finally`.

P1/P2 hardening:

- Do not return raw exception messages to the browser for operational failures unless explicitly classified safe. Some service errors may expose filesystem/database operational details.
- Consider audit logging for restore, delete, upload/import, and email-delivery actions.
- Consider per-user/rate controls for repeated large Drive imports or restore attempts.

## UI/UX Compliance

Mostly compliant with Admin UI Standard: loading states, confirmations, empty state, responsive layout.

P2: improve touch/mobile action discoverability and bounded backup history.

## Test Coverage

No dedicated System feature/unit test covering `BackupManager` was observed in the current test tree.

Recommended tests:

- unauthorized restore/delete/upload/email actions are rejected;
- invalid/non-full SQL upload rejected;
- restore only accepts full backup identifiers;
- Google Drive ID parsing and failed HTTP response handling;
- email size limit and missing-file race;
- destructive actions remain confirmed at UI level where practical.

## Issue List

### P1

- Raw operational exception messages may be surfaced to the browser.
- No dedicated automated authorization/restore/import tests were found for this high-risk component.

### P2

- Unbounded backup listing/render-time filesystem scan.
- Hover-dependent action visibility is weak for touch devices.
- Missing audit trail for privileged database operations.

## Recommended Direction

**Targeted refactor / hardening.** Keep the component and current `DatabaseService` architecture. Add tests, safe error mapping, audit logging, and bounded backup history. Do not rebuild the backup engine.

## Open Questions / Unknowns

- Whether global audit infrastructure already exists elsewhere and should be reused.
- Expected maximum retained backup count in production.
- Whether backup email delivery is intended for arbitrary recipient addresses or only privileged/admin-owned recipients.
