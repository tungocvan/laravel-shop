# Task: /refactor <ModuleName>

Refactor an existing module safely from verified analysis and documented intent.

## Required Reading

Before planning or writing code, read:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
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

## Rules

- Never modify unrelated modules unless an approved cross-module change is required and explicitly documented.
- Do not silently rename/remove routes, permissions, tables, columns, aliases, storage paths, APIs or other public contracts.
- Do not rewrite historical migrations merely to make code look cleaner.
- Do not treat UI refactor as permission to perform an unrelated global frontend migration.
- Any implementation that conflicts with repository reality must follow repository reality and document the deviation from older guidance.
