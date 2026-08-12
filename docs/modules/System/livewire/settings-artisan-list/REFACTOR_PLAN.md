# Settings/ArtisanList Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/ArtisanList.php` and its direct UI/service/test dependencies only.

Status: **Awaiting explicit approval before implementation.**

## 1. Refactor Goal

Replace the current free-form browser Artisan terminal with a small, server-defined allowlist of operational actions.

The refactor must:

- remove arbitrary command text execution;
- enforce `system.commands.run` at the Livewire action boundary;
- preserve the existing production containment that forces `system.settings.artisan-list` disabled;
- keep privileged operational logic out of Livewire;
- sanitize browser-facing failures;
- record a safe operational audit trail through application logs;
- add focused authorization/allowlist tests.

This is a security refactor. It intentionally narrows the old public behavior because unrestricted command execution is the P0 issue being removed.

## 2. Behavior / Public Contract

### Preserve

- Livewire alias: `system.settings.artisan-list`.
- Existing System tab ID/registration.
- `SystemConfigService::DISABLED_PRODUCTION_COMPONENTS` containment.
- Admin UI ability to run a small set of explicitly approved maintenance operations when the component is deliberately mounted in an allowed environment.
- Escaped text output displayed to the operator.
- Loading/disabled state during execution.

### Intentionally remove

- free-form command input;
- arbitrary Artisan command strings and arbitrary arguments;
- destructive shortcuts such as `key:generate`, `db:seed`, and `migrate:fresh`;
- raw exception messages returned to the browser.

### Initial allowlist

Only these operation IDs will be exposed by this component:

| Operation ID | Artisan command | Purpose | Risk |
|---|---|---|---|
| `artisan.list` | `list` | Read available Artisan commands | Read-only |
| `cache.optimize-clear` | `optimize:clear` | Clear Laravel framework caches | Operational mutation |

No user-controlled command arguments will be accepted.

Adding any further operation later is outside this refactor and must be reviewed explicitly.

## 3. Livewire PHP Changes

File:

`Modules/System/Livewire/Settings/ArtisanList.php`

Planned changes:

- use `Modules\System\Livewire\Concerns\AuthorizesSystemActions`;
- replace public `$artisanCommand` with a constrained operation ID state such as `$selectedOperation`;
- keep `$commandOutput` and a sanitized `$errorMessage`;
- replace `executeArtisanCommand()` with an operation-based action such as `executeOperation()`;
- call `authorizePermission('system.commands.run')` before any operation lookup/execution;
- validate that the selected operation ID exists in the server-defined service registry;
- delegate execution to a dedicated System service;
- never pass browser-controlled command text to `Artisan::call()`;
- clear prior output/error state predictably before/after each action;
- catch `Throwable`, log detailed failure in the service/log layer, and show only a generic operator-safe error message.

No database query or command workflow will be added to Livewire.

## 4. Livewire Blade Changes

File:

`Modules/System/resources/views/livewire/settings/artisan-list.blade.php`

Planned changes:

- remove the free-form text command field;
- remove current quick-command shortcuts, especially `key:generate`, `db:seed`, and `migrate:fresh`;
- render the server-defined operations as explicit buttons/cards/select options using stable operation IDs;
- show the operation label and short impact description;
- keep loading/disabled behavior;
- require an explicit Livewire confirmation for `cache.optimize-clear` because it mutates runtime cache state;
- keep command output rendered with escaped Blade output;
- replace the misleading `Production Mode` terminal framing with wording that accurately describes a restricted maintenance operation panel;
- remove component-local inline scrollbar CSS when standard Tailwind overflow utilities are sufficient;
- do not add jQuery or another UI framework.

## 5. Service Extraction / Delegation

Create:

`Modules/System/Services/SystemOperationService.php`

Responsibilities:

- own the fixed operation registry;
- expose only safe display metadata needed by Livewire, for example operation ID, label, description, and confirmation requirement;
- resolve an operation ID to a fixed command and fixed arguments;
- execute only registered operations;
- reject unknown operation IDs with a domain/runtime exception;
- call Artisan using fixed server-side command names and fixed arguments;
- return a small result DTO/array containing safe output and exit code;
- log operation attempts/results with safe metadata.

Proposed registry:

```text
artisan.list
  command: list
  arguments: []
  confirmation: false

cache.optimize-clear
  command: optimize:clear
  arguments: []
  confirmation: true
```

The service must not accept arbitrary command strings or arbitrary argument arrays from Livewire.

## 6. Authorization Changes

Permission already exists:

`system.commands.run`

Enforcement:

- every execution action must call `authorizePermission('system.commands.run')`;
- page/tab visibility is not considered sufficient authorization;
- no new permission is required for this refactor.

Production containment remains independent of authorization and must not be weakened.

## 7. Audit / Logging

No canonical persistent audit-log subsystem was found during this component-level review, so this refactor will not introduce a new database audit architecture or migration.

Use Laravel application logging for the initial audit trail.

For every attempted operation, log safe metadata such as:

- admin/user ID when available;
- operation ID;
- command name from the server-side registry;
- start/result status;
- exit code;
- timestamp through the logger context/runtime;
- exception class for failures.

Do not log:

- secrets;
- session/cookie values;
- arbitrary request payloads;
- full exception traces into browser-facing state.

If the project later adopts a canonical Audit Log framework, migration of these events to that framework is a separate cross-cutting task.

## 8. Transaction / Concurrency

No database transaction is needed because the approved operations are not multi-step domain writes.

Concurrency controls:

- UI keeps `wire:loading.attr="disabled"`;
- operation service remains stateless;
- no queue/background execution is introduced in this refactor;
- no destructive or long-running migration/seed/key-generation operations are allowed.

If future operations can be long-running, they must be planned separately rather than added silently to this service.

## 9. Performance

- remove `wire:model.live.debounce.500ms` because no free-form field remains;
- do not send command input on every keystroke;
- operation metadata is a small fixed in-memory registry;
- output remains bounded to the output of the two approved framework commands; if necessary during implementation, cap browser-visible output length without changing the underlying logged result.

## 10. Security / Data Integrity Fixes

### P0

- eliminate arbitrary Artisan execution;
- enforce `system.commands.run` inside the Livewire action;
- retain production forced-disable containment.

### P1

- sanitize browser-facing failures;
- remove destructive command suggestions;
- log privileged operation execution with actor and operation metadata.

### P2

- simplify terminal UI into a restricted operation panel;
- remove unnecessary live input binding and local CSS where possible.

## 11. Tests

Create focused tests under the existing System test namespace, proposed:

`tests/Feature/System/SystemArtisanOperationsTest.php`

Required coverage:

1. operation registry exposes only `artisan.list` and `cache.optimize-clear`;
2. arbitrary/unknown operation ID is rejected and does not execute a command;
3. user without `system.commands.run` receives 403 when invoking execution;
4. authorized admin can execute `artisan.list`;
5. authorized admin can execute `cache.optimize-clear`;
6. service uses fixed command mapping and does not accept arbitrary command text/arguments;
7. exception details are not copied into browser-facing error state;
8. `SystemConfigService` continues to force `system.settings.artisan-list` disabled even if an override attempts to enable it.

Where direct Artisan execution would create unnecessary side effects in tests, use Laravel facade fakes/mocks at the service boundary while still asserting the fixed mapped command.

## 12. Documentation Changes After Implementation

Update:

`docs/modules/System/livewire/settings-artisan-list/ANALYSIS.md`

Record that:

- arbitrary command execution was removed;
- allowlisted operations and authorization were implemented;
- production containment remains enabled;
- focused tests were added.

No module-wide `README.md`/`INFORMATION.md` update is required unless those documents exist by implementation time and materially describe this component's command behavior.

## 13. Files to Change

Application files:

- `Modules/System/Livewire/Settings/ArtisanList.php`
- `Modules/System/resources/views/livewire/settings/artisan-list.blade.php`
- `Modules/System/Services/SystemOperationService.php` (new)

Tests:

- `tests/Feature/System/SystemArtisanOperationsTest.php` (new; exact test organization may follow an existing System test directory if one exists at implementation time)

Documentation:

- `docs/modules/System/livewire/settings-artisan-list/ANALYSIS.md`
- `docs/modules/System/livewire/settings-artisan-list/REFACTOR_PLAN.md`

Files explicitly not planned for modification:

- `Modules/System/config/system_tabs.php` — retain disabled-by-default state;
- `Modules/System/Services/SystemConfigService.php` — retain forced production disablement unless a test-only adjustment is necessary; no behavioral weakening is allowed;
- `Modules/System/config/module.php` — `system.commands.run` already exists;
- routes/controllers/database schema.

## 14. Rollback / Recovery

The refactor has no migration or persistent schema change.

Rollback is file-level:

- revert `ArtisanList.php` and its Blade view;
- remove `SystemOperationService.php`;
- remove the focused tests if reverting the feature entirely.

Production containment remains disabled before, during, and after the refactor, minimizing deployment exposure.

No database recovery step is required.

## 15. Acceptance Criteria

Implementation is complete only when all of the following are true:

- [ ] no free-form Artisan command field remains;
- [ ] no browser-controlled string is passed to `Artisan::call()`;
- [ ] only `artisan.list` and `cache.optimize-clear` are executable from this component;
- [ ] every execution requires `system.commands.run`;
- [ ] unknown operation IDs are rejected;
- [ ] destructive shortcuts (`key:generate`, `db:seed`, `migrate:fresh`) are absent;
- [ ] detailed exceptions are logged but not exposed to the UI;
- [ ] privileged operation attempts/results have safe log context;
- [ ] output remains escaped in Blade;
- [ ] loading/disabled state remains functional;
- [ ] `system.settings.artisan-list` remains disabled by core config and forced disabled by `SystemConfigService`;
- [ ] focused tests pass;
- [ ] no routes, database schema, unrelated modules, or unrelated Livewire components are changed;
- [ ] component analysis is updated to reflect the implemented state.

## 16. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, implementation must not begin until the user explicitly approves this plan.
