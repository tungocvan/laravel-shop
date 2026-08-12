# System Livewire Analysis — Settings/SocialConfig

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/SocialConfig.php` manages Google/Facebook OAuth credentials, TinyMCE API key and Google Analytics ID in `.env`. It is **P1 / Major Refactor** because secret-bearing fields are hydrated into public Livewire state, `save()` does not enforce `system.env.update`, and the component performs almost no validation before persisting OAuth redirects and credentials.

## Component Purpose

Path: `Modules/System/Livewire/Settings/SocialConfig.php`

Alias: `system.settings.social-config`

View: `System::livewire.settings.social-config`

Responsibilities:

- manage Google OAuth client ID/secret/redirect;
- manage Facebook OAuth client ID/secret/redirect;
- manage TinyMCE API key;
- manage Google Analytics ID;
- persist values through `EnvManagerService`.

## Dependency Flow

`/admin/system/settings/env`
→ env tab
→ `SocialConfig`
→ `EnvManagerService`
→ `.env`

## Livewire PHP Analysis

`mount()` loads all configured values into public form state, including `GOOGLE_CLIENT_SECRET` and `FACEBOOK_CLIENT_SECRET`.

`save()` passes the whole form to `EnvManagerService::update()` and only handles a boolean write failure. There are no validation rules and no explicit authorization check.

A `SocialConfigService` exists in the System service tree, but this component does not use it, indicating service-layer drift or dead/unused infrastructure.

## Livewire Blade Analysis

The Blade has clear Google, Facebook and SEO/tool sections. OAuth secret fields are visually masked, but current secrets remain present in Livewire public state.

There are no inline validation messages, loading/disabled state on Save, or warning about replacing production OAuth credentials.

## State / Validation / Actions

Action:

- `save()`

Missing validation includes:

- redirect URL validation;
- allowed scheme/host policy;
- client ID/secret size limits;
- GA4 identifier format where useful;
- replacement-secret semantics;
- empty value semantics for optional integrations.

## Authorization

**P1:** `system.env.update` is defined by the module manifest but not enforced in `save()`.

## Service / Model Dependencies

`EnvManagerService` is the correct canonical env writer and already provides robust write-level safeguards.

The unused `Modules/System/Services/Env/SocialConfigService.php` should be evaluated: either route integration validation through it or remove/document it if obsolete. Avoid keeping two competing paths for social configuration behavior.

## Performance

No material performance issue. The component performs a bounded number of env operations.

## Security / Data Integrity

### P1 — OAuth secrets hydrated into public Livewire state

Current Google/Facebook client secrets should not be returned to the browser merely to display a masked field.

### P1 — Missing action authorization

Saving integration credentials requires explicit `system.env.update` authorization at the mutation boundary.

### P1 — Redirect URLs are persisted without validation

Malformed or hostile redirect values can break authentication flows. Redirect targets should be validated according to intended application URLs/environments.

### P2 — Unused service drift

A social config service exists but is bypassed by the component.

## UI/UX Compliance

Positive:

- clear grouping by integration;
- secret fields visually masked;
- responsive layout.

Needs improvement:

- inline validation feedback;
- save loading/disabled state;
- replacement-secret pattern;
- indicate configured/not-configured without revealing current secret value.

## Test Coverage

No System-specific test was found for this component.

Missing tests:

- unauthorized save rejection;
- preserve existing OAuth secret when replacement is blank;
- redirect URL validation;
- optional integration clearing semantics;
- safe persistence of special characters through `EnvManagerService`.

## Issue List

### P1 — Missing `system.env.update` authorization

### P1 — OAuth secrets exposed through public Livewire state

### P1 — No OAuth redirect/credential validation

### P2 — Existing SocialConfigService is bypassed

## Recommended Direction

**Major Refactor.** Retain `EnvManagerService`, adopt replacement-secret UX/state, enforce authorization, validate OAuth URLs/fields, and reconcile the currently unused social configuration service.

## Open Questions / Unknowns

- Whether System or Auth should be the canonical domain owner of OAuth provider configuration.
- Whether clearing a secret intentionally is supported or should require a separate explicit action.
