# Settings/Partials/Seo Livewire Refactor Plan

Plan date: 2026-08-12

Status: **Implemented — awaiting local test verification.**

Implemented scope:

- Added `Modules/System/Services/SeoSettingsService.php` with a fixed five-key allowlist.
- Enforced `system.settings.update` at the Livewire save boundary.
- Moved persistence and cache invalidation out of Livewire.
- Converted `seo_description` to plain text and escaped preview rendering.
- Preserved `header_script` as trusted public code for compatibility, with warning, confirmation, size bound, and hash/length-only audit metadata.
- Invalidated both `setting_<key>` and Website `wp_opt_<key>` cache namespaces after successful save.
- Preserved `/admin/system/settings` and its existing `system.settings.view` menu/route boundary; no duplicate menu was created.
- Added `tests/Feature/System/SystemSeoSettingsTest.php`.
- Updated post-refactor analysis documentation.

Local verification command:

```bash
php artisan test tests/Feature/System/SystemSeoSettingsTest.php
```

The detailed approved design decisions are reflected in the resulting implementation and `ANALYSIS.md`.
