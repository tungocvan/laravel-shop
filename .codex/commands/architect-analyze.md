# Command: @architect analyze <ModuleName>

## Agent Used

`@architect`

## Required Input

```text
@architect analyze <ModuleName>
```

Example:

```text
@architect analyze Category
```

## Required Files To Read

- `docs/CODEX_BOOTSTRAP.md`
- `docs/AI_PROJECT_CONTEXT.md`
- `docs/PROJECT_BOOTSTRAP.md`
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `composer.json`
- existing docs under `docs/modules/<ModuleName>/` when present
- target module files under `Modules/<ModuleName>/`

## Steps To Execute

1. Read required global files.
2. Read existing module docs.
3. Inspect the module by this flow:

```text
Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared UI Components -> Services -> Import -> Export -> Shared Services -> Model -> Migration -> Database
```

4. Identify architecture, security, performance, data, UI, and documentation findings.
5. Generate module docs.

## Output Files

- `docs/modules/<ModuleName>/ANALYSIS.md`
- `docs/modules/<ModuleName>/REFACTOR_PLAN.md`
- `docs/modules/<ModuleName>/REBUILD_SPEC.md`

## Safety Rules

- Do not modify application code.
- Do not modify unrelated module docs.
- Mark inferred behavior clearly.

## Example Output

```text
Generated Category analysis, refactor plan, and rebuild spec.
Key risk: Category table actions need explicit permission checks.
```
