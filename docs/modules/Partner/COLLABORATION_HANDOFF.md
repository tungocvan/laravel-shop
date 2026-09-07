# Partner Collaboration Handoff

## Current branch

`refactor/partner-master-hub-dashboard-lookup-sync`

## Objective

Major Refactor + Feature Development for `Modules/Partner`: establish Partner as the ERP master hub, add an Admin Dashboard, expose reviewed business-registry lookup, and provide explicit conflict-aware synchronization into canonical Partner records.

## Decisions locked

- `Partner` remains canonical ERP data.
- `PartnerSourceReference` remains canonical external provenance; no parallel source-reference table.
- `DoanhNghiepLookupService` is the primary runtime lookup adapter for arbitrary Vietnamese companies.
- MaSoThue is no longer the primary search provider because its token/session search flow returned accepted sessions without canonical results from server-side requests.
- Search/detail retrieval never writes Partner data.
- Multiple/fallback search results require explicit human candidate selection.
- Sync requires review of field-level diff states before mutation.
- External legal status is not mapped automatically to ERP `Partner.status`.
- `Partner.source` remains record acquisition origin (`manual/import/system`), not enrichment source.
- `(source, external_id)` provenance ownership is not silently reassigned.
- Current unique `partners.tax_code` schema contract remains unchanged.
- No bulk crawler, scheduled scraping, CAPTCHA bypass/OCR, automatic merge or automatic overwrite in this phase.

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
- replaced primary MaSoThue search with `DoanhNghiepLookupService` using JSON API search/detail endpoints;
- verified the external acceptance fixture `0314492345` resolves to `Công Ty TNHH Inafo Việt Nam` by MST and name search from the deployment server;
- preserved explicit source provenance and human review because external sources can disagree on fields such as legal representative;
- quarantined the incomplete unauthenticated `/api/partner/` route instead of exposing a controller with no supported API contract;
- added action-level authorization for Partner list/delete/import/export and create/edit form operations;
- centralized list/page-selection/export filters through `PartnerQueryService`;
- added focused planner and registry-adapter regression coverage.

## Verification required before merge

Run locally on this branch:

```bash
vendor/bin/pint Modules/Partner tests/Feature/Partner
php artisan test tests/Feature/Partner
php artisan route:list --path=admin/partners
php artisan route:list --path=api/partner
npm run build
git diff --check
git status -sb
```

Do not run full-project regression unless a focused failure proves a wider impact.

Manual UI smoke covered by user acceptance:

1. Partner Dashboard KPI cards and navigation.
2. Business Lookup search/error/empty states.
3. Multiple candidates: no automatic sync or fallback selection.
4. Candidate detail presentation.
5. Existing Partner match and field conflict preview.
6. New Partner preview.
7. Explicit selected-field sync.
8. Partner list pagination/filter behavior.

User acceptance marker: `UI PASS` on 2026-09-07.

Because closeout subsequently touched Partner create/edit navigation, the visible `province_code` field, and server-side list/action authorization, perform a short smoke of Partner list + create/edit form after pulling the latest branch.

## Remaining before merge

- record final Pint/test/route/build counts from local verification;
- confirm `/api/partner/` no longer exposes the incomplete public route;
- perform the short post-closeout Partner list + create/edit UI smoke;
- update this handoff with final PASS counts before PR/merge.

## Deferred

Verification semantics, automatic/bulk enrichment, scheduled crawling, CAPTCHA handling, automatic dedup/merge, multi-source arbitration, synchronization from `Invoices` into `Partner`, removal of tax-code uniqueness, and promotion of all source-only fields into Partner columns remain separate future work.
