# Admin Ownership & Reachability Baseline

## Purpose

This document is the implementation baseline for the Admin Major Refactor. It defines what the Admin module is allowed to own, records the current reachability boundary, and prevents legacy code from being moved or deleted without evidence.

The rule for this refactor is ownership-first: Admin is the authenticated presentation/composition shell. A feature being displayed inside the Admin UI does not make Admin the canonical owner of that feature's business data or workflow.

## Canonical Admin Shell

The following surfaces are canonical Admin ownership and must be preserved unless a later approved architecture change explicitly replaces them:

- `/admin` dashboard entry and shell composition;
- `/admin/menus`, `/admin/menus/create`, `/admin/menus/{id}/edit` as the configuration workspace for Admin sidebar/navigation;
- `AdminMenu`, `MenuService`, and `MenuImportExportService` where they manage Admin navigation metadata;
- Admin sidebar rendering and navigation composition;
- Admin header and header actions/user menu presentation;
- Admin layout workspaces: general, header, sidebar, footer, design, and navigation;
- Admin theme/design presentation;
- Admin shell-owned profile/preferences;
- shell-specific presentation services and reusable Admin shell components.

### Sidebar menu ownership rule

`/admin/menus` is **not legacy domain administration**. It is a canonical shell configuration surface because it defines how the Admin sidebar/navigation is composed.

A menu item may point to Product, Order, System, Account, or another module. That link is navigation metadata only. The target module remains the canonical owner of its routes, services, models, permissions, and business rules.

## Current Active Route Boundary

`Modules/Admin/routes/web.php` currently imports only:

- `AdminController`
- `DashboardController`
- `MenuController`
- `ProfileController`

The route group is protected by `web` + `auth:admin`, with named capability middleware for dashboard, menu, profile, layout, and header surfaces.

`Modules/Admin/routes/api.php` is intentionally empty. An Admin API must not be introduced without an explicit authentication/authorization contract and tests.

These facts define the active route baseline. Legacy controllers present elsewhere in `Modules/Admin/Http/Controllers` are not considered canonical merely because they remain autoloadable.

## Classification Vocabulary

Every legacy Admin component must be assigned one of these states before refactor action:

| State | Meaning | Allowed action |
|---|---|---|
| `KEEP` | Canonical Admin shell ownership | Preserve and improve in Admin |
| `MOVE` | Reachable behavior whose canonical owner is another module | Migrate caller/contract first, then remove Admin copy |
| `DEPRECATE` | Replacement is proven but compatibility remains | Add compatibility plan and remove later |
| `DEAD` | No reachable route/caller/alias/job/event/test and no production dependency | Remove in a focused cleanup MR |
| `UNKNOWN` | Reachability or production/schema state is not proven | Do not delete or move yet |
| `QUARANTINE` | Dangerous legacy capability that must stay unreachable | Add guardrails; redesign only in a separately approved owner |

## Ownership Families

This baseline classifies families, not every file as deleted or movable. File-level action requires caller evidence in the dedicated domain slice.

| Admin family | Baseline state | Canonical owner / direction | Notes |
|---|---|---|---|
| Dashboard shell | `KEEP` | Admin | Shell entry/composition |
| Sidebar/navigation/menu management | `KEEP` | Admin | Includes `/admin/menus`, `AdminMenu`, menu services and shell rendering |
| Layout/header/sidebar/footer/design/theme | `KEEP` | Admin | Admin shell presentation only |
| Admin profile/preferences | `KEEP` | Admin UI + canonical identity/account contracts as applicable | Persistence ownership must be checked before moving models |
| Product / ProductCommission legacy | `UNKNOWN -> MOVE candidate` | Product | Verify callers and replacement completeness |
| Orders legacy | `UNKNOWN -> MOVE candidate` | Order | Verify callers and compatibility |
| Posts/content legacy | `UNKNOWN -> MOVE candidate` | canonical content/Post owner | Verify current module contract before choosing owner |
| Categories legacy | `UNKNOWN -> MOVE candidate` | Category | Category module exists; verify replacement completeness |
| Chat legacy | `UNKNOWN -> MOVE candidate` | Chat | Chat module exists; verify callers |
| Customer/address legacy | `UNKNOWN -> MOVE candidate` | Account/User/Identity according to current contract | Do not guess schema owner |
| Roles/staff legacy | `UNKNOWN -> MOVE candidate` | Role/User/Account according to responsibility | Admin can present screens without owning identity/authorization domain |
| Banner/public website header/footer/home settings | `UNKNOWN -> MOVE candidate` | Website/content owner | Distinguish public-site presentation from Admin shell header/footer |
| Affiliate/flash sale/coupon/marketing legacy | `UNKNOWN -> MOVE candidate` | canonical marketing/promotion/domain owner | Verify actual module inventory and callers first |
| Environment/system settings legacy | `UNKNOWN -> MOVE candidate` | System or dedicated configuration owner | Requires explicit operational boundary |
| Database administration | `QUARANTINE` | future hardened System database-operation boundary | Must remain unreachable in Admin |
| Historical scaffold/resource methods | `UNKNOWN -> DEAD candidate` | none | Remove only after caller proof |

## Reachability Proof Required Before MOVE / DEPRECATE / DEAD

A file or family may not be removed merely because it is absent from `Modules/Admin/routes/web.php`. The corresponding refactor slice must check, where applicable:

1. web/API routes and route providers;
2. Livewire aliases and class discovery/registration;
3. Blade tags, includes and route links;
4. controller/service/model imports across all modules;
5. jobs, events, listeners, commands and scheduled tasks;
6. tests and factories/seeders;
7. menu/navigation records or config that reference historical URLs;
8. production table usage and migration ledger when schema ownership is involved;
9. external clients or bookmarked/historical URLs if compatibility is material.

Until those checks are complete, state remains `UNKNOWN`.

## P0 Database Administration Quarantine

`Modules/Admin/Services/DatabaseService.php` is a latent destructive capability and is not part of the canonical Admin shell.

Current containment contract:

- no active Admin web route references database administration;
- Admin API routes remain empty;
- `Modules/Admin/Livewire/Database/TableList.php` returns an empty table set;
- all exposed database mutation actions delegate to a single deny method;
- the deny method aborts with HTTP 403;
- the Livewire component does not instantiate or reference `DatabaseService`.

This branch adds contract tests for those conditions. The tests are intentionally containment tests; they do not make `DatabaseService` production-safe.

A future System-owned design requires a separate approval and must cover at least: explicit permissions, audit trail, server-controlled allowlists, safe process invocation, secret redaction, backup integrity checks, restore verification, bounded resource usage, and recovery/rollback procedures.

## Guardrails Introduced In This Slice

- Admin manifest remains type `shell` with declared Auth/User/Role dependencies.
- Active Admin route controller imports are limited to canonical shell controllers.
- `/admin/menus` and the `admin.menu.*` capability set are explicitly protected as canonical sidebar/navigation configuration.
- Admin API remains closed by default.
- Admin database administration remains unreachable from active route files.
- legacy database Livewire actions remain fail closed and disconnected from `DatabaseService`.

These guardrails protect the current good architecture while later MRs migrate one legacy family at a time.

## Out of Scope For This Baseline

This slice does not:

- delete legacy domain code;
- move domain classes between modules;
- rename or move migrations;
- alter production schema or migration ledger;
- re-enable database administration;
- redesign database backup/restore in System;
- change route names, permission names, Livewire aliases, or Admin UI behavior;
- introduce a new UI/design system.

## Planned Refactor Sequence

1. keep this ownership/reachability baseline and P0 containment green;
2. choose one legacy family with a verified canonical replacement;
3. prove all callers/reachability for that family;
4. migrate callers/contracts while preserving compatibility where required;
5. run focused Admin + impacted-module tests;
6. remove only the proven obsolete Admin copy;
7. repeat for the next family;
8. reconcile schema/migration ownership only after runtime ownership is stable.

## Outstanding Unknowns

- complete runtime reachability of every legacy Admin Livewire/controller/service outside active routes;
- production usage of legacy Admin tables;
- production migration ledger for Admin migrations;
- completeness of every candidate canonical replacement;
- external dependencies on historical Admin URLs or Livewire aliases.

These unknowns are deliberate blockers against bulk deletion, not authorization to infer or guess ownership.
