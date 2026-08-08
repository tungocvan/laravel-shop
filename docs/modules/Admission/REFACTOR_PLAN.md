# Admission Refactor Plan

## 1. Executive Summary

Admission should receive a major refactor focused on safety first: module registration, broken routes, schema correctness, authorization, validation, and scalable import/export. The existing domain structure can be preserved while responsibilities are tightened around service methods, policies, validation boundaries, and queued document/import/export workflows.

Refactor update 2026-06-23: The first safety slice has been implemented. Admission is enabled, the conflicting `/admin` dashboard route was moved to `admin.admission.dashboard`, the API stub now returns an intentional 501 response, admin routes now require named permissions, key admin Livewire mutations enforce permissions, import file validation was added, the missing controller export method was added, approval/rejection metadata fields were added through a new migration, the permission seeder now reads lowercase module config files, and focused route configuration tests were added.

## 2. P0 Critical Fixes

### Enable or Explicitly Disable Module With Documentation

Status: Implemented 2026-06-23.

- Issue: `Modules/Admission/config/module.php` has `enabled => false`.
- Root Cause: Manifest disables automatic module boot.
- Business Impact: Admission features may be unavailable.
- Technical Impact: Routes, views, migrations, Livewire aliases, and config may not register.
- Proposed Solution: Confirm intended state. If Admission is active, set enabled to true and add module boot tests. If inactive, document replacement workflow.
- Files To Change: `Modules/Admission/config/module.php`, tests to be added.
- Risk Level: High.
- Complexity: Low.
- Estimated Effort: 0.5 day.
- Acceptance Criteria: Module boot state is intentional and covered by a test.

### Fix API Route Contract

Status: Implemented 2026-06-23.

- Issue: `/api/admission` targets missing `index()` method.
- Root Cause: Stub API controller was routed.
- Business Impact: API endpoint fails.
- Technical Impact: Runtime method error.
- Proposed Solution: Remove route or implement `index()` with authentication/rate limiting and response contract.
- Files To Change: `Modules/Admission/routes/api.php`, `Modules/Admission/Http/Controllers/Api/AdmissionController.php`.
- Risk Level: Medium.
- Complexity: Low.
- Estimated Effort: 0.5 day.
- Acceptance Criteria: GET `/api/admission` returns intentional response or route no longer exists.

### Repair Approval Metadata Persistence

Status: Implemented 2026-06-23.

- Issue: Livewire writes approval/rejection metadata columns that do not exist.
- Root Cause: Workflow code and schema diverged.
- Business Impact: Approval/rejection may fail or lose audit data.
- Technical Impact: SQL errors or mass-assignment mismatch.
- Proposed Solution: Add approved/rejected metadata columns and fillable/casts, or move workflow history to audit table.
- Files To Change: `Modules/Admission/Livewire/Admin/Applications/Index.php`, `Modules/Admission/Models/AdmissionApplication.php`, migration files or new migration.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 1 day.
- Acceptance Criteria: Approve/reject works, metadata is persisted, and tests cover both transitions.

### Enforce Authorization on Admin Actions

Status: Partially implemented 2026-06-23. Route middleware and key Livewire admin mutation checks are in place. Record-level policies and full document/import/export audit coverage remain recommended.

- Issue: Mutating and sensitive actions rely on `auth:admin` only.
- Root Cause: Declared permissions are not enforced.
- Business Impact: Unauthorized admins can alter or export sensitive admission data.
- Technical Impact: Permission system is bypassed.
- Proposed Solution: Add policies/permissions for view/create/edit/delete/import/export/approve/reject/download/location management.
- Files To Change: `Modules/Admission/routes/web.php`, `Modules/Admission/Http/Controllers/AdmissionController.php`, `Modules/Admission/Livewire/Admin/Applications/Index.php`, `Modules/Admission/Livewire/Dvhc.php`, policy files.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: Denied users cannot perform actions directly through routes or Livewire calls.

## 3. P1 Important Refactors

### Complete Registration and Admin Validation

- Issue: Registration validates only name and identity number.
- Root Cause: No full validation map or DTO boundary.
- Business Impact: Invalid data enters official admission records.
- Technical Impact: Parse errors and inconsistent exports/documents.
- Proposed Solution: Add step-aware Livewire rules and service-level invariants for status, dates, phones, class type, guardian fields, and arrays.
- Files To Change: `Modules/Admission/Livewire/Public/RegistrationForm.php`, `Modules/Admission/Services/AdmissionService.php`.
- Risk Level: Medium.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: Invalid data is rejected with user-friendly errors and service tests pass.

### Remove Sensitive Search Credentials From URL

- Issue: Identity number and birth-date password are route parameters.
- Root Cause: QR/search shortcut encodes credentials in URL.
- Business Impact: Personal lookup credentials can leak through logs/history/referrers.
- Technical Impact: Privacy risk.
- Proposed Solution: Use POST lookup, signed short-lived token, or opaque receipt token.
- Files To Change: `Modules/Admission/routes/web.php`, `Modules/Admission/Livewire/Search.php`, `Modules/Admission/Services/AdmissionService.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 1 day.
- Acceptance Criteria: Search no longer requires sensitive credentials in URL and is rate limited.

### Standardize Import Path

- Issue: Two import implementations exist; controller uses the generic one.
- Root Cause: Module import was not wired or was superseded.
- Business Impact: Admins get weak import feedback.
- Technical Impact: Duplicated behavior and memory risk.
- Proposed Solution: Choose canonical import class, add file validation, row validation, chunking, and error report.
- Files To Change: `Modules/Admission/Http/Controllers/AdmissionController.php`, `Modules/Admission/Imports/ApplicationsImport.php`, `App/Services/Data/Import/GenericImport.php`.
- Risk Level: Medium.
- Complexity: High.
- Estimated Effort: 2-3 days.
- Acceptance Criteria: Import validates file/rows, handles duplicates, and reports rejected rows.

### Make Export Bounded and Explicit

- Issue: Export loads all records into memory and uses dynamic schema headings.
- Root Cause: `FromCollection` with `get()`.
- Business Impact: Large exports can fail.
- Technical Impact: Memory/timeouts.
- Proposed Solution: Use explicit columns/headings and query/queued export strategy.
- Files To Change: `Modules/Admission/Exports/ApplicationsExport.php`, `Modules/Admission/Livewire/Admin/Applications/Index.php`.
- Risk Level: Medium.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: Export has stable columns and can handle large datasets.

### Normalize Persistence Formats

- Issue: Date and array values are stored inconsistently.
- Root Cause: Display formatting is mixed with persistence mapping.
- Business Impact: Incorrect printed/search data.
- Technical Impact: Cast and database inconsistencies.
- Proposed Solution: Store dates as `Y-m-d`; convert array fields to JSON columns or store strings deliberately.
- Files To Change: `Modules/Admission/Services/AdmissionService.php`, `Modules/Admission/Models/AdmissionApplication.php`, `Modules/Admission/database/migrations/2026_04_21_200923_create_applications_table.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: Existing records are migrated safely and tests confirm date/array round-trips.

### Make MHS Generation Concurrency Safe

- Issue: `mhs` uses `count() + 1`.
- Root Cause: Non-atomic code generation.
- Business Impact: Concurrent registrations can fail.
- Technical Impact: Unique constraint violations.
- Proposed Solution: Use atomic sequence/counter or retry-safe generated code.
- Files To Change: `Modules/Admission/Services/AdmissionService.php`, possible new sequence table/migration.
- Risk Level: Medium.
- Complexity: Medium.
- Estimated Effort: 1 day.
- Acceptance Criteria: Concurrent create test cannot produce duplicate `mhs`.

## 4. P2 Nice To Have Improvements

### Remove Confirmed Dead Artifacts

- Issue: Placeholder model and unused edit view/import path may confuse maintainers.
- Root Cause: Scaffold and refactor leftovers.
- Business Impact: Low.
- Technical Impact: Extra maintenance noise.
- Proposed Solution: Remove only after reference scan and route/view confirmation.
- Files To Change: `Modules/Admission/Models/Admission.php`, `Modules/Admission/resources/views/pages/admin/edit.blade.php`, unused import class if not canonical.
- Risk Level: Low.
- Complexity: Low.
- Estimated Effort: 0.5 day.
- Acceptance Criteria: No references remain and route/view tests pass.

### Add Cache for Stable Data

- Issue: Dashboard, catalogs, and locations are queried repeatedly.
- Root Cause: No cache policy.
- Business Impact: Low to medium as data grows.
- Technical Impact: Avoidable DB load.
- Proposed Solution: Cache catalogs/location lists/dashboard counts with invalidation on writes.
- Files To Change: `Modules/Admission/Livewire/Public/RegistrationForm.php`, `Modules/Admission/Livewire/Admin/Dashboard/StatsOverview.php`, model/service write paths.
- Risk Level: Low.
- Complexity: Medium.
- Estimated Effort: 1 day.
- Acceptance Criteria: Cached values invalidate correctly after writes.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. Confirm and fix module enabled state.
2. Fix or remove API route.
3. Add permission enforcement to routes/controllers/Livewire.
4. Repair approval/rejection schema mismatch.
5. Remove sensitive search credentials from URL or protect with an opaque token.

### Phase 2: Correctness and Maintainability

1. Add complete validation boundaries.
2. Normalize date and array persistence.
3. Make `mhs` generation concurrency safe.
4. Move status transitions into service methods.
5. Add route, service, Livewire, and authorization tests.

### Phase 3: Performance and Cleanup

1. Refactor import to chunked/queued flow.
2. Refactor export to explicit query/queued flow.
3. Add indexes for search/filter columns.
4. Cache stable dashboard/catalog/location data.
5. Remove confirmed dead artifacts.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|----------|-------------|----------|--------|
| `Modules/Admission/config/module.php` | Config | P0 | Confirm enabled state. |
| `Modules/Admission/routes/api.php` | Route | P0 | Missing API controller method. |
| `Modules/Admission/Http/Controllers/Api/AdmissionController.php` | Controller | P0 | Implement or remove API contract. |
| `Modules/Admission/routes/web.php` | Route/security | P0 | Add permission middleware and improve search route. |
| `Modules/Admission/Http/Controllers/AdmissionController.php` | Controller/security | P0/P1 | Authorization, import validation, document errors. |
| `Modules/Admission/Livewire/Admin/Applications/Index.php` | Livewire/security | P0/P1 | Authorization, schema mismatch, bounded export. |
| `Modules/Admission/Livewire/Dvhc.php` | Livewire/security | P0 | Authorization and validation for location writes. |
| `Modules/Admission/Livewire/Public/RegistrationForm.php` | Livewire/validation | P1 | Complete validation and status boundary. |
| `Modules/Admission/Livewire/Search.php` | Livewire/security | P1 | Fix lookup bugs and remove URL credential flow. |
| `Modules/Admission/Services/AdmissionService.php` | Service | P1 | Date normalization, MHS generation, transactions. |
| `Modules/Admission/Models/AdmissionApplication.php` | Model | P0/P1 | Fillable/casts/events and approval metadata. |
| `Modules/Admission/database/migrations/2026_04_21_200923_create_applications_table.php` | Database | P0/P1 | Approval fields, indexes, JSON/date consistency. |
| `Modules/Admission/Imports/ApplicationsImport.php` | Import | P1 | Canonical import, validation, chunking. |
| `App/Services/Data/Import/GenericImport.php` | Shared import | P1 | If kept, add chunk/error/redaction strategy. |
| `Modules/Admission/Exports/ApplicationsExport.php` | Export | P1 | Query/queued export and explicit headings. |
| `Modules/Admission/Jobs/GenerateAdmissionPdfJob.php` | Job | P1/P2 | Document generation resilience and observability. |

## 7. Risk Control

- Do not change live data formats without migration/backfill plan.
- Do not enable public search changes without confirming current QR/receipt workflow.
- Do not remove `ApplicationsImport` or `GenericImport` until canonical import path is confirmed.
- Do not remove `Admission.php` placeholder until a full reference scan is included in CI.
- Preserve existing route names where possible for admin UI links.
- Back up admission data before changing date/JSON column formats.
- Verify queue workers and LibreOffice before changing document generation behavior.
- Permission changes must include Super Admin compatibility through existing `Gate::before`.

## 8. Acceptance Criteria Summary

- Module boot behavior is intentional and tested.
- No Admission route points to a missing method.
- Admin users need explicit permissions for sensitive actions.
- Approve/reject persists valid status and audit metadata.
- Registration rejects invalid data.
- Import validates files and reports row errors.
- Export can handle large data safely.
- Search no longer leaks credentials through URLs or is explicitly accepted by product/security owners. Needs confirmation before coding.
- Admission has feature, Livewire, service, import/export, authorization, and job tests.
