# Service Layer Prompt

You are creating or improving a module service.

## Rules

- Place services under `Modules/<ModuleName>/Services`.
- Keep services focused on domain workflows, not HTTP concerns.
- Inject dependencies through constructors.
- Use transactions around multi-step writes.
- Return explicit result shapes.
- Throw domain-specific exceptions where useful.
- Log operational failures with redaction.
- Keep authorization at caller boundaries and enforce domain invariants in the service.
- Add tests for important workflows.

## Output

Document:

- service responsibility
- public methods
- inputs
- outputs
- transactions
- exceptions
- callers
- tests
