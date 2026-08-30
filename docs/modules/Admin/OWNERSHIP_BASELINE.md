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
| Chat legacy runtime | `BOUNDARY MOVED` | Chat | Chat routes/controller/Livewire/service now use Chat-owned runtime/models; legacy Admin copies remain pending final caller proof |
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

## Chat Canonical Boundary Evidence

The Chat slice establishes `Modules/Chat` as canonical owner of Chat behavior while preserving Admin as presentation shell.

Preserved route contracts:

- `admin.chat.index` -> `/admin/chat/internal-chat`;
- `admin.chat.cskh` -> `/admin/chat`;
- both routes resolve to `Modules\Chat\Http\Controllers\ChatController`;
- both routes remain under `auth:admin` and now require `permission:view_chat,admin`.

Canonical runtime changes:

- `Modules/Chat/Services/ChatService.php` now uses `Modules\Chat\Models\ChatSession` and `ChatMessage`;
- `Modules/Chat/Livewire/Chat/ChatManager.php` now uses Chat-owned models/service;
- `Modules/Chat/Livewire/Chat/ChatWidget.php` now uses Chat-owned `ChatSession`;
- admin Chat Livewire actions enforce `view_chat`, `create_chat`, `edit_chat`, and `delete_chat` according to operation;
- internal Chat Livewire actions enforce `view_chat` and `create_chat`;
- canonical `ChatService` retains `deleteAllMessages()` so moving away from the legacy Admin service does not silently drop that behavior;
- realtime channel/payload contracts remain unchanged except for preserving the existing canonical Chat implementation.

Legacy Admin Chat controller/Livewire/model/service/view files are **not deleted in this boundary slice** because repository-wide caller proof is not yet complete. They are compatibility/deprecation candidates, not canonical ownership.

The Chat manifest still depends on `Admin` because Chat pages render inside `Admin::layouts.master`. Removing that presentation dependency requires a separately proven shell contract and is not inferred by this slice.

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

Until those checks are complete, the family remains compatible/deprecated rather than deleted.

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
- Chat canonical runtime must not import Admin Chat models/services.
- Admin Chat routes require `view_chat`; mutating Livewire actions require capability-specific permissions.

## Schema / Migration Rule

Runtime ownership cleanup does not authorize moving or renaming applied migrations or production tables. Chat table/migration ownership is intentionally unchanged in this slice.

## Planned Refactor Sequence

1. keep ownership/P0 containment guardrails green;
2. keep Category cleanup green;
3. verify Chat canonical boundary and authorization changes;
4. perform repository-wide caller proof for legacy Admin Chat copies before deletion;
5. choose one next legacy family after the Chat checkpoint is merged;
6. reconcile schema/migration ownership only after runtime ownership is stable.

## Outstanding Unknowns

- repository-wide callers of legacy Admin Chat models/service/Livewire aliases outside the active Chat routes;
- production usage of legacy Admin tables;
- production migration ledger for Admin migrations;
- external dependencies on historical Admin URLs or Livewire aliases.

These unknowns are blockers against bulk deletion, not authorization to infer or guess ownership.
