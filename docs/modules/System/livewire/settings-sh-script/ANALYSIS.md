# Settings/ShScript Livewire Analysis

Analysis date: 2026-08-12

Implementation status: **Refactored 2026-08-12**

Scope: `Modules/System/Livewire/Settings/ShScript.php` and direct dependencies.

## Executive Summary

The previous browser shell-script editor/executor has been removed. `Settings/ShScript` no longer allows an admin browser to create, edit, delete, chmod, select arbitrary filenames, or execute arbitrary shell content.

The component is now a restricted operation panel backed by `SystemScriptOperationService`. Only server-owned scripts explicitly registered in the service may be executed, and every execution requires `system.commands.run` at the Livewire action boundary.

At implementation time the repository contains no `app/sh` directory, so the initial approved registry is intentionally empty. The UI therefore presents a safe empty state instead of discovering or running arbitrary shell files.

Production containment remains unchanged: the System tab remains disabled by default and `SystemConfigService` continues to force `system.settings.sh-script` disabled.

## Current Dependency Flow

```text
Livewire ShScript
  -> authorize system.commands.run
  -> SystemScriptOperationService
      -> fixed server-side registry
      -> canonical path under app/sh
      -> /bin/bash + fixed script + fixed args
      -> Symfony Process timeout
      -> bounded output
      -> structured Laravel logging
  -> escaped output / generic browser-facing error
```

## Livewire PHP

Current public state is limited to:

- `$selectedOperation`;
- `$executionOutput`;
- `$errorMessage`.

Removed browser-controlled state:

- `$scripts`;
- `$selectedScript`;
- `$scriptContent`;
- `$newScriptName`.

Removed methods/workflows:

- arbitrary file discovery;
- `selectScript()`;
- `saveScript()`;
- `deleteScript()`;
- direct filesystem mutation;
- native shell execution.

`executeOperation()` now:

1. enforces `system.commands.run`;
2. requires a registered operation ID;
3. delegates to `SystemScriptOperationService`;
4. returns bounded safe output on success;
5. catches failures and exposes only a generic operator-safe message.

## SystemScriptOperationService

New file:

`Modules/System/Services/SystemScriptOperationService.php`

Responsibilities:

- owns the explicit script operation registry;
- rejects unknown operation IDs before process execution;
- accepts only fixed server-side script paths and fixed arguments;
- resolves scripts only below `app_path('sh')`;
- rejects absolute paths and dot-segment traversal in registered paths;
- canonicalizes root/target with `realpath()`;
- requires an existing readable regular file;
- verifies the resolved path remains inside the approved root;
- invokes Symfony Process with argument-array execution using `/bin/bash`;
- applies a per-operation timeout with a 60-second default;
- handles non-zero exit codes as failures;
- truncates browser-visible combined stdout/stderr to 32 KB;
- logs actor, operation ID, script basename, result and exception class without script content or request secrets.

## Initial Registry

The initial registry is intentionally empty.

Reason: the repository currently has no `app/sh` directory and therefore no repository-owned script can be reviewed and approved from evidence.

This is deliberate security behavior. Future scripts must be reviewed individually and added explicitly to the registry. Automatic discovery of all `.sh` files is forbidden.

## Blade UI

The old script manager/editor UI has been removed.

Removed:

- arbitrary script file selector;
- filename input;
- script content textarea;
- create/update button;
- delete button;
- arbitrary execute button tied to a client-selected file;
- inline component scrollbar CSS.

Current UI:

- renders only service-provided approved operation cards;
- shows a restricted-security badge;
- requires confirmation for registered operations by default;
- uses loading/disabled state during execution;
- keeps output escaped through Blade `{{ }}`;
- shows a safe empty state when no operation is registered.

## Authorization

Resolved P0 gap:

`ShScript` now uses `AuthorizesSystemActions` and executes:

```text
authorizePermission('system.commands.run')
```

before any registered operation can run.

## Security / Data Integrity Status

### Resolved P0 — Browser-authored shell code

No script content can be authored or sent from Livewire state.

### Resolved P0 — Browser-controlled filesystem path

No client filename/path reaches the process runner. Only server registry entries are resolved.

### Resolved P0 — Native arbitrary shell execution

`shell_exec()` was removed. Symfony Process receives an argument array with `/bin/bash`, a canonical registered script path and fixed arguments.

### Resolved P0 — Missing action authorization

`system.commands.run` is enforced at the mutation boundary.

### Improved P1 — Timeout/output/resource handling

Execution has a timeout and browser output is bounded to 32 KB.

### Improved P1 — Operational auditability

Structured Laravel logs record safe operation metadata.

## Concurrency

No per-operation lock was added in this implementation because the approved registry is empty, so no script can currently execute. When the first executable operation is proposed, its change review should decide whether the operation requires a cache lock based on idempotency and runtime behavior before enabling it.

## Production Containment

Unchanged defense-in-depth:

- `Modules/System/config/system_tabs.php` keeps the ShScript tab disabled;
- `SystemConfigService` continues to force `system.settings.sh-script` disabled.

No dedicated route or Admin Menu entry was added for ShScript.

## Tests

Added:

`tests/Feature/System/SystemScriptOperationsTest.php`

Coverage includes:

- registry is empty until explicit approval;
- unknown operation rejection;
- Livewire has no shell editor/direct filesystem/shell execution primitives;
- action-level `system.commands.run` authorization contract;
- Blade has no create/edit/delete/free-form shell UI;
- Symfony Process argument-array use;
- `/bin/bash`, timeout, output bound and `app/sh` root safety controls;
- production forced-disable containment.

## Remaining Risks / Follow-up

- No script is currently executable because no repository-owned script has been reviewed. This is intentional.
- When adding the first approved script, review its code, fixed arguments, timeout, idempotency, required lock, side effects and rollback path before registration.
- Laravel application logs remain the audit mechanism until a canonical persistent Audit Log framework is introduced.

## Refactor Decision

**Refactor complete for the approved scope.**

Do not restore browser shell authoring, arbitrary file discovery, arbitrary paths, arbitrary arguments, or generic command execution. Any future script operation must be an explicit reviewed server-owned registry entry.
