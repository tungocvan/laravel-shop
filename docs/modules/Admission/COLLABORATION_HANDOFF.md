# Admission — Collaboration Handoff

## Current objective

Compact major refactor of `Modules/Admission`, keeping the module intentionally small and centered on grade-1 registration, public lookup, and focused admin handling.

Branch: `refactor/admission-major-cleanup`

Status: implementation complete and ready for PR review.

## Approved scope

The user approved combining the originally proposed phases into one implementation branch to reduce repeated pull/test cycles.

Completed work in this branch:

- established `MODULE.md` architecture and ownership contract;
- repaired route/controller drift, including the missing Admission export action;
- consolidated duplicated DOCX/PDF generation behind `AdmissionDocumentService`;
- made `/admission/search` the canonical clean lookup route and retained the credential-path route only as a compatibility redirect;
- stopped reflecting identifier/date credentials into the public lookup component/view;
- hardened public lookup validation and failure handling;
- preserved persistence-sensitive/shared queue boundaries without destructive schema edits;
- aligned affected Admission tests with the new service and storage contracts;
- normalized Admission PHP formatting with Pint.

## Canonical runtime after refactor

Admission owns:

- grade-1 admission registration;
- public admission lookup;
- application administration/review;
- Admission settings/catalogs/location data;
- import/export;
- application-specific documents/receipts.

Key canonical boundaries:

- `AdmissionRegistrationService` owns registration orchestration;
- `AdmissionApplicationAdminService` owns admin application workflows/export/batch dispatch;
- `AdmissionDocumentService` owns Admission document naming/generation/path persistence;
- shared `DocumentConverterService` remains the generic DOCX/PDF conversion engine;
- `GenerateAdmissionPdfJob` is queue orchestration only;
- Admin shell, auth/permissions and shared queue infrastructure remain outside Admission ownership.

## Compatibility / quarantined debt

The following are intentionally not destructively removed in this branch:

1. The historical credential-path lookup route remains as `admission.search.legacy`, but redirects to the clean search form and no longer auto-populates credentials.
2. The Admission API remains a `501 Not Implemented` placeholder pending caller/contract proof before removal.
3. The historical Admission migration that conditionally creates shared `job_batches` remains quarantined; shared queue infrastructure must not be removed without migration-ledger/runtime proof.
4. Location/DVHC ownership remains Admission-owned pending future cross-module caller proof.

## Safety decisions

- No destructive schema cleanup was performed.
- Registration/application data and review metadata were preserved.
- Existing Admission permission middleware boundaries were preserved.
- Shared document conversion and queue infrastructure were reused rather than duplicated or moved into Admission.

## Final verification

Automated regression:

- `php artisan test tests/Feature/Admission`
- result: **50 passed (263 assertions)**
- duration reported locally: **1.76s**

Formatting:

- Pint executed over `Modules/Admission` and `tests/Feature/Admission`;
- 45 files checked, 23 style issues normalized;
- formatting changes committed as `style(admission): normalize refactor formatting`.

Route verification:

- `php artisan route:list --name=admission`
- result: **20 Admission routes registered**;
- canonical public lookup: `/admission/search` (`admission.search`);
- compatibility lookup: `/admission/search/{ma_dinh_danh}/{password}` (`admission.search.legacy`);
- admin export route resolves to `AdmissionController@export`.

Manual UI acceptance:

- user reported **UI PASS** on 2026-09-02;
- branch working tree was clean at acceptance;
- accepted branch head before this handoff closeout: `00a75c40`.

## Merge gate

Ready for PR review:

- focused Admission regression PASS;
- route contract PASS;
- Pint completed;
- UI PASS;
- no destructive persistence change;
- no unresolved authz blocker;
- remaining shared-infrastructure/API/legacy-route items are explicitly documented as compatibility or quarantined debt.

After merge, synchronize `main` and treat this compact Admission major refactor as complete unless a separate follow-up is explicitly approved.
