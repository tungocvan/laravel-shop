# Command: @documentation-writer generate <ModuleName>

## Agent Used

`@documentation-writer`

## Required Input

```text
@documentation-writer generate <ModuleName>
```

## Required Files To Read

- global bootstrap files
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `composer.json`
- target module docs and source files

## Steps To Execute

1. Read module analysis and source files.
2. Summarize module purpose and ownership.
3. Document routes, UI, services, models, migrations, imports, exports, authorization, validation, storage, queues, cache, and verification.
4. Update developer-facing module README.

## Output Files

- `docs/modules/<ModuleName>/INFORMATION.md`
- `Modules/<ModuleName>/README.md`

## Safety Rules

- Do not invent behavior.
- Do not expose secrets or personal data.
- Mark inferred behavior as inferred.

## Example

```text
@documentation-writer generate Category
```
