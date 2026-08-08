# Workflow: Review

Use this workflow for final module quality, security, and performance review.

## Flow

```text
@security-reviewer review <ModuleName>
@performance-reviewer review <ModuleName>
@reviewer final-check <ModuleName>
```

## Expected Outputs

- `docs/modules/<ModuleName>/SECURITY_REVIEW.md`
- `docs/modules/<ModuleName>/PERFORMANCE_REVIEW.md`
- `docs/modules/<ModuleName>/REVIEW.md`

## Rules

- Findings first, ordered by severity.
- Cite files and behavior.
- Do not modify code unless the user explicitly asks.
- Keep scope limited to the target module and directly referenced shared files.

## Done When

- Security risks are documented.
- Performance risks are documented.
- Final review summarizes blocking issues, residual risk, and suggested fixes.
