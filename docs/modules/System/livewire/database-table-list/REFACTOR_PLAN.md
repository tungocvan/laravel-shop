# REFACTOR PLAN — System / Database / TableList

## Goal

Refactor `Modules/System/Livewire/Database/TableList.php` and its direct database-service/UI dependencies to make database operations safer, more consistent, and production-ready before extending the component further.

This plan preserves current routes, Livewire alias, permissions, table backup naming, and existing user-visible capabilities unless explicitly listed below.

## Scope

Primary files expected to change after approval:

```text
Modules/System/Livewire/Database/TableList.php
Modules/System/resources/views/livewire/database/table-list.blade.php
Modules/System/Services/DatabaseService.php
```

Targeted tests may be added/updated under the repository's existing test structure.

No unrelated System components/modules are in scope.

---

## P0 — Stream SQL restore input

### Problem

`DatabaseService::runMysqlImport()` currently uses `file_get_contents($inputPath)` and passes the complete SQL dump to Symfony Process input.

### Change

- Replace full-file materialization with streamed/file-backed process input.
- Keep current backup identifier/path validation.
- Keep process timeout handling and server-side logging.
- Ensure the SQL file is never loaded entirely into PHP memory.

### Acceptance

- Large SQL backups can be restored without allocating the entire file size in PHP memory.
- Existing full database restore and per-table restore continue to work.

---

## P1 — Standardize `DatabaseService` dependency

### Problem

The component currently uses three resolution styles:

```text
boot(DatabaseService $service)
render(DatabaseService $service)
app(DatabaseService::class)
```

### Change

Use one canonical Livewire service-injection pattern throughout the component.

Recommended direction:

```php
protected DatabaseService $service;

public function boot(DatabaseService $service): void
{
    $this->service = $service;
}
```

All actions and render logic use the same dependency.

### Acceptance

- No `app(DatabaseService::class)` calls remain inside `TableList`.
- `render()` does not use a second service injection path.

---

## P1 — Safe user-facing errors

### Problem

Raw exception messages are currently dispatched to the UI.

### Change

- Log technical exception details server-side.
- Dispatch stable user-safe messages from Livewire.
- Preserve enough operation context in logs for diagnostics.
- Standardize the global notify payload key after confirming the repository listener contract.

### Acceptance

- SQL/process/storage internals are not exposed directly to the browser.
- User receives clear success/failure feedback.

---

## P1 — Restore UX correctness

### Problem

The current restore progress bar is simulated and can imply actual progress. The modal can also be closed while the backend restore continues.

### Change

For the current synchronous architecture:

- replace fake percentage with an indeterminate "Đang phục hồi dữ liệu..." state;
- disable modal close/cancel while restore is active;
- keep double-submit protection;
- disable destructive controls while their action is executing.

Do not add a queue/job architecture in this component refactor unless separately approved.

### Acceptance

- UI never displays fabricated restore percentages.
- User cannot accidentally trigger the same restore twice.
- UI does not imply that closing a modal cancels a running database restore.

---

## P1 — Bulk Export Selected

### Problem

The Blade shows `Export Selected` but the button has no action.

### Change

Implement the existing UI promise using selected table names.

Recommended behavior:

- authorization: `database.backup`;
- validate every selected table server-side;
- generate one SQL dump containing all selected tables, rather than many browser downloads;
- store it in private backup storage;
- return/offer a controlled download using the existing database download route when compatible;
- use a deterministic safe filename with timestamp;
- do not allow empty selection.

`DatabaseService` owns dump creation; Livewire only coordinates state/feedback.

### Acceptance

- `Export Selected` works for one or many selected tables.
- Invalid/manipulated table identifiers are rejected server-side.
- No table list is trusted directly from browser state without service validation.

---

## P2 — Selection state cleanup

### Change

- Keep `selectAll` synchronized with `selectedTables` where practical.
- When search changes, avoid leaving a misleading "all selected" state.
- Remove dead `$loadingAction` state unless implementation gives it a concrete role.
- Remove/comment-clean the empty `updatedSearch()` placeholder.

### Acceptance

Selection UI accurately reflects the current visible/selected table set.

---

## P2 — UI standardization

Within this component only:

- normalize button/input sizing and disabled/loading states;
- keep responsive table wrapper;
- preserve destructive colors/confirmations;
- reuse existing shared modal/button primitives if already canonical and compatible;
- do not perform a global Admin UI migration.

---

## Security / Data Integrity

Keep or strengthen:

- `database.backup`, `database.restore`, `database.destroy` action authorization;
- protected-table checks for truncate/drop;
- strict table identifier validation;
- private backup storage;
- full restore lock;
- pre-restore safety backup;
- automatic full-restore recovery path;
- controlled backup download path resolution.

Recommended addition during implementation if the project already has an audit facility: record backup, restore, truncate, and drop actions. Do not invent a second audit framework inside this component.

---

## Long-running Operations

This refactor does **not** automatically move backup/restore to queues because that would broaden the operational architecture.

However:

- synchronous execution remains a known P1 production limitation;
- implementation should structure service/actions so a later queued operation workflow can be introduced without rewriting business rules.

A separate plan should be created if queue/job based backup/restore is desired.

---

## Tests / Verification

Add or update focused tests where repository test infrastructure supports them:

- permission denial for backup/restore/destroy actions;
- invalid table identifier rejection;
- protected table truncate/drop rejection;
- bulk selected export validates all tables;
- backup path traversal rejection;
- restore rejects invalid/non-full backup files;
- full restore concurrency lock;
- restore failure/recovery path where testable;
- Livewire duplicate restore guard;
- selection state behavior;
- user-safe error notifications.

Manual verification should include a small test database dump/restore in a non-production environment.

---

## Rollback

- Preserve route names, Livewire alias and permissions.
- Do not change schema/migrations.
- Changes are limited to component, Blade, service, and tests.
- If the refactor fails verification, source changes can be reverted without database migration rollback.

---

## Acceptance Criteria

```text
[ ] SQL import no longer loads whole files into PHP memory
[ ] DatabaseService injection is consistent
[ ] Technical exception details are server-side only
[ ] Restore UI uses truthful indeterminate progress
[ ] Restore cannot be double-submitted
[ ] Export Selected is functional and server-validated
[ ] Selection state is coherent
[ ] Existing backup/export/restore/truncate/drop behavior is preserved
[ ] Authorization remains enforced at every sensitive Livewire action
[ ] Targeted verification passes
```

## Implementation Gate

**Do not implement this plan until the user explicitly approves `REFACTOR_PLAN.md`.**
