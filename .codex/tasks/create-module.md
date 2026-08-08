# Task: /create-module <ModuleName>

Create a new Laravel module that fits this repository.

## Required Reading
Read before writing code:
- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/prompts/laravel-admin-ui.md`
- `.codex/prompts/import-export.md`
- `ROADMAP.md`

## Steps

1. Confirm no existing module with the same name exists.
2. Create `Modules/<ModuleName>/config/module.php` with `type`, `enabled`, and a short description.
3. Create standard folders needed for the requested feature.
4. Add routes, controllers, Livewire components, services, models, migrations, policies, imports, or exports only when required.
5. Generate module docs under `docs/modules/<ModuleName>/`.
6. Run formatting and targeted tests when code is created.

## Rules

- Use namespace `Modules\<ModuleName>`.
- Use lower-case folder names where the repository already does: `config`, `routes`, `resources`, `database`.
- Do not register the module manually unless the provider cannot discover it.
- Keep the first version minimal and coherent.
