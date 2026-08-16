# Muasamcong Refactor Plan

## Status

```text
IMPLEMENTED — PENDING LOCAL VERIFICATION
```

Implementation branch:

```text
agent/muasamcong-refactor
```

Approved scope: Major Refactor focused on security/config correctness/admin route normalization while preserving the working search core.

## Approved Contract Changes

Web URIs changed from:

```text
/muasamcong
/muasamcong/hsmt
/muasamcong/config
```

to:

```text
/admin/muasamcong
/admin/muasamcong/hsmt
/admin/muasamcong/config
```

Route names remain unchanged:

```text
muasamcong.index
muasamcong.hsmt
muasamcong.config
```

API URIs remain unchanged:

```text
GET  /api/muasamcong
POST /api/muasamcong/search-pricing
```

## Implemented Findings

### P0 — Configuration authorization

Implemented:

- search pages keep `view_muasamcong`;
- config page uses `muasamcong.config.manage`;
- new permission is declared in module manifest;
- `ConfigManager::save()` and `testToken()` re-check the management capability server-side.

### P0 — SSRF / arbitrary destination

Implemented server-side allowlisting in both configuration and HTTP integration layers:

```text
scheme: https
host:   muasamcong.mpi.gov.vn
port:   default/443 only
URL credentials: rejected
redirects: disabled
```

Token/cookie are not sent until destination/origin/referer validation passes.

### P0 — Page-load mutation

Implemented:

- removed `ensureDefaults()` mutation path;
- `ConfigManager::mount()` is read-only;
- no `.env` write or `config:clear` occurs on config page load.

### P0 — Production TLS

Implemented:

- production HTTP client always forces SSL verification;
- config writer rejects `VERIFY_SSL=false` in production;
- UI disables SSL toggle in production.

### P1 — Runtime config correctness

Implemented env-backed runtime mappings for:

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

`.env.example` was updated accordingly.

### P1 — Provider cleanup

Implemented conservative cleanup:

- `Modules\ModuleServiceProvider` remains untouched;
- `MuasamcongServiceProvider` no longer registers routes/views/Livewire/commands;
- it remains only for root `muasamcong` config merge and config publishing.

### P1 — Tests

Implemented/updated:

```text
tests/Feature/MuasamcongModuleTest.php
tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
```

Coverage targets:

- route prefix/name/permission contract;
- exactly five module routes after provider cleanup;
- API URI preservation;
- secret non-hydration;
- read-only config mount;
- unauthorized config mutation denial;
- temporary token test through service;
- SSRF/private-host rejection before HTTP send;
- HTTP destination rejection;
- upstream schema/error behavior;
- missing token behavior;
- XLSX generation.

## Export Decision

Current HSMT export semantics are preserved:

```text
first upstream page
page_size <= 100
selected rows only
```

No import, fetch-all or export-all crawler was added. A future full-result export is a separate business/API task.

## API Decision

API remains protected by `auth:sanctum`. No existing repository-wide Muasamcong-compatible API rate/capability convention was found during implementation, so no new custom authorization/rate architecture was introduced.

## Database / Migration Impact

```text
None
```

No database table or migration was added.

## Files Changed

Core:

```text
Modules/Muasamcong/routes/web.php
Modules/Muasamcong/config/module.php
Modules/Muasamcong/config/muasamcong.php
Modules/Muasamcong/Livewire/ConfigManager.php
Modules/Muasamcong/Services/MuasamcongConfigService.php
Modules/Muasamcong/Services/MuaSamCongService.php
Modules/Muasamcong/Providers/MuasamcongServiceProvider.php
Modules/Muasamcong/resources/views/livewire/config-manager.blade.php
Modules/Muasamcong/.env.example
```

Tests:

```text
tests/Feature/MuasamcongModuleTest.php
tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
```

Documentation:

```text
docs/modules/Muasamcong/ANALYSIS.md
docs/modules/Muasamcong/INFORMATION.md
docs/modules/Muasamcong/README.md
docs/modules/Muasamcong/REFACTOR_PLAN.md
```

## Non-Goals Preserved

Not implemented:

- local procurement persistence;
- new migrations;
- true upstream pagination;
- export-all crawling;
- import workflow;
- broad Admin UI redesign;
- repository-wide module-loader changes;
- custom API auth framework;
- deletion of unused scaffold files.

## Local Verification Required

Run:

```bash
php artisan optimize:clear
php artisan route:list --path=muasamcong
php artisan test tests/Feature/MuasamcongModuleTest.php
php artisan test tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
vendor/bin/pint --test Modules/Muasamcong tests/Feature/MuasamcongModuleTest.php tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
```

Expected route output:

```text
GET|HEAD api/muasamcong
POST     api/muasamcong/search-pricing
GET|HEAD admin/muasamcong
GET|HEAD admin/muasamcong/config
GET|HEAD admin/muasamcong/hsmt
```

Acceptance requires targeted tests and Pint to pass before merge.

## Rollback

The implementation is isolated on `agent/muasamcong-refactor`. Rollback before merge is simply abandoning/reverting this branch. No schema rollback is required.

## Remaining Follow-up

Optional later work:

- verify/standardize API rate policy at repository level;
- design true upstream pagination/export-all if needed;
- remove confirmed unused scaffold artifacts in a separate cleanup;
- reconcile historical portable module README/metadata if portability remains a requirement.
