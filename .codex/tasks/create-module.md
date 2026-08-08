# Task: /create-module <ModuleName>

Create a new Laravel module that fits this repository.

## Required Reading

Before planning or writing code, read:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `.codex/prompts/import-export.md` when import/export is in scope
- `ROADMAP.md`

## Phase 1 — Resolve Scope

1. Confirm that `Modules/<ModuleName>/` does not already exist.
2. Read the business requirement/specification supplied for the new module.
3. Identify required features, routes, permissions, models, tables, services, Livewire components, files, imports/exports, events/jobs, and external/cross-module dependencies.
4. Mark unclear or risky business decisions explicitly. Do not invent domain rules silently.

## Phase 2 — Create Implementation Plan

Before application code, create or update:

`docs/modules/<ModuleName>/CREATE_PLAN.md`

The plan must include:

- Purpose and business scope.
- Proposed module structure.
- Routes and permissions.
- Database/model design.
- Service boundaries and transaction rules.
- Livewire/UI design.
- Import/export design when applicable.
- Cross-module dependencies.
- Security and data-integrity considerations.
- Tests and acceptance criteria.
- Files to create/change.
- Risks and unresolved questions.

## Approval Gate

After `CREATE_PLAN.md` is generated, STOP.

Do not create application code until the user explicitly approves the plan or explicitly requests implementation based on the approved plan.

## Phase 3 — Implementation After Approval

After approval:

1. Re-read the approved `CREATE_PLAN.md` and required standards.
2. Create `Modules/<ModuleName>/config/module.php` with `type`, `enabled`, and a concise description when consistent with repository discovery rules.
3. Create only the folders/classes required by the approved plan.
4. Add routes, controllers, Page Blades, Livewire components, services, models, migrations, policies/permissions, imports/exports, events/jobs only when required.
5. Follow `Modules\ModuleServiceProvider` discovery conventions; do not add `module.json`, nwidart infrastructure, or manual provider registration unless current repository infrastructure requires it.
6. Keep business logic in services and sensitive mutations explicitly authorized.
7. Add focused tests and run formatting/targeted verification.
8. Create/update module documentation under `docs/modules/<ModuleName>/` so it reflects implemented reality.

## Rules

- Use namespace `Modules\<ModuleName>`.
- Preserve repository folder casing/conventions; current module conventions favor lower-case `config`, `routes`, `resources`, and `database` where used.
- Do not modify unrelated modules.
- Keep the first implementation minimal, coherent, production-safe, and reviewable.
- Do not introduce a new framework or module system that conflicts with the repository.
- Do not implement unresolved high-risk assumptions without explicit approval.
