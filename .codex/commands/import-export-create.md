# Command: @import-export-specialist create <ModuleName>

## Agent Used

`@import-export-specialist`

## Required Input

```text
@import-export-specialist create <ModuleName>
```

## Required Files To Read

- global bootstrap files
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `composer.json`
- `Modules/Shared/Services/ImportExport`
- target module model, migration, service, import, export, Livewire, route, and docs

## Required Inputs Before Code

- Excel sample file or written column contract.
- Migration or table schema.
- Model.
- Mapping preference: header mapping or Excel column mapping.

## Steps To Execute

1. Define import/export contract.
2. Choose one-sheet or multi-sheet strategy.
3. Create thin orchestrator service.
4. Create import mapper, normalizer, validator, and import class.
5. Create export query, mapper, template builder, and export class.
6. Add storage, report, cleanup, logging, and queue strategy.
7. Update module documentation.

## Output Files

- `Modules/<ModuleName>/Import/...`
- `Modules/<ModuleName>/Export/...`
- `Modules/<ModuleName>/Services/ImportExport.php`
- `docs/modules/<ModuleName>/INFORMATION.md`

## Safety Rules

- Validate file type, MIME, size, headers, and rows.
- Never trust uploaded file names or client paths.
- Store sensitive files privately.
- Avoid undefined index errors.
- Normalize date, number, currency, and empty cells.

## Example

```text
@import-export-specialist create Pharma
```
