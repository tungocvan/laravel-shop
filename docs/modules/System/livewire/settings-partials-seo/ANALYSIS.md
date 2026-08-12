# System Livewire Analysis — Settings/Partials/Seo

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/Partials/Seo.php` manages default SEO metadata, social links and a free-form `header_script` setting. It is **P1 / Major Refactor** because writes lack `system.settings.update`, persistence occurs directly in Livewire, the Blade renders `seo_description` with raw `{!! !!}` HTML in its preview, and `header_script` explicitly accepts script markup without a documented authorization/sanitization/rendering contract.

## Component Purpose

Path: `Modules/System/Livewire/Settings/Partials/Seo.php`

Alias: `system.settings.partials.seo`

View: `System::livewire.settings.partials.seo`

Responsibilities:

- load and save SEO title/description;
- save Facebook/Zalo values;
- save arbitrary header scripts;
- provide SEO preview UI.

## Dependency Flow

`SettingForm`
→ `Seo`
→ `Modules\System\Models\Setting`
→ `settings` table

Potential downstream flow requiring separate verification:

`settings.header_script` / `seo_description`
→ frontend/admin renderer
→ HTML/script output

## Livewire PHP Analysis

`mount()` loads five settings through `Setting::getValue()`.

`save()` validates:

- title as nullable string <=255;
- description as nullable string;
- Facebook as nullable URL;
- Zalo as nullable string <=50;
- header script as nullable string.

It then loops through the settings and writes each directly with `Setting::setValue()`.

No action authorization or service layer is present.

## Livewire Blade Analysis

The Blade uses `x-editor` for SEO description and renders its Google preview using:

`{!! $settings['seo_description'] ... !!}`

This is direct raw HTML rendering inside the admin UI. If the setting contains active markup, it is interpreted rather than escaped.

The `header_script` textarea explicitly encourages `<script>...</script>` values for analytics/pixels. That may be an intentional trusted-admin feature, but it significantly raises the importance of mutation authorization and downstream rendering controls.

## State / Validation / Actions

Action:

- `save()`

Validation exists but does not sanitize HTML or constrain `header_script` content. That is acceptable only if the product explicitly treats these fields as trusted code editable solely by highly privileged operators.

## Authorization

**P1:** `save()` must enforce `system.settings.update`, and the repository should consider a more specific capability for custom script injection if this feature remains available in production.

## Service / Model Dependencies

The component directly persists settings. A service should define the trust model for HTML/script-bearing settings and centralize writes, validation/sanitization policy, cache invalidation and audit logging where required.

## Performance

No material performance issue. The component reads/writes a small fixed setting set.

## Security / Data Integrity

### P1 — Missing mutation authorization

Without an action-level update capability, script-bearing settings are not sufficiently protected.

### P1 — Raw HTML preview

`seo_description` is rendered raw in the Blade preview. This is direct XSS-capable rendering if the stored/editor value contains active HTML. If rich HTML is intentionally supported, sanitize against an explicit allowlist before rendering; if plain SEO text is intended, render escaped text instead.

### P1 — `header_script` is intentional arbitrary script storage

The feature is effectively a code-injection control by design. It should be treated like a privileged production-control surface: strong permission, confirmation, audit log, CSP implications, and clear trusted-admin policy.

### P1 — Downstream renderers unknown

Where `header_script` and SEO description are consumed outside this preview must be traced. If output raw on public pages, stored XSS/third-party-script risk becomes site-wide.

## UI/UX Compliance

Positive:

- structured sections;
- preview;
- loading-disabled save;
- clear header-script purpose.

Needs improvement:

- inline validation messages;
- explicit warning for script execution impact;
- permission-aware script feature;
- escaped/sanitized preview behavior.

## Test Coverage

No System-specific test was found.

Critical missing tests:

- unauthorized save rejection;
- HTML sanitization/escaping policy;
- script-setting permission boundary;
- downstream public rendering behavior;
- invalid social URL handling;
- audit behavior if script editing remains enabled.

## Issue List

### P1 — Missing `system.settings.update` authorization

### P1 — Raw `{!! !!}` SEO description preview can execute active HTML

### P1 — Arbitrary `header_script` storage needs explicit trusted-code policy

### P1 — Downstream script/HTML rendering is not yet verified

## Recommended Direction

**Major Refactor.** Keep SEO settings but formalize plain-text versus trusted-HTML fields, enforce update authorization, move persistence/policy to a service, and either sanitize or escape SEO description. Treat header scripts as a separately privileged production operation.

## Open Questions / Unknowns

- Exact frontend locations that render `header_script`.
- Whether `seo_description` is intended to be plain text or rich HTML.
- Whether CSP/nonces are used when custom header scripts are injected.
- Whether script changes require audit history/rollback.
