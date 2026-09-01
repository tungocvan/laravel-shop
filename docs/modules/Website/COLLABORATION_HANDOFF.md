# Website Collaboration Handoff

## Current objective

Major/Clean Module Refactor for `Website`.

Website Batch 1 ownership cleanup was merged to `main`. The active follow-up is the approved persistence-safe consolidation of legacy `wp_settings` into canonical System-owned `settings`.

## Current branch

`refactor/website-system-settings-consolidation`

Base: `main` at branch creation.

## Canonical settings decision

The canonical persistence owner is `Modules\System` and the canonical table is `settings`.

Approved runtime rule after this follow-up:

- `Modules\System\Models\Setting` is the canonical model;
- `Modules\System\Services\SettingsService` reads/writes only `settings`;
- Website does not own an independent settings persistence table;
- `Modules\Website\Models\Setting` is retained only as a deprecated compatibility adapter extending the System model while callers transition;
- production runtime must not read or write `wp_settings` after consolidation;
- legacy cache aliases may still be invalidated temporarily to avoid stale values across deployments.

## Database proof used for this follow-up

The user supplied local database proof before implementation:

- `settings` exists with columns `id`, `key`, `value`, `group_name`, `type`, `label`, timestamps and contained 55 rows;
- `wp_settings` exists with the same column shape and contained 30 rows;
- 12 keys existed in both tables;
- 11 duplicate keys had matching values;
- one real value conflict existed: `header.topbar.help_url`, where canonical `settings` contained `/` and legacy `wp_settings` contained `/help`;
- migration ledger recorded both `-0001_11_30_000024_create_settings_table` and `-0001_11_30_000024_create_wp_settings_table` in batch 1.

Conflict policy approved: an existing canonical `settings` row wins. Legacy data may fill missing keys but must not overwrite canonical values.

## Implementation completed on the branch

### Canonical System runtime

`Modules/System/Services/SettingsService.php` was simplified so runtime reads/writes only `settings`.

Removed runtime compatibility behavior includes:

- special `home_*` routing to `wp_settings`;
- `homepage` group reads from `wp_settings`;
- fallback reads from `settings` to `wp_settings`;
- writes of homepage keys into `wp_settings`;
- `isLegacyHomepageKey()`.

Legacy cache aliases `wp_opt_*` and `setting_*` are still invalidated during the transition; they are not persistence reads/writes.

### Website compatibility model

`Modules/Website/Models/Setting.php` no longer declares `wp_settings` as its table and no longer implements a second settings persistence API. It is now a deprecated compatibility adapter extending `Modules\System\Models\Setting`.

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

`wp_settings` remains a legacy safety copy after this PR. Dropping it is explicitly outside this follow-up and requires later production caller/data proof plus separate approval.

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
- the consolidation migration remains additive and non-destructive.

## Branch scope review

Compared with `main`, this branch is intentionally limited to five runtime/test files before this handoff update:

1. `Modules/System/Services/SettingsService.php`
2. `Modules/Website/Models/Setting.php`
3. `Modules/Website/database/migrations/2026_09_01_000001_consolidate_wp_settings_into_settings.php`
4. `tests/Feature/System/CanonicalSettingsServiceTest.php`
5. `tests/Feature/Website/WebsiteSettingsConsolidationContractTest.php`

No cart/checkout/payment/affiliate/promotion refactor is included.

## Safety boundaries

Do not in this follow-up:

- drop/rename/truncate `wp_settings`;
- rewrite historical migration ledger entries;
- recreate the already deployed `settings` table;
- overwrite canonical conflicts from legacy values;
- broaden into cart/checkout/payment/affiliate ownership cleanup.

The source repository may continue to contain the string `wp_settings` in historical migrations, the consolidation migration, regression tests and documentation. Acceptance is that production runtime application code no longer reads/writes the legacy table.

## Verification gate

Implementation is complete enough for the single grouped local verification cycle requested by the user.

Run focused verification after switching/pulling this branch:

- canonical System settings regression;
- Website settings consolidation contract;
- Website Feature regression;
- migration on the existing local database;
- post-migration proof that canonical values win and legacy table remains intact.

Do not run the full project test suite by default.

## Current status

- Website Batch 1: MERGED.
- Settings persistence architecture: APPROVED.
- Database schema/data/ledger proof: COMPLETE.
- Canonical conflict policy: APPROVED (`settings` wins).
- System runtime consolidation: IMPLEMENTED.
- Website compatibility adapter: IMPLEMENTED.
- Additive legacy data migration: IMPLEMENTED.
- Regression guards: IMPLEMENTED.
- Destructive legacy-table removal: DEFERRED / NOT AUTHORIZED.
- Local grouped verification: PENDING.
- PR creation: PENDING verification.
