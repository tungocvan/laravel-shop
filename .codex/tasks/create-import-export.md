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

### Verification

- tests to add/update
- failure cases
- large-file verification
- rollback/recovery expectations

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

## 5. Rules

- Follow `.codex/standards/MODULE_STANDARD.md`.
- Follow `.codex/standards/ADMIN_UI_STANDARD.md` for import/export UI.
- Preserve existing public contracts unless the approved plan explicitly changes them.
- Do not modify unrelated modules.
- Do not invent missing business rules.
- Mark unresolved decisions and stop before coding when they materially affect correctness.

## Final Response

Planning phase: report the plan path and state that implementation is waiting for approval.

Implementation phase: report files changed, tests/verification, documentation updated, and unresolved risks if any.
