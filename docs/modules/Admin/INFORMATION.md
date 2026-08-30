# Admin Module Information

## Purpose

`Modules/Admin` is the repository's Admin **shell** module. It owns authenticated Admin presentation/composition concerns such as the dashboard entry point, Admin navigation, shell layout, header, sidebar, footer, theme/design presentation, and shell profile/preferences.

It should not be the canonical owner of business domains such as Product, Order, Post, Category, Chat, affiliate/marketing, customer/account data, or production database administration.

## Registration

- Module path: `Modules/Admin`
- Manifest: `Modules/Admin/config/module.php`
- Type: `shell`
- Source default: enabled
- Declared dependencies: `Auth`, `User`, `Role`
- Registered through the first-party `Modules/ModuleServiceProvider`
- View namespaces: `Admin::` and `admin::`
- Livewire alias prefix: `admin.`

## Features

Current active shell features:

- Admin dashboard entry
- Admin menu/navigation management
- Admin profile
- Admin layout/settings hub
- Layout sections: general, header, sidebar, footer, design/theme, navigation
- Admin header configuration/presentation
- Admin shell presentation services and contract-tested layout behavior

Legacy/non-canonical feature code still physically present includes affiliate, banner, chat, customer, flash sale, product, order, post, category, role/staff, settings, database administration and related models/services/views/import/export code. Presence in the module does not prove active routing.

## Routes

All active web routes are under `web`, `auth:admin`, `/admin`, and route-name prefix `admin.`.

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | `admin.dashboard.view` |
| GET | `/admin/menus` | `admin.menus.index` | `admin.menu.view` |
| GET | `/admin/menus/create` | `admin.menus.create` | `admin.menu.create` |
| GET | `/admin/menus/{id}/edit` | `admin.menus.edit` | `admin.menu.update` |
| GET | `/admin/profile` | `admin.profile` | `admin.profile.view` |
| GET | `/admin/themes` | `admin.themes` | `admin.layout.view`; redirects to `admin.layout.design` |
| GET | `/admin/layout` | `admin.layout` | `admin.layout.view` |
| GET | `/admin/layout/general` | `admin.layout.general` | `admin.layout.view` |
| GET | `/admin/layout/header` | `admin.layout.header` | `admin.layout.view` |
| GET | `/admin/layout/sidebar` | `admin.layout.sidebar` | `admin.layout.view` |
| GET | `/admin/layout/footer` | `admin.layout.footer` | `admin.layout.view` |
| GET | `/admin/layout/design` | `admin.layout.design` | `admin.layout.view` |
| GET | `/admin/layout/navigation` | `admin.layout.navigation` | `admin.layout.view` |
| GET | `/admin/admin-header` | `admin.header` | `admin.header.view` |

`Modules/Admin/routes/api.php` is intentionally empty. There is no current Admin API route.

## Permissions

Manifest permissions:

- `view_admin`
- `create_admin`
- `edit_admin`
- `delete_admin`
- `admin.dashboard.view`
- `admin.menu.view`
- `admin.menu.create`
- `admin.menu.update`
- `admin.menu.delete`
- `admin.menu.restore`
- `admin.menu.import`
- `admin.menu.export`
- `admin.profile.view`
- `admin.profile.update`
- `admin.theme.view`
- `admin.theme.update`
- `admin.layout.view`
- `admin.layout.update`
- `admin.header.view`
- `admin.header.update`

The generic `view/create/edit/delete_admin` permissions are legacy-compatible manifest entries; active shell routes use the capability-specific permissions above.

## Controllers

Active route controllers:

- `Modules/Admin/Http/Controllers/DashboardController.php`
- `Modules/Admin/Http/Controllers/MenuController.php`
- `Modules/Admin/Http/Controllers/ProfileController.php`
- `Modules/Admin/Http/Controllers/AdminController.php`

Other controllers remain physically present for legacy/domain concerns including Affiliate, Auth, Banner, Category, Chat, Coupon, Customer, Database, EnvConfig, FlashSale, Footer, Header, HomeSettings, Order, Post, ProductCommission, Product, Role, Setting, and Staff.

## Livewire Components

Active/relevant shell areas include:

- `Livewire/Menus/*`
- `Livewire/Profile/*`
- `Livewire/Header/*`
- `Livewire/Footer/*`
- `Livewire/Partials/*`
- layout/design/theme shell components

The module also contains legacy Livewire directories for Affiliate, Auth, Banner, Categories, Chat, Customers, Database, FlashSale, Marketing, Orders, Posts, Products, Settings, System and others. Their current reachability must be verified before migration or deletion.

`Livewire/Database/TableList.php` is intentionally disabled: all database actions abort with HTTP 403 and render no table data.

## Blade Views

Canonical shell layout:

- `Modules/Admin/resources/views/layouts/master.blade.php`

Active page families include:

- dashboard
- menus
- profile
- admin layout/settings sections
- admin header

Livewire views follow `Modules/Admin/resources/views/livewire/...`.

Historical/legacy domain page and Livewire view trees remain and require caller verification before cleanup.

## Services

Shell-aligned services include:

- `AdminDesignService`
- `AdminFooterService`
- `AdminHeaderActionService`
- `AdminHeaderService`
- `AdminHeaderUserMenuService`
- `AdminLayoutDashboardService`
- `AdminShellPresentationService`
- `AdminThemeProfileService`
- `MenuService`
- `MenuImportExportService`
- profile/sidebar/header-menu related services

Legacy/domain services still include address, affiliate, banner, chat, flash sale and similar behavior.

High-risk legacy service:

- `Modules/Admin/Services/DatabaseService.php` — backup/restore/truncate/drop/full restore logic. It is **not safe for browser reactivation** and currently remains protected by the disabled UI/route boundary.

## Imports / Exports

- Admin menu import/export uses a dedicated recursive JSON-tree workflow through `MenuImportExportService`.
- Domain-oriented imports/exports under Admin are legacy ownership debt and should move to canonical modules when still required.
- Flat spreadsheet workflows should reuse `Modules/Shared/Services/ImportExport` when applicable.

## Models

Current model directory includes:

- `Admin`
- `AdminMenu`
- `AffiliateScheme`
- `Banner`
- `ChatMessage`
- `ChatSession`
- `FlashSale`
- `FlashSaleItem`
- `HeaderMenu`
- `HeaderMenuItem`
- `ModuleRouteTitle`
- `UserAddress`
- additional legacy models where present

`AdminMenu` is the current shell navigation model. It uses soft deletes, parent/children relationships, casts for status/order and automatic menu-cache invalidation.

Many other models represent non-shell concepts and require canonical ownership review.

## Database Tables

Exact table ownership is intentionally not restated as authoritative for all legacy models because production use and migration ledger have not been verified in this analysis.

Known shell/domain persistence includes Admin menu/navigation and shell configuration tables plus legacy business tables owned by models/migrations under Admin.

Before moving schema ownership, verify:

- `Modules/Admin/database/migrations`
- production `migrations` ledger
- current callers
- corresponding canonical module migrations/models

Do not rename applied migrations merely to normalize filenames.

## Relationships

Representative shell relationship:

- `AdminMenu.parent` -> `AdminMenu`
- `AdminMenu.children` -> `AdminMenu`

Legacy models include relationships to users and other domain entities. Those cross-module relationships are part of the ownership cleanup scope, not evidence that Admin should remain their canonical owner.

## Shared / Cross-Module Dependencies

Expected:

- `Auth`
- `User`
- `Role`
- Spatie Permission
- Laravel cache/storage/database
- shared import/export infrastructure where format-compatible

Legacy dependencies to Website/App/domain models should be migrated toward explicit canonical-owner contracts.

## Events / Jobs

`Modules/Admin/Events` and `Modules/Admin/Jobs` directories exist. No active Admin route contract depends on a newly introduced event/job in this documentation refresh. Reachability of legacy events/jobs should be verified as part of ownership mapping before cleanup.

## Configuration / Environment Variables

Primary module configuration:

- `Modules/Admin/config/module.php`

Admin shell configuration is also represented by shell services/settings/state used by layout/header/footer/design features.

`DatabaseService` reads database connection configuration and must not expose credentials through process arguments/logging in any future production-capable implementation.

## Tests

`tests/Feature/Admin` now has substantial shell contract coverage, including tests for content workspace, design, footer, general layout, header actions/configuration/presentation/settings/user menu, layout/settings hub, sidebar, route configuration, and menu-related behavior.

For this `/analyze Admin` documentation-only task:

- Focused application tests: **NOT APPLICABLE — documentation-only**
- Admin regression: **NOT APPLICABLE — documentation-only**
- Manual UI smoke: **NOT APPLICABLE — documentation-only**

## Known Limitations

- Admin still physically contains many legacy domain/system components.
- Reachability of every legacy component is not fully cataloged.
- Production migration-ledger/table usage is not verified.
- `DatabaseService` remains dangerous source code and must stay unreachable.
- Historical Admin plan/spec docs contain older assumptions and should not override current source.

## Maintenance Notes

- Treat current source/config/routes as source of truth.
- Preserve Admin as a shell.
- Reuse the existing shell layout and services; do not introduce a competing design system.
- Before deleting legacy code, prove it is unreachable through routes, Blade/Livewire aliases, jobs/events, service imports and tests.
- Move business ownership one domain at a time.
- Keep database administration under hardened System ownership rather than re-enabling the legacy Admin implementation.
- Current analysis recommendation: **Major Refactor**.