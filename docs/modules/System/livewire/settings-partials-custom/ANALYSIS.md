# System Livewire Analysis — Settings/Partials/Custom

Analysis date: 2026-08-12

Implementation status: **Refactored 2026-08-12**

## Executive Summary

`Settings/Partials/Custom` has been refactored from a direct Eloquent/public-storage Livewire component into a thin privileged UI backed by `Modules/System/Services/CustomSettingsService.php`.

The previous P1 findings were addressed in the approved scope:

- every mutation now enforces `system.settings.update`;
- Livewire no longer directly creates/deletes/updates `Setting` records;
- Livewire no longer directly stores/deletes public files;
- image/gallery uploads are restricted to JPG/JPEG/PNG/WebP and 5 MB per file;
- SVG is not accepted;
- gallery uploads are bounded to 20 files per setting per save;
- new files are staged before persistence and cleaned on persistence failure;
- replaced/removed old files are deleted only after successful DB persistence;
- delete is scoped to `group_name=custom` and cleanup is restricted to approved storage roots;
- `Setting::getValue()` cache keys are invalidated after custom writes/deletes;
- browser-facing errors are generic while exception class/operation metadata are logged server-side;
- delete confirmation, loading states, validation feedback and view-only UI state were added.

HTML remains an intentional trusted-admin setting type. This refactor does not silently strip HTML; consumers that render HTML raw must apply the project's approved trust/sanitization policy.

## Current Flow

```text
/admin/system/settings
  -> system.settings.view
  -> SettingForm
  -> Custom partial
      -> mutation boundary: system.settings.update
      -> CustomSettingsService
          -> canonical custom Setting query
          -> DB transaction where useful
          -> public storage under approved roots only
          -> staged-file compensation
          -> setting cache invalidation
```

## Service Boundary

`CustomSettingsService` now owns:

- listing custom settings;
- normalized/unique custom-field creation;
- group-scoped deletion;
- associated image/gallery cleanup;
- text/textarea/html persistence;
- image replacement ordering;
- gallery append/removal persistence;
- storage-root enforcement;
- cache invalidation;
- structured operation logging.

Approved public roots are fixed in source:

- `settings/custom`
- `settings/gallery`

Browser-provided destination paths are not supported.

## Upload / File Integrity

Livewire validates:

```text
dynamicImages.*       -> image + jpg/jpeg/png/webp + max 5120 KB
galleryUploads.*      -> array + max 20
galleryUploads.*.*    -> image + jpg/jpeg/png/webp + max 5120 KB
```

Replacement ordering is now:

```text
store new
→ persist DB
→ delete old
```

On DB/persistence failure:

```text
delete newly staged files
→ preserve previously persisted value/files
```

Gallery removals remain pending in Livewire state until Save. The service re-resolves the persisted gallery and only accepts retained paths that were already part of that setting, preventing crafted public paths from becoming delete targets.

## Authorization

Page/menu visibility:

`system.settings.view`

Mutation actions:

- `addField()`
- `deleteField()`
- `removeGalleryImage()`
- `save()`

all enforce:

`system.settings.update`

via `AuthorizesSystemActions`.

## Admin Menu

`Custom` remains a tab inside `/admin/system/settings`; no duplicate route/menu was created.

The existing settings menu was normalized to:

```text
Name: Thiết lập Hệ thống
URL: /admin/system/settings
Can: system.settings.view
```

## HTML Trust Policy

`html` remains a privileged field type because it is existing product behavior. The editor now displays an explicit warning.

Persistence does not sanitize or strip HTML in this component-level refactor. Any consumer that uses raw rendering must treat the setting as trusted-admin HTML or apply an approved sanitizer before output.

Do not broaden this into arbitrary script/storage/path input.

## Tests

Focused coverage added:

`tests/Feature/System/SystemCustomSettingsTest.php`

The tests lock:

- route/menu permission contract;
- action-level update authorization;
- absence of direct persistence/storage logic in Livewire;
- image type/size and gallery-count policy;
- group/root scoping;
- staged-file compensation ordering;
- setting cache invalidation;
- delete confirmation/loading/validation/HTML warning UX.

## Remaining Follow-up

- Add behavioral DB/storage-fake integration tests if the repository standardizes an isolated System settings database fixture.
- Audit consumers of custom HTML values before introducing any additional raw HTML renderer.
- Custom settings remain intentionally small configuration data; no pagination/hard field-count cap was invented without business evidence.

## Refactor Decision

**Major refactor complete for the approved scope.**

Do not restore direct Eloquent/storage mutation in Livewire, unvalidated public uploads, SVG uploads, arbitrary storage paths, or view-only mutation access.
