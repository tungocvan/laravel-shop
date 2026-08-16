# Muasamcong Module

## Module Overview

`Muasamcong` is a domain integration module for searching public-procurement data from `muasamcong.mpi.gov.vn`.

Current user-facing capabilities:

- search awarded-drug/pricing data;
- search HSMT/procurement notices by keyword and date range;
- export selected HSMT rows to XLSX;
- authenticated pricing-search API;
- upstream integration configuration and token testing.

The module currently does not persist procurement data in a local database.

For security/architecture findings, read:

```text
docs/modules/Muasamcong/ANALYSIS.md
```

For a factual module catalog, read:

```text
docs/modules/Muasamcong/INFORMATION.md
```

## Registration

Canonical repository loader:

```text
Modules\ModuleServiceProvider
```

Manifest:

```text
Modules/Muasamcong/config/module.php
```

Current manifest values:

```text
name: Muasamcong
type: domain
enabled: true
permission: view_muasamcong
```

The module also currently contains:

```text
Modules/Muasamcong/Providers/MuasamcongServiceProvider.php
```

This provider overlaps the generic registration performed by `Modules\ModuleServiceProvider`. Do not add another provider registration. Provider/config normalization is a planned refactor item.

## Main Routes

### Web

```text
GET /muasamcong
GET /muasamcong/hsmt
GET /muasamcong/config
```

Route names:

```text
muasamcong.index
muasamcong.hsmt
muasamcong.config
```

### API

```text
GET  /api/muasamcong
POST /api/muasamcong/search-pricing
```

## Permissions

Current web route permission:

```text
view_muasamcong
```

Important: this permission currently also reaches configuration UI that can write `.env`, change network endpoints and clear config cache. This is a known P0 finding.

Future refactor should separate read/search access from privileged configuration management while preserving compatibility for existing roles/routes.

## Features

### Pricing search

```text
TracuuThuoctrungthau
-> MuaSamCongService::searchPricing()
-> upstream pricing endpoint
```

### HSMT search

```text
SearchHsmt
-> MuaSamCongService::searchHsmt()
-> upstream contractor-selection endpoint
```

Current searches use upstream page zero only. Server-side page size is bounded to a maximum of 100.

### XLSX export

```text
SearchHsmt::exportExcel()
-> MuaSamCongService::exportRows()
-> HsmtExport
-> Excel::download()
```

Export scope is selected rows from the currently loaded page.

### Configuration

```text
ConfigManager
-> MuasamcongConfigService
-> root .env
```

This path is security-sensitive. Read `ANALYSIS.md` before modifying it.

## Dependencies

Primary dependencies:

```text
Laravel 12
Livewire 3
Laravel HTTP client
Laravel Sanctum
Spatie Laravel Permission
Maatwebsite Excel
Admin::layouts.master
```

Repository shared import/export services exist but are not currently used by this module.

## Configuration

Config file:

```text
Modules/Muasamcong/config/muasamcong.php
```

Environment example:

```text
Modules/Muasamcong/.env.example
```

Current active secret keys:

```text
MUASAMCONG_SMART_TOKEN
MUASAMCONG_SESSION_COOKIE
```

The configuration service currently supports additional `MUASAMCONG_*` keys, but the runtime config file does not yet consume all of them. Do not assume a successful UI save means every field has taken effect until this is refactored.

Never commit real token/cookie values.

## Operational Notes

- Upstream endpoints are external integration contracts and can change without repository changes.
- Smart token/session cookie may expire.
- Existing stored secret values are intentionally not hydrated into Livewire public state.
- Integration service logs host + exception class only for failures and should continue avoiding tokens/cookies/raw sensitive responses.
- SSL verification should remain enabled in production.
- Arbitrary configurable upstream hosts are a known security issue and should be allowlisted or deployment-controlled.
- Search is synchronous and therefore affected by upstream latency/timeouts.

## Tests

Targeted module test:

```bash
php artisan test tests/Feature/MuasamcongModuleTest.php
```

Useful local checks:

```bash
php artisan optimize:clear
php artisan route:list --path=muasamcong
php artisan test tests/Feature/MuasamcongModuleTest.php
vendor/bin/pint --test Modules/Muasamcong tests/Feature/MuasamcongModuleTest.php
```

The route-list check is especially important before refactoring provider registration because source analysis indicates duplicate registration paths.

## Developer Notes

Preferred active flow:

```text
Route
-> thin Controller
-> Page Blade
-> Livewire
-> MuaSamCongService
-> upstream HTTP service
```

Rules for future changes:

- keep business/integration logic out of Blade;
- keep controllers thin;
- validate all user-controlled search/config values;
- authorize privileged Livewire mutations inside the action;
- do not allow browser-controlled arbitrary HTTP destinations;
- never forward token/cookie to an unapproved host;
- keep result sets bounded;
- do not create migrations/database persistence unless required by approved business scope;
- add regression tests before provider/config changes;
- preserve current route names and Livewire aliases unless a migration plan is documented.

## Future Improvements

Based on current analysis, recommended order is:

1. add authorization/SSRF/config regression tests;
2. split configuration permission from view/search permission;
3. remove `.env` mutation and config-cache clearing from component mount;
4. enforce approved upstream scheme/host/path and production TLS verification;
5. normalize environment/config mapping;
6. normalize module provider/config registration with the repository loader;
7. define API capability and rate policy;
8. evaluate migration of XLSX export to shared import/export infrastructure;
9. remove confirmed unused scaffold/portable artifacts;
10. reconcile `Modules/Muasamcong/README.md` and `.env.example` with implemented behavior.

Current structural recommendation:

```text
Major Refactor
```

A full rebuild is not recommended because the existing search/integration separation is already usable and testable; the main work is targeted security and repository-integration correction.
