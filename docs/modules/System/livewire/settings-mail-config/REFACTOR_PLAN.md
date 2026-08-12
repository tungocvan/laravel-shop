# Settings/MailConfig Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/MailConfig.php`, its System Blade, SMTP test/persistence service boundaries, focused tests, and verification of the existing `/admin/system/settings/env` Admin Menu entry.

Status: **Implemented; focused test pending user execution.**

## Goal

Refactor MailConfig into a thin, authorized SMTP configuration UI while preserving the existing `.env` write safeguards in `EnvManagerService`.

## Implemented Result

- `MailConfig` remains the mail tab inside `/admin/system/settings/env`.
- No duplicate route or Admin Menu entry was added.
- Existing `Quản lý ENV` menu remains `/admin/system/settings/env` with `system.env.view`.
- `sendTest()` and `save()` enforce `system.env.update` through `AuthorizesSystemActions`.
- Existing `MAIL_PASSWORD` is no longer hydrated into public Livewire state.
- Blank password replacement preserves the current secret server-side.
- Mail settings use a fixed key allowlist and force SMTP mailer semantics.
- Livewire validates mailer, host, port, username, replacement password, encryption, sender and test recipient.
- `none` encryption is normalized to an empty env value rather than literal `null`.
- `SystemMailConfigService` now owns public read state, password resolution, test-send guarding and env-save orchestration.
- Test sends have a short per-admin cooldown and a per-admin concurrent lock.
- Save has an application-level lock.
- `MailConfigService` captures and restores runtime mail config in `finally` and purges the SMTP mailer after candidate/restored config changes.
- Raw SMTP exception text is not returned to the browser.
- Save writes only through `EnvManagerService` and clears config cache after successful env update.
- Blade includes password-preserve guidance, full validation feedback, read-only mode, loading states and save confirmation.
- No worker/process restart or automatic test send was added to save.
- Focused test file added: `tests/Feature/System/SystemMailConfigTest.php`.

## Acceptance Checklist

- [x] MailConfig remains the mail tab under `/admin/system/settings/env`.
- [x] ENV menu remains canonical with `system.env.view`.
- [x] send/save enforce `system.env.update`.
- [x] current SMTP password never hydrates into Livewire state.
- [x] blank replacement preserves current password.
- [x] SMTP configuration is fixed-key/allowlisted and validated.
- [x] raw SMTP exception detail never reaches browser.
- [x] runtime mail configuration is restored after every test-send attempt.
- [x] candidate secret is purged from runtime mailer state.
- [x] repeated/concurrent test sends are guarded.
- [x] Livewire no longer directly persists env data.
- [x] EnvManagerService remains canonical env writer/safety mechanism.
- [x] save/test have loading/read-only UX and save confirmation.
- [ ] focused tests pass in the target project runtime.
- [x] no destructive menu/env/database reset occurs.

## Post-implementation Verification

Run:

```bash
php artisan test tests/Feature/System/SystemMailConfigTest.php
```

If this passes, this P1 refactor is considered complete.
