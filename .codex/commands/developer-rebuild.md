# Command: @laravel-developer rebuild <ModuleName>

## Agent Used

`@laravel-developer`

## Required Input

```text
@laravel-developer rebuild <ModuleName>
```

## Required Files To Read

- global bootstrap files
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `composer.json`
- `docs/modules/<ModuleName>/ANALYSIS.md`
- `docs/modules/<ModuleName>/REFACTOR_PLAN.md`
- `docs/modules/<ModuleName>/REBUILD_SPEC.md`
- `docs/modules/<ModuleName>/INFORMATION.md`
- target module source files

## Steps To Execute

1. Read rebuild spec and compatibility requirements.
2. Inspect current routes, controllers, Livewire, views, services, models, migrations, imports, and exports.
3. Implement the rebuild inside the target module.
4. Preserve existing data unless migrations explicitly document changes.
5. Update tests where practical.
6. Update module docs.
7. Run focused verification.

## Output Files

- changed module source files
- tests when needed
- updated module docs

## Safety Rules

- Do not modify unrelated modules.
- Do not create a service provider unless project bootstrap requires it.
- Do not modify `composer.json` unless absolutely required.
- Do not hardcode paths.

## Example

```text
@laravel-developer rebuild Category
```
