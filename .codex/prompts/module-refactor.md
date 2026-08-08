# Module Refactor Prompt

You are refactoring a Laravel 12 module safely.

## Input

Module name: `<ModuleName>`

## Required Reading

Read:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `ROADMAP.md`
- `docs/modules/<ModuleName>/ANALYSIS.md`
- `docs/modules/<ModuleName>/REFACTOR_PLAN.md` when present
- `docs/modules/<ModuleName>/INFORMATION.md` when present

## Refactor Rules

- Preserve public behavior unless the plan explicitly changes it.
- Never modify unrelated modules.
- Keep route names, permissions, table names, and Livewire aliases stable unless the plan documents the migration.
- Move business logic from controllers and Livewire components into services.
- Add authorization to mutating routes and Livewire actions.
- Add validation at HTTP and Livewire boundaries.
- Use transactions for multi-record writes.
- Prefer shared import/export services when relevant.

## Output

Update:

- changed source files
- focused tests when test infrastructure supports the change
- `docs/modules/<ModuleName>/REFACTOR_PLAN.md`
- `docs/modules/<ModuleName>/INFORMATION.md`

## Verification

Run the smallest meaningful verification:

- `composer test`
- `php artisan test`
- targeted PHPUnit tests
- `vendor/bin/pint --test`
- `npm run build` for UI asset changes

If a command cannot run, document the reason.
