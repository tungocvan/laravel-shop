# Task: /import-export <ModuleName>

Create or improve import/export for an existing module using the repository's canonical architecture.

## Purpose

Import/export is a high-risk data workflow. This task MUST separate planning from implementation.

## 1. Required Context

Before planning, read when present:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
ROADMAP.md
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
```

Read the target module's relevant services, imports, exports, Livewire components, models, migrations and routes.

Read and reuse the canonical shared import/export infrastructure under:

```text
Modules/Shared/Services/ImportExport
Modules/Shared/Livewire/ImportExport
```

Do not create a competing shared framework when the existing foundation can be reused or extended.

## 2. Planning Phase

Create or update only:

```text
docs/modules/<ModuleName>/IMPORT_EXPORT_PLAN.md
```

The plan must define as applicable:

### Import

- accepted file formats
- template/header contract
- header aliases/mapping
- normalization rules
- validation rules
- authorization
- duplicate detection strategy
- create/update/upsert behavior
- transaction boundaries
- row-level error reporting
- chunk/batch strategy
- queue/progress threshold
- temporary/private storage
- cleanup/retention
- retry/idempotency behavior
- whether a file exported by the module can be imported back safely
- which fields are export-only and which fields are required only when creating new records

### Export

- authorization
- filters and scope
- columns/header contract
- data mapping/formatting
- query strategy
- large-dataset/chunk strategy
- queue/progress threshold
- private/public storage decision
- download lifecycle and cleanup
- selected-row export behavior

### Verification

- tests to add/update
- failure cases
- large-file verification
- rollback/recovery expectations
- UI verification for selected-row export and success feedback

## 3. Approval Gate

After creating/updating `IMPORT_EXPORT_PLAN.md`, STOP.

Implementation is forbidden until the user explicitly approves the plan.

Do not interpret creation of the plan as approval.

## 4. Implementation Phase

Only after explicit approval:

1. Re-read the approved plan and current source.
2. Implement only the approved scope.
3. Keep business validation/persistence in the owning module service.
4. Reuse shared import/export infrastructure.
5. Use transactions where atomicity is required.
6. Use private storage for sensitive files.
7. Never trust browser-provided paths.
8. Never load production-sized files/datasets entirely into memory.
9. Add queue/progress behavior when the approved plan requires it.
10. Add/update targeted tests.
11. Update `docs/modules/<ModuleName>/INFORMATION.md` and `README.md` when behavior changed.
12. Preserve selection state from the owning table into the shared import/export panel through reactive filters.
13. Show a clear success modal after a successful import/dry-run/export when the UI supports it; the modal should provide an explicit OK/refresh action so the list reflects the latest data.

## 5. Canonical Selected Export Contract

For admin list screens that expose row checkboxes, the default export behavior MUST be:

```text
selected_ids empty     -> export all records in the module's approved export scope
selected_ids not empty -> export only the selected records
```

Rules:

- Do not silently export only the current pagination page when nothing is selected.
- Do not require a separate "Export selected" button unless the approved UX explicitly calls for one.
- Pass `selected_ids` from the owning Livewire table into `shared.import-export.panel` as a reactive filter.
- The module export service must sanitize IDs to positive unique integers before applying `whereKey()`.
- Selected export takes precedence over normal list filters unless the approved plan explicitly says otherwise.
- Checkbox visibility must be allowed for users who can export selected rows even when they do not have destructive/delete permission.
- Add regression coverage proving both branches: no selection exports all; selection exports only selected IDs.

## 6. Import/Export Round-Trip Contract

When practical, a module's exported spreadsheet SHOULD be importable back into the same module for update/upsert workflows.

Therefore:

- Do not make a secret/non-exportable field a globally required import header if existing rows can be safely updated without it.
- A sensitive field may be required only for newly created rows while remaining optional for updates.
- Export must never expose secrets merely to satisfy import requirements.
- Existing system-owned values such as secret hashes, optimistic-lock versions, audit counters and internal IDs must be preserved on update unless the domain explicitly allows replacement.
- If a soft-deleted record is matched by the module's canonical unique key and the approved import behavior allows recovery, restore it through module-owned logic rather than creating a duplicate.

## 7. Success UX Contract

For shared Import/Export UI:

- Successful import should show a modal rather than only a transient flash message.
- Successful dry-run should show a modal with wording that no database write occurred.
- Successful export should show a modal after the export file is generated/download initiated when the framework supports component state updates with file downloads.
- The modal should have an explicit `OK` action that reloads/refreshes the current screen.
- Error reports should remain visible inline so the user can inspect row-level failures.
- Loading and disabled states are required for import/export actions.

## 8. Rules

- Follow `.codex/standards/MODULE_STANDARD.md`.
- Follow `.codex/standards/ADMIN_UI_STANDARD.md` for import/export UI.
- Preserve existing public contracts unless the approved plan explicitly changes them.
- Do not modify unrelated business modules.
- Shared Import/Export infrastructure may be improved when the change is generic and benefits all modules.
- Do not invent missing business rules.
- Mark unresolved decisions and stop before coding when they materially affect correctness.

## Final Response

Planning phase: report the plan path and state that implementation is waiting for approval.

Implementation phase: report files changed, tests/verification, documentation updated, and unresolved risks if any.
