# Phase 10F — Footer Brand & Layout Themes

## Scope

Phase 10F extends the Footer Builder with two independent capabilities:

1. a Footer-specific Brand logo with safe fallback to the global Website logo;
2. multiple named Footer layout themes that snapshot only layout + presentation.

The phase intentionally does not include Footer content in themes.

---

## Footer Brand Logo

New setting:

```text
footer.brand_logo
```

Runtime resolution:

```text
footer.brand_logo
    ↓ missing / empty
site_logo
    ↓ missing / empty
Brand initial fallback
```

Admin location:

```text
/admin/footer-settings
→ Thông tin & Brand
```

The existing `FooterInfo` Livewire component now supports image upload using `WithFileUploads`.

Validation:

```text
image
jpg / jpeg / png / webp
max 3 MB
```

Uploaded files are stored under:

```text
storage/app/public/footer/brand/
```

When replacing or removing a Footer-specific logo, deletion is restricted to paths beginning with:

```text
footer/brand/
```

This prevents the Footer editor from deleting the shared `site_logo` fallback.

Removing the Footer logo does not copy or mutate `site_logo`; it simply clears `footer.brand_logo` and restores runtime fallback behavior.

---

## Footer Layout Themes

New setting:

```text
footer.layout_themes
```

A theme snapshot contains only:

```php
[
    'version' => 1,
    'name' => 'Shop Footer Standard',
    'layout' => [...],
    'presentation' => [...],
    'updated_at' => '...',
]
```

A theme explicitly does not contain:

```text
Footer columns / links
Social links
Contact information
Copyright content
Brand logo
App Store / Play Store URLs
Blade view paths
```

This separation ensures changing a Footer theme cannot overwrite business/content data.

---

## Theme Actions

The Builder exposes:

```text
Save as new theme
Apply / load theme
Update selected theme
Rename selected theme
Delete selected theme
```

Maximum saved themes in v1:

```text
20
```

Theme names are limited to 2–60 characters.

---

## Preview-first Apply Flow

Applying a theme is intentionally non-destructive:

```text
Select theme
    ↓
Apply / load
    ↓
Builder state changes only
    ↓
Responsive Preview
    ↓
Admin reviews Desktop / Mobile
    ↓
Save layout
    ↓
footer.layout + footer.presentation published
```

`applyTheme()` does not persist `footer.layout` or `footer.presentation`.

Publishing remains exclusively in `saveBuilder()`.

---

## Admin UI Input Standard

Phase 10F established the approved visual baseline for editable Website admin controls after UI validation of the Footer information form.

Canonical documentation:

```text
docs/modules/Website/ADMIN_UI_INPUT_STANDARD.md
```

Future Website admin work should follow that standard for:

```text
input / select / textarea
field labels
checkboxes / toggles
file upload controls
repeatable collection rows
empty states
section cards
focus / hover states
validation messages
sticky primary save actions
responsive form grids
```

The key requirement is that editable controls must be visually distinguishable from static text. The current reference implementation is:

```text
Modules/Website/resources/views/livewire/admin/footer/footer-info.blade.php
```

Any intentional deviation from the shared standard should be documented in the relevant phase implementation notes.

---

## Security and Schema Safety

Theme layouts are sanitized through `FooterComponentRegistry` before they are saved or applied.

Persisted layout items remain limited to:

```text
type
enabled
config
```

A saved theme cannot inject a Blade renderer path.

Presentation values continue through `FooterPresentationService`, preserving the Phase 10B bounds and fallback behavior.

All persistent Footer theme and logo mutations require:

```text
website.footer.manage
```

---

## Files

```text
Modules/Website/Livewire/Admin/Footer/FooterInfo.php
Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php
Modules/Website/Providers/WebsiteServiceProvider.php
Modules/Website/resources/views/livewire/admin/footer/footer-info.blade.php
Modules/Website/resources/views/livewire/admin/footer/partials/theme-manager.blade.php
Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php
docs/modules/Website/ADMIN_UI_INPUT_STANDARD.md
tests/Feature/Website/WebsiteFooterBrandThemeConfigurationTest.php
```

---

## Validation

Focused tests:

```bash
php artisan test \
  tests/Feature/Website/WebsiteFooterBrandThemeConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterBuilderInteractionTest.php \
  tests/Feature/Website/WebsiteFooterBuilderConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterSchemaConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterPresentationConfigurationTest.php
```

Then run the Website-only gate:

```bash
php artisan test tests/Feature/Website
```

Do not run the full project test suite for this phase.

---

## UI Validation

### Brand

1. Open `/admin/footer-settings` → `Thông tin & Brand`.
2. Confirm the Website logo is shown as fallback when no Footer logo exists.
3. Upload a Footer logo and save.
4. Reload Admin and storefront; confirm Footer uses the new logo.
5. Remove the Footer logo; confirm storefront returns to `site_logo`.

### Admin Input UI

1. Confirm every editable input/textarea has a visible boundary before focus.
2. Confirm hover/focus states make the active field obvious.
3. Confirm labels remain visible and are not replaced by placeholders.
4. Confirm repeatable Legal Link / Trust Badge items are presented as distinct editable cards.
5. Confirm empty repeatable collections show a clear empty state and creation action.
6. Confirm the primary Footer save action remains easy to reach on long forms.

### Themes

1. Open `/admin/footer-settings` → `Bố cục Footer`.
2. Change layout/presentation.
3. Save as a new named theme.
4. Make a different Builder change.
5. Apply the saved theme and verify Responsive Preview restores the snapshot before publishing.
6. Reload the storefront before pressing `Lưu bố cục`; it must remain unchanged.
7. Press `Lưu bố cục`; storefront must then use the restored theme.
8. Test update, rename and delete actions.

---

## Out of Scope

Phase 10F does not add:

```text
Header themes
Whole-site theme packages
Theme file import/export
Database migrations for theme entities
Footer content snapshots
Chat widget layout configuration
```

A future phase may promote layout themes into a reusable Website-wide theme package system if required.
