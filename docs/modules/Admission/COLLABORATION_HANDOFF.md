# Admission — Collaboration Handoff

## Current objective

Compact major refactor of `Modules/Admission`, keeping the module intentionally small and centered on grade-1 registration, public lookup, and focused admin handling.

Branch: `refactor/admission-major-cleanup`

## Approved scope

The user approved combining the originally proposed phases into one implementation branch to reduce repeated pull/test cycles.

Target work in this branch:

- establish `MODULE.md` architecture contract;
- repair route/controller drift, especially export wiring;
- consolidate duplicated admission document-generation logic;
- improve public lookup security without abrupt compatibility breakage;
- preserve and document persistence-sensitive/shared queue boundaries;
- remove only caller-proven dead/placeholder runtime artifacts;
- align touched admin UI/pagination with the current Admin UI standard;
- finish with one focused regression cycle and manual UI acceptance.

## Runtime baseline

Canonical module responsibilities:

- grade-1 admission registration;
- public admission lookup;
- application administration/review;
- Admission settings/catalogs/location data;
- import/export;
- application documents/receipts.

Existing useful boundaries to preserve:

- `AdmissionRegistrationService`;
- `AdmissionApplicationAdminService`;
- Admission admin route family `/admin/admission/*`;
- explicit Admission permission middleware;
- queued document generation via `GenerateAdmissionPdfJob`.

## Confirmed architecture drift / debt

1. `/admin/admission/export` is registered against `AdmissionController@export`, but the controller currently has no `export()` method.
2. `AdmissionController` duplicates DOCX/PDF generation and LibreOffice process handling even though queue generation already uses shared `DocumentConverterService`.
3. Public lookup historically supports credentials in route path parameters; this is deprecated because URL-carried credentials can be exposed through browser/history/log/referrer surfaces.
4. Admission contains a historical migration that conditionally creates shared `job_batches`; the table is shared infrastructure and must not be destructively modified during this refactor.
5. Admission API currently exposes only a `501 Not Implemented` placeholder; removal requires caller proof.
6. Location/DVHC ownership remains Admission-owned pending cross-module caller proof.

## Safety decisions

- No destructive schema cleanup in this compact refactor.
- `job_batches` ownership remains quarantined/documented unless migration-ledger proof makes a safe correction possible.
- Placeholder API and legacy credential URL compatibility are not removed blindly.
- Registration/application data and review metadata must be preserved.
- Explicit admin permission boundaries must remain unchanged or stronger.

## Test/acceptance strategy

Run once near closeout rather than after every small commit:

- `tests/Feature/Admission`;
- only directly impacted Admin tests when navigation/routes are touched;
- route checks for Admission;
- Pint on touched PHP files;
- build only if frontend/template changes require it;
- manual UI: registration, lookup, admin application list/review, export/document actions.

User will report UI acceptance separately.

## Merge gate

Before PR/merge:

- focused automated checks pass;
- user reports UI PASS;
- this handoff is updated with final changed-file/test summary;
- no unresolved persistence/authz blocker remains.
