# Settings/StorageConfig — P2 Retirement Plan

Status: **Approved as part of the 2026-08-12 two-component P2 batch.**

## Decision

Retire `StorageConfig` instead of implementing a feature inside a dead placeholder.

Evidence:

- component only renders a view;
- view is only `<div></div>`;
- no repository reference to `system.settings.storage-config` was found;
- no route/tab/menu contract points to this component;
- commented imports are abandoned design noise.

## Implementation

- delete `Modules/System/Livewire/Settings/StorageConfig.php`;
- delete `Modules/System/resources/views/livewire/settings/storage-config.blade.php`;
- add regression coverage asserting the stale component/view remain absent;
- do not create replacement route, permission, menu, service or migration;
- if storage configuration is needed later, define a new feature contract first and follow ENV secret-handling rules.

## Acceptance

- no active repository reference is broken;
- focused retirement test passes;
- full `tests/Feature/System` suite has 0 failures.
