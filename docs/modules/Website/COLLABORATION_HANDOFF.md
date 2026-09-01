# Website Collaboration Handoff

## Current objective

Major/Clean Module Refactor for `Website`.

Website Batch 1 ownership cleanup and the persistence-safe consolidation of legacy `wp_settings` into canonical System-owned `settings` are both merged to `main`.

## Completed persistence consolidation

PR `#119` — `refactor(settings): consolidate Website settings into System persistence` — was merged to `main`.

Merge commit: `a95aa215634262d06dc71144469ca9e16b00492b`.

Canonical ownership is now:

- `Modules\System\Models\Setting` is the canonical model;
- `Modules\System\Services\SettingsService` reads/writes only `settings`;
- Website does not own an independent settings persistence table;
- `Modules\Website\Models\Setting` remains only as a deprecated compatibility adapter extending the System model;
- production runtime no longer reads or writes `wp_settings` through the known System/Website settings paths;
- legacy cache aliases may still be invalidated temporarily to avoid stale values across deployments.

## Database and migration proof

Before implementation, the user supplied database proof showing:

- canonical `settings`: 55 rows;
- legacy `wp_settings`: 30 rows;
- 12 duplicate keys;
- 11 duplicate values matched;
- one real conflict existed at `header.topbar.help_url` (`settings=/`, `wp_settings=/help`).

The approved conflict rule was: canonical `settings` wins and legacy rows may only fill missing keys.

Migration `2026_09_01_000001_consolidate_wp_settings_into_settings` completed successfully and verified:

- canonical `settings`: `73` rows;
- legacy `wp_settings`: remains `30` rows;
- canonical `header.topbar.help_url`: `/`;
- legacy `header.topbar.help_url`: `/help`;
- legacy keys missing from canonical settings: `0`.

The migration is additive and non-destructive. It does not drop, rename or truncate `wp_settings`, does not rewrite migration history, and its `down()` is intentionally non-destructive.

## Runtime cleanup completed

The merged implementation removed:

- special `home_*` write routing to `wp_settings`;
- homepage-group reads from `wp_settings`;
- runtime fallback from `settings` to `wp_settings`;
- `isLegacyHomepageKey()`;
- the Website model's direct `wp_settings` table ownership.

Focused regression also exposed one remaining runtime caller, `Modules/Website/Services/HomepageBackfillService.php`; it was migrated to canonical `settings` and covered by regression tests before merge.

## Verification closeout

Focused regression before merge:

- `154 passed (16325 assertions)`;
- duration: `5.39s`.

Post-migration verification passed on the user's existing local database. No full-project regression was required for this focused persistence boundary.

## Legacy `wp_settings` retirement debt

The legacy table remains intentionally present as a safety copy. Its physical removal is a separate future task and is **not currently authorized**.

Before any drop migration is proposed, require all of the following proof:

1. Repository-wide caller proof that production runtime application code no longer reads/writes `wp_settings` outside historical/consolidation migrations, tests and documentation.
2. Production data proof that every required legacy key exists in canonical `settings` and no new legacy-only writes have appeared after deployment.
3. Deployment/observation period sufficient to rule out hidden scheduled jobs, queues, scripts or rarely reached admin flows using the legacy table.
4. Confirmation that no rollback procedure still depends on `wp_settings` as a safety copy.
5. Explicit user approval for a separate destructive removal branch/PR.

Until those conditions are met, classify `wp_settings` as `QUARANTINE / DEFERRED`, not `DEAD` and not safe to drop.

## Remaining Website refactor boundaries

The settings persistence follow-up must not be treated as authorization to broaden ownership cleanup into unrelated domains.

Still separate/deferred unless a new objective is explicitly approved:

- Cart / Checkout / payment / MoMo boundaries;
- Affiliate ownership;
- Coupon / FlashSale / promotion ownership;
- customer/account identity boundaries;
- Wishlist / Review / Newsletter / Tag ownership;
- any destructive schema cleanup requiring new caller/schema/authz proof.

## Current status

- Website Batch 1: MERGED.
- Settings persistence consolidation PR #119: MERGED.
- Canonical settings owner: `System` / `settings`.
- System runtime consolidation: COMPLETE.
- Website compatibility adapter: COMPLETE.
- Homepage direct legacy caller: CLEANED.
- Additive legacy data migration: COMPLETE AND VERIFIED.
- Focused regression: PASS — 154 tests / 16325 assertions.
- Post-migration data proof: PASS — `settings=73`, `wp_settings=30`, missing legacy keys `0`.
- `wp_settings` physical removal: DEFERRED / NOT AUTHORIZED.
- Next Website refactor objective: NOT DETERMINED.
