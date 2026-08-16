# Muasamcong Module Analysis

## Executive Summary

`Modules/Muasamcong` is a domain module that integrates the Laravel application with public-procurement search endpoints used by `muasamcong.mpi.gov.vn`. Its current user-facing features are:

- search awarded-drug/pricing data;
- search procurement notices/HSMT by keyword and date range;
- export selected HSMT rows to XLSX;
- configure upstream endpoint/session settings;
- expose an authenticated internal API for pricing search;
- provide console smoke-test commands for the upstream integration.

The main business flow is relatively small and already separated into thin controllers, Livewire UI components, an integration service, and an export class. The module does **not** own persistent business data and currently has no migration/database workflow.

The module should **not be fully rebuilt**. The evidence supports a **Major Refactor** focused on security boundaries and repository integration rather than replacing the working search logic.

Most important findings:

1. **P0 — configuration capability is protected only by `view_muasamcong`** even though the page can write `.env`, change upstream URLs, disable TLS verification, store a token/cookie, and clear the application's config cache.
2. **P0 — configurable endpoints create an SSRF / production-control boundary** because a user with the view permission can supply arbitrary `http`/`https` URLs that the server later requests.
3. **P0 — opening the config page can mutate `.env` and clear config cache** through `ConfigManager::mount()` -> `MuasamcongConfigService::ensureDefaults()` -> `Artisan::call('config:clear')`.
4. **P1 — configuration persistence is internally inconsistent**: the configuration UI writes many `MUASAMCONG_*` environment keys, but `config/muasamcong.php` currently reads environment values only for token and session cookie. Several saved values therefore do not become runtime configuration.
5. **P1 — module boot responsibilities are duplicated**: the repository's `Modules\ModuleServiceProvider` automatically registers the module-specific provider and then also registers config/routes/resources/Livewire/console itself, while `MuasamcongServiceProvider` independently performs the same work.
6. **P1 — export does not use the repository canonical `Modules/Shared/Services/ImportExport` foundation** and is intentionally limited to selected rows from the first upstream page.
7. Automated tests cover safe secret hydration, upstream-response validation, connection errors and basic XLSX generation, but do not yet cover route authorization, config mutation denial, endpoint allowlisting/SSRF, duplicate route boot, or API capability/rate boundaries.

No P0 evidence of an already-exposed hard-coded token/cookie was found in the inspected current source. The current code deliberately avoids hydrating existing secrets into Livewire public state and avoids logging their values.

## Module Purpose and Overview

Canonical module path:

```text
Modules/Muasamcong/
```

Module type from `config/module.php`:

```text
domain
```

Current manifest permission:

```text
view_muasamcong
```

Main integration owner:

```text
Modules\Muasamcong\Services\MuaSamCongService
```

Configuration writer:

```text
Modules\Muasamcong\Services\MuasamcongConfigService
```

The module is an integration/search module rather than a persistence module. It sends bounded search requests to upstream procurement endpoints and displays the returned rows without persisting them locally.

## Bootstrap / Standards Context

The repository uses:

- Laravel 12 / PHP 8.3;
- Livewire 3;
- first-party modular monolith under `Modules/`;
- `Modules\ModuleServiceProvider` as the canonical module discovery/registration layer;
- Spatie permission for named capabilities;
- Sanctum for authenticated APIs;
- `Modules/Shared/Services/ImportExport` as the preferred import/export foundation;
- `Admin::layouts.master` for admin UI composition.

Relevant repository standards require:

- capability-specific authorization for privileged operations;
- mutating Livewire actions to enforce authorization at the action boundary;
- no arbitrary browser-controlled server targets/paths without allowlists;
- production-safe file and secret handling;
- bounded large data operations;
- module business workflows to remain in services;
- module-specific providers not to duplicate canonical repository registration without a demonstrated requirement.

## Dependency Graph

### Pricing lookup

```text
GET /muasamcong
-> MuasamcongController::index()
-> Muasamcong::muasamcong Blade page
-> muasamcong.tracuu-thuoctrungthau Livewire
-> TracuuThuoctrungthau::search()
-> MuaSamCongService::searchPricing()
-> Laravel HTTP client
-> muasamcong.mpi.gov.vn pricing endpoint
-> normalized first-page result
-> Livewire table
```

### HSMT lookup/export

```text
GET /muasamcong/hsmt
-> MuasamcongController::hsmt()
-> Muasamcong::hsmt Blade page
-> muasamcong.search-hsmt Livewire
-> SearchHsmt::search()
-> MuaSamCongService::searchHsmt()
-> Laravel HTTP client
-> contractor-selection search endpoint
-> normalized first-page result
-> row selection
-> SearchHsmt::exportExcel()
-> MuaSamCongService::exportRows()
-> HsmtExport
-> Maatwebsite Excel download
```

### Configuration

```text
GET /muasamcong/config
-> MuasamcongController::config()
-> Muasamcong::config Blade page
-> muasamcong.config-manager Livewire
-> ConfigManager::mount()
   -> MuasamcongConfigService::ensureDefaults()
   -> may write .env
   -> may Artisan::call('config:clear')

ConfigManager::save()
-> validation
-> MuasamcongConfigService::update()
-> writes base_path('.env')
-> Artisan::call('config:clear')

ConfigManager::testToken()
-> MuaSamCongService::testSmartToken()
-> upstream HTTP request
```

### API

```text
POST /api/muasamcong/search-pricing
-> auth:sanctum
-> Api\MuasamcongController::searchPricing()
-> request validation
-> MuaSamCongService::searchPricing()
-> upstream HTTP request
-> normalized JSON response
```

## Route / Controller / Blade / Livewire Analysis

### Routes

Files:

```text
Modules/Muasamcong/routes/web.php
Modules/Muasamcong/routes/api.php
```

Web routes:

| Method | URI | Route name | Current middleware |
|---|---|---|---|
| GET | `/muasamcong` | `muasamcong.index` | configured web/admin auth/view permission |
| GET | `/muasamcong/hsmt` | `muasamcong.hsmt` | configured web/admin auth/view permission |
| GET | `/muasamcong/config` | `muasamcong.config` | configured web/admin auth/view permission |

Default configured web middleware is:

```text
web
admin authentication
permission:view_muasamcong on admin guard
```

API routes:

| Method | URI | Current boundary |
|---|---|---|
| GET | `/api/muasamcong` | `auth:sanctum` |
| POST | `/api/muasamcong/search-pricing` | `auth:sanctum` |

The route-level read boundary is present. The material issue is that `/config` is not a read-only capability; the resulting Livewire component can mutate application configuration.

### Controllers

Files:

```text
Modules/Muasamcong/Http/Controllers/MuasamcongController.php
Modules/Muasamcong/Http/Controllers/Api/MuasamcongController.php
```

The web controller is thin and only returns page views. This follows repository architecture.

The API controller validates `keyword` and delegates the upstream workflow to `MuaSamCongService`. Error status normalization is also kept small. No business query logic is embedded in the controller.

### Page Blade

Files:

```text
Modules/Muasamcong/resources/views/muasamcong.blade.php
Modules/Muasamcong/resources/views/hsmt.blade.php
Modules/Muasamcong/resources/views/config.blade.php
```

All three extend `Admin::layouts.master`, provide a clear page title/description and delegate interactive behavior to Livewire. This is consistent with the Page Blade shell rule.

### Livewire: TracuuThuoctrungthau

File:

```text
Modules/Muasamcong/Livewire/TracuuThuoctrungthau.php
```

Positive findings:

- validates keyword;
- delegates HTTP/integration logic to service;
- does not persist upstream data;
- has explicit error state;
- UI provides loading and empty states;
- result count is bounded indirectly by configured page size.

Current limitation:

- only the first upstream page is requested.

### Livewire: SearchHsmt

File:

```text
Modules/Muasamcong/Livewire/SearchHsmt.php
```

Positive findings:

- validates keyword/date range;
- delegates upstream call to service;
- page size is server-bounded to maximum 100;
- selection is scoped to returned rows;
- export validates that at least one row is selected;
- UI explicitly tells the operator that only the first page is displayed.

Concerns:

- result rows are held in Livewire public state. This is currently bounded to <=100 rows, so it is not an unbounded-memory problem, but upstream rows can be wide and Livewire snapshots may become large.
- export is a direct `Excel::download()` of current in-memory selected rows and bypasses the shared import/export abstraction.
- the checkbox normalization logic partially lives in Blade (`notifyNo` normalization). This is small presentation-adjacent logic but can be normalized before rendering for cleaner boundaries.

### Livewire: ConfigManager

File:

```text
Modules/Muasamcong/Livewire/ConfigManager.php
```

Positive findings:

- existing smart token/session cookie are not hydrated into public Livewire state;
- secret inputs are validated for length and line breaks;
- user-facing exceptions are converted to safe messages;
- test-token supports temporary values without requiring save.

Critical boundary concerns are documented in the issue list below.

### Livewire: Hsmt scaffold

```text
Modules/Muasamcong/Livewire/Hsmt.php
Modules/Muasamcong/resources/views/livewire/hsmt.blade.php
```

This is currently a placeholder/scaffold and is not part of the active HSMT route flow. It is technical debt rather than a functional defect.

## Service Analysis

### MuaSamCongService

File:

```text
Modules/Muasamcong/Services/MuaSamCongService.php
```

Responsibilities:

- construct pricing/HSMT payloads;
- obtain endpoint and HTTP settings from Laravel config;
- attach token/cookie when required;
- call the upstream service;
- map connection/upstream/schema failures to safe result arrays;
- normalize the upstream page response;
- map selected HSMT rows for export.

Positive findings:

- catches `ConnectionException` and general throwable around HTTP operations;
- logs only upstream host and exception class, not token/cookie/body;
- validates response shape before exposing result rows;
- returns safe generic messages rather than raw exception text;
- page size is clamped from 1 to 100;
- token is transmitted as required by the upstream endpoint but is not logged;
- cookie is kept in request headers only.

Security concern:

- the service trusts endpoint/referer/origin values from configuration. Because the current admin UI can rewrite those values, the effective HTTP destination becomes browser-controlled after config save. This requires server-side host/scheme allowlisting or a design where endpoints are deployment-controlled rather than general UI fields.

Performance:

- each search performs a single synchronous upstream request;
- timeout is configurable;
- page size is bounded;
- there is no retry/backoff/circuit breaker/rate-control layer;
- no local caching is used.

For current first-page interactive searches this is acceptable at small scale, but repeated API/UI calls can place load on the upstream service and tie up PHP workers until timeout.

### MuasamcongConfigService

File:

```text
Modules/Muasamcong/Services/MuasamcongConfigService.php
```

Responsibilities:

- ensure expected `MUASAMCONG_*` keys exist in root `.env`;
- replace/update those keys;
- quote values for dotenv formatting.

Positive findings:

- uses a fixed key allowlist;
- rejects CR/LF in values;
- checks that `.env` exists and is writable;
- passes the lock flag to `File::put`.

Concerns:

- web UI writes the global application `.env` file;
- read-like `mount()` may invoke a write via `ensureDefaults()`;
- update rewrites the entire `.env` file content;
- there is no dedicated audit event for who changed configuration;
- the update and global `config:clear` operation do not form a recoverable/transactional configuration workflow;
- a successful write can alter global runtime behavior beyond this module by clearing config cache.

## Import / Export Analysis

### Import

**Not present.**

The module currently consumes upstream HTTP data but does not import spreadsheet rows into local persistence.

### Export

Files:

```text
Modules/Muasamcong/Exports/HsmtExport.php
Modules/Muasamcong/Livewire/SearchHsmt.php
Modules/Muasamcong/Services/MuaSamCongService.php
```

Current behavior:

- operator searches one upstream page;
- selects one or more returned HSMT rows;
- service maps selected rows to six columns;
- `HsmtExport` implements `FromArray` + `WithHeadings`;
- response is downloaded directly as XLSX.

The current export is bounded by first-page `page_size <= 100`, so there is no immediate large-dataset memory risk.

Repository-consistency gap:

- it does not use `Modules/Shared/Services/ImportExport` or the shared UI foundation.

Because this export is small and ephemeral, migration to shared infrastructure should preserve the lightweight direct-download UX unless the shared contract provides material benefits such as consistent authorization, file retention, selected/all scope, reporting or future larger exports.

## Shared Dependencies

Direct framework/package dependencies:

- Laravel HTTP client;
- Livewire 3;
- Maatwebsite Excel;
- Laravel Sanctum;
- Spatie permission via route middleware;
- Admin module layout.

Cross-module presentation dependency:

```text
Admin::layouts.master
```

Canonical shared import/export dependency is **not currently used**.

No direct business-model dependency on another domain module was observed.

## Model / Migration / Database Analysis

### Model

File:

```text
Modules/Muasamcong/Models/Muasamcong.php
```

The class is an unused Eloquent scaffold with commented example properties. No active flow references it in the inspected source.

### Migrations

**Not present.**

### Database tables

**Not present / not owned by this module.**

The active module is stateless with respect to application persistence.

Recommendation: remove or explicitly document the unused model scaffold during a later cleanup refactor if no future persisted domain object is planned. Do not create a database table merely to justify the model.

## Security

Positive current controls:

- web pages default to `auth:admin` plus named permission;
- API defaults to `auth:sanctum`;
- secret values already stored in config are not copied into Livewire public state;
- token/cookie values are not logged by the integration service;
- raw upstream exceptions are not returned to users;
- request keyword/date validation exists;
- page size is bounded.

Material risks:

1. configuration changes use a read-named permission;
2. mutation authorization is not explicitly enforced inside `ConfigManager::save()`/`testToken()`;
3. endpoint/origin/referer are browser-editable and can become server-side request destinations;
4. TLS verification can be disabled from the UI;
5. config page mount can write `.env` and clear global config cache;
6. no module-specific audit record is produced for secret/endpoint/config changes;
7. API search has authentication but no demonstrated capability-specific permission or module-specific rate limit.

## Performance

Current positive bounds:

- one upstream page per search;
- page size is clamped to max 100;
- no unbounded database query exists;
- export uses at most the current bounded page.

Risks/limitations:

- synchronous external HTTP can consume PHP workers until timeout;
- repeated authenticated API requests can relay high request volume upstream;
- Livewire keeps complete upstream row arrays in component state;
- no retry/backoff/rate-limiting strategy is visible;
- total upstream result count can exceed displayed/exportable rows because only page zero is retrieved.

## Validation and Authorization

Validation quality is generally good for user-entered search and configuration fields.

Key authorization gap:

```text
view_muasamcong
```

is sufficient to reach a Livewire component that can modify `.env`, clear config cache, alter network targets and disable TLS verification.

Recommended capability split:

```text
muasamcong.view
muasamcong.search
muasamcong.export
muasamcong.config.manage
```

Exact permission naming should be reconciled with the repository's existing naming migration policy; do not silently break `view_muasamcong` without compatibility handling.

`muasamcong.config.manage` (or equivalent canonical naming) should be required both at the config route boundary and inside every Livewire action that mutates/tests privileged configuration.

## Transactions, Concurrency and Data Integrity

No database transaction is required for search/export because the module does not persist rows.

The configuration workflow has a different integrity concern:

```text
.env rewrite
-> config cache clear
```

This is a global application configuration mutation, not a transactional database workflow. It should be treated as production-control configuration with:

- dedicated authorization;
- server-controlled allowed fields;
- audit logging;
- atomic/recoverable update strategy if retained;
- explicit production policy;
- avoidance of side effects from component `mount()`.

## Admin UI / UX Standard Review

Positive findings:

- uses canonical admin layout;
- clear page headings and descriptions;
- visible bordered form controls;
- responsive Tailwind layout;
- clear validation messages;
- table horizontal overflow handling;
- explicit empty/loading/error states;
- HSMT selection semantics are understandable;
- export button exposes selected count;
- config secrets use password controls and do not reveal stored values.

Improvement opportunities:

- ordinary search input fields use `wire:model.live` although search occurs on submit; deferred binding can reduce unnecessary Livewire requests;
- config form uses live binding for nearly every field even though values are saved on submit;
- config UI exposes advanced deployment/network settings too prominently; endpoint/origin/referer/TLS should be deployment-controlled or moved behind a strongly privileged advanced section if runtime editing is genuinely required;
- HSMT table intentionally represents only the first page; a future pagination/fetch-more UX should be considered only if business requirements need more than the first page.

## Cross-Module Dependencies

Observed:

- `Admin` for layout shell;
- repository `Modules\ModuleServiceProvider` for discovery/registration;
- shared import/export infrastructure is available but unused.

No circular business-domain dependency was observed.

## Technical Debt

- module-specific `MuasamcongServiceProvider` duplicates work already performed by canonical `Modules\ModuleServiceProvider`;
- `module.json` is a portability artifact not used by the canonical repository manifest system;
- module-level `composer.json` advertises Laravel package provider auto-discovery, which is not the repository's normal module-loading mechanism;
- module README contains portable-package integration instructions that can conflict with this repository's canonical loader;
- unused `Models/Muasamcong.php` scaffold;
- unused `Livewire/Hsmt.php` + tiny view scaffold;
- direct XLSX path bypasses shared import/export foundation;
- config environment behavior and documentation are out of sync with runtime config source.

## Test Coverage

Existing targeted test:

```text
tests/Feature/MuasamcongModuleTest.php
```

Covered:

- stored token/cookie are not hydrated into Livewire public state;
- token can be tested without saving;
- pricing response schema normalization;
- invalid upstream schema safe error;
- connection exception safe error;
- missing HSMT token safe error;
- XLSX generation produces a non-empty workbook.

Missing high-value coverage:

- web routes require admin guard and the expected named permissions;
- config route requires a dedicated config-management permission;
- Livewire `save()` denies unauthorized callers even if directly invoked;
- `testToken()` privilege behavior;
- allowed upstream host/scheme enforcement;
- production cannot disable SSL verification if that becomes the policy;
- `.env` update behavior / atomicity or replacement configuration store;
- route registration is not duplicated;
- API search permission/rate policy;
- config values saved by UI are actually consumed by runtime config;
- export selection normalization and malicious/tampered selected IDs;
- first-page limitation contract.

The analysis task did not execute tests because it operates through the GitHub repository connector rather than the user's local runtime. Local verification commands are listed under Open Questions / Unknowns.

## Documentation Drift

Existing module-local `Modules/Muasamcong/README.md` is useful historical/portable documentation, but parts no longer match repository conventions or current source behavior.

Observed drift:

- it promotes explicit registration of `MuasamcongServiceProvider`, while this repository's `Modules\ModuleServiceProvider` discovers modules and itself registers module-specific providers;
- it lists a portable `module.json`/provider pattern that is not the canonical repository architecture;
- it documents many `MUASAMCONG_*` environment variables as active runtime configuration even though current `config/muasamcong.php` only reads `MUASAMCONG_SMART_TOKEN` and `MUASAMCONG_SESSION_COOKIE` from env;
- `.env.example` currently says endpoint/timeouts live in module config and only includes token/cookie placeholders, which conflicts with the configuration UI/service that writes additional environment keys;
- README architecture list omits `ConfigManager` / `MuasamcongConfigService` in some earlier structure descriptions and contains legacy portability instructions.

This new `docs/modules/Muasamcong/*` documentation should be treated as repository-specific analysis. The module-local README can be reconciled during the approved refactor/cleanup phase.

## Issue List (P0/P1/P2)

### P0-01 — Read permission grants production configuration mutation

**Priority:** P0  
**File:** `Modules/Muasamcong/config/muasamcong.php`, `routes/web.php`, `Livewire/ConfigManager.php`  
**Evidence:** all web routes use `permission:view_muasamcong,admin`; `ConfigManager::save()` writes configuration and clears config cache.  
**Problem:** a capability named and apparently intended for viewing also grants the ability to modify application-level integration configuration and secrets. The mutating Livewire method has no explicit capability check of its own.  
**Impact:** unauthorized configuration modification, secret replacement, service disruption and production-control risk for any role granted read access.  
**Recommendation:** create/use a dedicated config-management permission, protect the config route with it, and authorize `save()` plus other privileged configuration actions server-side. Preserve compatibility for existing `view_muasamcong` only for read/search routes.

### P0-02 — Browser-editable network destinations allow SSRF-style server requests

**Priority:** P0  
**File:** `Livewire/ConfigManager.php`, `Services/MuasamcongConfigService.php`, `Services/MuaSamCongService.php`  
**Evidence:** config validation accepts arbitrary `http`/`https` URL values for origin/endpoints/referers; the service later sends server-side HTTP requests to configured endpoint URLs.  
**Problem:** a privileged browser user can control the server request destination instead of selecting from a server-side allowlist.  
**Impact:** potential access attempts against internal/private HTTP services, credential/token forwarding to unintended hosts, or controlled outbound traffic.  
**Recommendation:** make upstream hosts/endpoints deployment-controlled or enforce a strict server-side allowlist for scheme + host + allowed paths before persisting and again before requesting. Never send smart token/session cookie to a destination outside the approved procurement host set.

### P0-03 — Config page mount mutates `.env` and global config cache

**Priority:** P0  
**File:** `Livewire/ConfigManager.php`, `Services/MuasamcongConfigService.php`  
**Evidence:** `mount()` calls `ensureDefaults()`; missing keys cause `.env` write; success then triggers `Artisan::call('config:clear')`.  
**Problem:** opening a configuration page performs a global state mutation under a read-style route permission.  
**Impact:** unexpected production config mutation and cache invalidation from page navigation; difficult audit/recovery behavior.  
**Recommendation:** remove mutation from `mount()`. Provision defaults through deployment/config files or an explicit separately authorized setup action/command. Do not globally clear config cache as a side effect of page render.

### P0-04 — TLS verification can be disabled through the same broad UI capability

**Priority:** P0  
**File:** `Livewire/ConfigManager.php`, `Services/MuaSamCongService.php`  
**Evidence:** `form.verify_ssl` is editable and HTTP client passes the config value to Guzzle `verify`.  
**Problem:** production operators with broad view access can disable server certificate verification.  
**Impact:** upstream identity verification can be weakened, increasing interception/credential exposure risk.  
**Recommendation:** fail closed in production with TLS verification forced on. If local development needs an exception, gate it by environment and keep it outside ordinary production UI.

### P1-01 — Saved environment settings are not consumed consistently

**Priority:** P1  
**File:** `config/muasamcong.php`, `Livewire/ConfigManager.php`, `Services/MuasamcongConfigService.php`  
**Evidence:** configuration UI/service writes origin, TLS, timeout, user-agent, endpoints, referers and page size as `MUASAMCONG_*`; current config file only uses env for smart token and session cookie.  
**Problem:** the UI can report a successful save even though many persisted environment values do not control runtime configuration.  
**Impact:** misleading administration, inability to apply intended configuration, difficult debugging and potentially incorrect security assumptions.  
**Recommendation:** define one canonical configuration source. Prefer deployment-controlled defaults with env overrides where required, and make every editable field map deterministically to the config value used by `MuaSamCongService`.

### P1-02 — Module provider duplicates canonical module registration

**Priority:** P1  
**File:** `Modules/ModuleServiceProvider.php`, `Modules/Muasamcong/Providers/MuasamcongServiceProvider.php`  
**Evidence:** canonical loader registers a module-specific provider when found, then independently registers config/routes/resources/Livewire/console. `MuasamcongServiceProvider` also registers config/routes/views/Livewire/commands.  
**Problem:** the module has two registration paths for the same resources.  
**Impact:** duplicate routes/commands/component registration, confusing config precedence, harder maintenance and fragility when repository bootstrap changes.  
**Recommendation:** during refactor, align Muasamcong with the canonical repository loader and retain a module-specific provider only for responsibilities that canonical infrastructure cannot provide. Add a route/boot regression test before removal.

### P1-03 — Configuration key shape depends on the duplicate provider

**Priority:** P1  
**File:** `Modules/ModuleServiceProvider.php`, `Modules/Muasamcong/Providers/MuasamcongServiceProvider.php`, `Modules/Muasamcong/config/muasamcong.php`  
**Evidence:** generic loader merges module config files under `<module>.<file>` while the module-specific provider merges `config/muasamcong.php` directly as `muasamcong`; application code reads `muasamcong.origin`, `muasamcong.endpoints.*`, etc.  
**Problem:** simply deleting the module provider would change the effective config namespace.  
**Impact:** integration failure if provider cleanup is performed without a config migration plan.  
**Recommendation:** treat provider removal and config namespace normalization as one compatibility-safe refactor with tests.

### P1-04 — API is authenticated but capability/rate policy is incomplete

**Priority:** P1  
**File:** `routes/api.php`, `Http/Controllers/Api/MuasamcongController.php`  
**Evidence:** API requires Sanctum but no module-specific permission or rate middleware is visible in the route file.  
**Problem:** every otherwise-valid Sanctum caller may be able to relay upstream searches if no broader application policy restricts it.  
**Impact:** upstream abuse/load, application worker consumption and unclear authorization ownership.  
**Recommendation:** define who may call the integration API and apply a named capability and rate limit appropriate to upstream terms/capacity.

### P1-05 — Missing security/regression tests for critical boundaries

**Priority:** P1  
**File:** `tests/Feature/MuasamcongModuleTest.php`  
**Evidence:** existing tests cover integration safety and XLSX generation but not route permission, config action authorization, host allowlist or duplicate route registration.  
**Problem:** the most sensitive behavior is not regression-locked.  
**Impact:** future changes can re-open configuration/network/security failures without test detection.  
**Recommendation:** add targeted route, Livewire authorization, config/SSRF and registration tests before or together with the refactor.

### P1-06 — Export bypasses canonical shared import/export foundation

**Priority:** P1  
**File:** `Livewire/SearchHsmt.php`, `Exports/HsmtExport.php`  
**Evidence:** export uses `Excel::download(new HsmtExport(...))` directly and does not reuse `Modules/Shared/Services/ImportExport`.  
**Problem:** module-specific export behavior diverges from repository's target shared contract.  
**Impact:** inconsistent authorization/storage/error/selected-scope behavior if export functionality expands.  
**Recommendation:** evaluate migration to the shared foundation during refactor. Preserve current bounded selected-row export semantics unless business requirements change.

### P2-01 — First-page-only search/export limitation

**Priority:** P2  
**File:** `Services/MuaSamCongService.php`, `Livewire/SearchHsmt.php`, pricing Livewire flow  
**Evidence:** request payload always uses `pageNumber = 0`; UI states HSMT is showing the first page.  
**Problem:** users cannot browse/export results beyond the configured first page.  
**Impact:** incomplete discovery for broad searches.  
**Recommendation:** keep current behavior if intentional. If full discovery is required, add explicit bounded pagination/fetch-more rather than unbounded all-results loading.

### P2-02 — Dead/scaffold artifacts

**Priority:** P2  
**File:** `Models/Muasamcong.php`, `Livewire/Hsmt.php`, `resources/views/livewire/hsmt.blade.php`, `module.json`  
**Evidence:** active route/service flows do not use these scaffold files; repository does not use `module.json` as canonical manifest.  
**Problem:** stale artifacts obscure the real module architecture.  
**Impact:** developer confusion and portability patterns leaking into repository-specific work.  
**Recommendation:** confirm no external consumer requires them, then remove/retire during cleanup; do not delete them during analysis.

### P2-03 — Excessive live bindings for submit-driven forms

**Priority:** P2  
**File:** Livewire Blade views  
**Evidence:** many search/config inputs use `wire:model.live` even though actions are submitted explicitly.  
**Problem:** unnecessary Livewire synchronization requests.  
**Impact:** extra network chatter and server work.  
**Recommendation:** use deferred/non-live binding where immediate server synchronization is not needed.

### P2-04 — Repository-specific documentation drift

**Priority:** P2  
**File:** `Modules/Muasamcong/README.md`, `.env.example`, module-local composer/module metadata  
**Evidence:** portable provider/env instructions differ from current canonical repository bootstrap and runtime config implementation.  
**Problem:** future maintainers may follow incompatible integration instructions.  
**Impact:** duplicate provider registration, incorrect configuration expectations and unnecessary troubleshooting.  
**Recommendation:** reconcile module-local README/metadata after implementation decisions are approved.

## Module Health Summary

| Dimension | Assessment |
|---|---|
| Business separation | Good |
| Controller design | Good |
| Livewire UI separation | Good with config-boundary exception |
| Validation | Good |
| Upstream error handling | Good |
| Secret exposure in current UI | Good for hydration/logging |
| Authorization | **Critical gap around config capability** |
| Network target security | **Critical gap** |
| Configuration consistency | Needs refactor |
| Repository module registration | Needs refactor |
| Database design | Not applicable |
| Performance bounds | Acceptable for first-page searches |
| Export architecture | Functional but not canonical |
| Automated tests | Useful base, incomplete security coverage |
| Documentation | Useful but drifted |

## Final Recommendation

**Major Refactor**

Rationale:

- the search/integration logic is small, readable and mostly correctly separated;
- a rebuild would create unnecessary regression risk;
- the security/configuration boundary requires structural changes across routes, permissions, Livewire config actions, configuration source and HTTP destination policy;
- provider/config registration needs careful compatibility-safe cleanup;
- tests should be expanded before modifying those boundaries.

Recommended implementation order after approval:

```text
Phase 0 — Regression locks
-> route authorization tests
-> Livewire config denial tests
-> endpoint allowlist/SSRF tests
-> config mapping tests
-> route registration tests

Phase 1 — Security containment
-> dedicated config permission
-> authorize Livewire actions
-> remove mount-side mutation
-> force TLS verification in production
-> upstream host/path allowlist
-> API capability/rate policy

Phase 2 — Configuration architecture
-> canonical env/config mapping
-> remove unsafe/global config-cache side effects where possible
-> normalize provider/config registration

Phase 3 — Export/repository consistency
-> evaluate Shared ImportExport integration
-> preserve bounded selected export

Phase 4 — Cleanup/docs
-> remove confirmed dead scaffolds
-> reconcile module-local README/.env example/portable metadata
```

## Open Questions / Unknowns

The following require local/runtime verification before implementation is considered complete:

1. Does `php artisan route:list --path=muasamcong` currently show duplicate route entries due to dual registration? Source strongly indicates duplicate registration paths, but runtime route collection behavior should be verified.
2. Does the current root bootstrap register `Modules\ModuleServiceProvider` exactly once in the target deployment? Repository bootstrap documentation says yes; local runtime should confirm.
3. Which existing roles besides Super Admin currently possess `view_muasamcong`? This determines exposure of the config mutation boundary.
4. Is runtime editing of upstream endpoints/origin/referers a real operational requirement, or can these remain deployment-only configuration?
5. Does production use `config:cache`? If yes, the current web-driven `config:clear` behavior has broader operational impact and should be removed early.
6. Are there external consumers depending on the module-local portable `composer.json`, `module.json`, or `MuasamcongServiceProvider`? Confirm before cleanup.
7. Does the upstream provider permit automated/request-relay usage, and what rate limits/terms apply? Operational policy should be verified outside source code.
8. Is first-page-only HSMT/pricing search acceptable as a product requirement? If not, define bounded pagination before implementation.

Suggested local verification commands:

```bash
git checkout agent/muasamcong-analysis
git pull origin agent/muasamcong-analysis

php artisan optimize:clear
php artisan route:list --path=muasamcong
php artisan test tests/Feature/MuasamcongModuleTest.php
vendor/bin/pint --test Modules/Muasamcong tests/Feature/MuasamcongModuleTest.php
```

This analysis modified documentation only. No application source code was changed.
