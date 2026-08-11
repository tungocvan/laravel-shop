# Website Phase 1C — Settings / Cache / XSS Analysis

## Status

- Phase: `1C — Settings / Cache / XSS`
- Analysis: `COMPLETE`
- Implementation: `NOT STARTED`
- Tests: `NOT STARTED`
- Approval to implement: `PENDING`
- Previous phase: `1B — TESTED / APPROVED / CLOSED`

## Goal

Stabilize Website settings reads/writes, cache invalidation, raw-script handling, and upload safety before Phase 2 domain ownership work.

Phase 1C must not redesign the Website database, move domains, or rebuild admin UI.

## Current Settings Architecture

There are currently two overlapping settings access paths:

```text
Setting::getValue()/setValue()
    cache key: setting_{key}

SettingsService::get()/set()
    cache key: wp_opt_{key}
```

Both paths target the same table:

```text
wp_settings
```

`Setting::setValue()` and `SettingsService::set()` each attempt to clear both cache namespaces, but any direct `Setting::updateOrCreate()` bypasses both invalidation paths.

## Confirmed Findings

### F1C-01 — Dual settings cache namespace

`Modules/Website/Models/Setting.php` uses:

```text
setting_{key}
```

while `Modules/Website/Services/SettingsService.php` uses:

```text
wp_opt_{key}
```

This duplication increases the chance of stale values and makes correctness depend on every writer remembering to clear both namespaces.

**Target:** one canonical Website settings service and one cache-key policy.

### F1C-02 — Homepage bypasses canonical cache invalidation

`HomeSettings::save()` persists many homepage values via direct `Setting::updateOrCreate(...)` calls.

Examples include:

```text
home_show_*
home_category_ids
home_featured_ids
home_new_arrivals_count
home_best_sellers_count
home_blog_count
home_promo_banner
home_newsletter
home_trust_badges
```

These writes bypass `Setting::setValue()` and `SettingsService::set()`, so cached reads can remain stale.

**Target:** all persisted Website settings mutations must flow through the canonical settings service in Phase 1C.

### F1C-03 — SettingsService still contains fatal debug behavior

`SettingsService::updateMany()` catches exceptions and executes:

```php
dd($e->getMessage());
```

A normal persistence failure can therefore terminate the admin request and expose internal error text.

**Target:** remove `dd()`, rollback, log safely, and propagate/return a controlled failure contract.

### F1C-04 — Frontend Blade performs direct model/database access

`Modules/Website/resources/views/layouts/frontend.blade.php` imports `Modules\Website\Models\Setting` and calls:

```php
Setting::getValue('site_favicon')
Setting::getValue('header_script')
```

This couples presentation directly to persistence/cache behavior.

**Target:** frontend layout receives resolved Website configuration from a service/composer/controller/provider layer; Blade must not query the settings model directly.

### F1C-05 — `header_script` is rendered raw

Frontend layout renders:

```php
{!! Setting::getValue('header_script') !!}
```

This is intentionally raw HTML/script execution and therefore equivalent to privileged code injection into every frontend page.

This capability may be legitimate for analytics/pixels/custom integrations, but it cannot be treated as an ordinary text setting.

**Target security contract:**

- `header_script` remains disabled/empty by default when absent;
- only a privileged capability may mutate raw scripts;
- normal content/home/menu/footer editors must not gain this capability implicitly;
- render path must be explicit and documented as trusted raw configuration;
- do not pretend generic HTML escaping can preserve arbitrary script functionality;
- if a safer typed analytics configuration can replace raw script later, that belongs to Phase 3/5 design, not Phase 1C.

Suggested capability:

```text
website.settings.manage
```

If a dedicated script capability is later required, add it deliberately rather than silently expanding permissions.

### F1C-06 — Upload validation exists but lifecycle policy is incomplete

Homepage promo upload currently validates:

```text
image|max:3072
```

Banner uploads also validate image fields.

However Phase 1C must verify and document:

- allowed image MIME/types;
- maximum size;
- disk/path policy;
- old-file cleanup when replacing an image;
- delete cleanup where appropriate;
- no path supplied by browser is trusted directly.

Do not build a new media library in Phase 1C.

### F1C-07 — Setting model and SettingsService duplicate responsibilities

Both the model and service contain read/write/cache logic.

**Target:** keep Eloquent persistence behavior in the model, but application reads/writes/cache policy in `SettingsService` (or a renamed canonical Website settings service). `Setting::getValue()/setValue()` may remain temporarily for backward compatibility only if callers are migrated and tests protect behavior.

## Phase 1C Target Flow

### Read

```text
Controller / View Composer / Livewire
    -> SettingsService
    -> cache
    -> Setting model
    -> wp_settings
```

### Write

```text
Authorized Livewire/Admin action
    -> SettingsService
    -> transaction where needed
    -> Setting model
    -> cache invalidation
```

Blade must not query `Setting` directly.

## Cache Policy

Phase 1C should converge on one canonical cache-key function, for example:

```text
website.setting.{key}
```

Exact naming is implementation detail, but there must be one source of truth.

Requirements:

- every successful write invalidates the exact read key;
- batch writes invalidate all changed keys;
- failed transaction must not leave cache representing uncommitted data;
- direct model writes from Website settings UI are removed;
- compatibility reads can temporarily clear legacy keys during migration if needed.

## Raw Script Policy

`header_script` is trusted privileged content, not user-generated content.

Phase 1C implementation must:

- ensure only `website.settings.manage` can mutate it if a mutation surface exists;
- keep raw rendering isolated to one explicit place;
- add a clear code comment/documentation warning;
- prevent normal settings components from accepting arbitrary script keys through generic bulk input;
- test that unauthorized mutation is rejected before persistence.

Do not add a WYSIWYG/HTML sanitizer that breaks legitimate script and then claim the problem is solved. The correct control is capability + explicit trusted-field handling.

## Error Handling Policy

`SettingsService` must never use `dd()`, `dump()`, or expose raw exception messages to the browser.

Preferred behavior:

```text
transaction rollback
-> structured exception/logging
-> Livewire/controller converts to safe user-facing error
```

Phase 1C does not need a project-wide error framework; it only needs a safe local contract compatible with later standardization.

## Upload Safety Policy

For existing Website settings/banner uploads:

- validate server-side as images;
- use Laravel storage APIs;
- generate/store server-controlled paths;
- reject oversized/non-image uploads;
- replace old file only after new file is successfully stored;
- remove replaced orphan file where the current feature owns that file;
- preserve existing file if a replacement fails;
- do not delete shared/external URLs accidentally.

## Scope Checklist

### Settings service

- [ ] Choose one canonical Website settings service API.
- [ ] Choose one cache-key namespace.
- [ ] Remove `dd()` from settings persistence failure path.
- [ ] Add controlled error propagation/logging.
- [ ] Ensure set/updateMany invalidation is transaction-safe.
- [ ] Add helper for batch reads/writes only if needed by current callers.

### Callers

- [ ] Migrate Homepage settings writes away from direct `Setting::updateOrCreate()`.
- [ ] Migrate frontend layout reads away from direct `Setting::getValue()`.
- [ ] Review Header/Footer settings callers for canonical service usage.
- [ ] Review any remaining Website setting writer discovered during implementation.
- [ ] Do not refactor Product/Category queries in HomeSettings yet; that is Phase 2/4.

### Raw script

- [ ] Document `header_script` as trusted privileged raw config.
- [ ] Ensure mutation requires `website.settings.manage` where applicable.
- [ ] Ensure generic bulk settings writer cannot accidentally write unrestricted script keys.
- [ ] Keep raw render isolated and explicit.

### Uploads

- [ ] Verify homepage promo upload type/size validation.
- [ ] Verify banner image upload validation.
- [ ] Add safe replacement cleanup where missing and ownership is clear.
- [ ] Ensure failure keeps old image intact.

### Compatibility

- [ ] Preserve existing setting keys and `wp_settings` schema.
- [ ] Do not rename/drop settings keys in Phase 1C.
- [ ] Do not create Website CMS tables in Phase 1C.
- [ ] Preserve Phase 1A checkout/payment behavior.
- [ ] Preserve Phase 1B authorization behavior.

## Test Plan

### Settings cache tests

At minimum verify:

- read caches value;
- `set()` changes DB and subsequent read returns new value;
- batch update invalidates all changed keys;
- failed batch write rolls back and does not publish stale/new cache incorrectly;
- Homepage save path uses canonical service/invalidation.

### Error handling tests

- forced settings write failure does not execute `dd()`;
- failure is controlled and DB transaction rolls back.

### Raw script tests

- frontend can render an explicitly stored trusted `header_script` value;
- no raw script value renders when setting is absent;
- unauthorized admin cannot mutate raw script configuration;
- ordinary homepage/menu/footer permissions do not imply script-management permission.

### Upload tests

- valid image accepted;
- invalid/non-image rejected;
- oversized image rejected;
- successful replacement updates path and safely removes owned old file where implemented;
- failed replacement leaves previous value/file intact.

### Regression

Must remain green:

```text
WebsiteAdminAuthorizationConfigurationTest
WebsiteCheckoutConfigurationTest
WebsiteRouteConfigurationTest
all Tests/Feature/Website
```

Manual smoke after implementation:

```text
Frontend favicon/header layout: PASS
Homepage settings save + immediate frontend refresh: PASS
Header settings save + immediate frontend refresh: PASS
Footer settings save + immediate frontend refresh: PASS
Banner upload/replace: PASS
No unexpected 403 for Super Admin: PASS
```

## Out of Scope

Phase 1C must not:

- redesign `wp_settings` into new tables;
- build homepage section builder;
- move Product/Post/Order/User ownership;
- refactor direct Product/Category DB queries except where required for settings correctness;
- build a media library;
- redesign admin UI;
- rename legacy migrations.

## Exit Gate

Phase 1C may be marked `TESTED / APPROVED` only when:

1. one canonical settings cache/read/write path is in use for the touched Website settings surfaces;
2. direct Homepage settings writes no longer bypass invalidation;
3. frontend layout no longer queries the Setting model directly;
4. `SettingsService` contains no fatal debug behavior;
5. raw script mutation/render policy is explicit and permission-protected;
6. upload safety tests pass for touched upload flows;
7. previous Website regression tests remain green;
8. manual frontend/admin settings smoke passes;
9. user explicitly approves Phase 1C.

## Decision

**Recommended: IMPLEMENT Phase 1C using this checklist.**

This phase stabilizes the settings/security foundation only. Structured Website CMS/database redesign remains Phase 3.
