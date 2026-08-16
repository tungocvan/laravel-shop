# Muasamcong Module Information

## Purpose

`Modules/Muasamcong` integrates the application with public-procurement search endpoints used by `muasamcong.mpi.gov.vn`.

It currently supports:

- awarded-drug/pricing lookup;
- HSMT/procurement-notice lookup by keyword and date range;
- selected-row XLSX export for HSMT results;
- upstream token/cookie and connection configuration;
- authenticated internal pricing-search API;
- console smoke-test commands.

The active module does not persist procurement data locally.

## Features

### Pricing / awarded-drug search

Entry page:

```text
/muasamcong
```

Livewire:

```text
Modules\Muasamcong\Livewire\TracuuThuoctrungthau
```

Service method:

```text
MuaSamCongService::searchPricing()
```

Search fields sent upstream include drug name, active ingredient and procurement notice code fields according to the current payload builder.

### HSMT search

Entry page:

```text
/muasamcong/hsmt
```

Livewire:

```text
Modules\Muasamcong\Livewire\SearchHsmt
```

Service method:

```text
MuaSamCongService::searchHsmt()
```

Inputs:

- keyword;
- from date;
- to date.

Current behavior requests page zero only. Page size is clamped to 1–100 by the service.

### HSMT XLSX export

Current export scope:

```text
selected rows from the currently loaded first page
```

Files:

```text
Modules/Muasamcong/Exports/HsmtExport.php
Modules/Muasamcong/Livewire/SearchHsmt.php
Modules/Muasamcong/Services/MuaSamCongService.php
```

Columns:

- Tên gói thầu
- Mã TBMT
- Ngày đăng tải
- Đóng thầu
- Bên mời thầu
- Tỉnh

### Configuration management

Page:

```text
/muasamcong/config
```

Livewire:

```text
Modules\Muasamcong\Livewire\ConfigManager
```

Service:

```text
Modules\Muasamcong\Services\MuasamcongConfigService
```

Current UI exposes:

- origin;
- SSL verification flag;
- timeout;
- user agent;
- smart token;
- session cookie;
- pricing endpoint;
- contractor endpoint;
- portal referer;
- pricing referer;
- page size.

Important: current source has a configuration consistency issue. The UI/config service writes many `MUASAMCONG_*` values to `.env`, while `config/muasamcong.php` currently reads environment values only for smart token and session cookie. See `ANALYSIS.md` before changing this flow.

## Routes

### Web

Defined in:

```text
Modules/Muasamcong/routes/web.php
```

| Method | URI | Name |
|---|---|---|
| GET | `/muasamcong` | `muasamcong.index` |
| GET | `/muasamcong/hsmt` | `muasamcong.hsmt` |
| GET | `/muasamcong/config` | `muasamcong.config` |

Default configured middleware:

```text
web
auth:admin
permission:view_muasamcong,admin
```

### API

Defined in:

```text
Modules/Muasamcong/routes/api.php
```

| Method | URI | Purpose |
|---|---|---|
| GET | `/api/muasamcong` | integration/API availability response |
| POST | `/api/muasamcong/search-pricing` | pricing lookup |

Default API middleware:

```text
api
auth:sanctum
```

## Permissions

Manifest file:

```text
Modules/Muasamcong/config/module.php
```

Current declared permission:

```text
view_muasamcong
```

Current security analysis recommends splitting privileged configuration management from read/search access. Do not treat `view_muasamcong` as sufficient long-term authorization for `.env`/endpoint/TLS changes.

## Controllers

### Web controller

```text
Modules/Muasamcong/Http/Controllers/MuasamcongController.php
```

Methods:

```text
index()
hsmt()
config()
```

Responsibilities are limited to returning Blade page shells.

### API controller

```text
Modules/Muasamcong/Http/Controllers/Api/MuasamcongController.php
```

Methods:

```text
index()
searchPricing()
```

`searchPricing()` validates `keyword` and delegates to `MuaSamCongService`.

## Livewire Components

### Active

```text
Modules\Muasamcong\Livewire\TracuuThuoctrungthau
Modules\Muasamcong\Livewire\SearchHsmt
Modules\Muasamcong\Livewire\ConfigManager
```

Aliases under the current module loader:

```text
muasamcong.tracuu-thuoctrungthau
muasamcong.search-hsmt
muasamcong.config-manager
```

### Scaffold / currently unused

```text
Modules\Muasamcong\Livewire\Hsmt
```

Alias:

```text
muasamcong.hsmt
```

This component is separate from the route name `muasamcong.hsmt` and is not the active HSMT search component.

## Blade Views

Page shells:

```text
Modules/Muasamcong/resources/views/muasamcong.blade.php
Modules/Muasamcong/resources/views/hsmt.blade.php
Modules/Muasamcong/resources/views/config.blade.php
```

Livewire views:

```text
Modules/Muasamcong/resources/views/livewire/tracuu-thuoctrungthau.blade.php
Modules/Muasamcong/resources/views/livewire/search-hsmt.blade.php
Modules/Muasamcong/resources/views/livewire/config-manager.blade.php
Modules/Muasamcong/resources/views/livewire/hsmt.blade.php
```

Admin layout dependency:

```text
Admin::layouts.master
```

## Services

### MuaSamCongService

```text
Modules/Muasamcong/Services/MuaSamCongService.php
```

Responsibilities:

- pricing request payload;
- HSMT request payload;
- token/cookie attachment;
- upstream HTTP calls;
- response schema validation;
- safe error normalization;
- first-page result normalization;
- selected HSMT export-row mapping.

Return convention:

```php
[
    'success' => bool,
    'status' => int,
    'data' => mixed,
    'message' => ?string,
]
```

### MuasamcongConfigService

```text
Modules/Muasamcong/Services/MuasamcongConfigService.php
```

Responsibilities:

- check root `.env` for supported keys;
- append missing defaults;
- update allowlisted `MUASAMCONG_*` values;
- quote dotenv values.

This is a privileged production-control service. Read `ANALYSIS.md` before changing or calling it from new UI/actions.

## Imports / Exports

### Import

```text
Not present
```

### Export

```text
Modules/Muasamcong/Exports/HsmtExport.php
```

Library:

```text
maatwebsite/excel
```

Implementation:

```text
FromArray
WithHeadings
```

Canonical repository shared import/export foundation is currently not used.

## Models

```text
Modules/Muasamcong/Models/Muasamcong.php
```

Status:

```text
unused scaffold
```

No active service/controller/Livewire flow in the inspected source uses this model.

## Database Tables

```text
None owned by the active module
```

No `database/migrations` or `Database/Migrations` directory was found under the module during analysis.

## Relationships

```text
Not present
```

No active Eloquent relationships exist because the module does not currently persist domain data.

## Shared / Cross-Module Dependencies

### Admin

Used for page layout:

```text
Admin::layouts.master
```

### Shared

Canonical import/export infrastructure exists in the repository but is not currently used by Muasamcong export.

### Root module loader

```text
Modules\ModuleServiceProvider
```

The root loader discovers `Muasamcong`, reads `config/module.php`, registers the module-specific provider if present and also performs generic config/route/resource/Livewire/console registration.

This overlaps with:

```text
Modules\Muasamcong\Providers\MuasamcongServiceProvider
```

See `ANALYSIS.md` for the duplicate-registration finding.

## Events / Jobs

```text
Not present
```

All upstream searches are currently synchronous.

## Console Commands

```text
Modules/Muasamcong/Console/Commands/TestHsmtCommand.php
Modules/Muasamcong/Console/Commands/TestPricingCommand.php
```

Commands:

```bash
php artisan msc:test-hsmt "thuốc generic" "2026-07-01:2026-07-31"
php artisan msc:test --payload=Modules/Muasamcong/examples/pricing-payload.json
```

These are smoke/integration helpers and do not persist data.

## Configuration / Environment Variables

Module config:

```text
Modules/Muasamcong/config/muasamcong.php
```

Environment example:

```text
Modules/Muasamcong/.env.example
```

Current `.env.example` contains only:

```text
MUASAMCONG_SMART_TOKEN
MUASAMCONG_SESSION_COOKIE
```

`MuasamcongConfigService` knows the following key set:

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

Current runtime config file only sources token/cookie from env. This must be reconciled before relying on UI changes to the other keys.

## Upstream Contracts

Current default origin:

```text
https://muasamcong.mpi.gov.vn
```

Pricing path defaults to the current smart pricing endpoint under the same host.

HSMT/contractor search defaults to the contractor-selection smart search endpoint under the same host.

These endpoints are integration dependencies rather than repository-owned contracts and may change independently.

## Tests

Targeted test file:

```text
tests/Feature/MuasamcongModuleTest.php
```

Current coverage includes:

- secret hydration protection;
- temporary token test;
- upstream schema normalization;
- safe schema failure;
- safe connection failure;
- missing HSMT token failure;
- XLSX generation.

See `ANALYSIS.md` for missing security and route-boot coverage.

## Known Limitations

- only upstream page zero is requested;
- current export only includes selected rows already loaded into Livewire state;
- upstream token/cookie may expire;
- upstream response schema is outside repository control;
- synchronous searches depend on upstream latency;
- no local database history/cache exists;
- current configuration UI/source mapping is inconsistent;
- current module-specific provider overlaps canonical repository registration.

## Maintenance Notes

Before modifying this module:

1. Read `docs/modules/Muasamcong/ANALYSIS.md`.
2. Preserve existing public routes and Livewire aliases unless a compatibility plan is documented.
3. Never hard-code or log smart token/session cookie.
4. Do not allow browser-controlled arbitrary upstream hosts.
5. Keep SSL verification enabled in production.
6. Do not reintroduce a second module boot mechanism merely for portability.
7. Keep searches/exports bounded.
8. Add tests before changing provider/config/authorization behavior.
9. Do not create database tables unless a new persistence requirement is explicitly approved.
