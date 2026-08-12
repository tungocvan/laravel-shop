# System Livewire Analysis — Settings/MailConfig

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/MailConfig.php` edits SMTP environment values and can send a test email. It is **P1 / Major Refactor** because `save()` and `sendTest()` do not enforce `system.env.update`, SMTP password is hydrated into public Livewire state, and validation only covers sender/test-email fields while host, port, username, password, encryption and mailer configuration remain weakly constrained.

## Component Purpose

Path: `Modules/System/Livewire/Settings/MailConfig.php`

Alias: `system.settings.mail-config`

View: `System::livewire.settings.mail-config`

Responsibilities:

- load SMTP configuration from `.env`;
- edit mailer, host, port, username, password, encryption, sender address/name;
- send a test email;
- persist configuration through `EnvManagerService`.

## Dependency Flow

`/admin/system/settings/env`
→ env page/tab
→ `MailConfig`
→ `MailConfigService` / `EnvManagerService`
→ mail transport / `.env`

## Livewire PHP Analysis

`mount()` hydrates all current mail environment values, including `MAIL_PASSWORD`, into public form state.

`sendTest()` validates only:

- test recipient email;
- sender email;
- sender name.

`save()` validates only sender email/name before writing the complete form to `.env`.

No capability check is present.

## Livewire Blade Analysis

The Blade is clear and responsive, provides separate SMTP configuration and test-send areas, masks password input, shows validation feedback for sender fields, and uses a loading-disabled state for test mail.

The Save action does not expose an explicit loading-disabled state.

## State / Validation / Actions

Actions:

- `sendTest()`
- `save()`

Validation gaps:

- mailer allowlist;
- host format/length;
- numeric port/range;
- encryption allowlist semantics;
- username/password length constraints;
- replacement-secret behavior;
- test-send rate/abuse controls.

## Authorization

**P1:** `system.env.update` is defined for the System module but is not enforced inside either action.

Sending mail is also an externally visible side effect and should not be permitted merely because an operator can view the env page.

## Service / Model Dependencies

`EnvManagerService` supplies robust file-level update safeguards and should remain the canonical env writer.

`MailConfigService` handles the actual test-send workflow. This is the correct layer for transport configuration and technical failure handling; Livewire should authorize and validate before invoking it.

## Performance

Test email is synchronous from the Livewire request unless the service queues it. This can make the UI wait on SMTP timeout. A bounded timeout or queued test operation should be considered if production SMTP endpoints can be slow.

## Security / Data Integrity

### P1 — Missing mutation authorization

Both save and test-send need `system.env.update` or a more specific capability if introduced.

### P1 — SMTP password in public Livewire state

Existing `MAIL_PASSWORD` is hydrated into the browser-side component payload. Masked HTML input is not sufficient protection.

### P1 — Incomplete validation

The component can persist arbitrary mail host/port/encryption values without complete validation.

### P2 — External-side-effect abuse

Repeated test sends could be abused by an authorized-but-low-privilege admin or accidental repeated clicks. Loading state reduces double-clicking, but authorization and optional throttling should be evaluated.

## UI/UX Compliance

Positive:

- responsive layout;
- masked password field;
- inline validation for sender fields;
- loading state for test send.

Needs improvement:

- inline validation for all fields;
- save loading/disabled state;
- replacement-password UX;
- safe test-send result messages without leaking transport secrets.

## Test Coverage

No System-specific component test was found.

Missing tests:

- unauthorized save/send-test rejection;
- validation of port/encryption/mailer;
- preserve existing password when replacement is blank;
- successful and failed test mail behavior;
- error redaction.

## Issue List

### P1 — Missing `system.env.update` action authorization

### P1 — Existing SMTP secret hydrated into public state

### P1 — Partial validation only

### P2 — Save action lacks explicit loading/disabled UX

## Recommended Direction

**Major Refactor.** Keep the existing env and mail services; improve authorization, secret handling, validation, safe error mapping and tests.

## Open Questions / Unknowns

- Whether test email should run synchronously or through queue in production.
- Whether the current `MailConfigService` mutates runtime mail config globally during the request and reliably restores it afterward.
