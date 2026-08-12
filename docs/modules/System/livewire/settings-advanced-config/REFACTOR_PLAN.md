# Settings/AdvancedConfig Refactor Plan

Status: **Implemented 2026-08-12.**

Implemented: action-level `system.env.update`; write-only bridge secret; queue/URL validation; `AdvancedConfigService`; safe bounded Node health checks; 15-attempt queue polling cap; read-only/loading/confirmation UX; focused test. Existing `/admin/system/settings/env` + `system.env.view` route/menu retained. No migration, route, permission or Admin Menu item added.
