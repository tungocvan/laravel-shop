# System Livewire Analysis — Settings/Partials/Images

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/Partials/Images.php` manages website logo and favicon uploads/removal. It is **P1 / Major Refactor** because mutations lack `system.settings.update`, file/database writes occur directly in Livewire without a service/transaction or compensation boundary, and old files are deleted before the new upload/persistence workflow is guaranteed to complete.

## Component Purpose

Path: `Modules/System/Livewire/Settings/Partials/Images.php`

Alias: `system.settings.partials.images`

View: `System::livewire.settings.partials.images`

Responsibilities:

- load current logo/favicon settings;
- validate replacement uploads;
- delete old files;
- store new files to the public disk;
- persist setting paths;
- remove current logo/favicon;
- dispatch UI refresh events.

## Dependency Flow

`SettingForm`
→ `Images`
→ `Modules\System\Models\Setting`
→ `settings` table

and

`Images`
→ public Storage disk
→ `storage/settings`

## Livewire PHP Analysis

The component uses `WithFileUploads`.

Validation rules:

- logo: `nullable|image|max:2048`
- favicon: `nullable|file|mimes:png,ico|max:1024`

`save()` deletes the current file before storing the replacement and persisting the new path.

`remove($type)` deletes the current file and immediately sets the corresponding setting to null.

No explicit authorization is present.

## Livewire Blade Analysis

The Blade provides previews, temporary upload previews, loading feedback, removal actions, and a loading-disabled Save button.

There is a UI/validation mismatch: the logo file input advertises SVG support (`image/svg+xml`), while the PHP `image` validation rule commonly does not accept SVG by default. This can confuse operators and should be aligned intentionally.

Remove actions do not require confirmation and do not show a loading/disabled state.

## State / Validation / Actions

Actions:

- `save()`
- `remove($type)`

Upload validation is materially better than `Custom`, but mutation authorization and write ordering remain weak.

## Authorization

**P1:** both save and remove must enforce `system.settings.update` at the Livewire action boundary.

## Service / Model Dependencies

The component directly coordinates public storage and `Setting` updates. Repository standards prefer this workflow in a service so file replacement/cleanup and persistence can be tested and compensated consistently.

## Performance

No material performance concern. Upload sizes are bounded to 2 MB/1 MB.

## Security / Data Integrity

### P1 — Missing mutation authorization

Upload/removal changes public website assets and requires explicit update capability.

### P1 — Destructive ordering can lose the previous asset

The old file is deleted before the new file is fully stored and the database setting is updated. If storage or DB update fails after deletion, the previous asset is already lost.

Recommended sequence: validate → store new file → persist new path → remove old file after successful persistence, with cleanup of the new file if persistence fails.

### P1 — Direct file/DB workflow in Livewire

Move asset replacement/removal to a service with deterministic cleanup/compensation.

### P2 — SVG UI mismatch

Blade says SVG is accepted for logo, while PHP validation does not clearly support it. If SVG is intentionally allowed, add an explicit SVG security policy; otherwise remove SVG from the accepted UI types.

## UI/UX Compliance

Positive:

- previews for current/new assets;
- loading status during upload;
- favicon error feedback;
- save loading/disabled state.

Needs improvement:

- logo validation error display;
- removal confirmation/loading state;
- consistent accepted-file messaging;
- failure feedback for storage/database errors.

## Test Coverage

No System-specific test was found.

Missing tests:

- unauthorized save/remove rejection;
- invalid MIME/oversize rejection;
- old asset preserved on failed replacement;
- new asset cleanup on DB failure;
- remove behavior;
- SVG policy.

## Issue List

### P1 — Missing `system.settings.update` authorization

### P1 — Old file deleted before replacement is safely committed

### P1 — File/database orchestration belongs in a service

### P2 — Blade advertises SVG while validation policy is inconsistent

## Recommended Direction

**Major Refactor of workflow, not UI rebuild.** Preserve the existing UX, move asset replacement/removal into a System settings asset service, authorize all mutations, and implement safe file replacement ordering/compensation.

## Open Questions / Unknowns

- Whether SVG logos are intentionally supported.
- Whether logo/favicon should remain on public disk or use a managed media abstraction shared with other modules.
