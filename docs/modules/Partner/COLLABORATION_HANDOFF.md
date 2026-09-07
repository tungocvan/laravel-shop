# Partner Collaboration Handoff

## Current branch

`refactor/partner-master-hub-dashboard-lookup-sync`

## Objective

Major Refactor + Feature Development for `Modules/Partner`: establish Partner as the ERP master hub, add an Admin Dashboard, expose reviewed business-registry lookup, and provide explicit conflict-aware synchronization into canonical Partner records.

## Decisions locked

- `Partner` remains canonical ERP data.
- `PartnerSourceReference` remains canonical external provenance; no parallel source-reference table.
- Business Lookup uses an explicit provider contract and lets the operator select the registry source before searching.
- `MSTCongTy` is the default lookup source because it currently provides the preferred operational company detail and exposes a source-side update path for stale records.
- `DoanhNghiepLookupService` remains an alternate/cross-check provider.
- MaSoThue is retained only as reference/fallback context; its token/session search flow did not provide a reliable canonical server-side search contract.
- Search/detail retrieval never writes Partner data.
- Multiple/fallback search results require explicit human candidate selection.
- Cross-source differences are presented for human review; the system does not silently arbitrate which external source is true.
- Sync requires review of field-level diff states before mutation.
- Current synchronized Partner master fields are limited to `tax_code`, `name`, and `address`.
- External legal status, representative, activity date, organization type and other source-only attributes remain provenance/snapshot metadata and are not blindly promoted into Partner master columns.
- External legal status is not mapped automatically to ERP `Partner.status`.
- `Partner.source` remains record acquisition origin (`manual/import/system`), not enrichment source.
- `(source, external_id)` provenance ownership is not silently reassigned.
- Current unique `partners.tax_code` schema contract remains unchanged.
- No bulk crawler, scheduled scraping, CAPTCHA/browser-challenge bypass, OCR, automatic merge or automatic overwrite in this phase.

## Implemented in current batch

- added `docs/modules/Partner/MODULE.md` to satisfy the missing module-contract gate;
- added canonical `PartnerQueryService` and bounded pagination support;
- added `PartnerDashboardService` and `/admin/partners/dashboard` workspace;
- added `/admin/partners/business-lookup` workspace;
- added `ExternalPartnerData`, `PartnerMatcher`, `PartnerSyncPlanner`, and transactional `PartnerSyncService` boundaries;
- added reviewed field states: `new_value`, `same`, `local_differs`, `missing_locally`, `source_missing`;
- reused `PartnerSourceReference` for source URL, lookup/sync timestamps, snapshot and hash metadata;
- preserved legacy `/admin/partner/partners/*` routes while canonical workspace callers migrate;
- added `province_code` to Partner service/form/import/export data path and visible form field;
- added `BusinessRegistryProvider` as the provider boundary;
- added `MstCongTyProvider` using the site's server-rendered search/detail pages without browser automation or challenge bypass;
- adapted `DoanhNghiepLookupService` to the same provider contract;
- added `MultiSourceBusinessLookupService` for selected-source lookup plus optional cross-source comparison;
- added an explicit source selector to Business Lookup, defaulting to `MSTCongTy` and allowing `Doanhnghiep.vn` as an alternate source;
- added source provenance, source labels, source-origin links and conflict presentation in the review UI;
- preserved graceful failure when one external source is unavailable;
- verified the external acceptance fixture `0314492345` resolves to `Công Ty TNHH Inafo Việt Nam` through the registry integration path;
- preserved explicit source provenance and human review because external sources can disagree on fields such as legal representative;
- quarantined the incomplete unauthenticated `/api/partner/` route instead of exposing a controller with no supported API contract;
- added action-level authorization for Partner list/delete/import/export and create/edit form operations;
- centralized list/page-selection/export filters through `PartnerQueryService`;
- added focused planner and registry-adapter regression coverage.

## Final verification

Local verification on 2026-09-07:

- Pint: completed across `Modules/Partner` and `tests/Feature/Partner`; final run normalized 2 style issues in the multi-source lookup files and those fixes were committed as `c24ffbe8`.
- Partner focused tests: **5 passed, 20 assertions**.
- Canonical Partner routes: **5 routes** under `/admin/partners`.
- Vite: **PASS**, 34 modules transformed, production assets built successfully.
- Branch after the style commit/push: local branch synchronized with `origin/refactor/partner-master-hub-dashboard-lookup-sync` and working tree clean.
- Incomplete `/api/partner/` route remains quarantined; no supported public Partner API contract is exposed in this batch.

Do not run full-project regression unless a focused failure proves a wider impact.

## Manual UI acceptance

User acceptance marker: **`UI PASS` on 2026-09-07** after the selectable-source update.

Accepted UI behavior includes:

1. Partner Dashboard KPI cards and navigation.
2. Business Lookup source selector visible and usable.
3. `MSTCongTy` selected by default.
4. Operator can switch to `Doanhnghiep.vn` before searching.
5. Search/error/empty states.
6. Multiple candidates require explicit selection; no automatic sync or fallback selection.
7. Candidate detail and source provenance presentation.
8. Cross-source conflict review when comparison data differs.
9. Existing Partner match and field conflict preview.
10. New Partner classification preview.
11. Explicit selected-field sync.
12. Partner list pagination/filter behavior.

## Merge readiness

Implementation, focused verification, build and UI acceptance are complete for the current Partner batch. The branch is ready for PR/merge subject to the normal repository collaboration workflow.

## Deferred

Verification semantics, automatic/bulk enrichment, scheduled crawling, CAPTCHA/browser-challenge handling, automatic dedup/merge, unattended source arbitration, synchronization from `Invoices` into `Partner`, removal of tax-code uniqueness, and promotion of source-only fields into Partner columns remain separate future work.
