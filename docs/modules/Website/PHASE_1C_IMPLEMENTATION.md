# Website Phase 1C — Settings / Cache / XSS Implementation

## Status

- Phase: `1C — Settings / Cache / XSS`
- Implementation: `COMPLETE`
- Automated configuration gate: `PASS`
- Runtime DB-focused checks: `SKIPPED — PDO SQLite missing in current CLI`
- Regression: `PASS — 14 tests / 149 assertions; 2 runtime DB checks skipped`
- Manual admin smoke: `PASS — user verified`
- Approval: `APPROVED`
- Decision: `CLOSED`

The requested `PHASE_1C_ANALYSIS.md` was not present in the local workspace. The attached Phase 1C specification supplied by the user was therefore used as the locked implementation checklist.

## Implemented

- Standardized Website setting reads/writes through `SettingsService::get()`, `set()`, `updateMany()`, `getGroup()`, and `updateGroup()`.
- Standardized the canonical cache key to `wp_opt_{key}` while invalidating both `wp_opt_{key}` and legacy `setting_{key}` after mutations.
- Made bulk updates transactional and removed the `dd()`/false-success path. Exceptions now roll back and propagate to the UI/runtime error handler.
- Migrated homepage admin setting reads and mutations away from direct `Setting` model access.
- Migrated frontend homepage Livewire setting reads and `MarketingService` to `SettingsService`.
- Added view composition for site name, favicon, and trusted header script. Frontend Blade now renders provided data and does not query `Setting`.
- Kept `header_script` as privileged trusted configuration, rendered raw for Analytics/GTM compatibility, with mutation guarded by `website.settings.manage`.
- Added explicit image MIME/extension/size validation for homepage promo and banners.
- Changed replacement flow to store the new image, persist successfully, then delete the old image. Failed persistence removes the new orphan and preserves the old file/reference.
- Preserved Phase 1A payment behavior and Phase 1B method-level authorization.

## Automated Evidence

```text
WebsiteCheckoutConfigurationTest: PASS — 5 tests / 20 assertions
WebsiteAdminAuthorizationConfigurationTest: PASS — 4 tests / 88 assertions
WebsiteRouteConfigurationTest: PASS — 3 tests / 30 assertions
WebsiteSettingsConfigurationTest: 2 PASS / 11 assertions; 2 SKIPPED
Full tests/Feature/Website: 14 PASS / 149 assertions; 2 SKIPPED
```

The two skipped checks exercise real `wp_settings` persistence/cache invalidation and transaction rollback. The repository test configuration selects in-memory SQLite, but the current PHP CLI has no PDO SQLite driver (`could not find driver`). The checks remain in the suite and will run automatically where that driver is installed.

## Manual Test Evidence

- Website admin header settings page renders successfully after the single-root Livewire correction.
- User reported the Phase 1C admin test successful and approved continuation.
- Re-running the two DB-runtime checks with PDO SQLite remains an environment follow-up, not an observed application regression.

## Remaining / Deferred

- Legacy duplicate code under `Services/Services` remains recorded architectural debt and was not redesigned in Phase 1C.
- Seeder-only direct model writes remain bootstrap behavior; production Website mutation paths use `SettingsService`.
- No database redesign, ownership migration, or Phase 2 work was performed.

## Exit Decision

**PHASE 1C: IMPLEMENTED / TESTED / APPROVED / CLOSED**

Do not begin Phase 2 without a separately locked Phase 2 specification.
