# INAFO Pharma Implementation Roadmap

## Purpose

This roadmap converts the scope in `docs/INAFO_PROJECT_ANALYSIS.md` into an implementation sequence based on the current repository.

Assessment date: 2026-06-14

Repository snapshot:

- 17 modules: Account, Admin, Admission, Auth, Category, Chat, Order, Partner, Pharma, Post, Product, Role, Shared, System, Template, User, and Website.
- Approximately 361 Livewire classes, 78 service classes, 80 model classes, and 33 route files.
- Laravel 12 and Livewire 3 are installed.
- The installed UI stack is Bootstrap 5.3 and AdminLTE 4 RC, not Bootstrap 4.6 and AdminLTE 3 as stated in the analysis brief.
- The automated test suite contains only the default example unit and feature tests.

## Scoring

| Dimension | Low | Medium | High | Critical |
|---|---|---|---|---|
| Complexity | Local change, 1-2 days | Several classes, 3-5 days | Cross-module, 1-2 weeks | Architectural migration, more than 2 weeks |
| Risk | Small, reversible behavior change | Some regression potential | Data, authorization, or broad workflow risk | Security exposure or irreversible data loss |
| Impact | Local maintainability | One module or workflow | Multiple modules or core business flow | Entire system, sensitive data, or production availability |

Priorities:

- **P0 Critical:** security, data-loss, secret-exposure, and production-control risks.
- **P1 Important:** correctness, maintainability, performance, testability, and module integrity.
- **P2 Nice to have:** cleanup, developer experience, observability, and non-blocking optimization.

## P0 Critical

| ID | Task | Complexity | Risk | Impact | Dependencies |
|---|---|---:|---:|---:|---|
| P0-01 | Enforce capability-level authorization on every System/Admin route and Livewire action that changes configuration, runs commands, downloads backups, imports SQL, truncates/drops tables, or restores databases. Use policies/permissions in addition to `auth:admin`. | High | Critical | Critical | None |
| P0-02 | Remove arbitrary command execution from web requests. Disable the free-form Artisan runner and shell-script editor/executor in production; replace required operations with an explicit command allowlist, fixed arguments, timeouts, audit logs, and confirmation gates. | High | Critical | Critical | P0-01 |
| P0-03 | Harden database backup/restore/destructive operations. Validate table names against schema metadata, validate selected backup paths server-side, use Symfony Process argument arrays instead of shell command strings, avoid passwords in command lines, and always restore foreign-key checks in `finally` blocks. | High | Critical | Critical | P0-01 |
| P0-04 | Rotate and remove exposed/default secrets. Remove hard-coded MoMo credentials and default bridge secrets, audit `.env.bak`/response artifacts and Git history, keep backups outside public storage, and fail closed when required secrets are absent. | Medium | Critical | Critical | None |
| P0-05 | Add ownership and authorization checks to business actions and downloads. Cover orders/invoices, admission documents, account data, imports/exports, chat sessions, and all mutating Livewire methods; do not rely on hidden UI controls. | High | High | Critical | P0-01 |
| P0-06 | Build a P0 regression suite before broad refactoring. Test admin guard separation, denied command/database actions, path traversal, unauthorized record access, backup restore validation, checkout totals, and import rejection paths. | High | Medium | Critical | P0-01 through P0-05 |

### P0 Acceptance Criteria

- No browser-supplied string can select an arbitrary Artisan command, shell command, executable path, table identifier, or backup path.
- Sensitive actions require named permissions and are denied by default.
- Destructive actions require explicit confirmation and create an immutable audit record.
- Database credentials and payment/bridge secrets are not stored in source or emitted in process lists/logs.
- Security tests prove both allowed and denied behavior.

## P1 Important

| ID | Task | Complexity | Risk | Impact | Dependencies |
|---|---|---:|---:|---:|---|
| P1-01 | Define canonical module ownership and dependency rules. Admin should be a presentation shell; Product, Order, Post, Category, Account, and System should own their domain models/services. Document allowed cross-module dependencies and enforce them with architecture tests. | Critical | High | High | P0-06 |
| P1-02 | Consolidate duplicated implementations across Admin, Website, System, Order, Product, and Post. Remove parallel `Services/Services` trees and duplicate models/Livewire classes after callers are migrated to canonical modules. | Critical | High | High | P1-01 |
| P1-03 | Reconcile the declared and installed frontend stack. Choose either the installed Bootstrap 5/AdminLTE 4 path or intentionally downgrade; then remove mixed-version assets, APIs, and templates. Avoid building new UI work on the current mismatch. | High | High | High | P0 work can run in parallel |
| P1-04 | Standardize validation and authorization boundaries. Use Form Requests for controllers, consistent Livewire `rules()`/validated DTOs for components, strict upload MIME/size rules, enum/state validation, and service-level invariants for non-HTTP callers. | High | Medium | High | P0-05 |
| P1-05 | Unify import/export on `Modules/Shared/Services/ImportExport`. Define one contract for header mapping, normalization, row validation, transactions, duplicate detection, error reports, storage, and cleanup; migrate Account, Admission, Pharma, Product, Post, Role, and User flows. | Critical | High | High | P0-04, P0-06 |
| P1-06 | Make large exports/imports bounded and queueable. Replace full `get()` and `paginate(999999)` paths with chunking/lazy iteration, queued jobs, progress state, private temporary files, and retention cleanup. | High | Medium | High | P1-05 |
| P1-07 | Profile and fix query hotspots. Add query-count tests and eager loading for list/detail screens, paginate chat and nested data, cache stable catalogs/settings, and stop querying permissions directly from Blade views. | High | Medium | High | P0-06 |
| P1-08 | Repair migration hygiene. Rename malformed negative-year migrations, verify deterministic ordering and fresh-install behavior, eliminate duplicate table ownership, and add migration smoke tests for SQLite limitations and the production MySQL path. | High | High | High | P1-01 |
| P1-09 | Make destructive and multi-record operations transactional. Cover checkout/order creation, affiliate commission updates, account imports, admission status/document generation, and Pharma imports; add idempotency where requests/jobs may retry. | High | High | High | P0-06 |
| P1-10 | Establish meaningful automated coverage and CI. Include module route boot tests, policies, Livewire CRUD, service transactions, import fixtures, checkout/payment callbacks, migrations, static analysis, formatting, and frontend build checks. | Critical | Medium | Critical | Begins with P0-06; continuous |
| P1-11 | Add module manifests and disable-by-default rules for unfinished modules. Validate module type, dependencies, routes, migrations, and Livewire aliases during CI instead of silently relying on discovery fallbacks. | Medium | Medium | Medium | P1-01 |
| P1-12 | Normalize error handling and operational logging. Do not return raw exception text to users; add correlation IDs, structured logs, domain exceptions, retry policy, and redaction for credentials and personal data. | Medium | Medium | High | P0-04 |

### P1 Acceptance Criteria

- Each business concept has one canonical model and service owner.
- A fresh database migration and module boot test succeed in CI.
- Import/export jobs handle production-sized files without loading entire datasets into memory.
- High-traffic pages have query-count budgets and no known N+1 regressions.
- Critical workflows are transactionally consistent and retry-safe.
- Backend tests, static checks, and frontend builds gate merges.

## P2 Nice To Have

| ID | Task | Complexity | Risk | Impact | Dependencies |
|---|---|---:|---:|---:|---|
| P2-01 | Remove stale artifacts: `web copy.php`, Zone.Identifier files, placeholder classes/views, obsolete commented routes, duplicate seeders, root response dumps, and confirmed unused files. | Medium | Low | Medium | P1-02, route tests |
| P2-02 | Improve observability with queue/import duration metrics, failed-job alerts, slow-query logging, payment callback monitoring, backup verification status, and dashboard health checks. | Medium | Low | Medium | P1-10, P1-12 |
| P2-03 | Introduce cache policy for menus, settings, location/catalog data, and homepage content with explicit invalidation from write services. | Medium | Medium | Medium | P1-02, P1-07 |
| P2-04 | Improve admin UX for long-running work: queued progress, cancellation, downloadable error reports, safer confirmations, and clear permission-denied states. | Medium | Low | Medium | P1-05, P1-06 |
| P2-05 | Generate and maintain architecture catalogs for modules, routes, Livewire components, services, models, relationships, and imports/exports as CI artifacts. | Medium | Low | Medium | P1-01, P1-10 |
| P2-06 | Add dependency and security maintenance automation for Composer/NPM, including lockfile audit, scheduled update PRs, and an explicit policy for release-candidate packages such as AdminLTE 4 RC. | Low | Low | Medium | P1-03, P1-10 |

## Implementation Order

### Phase 0: Containment

1. **P0-04** Rotate exposed/default secrets and remove sensitive artifacts.
2. **P0-01** Add explicit permissions to privileged routes and Livewire actions.
3. **P0-02** Disable arbitrary Artisan and shell execution.
4. **P0-03** Harden database backup, restore, download, truncate, and drop operations.
5. **P0-05** Close record-level authorization gaps.
6. **P0-06** Lock the remediated behavior with security regression tests.

Release gate: do not expose System administration tools in production until Phase 0 passes.

### Phase 1: Establish Guardrails

7. **P1-10** Stand up CI and expand the test harness continuously.
8. **P1-12** Normalize safe error handling and structured logging.
9. **P1-01** Publish canonical module ownership and dependency rules.
10. **P1-11** Add complete module manifests and boot validation.
11. **P1-03** Decide and standardize the frontend framework versions.

### Phase 2: Correctness and Data Integrity

12. **P1-04** Standardize input validation and service invariants.
13. **P1-09** Add transactions and idempotency to critical workflows.
14. **P1-08** Repair migration ordering and fresh-install reliability.
15. **P1-05** Consolidate import/export contracts.
16. **P1-06** Queue and chunk large import/export workloads.

### Phase 3: Architecture and Performance

17. **P1-02** Migrate duplicate Admin/Website/System implementations to canonical modules.
18. **P1-07** Profile and fix query, N+1, and unbounded-loading hotspots.
19. **P2-03** Add measured caching with explicit invalidation.

### Phase 4: Cleanup and Operations

20. **P2-01** Remove confirmed dead and malformed artifacts.
21. **P2-02** Add production health and performance telemetry.
22. **P2-04** Improve long-running operation UX.
23. **P2-05** Generate architecture catalogs in CI.
24. **P2-06** Automate dependency/security maintenance.

## First Delivery Slice

The first implementation milestone should be small enough to review but meaningful enough to reduce immediate exposure:

1. Disable the System Artisan runner and shell-script manager outside local development.
2. Require a dedicated `system.manage` permission for System routes and Livewire actions.
3. Require dedicated `database.backup`, `database.restore`, and `database.destroy` permissions.
4. Replace client-provided backup paths with server-issued opaque identifiers.
5. Validate table names against the live schema and replace shell command strings with Process argument arrays.
6. Add denied-access and path/identifier tampering tests.

## Known Verification Constraints

- The current shell does not provide a `php` executable, so `artisan route:list`, migrations, and PHPUnit could not be executed during roadmap preparation.
- Git inspection is affected by mapped-drive ownership checks; secret-history review remains an explicit P0 task.
- The source analysis document currently contains the requested analysis scope rather than a completed A-J analysis report. This roadmap therefore uses direct repository inspection as its evidence base.
