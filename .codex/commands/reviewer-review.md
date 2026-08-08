# Command: @reviewer review <ModuleName>

## Agent Used

`@reviewer`

## Required Input

```text
@reviewer review <ModuleName>
```

## Required Files To Read

- global bootstrap files
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `composer.json`
- module docs
- changed module source files
- tests
- security and performance review docs when present

## Steps To Execute

1. Review architecture consistency.
2. Review service layer quality.
3. Review Livewire simplicity.
4. Review validation and authorization.
5. Review import/export behavior.
6. Review database safety.
7. Review UI consistency.
8. Review documentation completeness.
9. Generate findings ordered by severity.

## Output Files

- `docs/modules/<ModuleName>/REVIEW.md`

## Safety Rules

- Findings first.
- Use file references.
- Do not modify code unless explicitly requested.
- Do not review unrelated modules.

## Example

```text
@reviewer review User
```
