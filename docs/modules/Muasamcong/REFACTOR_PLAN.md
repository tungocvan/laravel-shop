# Muasamcong Refactor Plan

## Status

Planning only. Application source must not be modified until this plan is explicitly approved by the user.

Target branch:

```text
agent/muasamcong-analysis
```

Target module:

```text
Modules/Muasamcong
```

Structural recommendation from `ANALYSIS.md`:

```text
Major Refactor
```

## Refactor Goal

Refactor `Modules/Muasamcong` without rebuilding its working search/integration core.

Primary goals:

1. close the configuration authorization and SSRF/security gaps;
2. standardize admin web URLs under `/admin/muasamcong`;
3. make configuration persistence/runtime behavior correct and predictable;
4. remove write side effects from page mount;
5. simplify module registration responsibilities without changing Livewire aliases or API contracts;
6. strengthen tests around authorization, routes, configuration and upstream HTTP boundaries;
7. preserve the current working pricing/HSMT search flows and existing route names.

## Approved User Requirement To Carry Into Implementation

Web/admin routes must move from:

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

The existing route names must remain unchanged:

```text
muasamcong.index
muasamcong.hsmt
muasamcong.config
```

API routes remain unchanged:

```text
GET  /api/muasamcong
POST /api/muasamcong/search-pricing
```

This URL-prefix change is an explicitly approved public-contract change. Internal callers should continue to use route names rather than hard-coded URLs.

## Current Verified Baseline

Local verification supplied before this plan:

```text
php artisan route:list --path=muasamcong
```

showed exactly 5 routes:

```text
GET|HEAD api/muasamcong
POST     api/muasamcong/search-pricing
GET|HEAD muasamcong
GET|HEAD muasamcong/config
GET|HEAD muasamcong/hsmt
```

Targeted regression baseline:

```text
PASS Tests\Feature\MuasamcongModuleTest
7 passed
22 assertions
```

Therefore the implementation must preserve the currently passing functional behavior while changing the approved admin URI prefix and security boundaries.

## Findings Being Addressed

### P0-01 — Configuration capability uses a read permission

Current state:

```text
permission:view_muasamcong,admin
```

protects all three web routes, including `/muasamcong/config`.

But `ConfigManager` can:

- update root `.env`;
- store smart token/session cookie;
- alter origin/endpoints/referers;
- alter TLS verification;
- call `config:clear`.

### Planned correction

Keep existing read/search permission for search pages:

```text
view_muasamcong
```

Add a dedicated privileged capability:

```text
muasamcong.config.manage
```

Planned route boundaries:

```text
/admin/muasamcong
/admin/muasamcong/hsmt
    -> auth:admin
    -> permission:view_muasamcong,admin

/admin/muasamcong/config
    -> auth:admin
    -> permission:muasamcong.config.manage,admin
```

Mutation methods in `ConfigManager` must also enforce the privileged capability server-side. Hiding the config route/button is not sufficient.

Super Admin remains compatible through the repository `Gate::before` behavior.

## P0-02 — Browser-editable arbitrary upstream destination / SSRF boundary

Current UI permits arbitrary `http`/`https` origin, endpoints and referers. These values can become server-side request destinations.

### Planned correction

Introduce server-side validation in the integration/config layer that accepts only the approved procurement host:

```text
muasamcong.mpi.gov.vn
```

Rules:

- HTTPS required for production upstream requests;
- endpoint host must match the approved host exactly;
- origin/referer host must match the approved host exactly;
- no localhost/private IP/custom host is accepted from browser configuration;
- redirects must not silently escape the approved host boundary if redirects are enabled by the HTTP client;
- token/cookie must never be forwarded to an unapproved host.

Do not rely only on Livewire `url` validation. The service boundary must enforce the allowlist because services can be called outside Livewire.

## P0-03 — Opening config page mutates `.env`

Current flow:

```text
ConfigManager::mount()
-> MuasamcongConfigService::ensureDefaults()
-> may write .env
-> config:clear
```

### Planned correction

`mount()` becomes read-only.

It may:

- read current module configuration;
- determine whether secrets are configured;
- initialize public non-secret form state.

It must not:

- write `.env`;
- create missing keys;
- clear config cache;
- execute a mutation as a side effect of GET/page rendering.

Environment setup/default initialization, if still required, must be an explicit privileged action or deployment concern.

## P0-04 — TLS verification can be disabled from admin UI

### Planned correction

Production behavior must fail closed:

```text
app()->environment('production') => TLS verification forced true
```

If local/development compatibility requires configurable verification, that exception must remain environment-restricted and clearly represented in the UI.

The integration service must enforce the production rule even if configuration is tampered with.

## P1-01 — Configuration persistence does not match runtime configuration

Current `MuasamcongConfigService` writes many environment keys while `config/muasamcong.php` only reads environment values for token and cookie.

### Planned correction

Make the configuration contract explicit and consistent.

Planned runtime mappings:

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

Each setting presented as editable must either:

1. be read by runtime config and actually take effect; or
2. be removed from the admin editor and treated as deployment-owned fixed configuration.

Security-sensitive destination settings should preferably remain constrained/deployment-owned even if the env mapping is supported.

## P1-02 — Module provider responsibilities overlap canonical loader

Verified runtime currently shows only five routes, so there is no demonstrated duplicate route defect.

However source responsibilities overlap between:

```text
Modules\ModuleServiceProvider
Modules\Muasamcong\Providers\MuasamcongServiceProvider
```

### Planned correction

Refactor conservatively:

- do not change `Modules\ModuleServiceProvider` globally for this module task unless required;
- retain only Muasamcong-specific provider work that the canonical loader does not correctly provide;
- remove redundant route/view/Livewire/console boot registration from the module provider when safe;
- preserve existing config key contract `config('muasamcong.*')`;
- verify exactly one effective web/API route set and existing Livewire aliases after the change.

If preserving `config('muasamcong.*')` requires a minimal provider `register()` method, keeping that minimal provider is preferred over a cross-project loader refactor.

## P1-03 — Missing route/config/security regression coverage

Add targeted tests before/with implementation.

Required cases:

- web search routes use `/admin/muasamcong...`;
- old `/muasamcong...` URLs no longer represent the canonical admin routes;
- route names remain unchanged;
- unauthenticated admin access is denied;
- admin with `view_muasamcong` can access search pages;
- admin with only `view_muasamcong` cannot access/manage config;
- admin with `muasamcong.config.manage` can access config;
- Livewire config save denies unauthorized callers even if invoked directly;
- Livewire token test follows the chosen privileged permission rule;
- existing configured token/cookie are never hydrated into public state;
- unapproved endpoint host is rejected;
- local/private/HTTP destinations are rejected under production-safe rules;
- token/cookie are not sent to a rejected host;
- config mount performs no `.env` write and no `config:clear`;
- runtime config consumes the intended env-backed fields;
- existing upstream schema/error normalization tests continue passing;
- XLSX export regression remains passing;
- route boot still exposes exactly the intended five routes.

## P1-04 — API capability and abuse boundary

Current API uses `auth:sanctum` but has no demonstrated module-specific capability/rate rule.

### Planned correction

Keep current API URIs unchanged.

Evaluate and implement a small explicit rate limiter for upstream-relay search requests if the repository already has an approved rate-limiter convention.

Do not introduce a broad custom API authorization architecture during this refactor.

If capability-level Sanctum/permission integration is not already canonical for the project, keep `auth:sanctum` and document the remaining policy decision instead of inventing one.

## P1-05 — HSMT export architecture

Current export is intentionally bounded:

```text
first upstream page
page_size <= 100
selected rows only
```

This differs from the repository canonical selected-export contract where no selection normally means export all approved records.

### Applicability decision

The canonical "no selection => export all matching records" contract is **not directly applicable** to the current Muasamcong HSMT screen because:

- there is no local persistent dataset;
- only the first upstream page is loaded;
- exporting "all" would require a new upstream pagination/fetch-all business contract;
- that behavior has not been approved and may increase upstream load materially.

### Planned scope

For this security/config refactor:

- preserve selected-row-only HSMT export behavior;
- keep the export bounded to loaded rows;
- keep `HsmtExport` + `Excel::download()` unless a shared abstraction can be adopted without changing semantics;
- do not invent import support;
- do not implement fetch-all/export-all upstream crawling.

A separate import/export task can later redesign this contract if the user explicitly wants full-result export.

## P2-01 — Unused scaffold artifacts

Known candidates:

```text
Modules/Muasamcong/Livewire/Hsmt.php
Modules/Muasamcong/resources/views/livewire/hsmt.blade.php
Modules/Muasamcong/Models/Muasamcong.php
```

### Planned handling

Do not delete these in the first security phase.

After route/Livewire tests prove they are unused, remove them only as a small isolated cleanup commit, or leave them documented if portability/future work still requires them.

## P2-02 — UI/UX polish

The current search views already provide:

- visible form borders;
- validation feedback;
- loading states;
- empty states;
- responsive overflow for tables;
- clear search actions.

### Planned UI changes

Config UI:

- clarify which values are deployment-controlled versus editable;
- do not expose a production toggle that can disable SSL verification;
- provide clear permission-denied behavior via route/action authorization;
- preserve secret masking/non-hydration;
- keep save/test loading states;
- add concise security guidance for token/cookie fields;
- avoid exposing unrestricted arbitrary endpoint editing if endpoints become deployment-owned.

Search UI:

- no broad visual redesign;
- preserve current workspace and tables;
- update navigation/links only where needed for `/admin` prefix.

## Admin List / Workspace Applicability

### Keyword search

Applicable and already present on both search workflows. Preserve validation and loading behavior.

### Domain filters / reset filters

HSMT date range is applicable and already present. Additional filters/reset controls are not required by this refactor because the upstream contract currently exposes a small fixed search request.

### Bounded page sizes / pagination

The upstream page size is bounded to 1–100, but application pagination is not implemented because only upstream page zero is currently supported.

Do not add fake local pagination. Future upstream pagination must be a separate approved functional enhancement.

### Row selection / selected count

Applicable to HSMT and already implemented. Preserve page-scoped selection semantics and selected count.

### Bulk actions / destructive confirmation

Not applicable. The module has no local destructive bulk action.

### Import

Not present and not applicable to current scope.

### Export

Applicable to HSMT. Preserve selected-row-only bounded export during this refactor for the reasons documented above.

### Success/loading/refresh feedback

Search/export loading states already exist. No dataset mutation occurs, so forced refresh/success modal is not required for search. Config save should provide explicit success/error feedback and redirect/refresh only as needed to reload server-backed config safely.

## Files Expected To Change

### Core application files

Likely:

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

Potentially, if hard-coded URLs/navigation are found:

```text
Modules/Muasamcong/resources/views/*.blade.php
```

### Tests

```text
tests/Feature/MuasamcongModuleTest.php
```

A dedicated route authorization test may be added if that produces cleaner coverage, for example:

```text
tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
```

### Documentation

```text
docs/modules/Muasamcong/ANALYSIS.md
docs/modules/Muasamcong/INFORMATION.md
docs/modules/Muasamcong/README.md
docs/modules/Muasamcong/REFACTOR_PLAN.md
```

## Compatibility Constraints

Must preserve unless explicitly stated otherwise:

```text
Route names:
muasamcong.index
muasamcong.hsmt
muasamcong.config

Livewire aliases:
muasamcong.tracuu-thuoctrungthau
muasamcong.search-hsmt
muasamcong.config-manager

API URIs:
/api/muasamcong
/api/muasamcong/search-pricing

Core service behaviors:
searchPricing()
searchHsmt()
testSmartToken()
```

Explicitly approved URI change:

```text
/muasamcong... -> /admin/muasamcong...
```

Existing read permission remains supported:

```text
view_muasamcong
```

New privileged permission:

```text
muasamcong.config.manage
```

No database schema/table contract exists to migrate.

## Database / Migration Impact

```text
None
```

This refactor must not add a database table or migration merely for configuration/search behavior.

If audit logging requires persistent infrastructure, reuse an existing repository audit mechanism if present. Do not create a Muasamcong-specific audit table during this refactor without separate approval.

## Security / Authorization Impact

Expected improvement:

- search and configuration permissions are separated;
- config mutation is authorized at both route and Livewire action boundaries;
- arbitrary server-side destinations are rejected;
- production TLS verification fails closed;
- config page GET becomes side-effect free;
- stored secrets remain server-only;
- secret values continue to be excluded from logs;
- token/cookie are never sent to unapproved hosts.

## Transaction / Data Integrity Impact

No relational database transaction is needed because the module does not persist business records.

Configuration file writes remain high risk.

Implementation should:

- validate the full intended config payload before writing;
- keep the allowlisted key set;
- perform one controlled write per save action;
- use file locking/atomic replacement where practical;
- avoid partial logical updates;
- clear/reload configuration only after a successful write;
- provide a clear failure result without exposing raw exception details.

## Performance Impact

Expected neutral or positive impact.

Constraints:

- keep upstream page size <=100;
- do not add fetch-all crawling;
- do not add retry storms;
- keep HTTP timeout bounded;
- rate-limit API relay if using existing repository conventions;
- keep Livewire result payload bounded.

## Implementation Phases

### Phase R0 — Regression guardrails

Before risky code changes:

1. add route-prefix/name tests;
2. add config authorization tests;
3. add SSRF/host-allowlist tests;
4. add side-effect-free mount tests;
5. preserve existing seven regression tests.

### Phase R1 — Admin route and permission boundary

1. change web prefix to `/admin/muasamcong`;
2. preserve route names;
3. preserve `view_muasamcong` for search routes;
4. add `muasamcong.config.manage` to module manifest;
5. protect config route with the new permission;
6. enforce permission inside sensitive Livewire actions.

### Phase R2 — Configuration hardening/correctness

1. remove `ensureDefaults()` write from mount;
2. reconcile env/config mapping;
3. enforce production TLS verification;
4. restrict upstream URLs to approved host/scheme/path policy;
5. ensure token/cookie are not sent to rejected destinations;
6. make configuration write/reload flow explicit and failure-safe.

### Phase R3 — Provider normalization

1. reduce module-specific provider to the minimum required integration;
2. remove redundant boot-time registration where safe;
3. preserve `config('muasamcong.*')` compatibility;
4. verify exactly five effective routes and expected Livewire aliases.

### Phase R4 — Export/API hardening

1. preserve bounded selected-row export;
2. keep XLSX generation tests;
3. add API rate limiter only if repository conventions support it cleanly;
4. do not add full-result crawling/import.

### Phase R5 — UI/docs/cleanup

1. polish config screen to match hardened rules;
2. update docs to implemented reality;
3. remove scaffold files only if proven unused and still within approved scope;
4. run targeted and full regression checks as appropriate.

## Test Strategy

Targeted mandatory checks:

```bash
php artisan optimize:clear
php artisan route:list --path=muasamcong
php artisan test tests/Feature/MuasamcongModuleTest.php
```

If route authorization tests are split:

```bash
php artisan test tests/Feature/Muasamcong
```

Formatting:

```bash
vendor/bin/pint --test Modules/Muasamcong tests/Feature/MuasamcongModuleTest.php
```

or run Pint on the exact changed PHP files according to repository workflow.

Recommended broader verification after targeted PASS:

```bash
php artisan test
```

because provider/config/permission changes can affect application boot.

## Acceptance Criteria

Implementation is complete only when all applicable criteria hold:

1. web routes are exactly under `/admin/muasamcong...`;
2. existing route names are unchanged;
3. API routes are unchanged;
4. pricing and HSMT search still work through the existing services;
5. existing targeted baseline tests continue to pass;
6. config route requires `muasamcong.config.manage`;
7. config mutation is denied server-side without that permission;
8. `view_muasamcong` alone cannot modify config;
9. config page mount performs no `.env` write or cache clear;
10. unapproved upstream hosts/schemes are rejected;
11. production cannot disable TLS verification;
12. token/cookie are never hydrated from existing server config into public Livewire state;
13. token/cookie are never logged or sent to rejected hosts;
14. editable configuration maps correctly to runtime configuration;
15. effective route registration remains exactly the intended five routes;
16. Livewire aliases remain compatible;
17. HSMT export remains bounded and produces valid XLSX;
18. no database migration is introduced;
19. documentation matches implemented behavior;
20. targeted tests and formatting pass.

## Rollback / Recovery Notes

Route change rollback:

- revert `routes/web.php` prefix change while preserving route names.

Permission rollback:

- retain the newly declared permission if already seeded in environments; removing a permission is more disruptive than leaving an unused capability.

Configuration rollback:

- preserve the previous `.env` content before manual production rollout if configuration write logic changes;
- deployment operators should be able to restore prior `MUASAMCONG_*` values without database rollback;
- do not delete current token/cookie automatically during refactor.

Provider rollback:

- keep provider normalization isolated so it can be reverted without reverting security fixes.

## Explicit Non-Goals

This refactor does not include:

- Full Rebuild;
- local persistence of procurement data;
- migrations/tables/models for HSMT/pricing data;
- automated login/OTP/token harvesting;
- storing credentials in source;
- full upstream pagination or crawling;
- export-all-across-all-upstream-pages;
- spreadsheet import;
- redesign of unrelated Admin UI;
- changes to unrelated modules;
- new global module-loader architecture;
- renaming existing route names or Livewire aliases;
- replacing Laravel HTTP client or Maatwebsite Excel without demonstrated need.

## Approval Gate

Per `.codex/tasks/refactor-module.md`, implementation must stop here until the user explicitly approves this `REFACTOR_PLAN.md`.

Suggested approval command:

```text
Đồng ý implement refactor Muasamcong theo REFACTOR_PLAN.md
```
