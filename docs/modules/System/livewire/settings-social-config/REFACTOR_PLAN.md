# Settings/SocialConfig Refactor Plan

Status: Approved for immediate implementation by user on 2026-08-12.

## Scope
Refactor `Settings/SocialConfig` while retaining the existing ENV settings page/menu architecture.

## Plan
- enforce `system.env.update` on save;
- never hydrate existing Google/Facebook client secrets into public Livewire state; blank replacements preserve current secrets;
- treat TinyMCE API key as a credential and use replacement-secret semantics as well;
- reconcile the existing `SocialConfigService`: make it the focused validation/persistence orchestration layer instead of bypassing it;
- whitelist exact supported ENV keys;
- validate Google client ID format, numeric Facebook app ID, redirect HTTP(S) URLs, credential length and GA4 ID when present;
- keep explicit empty semantics for non-secret optional fields while secrets require replacement values to change;
- use canonical `EnvManagerService` for persistence and generic safe errors;
- add read-only/loading/validation/confirmation UX and configured/not-configured hints without revealing secrets;
- retain `/admin/system/settings/env` + `system.env.view`; no duplicate Admin Menu item;
- add `SystemSocialConfigTest` covering authorization, secret preservation, validation, exact key allowlist and safe persistence behavior.

No migration, new route, new permission or new menu is planned.
