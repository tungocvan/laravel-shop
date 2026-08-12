# Settings/Partials/Seo Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/Partials/Seo.php`, its Blade UI, fixed-key SEO/settings persistence service, cross-cache invalidation required by Website frontend consumption, focused tests, and verification of the existing `/admin/system/settings` Admin Menu entry.

Status: **Awaiting explicit approval before implementation.**

## 1. Goal

Refactor the SEO settings partial so it becomes a thin, authorized Livewire UI over a fixed-key service, removes raw HTML execution from the admin SEO preview, treats `header_script` as an explicit trusted-code production control, keeps public Website behavior compatible, and guarantees cache coherence between System writes and Website reads.

This is a major refactor, not a feature rebuild.

Existing fields remain supported:

- `seo_title`
- `seo_description`
- `social_facebook`
- `social_zalo`
- `header_script`

## 2. Route and Admin Menu

`Seo` is not a standalone page. It is the `seo` tab within `Settings/SettingForm` at:

```text
GET /admin/system/settings
name: admin.system.settings.index
middleware: auth:admin + permission:system.settings.view,admin
```

No new route or duplicate Admin Menu item should be created.

The previous Custom refactor has already normalized the canonical menu entry to:

```text
Name: Thiết lập Hệ thống
URL: /admin/system/settings
Can: system.settings.view
Active: true
```

This refactor should verify and preserve that entry rather than modify it again.

For existing installations that have not yet applied the prior targeted menu update, post-deploy guidance may repeat the same idempotent normalization, but implementation must not reset/reseed the full menu table.

## 3. Authorization

Page/menu visibility remains:

`system.settings.view`

Every SEO mutation must enforce:

`system.settings.update`

using `AuthorizesSystemActions` at the Livewire action boundary.

No new System permission is proposed in this refactor.

### Header-script privilege note

`header_script` is more sensitive than ordinary SEO metadata because it is rendered raw in the public Website `<head>`. Website's own settings capability is `website.settings.manage`, but the System settings page already owns this field and System must not acquire a new hard dependency on the Website domain module merely to save its own settings tab.

For this refactor:

- require `system.settings.update`;
- clearly label `header_script` as trusted production code;
- require explicit confirmation before save;
- structured-audit the change without logging script contents;
- keep the public rendering contract unchanged for backward compatibility.

A future permission-architecture pass may split this into a stronger dedicated capability if desired.

## 4. Confirmed Public Rendering Boundary

The Website frontend currently receives:

`headerScript = Website\Services\SettingsService::get('header_script', '')`

and renders it in `Modules/Website/resources/views/layouts/frontend.blade.php` using raw Blade output:

```blade
{!! $headerScript !!}
```

Therefore `header_script` is intentionally executable site-wide head markup/script and must be treated as trusted code, not normal text.

The refactor must not claim this field is sanitized or safe for untrusted authors.

## 5. SEO Description Policy

`seo_description` should be treated as **plain SEO text**, not executable/rich HTML.

Current Blade uses `x-editor` and raw `{!! !!}` in the Google preview. That is unnecessary and creates an admin-side stored-XSS surface.

Implementation should:

- replace the rich editor with a textarea/plain-text control;
- render preview using escaped `{{ }}` output only;
- normalize newly saved description to plain text (`strip_tags` + sensible whitespace normalization);
- preserve normal Unicode/Vietnamese text;
- document that HTML is no longer part of the SEO-description contract.

This is an intentional security/semantic correction: HTML tags have no useful role in a meta-description value.

Existing stored HTML may remain until the operator next saves this form; on save it will be normalized to plain text.

## 6. New SEO Settings Service

Create a focused service, proposed:

`Modules/System/Services/SeoSettingsService.php`

The service owns a fixed allowlist of keys and never accepts arbitrary setting keys from browser state.

Allowed keys:

```text
seo_title
seo_description
social_facebook
social_zalo
header_script
```

Responsibilities:

### Read

- load only approved keys;
- provide empty-string/default values consistently;
- calculate a non-secret hash/fingerprint for the current `header_script` if needed for change detection/audit;
- do not expose any unrelated setting rows.

### Save

- accept only the fixed approved structure;
- normalize title/description/social values;
- persist all five keys in one DB transaction where feasible;
- force an appropriate settings group (proposed `seo`) server-side;
- preserve `header_script` verbatim except newline normalization/size bound so intentional analytics/pixel markup is not broken;
- invalidate all relevant cache keys after commit;
- log safe actor/change metadata.

Livewire should no longer loop through arbitrary `$settings` keys and call `Setting::setValue()` directly.

## 7. Cache Coherence Across System and Website

This is a required part of the refactor.

System's `Setting::getValue()` uses:

```text
setting_<key>
```

Website's `SettingsService::get()` uses:

```text
wp_opt_<key>
```

Since `header_script` is read by Website but currently written through System, successful SEO writes must invalidate **both** cache namespaces for every affected key:

```text
setting_<key>
wp_opt_<key>
```

Do not make System depend on `Modules\Website\Services\SettingsService` directly. Instead, the SEO service may clear the known compatibility cache keys centrally, or a shared cache-invalidation helper can be introduced only if it is demonstrably useful without widening scope.

Focused tests must lock this behavior.

## 8. Validation / Normalization

Proposed Livewire validation:

- `seo_title`: nullable|string|max:255
- `seo_description`: nullable|string|max:1000
- `social_facebook`: nullable|url|max:2048
- `social_zalo`: nullable|string|max:255
- `header_script`: nullable|string|max:50000

The larger bound for `header_script` supports legitimate analytics/pixel snippets while preventing unbounded payloads.

Service-side defensive normalization should still reject unexpected key/value structures.

### Social Zalo

Do not force URL-only semantics because the current UI explicitly permits a phone number or OA link. Preserve that behavior with a bounded string.

## 9. Header Script Safety Contract

Do **not** sanitize `header_script` with an HTML purifier in this task because doing so could silently break legitimate analytics/pixel code and contradict the current intentional feature.

Instead:

- trusted administrators only (`system.settings.update`);
- visible high-risk warning;
- explicit save confirmation mentioning site-wide script execution;
- bounded size;
- no preview/execution of the script inside Admin;
- structured logging of actor ID, changed/not-changed, old/new SHA-256 fingerprints and byte lengths only;
- never log script body;
- sanitized browser errors;
- preserve raw Website rendering for compatibility.

CSP/nonces and a future script-snippet allowlist are broader platform hardening topics, not required to complete this component refactor.

## 10. Livewire Responsibility

Keep in Livewire:

- fixed UI state;
- validation;
- authorization;
- service delegation;
- `canUpdate` UI capability state;
- safe success/error notification.

Move out of Livewire:

- direct `Setting::getValue()` loop;
- direct `Setting::setValue()` loop;
- persistence grouping;
- cache invalidation;
- script-change audit metadata;
- description normalization.

## 11. Blade / UX

Preserve current section structure but improve:

- replace SEO description rich editor with plain textarea;
- escaped Google preview (`{{ }}` only);
- inline validation messages for every field;
- character counters/guidance for title and description where practical;
- strong warning panel above `header_script` explaining that code executes on public pages;
- no live/rendered preview of `header_script`;
- `wire:confirm` on save because the form contains executable public head code;
- loading/disabled save state;
- disable all mutation inputs/button for users without `system.settings.update` while retaining server-side authorization;
- preserve read-only visibility for `system.settings.view` users.

## 12. Error Handling / Logging

Do not expose raw exception messages to browser.

Log safe metadata:

- actor/admin ID;
- operation `seo.settings.save`;
- changed keys;
- whether header script changed;
- old/new header-script SHA-256 fingerprints;
- old/new byte lengths;
- exception class on failure.

Do not log:

- header script contents;
- SEO description/body contents;
- request/session payloads;
- secrets.

## 13. Data Integrity

Persist fixed SEO settings in one database transaction.

After a successful commit:

- invalidate `setting_<key>` cache keys;
- invalidate `wp_opt_<key>` cache keys.

If DB persistence fails:

- no cache invalidation is required before rollback completion;
- browser receives generic error;
- server log records safe failure metadata.

No filesystem operations are involved, so this workflow can be materially more atomic than Custom settings.

## 14. Tests

Create focused test file, proposed:

`tests/Feature/System/SystemSeoSettingsTest.php`

Coverage:

1. `/admin/system/settings` route requires `system.settings.view`;
2. canonical Admin Menu entry remains `/admin/system/settings` + `system.settings.view`;
3. Seo uses `AuthorizesSystemActions`;
4. save enforces `system.settings.update`;
5. view-only principal cannot save;
6. service has a fixed allowlist and ignores/rejects arbitrary browser keys;
7. all five approved fields persist correctly;
8. SEO description HTML is normalized to plain text;
9. admin Google preview contains no raw `{!! seo_description !!}` output;
10. `header_script` is persisted verbatim within the approved size bound;
11. header-script body is not rendered/executed in the Admin preview;
12. Website public layout still has its intentional trusted raw-render contract;
13. both `setting_<key>` and `wp_opt_<key>` caches are invalidated after successful save;
14. DB failure does not leave partial fixed-key updates;
15. Facebook URL validation works;
16. Zalo retains phone-or-link string compatibility;
17. `header_script` size bound is enforced;
18. save UI includes high-risk warning + confirmation + loading state;
19. read-only UI disables mutation controls;
20. browser errors do not expose raw exception text;
21. audit logging uses hashes/lengths and does not log script contents.

Tests must not execute stored `header_script` JavaScript.

## 15. Files Expected to Change

Application:

- `Modules/System/Livewire/Settings/Partials/Seo.php`
- `Modules/System/resources/views/livewire/settings/partials/seo.blade.php`
- `Modules/System/Services/SeoSettingsService.php` (new)

Potentially no change required:

- `Modules/Admin/data/menus.json` because it has already been normalized by the Custom refactor;
- `Modules/System/routes/web.php`;
- `Modules/Website/resources/views/layouts/frontend.blade.php` — public raw header-script behavior remains intentional for compatibility;
- `Modules/Website/Services/SettingsService.php` — its cache namespace is accounted for by System invalidation.

Tests:

- `tests/Feature/System/SystemSeoSettingsTest.php` (new)

Documentation:

- `docs/modules/System/livewire/settings-partials-seo/ANALYSIS.md`
- `docs/modules/System/livewire/settings-partials-seo/REFACTOR_PLAN.md`

No database migration is planned.
No new route is planned.
No duplicate Admin Menu item is planned.
No new permission is planned.

## 16. Existing Installation Menu Note

If the prior Custom deployment update has already been applied, no menu database change is needed for SEO.

If not, ensure the existing `/admin/system/settings` entry under `Công cụ Hệ thống` is normalized idempotently to:

```text
name: Thiết lập Hệ thống
url: /admin/system/settings
can: system.settings.view
is_active: true
```

Do not reset or reseed unrelated menu rows.

## 17. Acceptance Criteria

- [ ] Seo remains a tab inside `/admin/system/settings`;
- [ ] no duplicate SEO route/menu is created;
- [ ] settings page/menu visibility remains `system.settings.view`;
- [ ] save enforces `system.settings.update`;
- [ ] Livewire no longer directly reads/writes Setting models;
- [ ] persistence uses a fixed-key service and DB transaction;
- [ ] SEO description is plain text and admin preview is escaped;
- [ ] no raw SEO-description Blade output remains;
- [ ] header script remains intentional trusted public code, not silently sanitized;
- [ ] header-script save has clear warning + confirmation;
- [ ] header-script payload is bounded;
- [ ] header-script body is never logged;
- [ ] both System and Website cache namespaces are invalidated after save;
- [ ] browser errors are sanitized;
- [ ] view-only users see read-only UI;
- [ ] focused tests pass;
- [ ] no destructive menu/database reset occurs.

## 18. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, implementation must not begin until the user explicitly approves this plan.
