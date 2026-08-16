# Muasamcong Module

## Module Overview

`Muasamcong` is a domain integration module for searching public-procurement data from `muasamcong.mpi.gov.vn`.

Current capabilities:

- awarded-drug/pricing search;
- HSMT search by keyword/date range;
- selected-row XLSX export;
- authenticated pricing-search API;
- privileged upstream integration configuration.

The module does not persist procurement data locally.

## Registration

Canonical loader:

```text
Modules\ModuleServiceProvider
```

Manifest:

```text
Modules/Muasamcong/config/module.php
```

The module-specific `MuasamcongServiceProvider` is intentionally minimal and only preserves root config merge/publishing. Route/view/Livewire/console registration belongs to the canonical loader.

## Main Routes

### Admin Web

```text
GET /admin/muasamcong
GET /admin/muasamcong/hsmt
GET /admin/muasamcong/config
```

Route names remain:

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

```text
view_muasamcong
muasamcong.config.manage
```

`view_muasamcong` is for search pages. `muasamcong.config.manage` is required for the configuration page and is re-checked inside config mutation methods.

## Features

### Pricing search

```text
TracuuThuoctrungthau
-> MuaSamCongService::searchPricing()
-> approved procurement endpoint
```

### HSMT search/export

```text
SearchHsmt
-> MuaSamCongService::searchHsmt()
-> first upstream page (max 100)
-> selected rows
-> HsmtExport
```

### Configuration

```text
ConfigManager
-> MuasamcongConfigService
-> root .env
```

Config page mount is read-only. Save/test actions require the dedicated management permission.

## Security Boundaries

All outbound Muasamcong URLs must:

- use HTTPS;
- use exact host `muasamcong.mpi.gov.vn`;
- avoid embedded credentials;
- avoid non-443 explicit ports.

Redirects are disabled. Production SSL verification is forced on. Token/cookie values are never hydrated back into public Livewire state and must not be logged.

## Configuration

Runtime config reads the full documented `MUASAMCONG_*` set from environment. See:

```text
Modules/Muasamcong/.env.example
```

Never commit real token/cookie values.

## Dependencies

```text
Laravel 12
Livewire 3
Laravel HTTP client
Laravel Sanctum
Spatie Laravel Permission
Maatwebsite Excel
Admin::layouts.master
```

## Operational Notes

- upstream endpoints/schema can change independently;
- token/cookie may expire;
- searches are synchronous;
- only page zero is currently fetched;
- HSMT export is selected-row-only and bounded;
- no local procurement history/cache is stored.

## Verification

Run locally before merge:

```bash
php artisan optimize:clear
php artisan route:list --path=muasamcong
php artisan test tests/Feature/MuasamcongModuleTest.php
php artisan test tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
vendor/bin/pint --test Modules/Muasamcong tests/Feature/MuasamcongModuleTest.php tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
```

Expected route contract is exactly five Muasamcong routes: two API routes and three `/admin/muasamcong...` routes.

## Developer Notes

Preferred flow:

```text
Route -> Controller -> Page Blade -> Livewire -> Service -> upstream HTTP
```

Keep integration logic in services, mutation authorization inside sensitive Livewire actions, result sets bounded, and network destinations server-allowlisted.

## Future Improvements

Potential later tasks:

- true upstream pagination;
- explicit API rate/capability policy once the repository standardizes one;
- deliberate export-all design if upstream crawling is approved;
- removal of confirmed unused Hsmt/model scaffolds.

Current recommendation:

```text
No Structural Refactor Required
```
