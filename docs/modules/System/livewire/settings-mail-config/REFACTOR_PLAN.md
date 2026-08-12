# Settings/MailConfig Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/MailConfig.php`, its System Blade, SMTP test/persistence service boundaries, focused tests, and verification of the existing `/admin/system/settings/env` Admin Menu entry.

Status: **Awaiting explicit approval before implementation.**

## 1. Goal

Refactor MailConfig into a thin, authorized SMTP configuration UI while preserving the existing `.env` write safeguards in `EnvManagerService`.

Primary goals:

- enforce `system.env.update` for save and test-send;
- never hydrate the current `MAIL_PASSWORD` into public Livewire state;
- preserve existing password when the replacement field is blank;
- validate and allowlist SMTP configuration;
- prevent raw transport errors from reaching the browser;
- restore temporary runtime mail configuration after test send;
- add bounded/throttled test-send behavior;
- keep the existing ENV page/menu architecture without creating duplicate routes or menus.

This is a major refactor, not a rebuild.

## 2. Route and Admin Menu

MailConfig is the `mail` tab inside:

```text
GET /admin/system/settings/env
name: admin.system.settings.env
middleware: auth:admin + permission:system.env.view,admin
```

The canonical Admin Menu has already been normalized by the DatabaseConfig refactor to:

```text
Name: Quản lý ENV
URL: /admin/system/settings/env
Can: system.env.view
Active: true
```

This task must verify and preserve that entry. Do not create a separate MailConfig menu item or route.

## 3. Authorization

Page/menu visibility remains:

`system.env.view`

Both side-effect actions require:

`system.env.update`

using `AuthorizesSystemActions`:

- `sendTest()`
- `save()`

Sending test email is externally visible and must not be available to view-only operators.

## 4. Secret Handling — Write-only SMTP Password

Current code hydrates `MAIL_PASSWORD` from `.env` into public Livewire state. This must stop.

Target behavior:

- mount loads non-secret mail settings only;
- public `MAIL_PASSWORD` starts empty;
- UI states that blank means preserve the current SMTP password;
- service reads the current password server-side only when needed;
- blank replacement preserves the existing password for both test-send and save;
- explicit replacement is used but never returned to browser;
- reset public password field to empty after successful save;
- no password value/hash/body is logged.

## 5. Fixed Mail Configuration Allowlist

Only these keys may be accepted:

```text
MAIL_MAILER
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_ENCRYPTION
MAIL_FROM_ADDRESS
MAIL_FROM_NAME
```

Do not accept arbitrary `.env` keys from browser state.

Initial policy:

- `MAIL_MAILER`: fixed/allowlisted `smtp` for this SMTP UI;
- `MAIL_HOST`: required|string|max:255;
- `MAIL_PORT`: required|integer|min:1|max:65535;
- `MAIL_USERNAME`: nullable|string|max:255;
- `MAIL_PASSWORD`: nullable|string|max:4096;
- `MAIL_ENCRYPTION`: nullable|in:tls,ssl (normalize UI `none` to empty/null server-side rather than literal string `null`);
- `MAIL_FROM_ADDRESS`: required|email|max:255;
- `MAIL_FROM_NAME`: required|string|max:255;
- `testEmail`: required|email|max:255.

If SMTP authentication is intentionally optional, username/password remain nullable as above.

## 6. New Mail Configuration Orchestration Service

Create a focused service, proposed:

`Modules/System/Services/Env/SystemMailConfigService.php`

Responsibilities:

### Read public configuration

Return only non-secret mail values. Never return current `MAIL_PASSWORD`.

### Resolve effective candidate

- accept only fixed keys;
- force `MAIL_MAILER=smtp`;
- normalize encryption (`tls`, `ssl`, or null/empty);
- if replacement password is blank, read current password from `EnvManagerService` server-side;
- never expose resolved password back to Livewire.

### Test send

- resolve effective candidate;
- delegate transport operation to hardened `MailConfigService`;
- return only safe success/generic failure result;
- log safe metadata without credentials;
- apply a short application-level/throttle guard so repeated clicks/requests cannot rapidly send test messages.

### Save

- resolve fixed candidate;
- persist only approved MAIL_* keys using `EnvManagerService::update()`;
- rely on EnvManagerService safety backup/lock/rollback behavior;
- clear config cache only after successful env update;
- log safe metadata;
- do not automatically send a test email during save unless explicitly requested by existing business behavior (current save does not).

## 7. Harden Existing MailConfigService

Current `MailConfigService` mutates runtime global config and returns raw exception messages.

Refactor requirements:

- capture original values for all runtime mail config keys it will change;
- apply the candidate temporarily;
- send the test email;
- catch technical failures and log exception class/details server-side without credentials;
- return generic browser-safe failure text;
- restore all original runtime mail configuration in `finally`, regardless of success/failure;
- avoid leaving the candidate SMTP password in global runtime config after the operation;
- purge/refresh the mailer manager if required so restored config takes effect consistently;
- never return `$e->getMessage()` to Livewire.

## 8. Test-Send Throttling / Concurrency

Test email is an external side effect. Add conservative protection at the service/action boundary.

Suggested behavior:

- short lock or rate limiter keyed by authenticated admin ID;
- prevent duplicate concurrent test sends;
- reasonable cooldown such as one test send every few seconds, without creating a new business permission;
- controlled generic response when throttled.

Do not queue this operation in this component-level refactor unless repository evidence shows a canonical queue-based test-email pattern. Keep synchronous behavior for compatibility, but avoid double-submission.

## 9. Livewire Responsibility

Keep in Livewire:

- non-secret form state;
- write-only replacement password;
- test recipient;
- validation;
- authorization;
- service delegation;
- `canUpdate` read-only state;
- safe notifications.

Move out of Livewire:

- reading current SMTP password;
- password preservation logic;
- direct EnvManagerService update orchestration;
- runtime mail-config mutation;
- technical exception detail.

## 10. Blade / UX

Preserve current layout and improve:

- show SMTP mailer as fixed/read-only or explicit select containing only `smtp`;
- inline validation errors for host, port, username, password, encryption, from fields and test recipient;
- password guidance: `Để trống để giữ mật khẩu SMTP hiện tại`;
- encryption options should use semantic values (`tls`, `ssl`, empty/none), not literal string `null`;
- disable all mutation/test controls for users without `system.env.update`;
- show a read-only notice to view-only users;
- loading/disabled state for both Save and Test;
- `wire:confirm` before Save because changing SMTP can stop application mail delivery;
- test-send button should be clearly labeled as an external side effect;
- display only safe success/failure messages.

## 11. Runtime / Deployment Semantics

Writing `.env` and clearing config cache does not guarantee all long-running workers reload mail configuration immediately.

Do not restart workers/processes from this web UI. Add guidance that queue workers or long-running processes may require restart/reload after mail configuration changes.

## 12. Error Handling / Logging

Safe log metadata may include:

- actor/admin ID;
- operation (`mail.config.test`, `mail.config.save`);
- mailer (`smtp`);
- host/port if repository policy permits;
- recipient domain or recipient address only if already acceptable in operational logs;
- whether password was replaced (boolean only);
- exception class;
- success/failure.

Never log:

- MAIL_PASSWORD;
- full form/request payload;
- SMTP credentials/authorization headers;
- raw `.env` contents;
- raw exception text in browser messages.

## 13. Tests

Create focused test file:

`tests/Feature/System/SystemMailConfigTest.php`

Coverage:

1. `/admin/system/settings/env` requires `system.env.view`;
2. canonical `Quản lý ENV` menu remains `/admin/system/settings/env` + `system.env.view`;
3. component uses `AuthorizesSystemActions`;
4. `sendTest()` enforces `system.env.update`;
5. `save()` enforces `system.env.update`;
6. mount/public form never hydrates current `MAIL_PASSWORD`;
7. blank replacement preserves current password server-side;
8. explicit replacement is used/persisted but never returned in public config;
9. only fixed MAIL_* keys are accepted;
10. mailer is constrained to smtp;
11. port/encryption/from/test-email validation is enforced;
12. literal `null` encryption is normalized rather than persisted as string `null`;
13. test send returns generic failure instead of raw transport exception;
14. runtime mail config is restored after test success;
15. runtime mail config is restored after test failure;
16. candidate SMTP password is not left in runtime config;
17. repeated/concurrent test sends are guarded;
18. failed env update does not report success;
19. successful save clears config cache after env update;
20. Livewire contains no direct current-secret hydration or raw exception output;
21. Blade has password-preserve guidance, validation errors, save confirmation, loading states and read-only mode;
22. no duplicate route/menu is introduced.

Tests must not send real external email; use Laravel mail fakes/mocks.

## 14. Files Expected to Change

Application:

- `Modules/System/Livewire/Settings/MailConfig.php`
- `Modules/System/resources/views/livewire/settings/mail-config.blade.php`
- `Modules/System/Services/Env/SystemMailConfigService.php` (new)
- `Modules/System/Services/Env/MailConfigService.php`

Verified/preserved:

- `Modules/System/Services/Env/EnvManagerService.php`
- `Modules/Admin/data/menus.json` (already canonical; no change expected unless regression found)
- `Modules/System/routes/web.php`

Tests:

- `tests/Feature/System/SystemMailConfigTest.php` (new)

Documentation:

- `docs/modules/System/livewire/settings-mail-config/ANALYSIS.md`
- `docs/modules/System/livewire/settings-mail-config/REFACTOR_PLAN.md`

No database migration is planned.
No new route is planned.
No new Admin Menu entry is planned.
No new permission is planned.

## 15. Acceptance Criteria

- [ ] MailConfig remains the mail tab under `/admin/system/settings/env`;
- [ ] ENV menu remains canonical with `system.env.view`;
- [ ] send/save enforce `system.env.update`;
- [ ] current SMTP password never hydrates into Livewire state;
- [ ] blank replacement preserves current password;
- [ ] SMTP configuration is fixed-key/allowlisted and fully validated;
- [ ] raw SMTP exception detail never reaches browser;
- [ ] runtime mail configuration is restored after every test-send attempt;
- [ ] candidate secret is not retained in runtime config;
- [ ] repeated test sends are guarded;
- [ ] Livewire no longer directly persists env data;
- [ ] EnvManagerService remains canonical env writer/safety mechanism;
- [ ] save/test have loading/read-only UX and save confirmation;
- [ ] focused tests pass;
- [ ] no destructive menu/env/database reset occurs.

## 16. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, implementation must not begin until the user explicitly approves this plan.
