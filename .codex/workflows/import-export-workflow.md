# Workflow: Import Export

Use this workflow to create, refactor, or debug module import/export features.

## Flow

```text
@import-export-specialist create <ModuleName>
@livewire-developer fix-ui <ModuleName>
@reviewer review <ModuleName>
```

## Expected Outputs

- Import classes
- Export classes
- Thin orchestrator service
- UI integration when needed
- Updated `INFORMATION.md`
- Review document

## Rules

- Require an Excel sample or written column contract before implementation.
- Inspect migration and model before mapping fields.
- Use shared import/export foundation when possible.
- Store sensitive files privately.
- Chunk, stream, lazy-load, or queue large work.

## Done When

- Headers, validation, normalization, duplicate handling, storage, reports, and cleanup are documented.
- Import/export errors are debuggable without exposing sensitive data.
- Review is complete.
