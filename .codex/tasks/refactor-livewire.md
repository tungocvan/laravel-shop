# Task: /refactor-livewire <ModuleName> <Component>

Refactor one Livewire component safely based on a prior component analysis.

## Purpose

Use this task when one Livewire component needs architectural cleanup, performance/security fixes, maintainability improvements, or Admin UI consistency improvements without refactoring the entire module.

Example:

```text
/refactor-livewire Administrative Submissions/SubmissionDetail
```

## 1. Required Context

Before planning, read when present:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
.codex/tasks/create-import-export.md
ROADMAP.md
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
docs/modules/<ModuleName>/livewire/<component-key>/ANALYSIS.md
```

If the component-level `ANALYSIS.md` does not exist, run the `/analyze-livewire` workflow first.

## 2. Planning Phase

Create or update only:

```text
docs/modules/<ModuleName>/livewire/<component-key>/REFACTOR_PLAN.md
```

The plan must define as applicable:

- exact refactor goal
- behavior/public contract that must be preserved
- Livewire PHP changes
- Livewire Blade changes
- service extraction/delegation
- model/query changes
- authorization changes
- transaction/concurrency changes
- shared UI component reuse
- performance fixes
- security/data-integrity fixes
- test changes
- documentation changes
- files to change
- rollback/recovery notes
- acceptance criteria

When the component is a table/list/workspace, explicitly evaluate:

- search and domain filters
- reset-filter behavior
- bounded pagination/page-size options
- custom/shared pagination view and visual treatment
- checkbox selection semantics
- selected count
- bulk actions
- destructive confirmation modal
- loading/disabled states
- empty/error states
- Import/Export integration when applicable
- selected-row export contract
- success feedback and refresh behavior after mutations/import/export

Classify issues as P0/P1/P2 when useful.

Do not broaden the scope to unrelated components/modules.

## 3. Approval Gate

After creating/updating `REFACTOR_PLAN.md`, STOP.

Implementation is forbidden until the user explicitly approves the plan.

Do not treat creation or review of the plan as approval.

## 4. Implementation Phase

Only after explicit approval:

1. Re-read the approved component analysis, refactor plan, standards, and current source.
2. Implement only the approved scope.
3. Preserve routes, aliases, public behavior and contracts unless the approved plan explicitly changes them.
4. Keep UI state/validation in Livewire and business/domain logic in services.
5. Do not move transactions/business workflows into Livewire.
6. Enforce authorization on sensitive Livewire actions.
7. Reuse canonical shared UI components where appropriate.
8. Add/update focused Livewire/service/authorization tests.
9. Run targeted verification.
10. Update component analysis and module `INFORMATION.md` / `README.md` only when implemented behavior changed materially.
11. If the component owns a list, reset pagination and selection when filter scope changes.
12. Never introduce an unbounded `All` page-size option for production-capable data.
13. For destructive selected/all actions, use centered modal confirmation with explicit scope and loading/disabled state.
14. If Import/Export is applicable, reuse shared infrastructure and follow `.codex/tasks/create-import-export.md`.
15. Checkbox-enabled export must use the canonical contract: no selection exports all approved records; selected IDs export only selected records.
16. Successful operations that materially change visible data should provide explicit success feedback and a clean refresh/reload path.

## 5. Feature Change vs Refactor

If the user's request introduces new business behavior rather than only improving existing implementation, do not silently treat it as refactor.

Create a component-level change plan instead:

```text
docs/modules/<ModuleName>/livewire/<component-key>/CHANGE_PLAN.md
```

The change plan must identify all affected direct dependencies (Service, Model, permission, migration, event/job, tests, etc.) and use the same approval gate before implementation.

## 6. Admin UI Quality Gate

When the component renders Admin UI, verify applicable items before completion:

- visual hierarchy matches `ADMIN_UI_STANDARD.md`
- primary task remains dominant
- filter/search controls are useful, compact and resettable
- pagination is bounded and uses repository accent styling
- no accidental current-page-only export behavior
- selected count and selection scope are clear
- destructive actions use modal confirmation
- loading/disabled states prevent duplicate requests
- empty/error/success states are clear
- shared Import/Export behavior is consistent when present
- desktop/mobile smoke behavior is acceptable
- no DB query/business logic leaks into Blade

## 7. Rules

- Follow `.codex/standards/MODULE_STANDARD.md`.
- Follow `.codex/standards/ADMIN_UI_STANDARD.md`.
- Never modify unrelated modules/components.
- Do not hide new business behavior inside a refactor.
- Keep changes reversible and reviewable.
- Do not rewrite applied migrations just to simplify the refactor.
- If a newly discovered issue materially expands scope, update the plan and obtain approval before proceeding.
- Passing PHP tests is not sufficient for UI completion; manually verify the rendered interaction when UI behavior changed.

## Final Response

Planning phase: report the generated/updated `REFACTOR_PLAN.md` or `CHANGE_PLAN.md` and state that implementation is waiting for approval.

Implementation phase: report files changed, tests/verification, documentation updates, and remaining risks.
