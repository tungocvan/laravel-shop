# Settings/ShScript Livewire Analysis

Analysis date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/ShScript.php` and direct dependencies only. Documentation-only analysis; application source code is unchanged.

## Executive Summary

`Settings/ShScript` is a privileged browser shell-script editor and executor. It can list files under `app/sh`, read a selected file, create/update files, set executable permissions, execute them with `shell_exec("bash {$scriptPath}")`, and delete them. The component performs these operations directly inside Livewire without action-level authorization, a service boundary, strict filename validation, path canonicalization, audit logging, execution timeout, or server-side concurrency controls.

Current production exposure is partially contained because `Modules/System/Services/SystemConfigService.php` forcibly disables `system.settings.sh-script` during tab normalization, and the core System tab is disabled by default. This containment is essential and should remain in place.

The component remains a **P0 latent remote-code-execution / production-control surface** if it is mounted by another path or if the production disablement is relaxed. The preferred direction is removal from production-facing architecture or replacement with a narrowly allowlisted operational-job system. Adding only a permission check is not sufficient as the final design.

## Component Purpose

- PHP: `Modules/System/Livewire/Settings/ShScript.php`
- Blade: `Modules/System/resources/views/livewire/settings/sh-script.blade.php`
- Alias: `system.settings.sh-script`
- Managed directory: `app_path('sh')`
- Intended features: list, read, create, update, chmod, execute, and delete `.sh` scripts.
- Core tab: disabled by default.
- Production normalization: component alias is always forced disabled by `SystemConfigService`.

## Dependency Flow

```text
/admin/system
  -> SystemConfigService::getTabs()
  -> production normalization forces sh-script disabled

If mounted:
Livewire ShScript
  -> File facade
  -> app/sh/*
  -> chmod(..., 0755)
  -> shell_exec("bash {$scriptPath}")
  -> operating system / application filesystem / external services
```

No dedicated service layer is used.

## Livewire PHP Analysis

Public state includes:

- `$scripts`
- `$selectedScript`
- `$scriptContent`
- `$errorMessage`
- `$newScriptName`
- `$executionOutput`

Lifecycle:

- `mount()` calls `loadScripts()`.
- `loadScripts()` creates `app/sh` with mode `0755` if absent and lists all files in that directory.

Mutating/privileged actions:

- `selectScript()` reads selected file content.
- `saveScript()` writes arbitrary script content and executes `chmod(..., 0755)`.
- `executeScript()` invokes the selected script via `shell_exec()`.
- `deleteScript()` deletes the selected file.

The component directly owns filesystem and OS execution behavior, which violates the repository preference that Livewire own UI state/validation and delegate privileged workflows to services.

## Livewire Blade Analysis

The UI provides:

- select box of existing scripts;
- editable filename input;
- full script-content textarea;
- create/update action;
- execute action;
- delete action;
- terminal output.

Positive UX points:

- selected script name is escaped in Blade;
- execution output is rendered with escaped `{{ }}` syntax;
- an execution loading indicator exists.

Material UI concerns:

- save/update/delete buttons do not show server-side loading/disabled protection;
- delete has no `wire:confirm` in the inspected Blade;
- execute has no explicit confirmation despite arbitrary OS-level effect;
- the page states “Hệ thống ổn định”, which does not communicate the risk of running shell scripts;
- filename and script content use `wire:model.live`, creating unnecessary request traffic for fields that need synchronization only on action;
- inline `<style>` is present.

## State / Validation / Actions

Validation is critically insufficient:

- `newScriptName` is checked only for non-empty text;
- no `.sh` extension requirement;
- no character allowlist;
- no basename/canonicalization enforcement;
- no path traversal rejection;
- no maximum filename length;
- no content size limit;
- no command/content allowlist;
- no execution timeout.

`selectedScript` also reaches `app_path("sh/{$script}")` / `app_path("sh/{$this->selectedScript}")` without explicit basename/canonical path checks inside the component.

Because Livewire public state originates from the client boundary, server-side path validation is required even if normal UI options come from a server-generated list.

## Authorization

**P0 finding.**

`Modules/System/config/module.php` defines `system.commands.run`, but `ShScript` does not use `AuthorizesSystemActions` and none of its privileged actions enforce a permission.

The outer `/admin/system` route has `system.manage`, but repository standards require authorization at sensitive mutation boundaries.

Production tab normalization currently forces the component disabled, which is valuable containment but not an authorization implementation.

## Service / Model Dependencies

Direct dependency:

- `Illuminate\Support\Facades\File`
- native `chmod()`
- native `shell_exec()`

No dedicated service owns:

- safe path resolution;
- script registry;
- execution policy;
- timeouts;
- output limits;
- auditing;
- concurrency;
- environment restrictions.

No direct Eloquent model is used.

## Performance

Potential production issues if enabled:

- `shell_exec()` is synchronous and can block indefinitely based on script behavior;
- command output is captured into Livewire state and can become large;
- no output-size cap;
- no timeout;
- no queue/background orchestration;
- no lock preventing repeated/concurrent execution of the same script;
- `wire:model.live` on large script content can produce unnecessary network requests while editing.

## Security / Data Integrity

### P0-1 — Remote arbitrary shell-code execution capability

**Evidence:** browser-edited `$scriptContent` is written to an executable file and later passed to `bash` through `shell_exec()`.

**Impact:** if exposed, a permitted or compromised admin session could execute arbitrary OS commands with the PHP process user's privileges, affecting files, secrets, database credentials, deployments, network calls, application availability and data integrity.

**Recommendation:** do not provide arbitrary shell editing/execution in production. Replace it with fixed operational jobs/scripts whose identifiers and arguments are server controlled.

### P0-2 — Missing action-level authorization

**Evidence:** no permission guard exists in save, execute, delete, select/read, or script-list operations.

**Recommendation:** any retained privileged operation must require `system.commands.run` or more granular permissions, ideally split into read/run/manage capabilities.

### P0-3 — Unsafe path construction / traversal risk

**Evidence:** `newScriptName` and `selectedScript` are concatenated into `app/sh` paths without basename/canonicalization or an allowlisted filename pattern.

**Impact:** manipulated Livewire state may target paths outside the intended script directory depending on path value and filesystem behavior.

**Recommendation:** resolve through a service that accepts only stable script IDs or strict filenames, rejects separators and dot segments, canonicalizes the final path, and verifies it remains under the approved root.

### P1-1 — No execution timeout/output bound

**Impact:** resource exhaustion, hung PHP workers, oversized Livewire payloads.

**Recommendation:** if script execution remains in any form, use Symfony Process with fixed executable/arguments, timeout, output limits and explicit exit-code handling.

### P1-2 — No audit trail

**Impact:** no durable record of actor, script/operation, execution time, outcome, or destructive modifications.

**Recommendation:** audit all privileged system operations with safe metadata.

### P1-3 — Direct raw exception/output behavior

`selectScript()` swallows read exceptions and resets content, while execution only distinguishes null output from non-null output. Exit codes/stderr are not modeled, reducing operational correctness and observability.

## UI/UX Compliance

Partial compliance:

- responsive layout;
- escaped output;
- execution loading indicator;
- clear editor/output areas.

Material non-compliance:

- no confirmation for delete or execute;
- mutation buttons generally lack disabled/loading states;
- live binding is excessive for large content;
- no explicit warning of production/destructive scope;
- no controlled operation model;
- inline styling duplicates UI concerns.

## Test Coverage

No System-specific tests for this component were found in the repository's `tests/Feature` or `tests/Unit` trees.

Required tests for any safe replacement:

- denied user cannot read/manage/run scripts;
- production-disabled tab cannot be overridden;
- unsafe script identifiers/path traversal are rejected;
- only registered operations/scripts may execute;
- timeout and non-zero exit code are handled;
- output is bounded/sanitized;
- audit record contains actor and result;
- destructive actions require explicit confirmation/policy.

## Issue List

### P0 — Arbitrary shell execution

**File:** `Modules/System/Livewire/Settings/ShScript.php`

**Evidence:** editable script content is written and executed with `bash` via `shell_exec()`.

**Problem:** browser-accessible arbitrary OS command capability.

**Impact:** production compromise/data-loss/secret exposure risk.

**Recommendation:** remove from production architecture or replace with an allowlisted operation runner.

### P0 — Missing authorization

**Evidence:** `system.commands.run` exists in the manifest but actions do not enforce it.

**Recommendation:** enforce action-level permission on every retained privileged operation.

### P0 — Path validation missing

**Evidence:** client-controlled filename/selection is concatenated into filesystem paths.

**Recommendation:** strict identifier validation and canonical root enforcement.

### P1 — Synchronous unbounded execution

**Evidence:** native `shell_exec()` without timeout/output policy.

**Recommendation:** fixed Symfony Process operations with timeout and bounded output, or queue a controlled operational job.

## Recommended Direction

**Remove/replace; keep production disablement until replacement is complete.**

Preferred architecture:

```text
Livewire UI
  -> authorize explicit operation permission
  -> SystemOperationService
  -> server-owned operation registry
  -> fixed script/executable + fixed/validated args
  -> Symfony Process timeout
  -> bounded output
  -> audit log
```

The browser should never be allowed to author arbitrary production shell code.

## Open Questions / Unknowns

- Whether another page/module mounts this alias directly outside `SystemConfigService` is not proven in this component-level scope.
- Existing scripts under `app/sh` were not enumerated because their contents are not required to establish the component-level P0 issue.
- The repository's canonical audit implementation for privileged system operations needs to be selected during the implementation/refactor task.
