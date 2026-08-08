# Task: /rebuild <ModuleName>

Rebuild a module safely from documented intent.

## Required Reading

Read before writing code:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex\prompts\laravel-admin-ui.md`
- `.codex\prompts\import-export.md`
- `ROADMAP.md`

If present, read:

- `docs/modules/<ModuleName>/ANALYSIS.md`
- `docs/modules/<ModuleName>/REFACTOR_PLAN.md`
- `docs/modules/<ModuleName>/REBUILD_SPEC.md`
- `docs/modules/<ModuleName>/INFORMATION.md`


## Steps

1. Confirm the rebuild scope from `REBUILD_SPEC.md`.
2. Identify compatibility constraints: routes, permissions, Livewire aliases, tables, imports, exports, storage paths, and public views.
3. Implement the rebuild inside the module boundary.
4. Keep old behavior available until replacement behavior is verified.
5. Add or update tests.
6. Update docs.
7. Run verification.

## Rules

- Do not perform destructive rewrites without documented rollback.
- Do not modify unrelated modules.
- Keep generated files aligned with `Modules\ModuleServiceProvider`.
