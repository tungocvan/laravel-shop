# System Livewire Analysis — Settings/AdvancedConfig

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/AdvancedConfig.php` manages queue and NodeJS bridge configuration, can dispatch a queue test job, poll queue status, ping the NodeJS bridge, and persist `QUEUE_CONNECTION`, `NODEJS_SERVER_URL`, and `BRIDGE_SECRET_KEY` into `.env` through `EnvManagerService`.

The component is **P1 / Major Refactor** because sensitive environment mutations and operational tests do not enforce `system.env.update`, the bridge secret is hydrated into public Livewire state, and field validation/allowlisting is weak. The underlying env write service is substantially safer than the Livewire boundary and should be retained.

## Component Purpose

Path: `Modules/System/Livewire/Settings/AdvancedConfig.php`

Alias: `system.settings.advanced-config`

View: `System::livewire.settings.advanced-config`

Responsibilities:

- manage queue driver;
- manage NodeJS server URL;
- manage bridge secret;
- dispatch test queue job;
- check queue status;
- ping NodeJS server;
- persist configuration.

## Dependency Flow

`/admin/system/settings/env`
→ dynamic env tab
→ `AdvancedConfig`
→ `EnvManagerService` / `Modules\System\Services\Env\SystemConfigService`
→ `.env`, queue, NodeJS bridge

## Livewire PHP Analysis

Public state contains:

- `QUEUE_CONNECTION`
- `NODEJS_SERVER_URL`
- `BRIDGE_SECRET_KEY`
- queue and node status strings

`mount()` hydrates current env values into the form.

Actions:

- `testQueue()` dispatches a test job.
- `refreshQueueStatus()` polls status.
- `checkNode()` sends the configured URL and bridge secret to the service.
- `save()` writes all form values to `.env`.

No sensitive action contains explicit capability authorization.

## Livewire Blade Analysis

The Blade provides:

- queue driver select;
- queue test action with polling every two seconds while pending/processing;
- NodeJS URL field;
- masked bridge-secret input;
- NodeJS connection test;
- save action.

The UI has useful status feedback but Save has no loading/disabled state, and secret masking does not prevent the existing secret from being present in Livewire state.

## State / Validation / Actions

No explicit validation rules are present.

`QUEUE_CONNECTION` should be allowlisted to repository-supported drivers.

`NODEJS_SERVER_URL` should be validated as an expected HTTP(S) endpoint and constrained according to deployment policy. Because the application server performs the request, user-controlled/internal URLs should be evaluated for SSRF exposure.

`BRIDGE_SECRET_KEY` should use replacement-secret semantics instead of preloading the existing value.

## Authorization

**P1:** `system.env.update` exists in the module manifest but is not enforced by `testQueue()`, `checkNode()`, or `save()`.

Read access to the env settings page is not sufficient authorization for operational mutation or secret replacement.

## Service / Model Dependencies

`EnvManagerService` has strong file-level safeguards: candidate validation, safety backup, file lock, in-place writes, and restoration on write failure.

`SystemConfigService` is responsible for NodeJS ping, queue dispatch, and status. These operations should remain service-owned, with Livewire responsible for authorization, validation and UI state.

## Performance

Queue status polling every two seconds is bounded to pending/processing states, which is reasonable for a short-lived test but may create repeated requests if status never transitions. A timeout/attempt cap should be considered.

Node health checks are synchronous remote calls and should have strict connection/overall timeouts in the service.

## Security / Data Integrity

### P1 — Missing action authorization

All operational/mutation actions require explicit capability checks.

### P1 — Bridge secret hydration

Existing `BRIDGE_SECRET_KEY` is loaded into public Livewire state. Password input masking is not secret isolation.

### P1 — URL validation / SSRF boundary

`NODEJS_SERVER_URL` is operator-controlled and later used for server-side connectivity checks. The service should restrict acceptable schemes/hosts according to deployment policy or explicitly document why arbitrary internal URLs are required.

### P1 — Configuration validation

The component writes queue/bridge values without validating supported driver values or URL/secret constraints.

## UI/UX Compliance

Positive:

- responsive grouping;
- clear queue/node sections;
- visible health state;
- automatic polling only during active test states.

Needs improvement:

- validation feedback;
- loading/disabled Save state;
- timeout feedback for queue polling;
- replacement-secret UX instead of prefilled secret.

## Test Coverage

No System-specific test was found for this component.

Missing critical coverage:

- unauthorized mutation/test rejection;
- allowed queue driver validation;
- Node URL validation;
- secret preservation/replacement;
- queue polling terminal states;
- safe handling of NodeJS failure responses.

## Issue List

### P1 — Missing `system.env.update` authorization
**Recommendation:** authorize each mutation/operational action at the Livewire boundary.

### P1 — Secret exposed through public component state
**Recommendation:** do not hydrate the current bridge secret; accept only an optional replacement value.

### P1 — NodeJS URL needs a defined trust policy
**Recommendation:** validate scheme/host and protect server-side ping behavior against unintended internal-network probing.

### P2 — Polling can continue indefinitely
**Recommendation:** add a bounded polling/test lifecycle.

## Recommended Direction

**Major Refactor.** Preserve the current env/service infrastructure; improve action authorization, secret handling, validation, remote-call policy, and tests.

## Open Questions / Unknowns

- Whether arbitrary NodeJS endpoints are intentionally supported across environments.
- Exact timeout and host validation currently implemented inside the env `SystemConfigService` should be verified during refactor.
- Whether queue driver changes require process supervisor restart/reload in production.
