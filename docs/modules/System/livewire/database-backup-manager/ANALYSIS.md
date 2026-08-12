# Database/BackupManager Livewire Analysis

## Status

**P2 targeted hardening implemented on 2026-08-12.**

## Current Architecture

`/admin/system/database/backup-restore`
→ `system.database.backup-manager`
→ `BackupManager.php`
→ `DatabaseService`
→ private backup storage / database tools

The component remains an orchestration/UI boundary. Database restore, backup validation, path resolution and destructive database workflows remain owned by `DatabaseService`.

## Authorization

Capability-specific action authorization is preserved:

- restore → `database.restore`
- local SQL upload → `database.restore`
- Google Drive import → `database.restore`
- delete → `database.destroy`
- open/send email → `database.download`

The page route remains protected by `database.view`, and direct download remains protected by `database.download`.

## Implemented Hardening

- operational exceptions are logged server-side through structured `Log::error()` context instead of exposing arbitrary exception messages to the browser;
- browser-facing restore/delete/upload/Drive-import errors are stable generic messages;
- Google Drive still extracts a file ID and downloads only through the fixed `https://drive.usercontent.google.com/download` host;
- Drive temporary files are still removed in `finally`;
- email delivery still re-resolves the backup path and enforces `SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES` before queue dispatch;
- queued email delivery is logged with actor/backup/recipient metadata;
- backup history rendered by Livewire is bounded to the 50 most recent entries;
- the UI explains when history is truncated;
- row actions are visible by default on touch/mobile and only slightly compacted on larger screens rather than being hover-only;
- destructive confirmations and loading states remain.

## Performance

`DatabaseService::getAllBackupFiles()` is still called for freshness on render, but the Livewire payload/DOM is capped to 50 recent backups. This closes the unbounded rendering issue without introducing speculative caching or changing storage retention.

Filesystem scanning itself remains proportional to retained backup count. If production retention becomes very large, service-level indexed listing/pagination can be considered separately.

## Audit / Logging

No canonical project-wide audit facility was found during this refactor review, so no new audit framework was introduced. Structured application logging is used for operational errors and queued email delivery.

A reusable project-wide privileged-operation audit system remains optional architectural debt rather than component-local scope.

## UI / UX

Current state:

- responsive upload/import sections;
- empty state;
- recent-history explanation;
- touch/mobile-discoverable actions;
- restore/delete confirmations;
- loading states;
- protected download route;
- email modal with recipient validation.

## Test Coverage

Focused coverage now exists in:

```text
tests/Feature/System/SystemBackupManagerTest.php
```

The contract test verifies permission boundaries, safe error handling, bounded history, fixed Drive host, temp cleanup, email size/path checks, download route use and mobile-visible action behavior.

Full System regression must remain green after deployment/merge.

## Residual Risks / Future Work

- Full database restore remains a potentially long synchronous operation; moving database operations to persisted jobs/status is a larger architectural change and remains outside this P2 cleanup.
- Filesystem scanning still enumerates retained backups before the component slices the rendered history.
- A project-wide privileged-operation audit framework does not currently exist in the inspected repository.

## Final Direction

**Keep component. P2 hardening complete.**

No rebuild, route change, permission change, migration or Admin Menu change is required from this component review.
