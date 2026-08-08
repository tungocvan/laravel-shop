# Command: @architect plan <ModuleName>

## Agent Used

`@architect`

## Required Input

```text
@architect plan <ModuleName>
```

## Required Files To Read

- global bootstrap files
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `composer.json`
- target module docs and source files

## Steps To Execute

1. Confirm `ANALYSIS.md` exists or perform analysis first.
2. Identify refactor goals and non-goals.
3. Map public contracts that must remain stable.
4. Create implementation phases.
5. Define verification commands.

## Output Files

- `docs/modules/<ModuleName>/REFACTOR_PLAN.md`

## Safety Rules

- Planning only; do not write application code.
- Preserve route names, Livewire aliases, permissions, tables, and storage paths unless migration impact is documented.

## Example

```text
@architect plan Product
```
