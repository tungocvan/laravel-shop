# Task: /rebuild <ModuleName>

Rebuild an existing module safely from verified analysis and approved documented intent.

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

Read when present:

- `docs/modules/<ModuleName>/REFACTOR_PLAN.md`
- `docs/modules/<ModuleName>/REBUILD_SPEC.md`

If `ANALYSIS.md` does not exist or is materially stale relative to source, run/update `/analyze <ModuleName>` first.

## Phase 1 — Verify Current State

1. Confirm the target module exists.
2. Re-check the source needed to understand current behavior and compatibility boundaries.
3. Identify why rebuild is preferred over minor/major refactor.
4. Identify business behavior that must be preserved, intentionally changed, or removed.
5. Identify compatibility constraints: routes, permissions, Livewire aliases, tables/columns, imports/exports, storage paths, public views, external contracts and cross-module dependencies.

## Phase 2 — Rebuild Specification

If `REBUILD_SPEC.md` is missing, stale, or not explicitly approved, create/update:

`docs/modules/<ModuleName>/REBUILD_SPEC.md`

Base it on `ANALYSIS.md`, `INFORMATION.md`, `README.md`, current source, applicable `REFACTOR_PLAN.md`, repository standards and `ROADMAP.md`.

The specification must include:

- Goal and rebuild scope.
- Findings/evidence that justify rebuild.
- Business rules to preserve.
- Intentional behavior changes.
- Target architecture.
- Database/migration/data-conversion design.
- Model and service design.
- Livewire/UI design.
- Import/export design when applicable.
- Permissions and authorization.
- Transactions, concurrency and data integrity.
- Cross-module/shared dependencies.
- Compatibility/migration strategy.
- Rollout strategy keeping old behavior available when necessary.
- Rollback/recovery strategy.
- Test strategy and acceptance criteria.
- Deployment/verification checklist.
- Files expected to change.
- Explicit non-goals and unresolved decisions.

## Approval Gate

After `REBUILD_SPEC.md` is generated or materially changed, STOP.

Do not modify application source code, schema, storage or public contracts until the user explicitly approves the specification or explicitly requests implementation from the approved specification.

## Phase 3 — Implementation After Approval

After approval:

1. Re-read the approved `REBUILD_SPEC.md`, standards and directly affected source.
2. Implement only the approved rebuild scope inside the module boundary, except explicitly approved cross-module/shared changes.
3. Preserve old behavior/contracts until replacement behavior is verified when compatibility requires staged rollout.
4. Never perform destructive database/file rewrites without the approved migration and rollback strategy.
5. Follow `Modules\ModuleServiceProvider` discovery conventions; do not introduce nwidart/module.json/manual providers unless repository reality requires them.
6. Keep business workflows in services, sensitive mutations explicitly authorized, and multi-record writes transactional where required.
7. Add/update tests covering critical old behavior, target behavior, authorization, migrations/data conversion, and regression risks.
8. Run targeted verification, formatting and applicable frontend build checks.
9. Update `ANALYSIS.md`, `INFORMATION.md`, `README.md`, and `REBUILD_SPEC.md` to reflect implemented reality and verification status.

## Rules

- Do not modify unrelated modules.
- Do not remove or rename public contracts silently.
- Do not rewrite historical migrations as a shortcut.
- Do not delete old data/code paths until replacement behavior and rollback strategy are verified.
- Do not treat rebuild as permission for unrelated global architecture or frontend migrations.
- When a standard conflicts with actual repository infrastructure, repository reality wins and the deviation must be documented.
