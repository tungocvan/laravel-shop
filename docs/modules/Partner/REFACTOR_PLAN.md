# Partner Refactor Plan

## 1. Executive Summary

`Modules/Partner` already has a clear Laravel module shape for admin CRUD, but the current implementation has security, import/export, service-boundary, validation, transaction, and performance gaps.

The highest-risk items are the public broken API route in `Modules/Partner/routes/api.php`, missing server-side permission checks in `Modules/Partner/Livewire/Partner/Index.php` and `Modules/Partner/Livewire/Partner/Form.php`, and unsafe import behavior that uses nullable `tax_code` as an upsert key.

The main refactor direction is:

- Keep `Partner` as the canonical module owner for the `partners` table.
- Keep controllers thin and pages as Livewire shells.
- Move Partner queries, bulk delete, validation invariants, and bounded pagination/export behavior into `Modules/Partner/Services/PartnerService.php`.
- Move import/export to `Modules/Partner/Services/ImportExport.php` and the shared import/export foundation.
- Preserve the existing UI behavior until security and data-safety rules are confirmed.

No implementation should begin until import unique key behavior, null-overwrite behavior, import mode, dry-run behavior, and authorization conventions are confirmed.

## 2. P0 Critical Fixes

### P0-01 Public Broken API Route

* Issue: `Modules/Partner/routes/api.php` registers `GET partner` without authentication and points to `Modules\Partner\Http\Controllers\Api\PartnerController@index`, but that method does not exist.
* Root Cause: Scaffolded API route was left enabled while the API controller remained empty.
* Business Impact: A public endpoint may expose Partner behavior unexpectedly once implemented or cause production errors now.
* Technical Impact: Route boot can succeed, but requests to the endpoint can fail because `index()` is missing; security posture is fail-open at route level.
* Proposed Solution: Remove or disable the API route, or implement it only with explicit API authentication, authorization, response contract, and tests.
* Files To Change: `Modules/Partner/routes/api.php`, `Modules/Partner/Http/Controllers/Api/PartnerController.php`.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: `GET partner` is not publicly callable unless intentionally protected; route tests prove unauthenticated requests are denied or route is absent; no missing controller method remains.

### P0-02 Missing Permission Checks For List Actions

* Issue: `Modules/Partner/Livewire/Partner/Index.php` has `delete()`, `deleteSelected()`, `import()`, and `export()` actions without visible named permission checks.
* Root Cause: The component relies on `auth:admin` from `Modules/Partner/routes/web.php` and UI controls instead of capability-level authorization.
* Business Impact: Any authenticated admin who can reach the page may delete, import, or export partner data without explicit permission.
* Technical Impact: Livewire action calls can bypass hidden/disabled UI state; destructive and data-export operations are not denied by default.
* Proposed Solution: Add server-side permission checks at Livewire action boundaries using the project's role/permission convention for `delete_partner`, import, export, and any future bulk permissions.
* Files To Change: `Modules/Partner/Livewire/Partner/Index.php`, possibly `Modules/Partner/config/module.php` if import/export permissions must be declared.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Unauthorized admins cannot call delete, bulk delete, import, or export via Livewire; authorized admins retain expected behavior; denial paths are tested.

### P0-03 Missing Permission Checks For Form Save

* Issue: `Modules/Partner/Livewire/Partner/Form.php` saves create and update requests without visible create/update permission checks.
* Root Cause: The form trusts route access and does not enforce capability-level checks before persistence.
* Business Impact: Any authenticated admin with route access may create or edit Partner records.
* Technical Impact: Authorization is not enforced at the mutating Livewire action, which conflicts with the project security standard.
* Proposed Solution: Add create/update authorization in `save()` and, where helpful, mount-level denial for edit access.
* Files To Change: `Modules/Partner/Livewire/Partner/Form.php`, possibly `Modules/Partner/config/module.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Unauthorized admins cannot create or update partners through Livewire calls; authorized admins can save; denial paths are tested.

### P0-04 Unsafe Nullable Tax Code Import Upsert

* Issue: `Modules/Partner/Livewire/Partner/Index.php` imports rows with `Partner::updateOrCreate(['tax_code' => nullable value], ...)`; `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` allows nullable unique `tax_code`.
* Root Cause: Import logic assumed `tax_code` is a reliable unique business key, even though the schema allows null and business behavior is unconfirmed.
* Business Impact: Rows without tax code may overwrite or merge unrelated partner records, causing data loss or silent corruption.
* Technical Impact: Import persistence is not deterministic for missing unique keys and cannot report safe duplicate handling.
* Proposed Solution: Stop using nullable `tax_code` as the upsert key; require a confirmed non-null unique key, or reject/skip rows missing the unique key with row-level errors.
* Files To Change: `Modules/Partner/Livewire/Partner/Index.php`, `Modules/Partner/Services/ImportExport.php`, `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` only if schema rules are later confirmed.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1-2 days after import behavior is confirmed.
* Acceptance Criteria: Import cannot update/create by a null unique key; missing key behavior is explicit; tests cover blank `tax_code`, duplicate `tax_code`, and valid import rows.

### P0-05 Broken Empty API Controller

* Issue: `Modules/Partner/Http/Controllers/Api/PartnerController.php` is empty while `Modules/Partner/routes/api.php` references `index`.
* Root Cause: API scaffold was not completed or removed.
* Business Impact: API consumers, probes, or internal callers receive failures instead of a defined contract.
* Technical Impact: The module contains a route-controller mismatch and an unused insecure entry point.
* Proposed Solution: Remove the API controller and route if API is not required, or implement a minimal authenticated endpoint with explicit authorization.
* Files To Change: `Modules/Partner/Http/Controllers/Api/PartnerController.php`, `Modules/Partner/routes/api.php`.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: No route points to a missing API controller method; API surface is either absent or secured and tested.

## 3. P1 Important Refactors

### P1-01 Move Direct Model Queries Out Of Livewire Index

* Issue: `Modules/Partner/Livewire/Partner/Index.php` directly queries `Partner` in `deleteSelected()`, `currentPagePartnerIds()`, `import()`, and `export()`.
* Root Cause: List, selection, import, export, and bulk-delete behavior were implemented directly in the component instead of the service layer.
* Business Impact: Business rules become inconsistent and hard to secure across future callers.
* Technical Impact: Violates Laravel module flow; makes testing and transaction handling harder.
* Proposed Solution: Move query construction, filtered IDs, bulk delete, and persistence orchestration into `Modules/Partner/Services/PartnerService.php` and import/export service classes.
* Files To Change: `Modules/Partner/Livewire/Partner/Index.php`, `Modules/Partner/Services/PartnerService.php`, `Modules/Partner/Services/ImportExport.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: `Index.php` only manages UI state and calls services; no direct `Partner::query()`, `Partner::updateOrCreate()`, or unbounded export query remains in Livewire.

### P1-02 Centralize Duplicate Search And Filter Query Logic

* Issue: Search/filter logic is duplicated in `Modules/Partner/Livewire/Partner/Index.php` and `Modules/Partner/Services/PartnerService.php`.
* Root Cause: `currentPagePartnerIds()` rebuilt the same filter query inside Livewire for selection support.
* Business Impact: Filters may diverge between displayed rows and selected rows, causing wrong bulk actions.
* Technical Impact: Query maintenance doubles and increases regression risk.
* Proposed Solution: Add a reusable filtered query builder or service method in `PartnerService` for pagination and selected/current-page IDs.
* Files To Change: `Modules/Partner/Services/PartnerService.php`, `Modules/Partner/Livewire/Partner/Index.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: One service-owned filter implementation drives pagination and current-page IDs; tests prove selection honors active filters.

### P1-03 Replace Custom Import/Export With Shared Foundation

* Issue: Import/export is implemented directly in `Modules/Partner/Livewire/Partner/Index.php`; `Modules/Partner/Services/ImportExport.php` does not exist.
* Root Cause: Legacy component-level FastExcel usage predates or bypasses the shared import/export architecture.
* Business Impact: Imports lack dry-run, row reports, safe duplicate handling, and predictable export behavior.
* Technical Impact: Duplicates shared file validation, storage, mapping, normalization, and reporting responsibilities.
* Proposed Solution: Create `Modules/Partner/Services/ImportExport.php` extending the shared import/export foundation; mount `shared.import-export.panel` from `Modules/Partner/resources/views/livewire/partner/index.blade.php` or a dedicated page if needed.
* Files To Change: `Modules/Partner/Services/ImportExport.php`, `Modules/Partner/Livewire/Partner/Index.php`, `Modules/Partner/resources/views/livewire/partner/index.blade.php`, `Modules/Partner/Models/Partner.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days after import mapping confirmation.
* Acceptance Criteria: Import/export uses `serviceClass`; shared panel handles upload/report UI; Partner service handles module rules; old component-level FastExcel import/export is removed.

### P1-04 Define Import Mapping And Data Policy

* Issue: `Modules/Partner/Livewire/Partner/Index.php` silently maps a few English/Vietnamese headers, defaults missing values, and has no confirmed import mode or null-overwrite policy.
* Root Cause: Import behavior was coded without a documented rebuild spec or sample-file mapping gate.
* Business Impact: Bad spreadsheets can silently create incorrect statuses, sources, partner roles, or overwrite existing data.
* Technical Impact: No reliable validation matrix, dry-run, or row-level diagnostics.
* Proposed Solution: Before implementation, document confirmed headers, aliases, unique key, import mode, dry-run behavior, partial/all-or-nothing behavior, null-overwrite behavior, validation rules, and export columns.
* Files To Change: `docs/modules/Partner/REBUILD_SPEC.md`, `Modules/Partner/Services/ImportExport.php`, `Modules/Partner/Livewire/Partner/Index.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1 day for specification, 1-2 days for implementation.
* Acceptance Criteria: Import decisions are documented before code; invalid rows return row-level errors; defaults are explicit and tested.

### P1-05 Add Transaction Boundaries For Import And Bulk Delete

* Issue: `Modules/Partner/Livewire/Partner/Index.php` performs import writes and bulk delete without service-owned transaction strategy.
* Root Cause: Persistence occurs inside Livewire and uses independent model calls.
* Business Impact: Failed imports may leave partial, unreported data changes; bulk delete has no future-safe audit or rollback boundary.
* Technical Impact: Violates transaction ownership rules and complicates retry/idempotency.
* Proposed Solution: Move persistence to `PartnerService` and `ImportExport` service; define all-or-nothing versus partial import behavior; use transactions where multi-row consistency is required.
* Files To Change: `Modules/Partner/Livewire/Partner/Index.php`, `Modules/Partner/Services/PartnerService.php`, `Modules/Partner/Services/ImportExport.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Multi-row import/delete persistence has explicit transaction behavior; rollback/failure scenarios are tested.

### P1-06 Guard `All` Pagination And Large Exports

* Issue: `Modules/Partner/Services/PartnerService.php` returns `get()` for `perPage === 'All'`; `Modules/Partner/Livewire/Partner/Index.php` exports all records with `get()`.
* Root Cause: UI convenience was implemented without production data-size guardrails.
* Business Impact: Large datasets may cause slow requests, memory exhaustion, or failed exports.
* Technical Impact: Unbounded queries violate performance standards and make Livewire requests fragile.
* Proposed Solution: Cap or disable `All` above a safe count, use chunked/lazy export via shared import/export service, and queue long exports if needed.
* Files To Change: `Modules/Partner/Services/PartnerService.php`, `Modules/Partner/Livewire/Partner/Index.php`, `Modules/Partner/Services/ImportExport.php`, `Modules/Partner/resources/views/livewire/partner/index.blade.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Large datasets cannot be loaded unbounded into memory; export uses bounded iteration; UI communicates disabled/capped `All` behavior where applicable.

### P1-07 Add Service-Level Invariants For Enum-Like Fields

* Issue: `Modules/Partner/Services/PartnerService.php` normalizes data but does not enforce allowed values for `legal_type`, `partner_types`, `source`, and `status`.
* Root Cause: Validation exists mainly in `Modules/Partner/Livewire/Partner/Form.php`, not at the service boundary.
* Business Impact: Future non-Livewire callers can persist invalid states.
* Technical Impact: Business invariants are split across UI and model constants instead of enforced in service logic.
* Proposed Solution: Validate or guard enum-like values in `PartnerService` and `ImportExport` using `Modules/Partner/Models/Partner.php` constants.
* Files To Change: `Modules/Partner/Services/PartnerService.php`, `Modules/Partner/Services/ImportExport.php`, `Modules/Partner/Models/Partner.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Invalid enum-like values are rejected by service calls; Livewire and import paths share the same allowed value set.

### P1-08 Standardize Edit Parameter Contract

* Issue: `Modules/Partner/resources/views/pages/edit.blade.php` passes `partnerId`, and `Modules/Partner/Livewire/Partner/Form.php` accepts `partnerId`, while project examples standardize on scalar `id`.
* Root Cause: Local form contract evolved with a custom parameter name.
* Business Impact: Low direct business impact, but inconsistent conventions slow maintenance.
* Technical Impact: Component API deviates from module standards and may confuse future route/page generation.
* Proposed Solution: During form refactor, align edit page and component with `id` while preserving compatibility if existing links depend on `partnerId`.
* Files To Change: `Modules/Partner/resources/views/pages/edit.blade.php`, `Modules/Partner/Livewire/Partner/Form.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Edit page passes `id`; form accepts `?int $id = null`; edit behavior remains unchanged and tested.

### P1-09 Replace Full Page Range Pagination Rendering

* Issue: `Modules/Partner/resources/views/livewire/partner/index.blade.php` renders all page numbers with `$partners->getUrlRange(1, $partners->lastPage())`.
* Root Cause: Pagination UI was manually implemented without a bounded page window.
* Business Impact: Large partner lists can make the admin page slow or visually unusable.
* Technical Impact: Blade renders excessive buttons and Livewire payload can grow unnecessarily.
* Proposed Solution: Use Laravel/Livewire pagination links or a bounded page window.
* Files To Change: `Modules/Partner/resources/views/livewire/partner/index.blade.php`, possibly `Modules/Partner/Livewire/Partner/Index.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Pagination renders a bounded number of controls regardless of total pages; navigation still works.

### P1-10 Add Migration Comments And Review Schema Constraints

* Issue: `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` lacks comments on table and important enum-like columns.
* Root Cause: Migration was created before the current database documentation standard was applied.
* Business Impact: Future maintainers may misunderstand allowed status/source/type values.
* Technical Impact: Schema is less self-documenting and fresh-install review has less context.
* Proposed Solution: Add a future migration that comments important columns and only adds constraints after MySQL compatibility and enum policy are confirmed.
* Files To Change: `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` for reference only, new migration under `Modules/Partner/database/migrations/`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Schema comments exist for status/source/type fields in a safe migration; no destructive schema change is introduced without confirmation.

### P1-11 Use Shared Export Storage

* Issue: `Modules/Partner/Livewire/Partner/Index.php` writes exports to `storage/app/public/{filename}` instead of the shared export directory convention.
* Root Cause: Export was implemented locally inside Livewire.
* Business Impact: Export files may be stored inconsistently and lack retention/access policy.
* Technical Impact: Duplicates storage path logic and bypasses shared cleanup/download conventions.
* Proposed Solution: Move exports to `Modules/Partner/Services/ImportExport.php` and shared export storage under `storage/app/public/exports`.
* Files To Change: `Modules/Partner/Livewire/Partner/Index.php`, `Modules/Partner/Services/ImportExport.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: Included in P1-03.
* Acceptance Criteria: Partner exports use shared storage helpers; generated paths are consistent with the shared import/export foundation.

## 4. P2 Nice To Have Improvements

### P2-01 Fix Stale Page Comments

* Issue: Page Blade comments in `Modules/Partner/resources/views/pages/index.blade.php`, `Modules/Partner/resources/views/pages/create.blade.php`, and `Modules/Partner/resources/views/pages/edit.blade.php` refer to medicine lists instead of partners.
* Root Cause: Comments were copied from another module.
* Business Impact: None for runtime behavior.
* Technical Impact: Minor developer confusion.
* Proposed Solution: Correct or remove stale comments.
* Files To Change: `Modules/Partner/resources/views/pages/index.blade.php`, `Modules/Partner/resources/views/pages/create.blade.php`, `Modules/Partner/resources/views/pages/edit.blade.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Comments accurately describe Partner pages or are removed.

### P2-02 Remove Scaffold Placeholder Page And Components

* Issue: `Modules/Partner/resources/views/partner.blade.php`, `Modules/Partner/resources/views/components/placeholder.blade.php`, and `Modules/Partner/resources/views/livewire/placeholder.blade.php` look unused or scaffold-only.
* Root Cause: Module scaffolding generated placeholder artifacts that were not removed after real pages were added.
* Business Impact: None if truly unused; accidental routing to placeholders would look unfinished.
* Technical Impact: Dead files increase noise and route/provider ambiguity.
* Proposed Solution: Confirm no route/provider/external reference depends on them, then remove.
* Files To Change: `Modules/Partner/resources/views/partner.blade.php`, `Modules/Partner/resources/views/components/placeholder.blade.php`, `Modules/Partner/resources/views/livewire/placeholder.blade.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day after reference check.
* Acceptance Criteria: Reference search and route tests confirm removal is safe; placeholder files no longer exist.

### P2-03 Confirm Or Remove API Scaffold If Not Needed

* Issue: `Modules/Partner/routes/api.php` and `Modules/Partner/Http/Controllers/Api/PartnerController.php` may be unused scaffolding.
* Root Cause: API files were generated but not completed.
* Business Impact: A half-built API creates confusion about supported integrations.
* Technical Impact: Dead API surface conflicts with module manifest expectations.
* Proposed Solution: After P0 security fix, document whether Partner has an API. If not, remove the scaffold permanently.
* Files To Change: `Modules/Partner/routes/api.php`, `Modules/Partner/Http/Controllers/Api/PartnerController.php`, `Modules/Partner/config/module.php` only if module metadata tracks API capability.
* Risk Level: Low once P0 is handled.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Module docs and files agree on whether API support exists.

### P2-04 Consider Shared Form/Input Components

* Issue: `Modules/Partner/resources/views/livewire/partner/index.blade.php` and `Modules/Partner/resources/views/livewire/partner/form.blade.php` repeat long Tailwind input and button class strings.
* Root Cause: UI was built inline rather than using shared Blade components.
* Business Impact: None immediately.
* Technical Impact: Styling updates require repeated edits and increase drift.
* Proposed Solution: After behavior is stabilized, extract or reuse shared Blade components for common inputs/buttons if the project already has suitable components.
* Files To Change: `Modules/Partner/resources/views/livewire/partner/index.blade.php`, `Modules/Partner/resources/views/livewire/partner/form.blade.php`, possible shared component files under `Modules/Shared/resources/views/components/` only if existing conventions allow.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: UI remains visually equivalent; repeated classes are reduced; no new UI framework is introduced.

### P2-05 Review Search Indexes After Measuring Data Volume

* Issue: `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` indexes `name`, `legal_type`, `status`, and `source`, while search also uses `tax_code`, `phone`, `email`, and `contact_person`.
* Root Cause: Indexes were added for some fields before query-volume and selectivity review.
* Business Impact: Large partner lists may search slowly.
* Technical Impact: Adding indexes blindly can also hurt write performance, so this needs measurement.
* Proposed Solution: Measure query plans and real data volume, then add only useful indexes in a future migration.
* Files To Change: New migration under `Modules/Partner/database/migrations/`; reference query in `Modules/Partner/Services/PartnerService.php`.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 0.5-1 day after data review.
* Acceptance Criteria: Index changes are backed by query-plan evidence; no redundant indexes are added.

### P2-06 Revisit JSON Partner Types Only If Scale Requires It

* Issue: `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` stores `partner_types` as JSON, and `Modules/Partner/Services/PartnerService.php` filters with `whereJsonContains()`.
* Root Cause: JSON is a simple fit for shallow multi-role data, but filtering performance depends on MySQL features and data scale.
* Business Impact: Role filtering may slow down at high record counts.
* Technical Impact: Moving to a pivot table would be more complex and should not be done without reporting/filtering requirements.
* Proposed Solution: Keep JSON for now; revisit only after query metrics show a real bottleneck or partner roles gain metadata/relationships.
* Files To Change: `Modules/Partner/database/migrations/2026_05_26_095912_partners.php`, `Modules/Partner/Models/Partner.php`, `Modules/Partner/Services/PartnerService.php` only if a future schema refactor is approved.
* Risk Level: Low.
* Complexity: High if normalized later.
* Estimated Effort: No immediate work; 2-4 days if future pivot migration is approved.
* Acceptance Criteria: No schema change is made without evidence and a confirmed migration plan.

### P2-07 Confirm Phone Validation Rules

* Issue: `Modules/Partner/Livewire/Partner/Form.php` accepts any string up to 50 characters for `phone`.
* Root Cause: Business-specific phone format rules are not documented.
* Business Impact: Inconsistent contact data can reduce CRM/admin usefulness.
* Technical Impact: Validation cannot be safely tightened without confirming accepted Vietnamese/international formats.
* Proposed Solution: Document allowed phone formats before changing validation.
* Files To Change: `Modules/Partner/Livewire/Partner/Form.php`, `Modules/Partner/Services/PartnerService.php`, `Modules/Partner/Services/ImportExport.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day after business confirmation.
* Acceptance Criteria: Phone validation matches confirmed business rules and applies consistently to form and import paths.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. Fix or remove the public broken API surface in `Modules/Partner/routes/api.php` and `Modules/Partner/Http/Controllers/Api/PartnerController.php`.
2. Add server-side permission checks to `Modules/Partner/Livewire/Partner/Index.php` for delete, bulk delete, import, and export.
3. Add server-side permission checks to `Modules/Partner/Livewire/Partner/Form.php` for create and update.
4. Disable unsafe nullable-tax-code import upsert in `Modules/Partner/Livewire/Partner/Index.php` until import behavior is confirmed.

### Phase 2: Correctness and Maintainability

1. Create `docs/modules/Partner/REBUILD_SPEC.md` for confirmed import/export mapping, unique key, mode, dry-run, null-overwrite, transaction behavior, and export columns.
2. Create `Modules/Partner/Services/ImportExport.php` and migrate import/export out of `Modules/Partner/Livewire/Partner/Index.php`.
3. Centralize Partner query construction, filtered IDs, bounded pagination, and bulk delete in `Modules/Partner/Services/PartnerService.php`.
4. Add service-level invariants for enum-like fields in `Modules/Partner/Services/PartnerService.php`.
5. Align edit parameter naming between `Modules/Partner/resources/views/pages/edit.blade.php` and `Modules/Partner/Livewire/Partner/Form.php`.

### Phase 3: Performance and Cleanup

1. Replace full page range pagination in `Modules/Partner/resources/views/livewire/partner/index.blade.php`.
2. Add schema comments through a safe future migration under `Modules/Partner/database/migrations/`.
3. Review indexes for search fields after query-plan measurement.
4. Remove confirmed unused placeholder files in `Modules/Partner/resources/views/`.
5. Clean stale comments and consider shared UI components after behavior is stable.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/Partner/routes/api.php` | Modify or remove | P0 | Public broken API route without authentication or valid controller action. |
| `Modules/Partner/Http/Controllers/Api/PartnerController.php` | Modify or remove | P0 | Empty controller referenced by API route. |
| `Modules/Partner/Livewire/Partner/Index.php` | Modify | P0/P1 | Add authorization; remove unsafe import key; move queries/import/export/bulk delete to services. |
| `Modules/Partner/Livewire/Partner/Form.php` | Modify | P0/P1/P2 | Add create/update authorization; align parameter naming; apply consistent validation boundaries. |
| `Modules/Partner/config/module.php` | Modify if needed | P0/P2 | Declare missing import/export permissions if the permission system requires module metadata. |
| `Modules/Partner/Services/PartnerService.php` | Modify | P1/P2 | Centralize queries, filtered IDs, bulk delete, bounded pagination, and service invariants. |
| `Modules/Partner/Services/ImportExport.php` | Create | P1 | Provide module import/export entry point using shared foundation. |
| `docs/modules/Partner/REBUILD_SPEC.md` | Create later | P1 | Confirm import/export mapping and data behavior before implementation. |
| `Modules/Partner/resources/views/livewire/partner/index.blade.php` | Modify | P1/P2 | Replace custom import/export controls, bound pagination rendering, and optionally reduce repeated classes. |
| `Modules/Partner/resources/views/livewire/partner/form.blade.php` | Modify | P2 | Optional UI component cleanup after behavior stabilizes. |
| `Modules/Partner/resources/views/pages/edit.blade.php` | Modify | P1/P2 | Align edit parameter naming and remove stale comment. |
| `Modules/Partner/resources/views/pages/index.blade.php` | Modify | P2 | Remove stale medicine comment. |
| `Modules/Partner/resources/views/pages/create.blade.php` | Modify | P2 | Remove stale medicine comment. |
| `Modules/Partner/resources/views/partner.blade.php` | Remove after confirmation | P2 | Scaffold page appears unused. |
| `Modules/Partner/resources/views/components/placeholder.blade.php` | Remove after confirmation | P2 | Placeholder only used by scaffold page. |
| `Modules/Partner/resources/views/livewire/placeholder.blade.php` | Remove after confirmation | P2 | Placeholder appears unused. |
| `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` | Reference only | P0/P1/P2 | Existing schema explains nullable tax-code risk and missing comments/index review. |
| `Modules/Partner/database/migrations/` | Create new migration later | P1/P2 | Add comments or measured indexes without editing historical migration unless project policy allows. |
| `Modules/Partner/Models/Partner.php` | Modify if needed | P1/P2 | Support import/export exclusions or future validated constants; avoid business logic. |

## 7. Risk Control

Do not change unrelated modules. `Modules/Partner` is the canonical owner for Partner CRUD and the `partners` table; shared import/export code may be reused, but Partner business rules should remain in `Modules/Partner`.

Do not implement import/export until these decisions are confirmed: unique key, behavior for missing `tax_code`, import mode, dry-run behavior, null-overwrite behavior, all-or-nothing versus partial import, header aliases, export columns, and queue requirements.

Do not normalize `partner_types` into a pivot table yet. The current JSON field is acceptable until query metrics or business rules prove it is insufficient.

Do not add speculative indexes. Review data volume and query plans first.

Do not remove placeholder files until route/provider/reference checks confirm they are unused.

Do not edit historical migrations for an already-applied production database unless the project migration policy allows it. Prefer additive migrations for comments, constraints, and indexes.

Do not rely on UI disabled states, hidden buttons, or route access for authorization. Every mutating Livewire action and export/import path must enforce server-side permissions.

Do not introduce DTOs, Bootstrap, jQuery, direct model queries in Livewire, or new app-level business classes outside `Modules/Partner`.
