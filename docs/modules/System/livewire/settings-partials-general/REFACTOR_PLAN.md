# Settings/Partials/General Refactor Plan

Status: Approved for immediate implementation by user on 2026-08-12.

## Plan
- enforce `system.settings.update` on save;
- route reads/writes through a focused shared `SettingsService` with exact-key allowlist and transaction boundary;
- normalize scalar input by trimming while preserving nullable semantics;
- keep current validation and add hotline/address validation feedback;
- add read-only state and safe generic persistence failure feedback;
- preserve `site-name-updated` and existing Settings page/tab/menu architecture;
- add focused `SystemSettingsGeneralTest` contract coverage.

No migration, route, permission or Admin Menu item is planned.
