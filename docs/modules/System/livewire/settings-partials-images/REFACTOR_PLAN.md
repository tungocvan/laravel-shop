# Settings/Partials/Images Refactor Plan

Status: **Implemented 2026-08-12.**

Implemented: `system.settings.update` on save/remove; canonical `SettingsService`; safe store → persist → old-file cleanup ordering with new-file compensation; persist-null → old-file removal ordering; PNG/JPG logo policy (SVG removed); read-only/validation/confirmation/loading UX; focused contract test. Existing Settings page/tab/menu retained. No migration, route, permission or Admin Menu item added.
