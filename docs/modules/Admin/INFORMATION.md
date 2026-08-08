# Admin Architecture Review Report

## 0.1 Implementation Update - 2026-06-26

Status: **Menu import/export service extraction implemented**

This update rewrites the import/export surface used by `Modules\Admin\Livewire\Menus\MenuTable`. It keeps the current JSON tree menu format because no Excel sample, Excel mapping, or confirmed A/B/C mapping was provided for menu data. The shared import/export panel currently supports flat `.xlsx/.csv` rows, while Admin menus are recursive JSON trees, so this slice intentionally uses a dedicated Admin menu service instead of forcing the tree format into the shared Excel panel.

Changed scope:

- `Modules/Admin/Services/MenuImportExportService.php`: added a dedicated service for menu JSON tree import, export, restore-default orchestration, validation, report generation, transactions, cache clearing, and logging.
- `Modules/Admin/Livewire/Menus/MenuTable.php`: removed JSON parsing, recursive persistence, export tree building, config-file writing, and restore import logic from Livewire; Livewire now authorizes, validates upload input, calls the service, dispatches UI notifications, and returns the download response.
- `Modules/Admin/resources/views/livewire/menus/menu-table.blade.php`: added an import report panel for total/success/skipped/error counts and row-level validation errors.
- `tests/Feature/Admin/MenuImportExportServiceTest.php`: added focused validation tests for invalid JSON and invalid menu tree payloads.

Important behavior decisions:

- Upload import mode remains `skip_duplicate` to avoid accidental overwrite or delete.
- Restore default menu uses `replace` internally, matching the existing restore intent, but now validates the full JSON tree before persistence and runs through a service transaction.
- Export no longer writes to `Modules/Admin/data/menus.json`; it only streams the downloaded JSON response. This avoids mutating source/config data during an export action.
- Excel import/export for menus remains **Needs verification** until a menu Excel template and mapping mode are confirmed.

Verification:

- PHP lint passed for the new service, rewritten Livewire component, and new test.
- `php artisan test --filter=MenuImportExportServiceTest` passed.
- `php artisan test --filter=Admin` passed.

## 0.2 Implementation Update - 2026-06-26

Status: **Menu Livewire service-boundary refactor implemented**

This update makes `Modules\Admin\Livewire\Menus\MenuTable` thinner and moves menu business workflows into a dedicated service.

Changed scope:

- `Modules/Admin/Services/MenuService.php`: added service-owned menu query, stats, delete, toggle, duplicate, bulk delete, bulk status, bulk permission, and drag/drop ordering workflows.
- `Modules/Admin/Livewire/Menus/MenuTable.php`: removed direct menu model queries, direct transactions, duplicate recursion, slug generation, and order recursion from the Livewire component.
- Mutating menu workflows now scope records through `Category::menu()` inside `MenuService`, reducing the risk that crafted Livewire payloads affect non-menu category records.
- Drag/drop order payloads are validated for structure, duplicate IDs, and invalid/non-menu IDs before persistence.

Remaining menu improvement opportunities:

- Split `MenuImportExportService` further into import/export/restore collaborators if the Excel workflow grows.
- Add database-backed feature tests for `MenuService` when the test environment has a working database driver.

Verification:

- PHP lint passed for `MenuService`, `MenuImportExportService`, and `MenuTable`.
- `php artisan test --filter=Admin` passed.

## 0. Implementation Update - 2026-06-22

Status: **P0 containment slice implemented**

This update does not complete the full Admin rebuild. It applies the first safe containment step from the rebuild decision: close unauthenticated/over-broad surfaces, add explicit authorization to active shell routes and mutating Livewire actions, and make browser-triggered database administration fail closed until a hardened ownership decision is made.

Changed scope:

- `Modules/Admin/routes/api.php`: removed the unauthenticated `GET /api/admin` stub by leaving the Admin API route file intentionally empty.
- `Modules/Admin/routes/web.php`: added named permission middleware to active Admin shell routes.
- `Modules/Admin/config/module.php`: added explicit Admin shell permissions for dashboard, menu, profile, theme, and header workflows.
- `Modules/Admin/Livewire/Menus/MenuTable.php`: added permission checks to mutating menu actions, including restore, delete, status changes, duplicate, bulk actions, ordering, import, and export.
- `Modules/Admin/Livewire/Menus/MenuForm.php`: added create/update permission checks before saving menus.
- `Modules/Admin/Livewire/ThemeSwitcher.php`: added theme update permission checks.
- `Modules/Admin/Livewire/Header/GeneralSettings.php`: added header update permission checks.
- `Modules/Admin/Livewire/Header/MenuManager.php`: added header update permission checks for save/delete actions.
- `Modules/Admin/Livewire/Profile/UserProfile.php`: added profile update permission checks before profile/password updates.
- `Modules/Admin/Livewire/Profile/UserAddress.php`: added profile update permission checks before address mutations.
- `Modules/Admin/Livewire/Database/TableList.php`: disabled backup/export/restore/truncate/drop actions from the Livewire browser surface by failing closed with HTTP 403.
- `Modules/Admin/resources/views/livewire/database/table-list.blade.php`: replaced the interactive database administration UI with a disabled safety notice.
- `Modules/Admin/Services/ProfileService.php`: corrected the namespace to `Modules\Admin\Services` to match Admin PSR-4 ownership.
- `tests/Feature/Admin/AdminRouteConfigurationTest.php`: added route tests for removed API exposure and named permission middleware.

Remaining Admin rebuild work:

- Database administration is still not rebuilt. `DatabaseService` remains unsafe and should either move to a hardened System module or be redesigned with strict permission, audit, allowlist, process, and secret-handling controls before any UI is re-enabled.
- Menu behavior still needs service extraction, transactional restore/import, structured validation, and cache invalidation tests.
- Profile address behavior still needs canonical ownership verification and transaction review.
- Domain pages, controllers, models, imports, and exports still need ownership migration to canonical modules.
- Existing environments must seed or otherwise create the new Admin permissions before non-super-admin users can access the protected routes/actions.

Verification:

- Added focused route configuration coverage for the P0 route containment slice.
- Full verification commands and results should be recorded in the implementation handoff after test execution.

## 1. Executive Summary

Current maturity scores:

| Dimension | Score |
|---|---:|
| Current module maturity | 34/100 |
| Maintainability | 35/100 |
| Security | 20/100 |
| Scalability | 38/100 |
| Performance | 42/100 |
| Testability | 30/100 |

Overall verdict: **Requires major redesign**

The Admin module is currently a mixed shell/domain/system module. Its target direction is correct: Admin should become a presentation shell with service-backed shell features, while domain behavior moves to canonical modules. The proposed refactor plan and rebuild spec are broadly sound, but implementation is not ready until several decisions are confirmed: API route removal vs protection, database administration ownership, canonical ownership for settings/menu/category/user address/domain screens, migration compatibility, and import/export mapping behavior.

Principal recommendation:

- Priority: P0
- Reason: Current Admin contains production-control and data-loss risks through unauthenticated API exposure and database administration flows.
- Expected benefit: Prevents implementation from expanding unsafe surfaces before containment.

Codex should not begin implementation yet. The module needs confirmation gates and a narrower first implementation slice.

## 2. Current State Assessment

### Architecture Quality

The architecture is inconsistent. Active routes are limited, but many controllers, page blades, Livewire components, services, models, imports, and exports exist for domain behavior that Admin should not own.

P0:

- `Modules/Admin/routes/api.php` exposes an unauthenticated admin API route.
- `Modules/Admin/Services/DatabaseService.php` contains backup, restore, drop, truncate, and full restore behavior with unsafe command and identifier handling.
- `Modules/Admin/Livewire/Database/TableList.php` exposes destructive actions.

P1:

- `Modules/Admin/Livewire/Menus/MenuTable.php` and `Modules/Admin/Livewire/Menus/MenuForm.php` bypass the service layer.
- Product, order, post, category, coupon, role, staff, affiliate, flash sale, banner, chat, footer, home, and settings behavior crosses module boundaries.
- Import/export is duplicated and not aligned with the shared foundation.

P2:

- `Modules/Admin/routes/web copy.php`, Zone.Identifier, placeholder files, and duplicate Livewire view trees create noise.

Recommendation:

- Priority: P0
- Reason: The module combines shell UI, business domains, and system administration.
- Expected benefit: Clear containment prevents accidental production-control exposure and allows safe staged refactoring.

### Code Quality

Code quality is uneven. Some controllers are thin, but Livewire components contain queries, transactions, import/export, filesystem access, recursive persistence, and direct model writes.

Recommendation:

- Priority: P1
- Reason: Livewire should own UI state and call services, not implement business logic.
- Expected benefit: Improves testability, reduces regressions, and aligns with Laravel 12 / Livewire 3 patterns.

### Data Integrity Risks

Major risks:

- Menu restore can delete current menus before import completes.
- Address default updates are multi-write without explicit transactions.
- Database destructive operations can leave foreign key checks disabled.
- Product imports can create partial data and duplicates.

Recommendation:

- Priority: P0/P1
- Reason: Data loss and partial writes are possible.
- Expected benefit: Atomic operations and rollback rules protect production data.

### Security Risks

Major risks:

- Missing named permissions on active Admin routes and mutating Livewire actions.
- Raw filename/table/file path inputs in database operations.
- Shell command strings include database credentials.
- Raw exception leakage from system operations.
- UI menu permission display may be mistaken for authorization.

Recommendation:

- Priority: P0
- Reason: Authentication alone is not authorization.
- Expected benefit: Sensitive actions fail closed and become auditable.

### Technical Debt

Technical debt is high:

- Stale routes.
- Duplicate Livewire views.
- Domain code in Admin.
- Malformed migration timestamps.
- Legacy import/export.
- Placeholder/scaffold files.

Recommendation:

- Priority: P1/P2
- Reason: Dead and misplaced code hides the actual module boundary.
- Expected benefit: Cleaner module ownership and lower maintenance cost.

## 3. Refactor Plan Validation

| Refactor Item | Status | Recommendation |
|---|---|---|
| P0-1 Unauthenticated Admin API Route | Approved | Priority: P0. Reason: Public admin route is unnecessary risk. Expected benefit: API surface fails closed. Prefer deleting the route unless a real API consumer is confirmed. |
| P0-2 Database Download Lacks Authorization | Approved | Priority: P0. Reason: Backup downloads are sensitive. Expected benefit: Prevents data exfiltration. Add opaque server-issued backup IDs. |
| P0-3 Dangerous Database Livewire Actions | Approved | Priority: P0. Reason: Browser-triggered export/truncate/drop is production-control risk. Expected benefit: Destructive flows become permissioned, confirmed, and audited. |
| P0-4 Unsafe Shell Commands and Credential Exposure | Approved | Priority: P0. Reason: Shell strings and command-line credentials violate roadmap P0. Expected benefit: Reduces command injection and secret leakage. |
| P0-5 Foreign Key Checks Not Restored Reliably | Approved | Priority: P0. Reason: Failed destructive operations can corrupt DB session state. Expected benefit: Safer rollback and data integrity. |
| P0-6 Raw Exception Leakage | Approved | Priority: P0. Reason: Process output may expose paths, DB names, or secrets. Expected benefit: Safer operational error handling. |
| P1-1 Admin Shell Boundary and Domain Ownership | Modify | Priority: P1. Reason: Correct direction, but too broad to implement in one slice. Expected benefit: Use a canonical ownership map first, then migrate domain by domain. |
| P1-2 Active Admin Routes Lack Named Permissions | Approved | Priority: P1. Reason: `auth:admin` alone is insufficient. Expected benefit: Route/action authorization becomes explicit and testable. |
| P1-3 Menu Livewire Bypasses Service Layer | Approved | Priority: P1. Reason: Menu is active Admin shell behavior and can be refactored safely after P0. Expected benefit: Thin Livewire and service-owned business logic. |
| P1-4 Menu Validation and Destructive Restore | Approved | Priority: P1. Reason: Current restore/import path can corrupt navigation. Expected benefit: Validated all-or-nothing menu changes. |
| P1-5 Shared Import/Export Foundation | Modify | Priority: P1. Reason: Correct standard, but Admin should only own shell menu import/export. Expected benefit: Avoids rebuilding domain imports in the wrong module. |
| P1-6 Product Import/Export | Modify | Priority: P1. Reason: Correct issue, wrong implementation location if done in Admin. Expected benefit: Product import/export moves to canonical Product owner. |
| P1-7 Direct Model Queries in Controllers/Livewire | Approved | Priority: P1. Reason: Direct queries violate architecture flow. Expected benefit: Centralized rules, authorization, and tests. |
| P1-8 Settings and Category Ownership | Modify | Priority: P1. Reason: Needs a decision before schema/model changes. Expected benefit: Prevents duplicate table ownership and migration churn. |
| P1-9 Malformed Migration Timestamps | Modify | Priority: P1. Reason: Must not rename blindly if production migration history exists. Expected benefit: Reliable fresh installs without breaking deployed history. |
| P1-10 Pagination and N+1 Risks | Approved | Priority: P1. Reason: Current `get()` and `paginate(999999)` are unsafe. Expected benefit: Production-sized data remains usable. |
| P1-11 Address Default Transactions | Modify | Priority: P1. Reason: Correct only if Admin remains address owner. Expected benefit: Atomic default address updates in the canonical owner. |
| P1-12 Shared UI Components | Modify | Priority: P2/P1. Reason: Move only after usage audit. Expected benefit: Reuse without coupling modules to Admin. |
| P2-1 Stale `web copy.php` | Approved | Priority: P2. Reason: Not loaded by module bootstrap. Expected benefit: Less route confusion after route tests. |
| P2-2 Zone.Identifier Artifact | Approved | Priority: P2. Reason: Repository noise. Expected benefit: Cleaner deploy/file scans. |
| P2-3 Duplicate Livewire Blade Trees | Approved | Priority: P2. Reason: Duplicate views create maintenance drift. Expected benefit: Single active view path per component. |
| P2-4 Scaffold and Placeholder Files | Approved | Priority: P2. Reason: Dead code adds noise. Expected benefit: Smaller module surface. |
| P2-5 Menu Permission Display | Approved | Priority: P2/P1. Reason: UI permission filtering is not authorization. Expected benefit: Prevents false security assumptions. |

Rejected items: none.

Main challenge to the plan:

- Priority: P0
- Reason: Database administration may belong in `Modules/System`, not Admin.
- Expected benefit: Keeps Admin as shell while isolating production-control tooling. **NEEDS CONFIRMATION BEFORE CODING.**

## 4. Rebuild Specification Validation

### Route Design

Strengths:

- Correctly limits active Admin routes to shell routes.
- Calls out API removal/protection.
- Requires named permissions.

Weaknesses:

- Permission names are proposed but not confirmed against Role module seeders.
- Database route ownership remains open.

Missing parts:

- Explicit route naming convention for any retained database/system route.
- Decision on API route deletion vs protection.

Recommended improvements:

- Priority: P0. Reason: Route surface must fail closed. Expected benefit: Prevents accidental exposure. Confirm whether `Modules/Admin/routes/api.php` should be deleted.
- Priority: P1. Reason: Permission names must match existing authorization model. Expected benefit: Avoids broken seeders and denied valid users.

### Controller Design

Strengths:

- Thin controller rule is correct.
- Correctly identifies `ProductCommissionController` as a boundary violation.

Weaknesses:

- Does not specify whether inactive controllers should be removed or migrated.

Missing parts:

- Controller migration/deprecation strategy for domain controllers.

Recommended improvements:

- Priority: P1. Reason: Dead controllers can be mistaken for active endpoints. Expected benefit: Clearer route-to-controller map after route tests.

### Livewire Design

Strengths:

- Correctly moves menu business logic out of Livewire.
- Defines state, validation, events, pagination, and action protection.

Weaknesses:

- Still includes `UserAddress` as possibly Admin-owned; this is uncertain.
- Database Livewire components need stronger “disabled until P0 complete” language.

Missing parts:

- Explicit use of `boot()` service injection for Livewire services.
- Confirmation behavior for destructive menu restore and bulk deletes.

Recommended improvements:

- Priority: P1. Reason: Livewire action boundaries must be uniform. Expected benefit: Easier tests and safer service calls.
- Priority: P0. Reason: Database Livewire actions are dangerous. Expected benefit: No accidental production-control exposure.

### Service Design

Strengths:

- `MenuService` is the right abstraction for active shell menu work.
- Defines transactions and business rules.
- Flags `DatabaseService` ownership as uncertain.

Weaknesses:

- The service list is still broad; some services should likely move before being hardened in Admin.

Missing parts:

- Explicit application service vs infrastructure service split for database operations.
- Audit service dependency for destructive actions.

Recommended improvements:

- Priority: P0. Reason: Database operations are infrastructure/system concerns. Expected benefit: Cleaner ownership and safer operational controls.
- Priority: P1. Reason: Menu service should be small and shell-specific. Expected benefit: Avoids creating another god service.

### Import Design

Strengths:

- Correctly limits Admin imports to shell menu data.
- Rejects destructive replace/truncate without confirmation.
- Requires dry-run and structured error reporting.

Weaknesses:

- Ambiguous whether JSON import should use shared import/export foundation.

Missing parts:

- Final decision on JSON vs spreadsheet import.
- Sample import file requirement.

Recommended improvements:

- Priority: P1. Reason: Import behavior cannot be guessed. Expected benefit: Prevents bad mapping and data loss. **NEEDS CONFIRMATION BEFORE CODING.**

### Export Design

Strengths:

- Correctly moves product export out of Admin.
- Requires bounded export strategy.

Weaknesses:

- Menu JSON export vs spreadsheet export remains undecided.

Missing parts:

- Export storage visibility and retention rules.

Recommended improvements:

- Priority: P1. Reason: Export files can leak internal navigation/permissions. Expected benefit: Controlled storage and cleanup.

### Model Design

Strengths:

- Distinguishes Admin shell models from domain models.
- Correctly questions `Category` and `Setting` ownership.

Weaknesses:

- Proposed `AdminMenuItem` table/model is sensible but not confirmed.

Missing parts:

- Decision record for whether menu data remains in `categories`.

Recommended improvements:

- Priority: P1. Reason: Model ownership determines migrations and caller migration order. Expected benefit: Prevents duplicate model/table ownership.

### Database Design

Strengths:

- Identifies malformed migration timestamps.
- Lists indexes and constraints for shell menu/header tables.

Weaknesses:

- Does not yet include a production migration-history safety check.

Missing parts:

- Migration compatibility path for already-run negative timestamp migrations.

Recommended improvements:

- Priority: P1. Reason: Migration renames can break deployed history. Expected benefit: Safe fresh install and safe production upgrade.

### Authorization Design

Strengths:

- Requires route middleware and Livewire action checks.
- Recognizes UI menu filtering is not authorization.

Weaknesses:

- Exact permission list is not tied to current Role module seeders.

Missing parts:

- Policy/Gate implementation location.
- Audit log contract for destructive actions.

Recommended improvements:

- Priority: P0. Reason: P0 roadmap requires named permissions and audit records. Expected benefit: Denied/default-secure behavior.

### Test Design

Strengths:

- Covers route, Livewire, service, import, export, and authorization tests.
- Includes negative tests for destructive operations.

Weaknesses:

- Does not specify test ownership/location by module.
- Does not address current lack of meaningful test suite.

Missing parts:

- Minimal first test slice.
- Query-count tests for menu tree and large list paths.

Recommended improvements:

- Priority: P0/P1. Reason: Security and architecture refactors need guardrails. Expected benefit: Safer incremental implementation.

## 5. Architecture Gap Analysis

| Area | Current Architecture | Target Architecture | Gap | Priority | Recommendation |
|---|---|---|---|---|---|
| Routes | Active web routes use `auth:admin`; API route unauthenticated | Auth plus named permissions; API removed/protected | Missing permission layer | P0 | Reason: sensitive admin surface. Expected benefit: fail-closed routing. |
| Controllers | Mostly thin, but some query models | View/scalar-only controllers | Domain queries in controller | P1 | Reason: controller should not own queries. Expected benefit: testable service boundary. |
| Page Blade | Many domain pages under Admin | Shell pages only | Domain pages in shell module | P1 | Reason: unclear ownership. Expected benefit: canonical module boundaries. |
| Livewire | Direct queries, transactions, import/export | UI state and service calls only | Missing service boundary | P1 | Reason: Livewire components are too fat. Expected benefit: maintainability/testability. |
| Services | Some services exist, but many cross domains | Admin shell services only | Cross-domain services | P1 | Reason: Admin acts as domain owner. Expected benefit: DDD alignment. |
| Import | Legacy Maatwebsite/custom imports | Shared import/export service | Missing shared import foundation | P1 | Reason: inconsistent validation/reporting. Expected benefit: safer imports. |
| Export | Unbounded `get()` and custom exports | Bounded/queued shared exports | Missing bounded export strategy | P1 | Reason: large dataset risk. Expected benefit: scalable exports. |
| Models | Admin owns domain models/tables | Shell-owned models only | Duplicate model ownership | P1 | Reason: data ownership unclear. Expected benefit: safer schema evolution. |
| Migrations | Negative timestamps | Deterministic migrations | Migration hygiene gap | P1 | Reason: fresh install risk. Expected benefit: reliable deployment. |
| Authorization | UI hiding and auth guard | Server-side permissions/policies | Missing action checks | P0 | Reason: hidden UI is not security. Expected benefit: real access control. |
| Transactions | Livewire and service mixed; missing `finally` | Service-owned transactions | Data integrity gap | P0/P1 | Reason: data loss/corruption risk. Expected benefit: atomic changes. |
| Tests | No meaningful module tests documented | Route/Livewire/service/security tests | Missing test suite | P0/P1 | Reason: refactor has high blast radius. Expected benefit: safer automation. |

## 6. Domain Design Review

### Presentation Layer

Current:

- Admin layout, page blades, partials, and many domain pages coexist.

Target:

- Admin owns shell layout, navigation, dashboard entry, profile shell, header/theme/menu UI.

Recommendation:

- Priority: P1
- Reason: Presentation shell should not imply domain ownership.
- Expected benefit: Cleaner UI composition and lower coupling.

### Application Layer

Current:

- Application orchestration is often inside Livewire.

Target:

- Livewire delegates to services such as `MenuService`, `HeaderMenuService`, and canonical domain services.

Recommendation:

- Priority: P1
- Reason: Use service layer for business workflows.
- Expected benefit: Reusable, testable workflows.

### Domain Layer

Current:

- Admin contains models for affiliate, banner, chat, flash sale, settings, user address, and menu/category-like behavior.

Target:

- Domain models live in canonical domain modules. Admin keeps only shell models.

Recommendation:

- Priority: P1
- Reason: A business concept needs one canonical owner.
- Expected benefit: Reduces duplicate behavior and schema conflicts.

### Infrastructure Layer

Current:

- Database backup/restore/truncate/drop infrastructure sits in Admin.

Target:

- Move to `System` or isolate behind a hardened infrastructure service with strict permissions.

Recommendation:

- Priority: P0
- Reason: Production-control tooling is not normal admin presentation.
- Expected benefit: Smaller blast radius and stronger operational safety.

## 7. Shared Component Opportunities

| Opportunity | Candidate Files | Reuse Value | Recommendation |
|---|---|---:|---|
| Shared import/export foundation | `Modules/Admin/Imports/ProductsImport.php`, `Modules/Admin/Exports/ProductsExport.php`, Livewire import/export tables | High | Priority: P1. Reason: Current imports are duplicated. Expected benefit: consistent validation, dry-run, reports. |
| Shared file upload/image component | `Modules/Admin/resources/views/components/image-upload.blade.php` | High | Priority: P1. Reason: Multiple modules likely need image upload. Expected benefit: consistent upload UI and validation. NEEDS CONFIRMATION BEFORE CODING. |
| Shared editor component | `Modules/Admin/resources/views/components/editor.blade.php` | Medium | Priority: P2. Reason: Rich text likely reused. Expected benefit: fewer duplicated editor integrations. |
| Shared currency input | `Modules/Admin/resources/views/components/currency-input.blade.php` | High | Priority: P1. Reason: Money formatting rules are project-wide. Expected benefit: fewer formatted-string persistence bugs. |
| Shared category/select search | `Modules/Admin/resources/views/components/category-select.blade.php` | Medium | Priority: P2. Reason: Relationship selectors repeat. Expected benefit: consistent searchable select UX. |
| Shared table patterns | Menu/product/post/order tables | High | Priority: P2. Reason: Tables need consistent pagination/loading/empty states. Expected benefit: faster UI refactors. |
| Shared bulk action confirmation | Bulk delete/status/permission flows | High | Priority: P1. Reason: Dangerous actions repeat. Expected benefit: safer UX and authorization consistency. |
| Shared filters | Search/status/perPage filters | Medium | Priority: P2. Reason: Common list behavior. Expected benefit: lower Livewire boilerplate. |

Do not move shared candidates until actual cross-module usage is verified.

## 8. Performance Review

| Area | Risk | Finding | Recommendation |
|---|---|---|---|
| Queries | High | Livewire components perform direct queries and unbounded loads. | Priority: P1. Reason: Query behavior must be centralized. Expected benefit: service-level optimization and tests. |
| Relationships | Medium | Menu tree uses recursive children and can N+1 deeper levels. | Priority: P1. Reason: Tree depth can grow. Expected benefit: predictable query count. |
| Pagination | High | `paginate(999999)` and unguarded `All` patterns exist. | Priority: P1. Reason: Memory risk. Expected benefit: production-sized lists survive. |
| Search | Medium | Search lives in components. | Priority: P1. Reason: Filtering belongs in services. Expected benefit: consistent indexed queries. |
| Filters | Medium | Filters vary by component. | Priority: P2. Reason: UI inconsistency. Expected benefit: reusable list patterns. |
| Import | High | Imports lack dry-run, chunking, and reports. | Priority: P1. Reason: Data risk. Expected benefit: controlled large imports. |
| Export | High | Product export can load all records. | Priority: P1. Reason: Large dataset failure risk. Expected benefit: chunked/queued exports. |
| Caching | Medium | Menu cache invalidation is spread across model/component/service behavior. | Priority: P1. Reason: stale nav risk. Expected benefit: reliable cache invalidation. |
| Queue Usage | Medium | Large imports/exports are not queued. | Priority: P1. Reason: request timeout risk. Expected benefit: scalable background processing in canonical modules. |
| Large Dataset Handling | High | Current patterns do not support 100K+ rows safely. | Priority: P1. Reason: unbounded memory. Expected benefit: predictable scaling. |

## 9. Security Review

| Area | Risk | Review | Recommendation |
|---|---|---|---|
| Authorization | High | Active routes and Livewire methods lack named permissions. | Priority: P0/P1. Reason: auth is not enough. Expected benefit: least-privilege access. |
| Policies/Gates | High | Policy/Gate design not confirmed. | Priority: P1. Reason: permission naming must align with Role module. Expected benefit: consistent denial behavior. |
| Mass Assignment | Medium | Multiple models expose broad fillable fields. | Priority: P1. Reason: direct model writes from Livewire increase risk. Expected benefit: validated service data only. |
| Validation | High | Menu/import validation is incomplete. | Priority: P1. Reason: bad inputs can corrupt navigation/data. Expected benefit: safer persistence. |
| File Upload | High | Imports/uploads are custom and not always shared. | Priority: P1. Reason: file content cannot be trusted. Expected benefit: consistent file validation. |
| Import | High | No dry-run/unique key/null-overwrite policy for legacy imports. | Priority: P1. Reason: data loss/duplicates. Expected benefit: predictable import behavior. |
| Export | High | Backup/product exports can leak sensitive data. | Priority: P0/P1. Reason: data exfiltration risk. Expected benefit: controlled exports and storage. |
| Sensitive Data Exposure | Critical | DB passwords in shell commands and raw exceptions. | Priority: P0. Reason: secret leakage. Expected benefit: safer operations. |
| CSRF | Medium | Livewire generally protects, but API route is open. | Priority: P0. Reason: API is unauthenticated. Expected benefit: reduced attack surface. |
| XSS | Medium | Menu labels/icons/URLs render in navigation. | Priority: P1. Reason: admin-provided content can still become XSS. Expected benefit: escaped output and URL validation. |
| SQL Injection | High | Table names in DB service statements are risky. | Priority: P0. Reason: identifiers are not safely parameterized. Expected benefit: allowlisted schema operations. |

## 10. Future Scalability Review

| Capability | Current Fit | Target Fit | Recommendation |
|---|---|---|---|
| 10K records | Partial | Good after service pagination | Priority: P1. Reason: remove unbounded loads. Expected benefit: stable admin lists. |
| 100K records | Poor | Possible with chunking/queues | Priority: P1. Reason: current exports/imports unsafe. Expected benefit: large dataset readiness. |
| 1M records | Not ready | Requires queues, indexing, async exports | Priority: P1/P2. Reason: request-time processing will fail. Expected benefit: long-term growth path. |
| Multi-user usage | Weak | Requires transactions and audit | Priority: P1. Reason: concurrent menu/order/settings changes can conflict. Expected benefit: safer concurrent admin usage. |
| Background jobs | Weak | Needed for exports/imports | Priority: P1. Reason: long-running work blocks requests. Expected benefit: resilient processing. |
| Audit logs | Missing | Required for P0 destructive actions | Priority: P0. Reason: destructive actions need accountability. Expected benefit: operational traceability. |
| Soft delete | Unclear | Domain-specific | Priority: P2. Reason: shell menus may not need it, domain modules might. Expected benefit: safer recovery when needed. NEEDS CONFIRMATION BEFORE CODING. |
| Versioning | Missing | Useful for menu/settings | Priority: P2. Reason: admin config mistakes need rollback. Expected benefit: safer operations. |
| API integration | Not ready | Secure route contracts required | Priority: P0/P1. Reason: current API route is unauthenticated. Expected benefit: controlled integration surface. |

## 11. Recommended Final Architecture

```text
Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import
→ Export
→ Model
→ Migration
```

Layer responsibilities:

- Route: URL, route name, `web`, `auth:admin`, and named permission middleware only.
- Controller: Return Admin shell views and pass scalar IDs only.
- Page Blade: Extend `Admin::layouts.master`, define shell layout, mount Livewire.
- Livewire PHP: UI state, validation, authorization call, service call, events.
- Livewire Blade: Inputs, tables, forms, loading/disabled/empty states, confirmation UI.
- Shared Components: Reusable inputs, upload controls, table patterns, confirmation components after usage audit.
- Service: Queries, search/filter/sort, transactions, cache invalidation, import/export orchestration, domain invariants.
- Import: Only via canonical module service and shared import foundation.
- Export: Bounded or queued export via canonical module service and shared export foundation.
- Model: ORM config, fillable, casts, scopes, relationships only.
- Migration: Schema, constraints, indexes, comments, deterministic ordering.

Recommendation:

- Priority: P1
- Reason: The target flow matches project standards and DDD layering.
- Expected benefit: Smaller Admin module, easier testing, safer refactors.

## 12. Final Implementation Strategy

### Phase 1: Critical Fixes

1. Remove or secure `Modules/Admin/routes/api.php`.
2. Disable or harden database administration flows in `DatabaseController`, database Livewire classes, and `DatabaseService`.
3. Replace shell command strings and secret exposure.
4. Add named permissions and denial tests for active Admin routes/actions.

Recommendation:

- Priority: P0
- Reason: Security and data-loss risks block safe implementation.
- Expected benefit: Admin cannot expose production-control behavior while refactoring.

### Phase 2: Architecture Improvements

1. Confirm canonical ownership map.
2. Extract active menu behavior to `MenuService`.
3. Move direct model queries out of Admin controllers/Livewire.
4. Resolve settings/category/user-address ownership.
5. Define migration compatibility plan.

Recommendation:

- Priority: P1
- Reason: Service and domain boundaries are prerequisites for maintainable implementation.
- Expected benefit: Reduced coupling and clear ownership.

### Phase 3: Performance Improvements

1. Replace unbounded export/import/list patterns.
2. Add guarded `All` pagination.
3. Add menu tree query-count tests.
4. Queue large domain imports/exports in canonical modules.

Recommendation:

- Priority: P1
- Reason: Existing patterns are not production-scale.
- Expected benefit: Stable behavior with larger datasets.

### Phase 4: Code Cleanup

1. Remove `web copy.php` after route tests.
2. Remove Zone.Identifier and placeholder files.
3. Prune duplicate Livewire view trees.
4. Move confirmed reusable components to Shared.

Recommendation:

- Priority: P2
- Reason: Cleanup should follow safety and behavior-preserving refactors.
- Expected benefit: Cleaner repository without accidental feature loss.

## 13. Codex Readiness Assessment

Implementation Readiness: **62/100**

Status: **NOT APPROVED FOR IMPLEMENTATION**

Missing information because score is below 80:

- NEEDS CONFIRMATION BEFORE CODING: Should `Modules/Admin/routes/api.php` be deleted or protected?
- NEEDS CONFIRMATION BEFORE CODING: Should database administration stay in Admin or move to System?
- NEEDS CONFIRMATION BEFORE CODING: Exact permission names and seed location for Admin shell and database actions.
- NEEDS CONFIRMATION BEFORE CODING: Canonical owner for settings, menu/category data, user addresses, banners, flash sales, affiliate schemes, and chat.
- NEEDS CONFIRMATION BEFORE CODING: Whether Admin shell menus stay in `categories` or move to a dedicated `admin_menu_items` table.
- NEEDS CONFIRMATION BEFORE CODING: Migration compatibility plan for negative-year migration filenames.
- NEEDS CONFIRMATION BEFORE CODING: Menu import/export format: JSON, spreadsheet, or both.
- NEEDS CONFIRMATION BEFORE CODING: Sample files and mapping rules for any import/export work.
- NEEDS CONFIRMATION BEFORE CODING: First test slice and test location conventions.

Final recommendation:

- Priority: P0
- Reason: The architecture direction is correct, but implementation still has unresolved security and ownership decisions.
- Expected benefit: Confirming these decisions first prevents Codex from implementing the wrong boundary or expanding unsafe behavior.
