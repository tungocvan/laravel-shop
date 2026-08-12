# Settings/ShScript Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/ShScript.php` and its direct UI/service/test dependencies only.

Status: **Implemented 2026-08-12**

## Approved Goal

Remove the browser shell-script editor / arbitrary shell execution surface and replace it with a server-owned, allowlisted script-operation panel.

Approved constraints:

- no browser-controlled script creation/editing/deletion;
- no arbitrary filename/path input;
- no direct `File::put`, `chmod`, `File::delete`, or `shell_exec()` in Livewire;
- enforce `system.commands.run` at the Livewire action boundary;
- preserve production forced-disable containment for `system.settings.sh-script`;
- execute only scripts explicitly registered server-side;
- validate/canonicalize registered paths below `app/sh`;
- use Symfony Process with `/bin/bash`, fixed arguments and timeout;
- bound browser-visible output;
- log safe actor/operation/result metadata;
- add focused tests.

## Implemented Architecture

```text
Livewire ShScript
  -> authorize system.commands.run
  -> SystemScriptOperationService
      -> fixed registry
      -> app/sh canonical path validation
      -> /bin/bash + fixed script + fixed args
      -> Symfony Process timeout
      -> bounded output
      -> structured Laravel logging
```

The repository did not contain `app/sh` at implementation time, so the initial registry is intentionally empty. No script is automatically discovered or executable.

## Files Changed

- `Modules/System/Livewire/Settings/ShScript.php`
- `Modules/System/resources/views/livewire/settings/sh-script.blade.php`
- `Modules/System/Services/SystemScriptOperationService.php` (new)
- `tests/Feature/System/SystemScriptOperationsTest.php` (new)
- `docs/modules/System/livewire/settings-sh-script/ANALYSIS.md`
- `docs/modules/System/livewire/settings-sh-script/REFACTOR_PLAN.md`

No route, menu, migration, permission manifest, or unrelated component was changed.

## Acceptance Status

- [x] browser cannot create shell scripts;
- [x] browser cannot edit shell script contents;
- [x] browser cannot delete shell scripts;
- [x] browser cannot supply a script path/filename to execution;
- [x] `shell_exec()` is absent from the replacement;
- [x] Livewire contains no direct filesystem mutation logic;
- [x] only explicitly registered server-owned operation IDs can execute;
- [x] every execution requires `system.commands.run`;
- [x] registered paths are constrained below `app/sh`;
- [x] Symfony Process uses argument-array execution with `/bin/bash`;
- [x] execution has a timeout;
- [x] output is bounded to 32 KB before entering Livewire state;
- [x] browser-facing failures are sanitized;
- [x] safe operation metadata is logged;
- [x] production forced-disable containment remains unchanged;
- [x] focused tests were added;
- [x] no route/menu/schema/unrelated module changes occurred;
- [x] component analysis was updated.

## Verification

Targeted test to run in the project runtime:

```bash
php artisan test tests/Feature/System/SystemScriptOperationsTest.php
```

The GitHub connector environment cannot execute the repository's PHP runtime, so runtime PASS must be confirmed after pull/deploy.

## Follow-up Rule

The first future script registration is a reviewed change. Before adding it to the registry, inspect its source and decide its fixed arguments, timeout, side effects, idempotency, concurrency lock requirement and rollback path. Never restore arbitrary shell authoring or automatic `.sh` discovery.
