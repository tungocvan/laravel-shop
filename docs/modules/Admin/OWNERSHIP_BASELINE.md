# Admin Ownership & Reachability Baseline

## Purpose

This document is the implementation baseline for the Admin Major Refactor. Admin is the authenticated presentation/composition shell. Displaying a business feature inside the Admin UI does not make Admin the canonical owner of that feature's routes, models, services, permissions, or workflow.

## Canonical Admin Shell

The following surfaces are canonical Admin ownership and must be preserved unless a later approved architecture change explicitly replaces them:

- `/admin` dashboard entry and shell composition;
- `/admin/menus`, `/admin/menus/create`, `/admin/menus/{id}/edit` as the configuration workspace for Admin sidebar/navigation;
- `AdminMenu`, `MenuService`, and `MenuImportExportService` for Admin navigation metadata;
- Admin sidebar rendering and navigation composition;
- Admin header and header actions/user-menu presentation;
- Admin layout workspaces: general, header, sidebar, footer, design, and navigation;
- Admin theme/design presentation;
- Admin shell-owned profile/preferences;
- shell-specific presentation services and reusable Admin shell components.

A sidebar item may link to Product, Order, System, Account, Category, Chat, or another module. That link is navigation metadata only. The target module remains the canonical owner of its business behavior.

## Current Active Route Boundary

`Modules/Admin/routes/web.php` imports only the canonical shell controllers:

- `AdminController`
- `DashboardController`
- `MenuController`
- `ProfileController`

`Modules/Admin/routes/api.php` is intentionally empty. Admin APIs require an explicit authentication/authorization contract and tests before introduction.

## Classification Vocabulary

| State | Meaning | Allowed action |
|---|---|---|
| `KEEP` | Canonical Admin shell ownership | Preserve and improve in Admin |
| `MOVE` | Reachable behavior whose canonical owner is another module | Migrate caller/contract first, then remove Admin copy |
| `BOUNDARY MOVED` | Canonical runtime now uses the target module, but legacy Admin copies still require final reachability proof | Keep compatibility until dedicated cleanup is proven safe |
| `CLEANED` | Canonical replacement is active and the proven Admin runtime duplicate was removed | Keep ownership guardrails green |
| `DEPRECATE` | Replacement is proven but compatibility remains | Preserve compatibility until separately removed |
| `DEAD` | No reachable route/caller/alias/job/event/test and no production dependency | Remove in a focused cleanup slice |
| `UNKNOWN` | Reachability or production/schema state is not proven | Do not delete or move yet |
| `QUARANTINE` | Dangerous legacy capability that must stay unreachable | Redesign only in a separately approved owner |

## Ownership Families

| Admin family | Current state | Canonical owner / direction | Notes |
|---|---|---|---|
| Dashboard shell | `KEEP` | Admin | Shell entry/composition |
| Sidebar/navigation/menu management | `KEEP` | Admin | Includes `/admin/menus`, `AdminMenu`, menu services and shell rendering |
| Layout/header/sidebar/footer/design/theme | `KEEP` | Admin | Admin shell presentation only |
| Admin profile/preferences | `KEEP` | Admin UI + canonical identity/account contracts as applicable | Persistence ownership must be verified before moving models |
| Categories legacy runtime | `CLEANED` | Category | Canonical routes/controller/Livewire/views are in `Modules/Category`; proven Admin runtime duplicates removed |
| Chat legacy runtime | `CLEANED` | Chat | Canonical Chat runtime is active; proven legacy Admin controller/Livewire/models/service/views removed |
| Product / ProductCommission legacy | `UNKNOWN -> MOVE candidate` | Product | Verify callers and replacement completeness |
| Orders legacy | `UNKNOWN -> MOVE candidate` | Order | Verify callers and compatibility |
| Posts/content legacy | `UNKNOWN -> MOVE candidate` | canonical content/Post owner | Verify current module contract before choosing owner |
| Customer/address legacy | `UNKNOWN -> MOVE candidate` | Account/User/Identity according to current contract | Do not guess schema owner |
| Roles/staff legacy | `UNKNOWN -> MOVE candidate` | Role/User/Account according to responsibility | Admin may present screens without owning identity/authorization domain |
| Banner/public website header/footer/home settings | `UNKNOWN -> MOVE candidate` | Website/content owner | Distinguish public-site presentation from Admin shell header/footer |
| Affiliate/flash sale/coupon/marketing legacy | `UNKNOWN -> MOVE candidate` | canonical promotion/domain owner | Verify actual module inventory and callers first |
| Environment/system settings legacy | `UNKNOWN -> MOVE candidate` | System or dedicated configuration owner | Requires explicit operational boundary |
| Database administration | `QUARANTINE` | future hardened System database-operation boundary | Must remain unreachable in Admin |
| Historical scaffold/resource methods | `UNKNOWN -> DEAD candidate` | none | Remove only after caller proof |

## Category Cleanup Evidence

`Modules/Category` is the canonical owner of the Category admin workspace. The active `admin.category.*` routes, controller, Livewire components and views are Category-owned; proven Admin runtime duplicates were removed without schema/migration changes.

`tests/Feature/Admin/AdminCategoryOwnershipCleanupContractTest.php` prevents those runtime copies from returning.

## Chat Cleanup Evidence

`Modules/Chat` is the canonical owner of Chat runtime behavior while Admin remains its authenticated presentation shell.

Preserved contracts:

- `admin.chat.index` -> `/admin/chat/internal-chat`;
- `admin.chat.cskh` -> `/admin/chat`;
- both routes resolve to `Modules\\Chat\\Http\\Controllers\\ChatController`;
- both routes remain under `auth:admin` and require `permission:view_chat,admin`;
- capability-specific Livewire permissions remain enforced;
- canonical realtime channel/event behavior is unchanged;
- Chat continues to depend on Admin presentation because Chat pages extend `Admin::layouts.master`.

After the canonical boundary was verified, the following obsolete Admin runtime copies were removed in the dedicated compatibility cleanup slice:

- `Modules/Admin/Http/Controllers/ChatController.php`;
- `Modules/Admin/Livewire/Chat/ChatManager.php`;
- `Modules/Admin/Models/ChatSession.php`;
- `Modules/Admin/Models/ChatMessage.php`;
- `Modules/Admin/Services/ChatService.php`;
- `Modules/Admin/resources/views/pages/chat/index.blade.php`;
- `Modules/Admin/resources/views/livewire/chat/chat-manager.blade.php`.

`tests/Feature/Admin/AdminChatOwnershipBoundaryContractTest.php` now prevents those legacy copies from returning while protecting canonical Chat files, routes, authorization, and retained `deleteAllMessages()` compatibility behavior.

No Chat schema/table/migration ownership is changed by this runtime cleanup.

## Reachability Proof Required Before Future MOVE / DEPRECATE / DEAD

A file or family may not be removed merely because it is absent from `Modules/Admin/routes/web.php`. Each future domain slice must check, where applicable:

1. web/API routes and route providers;
2. Livewire aliases and class discovery/registration;
3. Blade tags, includes and route links;
4. controller/service/model imports across modules;
5. jobs, events, listeners, commands and scheduled tasks;
6. tests and factories/seeders;
7. menu/navigation records or config using historical URLs;
8. production table usage and migration ledger when schema ownership is involved;
9. external clients or compatibility requirements for historical URLs/aliases.

## P0 Database Administration Quarantine

`Modules/Admin/Services/DatabaseService.php` is a latent destructive capability and is not part of the canonical Admin shell.

Current containment contract:

- no active Admin web route references database administration;
- Admin API routes remain empty;
- `Modules/Admin/Livewire/Database/TableList.php` remains fail closed;
- the Livewire component does not instantiate or reference `DatabaseService`.

The containment tests do not make `DatabaseService` production-safe.

## Guardrails

- Admin manifest remains type `shell` with declared Auth/User/Role dependencies.
- Active Admin route controller imports remain limited to canonical shell controllers.
- `/admin/menus` and `admin.menu.*` remain canonical sidebar/navigation configuration.
- Admin API remains closed by default.
- Admin database administration remains fail-closed and quarantined.
- Category business runtime remains owned by `Modules/Category`.
- Chat business runtime remains owned by `Modules/Chat`.
- Legacy Admin Chat controller/Livewire/models/service/views must remain absent.
- Admin Chat routes require `view_chat`; mutating Livewire actions require capability-specific permissions.

## Schema / Migration Rule

Runtime ownership cleanup does not authorize moving or renaming applied migrations or production tables. Chat table/migration ownership is intentionally unchanged in this slice.

## Planned Refactor Sequence

1. keep ownership/P0 containment guardrails green;
2. keep Category cleanup green;
3. keep Chat canonical ownership and cleanup guardrails green;
4. choose one next legacy family with a verified canonical replacement;
5. prove callers/reachability and compatibility;
6. migrate/remove only the proven obsolete Admin runtime copy;
7. reconcile schema/migration ownership only after runtime ownership is stable.

## Outstanding Unknowns

- complete runtime reachability of remaining legacy Admin families;
- production usage of legacy Admin tables;
- production migration ledger for Admin migrations;
- external dependencies on historical Admin URLs/aliases outside cleaned Category/Chat families.

These unknowns remain blockers against bulk deletion of unrelated families.
