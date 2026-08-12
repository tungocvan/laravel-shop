# Settings/Partials/General Refactor Plan

Status: **Implemented 2026-08-12.**

Implemented: `system.settings.update`; canonical `SettingsService` with exact General key allowlist and transaction boundary; trimmed scalar normalization; hotline/address validation feedback; read-only and safe failure UX; preserved `site-name-updated`; focused contract test. Existing Settings page/tab/menu retained. No migration, route, permission or Admin Menu item added.
