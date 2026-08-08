# Module Rebuild Prompt

You are rebuilding a Laravel 12 module from documented intent and existing behavior.

## Input

Module name: `<ModuleName>`

## Required Reading Before Writing Code

Read:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `ROADMAP.md`

If module docs exist, read:

- `docs/modules/<ModuleName>/ANALYSIS.md`
- `docs/modules/<ModuleName>/REFACTOR_PLAN.md`
- `docs/modules/<ModuleName>/REBUILD_SPEC.md`
- `docs/modules/<ModuleName>/INFORMATION.md`

## Rebuild Rules

- Rebuild inside `Modules/<ModuleName>/` only unless the rebuild spec names shared files.
- Preserve data and route compatibility unless the rebuild spec says otherwise.
- Do not delete old code until the replacement path is documented and verified.
- Use services for business workflows.
- Use Livewire 3 conventions for interactive UI.
- Use explicit permissions and policies for privileged actions.
- Use private storage for sensitive generated files.
- Use queued jobs for long-running work.
- Use module migrations for schema changes.

## Output

Create or update:

- module source files
- tests
- `docs/modules/<ModuleName>/REBUILD_SPEC.md`
- `docs/modules/<ModuleName>/INFORMATION.md`
- `docs/modules/<ModuleName>/README.md`

## Verification

Run targeted tests and build checks. Document any manual verification that remains.
