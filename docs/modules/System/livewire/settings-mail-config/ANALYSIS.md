# System Livewire Analysis — Settings/MailConfig

Analysis date: 2026-08-12
Refactor status: **Implemented; focused test pending user execution.**

## Executive Summary

`Modules/System/Livewire/Settings/MailConfig.php` remains the SMTP tab under `/admin/system/settings/env`, but the previous P1 findings have now been addressed in source.

The component is now a thin authorized UI over `SystemMailConfigService`. It no longer hydrates the current `MAIL_PASSWORD` into public Livewire state, both `sendTest()` and `save()` enforce `system.env.update`, SMTP configuration is validated/allowlisted, and the current password is resolved only server-side when the replacement field is blank.

`MailConfigService` now treats SMTP test configuration as temporary runtime state: it captures existing mail config, applies the candidate, sends the test, returns safe messages, restores original runtime config in `finally`, and purges the SMTP mailer so the candidate password is not retained in the runtime mailer instance.

## Current Architecture

`/admin/system/settings/env`
→ `EnvConfigController`
→ `system.settings.mail-config`
→ `SystemMailConfigService`
→ `MailConfigService` for temporary test send
→ `EnvManagerService` for canonical `.env` persistence

## Authorization

Page/menu visibility:

`system.env.view`

Sensitive actions:

- `sendTest()` → `system.env.update`
- `save()` → `system.env.update`

Server-side authorization remains authoritative even though the Blade also disables controls for view-only users.

## Secret Handling

Current SMTP password is not returned from `SystemMailConfigService::publicConfig()` and is never mounted into browser state.

The public password field is a write-only replacement value:

- blank → preserve current server-side `MAIL_PASSWORD`;
- non-blank → use the replacement;
- after successful save → reset browser field to blank.

Logs contain only safe metadata such as whether a password was replaced; password contents are not logged.

## Validation / Allowlist

The UI is intentionally SMTP-only:

- mailer: `smtp` only;
- host: required bounded string;
- port: integer 1–65535;
- username: optional bounded string;
- password replacement: optional bounded string;
- encryption: `tls`, `ssl`, or `none`;
- from address: valid email;
- from name: bounded string;
- test recipient: valid bounded email.

The orchestration service accepts only the fixed MAIL_* key set and forces `MAIL_MAILER=smtp` server-side.

`none` encryption is normalized to an empty persisted env value rather than literal `null`.

## Test-send Safety

Test email remains synchronous for backward compatibility.

Safeguards now include:

- action authorization;
- per-admin short cooldown;
- per-admin concurrent lock;
- safe generic browser errors;
- runtime config restoration after success/failure;
- SMTP mailer purge after candidate application and after restoration.

No real test email is expected from automated tests.

## Persistence

`SystemMailConfigService::save()` serializes updates with an application lock, writes only fixed mail keys through `EnvManagerService`, and clears Laravel config cache only after the env update succeeds.

`EnvManagerService` remains the canonical safety boundary for dotenv validation, backup, lock, in-place write and rollback.

Long-running queue/runtime processes may still require operational restart after an env change; the web UI does not restart them.

## Admin Menu

No dedicated MailConfig menu is created.

Canonical parent entry remains:

```text
Quản lý ENV
/admin/system/settings/env
system.env.view
```

## Test Coverage

Focused test added:

`tests/Feature/System/SystemMailConfigTest.php`

It covers route/menu contract, authorization contract, secret non-hydration, SMTP/encryption validation, orchestration guards, runtime-config restoration/redaction, Blade UX, and absence of duplicate route/menu.

## Residual Notes

- SMTP test send is still synchronous and may wait for transport timeout.
- Process restart/reload is operational, not automatic.
- A future platform-level rate limiter may replace the local cooldown if a shared convention is introduced.

## Current Recommendation

No further major refactor is required for this component once the focused test passes. Treat future changes as incremental hardening unless SMTP configuration requirements materially change.
