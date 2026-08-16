# Muasamcong Module Information

## Purpose

`Modules/Muasamcong` integrates the application with procurement-search endpoints on `muasamcong.mpi.gov.vn`.

Current capabilities:

- awarded-drug/pricing lookup;
- HSMT lookup by keyword/date range;
- selected-row XLSX export;
- privileged upstream connection/token/cookie configuration;
- authenticated internal pricing API;
- console smoke-test commands.

No procurement data is persisted locally.

## Features

### Pricing search

```text
/admin/muasamcong
Modules\Muasamcong\Livewire\TracuuThuoctrungthau
MuaSamCongService::searchPricing()
```

### HSMT search

```text
/admin/muasamcong/hsmt
Modules\Muasamcong\Livewire\SearchHsmt
MuaSamCongService::searchHsmt()
```

The module requests upstream page zero only. Page size is clamped to 1–100.

### HSMT export

Export scope is selected rows from the currently loaded bounded page.

Files:

```text
Modules/Muasamcong/Exports/HsmtExport.php
Modules/Muasamcong/Livewire/SearchHsmt.php
Modules/Muasamcong/Services/MuaSamCongService.php
```

### Configuration

```text
/admin/muasamcong/config
Modules\Muasamcong\Livewire\ConfigManager
Modules\Muasamcong\Services\MuasamcongConfigService
```

`mount()` is read-only. `save()` and `testToken()` require `muasamcong.config.manage` inside the Livewire action.

## Routes

### Web

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/admin/muasamcong` | `muasamcong.index` | `view_muasamcong` |
| GET | `/admin/muasamcong/hsmt` | `muasamcong.hsmt` | `view_muasamcong` |
| GET | `/admin/muasamcong/config` | `muasamcong.config` | `muasamcong.config.manage` |

All web routes use `auth:admin`.

### API

```text
GET  /api/muasamcong
POST /api/muasamcong/search-pricing
```

Default API middleware remains `api` + `auth:sanctum`.

## Permissions

Declared in `Modules/Muasamcong/config/module.php`:

```text
view_muasamcong
muasamcong.config.manage
```

`view_muasamcong` covers search pages. `muasamcong.config.manage` covers the configuration page and its mutations.

## Controllers

```text
Modules/Muasamcong/Http/Controllers/MuasamcongController.php
Modules/Muasamcong/Http/Controllers/Api/MuasamcongController.php
```

The web controller only returns page shells. The API controller validates `keyword` and delegates to `MuaSamCongService`.

## Livewire Components

Active:

```text
muasamcong.tracuu-thuoctrungthau
muasamcong.search-hsmt
muasamcong.config-manager
```

Unused scaffold still present:

```text
Modules\Muasamcong\Livewire\Hsmt
```

## Blade Views

Page shells use `Admin::layouts.master`.

Config UI includes secure-host guidance, secret masking, standard bordered controls, loading states and production-disabled SSL toggle.

## Services

### MuaSamCongService

Responsibilities:

- construct pricing/HSMT payloads;
- validate approved upstream endpoint/origin/referer;
- attach token/cookie only after destination validation;
- perform bounded HTTPS requests;
- normalize upstream responses/errors;
- map selected HSMT export rows.

Approved host:

```text
muasamcong.mpi.gov.vn
```

Redirect following is disabled. Production SSL verification is forced on.

### MuasamcongConfigService

Responsibilities:

- update only allowlisted `MUASAMCONG_*` values;
- reject CR/LF values;
- validate all network URLs against the approved HTTPS host;
- prevent disabling SSL verification in production;
- perform one locked `.env` write after full validation.

## Imports / Exports

Import: `Not present`.

Export: `HsmtExport` using Maatwebsite Excel (`FromArray`, `WithHeadings`).

## Models / Database

`Modules/Muasamcong/Models/Muasamcong.php` is an unused scaffold.

Database tables/migrations/relationships: `Not present`.

## Shared / Cross-Module Dependencies

- Admin layout;
- canonical `Modules\ModuleServiceProvider`;
- Sanctum;
- Spatie Permission;
- Maatwebsite Excel.

The module-specific provider now only merges/publishes Muasamcong config; generic boot work is handled by the canonical loader.

## Events / Jobs

`Not present`.

Searches remain synchronous.

## Console Commands

```bash
php artisan msc:test-hsmt "thuốc generic" "2026-07-01:2026-07-31"
php artisan msc:test --payload=Modules/Muasamcong/examples/pricing-payload.json
```

## Configuration / Environment Variables

Runtime config consumes:

```text
MUASAMCONG_ORIGIN
MUASAMCONG_VERIFY_SSL
MUASAMCONG_TIMEOUT
MUASAMCONG_USER_AGENT
MUASAMCONG_SMART_TOKEN
MUASAMCONG_SESSION_COOKIE
MUASAMCONG_PRICING_ENDPOINT
MUASAMCONG_CONTRACTOR_ENDPOINT
MUASAMCONG_PORTAL_REFERER
MUASAMCONG_PRICING_REFERER
MUASAMCONG_PAGE_SIZE
```

See `Modules/Muasamcong/.env.example` for placeholders/defaults. Never commit real token/cookie values.

## Upstream Contract

All configured origin/endpoint/referer URLs must use HTTPS and the exact host:

```text
muasamcong.mpi.gov.vn
```

Explicit non-443 ports and embedded URL credentials are rejected.

## Tests

```text
tests/Feature/MuasamcongModuleTest.php
tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
```

Coverage includes secret non-hydration, read-only mount, mutation denial, SSRF blocking, upstream error/schema behavior, XLSX generation and route/permission/provider contracts.

## Known Limitations

- page zero only;
- selected-row export only;
- token/cookie may expire;
- upstream schema is external and can change;
- synchronous upstream latency affects request duration;
- no local history/cache;
- API has Sanctum auth but no module-specific rate/capability policy yet.

## Maintenance Notes

- preserve route names and API URIs;
- keep config mutations capability-protected;
- never relax approved-host validation when secrets can be forwarded;
- keep production SSL verification on;
- keep result/export scope bounded;
- do not add persistence without explicit business scope;
- run targeted tests and route-list verification before merge.
