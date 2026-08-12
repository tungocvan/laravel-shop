# Settings/Partials/Custom Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/Partials/Custom.php`, its Blade UI, custom-setting persistence/upload service, focused tests, and normalization of the existing `/admin/system/settings` Admin Menu entry.

Status: **Awaiting explicit approval before implementation.**

## 1. Goal

Refactor the dynamic Custom settings partial so it becomes a thin Livewire UI over a controlled service layer, enforces `system.settings.update` at every mutation boundary, validates all public uploads, preserves/cleans files safely, avoids partial multi-resource writes where practical, and makes the HTML trust policy explicit.

This is a major refactor, not a feature rebuild. Existing field types remain supported:

- text
- textarea
- html
- image
- gallery

## 2. Route and Admin Menu

`Custom` is not a standalone page. It is mounted by `Settings/SettingForm` as the `custom` tab inside:

```text
GET /admin/system/settings
name: admin.system.settings.index
middleware: auth:admin + permission:system.settings.view,admin
```

Therefore implementation must **not create a duplicate route or a separate Admin Menu item for Custom**.

The existing canonical Admin Menu entry for `/admin/system/settings` is currently legacy:

```text
Name: Thiết lập Biến
URL: /admin/system/settings
Can: view_staff
```

Normalize it to:

```text
Name: Cấu hình Hệ thống
URL: /admin/system/settings
Can: system.settings.view
Active: true
```

If renaming would conflict with the existing `/admin/system` label, use `Thiết lập Hệ thống` instead; the important requirement is the canonical URL and `system.settings.view` capability.

For existing installations, provide a narrowly scoped idempotent database update for this one menu entry. Do not wipe/reseed the entire menu table.

## 3. Authorization

Page/menu visibility remains:

`system.settings.view`

Every Custom mutation must enforce:

`system.settings.update`

using `AuthorizesSystemActions`.

Actions requiring update permission:

- `addField()`
- `deleteField()`
- `removeGalleryImage()` because it changes pending persisted state and will become service-backed removal intent
- `save()`

Read/load/render state may remain available to users with view permission.

No new permission is required because `system.settings.view` and `system.settings.update` already exist in `Modules/System/config/module.php`.

## 4. Livewire Responsibility

Keep in Livewire:

- public UI state;
- form validation;
- upload input state;
- selected/pending gallery state;
- authorization at action boundary;
- service delegation;
- refresh state;
- safe user feedback.

Move out of Livewire:

- `Setting::create()` / `Setting::destroy()` / direct model updates;
- public-disk `Storage::delete()` / upload-store orchestration;
- gallery JSON persistence;
- replacement-file ordering;
- associated-file cleanup on field deletion;
- transaction/compensation workflow.

## 5. New Custom Settings Service

Create a focused service, proposed:

`Modules/System/Services/CustomSettingsService.php`

Responsibilities:

### Read

- return custom settings in a stable display structure;
- decode gallery JSON safely;
- do not expose filesystem internals beyond the stored public relative paths already needed for previews.

### Create field

- accept validated label/key/type only;
- force `group_name = custom` server-side;
- normalize key server-side;
- enforce unique key at the database boundary in addition to Livewire validation;
- return created setting metadata.

### Delete field

- re-resolve the setting by ID with `group_name = custom` so a crafted ID cannot delete another settings group;
- identify associated image/gallery files;
- delete database row and clean owned files with deliberate ordering/compensation;
- never delete arbitrary paths outside approved `settings/custom` and `settings/gallery` roots.

### Save values/uploads

- re-resolve current custom settings from database instead of trusting Livewire's `$customSettings` objects as the source of truth;
- accept only setting IDs that belong to `group_name = custom`;
- save text/textarea/html values;
- validate/normalize gallery arrays;
- store new files before deleting replaced old files;
- update database only after required new uploads succeed;
- delete old/replaced files only after successful database persistence;
- if database persistence fails after storing new files, delete newly stored files where feasible;
- clear relevant `Setting::getValue()` cache keys after successful writes.

## 6. Upload Policy

All image and gallery uploads must be validated in Livewire before service invocation.

Initial policy:

```text
image types: jpg, jpeg, png, webp
max per file: 5 MB
```

Do not allow SVG in this refactor because active SVG content can become a browser execution surface when publicly served.

Proposed validation:

- each `dynamicImages.*`: nullable, image, mimes:jpg,jpeg,png,webp, max:5120;
- each `galleryUploads.*.*`: image, mimes:jpg,jpeg,png,webp, max:5120;
- impose a bounded gallery upload count per save (proposed max 20 files per setting per request unless existing business evidence requires another bound).

Service must also enforce approved storage roots and must never accept a browser-provided destination path.

## 7. File Replacement / Compensation

For a single image replacement use:

```text
validate
→ store new file
→ update setting row
→ delete old file
```

If DB update fails:

```text
delete newly stored file
→ keep old DB value/file
```

For gallery uploads:

```text
validate all
→ store new files
→ build final bounded gallery list
→ update setting row
→ delete explicitly removed old gallery files
```

If persistence fails, delete newly uploaded files and leave previously persisted gallery files intact.

Do not interleave destructive old-file deletion before successful persistence.

## 8. Gallery Removal Semantics

Current `removeGalleryImage()` only mutates Livewire state and actual cleanup occurs implicitly/never.

Refactor should treat removals as pending state until `save()` succeeds.

Requirements:

- only remove an image path that already belongs to the target custom gallery setting;
- track removed persisted paths separately from new uploads;
- delete removed physical files only after successful DB update;
- prevent crafted indexes/paths from deleting unrelated public files.

## 9. HTML Policy

`html` remains a supported trusted-admin field type because it is an intentional current feature.

For this refactor:

- authorization must be `system.settings.update`;
- UI must label HTML fields as privileged content;
- persistence may retain HTML, but the service must not transform arbitrary upload/path data through it;
- add documentation that consumers must either escape HTML or sanitize using an approved policy before raw rendering;
- search/identify current raw renderers in focused follow-up/tests where feasible.

Do not silently strip all HTML in this task because that would be a behavior-breaking feature change without evidence of the intended allowed markup.

If an existing canonical sanitizer is found in the repository during implementation, reuse it rather than inventing another sanitizer.

## 10. Data Integrity / Transactions

Database-only create/delete/update work should use DB transactions where useful.

File operations cannot participate in DB transactions, so use explicit compensation as described above.

The global `save()` should avoid silently committing half the settings when a later upload/write fails. Preferred approach:

- validate all inputs first;
- stage uploads;
- persist database changes in a transaction when feasible;
- on DB failure, clean all newly staged files;
- after commit, delete old/replaced/removed files.

If a fully atomic multi-setting workflow would make the service disproportionately complex, at minimum guarantee per-setting compensation and return a clear generic failure without deleting previous files.

## 11. Cache Coherence

`Setting::getValue()` uses per-key forever cache.

The service must clear `setting_<key>` after create/update/delete as appropriate so Custom writes do not leave stale values.

This should be tested because current direct Eloquent updates bypass `Setting::setValue()` cache invalidation.

## 12. Blade / UX

Preserve type-specific UI and previews.

Improve:

- `wire:confirm` for field deletion;
- loading/disabled state for add, delete, save;
- validation error messages for label/key/type/image/gallery inputs;
- responsive create-field grid (`col-span-12` fallbacks before md/lg spans);
- visible image/gallery upload size/type hint;
- privileged-content notice on HTML fields;
- disable mutation controls when current admin lacks `system.settings.update`, while server-side authorization remains authoritative;
- safe empty state remains.

Do not expose storage path editors or arbitrary destination controls.

## 13. Boundaries / Limits

Custom settings are intended as configuration, not an unbounded CMS table.

Implementation should avoid introducing pagination unless necessary, but add a conservative field-count guard to `addField()` if there is already a repository convention. If no convention exists, document the absence rather than inventing a business-breaking hard cap.

Gallery uploads should have an explicit per-request bound as described above.

## 14. Error Handling / Logging

Do not return raw exception messages to the browser.

Log safe metadata:

- actor/admin ID;
- operation (`create`, `delete`, `save`);
- setting ID/key where safe;
- setting type;
- number of new/removed files;
- exception class on failure.

Do not log HTML body contents, uploaded file bytes, session payloads or secrets.

## 15. Tests

Create focused test file, proposed:

`tests/Feature/System/SystemCustomSettingsTest.php`

Coverage:

1. `/admin/system/settings` route requires `system.settings.view`;
2. canonical Admin Menu entry uses `/admin/system/settings` and `system.settings.view`;
3. Custom uses `AuthorizesSystemActions`;
4. all mutation actions enforce `system.settings.update`;
5. view-only user cannot create/delete/save;
6. duplicate custom key rejected;
7. create always forces `group_name = custom`;
8. crafted ID cannot delete a setting from another group;
9. invalid/non-image upload rejected;
10. SVG rejected;
11. file > 5 MB rejected;
12. image replacement stores new before deleting old;
13. DB failure cleans newly staged image and preserves old file/value;
14. gallery upload list is validated/bounded;
15. gallery removals delete files only after successful persistence;
16. delete cleans owned custom/gallery files but never paths outside approved roots;
17. text/textarea/html values persist correctly;
18. Setting cache is invalidated after writes/deletes;
19. raw exception detail is not flashed to browser;
20. Blade includes delete confirmation, loading states and upload validation feedback.

High-impact filesystem tests should use Laravel fake public storage where possible.

## 16. Files Expected to Change

Application:

- `Modules/System/Livewire/Settings/Partials/Custom.php`
- `Modules/System/resources/views/livewire/settings/partials/custom.blade.php`
- `Modules/System/Services/CustomSettingsService.php` (new)
- `Modules/Admin/data/menus.json` (normalize existing `/admin/system/settings` entry)

Potentially minor/supporting changes only if needed:

- `Modules/System/Models/Setting.php` for a reusable cache-forget helper, only if doing so reduces duplicated cache internals without changing existing APIs.

Tests:

- `tests/Feature/System/SystemCustomSettingsTest.php` (new)

Documentation:

- `docs/modules/System/livewire/settings-partials-custom/ANALYSIS.md`
- `docs/modules/System/livewire/settings-partials-custom/REFACTOR_PLAN.md`

No database schema migration is planned.
No new route is planned.
No duplicate Admin Menu entry is planned.

## 17. Existing Installation Menu Update

Because an existing database may contain the legacy `/admin/system/settings` menu entry with `can = view_staff`, post-deploy guidance must include an idempotent targeted update:

- match `/admin/system/settings` (or the current entry name under `Công cụ Hệ thống`);
- set `can = system.settings.view`;
- preserve parent and active state;
- optionally normalize the display name to `Thiết lập Hệ thống`;
- do not reset/reseed unrelated menus.

## 18. Acceptance Criteria

- [ ] Custom remains a tab inside `/admin/system/settings`;
- [ ] no duplicate Custom route/menu is created;
- [ ] settings menu visibility uses `system.settings.view`;
- [ ] every Custom mutation enforces `system.settings.update`;
- [ ] Livewire contains no direct Setting create/destroy/update orchestration;
- [ ] Livewire contains no direct public-storage delete/store orchestration beyond temporary upload state;
- [ ] uploads are jpg/jpeg/png/webp only and max 5 MB per file;
- [ ] SVG is rejected;
- [ ] gallery upload count is bounded;
- [ ] replacement/removal ordering preserves old files until persistence succeeds;
- [ ] failed persistence cleans staged new files where feasible;
- [ ] delete cannot affect non-custom settings or files outside approved roots;
- [ ] `Setting::getValue()` cache remains coherent after writes;
- [ ] HTML remains privileged and its trust/render contract is documented;
- [ ] delete has confirmation and mutation buttons have loading/disabled states;
- [ ] browser errors are sanitized;
- [ ] focused tests pass;
- [ ] no destructive menu/database reset occurs.

## 19. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, implementation must not begin until the user explicitly approves this plan.
