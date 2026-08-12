# System Livewire Analysis — Settings/Partials/General

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/Partials/General.php` manages basic site name, email, hotline and address settings. It is lower risk than the other P1 components but still needs a **P1 / targeted refactor** because `save()` writes settings directly from Livewire without `system.settings.update` authorization or a service boundary.

## Component Purpose

Path: `Modules/System/Livewire/Settings/Partials/General.php`

Alias: `system.settings.partials.general`

View: `System::livewire.settings.partials.general`

Responsibilities:

- load four general site settings;
- validate user input;
- save values;
- dispatch site-name refresh and notification events.

## Dependency Flow

`SettingForm`
→ `General`
→ `Modules\System\Models\Setting`
→ `settings` table/cache

## Livewire PHP Analysis

Public state contains:

- `site_name`
- `site_email`
- `site_hotline`
- `site_address`

`mount()` reads every value through `Setting::getValue()`.

`save()` validates all four fields and loops over the settings calling `Setting::setValue()` directly.

No authorization is enforced.

## Livewire Blade Analysis

The Blade is straightforward and responsive. It provides:

- site name input;
- hotline;
- contact email;
- office/warehouse address;
- inline error messages for site name and email;
- loading-disabled submit state.

Validation feedback is missing beside hotline/address even though they have validation rules.

## State / Validation / Actions

Action:

- `save()`

Validation is reasonable for a basic settings form:

- site name required, string, max 255;
- email nullable email;
- hotline nullable string max 50;
- address nullable string max 500.

Potential normalization (trim, phone normalization) is not centralized.

## Authorization

**P1:** `save()` must enforce `system.settings.update` at the Livewire mutation boundary.

## Service / Model Dependencies

Direct use of `Setting::getValue()/setValue()` keeps the component simple, but it bypasses the repository's preferred service layer. A shared System settings service would allow General, Images, SEO and Custom to use one authorization-independent persistence API with consistent normalization, cache behavior and audit hooks.

## Performance

No material issue. Four reads and four writes are bounded.

However, the four writes are separate operations rather than a single explicit service/transaction workflow. For these independent scalar settings this is moderate correctness debt rather than a high-risk transaction problem.

## Security / Data Integrity

### P1 — Missing mutation authorization

An operator with view-level access should not automatically gain the ability to change public site identity/contact settings.

### P2 — Direct model writes in Livewire

This creates inconsistent architecture with settings components that should share a service.

### P2 — No normalization policy

Whitespace and phone/address normalization are not centralized. This is a data-quality concern, not a security blocker.

## UI/UX Compliance

Positive:

- responsive grid;
- clear labels;
- loading-disabled save;
- inline errors for required/name/email fields.

Needs improvement:

- show hotline/address validation errors;
- optionally indicate successful persistence consistently with other System settings;
- maintain consistent form sizing/components across settings tabs.

## Test Coverage

No System-specific test was found.

Missing tests:

- unauthorized save rejection;
- validation rules;
- successful persistence;
- site-name update event;
- cache update behavior through `Setting::setValue()`.

## Issue List

### P1 — Missing `system.settings.update` authorization

### P2 — Persistence directly in Livewire instead of shared settings service

### P2 — Partial validation UX

## Recommended Direction

**Targeted refactor.** This component does not need a rebuild. Add mutation authorization and route persistence through the same System settings service introduced for the higher-risk settings components.

## Open Questions / Unknowns

- Whether the System-owned `Setting` model or Admin-owned settings infrastructure is the canonical long-term owner.
- Whether general setting changes require audit history in production.
