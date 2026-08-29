# System Collaboration Handoff

## Current Status

- Module: `System`
- Feature: Module Catalog & Runtime Boundaries — Phase A
- Delivery branch: `refactor/system-module-catalog-runtime-boundaries`
- Base/source checkpoint: `main@62eb2e76126f92842a906ffb58fa0deb076c26d5`
- Implementation status: **IMPLEMENTED — AWAITING OPERATOR VERIFICATION**
- Pull request: **NOT OPENED**

This phase separates filesystem catalog discovery, graph validation and current-request registry projection without changing the established `config('modules.registry')` consumer contract. Module runtime state remains an atomic file-backed override, manifests remain immutable at runtime, and the root provider keeps its existing registration behavior and boot order.

## Approved Scope

- introduce one read-only `ModuleCatalog` for filesystem discovery, manifest normalization and runtime-state resolution;
- introduce one pure `ModuleGraphValidator` for boot and runtime transition rules;
- introduce `ModuleRegistry` as the compatible current-request projection boundary;
- reduce the root `ModuleServiceProvider` to catalog/validation/projection orchestration plus enabled-module registration;
- make `ModulePermissionManager` consume canonical catalog descriptors instead of independently scanning module directories;
- make System module control preflight against a fresh catalog and publish through `ModuleRegistry` after persistence;
- extract module overview rows and realtime mutation into dedicated System services;
- retire browser-driven module source archival and its `Gỡ` action;
- migrate directly affected Request, Ebook, System and module-runtime tests from private provider discovery/graph methods to public contracts.

## Runtime Ownership Contract

| Concern | Canonical owner | Contract |
|---|---|---|
| Filesystem discovery and manifest normalization | `App\Modules\ModuleCatalog` | Read-only descriptors; directory name remains the canonical module name |
| Runtime enabled-state resolution | `ModuleCatalog` + `ModuleStateResolver` | Runtime override wins over manifest default; shell modules remain enabled |
| Dependency rules | `App\Modules\ModuleGraphValidator` | Required, missing, disabled, self and circular dependency rules share one implementation |
| Current-request registry | `App\Modules\ModuleRegistry` | Publishes the existing seven-field `config('modules.registry')` shape |
| Boot registration | `Modules\ModuleServiceProvider` | Registers only enabled modules in the existing type/name order |
| Runtime mutation | `Modules\System\Services\SystemModuleControlService` | Fresh preflight, migration/permission sync, atomic state persistence, then registry refresh |
| System module overview | `SystemModuleOverviewService` | Builds dependency and database-health rows for the existing Livewire screen |
| Realtime mutation | `SystemRealtimeControlService` | No longer coupled to module lifecycle control |

## Preserved Boundaries

- no migration, schema, setting-key, permission-name or stored-data change;
- no module manifest mutation;
- no change to `storage/app/system/module-state.json` schema or locking behavior;
- no change to the public registry keys: `name`, `type`, `enabled`, `required`, `depends`, `path`, `source`;
- no change to module type fallback, required-shell behavior, existing type/name boot order, provider/config/route/resource registration or Super Admin gate;
- no route-name or URL change for `/admin/system/modules` and the historical settings component alias;
- no package addition.

## Retired Boundary

The browser no longer moves `Modules/<Module>` into `storage/app/module-trash`. The Livewire `deleteModule` action, the control/lifecycle `archive` methods and the `Gỡ` button were removed. Adding or removing tracked module source must use a reviewed deployment workflow rather than a browser permission that dirties the production Git worktree.

## Files

### Added

```text
app/Modules/ModuleCatalog.php
app/Modules/ModuleGraphValidator.php
app/Modules/ModuleRegistry.php
Modules/System/Services/SystemModuleOverviewService.php
Modules/System/Services/SystemRealtimeControlService.php
tests/Feature/System/ModuleCatalogRegistryTest.php
tests/Feature/System/ModuleGraphValidatorTest.php
```

### Updated

```text
Modules/ModuleServiceProvider.php
app/Modules/ModuleLifecycleManager.php
app/Modules/ModulePermissionManager.php
Modules/System/Livewire/Settings/ModulesForm.php
Modules/System/Services/SystemModuleControlService.php
Modules/System/resources/views/livewire/settings/modules-form.blade.php
tests/Feature/Ebook/EbookBootstrapTest.php
tests/Feature/Modules/ModuleRuntimeStateToggleTest.php
tests/Feature/Request/Architecture/RequestBootstrapTest.php
tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
tests/Feature/System/SystemModuleRuntimeControlTest.php
tests/Feature/System/SystemModuleRuntimeGitCleanTest.php
tests/Feature/System/SystemModuleRuntimeLifecycleTest.php
tests/Feature/System/SystemModulesControlTest.php
```

## Verification Gate

Completed locally:

```text
PHP syntax parse for changed/new PHP files    PASS
Pint 1.30.5 changed/new PHP files             PASS
git diff --check                              PASS
```

Pending operator verification in the application environment:

```text
Focused catalog/graph/registry/state/control tests
System Feature regression
Role Feature regression
Request architecture/authorization/module-state regression
Ebook bootstrap regression
Admission permission-catalog regression
ClientPortal and ClientApps registry-consumer regression
Admin Feature regression
System module route inspection
Frontend production build
Desktop/mobile System Modules UI acceptance
Toggle round-trip with manifest and Git worktree unchanged
```

A full-project regression is outside the approved gate.

## Deferred Work

- Dependency-topological boot ordering. Phase A intentionally preserves the established type/name order across all modules.
- Splitting the existing module screen into separate Livewire child components. This is a Phase B decision after Phase A stabilizes.
- Cross-module/distributed locking for concurrent dependency transitions.
- Consolidating other permission-domain filesystem scans that are not part of root module discovery.
- Scheduler idempotency, distributed locks and persisted health heartbeat improvements.

## PR and Merge Gate

1. **COMPLETE** — Scope approved and branch created from `main@62eb2e76126f92842a906ffb58fa0deb076c26d5`.
2. **COMPLETE** — Catalog, validator, registry, System adapters, archive retirement and directly affected tests implemented.
3. **COMPLETE** — Local syntax, Pint and whitespace gates passed.
4. **PENDING** — Operator pulls the branch and runs the approved focused/regression/build/UI gates.
5. **PENDING** — A PR is opened only after the operator reports the required gates.
6. **PENDING** — User performs manual review and merge; automatic merge is not authorized.
