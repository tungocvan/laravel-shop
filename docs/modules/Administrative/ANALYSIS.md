# Administrative Module Analysis

## Executive Summary

`Modules/Administrative` là domain module tiếp nhận và xử lý hồ sơ hành chính công khai, không yêu cầu người nộp đăng nhập. Source hiện tại đã có nền tảng tốt: route/controller mỏng, Livewire class-based, service layer rõ ràng, private storage, lookup token chỉ lưu hash, rate limiting, audit trạng thái, transaction, row locking và optimistic versioning.

Kết luận: **Major Refactor**, không cần Full Rebuild. Lý do là kiến trúc lõi đúng hướng và có thể giữ nguyên, nhưng tồn tại một lỗi correctness ở luồng tải file admin, một số contract permission chưa nhất quán, truy vấn `All` không bounded, thao tác archive hồ sơ thiếu audit domain, và automated tests hiện chủ yếu khóa route/schema chứ chưa bảo vệ đầy đủ service/Livewire workflow quan trọng.

Task này chỉ phân tích và cập nhật tài liệu. Không sửa application source.

## Module Purpose and Overview

Luồng nghiệp vụ chính:

```text
Thủ tục hành chính
    -> Public xem thủ tục / tải biểu mẫu
    -> Nộp hồ sơ + file
    -> Pending
       -> Approved
       -> Rejected
       -> Need Supplement
            -> Public bổ sung file
            -> Pending
```

Public lookup dùng `submission_code` + mã bí mật. Mã bí mật chỉ lưu dạng hash; sau khi xác minh thành công, hệ thống cấp access token ngẫu nhiên gắn với session trong 15 phút.

Admin quản lý thủ tục, danh sách hồ sơ, chi tiết, tải file, phê duyệt, từ chối, yêu cầu bổ sung, lịch sử trạng thái và soft-delete/archive hồ sơ.

## Bootstrap / Standards Context

Repository hiện dùng:

- Laravel 12, PHP 8.3.
- Livewire 3 class-based.
- First-party modular monolith dưới `Modules/`.
- Module registration do `Modules\ModuleServiceProvider` thực hiện; không dùng `nwidart/laravel-modules`.
- Spatie Permission cho authorization.
- Admin UI theo canonical `ADMIN_UI_STANDARD.md`.

`Administrative` có `config/module.php`, type `domain`, được auto-register routes, views, migrations và Livewire theo `Modules\ModuleServiceProvider`.

## Dependency Graph

```text
Public Route
 -> PublicProcedureController / PublicLookupController
 -> Page Blade
 -> Public Livewire
 -> ProcedureService / SubmissionService / LookupService / ReceiptService
 -> Administrative models
 -> administrative_* tables
 -> private Storage / Mail / PDF

Admin Route
 -> ProcedureController / SubmissionController
 -> Page Blade
 -> ProcedureForm / ProcedureTable / SubmissionTable / SubmissionDetail
 -> ProcedureService / SubmissionService / AdministrativeFileService
 -> Administrative models
 -> administrative_* tables

Cross-module
 AdministrativeSubmission.processed_by
 -> Modules\Account\Models\User
 -> users table
```

No import/export implementation is present in this module.

## Route / Controller / Blade / Livewire Analysis

### Routes

Public routes are separated into:

- `/thu-tuc-hanh-chinh`
- `/tra-cuu-ho-so`

Sensitive public routes use throttling and no-store/private cache headers where appropriate. Lookup access tokens and receipt tokens use strict regex constraints.

Admin routes use `web`, `auth:admin` and named permissions. Procedure and submission areas are separated cleanly.

Finding: `config/module.php` declares `administrative.dashboard.view`, but dashboard route currently requires `administrative.submission.view`; the dedicated dashboard permission is therefore not the actual route boundary.

### Controllers

Controllers are thin and generally compliant with `MODULE_STANDARD.md`. Public controller delegates list/download/receipt workflows to services and only performs simple active-state checks and session receipt lookup.

### Page Blade

Page views are small shells that compose Livewire components. No database-query responsibility was observed in the inspected page layer.

### Livewire

Components are class-based and use explicit mutation authorization.

Observed components:

- `Procedures/ProcedureForm.php`
- `Procedures/ProcedureTable.php`
- `Public/LookupForm.php`
- `Public/PublicHeader.php`
- `Public/SubmissionForm.php`
- `Public/SupplementForm.php`
- `Submissions/SubmissionDetail.php`
- `Submissions/SubmissionTable.php`

Strengths:

- Sensitive mutation methods authorize inside the Livewire action.
- Procedure form validates unique code/slug, upload type/size, allowed extensions, limits and booleans.
- Locked IDs/version fields reduce client-side tampering risk.
- Pagination is used by admin tables.

Concerns:

- `SubmissionDetail` uses `administrative.submission.edit` for approve/reject/request-supplement, while manifest also declares `administrative.submission.process`. The capability naming/usage is ambiguous.
- `administrative.history.view` exists in the manifest, but the inspected detail workflow loads history as part of normal submission view rather than enforcing a distinct history capability.
- Both procedure and submission tables expose `perPage = 'All'`.

## Service Analysis

### `ProcedureService`

Responsibilities:

- public/admin procedure queries
- create/update
- active state
- archive
- template private storage/download
- normalization

Good practices:

- transactions for writes
- `lockForUpdate()` during updates/state changes
- old template cleanup after successful replacement
- procedures with submissions cannot be archived; they must be deactivated instead

Concern: admin listing supports `All`, resulting in unbounded `get()`.

### `SubmissionService`

Responsibilities:

- initial public submission
- validation orchestration with file service
- admin list/stats/detail
- approve/reject/request supplement
- public resubmission
- soft-delete/archive many submissions

Strong implementation areas:

- submit throttled by IP + normalized phone key
- transaction around submission + files + history
- cleanup stored files after failed transaction
- lookup token stored via `Hash::make()`
- status transitions use row lock + expected `version`
- concurrent admin processing is rejected safely
- resubmission returns status to `pending` and increments revision/version
- status history is written with transitions

Concern: `softDeleteMany()` soft-deletes selected records inside a transaction but does not write a status/audit history entry for the destructive/archive action.

### `AdministrativeFileService`

Good practices:

- private disk from server config
- extension and MIME allowlist
- size/count validation
- generated UUID storage names
- SHA-256 checksum
- controlled server path prefix for cleanup
- download validates submission/file ownership

Correctness defect: `downloadForAdmin()` calls `AdministrativeFile::query()` but the class does not import `Modules\Administrative\Models\AdministrativeFile`. In namespace `Modules\Administrative\Services`, PHP will resolve this as `Modules\Administrative\Services\AdministrativeFile`, which does not exist in the inspected Services tree. Admin file download can therefore fail at runtime.

### `LookupService`

Security posture is good:

- uniform invalid-lookup message
- per-code and per-IP rate limiting
- secret hash verification
- random 32-byte access token
- session-bound access grant with 15-minute expiry
- result file constrained to current submission and `Result` file type
- controlled Storage download

### Receipt / Branding

Present as dedicated services; no import/export responsibilities detected.

## Import / Export Analysis

**Not present.**

This module currently manages uploaded administrative files and generated receipt/status outputs, not spreadsheet import/export. Do not add shared import/export infrastructure unless a future business requirement introduces it.

## Shared Dependencies

Direct cross-module dependency observed:

```text
Modules\Administrative\Models\AdministrativeSubmission
 -> Modules\Account\Models\User
```

This is used for `processed_by` / processor relationship. The dependency should be documented explicitly in the module manifest/docs. The current `config/module.php` does not declare `depends => ['Account']` even though the model imports the Account-owned user model.

## Model / Migration / Database Analysis

Models:

- `AdministrativeProcedure`
- `AdministrativeSubmission`
- `AdministrativeFile`
- `AdministrativeStatusHistory`

Tables:

- `administrative_procedures`
- `administrative_submissions`
- `administrative_files`
- `administrative_status_histories`

Submission model:

- uses SoftDeletes
- hides `lookup_token_hash`
- casts status to `SubmissionStatus`
- casts dates/version/revision fields
- defines procedure/files/history/processor relationships

Submission migration provides:

- FK `procedure_id` with restrict-on-delete
- unique `submission_code`
- nullable processor FK with null-on-delete
- optimistic `version`
- indexes for status/date, procedure/status/date, phone, email, student name/code

The admin search uses leading-wildcard `%keyword%` across multiple columns; normal B-tree indexes generally cannot optimize that pattern effectively at scale. This is a future performance concern rather than a current correctness failure.

## Security

Strengths:

- admin guard + named permissions at route boundaries
- mutation authorization inside Livewire
- Super Admin handled centrally by repository Gate
- private storage
- controlled downloads rather than public URLs
- upload extension + MIME + size validation
- lookup token hash instead of plaintext persistence
- throttling on public submission/lookup/download endpoints
- receipt/lookup cache headers use private/no-store where needed
- sensitive lookup hash hidden from model serialization

Risks/gaps:

1. Permission contract drift (`dashboard.view`, `submission.process`, `history.view`) can make role configuration misleading.
2. Soft-delete/archive of submissions is destructive from normal user visibility but has no module audit history event.
3. Admin download correctness defect should be fixed before considering file access verified.

No evidence of arbitrary path input, public storage exposure, raw SQL injection or secret persistence was found in the inspected module flow.

## Performance

Material findings:

- `ProcedureService::listForAdmin(..., 'All')` can call unbounded `get()`.
- `SubmissionService::listForAdmin(..., 'All')` can call unbounded `get()`.
- submission search uses multiple leading-wildcard LIKE predicates.
- public procedure list uses `get()`; likely small reference data, but production cardinality should remain bounded/monitored.

Eager loading is used for key admin list/detail relationships, reducing obvious N+1 risk in inspected queries.

## Validation and Authorization

Validation is generally strong at Livewire/service boundary. Upload rules exist both at Livewire configuration level and inside `AdministrativeFileService`, which is appropriate because file-service invariants also protect non-UI callers.

Authorization is duplicated intentionally at route and Livewire mutation boundaries. The main concern is semantic consistency of declared permission names rather than absence of authorization.

## Transactions, Concurrency and Data Integrity

This is one of the module's strongest areas.

- Multi-record submission creation is transactional.
- Failed file/database workflows clean up stored files.
- Admin state transitions lock the row.
- Expected version is checked before mutation.
- Supplement resubmission also validates status + version under lock.
- Status transitions create history records in the same transaction.

The design directly addresses the documented two-admin concurrency scenario.

## Admin UI / UX Standard Review

The module follows the repository page-shell + Livewire pattern and separates procedure management from submission processing.

Positive:

- list/detail/form surfaces are separated
- filters/search/pagination exist
- destructive procedure archive is confirmation-driven at component state level
- status workflow is focused in submission detail

Improvements:

- remove or bound the `All` option rather than allowing production-scale unbounded lists
- ensure destructive submission archive has a clear confirmation and audit trail
- keep approve/reject/supplement as the dominant detail workflow; secondary history/files should not visually overpower processing
- verify responsive table overflow, empty/loading states, disabled states and actual rendered desktop/mobile proportions during refactor; static source inspection cannot prove visual quality

## Cross-Module Dependencies

Observed:

- `Administrative -> Account` through `Modules\Account\Models\User`.
- Admin shell/layout and Spatie permission infrastructure are repository-level dependencies.

Potential manifest drift: `config/module.php` does not currently declare the Account dependency even though source uses it.

No circular dependency was identified from the inspected source.

## Technical Debt

- Missing model import in `AdministrativeFileService`.
- Permission declarations and actual use need reconciliation.
- `All` list option violates bounded-query standard.
- Submission archive lacks domain audit history.
- rejection reason codes and relationship options are partly string-based; consider enum/config consolidation only when business vocabulary is stable.
- module manifest should document direct Account dependency.

## Test Coverage

Automated tests are present under `tests/Feature/Administrative`:

- `AdministrativeDatabaseStructureTest.php`
- `AdministrativeLookupRouteTest.php`
- `AdministrativeProcedureRouteTest.php`
- `AdministrativePublicRouteTest.php`
- `AdministrativeSubmissionRouteTest.php`

This corrects older documentation that said tests were unverified.

Observed route test coverage checks URI, `auth:admin`, named permissions and numeric constraints.

Coverage gap: no evidence in the inspected test set of dedicated service/Livewire tests for:

- admin file download service resolution/ownership
- actual upload MIME rejection and cleanup
- approve/reject/request-supplement state transitions
- optimistic concurrency conflict
- public lookup hash verification/session expiry
- supplement resubmission
- soft-delete authorization/audit behavior
- queued email/PDF behavior

Tests were inspected from repository source; they were **not executed** by this GitHub-only analysis.

## Documentation Drift

Previous docs correctly described architecture and primary workflow but were too brief for the current `/analyze` contract.

Confirmed drift:

- old `ANALYSIS.md` said dedicated automated tests were not verified; five Administrative feature test files now exist.
- cross-module Account dependency was not fully documented.
- permission contract inconsistencies were not documented.
- admin file download missing import was not documented.
- current docs did not enumerate all required analysis sections or priority findings.

## Issue List (P0/P1/P2)

### P0

No confirmed P0 issue was found in the inspected module source.

### P1-01 — Admin file download can resolve the wrong class

**Priority:** P1  
**File:** `Modules/Administrative/Services/AdministrativeFileService.php`  
**Evidence:** `downloadForAdmin()` calls `AdministrativeFile::query()` without importing the model; no `AdministrativeFile` class exists in the Services tree.  
**Problem:** namespace resolution points to `Modules\Administrative\Services\AdministrativeFile`.  
**Impact:** authorized admin file download can fail at runtime.  
**Recommendation:** import `Modules\Administrative\Models\AdministrativeFile` and add a targeted download test.

### P1-02 — Permission contract is internally inconsistent

**Priority:** P1  
**Files:** `Modules/Administrative/config/module.php`, `routes/web.php`, `Livewire/Submissions/SubmissionDetail.php`  
**Evidence:** manifest declares `dashboard.view`, `submission.process`, `submission.edit`, `history.view`; dashboard uses `submission.view`, processing actions use `submission.edit`, and history is loaded under submission view.  
**Problem:** declared capabilities do not map cleanly to enforcement boundaries.  
**Impact:** role assignment can grant permissions that have no effect or unintentionally require a broader/different permission.  
**Recommendation:** define one canonical permission matrix and align route + Livewire + menu/seed behavior without silently renaming public contracts.

### P1-03 — Unbounded `All` admin queries

**Priority:** P1  
**Files:** `ProcedureService.php`, `SubmissionService.php`, both admin table Livewire components  
**Evidence:** selecting `All` returns `$query->get()`.  
**Problem:** user-controlled list size can grow without bound.  
**Impact:** memory/response-time degradation and production instability with large datasets.  
**Recommendation:** remove `All`, cap it, or implement bounded export/streaming for bulk use cases.

### P1-04 — Submission archive lacks domain audit history

**Priority:** P1  
**File:** `SubmissionService.php`  
**Evidence:** `softDeleteMany()` locks and soft-deletes records but does not append `AdministrativeStatusHistory`.  
**Problem:** destructive visibility change is not represented in the module's audit trail.  
**Impact:** weaker accountability/recovery investigation for administrative records.  
**Recommendation:** define archive semantics and write immutable audit metadata including actor, IDs and timestamp; preserve soft delete.

### P1-05 — Critical workflow tests are incomplete

**Priority:** P1  
**Path:** `tests/Feature/Administrative`  
**Evidence:** five tests exist, but inspected set is route/schema oriented and does not demonstrate service/Livewire workflow coverage listed above.  
**Problem:** concurrency, file security and state transitions can regress without targeted tests.  
**Impact:** refactoring risk remains high despite route boot coverage.  
**Recommendation:** add focused service/Livewire/security regression tests before structural refactor.

### P2-01 — Search scalability

**Priority:** P2  
**File:** `SubmissionService.php`  
**Evidence:** multiple `%keyword%` predicates across submission fields.  
**Problem:** leading wildcard search scales poorly on standard indexes.  
**Impact:** slower admin search as records grow.  
**Recommendation:** profile first; only then consider prefix search, normalized search fields, fulltext/index strategy or dedicated search infrastructure.

### P2-02 — Manifest dependency drift

**Priority:** P2  
**Files:** `config/module.php`, `Models/AdministrativeSubmission.php`  
**Evidence:** model imports `Modules\Account\Models\User`, manifest does not declare `depends`.  
**Problem:** runtime dependency is not expressed in module graph metadata.  
**Impact:** disabling/removing Account can break Administrative without graph validation catching it.  
**Recommendation:** declare `Account` dependency if this is the canonical user owner for the project.

## Module Health Summary

| Area | Assessment |
|---|---|
| Architecture | Good |
| Domain workflow | Good |
| Security | Good, with permission contract cleanup needed |
| File handling | Good design, one confirmed correctness defect |
| Transactions/concurrency | Strong |
| Database design | Good |
| Performance | Needs bounded admin lists |
| Admin UI structure | Good baseline; visual verification still required |
| Tests | Present but insufficient for critical workflow refactor |
| Documentation | Updated by this analysis |

## Final Recommendation

**Major Refactor**.

Do not rebuild the module. Preserve routes, tables, storage paths, models and core workflow. Refactor in small phases, starting with correctness + permission contract + tests, then bounded queries/audit/UI polish.

Recommended order for a later `/refactor-module Administrative` task:

```text
P1-01 file download correctness
-> P1-02 permission matrix
-> P1-05 critical regression tests
-> P1-03 bounded list behavior
-> P1-04 archive audit trail
-> P2 performance/dependency/UI polish
```

## Open Questions / Unknowns

- Tests were not executed in this GitHub-only analysis; Local/CI must confirm their current pass/fail state.
- Actual rendered desktop/mobile UI was not visually inspected; screenshot review is required before declaring ADMIN_UI_STANDARD quality gate complete.
- Queue worker, mail transport and PDF generation behavior were not runtime-tested.
- Production dataset cardinality is unknown, so search-performance impact is an evidence-based risk, not a measured production bottleneck.
- Confirm whether `Modules\Account\Models\User` is the intended permanent canonical owner for admin processor relationships before adding manifest dependency.
