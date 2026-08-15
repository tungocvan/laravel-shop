# Task: /refactor <ModuleName>

Refactor an existing module safely from verified analysis and documented intent.

## Required Reading

Before planning or writing code, read:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `.codex/tasks/create-import-export.md` when list data/import/export is in scope
- `.codex/prompts/import-export.md` when import/export is in scope
- `ROADMAP.md`
- `docs/modules/<ModuleName>/ANALYSIS.md`
- `docs/modules/<ModuleName>/INFORMATION.md`
- `docs/modules/<ModuleName>/README.md`

If `ANALYSIS.md` does not exist or is materially stale relative to source, run/update the `/analyze <ModuleName>` workflow first.

## Phase 1 — Verify Current State

1. Confirm the target module exists.
2. Re-check source files directly affected by the proposed refactor; documentation is context, not a substitute for current source.
3. Identify the exact P0/P1/P2 findings being addressed.
4. Identify compatibility constraints: routes, permissions, Livewire aliases, database schema, storage paths, imports/exports, public contracts and cross-module dependencies.
5. For Admin list/workspace screens, compare current UI against `.codex/standards/ADMIN_UI_STANDARD.md`, especially search/filter, bounded pagination, bulk selection/actions, destructive confirmation, loading states, empty states and Import/Export behavior.

## Phase 2 — Refactor Plan

Create or update:

`docs/modules/<ModuleName>/REFACTOR_PLAN.md`

The plan must include:

- Refactor goal and scope.
- Findings/evidence from `ANALYSIS.md` being addressed.
- P0/P1/P2 work items.
- Files to change.
- Behavior/contracts that must remain compatible.
- Database/migration impact.
- Security and authorization impact.
- Transaction/data-integrity impact.
- Performance impact.
- Test strategy and acceptance criteria.
- Rollback/recovery notes for risky changes.
- Explicit non-goals.

For Admin list/workspace refactors, the plan must also state whether the following are applicable and what will change:

- keyword search
- domain filters and reset-filter behavior
- bounded page sizes and pagination visual treatment
- row selection and selected count
- bulk actions and confirmation modal behavior
- Import/Export and selected-row export contract
- success/loading/refresh feedback

Do not add these mechanically. If one is not applicable, state why.

## Approval Gate

After `REFACTOR_PLAN.md` is generated or materially changed, STOP.

Do not modify application source code until the user explicitly approves the plan or explicitly requests implementation from the approved plan.

## Phase 3 — Implementation After Approval

After approval:

1. Re-read the approved `REFACTOR_PLAN.md`, standards, and directly affected source.
2. Implement only approved scope.
3. Preserve behavior and public contracts unless the approved plan explicitly documents a change and migration/compatibility strategy.
4. Keep domain logic in services and authorization at sensitive mutation boundaries.
5. Keep changes reversible and reviewable; avoid unrelated cleanup.
6. Add/update focused tests for changed behavior and regressions.
7. Run targeted verification and formatting.
8. Update `ANALYSIS.md` when findings are resolved or architecture changes materially.
9. Update `INFORMATION.md` and `README.md` to match implemented reality.
10. Update `REFACTOR_PLAN.md` with completion/verification status when useful.
11. When Admin UI is touched, verify the rendered screen against `ADMIN_UI_STANDARD.md`, not only PHP/tests.
12. For checkbox-enabled export, preserve the canonical behavior from `create-import-export.md`: no selection exports all approved records; selection exports only selected IDs.
13. Use centered modal confirmation for destructive bulk actions and explicit success feedback/refresh when dataset state changes materially.

## Admin UI Refactor Quality Gate

When Admin UI/list behavior is part of the refactor, do not declare completion until applicable checks pass:

- search/filter controls are useful and reset correctly
- changing filters/page size resets invalid pagination/selection state
- page sizes are bounded; no production `All`
- pagination matches repository accent styling and remains accessible
- row selection scope is clear
- destructive selected/all actions use modal confirmation
- loading/disabled state prevents duplicate mutations
- Import/Export uses shared infrastructure when applicable
- selected export behavior matches the canonical contract
- successful import/export can refresh the owning list cleanly
- empty/loading/error states are usable
- desktop/mobile smoke test is acceptable

## Rules

- Never modify unrelated modules unless an approved cross-module change is required and explicitly documented.
- Do not silently rename/remove routes, permissions, tables, columns, aliases, storage paths, APIs or other public contracts.
- Do not rewrite historical migrations merely to make code look cleaner.
- Do not treat UI refactor as permission to perform an unrelated global frontend migration.
- Any implementation that conflicts with repository reality must follow repository reality and document the deviation from older guidance.
- UI changes must follow `.codex/standards/ADMIN_UI_STANDARD.md`; passing backend tests alone is not sufficient evidence that a UI refactor is complete.
