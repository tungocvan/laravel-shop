# Settings/ArtisanList Livewire Analysis

Analysis date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/ArtisanList.php` and direct dependencies only. Documentation-only analysis; application source code is unchanged.

## Executive Summary

`Settings/ArtisanList` is a browser-accessible Artisan command terminal. Its current PHP action accepts arbitrary command text from Livewire state and passes it directly to `Artisan::call()`. The component has no action-level authorization, no command allowlist, no validation beyond non-empty input, no audit trail, and returns command exception messages/output to the UI.

The immediate production risk is partially contained because `Modules/System/Services/SystemConfigService.php` forcibly disables `system.settings.artisan-list` during tab normalization, and the core tab is also configured with `enabled => false`. This containment is important and must not be weakened. The source remains a P0 latent remote-administration surface if mounted through another path or if the containment is removed.

Recommended direction: **remove the free-form terminal from production architecture or replace it with explicitly allowlisted operations implemented behind `system.commands.run` authorization.** Do not simply add one permission check and keep unrestricted arbitrary command execution as the long-term design.

## Component Purpose

- PHP: `Modules/System/Livewire/Settings/ArtisanList.php`
- Blade: `Modules/System/resources/views/livewire/settings/artisan-list.blade.php`
- Alias: `system.settings.artisan-list`
- Intended purpose: execute Laravel Artisan commands from the admin UI and display output.
- Core tab: `Modules/System/config/system_tabs.php`, `id=artisan`, disabled by default.
- Production containment: `SystemConfigService::DISABLED_PRODUCTION_COMPONENTS` forces the component disabled.

## Dependency Flow

```text
/admin/system
  -> SystemController
  -> SystemConfigService::getTabs()
  -> production normalization forces artisan-list disabled

If component is mounted:
Livewire ArtisanList
  -> Artisan facade
  -> Laravel console commands
  -> potentially filesystem / database / cache / secrets / queues / application state
```

No dedicated service layer is used.

## Livewire PHP Analysis

Public state:

- `$artisanCommand`
- `$commandOutput`
- `$errorMessage`

Main mutation:

```text
executeArtisanCommand()
  -> trim/non-empty check
  -> Artisan::call($this->artisanCommand)
  -> Artisan::output()
```

There is no command parser/allowlist, no capability check, no production environment guard inside the component, no rate/concurrency guard, and no audit record.

This violates the repository's `MODULE_STANDARD.md` requirement that sensitive mutations enforce authorization at the action boundary and that Livewire delegate domain/operational workflows to services.

## Livewire Blade Analysis

The UI contains:

- free-form command text input;
- execution button with loading/disabled state;
- terminal output display;
- common-command shortcuts.

The shortcuts currently include:

- `list`
- `key:generate`
- `optimize:clear`
- `db:seed`
- `migrate:fresh`

`key:generate` can rotate the application key, and `migrate:fresh` is destructive to database contents. Presenting these as common production commands materially increases operational risk.

Positive UI behavior:

- action button uses `wire:loading.attr="disabled"`;
- command output uses escaped Blade output (`{{ }}`), reducing direct HTML injection risk from console output.

UI concerns:

- badge says `Production Mode` while exposing a terminal design;
- no destructive-operation confirmation;
- no command-specific explanation of impact;
- no permission/role information;
- inline `<style>` exists rather than shared UI utilities.

## State / Validation / Actions

Validation is insufficient for the risk level:

- only an empty-string check exists;
- command name and arguments are unrestricted;
- no maximum input size;
- no denial of destructive commands;
- no allowlist of safe commands.

The method accepts one string into Laravel's console kernel. Exact parsing behavior is framework-controlled, but arbitrary Artisan access is sufficient to classify the component as a privileged execution surface.

## Authorization

**P0 finding.**

`Modules/System/config/module.php` defines the specific permission `system.commands.run`, but `ArtisanList` does not use `AuthorizesSystemActions` and does not call authorization before execution.

The `/admin/system` page has `system.manage`, but repository standards explicitly require sensitive Livewire mutations to enforce authorization at the action boundary rather than relying only on page access.

Production tab normalization currently reduces exposure by forcing this alias disabled. This is containment, not authorization.

## Service / Model Dependencies

Direct dependency:

- `Illuminate\Support\Facades\Artisan`

No System service encapsulates command execution. This makes Livewire directly responsible for a privileged operational workflow.

No direct model is used, but commands executed through Artisan may themselves mutate any application subsystem.

## Performance

Potential concerns if enabled:

- commands can be long-running and block the Livewire request;
- output is retained in component state and returned to the browser;
- repeated clicks are UI-disabled while loading, but there is no server-side idempotency/concurrency policy;
- users can trigger cache rebuilds, migrations, seeds or other resource-intensive operations.

## Security / Data Integrity

### P0-1 — Arbitrary privileged Artisan execution

**Evidence:** `executeArtisanCommand()` passes user-controlled `$artisanCommand` to `Artisan::call()`.

**Impact:** depending on registered commands, an authorized page user could mutate database/schema, configuration, users, caches, files, queues, secrets or other production state.

**Recommendation:** remove free-form execution. Replace with a small server-side operation registry where every operation has a stable ID, fixed command/arguments, explicit permission, confirmation policy, timeout and audit record.

### P0-2 — Missing action-level authorization

**Evidence:** manifest contains `system.commands.run`; component does not enforce it.

**Impact:** authorization boundary is weaker than repository standard and depends on outer rendering/middleware behavior.

**Recommendation:** any retained operational action must call `authorizePermission('system.commands.run')` or an equivalent canonical authorization mechanism before execution.

### P0-3 — Destructive commands promoted in UI

**Evidence:** UI suggestions include `migrate:fresh`, `db:seed`, and `key:generate`.

**Impact:** accidental destructive or environment-changing execution is made easier.

**Recommendation:** remove these shortcuts even in development unless an explicit safe workflow requires them.

### P1-1 — Exception/output disclosure

**Evidence:** caught exception messages are sent directly to `$errorMessage`; Artisan output is displayed to the browser.

**Impact:** command output can reveal filesystem paths, table names, internal configuration context or operational details.

**Recommendation:** log detailed errors server-side and return sanitized operator messages.

### P1-2 — No audit trail

**Impact:** production command execution would not have a durable actor/action/outcome trail within this component.

**Recommendation:** privileged operations should log actor, operation ID, timestamp, result and safe metadata without secrets.

## UI/UX Compliance

Partial compliance:

- responsive layout;
- loading state;
- escaped output;
- clear command/output areas.

Non-compliance/material concerns:

- dangerous action lacks confirmation;
- unrestricted free-form terminal is not a safe admin operation pattern;
- inline CSS duplicates styling concerns;
- the UI does not distinguish read-only vs destructive commands;
- `wire:model.live.debounce.500ms` is unnecessary for a command field that only needs its value on submit.

## Test Coverage

Repository test trees under `tests/Feature` and `tests/Unit` contain no System-specific test suite for this component.

Required tests if any replacement is retained:

- user without `system.commands.run` cannot execute;
- allowlisted operation executes expected fixed command only;
- arbitrary command text is impossible/rejected;
- destructive operations require explicit confirmation/policy;
- production-disabled behavior cannot be overridden through System tab JSON;
- errors are sanitized and audit records are generated.

## Issue List

### P0 — Free-form Artisan terminal

**File:** `Modules/System/Livewire/Settings/ArtisanList.php`

**Evidence:** user-controlled command is passed to `Artisan::call()`.

**Problem:** unrestricted privileged command execution.

**Impact:** production control, data-loss and secret/configuration risk.

**Recommendation:** remove or replace with allowlisted operational actions.

### P0 — Missing `system.commands.run` authorization

**File:** `Modules/System/Livewire/Settings/ArtisanList.php`

**Evidence:** permission exists in `Modules/System/config/module.php`, but no action authorization is performed.

**Impact:** mutation boundary does not meet repository security standard.

**Recommendation:** enforce permission on every retained operation.

### P1 — Raw operational output/error exposure

**File:** PHP + Blade pair.

**Recommendation:** sanitize browser-facing errors and keep detailed diagnostics in protected logs.

## Recommended Direction

**Refactor/replacement, with current production disablement preserved until complete.**

Preferred target:

```text
Livewire UI
  -> authorize system.commands.run
  -> SystemOperationService
  -> fixed operation registry / allowlist
  -> Process/Artisan with fixed arguments + timeout
  -> audit log
```

Do not restore a generic web terminal in production.

## Open Questions / Unknowns

- Whether another module/page directly mounts `system.settings.artisan-list` outside `SystemConfigService` was not proven within the allowed component-level scope.
- Whether external infrastructure blocks Livewire access to this alias cannot be established from this component alone.
- Exact approved audit-log infrastructure for privileged System actions should be resolved before implementation.
