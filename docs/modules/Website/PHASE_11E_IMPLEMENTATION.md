# Phase 11E — Homepage Layout Themes

## Status

Implemented on `refactor/website-homepage-phase-11`.

## Goal

Provide reusable Homepage layout themes with the same admin workflow used by Header/Footer while keeping business content outside theme snapshots.

## Persistence

Themes are stored in:

```text
homepage.layout_themes
```

Maximum: 20 themes.

## Theme schema

```php
[
    'version' => 1,
    'name' => 'Commerce Classic',
    'layout' => [
        'section_order' => [...],
        'visibility' => [...],
        'section_types' => [...],
    ],
    'presentation' => [...],
    'updated_at' => 'ISO-8601',
]
```

Theme snapshots contain only builder layout metadata and presentation.

They must not contain category IDs, product IDs, Promo Banner content, Newsletter content, Trust Badge content, Hero Banner records, Flash Sale records or other business content.

## Admin actions

Homepage Theme Manager supports:

- Save new
- Apply to Builder
- Update selected theme
- Rename
- Delete
- Export JSON
- Import JSON

Apply remains preview-first. Applying a theme changes only current Livewire Builder state. The storefront changes only after `Lưu thay đổi` publishes the Homepage Builder state.

## Import/export schema

Export envelope:

```json
{
  "schema": "flexbiz.homepage-layout-theme",
  "version": 1,
  "theme": {}
}
```

Import validates schema, version, allowed top-level theme keys, allowed layout keys, Registry-resolvable sections, visibility values and Presentation tokens before persistence.

Unknown theme keys are rejected instead of silently accepting business content.

## Files

- `Modules/Website/Services/HomepageLayoutThemeService.php`
- `Modules/Website/Livewire/Admin/Home/Concerns/ManagesHomepageLayoutThemes.php`
- `Modules/Website/resources/views/livewire/admin/home/partials/layout-themes.blade.php`
- `Modules/Website/resources/views/livewire/admin/home/home-settings-v3.blade.php`
- `tests/Feature/Website/WebsiteHomepageLayoutThemeConfigurationTest.php`

## UI standard

Theme name, theme selector and JSON textarea follow `ADMIN_UI_INPUT_STANDARD.md`.

## Targeted validation

```bash
php artisan test \
  tests/Feature/Website/WebsiteHomepageLayoutThemeConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepagePresentationConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepageBuilderStateConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepageSectionAdminUiConfigurationTest.php
```

Then:

```bash
php artisan test tests/Feature/Website/WebsiteHomepage*
```

No full-project test suite is required for this phase.
