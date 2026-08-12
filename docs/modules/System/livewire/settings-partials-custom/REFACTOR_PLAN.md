# Settings/Partials/Custom Livewire Refactor Plan

Plan date: 2026-08-12

Status: **Implemented 2026-08-12**

## Approved Scope

The approved refactor retained the existing custom field types (`text`, `textarea`, `html`, `image`, `gallery`) while moving persistence/file workflows out of Livewire and enforcing canonical System settings permissions.

## Implemented Architecture

```text
/admin/system/settings
  -> system.settings.view
  -> SettingForm
  -> Custom partial
      -> system.settings.update on every mutation
      -> CustomSettingsService
          -> group-scoped Setting CRUD
          -> upload staging
          -> DB transaction
          -> compensation on failure
          -> post-commit old-file cleanup
          -> cache invalidation
```

## Implemented Safety Rules

- no duplicate route/menu for the Custom tab;
- settings menu normalized to `/admin/system/settings` + `system.settings.view`;
- `addField`, `deleteField`, `removeGalleryImage`, `save` require `system.settings.update`;
- Livewire contains no direct `Setting::create/destroy/update` or public-storage orchestration;
- uploads accept JPG/JPEG/PNG/WebP only, max 5 MB/file;
- SVG is not accepted;
- gallery upload count is bounded to 20 per setting per save;
- custom delete re-resolves `group_name=custom`;
- physical delete is restricted to `settings/custom` and `settings/gallery`;
- new files are stored before DB update;
- newly staged files are removed on persistence failure;
- replaced/removed old files are deleted after successful persistence;
- per-setting cache keys are invalidated;
- browser errors are sanitized;
- HTML remains privileged trusted-admin content and is not silently stripped;
- UI includes confirmation/loading/validation/read-only feedback.

## Files Implemented

- `Modules/System/Services/CustomSettingsService.php`
- `Modules/System/Livewire/Settings/Partials/Custom.php`
- `Modules/System/resources/views/livewire/settings/partials/custom.blade.php`
- `Modules/Admin/data/menus.json`
- `tests/Feature/System/SystemCustomSettingsTest.php`
- `docs/modules/System/livewire/settings-partials-custom/ANALYSIS.md`

## Acceptance Status

- [x] Custom remains a tab inside `/admin/system/settings`.
- [x] No duplicate Custom route/menu was created.
- [x] Settings menu visibility uses `system.settings.view`.
- [x] Every Custom mutation enforces `system.settings.update`.
- [x] Livewire no longer owns direct Setting persistence/storage workflows.
- [x] Upload formats/sizes are bounded and SVG is excluded.
- [x] Gallery uploads are bounded.
- [x] Replacement/removal ordering preserves previous files until persistence succeeds.
- [x] Failed persistence cleans staged files where feasible.
- [x] Delete cannot target non-custom settings or files outside approved roots.
- [x] Setting cache invalidation is included.
- [x] HTML trust policy is explicit.
- [x] Delete confirmation/loading/validation feedback is included.
- [x] Focused tests were added.

Runtime test execution remains to be verified in the user's PHP environment after pulling the implementation.
