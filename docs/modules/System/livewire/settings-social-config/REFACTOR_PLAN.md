# Settings/SocialConfig Refactor Plan

Status: **Implemented 2026-08-12.**

Implemented: `system.env.update` on save; Google/Facebook/TinyMCE credentials use write-only replacement semantics; existing `SocialConfigService` is now canonical validation/persistence orchestration; exact env key allowlist; OAuth redirect/client and GA4 validation; configured/not-configured hints; read-only/loading/confirmation UX; focused test. Existing ENV route/menu retained. No migration, route, permission or Admin Menu item added.
