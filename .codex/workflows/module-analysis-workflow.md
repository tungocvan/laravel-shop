# Workflow: Module Analysis

Use this workflow to understand a module before refactoring or rebuilding it.

## Flow

```text
@architect analyze <ModuleName>
@database-architect analyze <ModuleName>
@service-layer-specialist refactor-plan <ModuleName>
@documentation-writer generate <ModuleName>
```

## Expected Outputs

- `docs/modules/<ModuleName>/ANALYSIS.md`
- `docs/modules/<ModuleName>/REFACTOR_PLAN.md`
- `docs/modules/<ModuleName>/REBUILD_SPEC.md`
- `docs/modules/<ModuleName>/INFORMATION.md`
- `Modules/<ModuleName>/README.md`

## Rules

- Documentation and planning only unless the user asks for code.
- Follow the module analysis flow.
- Keep scope limited to the target module and directly referenced shared files.

## Done When

- Module ownership is clear.
- Routes, UI, services, models, migrations, imports, and exports are documented.
- Risks and next steps are prioritized.
