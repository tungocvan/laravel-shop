# System Livewire Analysis — Database/TableList

## Executive Summary

Target component:

```text
Modules/System/Livewire/Database/TableList.php
```

Blade:

```text
Modules/System/resources/views/livewire/database/table-list.blade.php
```

Purpose: display database tables and provide operational actions for backup, table export/restore, truncate, drop, and full database restore.

Overall direction: **Refactor recommended before adding more database-management features.** The component follows the Service-layer rule for core database operations and has permission checks per sensitive action, but the current synchronous backup/restore workflow, service resolution style, UI/action inconsistencies, and large-file restore path should be tightened before expansion.

---

## Component Purpose

The component manages UI state for:

- table search
- select all / selected tables
- full database backup
- per-table export
- per-table restore
- truncate/drop table
- selecting a full backup file
- full database restore modal

Dependency flow:

```text
/admin/system/database
        ↓
DatabaseController@index
        ↓
System::pages.database
        ↓
system.database.table-list
        ↓
TableList.php ↔ table-list.blade.php
        ↓
DatabaseService
        ↓
MySQL + local private backup storage + mysqldump/mysql processes
```

---

## Livewire PHP Analysis

### Good

- Core database operations are delegated to `DatabaseService`; the component does not execute raw DB mutation logic directly.
- Sensitive actions call capability-specific authorization:
  - `database.backup`
  - `database.restore`
  - `database.destroy`
- `restoreDatabase()` has a local double-submit guard through `$isRestoring`.
- Exceptions are caught so UI receives operation feedback.

### Issues

#### P1 — Service dependency handling is inconsistent

**File:** `Modules/System/Livewire/Database/TableList.php`

**Evidence:** `boot(DatabaseService $service)` assigns `$this->service`, `render()` injects `DatabaseService` again, while `openRestoreModal()` and `restoreDatabase()` resolve it through `app(DatabaseService::class)`.

**Problem:** one component uses three service-resolution patterns, and `$service` is not explicitly declared as a component property.

**Impact:** harder to reason about/test; dynamic-property behavior can become fragile across PHP/Livewire changes.

**Recommendation:** declare a protected service dependency and use one repository-approved injection pattern consistently.

---

#### P1 — Long-running destructive operations run synchronously in Livewire requests

**Files:**

- `Modules/System/Livewire/Database/TableList.php`
- `Modules/System/Services/DatabaseService.php`

**Evidence:** `backupFull()`, `restoreTable()`, and `restoreDatabase()` invoke `mysqldump` / `mysql` processes directly; full restore can use a 600-second process timeout.

**Problem:** the browser request remains responsible for a potentially long database operation.

**Impact:** web-server/PHP/Livewire timeouts, interrupted UX, duplicate/retry ambiguity, poor observability for production-size databases.

**Recommendation:** for production-sized backup/restore, move execution to a controlled job/command workflow with persisted operation status/progress and explicit authorization/audit. Keep synchronous mode only for clearly bounded environments if intentionally supported.

---

#### P1 — Detailed exception messages are exposed directly to the UI

**File:** `Modules/System/Livewire/Database/TableList.php`

**Evidence:** actions dispatch messages containing `$e->getMessage()`.

**Problem:** process/database/storage errors can contain operational details not intended for admin-facing UI.

**Impact:** information disclosure and inconsistent UX.

**Recommendation:** log technical details server-side and return stable user-safe messages/codes. Preserve detailed diagnostics in logs/audit only.

---

#### P2 — Unused / incomplete state exists

**File:** `Modules/System/Livewire/Database/TableList.php`

**Evidence:** `$loadingAction` is declared but not used; `updatedSearch()` contains only a commented pagination reset.

**Recommendation:** remove dead state/comments or implement the intended behavior during refactor.

---

## Livewire Blade Analysis

### Good

- Table uses `overflow-x-auto`.
- Rows use stable `wire:key` based on table name.
- Empty state exists.
- Dangerous table actions use confirmation prompts.
- Backup/export/restore actions expose loading UI in several places.
- Full restore requires an additional confirmation.

### Issues

#### P1 — “Export Selected” is currently non-functional

**File:** `Modules/System/resources/views/livewire/database/table-list.blade.php`

**Evidence:** when `selectedTables` is non-empty, the `Export Selected` button has no `wire:click` or other action.

**Impact:** UI advertises a feature that does nothing.

**Recommendation:** either implement a planned bulk-export workflow or remove/hide the button until supported.

---

#### P1 — Restore progress is simulated rather than real operation progress

**File:** `Modules/System/resources/views/livewire/database/table-list.blade.php`

**Evidence:** Alpine increments progress randomly until 90% while `$wire.isRestoring` is true.

**Problem:** displayed percentage is not linked to actual restore progress.

**Impact:** misleading operational feedback for a destructive database action.

**Recommendation:** use an indeterminate progress state for synchronous execution, or real persisted progress if restore is moved to a job/operation workflow.

---

#### P1 — Full restore cancellation/control UX is weak during execution

**File:** `Modules/System/resources/views/livewire/database/table-list.blade.php`

**Evidence:** modal cancel control can set `showRestoreModal=false`; restore is a server-side process and closing the modal does not cancel the operation.

**Impact:** user may believe restore was cancelled while it continues server-side.

**Recommendation:** disable modal close/cancel while an irreversible restore is active, or explicitly label it as “Hide” rather than cancel when the backend cannot abort safely.

---

#### P2 — Component UI is visually functional but not fully aligned with the canonical Admin UI standard

**Evidence:** mixed `rounded-lg` / `rounded-xl`, varying button heights/classes, hand-built modal/progress patterns.

**Recommendation:** during refactor, reuse approved shared UI primitives where available and standardize button/input/modal states without performing unrelated global UI migration.

---

## State / Validation / Actions

### State

```text
search
selectedTables
selectAll
loadingAction
backupFiles
selectedBackupFile
showRestoreModal
isRestoring
```

### Validation

There is no Livewire validation ruleset. Most dangerous identifiers are validated again by `DatabaseService::assertAllowedTable()` or `resolveBackupIdentifier()`, which is the correct security boundary for server-provided table/backup identifiers.

For `selectedBackupFile`, the service validates the identifier and verifies the backup appears to be a full dump before restore.

### Selection consistency

`selectAll` loads all currently matching tables into `selectedTables`, but there is no synchronization that clears/updates `selectAll` when individual selections change or when search changes.

**Priority: P2.** This is primarily UI-state correctness and becomes more important if bulk actions are implemented.

---

## Authorization

Route access is guarded by:

```text
auth:admin
permission:database.view,admin
```

Sensitive Livewire mutations additionally enforce:

```text
database.backup
database.restore
database.destroy
```

This is a strong design point and matches `MODULE_STANDARD.md`: authorization exists at the mutation boundary, not only at page access.

Download is separately protected by `database.download` in the route/controller.

---

## Service / Database Dependencies

`DatabaseService` performs:

- `SHOW TABLE STATUS`
- table existence/identifier checks
- private backup storage
- `mysqldump`
- `mysql` import
- truncate/drop
- full restore locking
- pre-restore safety backup
- automatic recovery attempt

Positive controls include:

- protected-table list
- strict table identifier regex
- current-table existence verification
- private backup path resolution
- backup identifier sanitization
- full-restore file heuristic
- restore lock file
- automatic safety backup before full restore

### Important service-level risk discovered from the component dependency

#### P0 — Full SQL import reads the whole backup into PHP memory

**File:** `Modules/System/Services/DatabaseService.php`

**Evidence:** `runMysqlImport()` uses:

```php
$process->setInput(file_get_contents($inputPath));
```

while uploaded SQL files may be accepted up to 500 MB.

**Problem:** the entire SQL dump is materialized in PHP memory before being piped to `mysql`.

**Impact:** memory exhaustion, failed restore, and elevated operational risk during a destructive recovery workflow.

**Recommendation:** stream the SQL file to the process/STDIN or invoke mysql with safe redirected/streamed input without loading the complete dump into PHP memory. This should be fixed before relying on large production restores.

---

## Performance

### Table listing

`render()` calls `getAllTables($search)` on every render. That method executes `SHOW TABLE STATUS` and performs a storage existence check for every returned table.

For a normal application schema this may be acceptable, but every keystroke uses `wire:model.live.debounce.300ms`, producing repeated metadata/storage work.

**Priority: P2** unless the database contains a very large number of tables or remote/slow storage.

Possible improvements:

- retain debounce
- avoid duplicated table refreshes around actions when unnecessary
- evaluate caching only if measurements show a problem

Do not add pagination merely for abstraction; table counts are typically bounded, but the current commented pagination code should be cleaned up.

---

## Security / Data Integrity

### Strong points

- destructive table names are server-validated
- protected tables cannot be truncated/dropped
- full restore creates a safety backup
- full restore uses a lock to prevent concurrent restore processes
- backup downloads use controlled resolution rather than direct browser paths
- database credentials are passed through process environment for password rather than directly embedding password in the command

### Risks

1. **P0:** memory-heavy full restore input described above.
2. **P1:** synchronous destructive operations are coupled to web request lifecycle.
3. **P1:** raw exception details can leak internal operational information.
4. **P1 / Inference:** truncate/drop disable `FOREIGN_KEY_CHECKS` per connection operation. This is carefully reset in `finally`, but destructive database-management actions merit audit logging and stronger operational safeguards if not already provided elsewhere.

---

## UI / UX Compliance

Status: **Partially compliant**.

Good:

- responsive table wrapper
- empty state
- loading states
- confirmations
- clear destructive coloring

Needs improvement:

- simulated restore percentage
- no action for Export Selected
- cancel semantics during restore
- component-specific modal/button styling instead of shared canonical patterns where available
- inconsistent notification payload keys (`content` in some actions, `message` in full restore); verify the global notify listener contract before changing it

---

## Test Coverage

**Unknown:** no directly related automated test was identified through the inspected component dependencies/search available in this analysis.

Recommended focused tests before/with refactor:

- unauthorized user cannot call backup/restore/destroy actions
- protected table cannot be truncate/drop
- invalid table identifier rejected
- backup identifier/path traversal rejected
- full restore rejects non-full backup
- concurrent full restore is blocked
- failed restore triggers recovery path
- Livewire selection/search state behavior
- bulk export behavior if implemented
- user-safe error handling

---

## Issue Summary

| Priority | Issue |
|---|---|
| P0 | `runMysqlImport()` loads the full SQL backup into PHP memory |
| P1 | Backup/restore operations run synchronously in Livewire/web requests |
| P1 | Raw exception messages are exposed to UI |
| P1 | `DatabaseService` injection/resolution is inconsistent |
| P1 | “Export Selected” button has no action |
| P1 | Restore progress percentage is simulated |
| P1 | Modal “Cancel” can imply cancellation while restore continues |
| P2 | Selection/select-all state can become inconsistent |
| P2 | Dead/incomplete state (`loadingAction`, empty `updatedSearch`) |
| P2 | UI styles/shared patterns can be standardized |

---

## Recommended Direction

**Refactor this component and its direct database-service restore path before adding substantial new functionality.**

Recommended order:

```text
1. Fix streaming restore input (P0)
2. Define safe operation/error/audit contract
3. Standardize DatabaseService injection
4. Decide synchronous vs queued backup/restore architecture
5. Fix restore UX/progress semantics
6. Implement or remove bulk Export Selected
7. Add targeted security/restore tests
8. Then add new Database Manager features
```

No full module rebuild is required based on this component alone.

---

## Open Questions / Unknowns

- Whether a global audit log already records database backup/restore/truncate/drop operations.
- Exact global `notify` event payload contract (`content` vs `message`).
- Whether production PHP/web-server timeouts are intentionally configured for long database operations.
- Whether the intended “Export Selected” behavior is one combined SQL file, multiple files, or a ZIP.
