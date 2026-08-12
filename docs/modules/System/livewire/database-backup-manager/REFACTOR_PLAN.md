# Database/BackupManager — P2 Refactor Plan

## 1. Scope

Target:

```text
Modules/System/Livewire/Database/BackupManager.php
Modules/System/resources/views/livewire/database/backup-manager.blade.php
```

This is a **targeted P2 hardening/refactor**, not a rebuild of the database backup engine.

The current `DatabaseService`, routes, Livewire alias and existing backup/restore behavior remain canonical unless explicitly stated below.

## 2. Goals

1. Stop exposing raw operational exception messages to the browser.
2. Preserve capability-specific authorization on every sensitive action.
3. Add focused automated coverage for the high-risk backup-management surface.
4. Bound backup-history rendering so filesystem growth does not create an unbounded Livewire payload/DOM.
5. Improve action discoverability on touch/mobile without redesigning the page.
6. Keep all database/file workflow logic in `DatabaseService`; Livewire remains orchestration/UI state.
7. Do not introduce a queue/operation framework as part of this P2 refactor.

## 3. Public Contract to Preserve

Preserve:

- Livewire alias/component: `system.database.backup-manager`.
- Route/page: `/admin/system/database/backup-restore`.
- Route view permission: `database.view`.
- Action permissions:
  - restore/upload/Google Drive import → `database.restore`
  - delete → `database.destroy`
  - email/download-related actions → `database.download`
- Existing supported operations:
  - list backups
  - upload SQL backup
  - import SQL backup from Google Drive
  - restore full backup
  - delete backup
  - download backup through the existing protected route
  - queue backup email delivery
- Existing `DatabaseService` path/backup validation as the authoritative server-side boundary.
- `SendDatabaseBackupEmail` and its attachment-size contract.

No Admin Menu change is required because this component is already reached through the existing Database/Backup Restore page.

## 4. P1/P2 Findings to Close

### P1 hardening carried into this P2 batch

Current component catches operational exceptions and in several paths sends `$e->getMessage()` directly to Livewire/browser state.

Affected flows include:

- restore
- delete
- upload
- Google Drive import

Refactor rule:

- log/report technical exception details server-side;
- show stable user-safe messages in browser;
- validation errors that are intentionally user-facing may remain specific when they are generated from known validation/business rules rather than arbitrary infrastructure exceptions.

### P2 — unbounded backup history

`render()` currently calls `DatabaseService::getAllBackupFiles()` and renders the complete result.

Plan:

- introduce a small explicit recent-history limit at the component/UI boundary;
- preserve newest-first ordering supplied by the service;
- expose a simple bounded list rather than adding pagination infrastructure unless current service behavior requires it;
- document the chosen limit in code/test so behavior is deterministic.

This is intentionally a bounded-history optimization, not a redesign of backup retention.

### P2 — touch/mobile action discoverability

Actions should not depend exclusively on hover visibility.

Plan:

- keep desktop compactness;
- ensure primary row actions remain discoverable/usable without hover on touch/mobile;
- do not introduce a new global UI primitive unless an existing canonical one can be reused without scope expansion.

### P2 — audit trail

The analysis recommends audit logging for privileged backup operations. Before implementing a new audit mechanism, inspect the current System/project audit infrastructure.

Decision rule:

- if a canonical audit facility already exists and can be reused locally, record restore/delete/upload/import/email-dispatch actions through it;
- if no canonical audit facility exists, do **not** invent a new module-wide audit framework inside this component refactor. Keep normal structured application logging and record audit infrastructure as residual architectural debt.

## 5. Livewire PHP Changes

Planned changes to `BackupManager.php`:

- add a consistent private helper for user-safe notifications where useful;
- add a consistent private helper for structured operation error logging/reporting;
- replace raw exception messages from operational catch blocks with stable safe messages;
- retain `authorizePermission(...)` as the first meaningful boundary for every sensitive mutation;
- retain server-side re-resolution of backup identifiers before email dispatch;
- keep Google Drive final download host fixed;
- retain temporary-file cleanup in `finally`;
- bound the backup collection passed to Blade;
- keep business/file/database logic delegated to `DatabaseService`.

Do not move SQL validation, restore mechanics, path resolution, locking, recovery, or filesystem ownership into Livewire.

## 6. Blade Changes

Planned changes to `backup-manager.blade.php`:

- preserve existing destructive confirmations;
- preserve loading states and empty state;
- make row/action controls usable without hover-only discovery;
- if the backup list is truncated to recent history, show a small explanatory message so the UI does not imply it is the complete retention inventory;
- preserve protected download route usage;
- avoid unrelated visual redesign.

## 7. Authorization

No permission redesign.

Verification matrix:

| Action | Required permission |
|---|---|
| page/view | `database.view` |
| restore backup | `database.restore` |
| upload SQL | `database.restore` |
| Google Drive import | `database.restore` |
| delete backup | `database.destroy` |
| open email modal | `database.download` |
| send backup email | `database.download` |
| direct download route | `database.download` |

Tests must verify mutation-level authorization, not only route middleware.

## 8. Security / Data Integrity

Must preserve:

- fixed Google Drive host;
- Drive ID extraction rather than arbitrary remote URL fetching;
- service-side `.sql`/backup validation;
- service-side safe backup identifier/path resolution;
- temporary-file cleanup;
- email file existence and size re-check immediately before dispatch;
- capability-specific authorization.

Must improve:

- no arbitrary infrastructure exception text in browser messages;
- structured logs retain diagnostic context.

No changes to database migrations are required.

## 9. Performance

Primary P2 optimization:

```text
unbounded getAllBackupFiles() render output
→ deterministic recent backup history window
```

Do not add caching until measurement demonstrates a need. Backup mutation events already cause re-render and freshness is more important than speculative caching here.

## 10. Tests

Add a focused System feature test, proposed:

```text
tests/Feature/System/SystemBackupManagerTest.php
```

Coverage should include, as practical with fakes/mocks/source-contract assertions consistent with the existing System test style:

1. route remains protected by `database.view`;
2. restore requires `database.restore`;
3. delete requires `database.destroy`;
4. upload/import require `database.restore`;
5. email modal/send require `database.download`;
6. raw exception messages are not surfaced to browser-facing notify/error paths;
7. backup history is bounded deterministically;
8. Google Drive flow does not accept arbitrary final hosts;
9. missing/oversized email backup remains rejected;
10. component retains `DatabaseService` delegation rather than implementing DB operations directly.

Run:

```bash
php artisan test tests/Feature/System/SystemBackupManagerTest.php
php artisan test tests/Feature/System
```

Acceptance target after implementation:

```text
SystemBackupManagerTest: PASS
Full tests/Feature/System regression suite: 0 failed
```

## 11. Files Expected to Change

Primary:

```text
Modules/System/Livewire/Database/BackupManager.php
Modules/System/resources/views/livewire/database/backup-manager.blade.php
tests/Feature/System/SystemBackupManagerTest.php
docs/modules/System/livewire/database-backup-manager/ANALYSIS.md
docs/modules/System/livewire/database-backup-manager/REFACTOR_PLAN.md
```

Conditional only if an already-existing canonical audit facility is reused:

```text
existing System/shared audit integration point
```

Do not modify unrelated modules/components.

## 12. Explicitly Out of Scope

- rebuilding `DatabaseService`;
- changing database backup format;
- changing backup retention policy;
- adding cloud providers beyond the existing Google Drive flow;
- introducing a generic queue/progress framework;
- changing Admin Menu structure;
- changing permissions/permission names;
- changing database migrations;
- refactoring `Database/TableList` in this batch;
- creating a new project-wide audit subsystem.

## 13. Rollback / Recovery

The refactor is intentionally reversible:

- no schema change;
- no route/alias change;
- no permission rename;
- no backup-format change;
- no storage-path migration.

If regression occurs, revert the component/view/test commit without requiring data rollback.

## 14. Acceptance Criteria

The refactor is complete only when all are true:

- [ ] all sensitive actions still enforce the correct permission;
- [ ] no operational catch block exposes arbitrary `$e->getMessage()` to the browser;
- [ ] technical failures are logged with useful server-side context;
- [ ] Google Drive final host remains fixed and temp files are cleaned up;
- [ ] backup history rendered by Livewire is bounded;
- [ ] actions are usable/discoverable on touch/mobile without hover dependency;
- [ ] download/email file validation remains intact;
- [ ] no DB/business workflow is moved from `DatabaseService` into Livewire;
- [ ] focused BackupManager tests pass;
- [ ] full `tests/Feature/System` suite has 0 failures;
- [ ] component `ANALYSIS.md` is updated to reflect the implemented state.

## 15. Implementation Gate

**STOP after this plan.**

Implementation must not begin until the user explicitly approves this `REFACTOR_PLAN.md`, per `.codex/tasks/refactor-livewire.md`.
