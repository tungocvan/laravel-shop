# System Livewire Analysis — Settings/MomoConfig

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/MomoConfig.php` manages MoMo endpoint and credentials in `.env` and can test the configured endpoint. It is **P1 / Major Refactor** because credential updates do not enforce `system.env.update`, access/secret keys are hydrated into public Livewire state, endpoint validation is absent, and `testEndpoint()` performs a server-side HTTP request to operator-controlled input without a defined host trust policy.

## Component Purpose

Path: `Modules/System/Livewire/Settings/MomoConfig.php`

Alias: `system.settings.momo-config`

PHP view: `Admin::livewire.settings.momo-config`

System-local view exists: `Modules/System/resources/views/livewire/settings/momo-config.blade.php`.

Responsibilities:

- load MoMo endpoint/partner/access/secret values;
- test endpoint reachability;
- persist values to `.env`.

## Dependency Flow

`/admin/system/settings/env`
→ env tab
→ `MomoConfig`
→ `Http` / `EnvManagerService`
→ external endpoint / `.env`

## Livewire PHP Analysis

Public form includes:

- `MOMO_ENDPOINT`
- `MOMO_PARTNER_CODE`
- `MOMO_ACCESS_KEY`
- `MOMO_SECRET_KEY`

`mount()` loads all existing values into public state.

`testEndpoint()` sends a GET request to the configured endpoint, or to the MoMo test endpoint when empty.

`save()` writes the complete form through `EnvManagerService`.

No explicit authorization or validation is present.

## Livewire Blade Analysis

The System-local Blade provides separate fields for endpoint, partner code, access key and masked secret key, plus Test Connection and Save actions.

There are no inline validation errors and neither button shows a robust loading/disabled state.

The PHP component renders an Admin-owned view rather than the System-local Blade, which is module ownership drift.

## State / Validation / Actions

Actions:

- `testEndpoint()`
- `save()`

Required validation should include:

- HTTPS URL policy;
- approved MoMo host/environment policy where appropriate;
- partner/access-key length constraints;
- replacement-secret behavior;
- optional distinction between sandbox and production credentials.

## Authorization

**P1:** no `system.env.update` authorization is enforced inside save/test actions.

## Service / Model Dependencies

Environment persistence correctly delegates to `EnvManagerService`, which already has file validation, safety backup, locking and rollback behavior.

HTTP endpoint testing currently occurs directly in Livewire rather than through a service. This mixes remote integration behavior into the UI component and makes security/testing harder.

## Performance

`Http::timeout(5)` bounds the endpoint test, which is positive. The request remains synchronous.

## Security / Data Integrity

### P1 — Missing authorization

Credential mutation and endpoint probing require explicit `system.env.update` authorization at action boundaries.

### P1 — Payment secrets hydrated into public state

`MOMO_ACCESS_KEY` and `MOMO_SECRET_KEY` are loaded into Livewire public state. Sensitive existing credentials should not be returned to the browser when not necessary.

### P1 — SSRF / endpoint trust boundary

`testEndpoint()` performs a server-side GET to operator-controlled `MOMO_ENDPOINT`. Without host/scheme restrictions this creates an internal-network probing primitive for any actor who can invoke the action.

### P1 — Integration logic in Livewire

Remote payment endpoint validation belongs in a service with explicit validation, timeouts and safe error mapping.

## UI/UX Compliance

Positive:

- clear payment-specific grouping;
- secret input is visually masked.

Needs improvement:

- validation messages;
- loading/disabled states;
- environment/sandbox indicator;
- replacement-secret UX;
- canonical System-owned view.

## Test Coverage

No System-specific test was found.

Missing tests:

- unauthorized save/test rejection;
- disallowed endpoint host/scheme;
- replacement-secret semantics;
- HTTP timeout/failure handling;
- safe messages that do not expose credentials.

## Issue List

### P1 — Missing `system.env.update` authorization

### P1 — Payment credentials exposed through public Livewire state

### P1 — Server-side request to arbitrary configured endpoint

### P2 — Admin/System view ownership drift

## Recommended Direction

**Major Refactor.** Move endpoint test logic into a MoMo configuration/integration service, enforce authorization and endpoint policy, stop hydrating existing secrets, preserve the hardened env writer, and add tests.

## Open Questions / Unknowns

- Whether both sandbox and production MoMo endpoints are intentionally configurable.
- Whether this System component is still the canonical owner of payment configuration or whether Website/payment domain code should own it.
