# Modules/System — P2 Completion Review

Review date: 2026-08-12

## Baseline

- Original Livewire inventory reviewed: **19 components**.
- System regression suite after P0/P1 work: **80 passed, 0 failed** (user-verified).
- P0/P1 implementation is treated as closed unless this review identifies residual P2 debt.
- This document is a review/checklist only. It does not authorize unrelated feature expansion.

## Executive Result

### Formal P2 queue remaining from the original checklist: 4 components

1. `Database/BackupManager` — targeted hardening/refactor.
2. `Settings/SettingForm` — light cleanup/refactor.
3. `Settings/StorageConfig` — remove dead placeholder after final reference check.
4. `Settings/Placeholder` — remove broken/dead placeholder after final reference check.

### Additional residual P2 debt found during current-source review

5. `Database/TableList` — P0/P1 work is closed, but long-running synchronous backup/restore/import operations and audit/operational observability remain as architectural P2 debt. This is **not** reopening the prior P0/P1 refactor.

Therefore:

- **Formal untouched P2 components:** 4
- **Components with P2 action remaining including residual debt:** 5
- **No-action/closed/retired components:** 14

---

## 19/19 Component Completion Matrix

| # | Component | Current status | P2 action |
|---|---|---|---|
| 1 | `Database/TableList` | P0/P1 closed; residual debt | Targeted P2 architecture/ops review |
| 2 | `Database/BackupManager` | Formal P2 | Targeted hardening + tests |
| 3 | `Database/ImportDrawer` | Retired | None |
| 4 | `Settings/AdvancedConfig` | P1 closed | None |
| 5 | `Settings/ArtisanList` | P0/P1 closed | None |
| 6 | `Settings/DatabaseConfig` | P1 closed | None |
| 7 | `Settings/EnvManager` | P1 closed | None |
| 8 | `Settings/MailConfig` | P1 closed | None |
| 9 | `Settings/ModulesForm` | P0/P1 closed | None |
| 10 | `Settings/MomoConfig` | P1 closed | None |
| 11 | `Settings/SettingForm` | Formal P2 | Light cleanup + contract tests |
| 12 | `Settings/ShScript` | P0/P1 closed | None |
| 13 | `Settings/SocialConfig` | P1 closed | None |
| 14 | `Settings/StorageConfig` | Formal P2 | Remove if final reference check remains empty |
| 15 | `Settings/Placeholder` | Formal P2 | Remove if final reference check remains empty |
| 16 | `Settings/Partials/Custom` | P1 closed | None |
| 17 | `Settings/Partials/General` | P1 closed | None |
| 18 | `Settings/Partials/Images` | P1 closed | None |
| 19 | `Settings/Partials/Seo` | P1 closed | None |

---

# P2-1 — Database/BackupManager

## Current strengths

Keep the existing architecture:

- capability-specific action authorization already exists (`database.restore`, `database.destroy`, `database.download`);
- restore/import behavior delegates to `DatabaseService`;
- Google Drive final download host is fixed;
- restore and delete UI already have destructive confirmations;
- backup delivery is queued rather than attaching the file to Livewire state.

## Remaining issues

### P2-A — Safe browser error mapping

Current component still surfaces `$e->getMessage()` through restore/delete/upload/Drive-import paths. Operational database/storage/process exceptions must remain in logs while browser messages become stable and generic.

### P2-A — Dedicated tests for a high-risk operational component

Add focused coverage for:

- action-level authorization;
- invalid/full-backup upload semantics through the service contract;
- Google Drive ID parsing and HTTP failure mapping;
- restore/delete identifiers;
- email size/missing-file race behavior;
- browser messages not containing raw exception text.

### P2-B — Bounded backup history

`render()` currently requests `getAllBackupFiles()` and renders the complete collection. Add a bounded recent-history policy or service-level limit suitable for production retention.

### P2-B — Privileged operation audit

Record actor/action/backup identifier metadata for restore/delete/upload/Drive import/email dispatch without logging secrets or SQL contents. Reuse an existing audit facility if one exists; do not invent a parallel audit system.

### P2-C — Touch/mobile action discoverability

If the current Blade still relies on hover-only actions, make critical controls visible/discoverable on touch layouts.

## Admin Menu / route

Do not add a new menu. Keep the existing database backup/restore page under the System database area and retain route-level `database.view`; mutations remain capability-specific inside Livewire.

## Recommended implementation class

**Targeted refactor. No rebuild of `DatabaseService`.**

---

# P2-2 — Settings/SettingForm

## Current strengths

- public tab selection is allowlisted;
- component aliases are returned by a fixed server-side `match`;
- shell owns no persistence;
- page-level access remains `system.settings.view` while child components enforce mutation permissions.

## Remaining issues

### P2-A — Remove unrelated CDN assets

The shell currently injects jQuery 3.7.1 and Summernote 0.8.18 from external CDNs. Current System children use project-owned abstractions and there is no clear direct need for these assets in the shell.

Before removal, verify the two cross-module children (`admin.theme-switcher`, `admin.header.menu-manager`) do not implicitly require these globals. If they do, ownership must move to the child/layout that actually requires them rather than remaining hidden in System's tab shell.

### P2-B — Document cross-module contracts

Document that System Settings intentionally composes:

- `admin.theme-switcher`
- `admin.header.menu-manager`

These are supported cross-module component contracts and must not silently become arbitrary browser-controlled aliases.

### P2-B — Focused tab-resolution tests

Cover:

- invalid tab → safe fallback;
- every key → expected fixed alias;
- no arbitrary component alias from browser state;
- settings route remains protected by `system.settings.view`.

### P2-C — Accessibility semantics

Add active-tab semantics (`aria-selected`, tab role/relationship) if consistent with repository UI primitives.

## Admin Menu / route

No new menu. Continue using `/admin/system/settings` with `system.settings.view`.

## Recommended implementation class

**Light cleanup/refactor.**

---

# P2-3 — Settings/StorageConfig

## Current source state

The component is render-only, has no state/action/service contract, contains commented abandoned imports, and its view is empty.

## Recommended action

**Remove**, provided the final reference scan confirms no route/config/dynamic alias points to `system.settings.storage-config`.

Do not implement storage credentials/features merely to justify keeping a dead placeholder. If storage configuration is needed later, create a separate feature specification with secret-handling and permission design first.

## Admin Menu / route

No menu/route should be added for deletion.

## Tests

A regression test may assert the dead component/view are absent only if repository conventions use such retirement guards; otherwise deletion + full System regression suite is sufficient.

---

# P2-4 — Settings/Placeholder

## Current source state

The component renders `System::livewire.settings.placeholder`, but that view path is absent. A different generic placeholder Blade exists elsewhere, making the current contract ambiguous. The component has no state/actions/business responsibility.

## Recommended action

**Remove**, provided final reference scan confirms no dynamic alias usage.

Do not fix the missing view just to preserve unused dead code. If a generic placeholder becomes a real UI requirement, define one canonical component/view explicitly later.

## Admin Menu / route

No menu/route should be added.

---

# Residual P2 — Database/TableList

## What is already closed

The current source now has:

- one declared `DatabaseService` dependency pattern;
- capability authorization at mutation boundaries;
- safe generic browser errors + server logging;
- bulk selected-table export implemented;
- table import integrated into the canonical table workflow;
- select/search/module-filter synchronization;
- indeterminate restore/import UX rather than fake percentages;
- modal close blocked during active restore/import.

Do not reopen those items.

## Remaining architectural P2 debt

### Long-running operations remain synchronous

Backup, restore and import can still execute external DB tooling in a Livewire/web request. For small/controlled databases this may remain acceptable, but production-scale operation should be evaluated for a persisted job/operation model.

This is a larger architectural change than normal UI cleanup. It should be a separate P2 design decision, not slipped into `BackupManager` cleanup.

### Audit/observability

Destructive database operations should share a consistent audit/event model if the project has one.

### Performance measurement

`getAllTables()` still runs during render/search changes. Do not add caching/pagination speculatively; measure before changing this path.

## Recommended action

Create a **separate P2 TableList operations plan** only if production DB size/timeout requirements justify asynchronous execution. Otherwise document synchronous-mode operating limits and leave code unchanged.

---

# Route / Permission / Admin Menu Review

Canonical System route boundaries to preserve:

- `/admin/system/settings` → `system.settings.view`
- `/admin/system/settings/env` → `system.env.view`
- `/admin/system/modules` → `system.modules.view`
- `/admin/system/artisan` and `/admin/system/scripts` → `system.commands.run`
- `/admin/system/database` and `/admin/system/database/backup-restore` → `database.view`
- database downloads → `database.download`

P2 work should **not introduce new Admin Menu entries** for `SettingForm`, `StorageConfig`, or `Placeholder`.

`BackupManager` remains part of the existing database management navigation rather than becoming a duplicate menu entry.

---

# Proposed P2 Execution Order

Recommended order based on risk and effort:

1. **`Database/BackupManager`** — highest operational risk; safe errors + tests + bounded history/audit.
2. **`Settings/SettingForm`** — remove CDN coupling + tests/documented cross-module contract.
3. **`Settings/StorageConfig` + `Settings/Placeholder`** — batch dead-code retirement after reference check.
4. **`Database/TableList` residual architecture decision** — separately decide sync operating limits vs queued/persisted operations; do not refactor automatically.

---

# Acceptance Checklist for P2 Completion

- [ ] `BackupManager` no longer exposes raw operational exceptions to browser.
- [ ] Dedicated `BackupManager` authorization/import/restore tests pass.
- [ ] Backup history has an intentional bounded/retention display policy.
- [ ] Privileged backup operations use the project's canonical audit mechanism if available.
- [ ] `SettingForm` no longer owns unrelated external jQuery/Summernote CDN dependencies.
- [ ] Fixed tab/component mapping remains server-owned and tested.
- [ ] Admin cross-module child dependencies are documented.
- [ ] `StorageConfig` is removed or explicitly retained by a documented real feature contract.
- [ ] `Placeholder` is removed or explicitly retained by a valid render contract.
- [ ] No duplicate route/Admin Menu entry is introduced.
- [ ] `php artisan test tests/Feature/System` remains fully green.
- [ ] A final 19/19 status document marks every component as Closed, Retired, or intentionally deferred architectural debt.

## P2 completion definition

P2 is considered complete when the four formal P2 components are closed/retired and the `TableList` residual architecture decision is explicitly documented (implemented or intentionally deferred with operating constraints), while the full System regression suite remains green.
