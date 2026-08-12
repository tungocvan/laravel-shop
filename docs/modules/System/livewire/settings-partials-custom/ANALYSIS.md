# System Livewire Analysis — Settings/Partials/Custom

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/Partials/Custom.php` provides dynamic custom settings with text, textarea, HTML, image and gallery field types. It is **P1 / Major Refactor** because it performs model creation/deletion/updates and public file storage directly in Livewire, has no `system.settings.update` authorization, does not validate uploaded image/gallery files before storage, and supports HTML content without a clearly documented sanitization/rendering policy.

## Component Purpose

Path: `Modules/System/Livewire/Settings/Partials/Custom.php`

Alias: `system.settings.partials.custom`

View: `System::livewire.settings.partials.custom`

Responsibilities:

- list custom `Setting` records;
- add dynamic settings fields;
- delete fields;
- edit text/textarea/HTML values;
- upload image and gallery files;
- persist all custom values.

## Dependency Flow

`SettingForm`
→ `Custom`
→ `Modules\System\Models\Setting`
→ `settings` table

and

`Custom`
→ public Storage disk
→ `storage/settings/custom` / `storage/settings/gallery`

## Livewire PHP Analysis

`loadSettings()` runs `Setting::where('group_name', 'custom')->get()` and populates public arrays.

`addField()` validates label/key/type and directly creates a `Setting` model.

`deleteField()` directly destroys a record by ID.

`save()` iterates all settings and directly performs:

- old-file deletion;
- new image storage;
- gallery upload storage;
- JSON encoding;
- model updates.

There is no service layer or transaction around the multi-setting/multi-file save workflow.

No explicit capability authorization is present.

## Livewire Blade Analysis

The Blade provides creation controls, dynamic rendering per type, `x-editor` for HTML fields, public image previews, gallery removal and a global Save action.

Important UI findings:

- delete-field action has no confirmation;
- image/gallery upload inputs do not show validation errors;
- save has no loading/disabled state;
- grid uses fixed 12-column spans without responsive fallbacks on the create-field row;
- empty state exists.

## State / Validation / Actions

Actions:

- `addField()`
- `deleteField()`
- `removeGalleryImage()`
- `save()`

Only `addField()` validates its input. `save()` does not validate `dynamicImages` or `galleryUploads` before writing them to public storage.

This is a major correctness/security gap for uploads.

## Authorization

**P1:** every mutation should enforce `system.settings.update` at the action boundary.

The parent settings page uses view-level access; child Livewire components must not assume parent visibility equals mutation permission.

## Service / Model Dependencies

The component bypasses a service layer and owns persistence/file workflows directly. This conflicts with the repository module standard, where Livewire should own UI state/validation and delegate business/persistence workflows to services.

The `Setting` model is also shared with settings data whose migration ownership is outside System, so service-level ownership would make that boundary clearer.

## Performance

`loadSettings()` loads all custom settings with unbounded `get()`. This may be acceptable while the configuration set is intentionally small, but the component allows operators to create arbitrary additional fields, so the collection can grow without a hard bound.

Every `save()` loops over every custom setting and may perform multiple file/model writes.

## Security / Data Integrity

### P1 — Missing mutation authorization

All create/delete/save actions lack `system.settings.update` checks.

### P1 — Unvalidated public uploads

`dynamicImages` and `galleryUploads` are stored without image/mime/size validation. Files are written to the public disk.

### P1 — Non-atomic multi-resource save

Database updates and file operations are interleaved without a transaction/compensation strategy. Partial failures can leave settings and files out of sync.

### P1 — HTML content policy unclear

HTML fields are accepted through `x-editor` and persisted directly. Whether this is safe depends on every renderer that later outputs those settings. A sanitization/trusted-admin policy must be explicit and tested.

### P1 — Deleting a field does not clean associated files

`deleteField()` calls `Setting::destroy($id)` without inspecting/removing image/gallery files referenced by that setting. This can orphan public files.

## UI/UX Compliance

Positive:

- clear type-specific controls;
- empty state;
- existing image/gallery previews.

Needs improvement:

- confirmation for delete;
- upload validation errors;
- loading/disabled save/delete actions;
- responsive create-field layout;
- explicit HTML trust warning/sanitization semantics.

## Test Coverage

No System-specific test was found.

Critical missing tests:

- unauthorized mutations rejected;
- invalid/non-image upload rejected;
- upload size limits;
- HTML storage/render policy;
- file cleanup on delete/replacement;
- partial failure rollback/compensation;
- duplicate custom key behavior;
- large custom settings collection behavior.

## Issue List

### P1 — Missing `system.settings.update` authorization

### P1 — Arbitrary/unvalidated files can be stored on public disk

### P1 — Business/persistence logic lives directly in Livewire

### P1 — Multi-file/multi-model save is not atomic or compensating

### P1 — HTML trust/sanitization contract is undocumented

### P2 — Delete lacks confirmation and may orphan files

## Recommended Direction

**Major Refactor.** Introduce a System settings service responsible for validated custom-field CRUD, upload policy, file cleanup and transactional/compensating writes. Keep Livewire focused on state and UX. Do not rebuild the feature unless the dynamic-setting requirement itself is being removed.

## Open Questions / Unknowns

- Where custom HTML settings are rendered and whether they are escaped or output raw.
- Whether SVG or other active-content formats are intended for custom images.
- Expected maximum number of custom fields/gallery items.
- Whether deleting a custom field should permanently delete associated public files.
