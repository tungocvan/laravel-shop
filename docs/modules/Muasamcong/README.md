# Muasamcong Module

> Documentation entry point updated 2026-08-28 from `main@3c755169ecb99610a0a00c6a023d57b80cfe6f2b`.

## Module Overview

`Modules/Muasamcong` is the domain integration and persistence layer for medicine pricing and public-procurement workflows backed by `muasamcong.mpi.gov.vn`.

It owns upstream access, normalization, snapshots, synced pricing, wishlist, contractor history, KQLCNT/HSMT data, manual lot confirmation, export profiles, and personal upstream sessions. Customer-facing pages are presented by the ClientPortal Muasamcong adapter, which consumes this module's models/services.

Baseline recommendation: **Major Refactor through compatible incremental batches; no full rebuild.** See `ANALYSIS.md` for evidence and priorities.

## Registration

- Loader: `Modules\ModuleServiceProvider`.
- Manifest: `Modules/Muasamcong/config/module.php`.
- Type: `domain`.
- Declared module dependencies: none.
- Configuration provider: `Modules\Muasamcong\Providers\MuasamcongServiceProvider`.
- Runtime/production enablement: **NOT VERIFIED** by this documentation baseline.

## Main Routes

| Route | Current purpose |
|---|---|
| `/admin/muasamcong` | Smart Pricing workspace; currently not an Admin Dashboard |
| `/admin/muasamcong/contractors` | Contractor lookup and queued history |
| `/admin/muasamcong/contractors/history` | Archived contractor searches |
| `/admin/muasamcong/hsmt` | HSMT search/export |
| `/admin/muasamcong/synced` | Synced pricing and export profiles |
| `/admin/muasamcong/wishlist` | Admin pricing wishlist |
| `/admin/muasamcong/config` | Integration/session configuration |
| `/api/muasamcong` | Authenticated API availability |
| `/api/muasamcong/search-pricing` | Authenticated pricing search |
| `/api/muasamcong/update-cookie` | Throttled one-time session import |
| `/apps/muasamcong` | ClientPortal end-user dashboard, owned by ClientPortal |

The complete route inventory is in `INFORMATION.md`.

## Admin Dashboard Status

An Admin management Dashboard is requested but does not exist at this checkpoint. It is a separate capability, not part of this docs-only baseline.

The recommended compatibility contract is:

- add `/admin/muasamcong/dashboard` and leave the Smart Pricing index unchanged.

If the user instead approves `/admin/muasamcong` as the canonical Dashboard, Smart Pricing can move to `/admin/muasamcong/pricing`, but the existing index bookmark/link behavior changes and needs an explicit route-name/redirect migration plan.

The Dashboard should provide permission-aware links and bounded operational summaries without loading full payloads or exposing secrets.

## Permissions

- `view_muasamcong`
- `muasamcong.config.manage`
- `muasamcong.pricing.sync`
- `muasamcong.pricing.wishlist`

Important: current enforcement is inconsistent. Several contractor, history, manual-lot, verified-lot, and wishlist bulk mutations rely only on the view boundary. Do not treat hidden UI actions as authorization; see P1-01 in `ANALYSIS.md`.

ClientPortal uses separate `client.muasamcong.*` application/feature/action permissions.

## Features

- Smart Pricing with database-first snapshots and explicit refresh.
- Bounded multi-page TBMT/company search, local filters, pagination, and cross-page selection.
- Duplicate-safe selected pricing sync and per-user wishlist.
- Synced record management, configurable Excel profiles, and BBG export.
- Contractor participation history, queue jobs, archives, KQLCNT, and HSMT catalogue snapshots.
- Manual contractor-lot confirmation and download.
- Privileged integration/environment management and encrypted personal sessions.
- Internal Sanctum API.
- ClientPortal search, queue, history, wishlist, price-list, sharing, and PWA file handoff.

## Dependencies

- Laravel 12 / PHP 8.2+ / MySQL.
- Livewire 3.1 class-based components.
- Repository Admin authentication and permission infrastructure.
- Laravel HTTP client, queue, storage, validation, and Eloquent.
- FastExcel and PhpSpreadsheet.
- ClientPortal as a one-way presentation consumer:

```text
ClientPortal -> Muasamcong domain
```

No circular module dependency was observed.

## Configuration

Tracked defaults live in `Modules/Muasamcong/config/muasamcong.php`; safe examples live in `.env.example`.

Key groups:

- origin, timeout, user agent, page sizes;
- Smart token and session cookie;
- pricing, contractor, KQLCNT, contract, and HSMT endpoints;
- portal/pricing/contractor/KQLCNT referers.

Security invariants:

- exact HTTPS host `muasamcong.mpi.gov.vn` only;
- no URL credentials or non-443 ports;
- redirects disabled;
- production TLS verification always on;
- never commit/log/render real token or cookie values.

## Operational Notes

- Queue workers are required for contractor-history jobs and ClientPortal background workflows.
- HSMT snapshots are stored privately under `muasamcong/hsmt/<notifyNo>/` as JSON, XLSX, and metadata.
- Configurable synced exports accept at most 5,000 selected IDs and generate temporary private downloads.
- Search snapshots/raw payloads/HSMT files currently have no defined retention policy.
- Large upstream searches and formatted exports may be expensive despite hard caps.
- A reliable winner/contractor-to-exact-lot/medicine join key has not been found. Never introduce heuristic mapping.
- Upstream HTTP 200 is not sufficient; body/schema/count must be validated.

## Developer Notes

Preferred flow:

```text
Route -> thin Controller -> Page Blade -> Livewire -> Service -> Model/DB or upstream
```

Preserve these compatibility contracts unless an approved change says otherwise:

- Admin/API route names and URIs;
- ClientPortal adapter contracts;
- database tables/columns and migration history;
- private HSMT/export storage paths;
- export profile schema and generated workbook behavior;
- separation between upstream source data and manual enrichment.

Targeted test locations:

```text
tests/Feature/MuasamcongModuleTest.php
tests/Feature/Muasamcong/
tests/Feature/ClientApps/   # when ClientPortal contracts are affected
```

No fresh test run was performed for this documentation-only baseline. For a local docs review:

```bash
git diff --check main...HEAD
git diff --stat main...HEAD
```

For runtime changes, select formatting/tests according to the touched domain and expand to ClientPortal/shared coverage only when those contracts change.

## Documentation

Read in this order:

1. `README.md` — current entry point.
2. `INFORMATION.md` — factual inventory.
3. `ANALYSIS.md` — findings, evidence, Dashboard assessment, and recommendation.
4. `ROUTES.md`, `SYNCED.md`, `ENV_DOCTOR.md` — supporting references; verify against source because they were outside this baseline output scope.
5. `AI_HANDOFF.md` — legacy investigation context, especially winner/lot research; it is not a canonical current handoff.
6. Source and tests — final source of truth.

`docs/modules/Muasamcong/COLLABORATION_HANDOFF.md` is not present. Create it only in a separately approved handoff batch after the baseline is accepted.

## Future Improvements

Recommended order:

1. enforce capability-specific authorization and add denial tests;
2. atomically claim one-time session tokens;
3. make contractor job/sync workflows idempotent;
4. correct ClientPortal search completeness beyond 500 matching rows;
5. define snapshot/file retention and operational metrics;
6. implement the approved Admin Dashboard route contract;
7. extract controller/Livewire/export orchestration into focused services;
8. remove unused scaffolds and modernize manifest/model policies in compatibility-checked cleanup.
