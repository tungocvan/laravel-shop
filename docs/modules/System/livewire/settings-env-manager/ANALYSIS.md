# System Livewire Analysis — Settings/EnvManager

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/EnvManager.php` creates `.env.<suffix>` snapshots by copying the current `.env`. The component is **P1 / Refactor or remove** because `exportEnv()` has no authorization, the suffix is supplied to the service without an explicit allowlist at the component/service boundary, and `render()` calls an unfinished `getTabsDefinition()` method that returns nothing.

## Component Purpose

Path: `Modules/System/Livewire/Settings/EnvManager.php`

Alias: `system.settings.env-manager`

View: `System::livewire.settings.env-manager`

Current visible UI offers two fixed actions:

- Snapshot Production
- Snapshot Local

## Dependency Flow

Env settings UI
→ `EnvManager`
→ `EnvManagerService::exportToEnvironment($suffix)`
→ copy `.env` to `.env.<suffix>`

## Livewire PHP Analysis

`exportEnv(string $envType, EnvManagerService $service)` directly passes a caller-supplied suffix into `exportToEnvironment()`.

`render()` calls `getTabsDefinition()` and passes `$tabs` to the view, but the private method currently has only a comment and returns nothing. The current Blade does not use `$tabs`, so this defect may be latent rather than immediately visible.

No authorization is enforced.

## Livewire Blade Analysis

The Blade currently exposes only two hard-coded buttons (`production`, `local`), which reduces ordinary UI input risk. However, Livewire public actions should not rely on the Blade being the only caller.

Production button has loading/disabled state. Local button lacks equivalent loading state.

## State / Validation / Actions

Action:

- `exportEnv($envType)`

The component and service should enforce a strict allowlist of supported snapshot suffixes instead of trusting a string argument.

## Authorization

**P1:** environment snapshots contain all application secrets and should require `system.env.update` or a dedicated env-backup capability.

## Service / Model Dependencies

`EnvManagerService::exportToEnvironment()` currently constructs `base_path(".env.{$suffix}")` directly and copies `.env` there if present.

Unlike `update()`, snapshot export does not validate or normalize the suffix itself.

## Performance

Negligible. File copy size is the size of `.env`.

## Security / Data Integrity

### P1 — Missing authorization

Creating secret-bearing environment copies is a privileged operation.

### P1 — Unvalidated suffix at service boundary

Although the current UI only sends `production`/`local`, the public Livewire action accepts arbitrary strings. The service should reject anything outside an explicit allowlist or a conservative suffix pattern and expected names.

### P1 — Secret proliferation

Every snapshot creates another plaintext copy of all environment secrets in the project root. File permissions, retention and backup policy need to be explicit.

### P2 — Dead/incomplete tabs logic

`getTabsDefinition()` returns nothing and should either be completed or removed if obsolete.

## UI/UX Compliance

Positive:

- simple explicit snapshot choices;
- production action has progress/disabled state.

Needs improvement:

- consistent loading state on both actions;
- confirmation before production snapshot;
- clear destination/retention explanation;
- success should identify snapshot name without exposing path details unnecessarily.

## Test Coverage

No System-specific test was found.

Missing tests:

- unauthorized export rejection;
- unsupported suffix rejection;
- expected file permissions and destination;
- missing `.env` behavior;
- no overwrite/retention collision issues.

## Issue List

### P1 — Missing env snapshot authorization

### P1 — Snapshot suffix not validated at component/service boundary

### P1 — Plaintext secret copies lack documented retention/permission policy

### P2 — `getTabsDefinition()` is incomplete/dead logic

## Recommended Direction

**Minor-to-Major Refactor depending on intended use.** If environment snapshots are still required, secure and simplify the component. If not operationally required, remove the component rather than completing unused tab logic.

## Open Questions / Unknowns

- Whether `.env.production` and `.env.local` are deployed artifacts or only manual snapshots.
- Required retention and filesystem permissions for env snapshots.
- Whether this component is currently mounted anywhere in active System UI.
