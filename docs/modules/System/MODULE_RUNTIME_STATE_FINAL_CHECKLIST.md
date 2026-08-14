# Module Runtime State Final Checklist

## Automated regression

Run in this order:

```bash
php artisan test tests/Feature/System/ModuleStateRepositoryTest.php \
  tests/Feature/System/ModuleStateResolverTest.php \
  tests/Feature/System/ModuleBootstrapRuntimeStateTest.php \
  tests/Feature/System/SystemModuleRuntimeControlTest.php \
  tests/Feature/System/SystemModuleRuntimeLifecycleTest.php \
  tests/Feature/System/SystemModuleRuntimeUiTest.php \
  tests/Feature/System/SystemModuleRuntimeGitCleanTest.php \
  tests/Feature/System/SystemModulesControlTest.php

php artisan test tests/Feature/System

php artisan test
```

## Manual Admin smoke

1. Start with a clean Git working tree.
2. Open `/admin/system/modules`.
3. Toggle one non-required module.
4. Confirm the UI shows source `Runtime`.
5. Confirm `storage/app/system/module-state.json` exists and contains the override.
6. Confirm the module manifest under `Modules/<Module>/config/module.php` is unchanged.
7. Confirm `git status` stays clean.
8. Toggle the module back if needed and confirm runtime state updates accordingly.

## Runtime fallback smoke

1. Back up `storage/app/system/module-state.json` if it exists.
2. Remove only the runtime state file.
3. Reboot/reload the application process.
4. Confirm module state falls back to `default_enabled`, then legacy `enabled`, then `true`.
5. Restore the runtime file if the environment needs its previous deployment state.

## Docker persistence smoke

For an environment where Laravel `storage` is a persistent Docker volume:

1. Toggle a non-required module and confirm `module-state.json` is written.
2. Record the state file content.
3. Restart/recreate the application container without deleting the storage volume.
4. Confirm the same runtime state is still present and the module effective state matches it.
5. Confirm `git status` remains clean.

Do not delete the storage volume during this smoke test; volume deletion intentionally removes deployment runtime state.

## Completion gate

MR-7 passes only when:

- focused runtime-state tests pass;
- full `tests/Feature/System` regression passes;
- full project regression passes;
- Admin toggle smoke passes;
- manifest remains unchanged after toggle;
- Git remains clean after toggle;
- Docker persistence smoke passes on the deployment topology that persists Laravel `storage`.
