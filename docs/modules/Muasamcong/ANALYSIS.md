# Muasamcong Module Analysis

## Executive Summary

`Modules/Muasamcong` is a stateless domain-integration module for procurement searches against `muasamcong.mpi.gov.vn`.

The approved Major Refactor has been implemented on `agent/muasamcong-refactor`. The working search core was preserved; changes focused on security, authorization, admin-route normalization, runtime config correctness, provider cleanup and focused regression coverage.

Post-refactor structural recommendation:

```text
No Structural Rebuild Required
```

Local verification is still required before merge.

## Module Purpose and Overview

Active capabilities:

- awarded-drug/pricing search;
- HSMT search by keyword/date range;
- selected-row XLSX export from the currently loaded bounded page;
- authenticated internal pricing-search API;
- privileged connection/token/cookie configuration;
- console smoke commands.

The module owns no procurement database table or migration.

## Bootstrap / Standards Context

Repository stack: Laravel 12, PHP 8.3, Livewire 3, first-party modules under `Modules/`.

`Modules\ModuleServiceProvider` remains the canonical module loader. `Modules\Muasamcong\Providers\MuasamcongServiceProvider` is now minimal: it keeps the root `config('muasamcong.*')` merge and publish behavior only. Generic route/view/Livewire/console registration is left to the repository loader.

## Dependency Graph

```text
/admin/muasamcong
-> Controller
-> Page Blade
-> TracuuThuoctrungthau
-> MuaSamCongService::searchPricing()
-> approved HTTPS upstream

/admin/muasamcong/hsmt
-> Controller
-> Page Blade
-> SearchHsmt
-> MuaSamCongService::searchHsmt()
-> approved HTTPS upstream
-> selected rows
-> HsmtExport

/admin/muasamcong/config
-> permission:muasamcong.config.manage,admin
-> ConfigManager::mount() [read-only]
-> save()/testToken() [action authorization]
-> config/integration services
```

## Route / Controller / Blade / Livewire Analysis

Current web contract:

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/admin/muasamcong` | `muasamcong.index` | `view_muasamcong` |
| GET | `/admin/muasamcong/hsmt` | `muasamcong.hsmt` | `view_muasamcong` |
| GET | `/admin/muasamcong/config` | `muasamcong.config` | `muasamcong.config.manage` |

API remains unchanged:

```text
GET  /api/muasamcong
POST /api/muasamcong/search-pricing
```

Route names and active Livewire aliases are preserved.

The web controller remains thin. Page Blade files remain shells using `Admin::layouts.master`. Search components still own UI state/validation and delegate integration work to `MuaSamCongService`.

`ConfigManager::mount()` no longer writes `.env`, creates missing keys or clears configuration cache. `save()` and `testToken()` both enforce `muasamcong.config.manage` against the authenticated admin user.

## Service Analysis

### MuaSamCongService

The service still owns payload building, upstream calls, response validation/error normalization and HSMT export-row mapping.

Before any outbound request it now validates endpoint, referer and origin:

- scheme must be `https`;
- host must exactly equal `muasamcong.mpi.gov.vn`;
- URL user/password are rejected;
- explicit ports other than 443 are rejected;
- redirects are disabled;
- production SSL verification is always forced on;
- token/cookie are resolved/sent only after the destination boundary passes.

This resolves the previous arbitrary-host/SSRF secret-forwarding risk.

### MuasamcongConfigService

The configuration writer now:

- accepts only a fixed `MUASAMCONG_*` key allowlist;
- rejects CR/LF injection;
- validates editable network URLs against the approved HTTPS host;
- refuses `VERIFY_SSL=false` in production;
- validates the complete payload before writing;
- performs one locked `.env` write per explicit save.

The old `ensureDefaults()` page-load mutation path has been removed.

## Import / Export Analysis

Import: **Not present**.

HSMT export remains intentionally bounded and selected-row-only from page zero (`page_size <= 100`). No fetch-all crawler or new import behavior was introduced.

The shared import/export foundation was not forced into this refactor because the current data source is ephemeral upstream data rather than a persistent local dataset.

## Model / Migration / Database Analysis

No active database model/table/migration is required. `Models/Muasamcong.php` remains an unused scaffold and is a later cleanup candidate only.

## Security

Resolved P0 findings:

1. config route no longer shares the read-only permission;
2. config mutations authorize again inside Livewire actions;
3. arbitrary/private/HTTP upstream destinations are rejected at service boundaries;
4. production SSL verification cannot be disabled;
5. GET/config mount has no `.env`/`config:clear` side effect;
6. existing token/cookie values stay out of public Livewire state and logs.

Remaining policy decision:

- API stays protected by `auth:sanctum`; no repository-wide canonical API capability/rate-limit convention was found, so this refactor does not invent one.

## Performance

Current bounds remain:

- one upstream page per search;
- page size 1–100;
- timeout 1–120 seconds;
- no redirect following;
- no fetch-all export;
- no unbounded database loading.

External requests remain synchronous and therefore depend on upstream latency.

## Validation and Authorization

Search validation remains unchanged. Config URL fields require HTTPS at UI validation and are revalidated by the config/integration services. Config actions require `muasamcong.config.manage` independently of route middleware.

## Transactions, Concurrency and Data Integrity

No database transaction is applicable. `.env` remains a high-risk global configuration file, so updates are allowlisted, fully validated before mutation and written only on an explicit privileged save.

## Admin UI / UX Standard Review

Config UI now provides:

- visible bordered controls and standard focus treatment;
- non-live bindings for fields that do not need immediate round trips;
- explicit approved-host guidance;
- masked/non-hydrated stored secrets;
- production-disabled SSL toggle;
- existing loading/success/error feedback.

Search UI was not broadly redesigned.

## Cross-Module Dependencies

- `Admin::layouts.master`;
- `Modules\ModuleServiceProvider`;
- Laravel Sanctum;
- Spatie Permission;
- Maatwebsite Excel.

No cross-domain model ownership exists.

## Technical Debt

Non-blocking remaining items:

- unused `Livewire/Hsmt.php`, tiny matching Blade scaffold and `Models/Muasamcong.php`;
- no upstream pagination beyond page zero;
- no module-specific API rate/capability policy beyond Sanctum;
- portable legacy metadata/docs under `Modules/Muasamcong` may be reconciled separately.

## Test Coverage

Added/updated coverage includes:

- secret non-hydration;
- read-only config mount with no config-cache clear;
- unauthorized config token-test denial;
- temporary token service request;
- private/unapproved host rejection before any HTTP send;
- HTTP destination rejection;
- upstream schema/error normalization;
- missing token behavior;
- XLSX generation;
- `/admin/muasamcong` route contract;
- read/config permission separation;
- unchanged API URIs;
- exactly five Muasamcong routes after provider cleanup.

These tests must be executed locally before merge.

## Documentation Drift

`docs/modules/Muasamcong/*` now reflects the refactored repository implementation. Historical standalone-install guidance in `Modules/Muasamcong/README.md` should not override these repository-specific docs.

## Issue List

### Resolved P0

- configuration permission boundary;
- SSRF/arbitrary-host boundary;
- config-page mutation side effect;
- production SSL disable path.

### Resolved P1

- env/runtime configuration mismatch;
- duplicate provider boot responsibilities;
- missing route/security regression guardrails.

### Open P2

- unused scaffolds;
- optional API rate/capability policy;
- future upstream pagination/export-all design.

## Module Health Summary

Architecture: good after targeted cleanup.  
Security: materially improved; local tests required.  
Performance: bounded for the current first-page contract.  
Database: not applicable.  
Maintainability: improved by explicit config/permission/provider boundaries.

## Final Recommendation

```text
No Structural Refactor Required after this implementation.
```

Future work should be incremental rather than a rebuild.

## Open Questions / Unknowns

- Whether true upstream pagination/export-all is desired later.
- Whether the project will define a common API capability/rate policy for external integrations.
- Whether the unused portable/scaffold artifacts should be removed in a separate cleanup task.
