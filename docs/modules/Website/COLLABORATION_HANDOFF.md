# Website Collaboration Handoff

## Current objective

Major/Clean Module Refactor for `Website`.

Website Batch 1 ownership cleanup was merged to `main`. This follow-up consolidates legacy `wp_settings` into canonical System-owned `settings` without destructive legacy-table removal.

## Current branch

`refactor/website-system-settings-consolidation`

Base: `main` at branch creation.

## Canonical settings decision

The canonical persistence owner is `Modules\System` and the canonical table is `settings`.

Runtime rule after this follow-up:

- `Modules\System\Models\Setting` is the canonical model;
- `Modules\System\Services\SettingsService` reads/writes only `settings`;
- Website does not own an independent settings persistence table;
- `Modules\Website\Models\Setting` is retained only as a deprecated compatibility adapter extending the System model while callers transition;
- production runtime must not read or write `wp_settings` after consolidation;
- legacy cache aliases may still be invalidated temporarily to avoid stale values across deployments.

## Database proof used for this follow-up

The user supplied local database proof before implementation:

- `settings` existed with columns `id`, `key`, `value`, `group_name`, `type`, `label`, timestamps and contained 55 rows;
- `wp_settings` existed with the same column shape and contained 30 rows;
- 12 keys existed in both tables;
- 11 duplicate keys had matching values;
- one real value conflict existed: `header.topbar.help_url`, where canonical `settings` contained `/` and legacy `wp_settings` contained `/help`;
- migration ledger recorded both `-0001_11_30_000024_create_settings_table` and `-0001_11_30_000024_create_wp_settings_table` in batch 1.

Conflict policy: an existing canonical `settings` row wins. Legacy data may fill missing keys but must not overwrite canonical values.

## Implementation completed

### Canonical System runtime

`Modules/System/Services/SettingsService.php` now reads/writes only `settings`.

Removed runtime compatibility behavior includes:

- special `home_*` routing to `wp_settings`;
- `homepage` group reads from `wp_settings`;
- fallback reads from `settings` to `wp_settings`;
- writes of homepage keys into `wp_settings`;
- `isLegacyHomepageKey()`.

Legacy cache aliases `wp_opt_*` and `setting_*` are still invalidated during the transition; they are not persistence reads/writes.

### Website compatibility model

`Modules/Website/Models/Setting.php` no longer declares `wp_settings` as its table and no longer implements a second settings persistence API. It is a deprecated compatibility adapter extending `Modules\System\Models\Setting`.

### Homepage legacy caller cleanup

Focused regression exposed one remaining production caller: `Modules/Website/Services/HomepageBackfillService.php` still read `wp_settings` directly.

That service now reads canonical `settings`, and its test fixtures were updated to exercise the canonical table. This closes the known Website homepage runtime dependency on `wp_settings`.

### Additive data consolidation

Added migration:

`Modules/Website/database/migrations/2026_09_01_000001_consolidate_wp_settings_into_settings.php`

Behavior:

- exits safely if either table is unavailable;
- iterates legacy rows in bounded chunks;
- copies only keys missing from `settings`;
- preserves value/group/type/label/timestamps for copied rows;
- never overwrites an existing canonical key;
- never drops, renames or truncates `wp_settings`;
- `down()` is intentionally non-destructive because copied canonical rows may receive later production writes.

`wp_settings` remains a legacy safety copy after this PR. Dropping it is outside this follow-up and requires later production caller/data proof plus separate approval.

## Regression guards

Updated `tests/Feature/System/CanonicalSettingsServiceTest.php` to prove:

- System reads/writes canonical `settings`;
- a legacy `home_*` row in `wp_settings` is not a runtime fallback;
- homepage group reads do not source `wp_settings`;
- a new homepage write lands in `settings` while the legacy row remains unchanged;
- Website's compatibility Setting model delegates to System ownership.

Added `tests/Feature/Website/WebsiteSettingsConsolidationContractTest.php` to prove:

- Website Setting delegates to the System model;
- Website model contains no `wp_settings` persistence declaration;
- System SettingsService contains no `DB::table('wp_settings')` and no `isLegacyHomepageKey` runtime routing;
- HomepageBackfillService does not read `wp_settings`;
- the consolidation migration remains additive and non-destructive.

Updated Website homepage/settings tests so canonical `settings` is the expected persistence contract and new writes do not target `wp_settings`.

## Safety boundaries

This follow-up does not:

- drop/rename/truncate `wp_settings`;
- rewrite historical migration ledger entries;
- recreate the already deployed `settings` table;
- overwrite canonical conflicts from legacy values;
- broaden into cart/checkout/payment/affiliate/promotion ownership cleanup.

The source repository may continue to contain the string `wp_settings` in historical migrations, the consolidation migration, regression tests and documentation. Acceptance is that production runtime application code no longer reads/writes the legacy table.

## Verification results

Focused grouped regression completed successfully:

- `154 passed (16325 assertions)`;
- duration: `5.39s`;
- no full-project regression was run or required for this focused persistence boundary.

The consolidation migration was then executed successfully on the user's existing local database:

- `2026_09_01_000001_consolidate_wp_settings_into_settings`: DONE;
- canonical `settings`: `73` rows after migration;
- legacy `wp_settings`: remains `30` rows;
- canonical `header.topbar.help_url`: `/`;
- legacy `header.topbar.help_url`: `/help`;
- legacy keys still missing from canonical settings: `0`.

This proves all 18 previously legacy-only rows were copied, canonical duplicate/conflict values were preserved, and the legacy safety table remained intact.

## Current status

- Website Batch 1: MERGED.
- Settings persistence architecture: APPROVED.
- Database schema/data/ledger proof: COMPLETE.
- Canonical conflict policy: VERIFIED (`settings` wins).
- System runtime consolidation: IMPLEMENTED.
- Website compatibility adapter: IMPLEMENTED.
- Homepage direct legacy caller: CLEANED.
- Additive legacy data migration: IMPLEMENTED AND VERIFIED.
- Regression guards: IMPLEMENTED AND PASSING.
- Focused local regression: PASS — 154 tests / 16325 assertions.
- Post-migration data proof: PASS — `settings=73`, `wp_settings=30`, missing legacy keys `0`.
- Destructive legacy-table removal: DEFERRED / NOT AUTHORIZED.
- Persistence follow-up: READY FOR PR REVIEW.
