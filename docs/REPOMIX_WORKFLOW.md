# Repomix — safe repository packs

## Purpose

Repomix exports **source context**, not a backup, deployment artifact or substitute for inspecting GitHub `main`. Always read `docs/GITHUB_COLLABORATION_WORKFLOW.md` and module handoff before changing code. Do not upload a pack without reviewing it for credentials, tokens, customer data and internal information.

## Safety rules

- Run from the repository root on a development machine, **not Production**.
- Default configuration `repomix.config.json` keeps the security check enabled and excludes environment files, private keys, runtime data, logs, generated bundles and common binaries. This is **defense in depth**, not a guarantee that every secret will be detected.
- Never use `--no-security-check`, `--include` with secret paths, or `--ignore` to bypass the defaults. Review generated files before sharing.
- Existing `repomix-output.xml` is untracked and must not be removed, replaced or uploaded automatically. Commands below write **different filenames**. Confirm a target file does not already exist before running.
- Never add generated packs, `.env*` or production-only untracked files to Git.

## One-off scoped packs

Run from the repository root. Each example explicitly names its output. Use **only the pack needed** for the task. The `--include` flag narrows scope; `ignore.customPatterns` continues to exclude sensitive files.

```bash
# Architecture and workflow documentation (source-only)
npx repomix --include "app/**,bootstrap/app.php,routes/**,docs/GITHUB_COLLABORATION_WORKFLOW.md,docs/HTML_DEMO_STANDARD.md,docs/HTML_DEMO_BLADE_WORKFLOW.md" --output repomix-output-architecture.xml

# Demo source, UI and handoff
npx repomix --include "Modules/Demo/**,resources/views/demo/**,docs/modules/Demo/**,docs/HTML_DEMO_STANDARD.md,docs/HTML_DEMO_BLADE_WORKFLOW.md" --output repomix-output-demo.xml

# Finance ownership
npx repomix --include "Modules/Finance/**,docs/modules/Finance/**" --output repomix-output-finance.xml

# Pharma ownership
npx repomix --include "Modules/Pharma/**,docs/modules/Pharma/**" --output repomix-output-pharma.xml
```

A large demo asset can still create a huge pack. If needed, narrow the include patterns to the specific Controller, Blade, JS and tests rather than disabling security. For cross-module work, inspect the actual dependency boundary and add only the necessary files.

## Validation and Git hygiene

```bash
node -e "const c=require('./repomix.config.json'); if(c.security?.enableSecurityCheck!==true||c.output?.style!=='xml') process.exit(1); console.log('Repomix config OK')"
git check-ignore -v repomix-output.xml repomix-output-demo.xml
git status --short
```

Do not claim a pack is safe solely because the Repomix security scanner passed. Keep generated XML local, inspect the file before any sharing, and use the GitHub connector to read live source where possible.

## Regression scope

This change affects development tooling only. Focused configuration validation and Git ignore checks are sufficient; Laravel module regression and UI acceptance are **NOT APPLICABLE** unless a subsequent task changes runtime code.
