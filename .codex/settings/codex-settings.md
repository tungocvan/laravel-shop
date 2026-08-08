# Codex Workspace Settings

## Default Behavior

- Read bootstrap docs before code changes.
- Prefer repository conventions over new abstractions.
- Keep edits scoped to the requested module.
- Generate markdown documentation for module analysis and rebuild work.
- Be explicit about assumptions and verification gaps.

## Preferred Commands

Use these commands when available:

```bash
composer test
php artisan test
vendor/bin/pint --test
npm run build
```

Use targeted tests first for narrow work.

## Documentation Paths

Module docs live under:

```text
docs/modules/<ModuleName>/
```

Workspace instructions live under:

```text
.codex/
```

## Safety Defaults

- Do not modify unrelated modules.
- Do not overwrite files before reading them.
- Do not introduce arbitrary command execution.
- Do not trust browser-provided filesystem or database identifiers.
- Do not expose secrets or raw exceptions.
- Do not perform destructive migrations without a documented rollback.

## Commit Portability

This workspace must remain plain text and account-neutral. Do not include local API keys, personal paths, account identifiers, editor state, or generated machine-specific cache files.
