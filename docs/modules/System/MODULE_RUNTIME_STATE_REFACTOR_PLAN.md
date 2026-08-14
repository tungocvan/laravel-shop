# Module Runtime State Refactor Plan

## Goal
Move module enable/disable state out of tracked `Modules/<Module>/config/module.php` and into deployment runtime state so Admin toggles do not make Git dirty.

Runtime target:
`storage/app/system/module-state.json`

Core invariant: `SOURCE STATE != DEPLOYMENT STATE`.

## Resolution order
`runtime state -> manifest.default_enabled -> manifest.enabled (legacy) -> true`

Missing runtime file is valid and falls back to the manifest. Existing-but-corrupt runtime state must fail safely rather than silently re-enable modules.

## Runtime format
```json
{
  "version": 1,
  "modules": {
    "Website": false,
    "Admission": true
  }
}
```

Only explicit overrides need to be stored.

## Repository abstraction
Introduce `ModuleStateRepository` and `FileModuleStateRepository`. No consumer reads JSON directly.

Required operations: `has`, `get`, `all`, `set`, `forget`.

## File safety
Writes must be lock-protected and atomic: lock -> read -> validate -> modify -> temp write -> atomic rename -> unlock. Failed writes preserve the previous valid state. Never require `chmod 777`.

## Bootstrap
`ModuleServiceProvider` will merge runtime state with manifest defaults into one effective registry. Boot is read-only and does not create the runtime file.

Required/shell modules remain protected. Dependency checks use effective state.

## Module control
`SystemModuleControlService` must stop modifying tracked manifests.

Enable: authorize -> lock -> dependency validation -> migrations -> permission sync -> persist enabled=true -> sync current registry.

Disable: authorize -> required/dependent validation -> persist enabled=false -> sync current registry.

Failed migration or permission sync must not persist enabled=true.

## Lifecycle
Archive/remove forgets runtime state. Reinstalled modules without an override fall back to manifest/default state.

## Request/process semantics
A module already booted cannot be fully unregistered mid-request. Persisted state becomes canonical on the next bootstrap/request. Long-running workers may require restart/reload; automatic infrastructure restart is out of scope.

## Docker
State lives under persistent Laravel `storage`, so it survives normal container restart/recreate/image rebuild while the storage volume is retained. Removing the volume intentionally removes runtime state.

## Backward compatibility
Keep legacy `manifest.enabled` during the transition. Do not bulk-rewrite manifests in the core runtime-state implementation. Later optional modernization may rename it to `default_enabled`.

## Phases
- MR-0: baseline + this plan.
- MR-1: repository contract, file implementation, config, focused tests; no bootstrap integration yet.
- MR-2: integrate resolver into `ModuleServiceProvider`.
- MR-3: refactor `SystemModuleControlService` to persist runtime state only.
- MR-4: lifecycle/archive cleanup.
- MR-5: review `ModulesForm` diagnostics only if needed.
- MR-6: optional manifest modernization.
- MR-7: focused/System/full regression + Admin/Git-clean + Docker persistence smoke tests.

## Required tests
Repository: missing file, valid true/false, unknown module, lazy creation, repeated writes, forget, malformed/invalid state, atomic/concurrency safety.

Later phases: runtime precedence, legacy fallback, required/dependency protection, failed prerequisites not persisting state, manifest unchanged after toggle, archive cleanup.

## Completion criteria
Admin toggle never modifies tracked module source; canonical runtime file is `storage/app/system/module-state.json`; missing file is valid; corrupt existing state fails safely; writes are atomic/concurrency protected; required/dependency rules use effective state; state survives normal Docker recreate; focused/System/full regression and manual smoke tests pass; Git remains clean after runtime toggles.
