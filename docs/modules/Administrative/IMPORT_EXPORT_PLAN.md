# Administrative Import / Export Plan

## Status

**IMPLEMENTED / UI VERIFIED — FINAL AUTOMATED VERIFICATION PENDING**

This implementation follows `.codex/tasks/create-import-export.md` and reuses the repository canonical shared foundation:

```text
Modules/Shared/Services/ImportExport
Modules/Shared/Livewire/ImportExport
shared.import-export.panel
```

## Implemented Scope

Primary dataset:

```text
Administrative submissions / Hồ sơ hành chính
```

Supported:

- `.xlsx` and `.csv` import.
- Downloadable template.
- Dry-run validation.
- `create_only`, `update_or_create`, `skip_duplicate` modes.
- Unsafe `replace` mode blocked for Administrative.
- Export using current Administrative filters.
- Export all approved-scope rows when no checkbox is selected.
- Export only selected records when one or more checkboxes are selected.
- Shared success modal with explicit refresh action after import/dry-run/export.

## Selected Export Contract

Canonical behavior:

```text
selected_ids empty     -> export all records in the approved export scope
selected_ids not empty -> export only selected records
```

The current paginator page is not treated as export scope.

## Import / Export Permission

```text
administrative.submission.import_export
```

The shared panel and selected-row export UI are gated by this permission. Server-side authorization remains authoritative.

## Import Contract

Canonical stable key:

```text
submission_code
```

Business reference:

```text
procedure_code -> procedure_id
```

System-owned/internal fields are not accepted directly from user spreadsheets.

### Lookup token handling

For new rows, the import path requires a safe lookup token and stores only its hash.

For existing submissions imported from a prior Administrative export, plaintext `lookup_token` is not required. Existing `lookup_token_hash`, `version`, and `revision_count` remain system-owned and are not overwritten during update/upsert.

Plaintext lookup secrets are never exported and are redacted from import error reporting.

### Soft-deleted records

`update_or_create` may resolve an existing matching submission with `withTrashed()` and restore it where the implemented business rule allows. This supports demo/test recovery without bypassing the audited Administrative delete workflow.

### Status integrity

Supported statuses remain:

```text
pending
need_supplement
approved
rejected
```

Import normalization validates status-specific reason fields and writes Administrative status-history metadata using source `administrative_import`.

## Export Contract

Export honors current filters:

```text
search
status
procedure_id
date_from
date_to
```

Selected IDs, when present, further restrict the export query.

Sensitive/internal fields are excluded, including:

```text
lookup_token
lookup_token_hash
version
deleted_at
internal file paths
checksums
```

Exported files are designed to be importable back for safe update/upsert without exposing secrets.

## Large Dataset Guard

Current synchronous export is intentionally bounded to the Administrative implementation limit rather than using an unbounded table/paginator query.

If production volume exceeds the current limit, queue/progress support should be added through shared infrastructure rather than a module-specific workaround.

## UI Contract

Administrative reuses `shared.import-export.panel`.

Required UX now implemented:

- loading/disabled states for long-running actions;
- row-level import errors remain inspectable;
- success modal after template/import/dry-run/export when appropriate;
- explicit `OK — tải lại` action to resynchronize parent/child Livewire state;
- checkbox selection is available to users allowed to export selected records, independent from delete permission.

## Manual Verification Completed

User manually verified:

- export all with no checkbox selected;
- export selected rows when checkboxes are selected;
- importing an exported file back into Administrative;
- delete-all flow;
- Administrative UI behavior after the Import/Export integration.

## Automated Verification Evidence

Latest supplied full regression checkpoint before this documentation closure:

```text
356 passed
12,858 assertions
0 failed
Duration: 19.00s
```

A final post-closure targeted/Pint/full regression run is still required before merge.

## Non-Goals

- Export lookup secrets.
- Import/export attachment binaries.
- Spreadsheet-driven hard delete.
- Raw shared `replace` semantics.
- Separate Administrative-only Import/Export framework.

## Acceptance Criteria

- [x] Shared Import/Export foundation reused.
- [x] Import template/dry-run/import/export implemented.
- [x] `procedure_code` used instead of internal procedure ID.
- [x] Lookup plaintext is not persisted/exported/logged.
- [x] Existing system-owned lookup/version fields are preserved during update.
- [x] Unsafe replace mode blocked.
- [x] Selected export semantics implemented.
- [x] Exported file can be imported back safely for supported update/upsert flows.
- [x] Import/Export permission declared.
- [x] Success modal + refresh UX implemented.
- [x] Manual UI verification completed.
- [ ] Final Pint/targeted/full regression verification completed after documentation closure.
