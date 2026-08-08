# System Rebuild Decision

Assessment date: 2026-06-22

Inputs reviewed:

- `docs/CODEX_BOOTSTRAP.md`
- `docs/AI_PROJECT_CONTEXT.md`
- `docs/PROJECT_BOOTSTRAP.md`
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `composer.json`
- `docs/modules/System/ANALYSIS.md`
- `docs/modules/System/REFACTOR_PLAN.md`
- `docs/modules/System/REBUILD_SPEC.md`
- `docs/modules/System/INFORMATION.md` was not found
- Actual source under `Modules/System`

## Source Of Truth

Actual source code in `Modules/System` matches the major findings in the existing module documentation. Where risk is assessed, source code is treated as authoritative.

## Decision Factors

### Keep As-Is

Rejected.

Reason: The current module exposes high-risk operations through broad `auth:admin` access, arbitrary Artisan execution, shell script execution, direct `.env` writes, and unsafe database backup/restore/drop/truncate operations.

### Partial Refactor

Rejected as the primary strategy.

Reason: Some stable pieces can be refactored in place, but the database and command execution workflows need more than small patches. A partial refactor would likely preserve unsafe assumptions around browser-provided table names, backup paths, command names, and shell scripts.

### Safe Rebuild

Accepted.

Reason: The module has useful structure that should be preserved: route/page shell, module manifest, Livewire component organization, settings form concepts, tab service concept, and admin layout integration. However, the privileged workflows must be rebuilt around explicit permissions, allowlists, validated Livewire state, service boundaries, private storage, safe process execution, and auditability.

### Rewrite From Scratch

Rejected for the whole module.

Reason: A full rewrite would discard useful module structure and increase cross-module risk. Only specific surfaces should be rewritten from scratch: `ArtisanList`, `ShScript`, unsafe database restore/import, and the unauthenticated API endpoint.

## Final Recommendation

Decision:
- Safe rebuild

Reason:

`Modules/System` is an operations shell with valid high-level responsibilities, but its current implementation is too risky for production. The module should be rebuilt in staged slices: P0 security containment first, P1 correctness and ownership cleanup second, and P2 cleanup/performance/documentation last. This preserves stable UI and module structure while replacing unsafe operational workflows.

Risk level:
- High

Suggested next step:

Start with the P0 containment slice from `ROADMAP.md` and `docs/modules/System/REFACTOR_PLAN.md`: protect/remove `/api/system`, add explicit permissions to System routes and Livewire mutating actions, disable arbitrary Artisan and shell execution in production, harden database backup/restore/destructive operations, and add denied-access/path-tampering regression tests.

## Needs Verification

- Whether `docs/modules/System/INFORMATION.md` is required before implementation starts.
- Whether `settings` should be canonically owned by System, Admin, Shared, or another support module.
- Whether an existing audit-log mechanism exists before adding any System-specific audit table.
- Whether any external client depends on `/api/system`.
- Whether placeholder components/pages are referenced by menus or dynamic configuration outside `Modules/System`.
