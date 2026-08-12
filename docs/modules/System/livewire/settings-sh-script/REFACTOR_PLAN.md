# Settings/ShScript Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/ShScript.php` and its direct UI/service/test dependencies only.

Status: **Awaiting explicit approval before implementation.**

## 1. Refactor Goal

Remove the browser shell-script editor / arbitrary shell execution surface and replace it with a server-owned, allowlisted script-operation panel.

The refactor must:

- remove browser-controlled script creation/editing/deletion;
- remove arbitrary filename/path input;
- remove direct `File::put`, `chmod`, `File::delete`, and `shell_exec()` from Livewire;
- enforce `system.commands.run` at the Livewire action boundary;
- preserve the current production containment that forces `system.settings.sh-script` disabled in the System tab;
- execute only scripts explicitly registered by server-side configuration/service;
- canonicalize/validate every registered script path under an approved root;
- use Symfony Process with timeout and explicit exit-code handling;
- bound browser-visible output;
- log actor/operation/result metadata without secrets;
- add focused security/authorization/service tests.

This refactor intentionally removes functionality. Arbitrary production shell authoring/execution is the P0 issue and is not a behavior that should be preserved.

## 2. Behavior / Public Contract

### Preserve

- Livewire alias: `system.settings.sh-script`.
- Existing tab registration and production forced-disable behavior.
- Admin ability, when deliberately mounted in an allowed environment, to run a small set of approved maintenance scripts.
- Escaped output display.
- Loading/disabled state while an approved script is executing.

### Intentionally remove

- script filename input;
- script content editor;
- create/update script action;
- delete script action;
- direct filesystem mutation from browser state;
- arbitrary `.sh` file discovery as executable operations;
- arbitrary selected path/filename execution;
- native `shell_exec()` execution.

## 3. Server-Owned Script Registry

Create a dedicated service, proposed:

`Modules/System/Services/SystemScriptOperationService.php`

The service owns a fixed registry of approved script operations.

Each registry entry must contain only server-side values:

- stable operation ID;
- label;
- description;
- fixed script path relative to an approved root;
- confirmation requirement;
- timeout seconds;
- optional fixed arguments only;
- enabled policy if needed.

No browser-controlled script path or argument array may reach Process execution.

### Initial registry policy

Do **not** automatically register every file currently found under `app/sh`.

Implementation should first enumerate repository-owned scripts under the approved directory. Only scripts that are clearly safe and intentionally operational should be added to the registry.

If no script can be confidently approved from repository evidence, the registry may initially be empty and the UI should show an empty-state message rather than preserving arbitrary execution.

Adding any future script to the registry is an explicit reviewed change.

## 4. Approved Script Root / Path Safety

Preferred root remains compatible with the current component:

`app_path('sh')`

But the service must never concatenate client-controlled values into a path.

For every registry entry:

1. build path from a fixed registry-relative path;
2. reject absolute paths and dot segments;
3. resolve/canonicalize the approved root and target where possible;
4. verify target remains inside the approved root;
5. require an existing regular readable file;
6. do not require browser-triggered chmod; deployment should own file permissions.

If repository scripts need executable permission, deployment/source ownership should set it outside this Livewire component.

## 5. Livewire PHP Changes

File:

`Modules/System/Livewire/Settings/ShScript.php`

Planned changes:

- use `AuthorizesSystemActions`;
- remove `File` facade usage;
- remove public states `$scripts`, `$selectedScript`, `$scriptContent`, `$newScriptName`;
- replace with constrained state such as `$selectedOperation`;
- retain sanitized `$errorMessage` and bounded `$executionOutput`;
- remove `loadScripts()`, `selectScript()`, `saveScript()`, and `deleteScript()`;
- replace `executeScript()` with an operation-based action such as `executeOperation()`;
- call `authorizePermission('system.commands.run')` before lookup/execution;
- delegate all path validation/process execution to `SystemScriptOperationService`;
- catch `Throwable` and show only a generic operator-safe message;
- never expose raw process exception text in Livewire state.

No filesystem or shell workflow remains inside Livewire.

## 6. Blade Changes

File:

`Modules/System/resources/views/livewire/settings/sh-script.blade.php`

Planned changes:

- remove script selector based on arbitrary files;
- remove filename input;
- remove script-content textarea/editor;
- remove create/update/delete buttons;
- render only server-provided approved operation cards/options;
- show label, description and timeout/risk information;
- require `wire:confirm` for every shell operation unless a future operation is proven read-only and explicitly marked otherwise;
- keep `wire:loading.attr="disabled"`;
- keep output escaped with `{{ }}`;
- replace misleading “Hệ thống ổn định” text with restricted-operation/security wording;
- show a safe empty state if no scripts are registered;
- remove component-local inline scrollbar CSS if Tailwind utilities are sufficient.

## 7. Process Execution

Use Symfony Process rather than native `shell_exec()`.

Expected execution shape:

```text
Process([
  '/bin/bash',
  fixed-script-path,
  ...fixed-arguments,
])
```

Requirements:

- command array, not a shell command string;
- fixed executable (`/bin/bash`) and fixed registered script path;
- fixed arguments only for initial scope;
- timeout configured per operation with conservative default (for example 60 seconds unless script evidence requires less/more);
- capture stdout/stderr separately or combine into a safe result model;
- explicit exit-code handling;
- non-zero exit returns controlled failure state;
- no shell interpolation from Livewire state.

## 8. Output Limits

Browser-visible output must be bounded, proposed maximum 32-64 KB.

If process output exceeds the limit:

- truncate browser-visible output with a clear marker;
- do not place unbounded output into Livewire state;
- logging should record metadata, not dump arbitrary full script output by default.

## 9. Authorization

Use existing permission:

`system.commands.run`

Every execution action must enforce it internally via `AuthorizesSystemActions`.

No page/tab/menu visibility is accepted as a replacement for mutation authorization.

No new permission is required for this refactor. If later script management is reintroduced, it must use separate granular permissions and a new change plan rather than `system.commands.run` alone.

## 10. Production Containment

Preserve unchanged:

- core System tab for `system.settings.sh-script` remains disabled;
- `SystemConfigService::DISABLED_PRODUCTION_COMPONENTS` continues to force this component disabled during tab normalization.

This refactor does not add a dedicated public/admin route or menu for ShScript.

Any future route/menu exposure is a separate feature change and requires its own approval gate.

## 11. Logging / Auditability

Use Laravel structured application logging for this component-level refactor, consistent with the current `SystemOperationService` approach.

Log safe metadata:

- actor/admin ID;
- script operation ID;
- server-defined script identifier/path label (safe relative identifier, not secret content);
- started/completed/failed status;
- exit code;
- duration if straightforward;
- exception class on service failure.

Do not log:

- environment secrets;
- full request/session payloads;
- arbitrary script contents;
- unbounded process output.

Migration to a future persistent Audit Log framework remains separate cross-cutting work.

## 12. Concurrency

For initial approved shell operations:

- UI uses loading disablement;
- service should add a per-operation cache/atomic lock if Laravel's existing cache lock infrastructure is available and testable in the project;
- duplicate concurrent execution of the same operation should be rejected with a controlled message;
- lock expiry must exceed operation timeout by a safe margin.

If adding a lock materially conflicts with current cache-driver support during implementation, document that limitation rather than silently introducing an unreliable lock.

## 13. Tests

Create focused tests, proposed:

`tests/Feature/System/SystemScriptOperationsTest.php`

Required coverage:

1. Livewire source no longer contains `shell_exec`, `File::put`, `chmod`, or browser script-content fields;
2. execution action enforces `system.commands.run`;
3. service registry exposes only explicitly approved operation IDs;
4. unknown operation ID is rejected before Process starts;
5. traversal/absolute path cannot be introduced through registry/path resolver;
6. Process is created as an argument array using `/bin/bash` plus fixed script path;
7. timeout is configured;
8. non-zero exit is handled safely;
9. browser-visible output is bounded;
10. raw exception details are not copied into Livewire error state;
11. production containment still forces `system.settings.sh-script` disabled;
12. no create/update/delete browser shell-script behavior remains.

If repository-owned scripts are registered, add one test per approved script mapping.

## 14. Files to Change

Application files:

- `Modules/System/Livewire/Settings/ShScript.php`
- `Modules/System/resources/views/livewire/settings/sh-script.blade.php`
- `Modules/System/Services/SystemScriptOperationService.php` (new)

Potentially inspected but not automatically changed:

- repository-owned scripts under `app/sh` or the current approved script directory;
- `Modules/System/Services/SystemConfigService.php` (containment must remain unchanged);
- `Modules/System/config/system_tabs.php` (disabled state must remain unchanged).

Tests:

- `tests/Feature/System/SystemScriptOperationsTest.php` (new)

Documentation:

- `docs/modules/System/livewire/settings-sh-script/ANALYSIS.md`
- `docs/modules/System/livewire/settings-sh-script/REFACTOR_PLAN.md`

No route, menu, migration, permission manifest, or unrelated component changes are part of this refactor.

## 15. Rollback / Recovery

No database migration or persistent schema change.

Rollback is file-level:

- restore previous `ShScript.php` and Blade;
- remove `SystemScriptOperationService.php` and focused tests.

However, rolling back would restore the P0 arbitrary shell editor/executor and therefore is not recommended for production exposure.

Production tab containment remains in place before, during and after implementation.

## 16. Acceptance Criteria

Implementation is complete only when:

- [ ] browser cannot create shell scripts;
- [ ] browser cannot edit shell script contents;
- [ ] browser cannot delete shell scripts;
- [ ] browser cannot supply a script path/filename to execution;
- [ ] `shell_exec()` is absent from the component/service replacement;
- [ ] Livewire contains no direct filesystem mutation logic;
- [ ] only explicitly registered server-owned script operation IDs can execute;
- [ ] every execution requires `system.commands.run`;
- [ ] approved paths remain inside the approved script root;
- [ ] Symfony Process uses argument-array execution;
- [ ] execution has a timeout;
- [ ] output is bounded before entering Livewire state;
- [ ] failure messages are sanitized;
- [ ] safe operation metadata is logged;
- [ ] production forced-disable containment remains unchanged;
- [ ] focused tests pass;
- [ ] no route/menu/schema/unrelated module changes occur;
- [ ] component analysis is updated after implementation.

## 17. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, implementation must not begin until the user explicitly approves this plan.
