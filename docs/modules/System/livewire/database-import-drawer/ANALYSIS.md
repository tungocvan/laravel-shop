# System Livewire Analysis — Database/ImportDrawer

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Database/ImportDrawer.php` is a legacy SQL upload/import component. It is **P1 / Remove or replace** because it does not enforce `database.restore`, calls `DatabaseService::import()` even though the current `DatabaseService` exposes the newer `importBackupFile()`, `importTableFromFile()`, and `restoreFromFile()` workflows instead, and duplicates functionality already implemented more safely by `TableList` and `BackupManager`.

## Component Purpose

Path: `Modules/System/Livewire/Database/ImportDrawer.php`

Alias: `system.database.import-drawer`

View: `System::livewire.database.import-drawer`

Intended responsibility:

- upload a SQL file;
- invoke database import;
- notify UI;
- dispatch `backup-updated`.

## Dependency Flow

Database UI
→ `ImportDrawer`
→ `DatabaseService::import($path)` **(method not present in current service)**
→ intended database import

## Livewire PHP Analysis

The component uses `WithFileUploads` and exposes `$sqlFile`.

`save()` validates the upload as a file with SQL/plain-text mimetypes and a 50 MB limit, then calls `$service->import($path)`.

Current `DatabaseService` does not expose that method. The service now has distinct workflows for:

- importing a full backup file into private backup storage;
- restoring a validated full database backup;
- importing a validated single-table SQL file with safety backup and automatic recovery.

The component does not use `AuthorizesSystemActions`.

## Livewire Blade Analysis

The Blade uses an Alpine drawer and a Livewire form with file selection, validation output, cancel button, and loading-disabled submit state.

The UI labels the operation generically as "Import Database" and "Import SQL File" without explaining whether the file is a full database dump, a table dump, or merely uploaded for later restore. That ambiguity is unsafe for destructive database operations.

## State / Validation / Actions

Action:

- `save()`

Upload validation checks mimetype/size, but does not prove the SQL structure is an approved full backup or a dump for a specific table. The newer `DatabaseService` workflows contain stronger semantic validation and safety backups.

## Authorization

**P1:** no `database.restore` capability check exists inside `save()`.

## Service / Model Dependencies

The direct service contract is stale. This is clear documentation/code drift:

`ImportDrawer` expects `DatabaseService::import()` while the current service has different import/restore APIs.

Maintaining this legacy component would create a second database-import path competing with the safer current architecture.

## Performance

The component allows files up to 50 MB and intends synchronous processing inside the Livewire request. The current dedicated service workflows already define explicit import timeouts and safety mechanisms and should be reused instead.

## Security / Data Integrity

### P1 — Broken/stale service contract

Invoking `save()` reaches a method that is not present in current `DatabaseService`, causing runtime failure.

### P1 — Missing `database.restore` authorization

Database import/restore is a privileged mutation and must authorize at the Livewire action boundary.

### P1 — Ambiguous SQL semantics

Mimetype is insufficient to decide whether arbitrary SQL is safe to execute. The newer service validates full-backup/table-dump structure and creates safety backups; this component bypasses those protections.

### P2 — Duplicate import UX

Current `TableList` and `BackupManager` already provide explicit, safer import/restore flows.

## UI/UX Compliance

Positive:

- visible upload error;
- loading-disabled submit;
- drawer cancel action.

Needs improvement if retained:

- explain import type and destructive effect;
- explicit confirmation;
- capability-aware action;
- semantic file validation;
- recovery status.

## Test Coverage

No System-specific test was found for this component.

Missing tests would include authorization, service-contract correctness, SQL semantic validation, oversized file rejection, restore safety and failure recovery. However, adding tests to a legacy duplicate path is lower value than removing/replacing it.

## Issue List

### P1 — Calls missing `DatabaseService::import()`

### P1 — Missing `database.restore` authorization

### P1 — Legacy path bypasses current safety/recovery workflows

### P2 — Duplicates current database import UI

## Recommended Direction

**Remove/Replace rather than refactor in place.** Prefer the current `TableList::importTable()` for table-level imports and `BackupManager` + `DatabaseService::importBackupFile()/restoreFromFile()` for full database restore workflows. Preserve one canonical import architecture.

## Open Questions / Unknowns

- Whether this component is still mounted by any active page/configuration.
- Whether any external code dispatches `open-import-drawer` expecting this exact component.
