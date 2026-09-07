# Partner Collaboration Handoff

## Current branch

`refactor/partner-master-hub-dashboard-lookup-sync`

## Objective

Major Refactor + Feature Development for `Modules/Partner`: establish Partner as the ERP master hub, add an Admin Dashboard, expose reviewed MaSoThue business lookup, and provide explicit conflict-aware synchronization into canonical Partner records.

## Decisions locked

- `Partner` remains canonical ERP data.
- `PartnerSourceReference` remains canonical external provenance; no parallel source-reference table.
- `App\Services\MasothueLookupService` remains the source-specific read adapter and is reused rather than duplicated.
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
- added `province_code` to Partner service/form data path;
- added focused planner regression coverage.

## Verification still required before merge

Run locally on this branch:

```bash
vendor/bin/pint Modules/Partner tests/Feature/Partner
php artisan test tests/Feature/Partner
php artisan route:list --path=admin/partners
npm run build
git diff --check
git status -sb
```

Also run any directly impacted existing Partner/MaSoThue tests discovered locally. Do not run full-project regression unless a focused failure proves a wider impact.

Manual UI smoke must cover:

1. Partner Dashboard KPI cards and navigation.
2. Business Lookup search/error/empty states.
3. Multiple candidates: no automatic sync or fallback selection.
4. Candidate detail presentation.
5. Existing Partner match and field conflict preview.
6. New Partner preview.
7. Explicit selected-field sync.
8. Repeated sync does not duplicate source references.
9. Partner list pagination/filter/export behavior remains intact.
10. Partner create/edit form remains intact.

User acceptance marker: `UI PASS`.

## Remaining hardening before merge

- finish eliminating the legacy duplicated filter query in `Partner\Index` in favor of `PartnerQueryService` for list/page-selection/export consistency;
- carry `province_code` through visible form/import/export UI where appropriate;
- apply repository-canonical permission registration and action-level authorization after verifying the exact permission seeding convention;
- add focused Dashboard, lookup, sync/idempotency/source-collision and export contract tests;
- resolve any Pint/runtime issues found by local verification;
- update this handoff with final test counts and UI result before PR/merge.

## Deferred

Verification semantics, automatic/bulk enrichment, scheduled crawling, CAPTCHA handling, automatic dedup/merge, multi-source arbitration, removal of tax-code uniqueness, and promotion of all source-only fields into Partner columns remain separate future work.
