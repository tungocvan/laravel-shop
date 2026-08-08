# Workflow: Module Rebuild

Use this workflow when a module has a rebuild spec and needs safe implementation.

## Flow

```text
@architect rebuild-spec <ModuleName>
@laravel-developer rebuild <ModuleName>
@livewire-developer refactor <ModuleName>
@reviewer review <ModuleName>
```

## Expected Outputs

- Updated `REBUILD_SPEC.md`
- Rebuilt module code
- Updated Livewire UI
- Updated module docs
- `docs/modules/<ModuleName>/REVIEW.md`

## Rules

- Read all required bootstrap and module docs before writing code.
- Preserve public contracts unless migration impact is documented.
- Do not modify unrelated modules.
- Run focused verification and record results.

## Done When

- Rebuild spec is satisfied.
- Review findings are resolved or documented.
- Verification is complete or limitations are documented.
