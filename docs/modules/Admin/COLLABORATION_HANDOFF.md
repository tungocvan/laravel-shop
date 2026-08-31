# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Website Header/Footer Legacy Runtime Cleanup**

Status: **IMPLEMENTED — FOCUSED VERIFICATION PASS / UI PASS / PR READY**

Branch/checkpoint: `refactor/admin-header-footer-legacy-runtime-cleanup`

This approved slice removes the historical Admin-owned Website Header/Footer presentation trees after caller-proof established the Website module as the active runtime owner. Canonical Admin shell Header/Footer configuration under `/admin/layout/header` and `/admin/layout/footer` remains unchanged.

## Ownership decision

- `Modules/Admin` remains the authenticated shell.
- `/admin/layout/header` and `/admin/layout/footer` remain canonical Admin shell layout configuration.
- Website owns public Website Header/Footer management through `/admin/header-settings` and `/admin/footer-settings`.
- Website Header/Footer controllers, wrapper views, Livewire components, services/models and permissions remain canonical.
- Deprecated Admin HeaderMenu/Banner compatibility model/service adapters remain outside this cleanup and are not claimed to be zero-caller.

## Runtime cleanup

Removed the historical Admin Header runtime tree:

- `Modules/Admin/Http/Controllers/HeaderController.php`
- `Modules/Admin/Livewire/Header/GeneralSettings.php`
- `Modules/Admin/Livewire/Header/HeaderSettingsHub.php`
- `Modules/Admin/Livewire/Header/MenuManager.php`
- `Modules/Admin/resources/views/pages/header/index.blade.php`
- `Modules/Admin/resources/views/livewire/header/general-settings.blade.php`
- `Modules/Admin/resources/views/livewire/header/header-settings-hub.blade.php`
- `Modules/Admin/resources/views/livewire/header/menu-manager.blade.php`
- `Modules/Admin/resources/views/livewire/header/partials/menu-item-row.blade.php`
- `Modules/Admin/resources/views/livewire/header/partials/menu-tree-manager.blade.php`

Removed the historical Admin Footer runtime tree:

- `Modules/Admin/Http/Controllers/FooterController.php`
- `Modules/Admin/Livewire/Footer/FooterInfo.php`
- `Modules/Admin/Livewire/Footer/FooterColumns.php`
- `Modules/Admin/Livewire/Footer/SocialLinks.php`
- `Modules/Admin/resources/views/pages/footer/index.blade.php`
- `Modules/Admin/resources/views/livewire/footer/footer-info.blade.php`
- `Modules/Admin/resources/views/livewire/footer/footer-columns.blade.php`
- `Modules/Admin/resources/views/livewire/footer/social-links.blade.php`

## Canonical runtime evidence

Header management remains:

`Website route /admin/header-settings -> Modules\Website\Http\Controllers\Admin\HeaderController -> Website::pages.admin.header.index -> website.admin.header.header-settings-hub`.

Footer management remains:

`Website route /admin/footer-settings -> Modules\Website\Http\Controllers\Admin\FooterController -> Website::pages.admin.footer.index -> website.admin.footer.footer-info / footer-columns / social-links / footer-settings-hub`.

The removed Admin Livewire components were already delegating Website presentation behavior to Website `HeaderMenuService`, `HeaderMenuItem`, `FooterService`, `FooterColumn`, `SocialLink` or shared System settings, which confirmed migration residue rather than canonical Admin business ownership.

## Guardrails

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` asserts:

- the complete historical Admin Header/Footer runtime trees stay absent;
- Website Header/Footer admin routes and permissions stay canonical;
- Website controllers render Website wrapper views;
- Website wrapper views mount Website Livewire components;
- `/admin/layout/header` and `/admin/layout/footer` remain canonical Admin shell configuration;
- deprecated Admin Banner/HeaderMenu compatibility classes remain compatibility-only;
- promotion/database quarantine families remain outside this slice.

## Schema and data decision

No schema, migration, foreign-key or production-data change is authorized or included.

## Verification

Focused verification reported PASS:

```text
AdminWebsitePresentationOwnershipContractTest: 6 passed, 77 assertions
AdminOwnershipBoundaryContractTest: 5 passed, 31 assertions
Total: 11 passed, 108 assertions
```

Route verification reported PASS:

```text
/admin/layout/header -> Modules\Admin\Http\Controllers\AdminController@layoutHeader
/admin/layout/footer -> Modules\Admin\Http\Controllers\AdminController@layoutFooter
/admin/header-settings -> Modules\Website\Http\Controllers\Admin\HeaderController
/admin/footer-settings -> Modules\Website\Http\Controllers\Admin\FooterController
```

Manual UI smoke: **PASS** for `/admin/layout/header`, `/admin/layout/footer`, `/admin/header-settings`, and `/admin/footer-settings`.

Working tree before documentation closeout: **clean** at implementation checkpoint `0fdb4ce7`.

## Acceptance criteria

- canonical Admin shell Header/Footer `/admin/layout/header|footer`: **PRESERVED**;
- canonical Website Header/Footer management `/admin/header-settings|footer-settings`: **PRESERVED**;
- historical Admin Header runtime tree: **REMOVED**;
- historical Admin Footer runtime tree: **REMOVED**;
- deprecated Admin HeaderMenu/Banner compatibility adapters: **PRESERVED**;
- schema/migration/data changes: **NONE**;
- focused regression: **PASS — 11 tests / 108 assertions**;
- route ownership verification: **PASS**;
- manual UI smoke: **PASS**.

## Remaining compatibility debt

Deprecated Admin `HeaderMenu`, `HeaderMenuItem`, `HeaderMenuService` and Banner compatibility adapters remain intentionally. Home settings residue, Flash Sale/Coupon/Affiliate/Order residue, environment/System adapters and Database quarantine remain separate scopes.

## Next phase

Open and merge this Website Header/Footer Legacy Runtime Cleanup as a focused PR. Do not start another compatibility-debt family until this branch is merged.

After merge, resume route -> controller -> view -> Livewire -> service/model caller proof for exactly one remaining legacy family before proposing another implementation scope.
