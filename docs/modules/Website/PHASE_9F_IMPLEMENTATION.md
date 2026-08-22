# Phase 9F — Header Content, Navigation & Themes

## Scope

Phase 9F brings the Header admin system to the same management standard established by Footer Phase 10F.

It covers:

1. Header-specific Brand logo upload with safe `site_logo` fallback;
2. source-owned menu locations instead of hardcoded Livewire arrays;
3. removal of database mutations from Header menu `render()`;
4. Header navigation drag/drop and hierarchy safety;
5. named Header layout themes/presets;
6. Website Admin UI Input Standard adoption.

Admin route remains:

```text
/admin/header-settings
```

---

## Header Brand Logo

New setting:

```text
header.brand_logo
```

Runtime resolution:

```text
header.brand_logo
    ↓ missing / empty
site_logo
    ↓ missing / empty
Header Brand Name
```

Upload validation:

```text
image
jpg / jpeg / png / webp
max 3 MB
```

Files are stored below:

```text
storage/app/public/header/brand/
```

Deletion is restricted to paths beginning with `header/brand/`, so removing a Header logo can never delete the shared `site_logo`.

---

## Menu Locations

Canonical menu locations are now defined in:

```text
Modules/Website/Config/header.php
→ menu_locations
```

`MenuManager` obtains locations through `HeaderMenuService::getAvailableLocations()`.

Livewire no longer owns a duplicated hardcoded location array.

The initial source-owned locations are:

```text
primary
mobile
account
admin
```

These are identifiers/configuration, not demo menu content.

---

## No Database Mutation During Render

Opening the Header menu admin must not create menu rows or demo items.

`MenuManager::render()` is read-only.

A missing `HeaderMenu` row is created only during an explicit save action through:

```text
HeaderMenuService::ensureMenu()
```

The previous account-menu fallback that could insert `Ứng dụng của tôi` during render is removed.

Demo content belongs to seeders, not UI rendering.

---

## Navigation Management

Header Navigation Admin supports:

```text
CRUD
active / hidden state
root / child hierarchy
reorder within the same parent
move between root and child groups
native drag/drop
```

Server-side drag/drop processing validates:

- menu ownership;
- target parent ownership;
- self-parent attempts;
- simple cyclic parent attempts;
- ordered IDs against the selected menu/parent group.

Admin uses `getMenuTreeForAdmin()` so inactive items remain visible and editable, while storefront continues to use the active-only `getMenuTreeByLocation()` contract.

The mobile account navigation no longer falls back to a hardcoded `/my-apps` item. Account navigation is managed through the `account` menu location.

---

## Header Layout Themes

New setting:

```text
header.layout_themes
```

Each theme stores only:

```php
[
    'version' => 1,
    'name' => 'Shop Header Standard',
    'layout' => [...],
    'presentation' => [...],
    'updated_at' => '...',
]
```

Themes do not contain:

```text
menu content
hotline
email
help / order tracking URLs
Brand Name
Brand Logo
header_script
Blade renderer paths
```

Theme actions:

```text
Save as new theme
Apply / load theme
Update selected theme
Rename selected theme
Delete selected theme
```

Maximum saved themes in v1: `20`.

---

## Preview-first Theme Apply

Applying a Header theme does not immediately publish storefront changes.

```text
Select Header theme
    ↓
Apply / load
    ↓
Builder state changes only
    ↓
Responsive Preview
    ↓
Review Desktop / Mobile
    ↓
Lưu bố cục
    ↓
header.layout + header.presentation published
```

`applyTheme()` does not persist `header.layout` or `header.presentation`.

---

## UI Input Standard

Header admin forms now follow:

```text
docs/modules/Website/ADMIN_UI_INPUT_STANDARD.md
```

The accepted baseline includes visible editable controls with:

```text
border border-gray-300
bg-white
px-3 py-2.5
shadow-sm
hover:border-gray-400
focus:border-blue-500
focus:ring-2 focus:ring-blue-100
```

Long forms use a visible primary save action. Header General Settings uses a sticky save bar. Repeatable/navigation items use bordered cards and explicit empty states.

---

## Files

Primary files changed:

```text
Modules/Website/Config/header.php
Modules/Website/Services/HeaderMenuService.php
Modules/Website/Livewire/Admin/Header/GeneralSettings.php
Modules/Website/Livewire/Admin/Header/MenuManager.php
Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php
Modules/Website/Providers/WebsiteServiceProvider.php
Modules/Website/resources/views/livewire/admin/header/general-settings.blade.php
Modules/Website/resources/views/livewire/admin/header/menu-manager.blade.php
Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php
Modules/Website/resources/views/livewire/admin/header/partials/theme-manager.blade.php
Modules/Website/resources/views/components/header/mobile-menu.blade.php
tests/Feature/Website/WebsiteHeaderPhase9FConfigurationTest.php
```

---

## Validation

Focused Phase 9F tests:

```bash
php artisan test \
  tests/Feature/Website/WebsiteHeaderPhase9FConfigurationTest.php \
  tests/Feature/Website/WebsiteHeaderBuilderInteractionTest.php \
  tests/Feature/Website/WebsiteHeaderBuilderConfigurationTest.php \
  tests/Feature/Website/WebsiteHeaderSchemaConfigurationTest.php \
  tests/Feature/Website/WebsiteSettingsConfigurationTest.php
```

Then run the Website-only gate:

```bash
php artisan test tests/Feature/Website
```

Do not run the full project test suite for this phase.

---

## UI Validation

### General / Brand

1. Open `/admin/header-settings` → `Thông tin chung`.
2. Verify editable inputs are visually obvious before focus.
3. Confirm `site_logo` is shown when no Header-specific logo exists.
4. Upload a Header logo and save.
5. Reload Admin and storefront; confirm Header uses the Header logo.
6. Remove Header logo; confirm fallback returns to `site_logo`.

### Navigation

1. Open `Cấu trúc Menu`.
2. Switch `primary`, `mobile`, `account`, and `admin` locations.
3. Confirm opening an empty location does not create database content.
4. Add root and child items.
5. Hide an item and confirm it remains visible in Admin but disappears from storefront.
6. Drag root items to reorder.
7. Drag a child to another parent.
8. Drag a child back to root.
9. Reload and verify persisted hierarchy/order.

### Themes

1. Open `Bố cục Header`.
2. Change layout/presentation.
3. Save a new Header theme.
4. Make another Builder change.
5. Apply the saved theme and verify Responsive Preview restores it.
6. Confirm storefront remains unchanged until `Lưu bố cục` is pressed.
7. Test update, rename, and delete.
