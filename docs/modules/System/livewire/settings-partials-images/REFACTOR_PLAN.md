# Settings/Partials/Images Refactor Plan

Status: Approved for immediate implementation by user on 2026-08-12.

## Plan
- enforce `system.settings.update` on save/remove;
- move file + Setting persistence to `SettingsService`/asset workflow;
- replacement order: store new → persist path → delete old; clean new file on persistence failure;
- removal order: persist null → delete old file;
- keep PNG/JPG logo policy and remove misleading SVG UI support rather than accepting unsanitized SVG;
- add read-only state, logo validation feedback, remove confirmation/loading and safe generic errors;
- retain existing Settings page/tab/menu architecture;
- add focused `SystemSettingsImagesTest` contract coverage.

No migration, route, permission or Admin Menu item is planned.
