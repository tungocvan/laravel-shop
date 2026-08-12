# Settings/StorageConfig — P2 Review Decision

Status: **Keep / runtime contract restored 2026-08-12.**

## Corrected Decision

`StorageConfig` must not be retired yet.

The initial retirement review was incomplete because it searched direct Livewire alias references but missed the runtime composition contract in `EnvConfigController::$rawTabs`. `/admin/system/settings/env` includes:

```text
storage → system.settings.storage-config
```

and the ENV page dynamically mounts every ready tab component. Removing the class therefore caused `Livewire\Exceptions\ComponentNotFoundException` before the page could render.

## Implemented Correction

- restored `Modules/System/Livewire/Settings/StorageConfig.php`;
- restored `Modules/System/resources/views/livewire/settings/storage-config.blade.php`;
- kept the current empty placeholder behavior to avoid inventing an unapproved cloud-storage feature;
- added regression coverage tying the ENV controller tab contract to the component/view existence;
- no route, permission, Admin Menu, migration, service, or storage behavior was added.

## Future Refactor Rule

`StorageConfig` can only be removed after the ENV tab contract is deliberately removed/replaced and runtime page coverage confirms `/admin/system/settings/env` renders successfully. If cloud storage is implemented later, create a dedicated feature plan with ENV secret-handling and provider boundaries first.

## Acceptance

```bash
php artisan test tests/Feature/System/SystemRetiredSettingsPlaceholdersTest.php
php artisan test tests/Feature/System
```

Then manually smoke-test:

```text
/admin/system/settings/env
```

Expected: page renders without `ComponentNotFoundException`.
