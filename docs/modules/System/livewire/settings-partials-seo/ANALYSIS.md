# System Livewire Analysis — Settings/Partials/Seo

Analysis date: 2026-08-12

Status: **Refactored and awaiting local test verification.**

## Current State

`Settings/Partials/Seo` has been refactored into a thin authorized Livewire UI backed by `Modules/System/Services/SeoSettingsService.php`.

Implemented controls:

- `system.settings.update` enforced on save through `AuthorizesSystemActions`;
- fixed allowlist of `seo_title`, `seo_description`, `social_facebook`, `social_zalo`, `header_script`;
- DB transaction for all fixed SEO settings;
- `seo_description` normalized to plain text and preview rendered escaped;
- `header_script` retained as intentional trusted production code for backward compatibility;
- explicit high-risk UI warning and save confirmation;
- bounded `header_script` input;
- audit metadata logs only hash/length/change state, never script body;
- both `setting_<key>` and Website `wp_opt_<key>` cache namespaces invalidated after save;
- generic browser errors with detailed exception class retained server-side;
- read-only UI for admins without update permission.

## Public Website Contract

Website still intentionally renders `header_script` raw in `Modules/Website/resources/views/layouts/frontend.blade.php`. This remains a trusted-admin production-code feature, not sanitized untrusted HTML.

The System SEO service now invalidates Website's `wp_opt_header_script` cache after save, so frontend changes are visible without stale cache.

## SEO Description Contract

`seo_description` is now plain text. Rich-editor/raw-preview behavior was removed because HTML has no useful role in a meta description and created an admin-side stored-XSS surface.

## Admin Menu

SEO remains a tab inside `/admin/system/settings`. No duplicate route/menu was created. The canonical settings menu remains protected by `system.settings.view`.

## Verification

Focused test:

`tests/Feature/System/SystemSeoSettingsTest.php`

Run locally:

```bash
php artisan test tests/Feature/System/SystemSeoSettingsTest.php
```

## Remaining Platform-Level Considerations

Future hardening may introduce a dedicated permission for public script injection and CSP/nonces. Those are broader platform concerns and are outside this component refactor.
