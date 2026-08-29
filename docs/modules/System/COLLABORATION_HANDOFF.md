# System Collaboration Handoff

## Current Status

- Module: `System`
- Feature: read-only Admin Dashboard
- Delivery branch: `feat/system-admin-dashboard`
- Base/source checkpoint: `main@645e7737540436333e82ad4798ad67390c882cc6`
- Implementation status: **READY FOR OPERATOR VERIFICATION**
- Pull request: **NOT OPENED**
- Merge status: **NOT MERGED**

The first approved implementation phase is intentionally limited to a read-only Admin Dashboard. The broader System consolidation roadmap remains deferred to separate branches and PRs.

## Approved Scope

Add a permission-aware Dashboard at:

```text
GET /admin/system/dashboard
admin.system.dashboard
web, auth:admin, permission:system.manage
```

The existing route and behavior at `GET /admin/system` / `admin.system.index` remain unchanged.

The Dashboard may navigate to existing workspaces but must not directly change configuration, run Artisan or shell operations, probe workers, synchronize Google Drive, create/delete backups, restore data, clear cache or perform any other mutation.

## Architecture

```text
SystemDashboardController
    -> SystemDashboardService
        -> SystemDashboardData (immutable bounded safe DTO)
            -> System::pages.dashboard
```

The Dashboard service:

- checks table availability before querying;
- uses fixed aggregate queries for queue counts;
- reads only four allowlisted settings keys for sanitized Drive/backup state;
- does not scan backup directories or Module manifests;
- does not call Google or another remote API;
- does not execute Artisan, shell commands or scheduler operations;
- limits warnings to five and workspace definitions to eight;
- returns generic unavailable states instead of raw exceptions;
- logs only section context and exception class.

## Permission Contract

| Capability | Dashboard behavior |
|---|---|
| `system.manage` | Required to open the Dashboard; shows the existing System workspace |
| `system.settings.view` | Shows settings/queue navigation and sanitized local queue counts |
| `system.env.view` | Shows environment/integration navigation and boolean configuration, Drive and cloud-backup state |
| `system.modules.view` | Shows the Module workspace link |
| `system.commands.run` | Shows links to the existing allowlisted Artisan and script operation workspaces; no operation exists on Dashboard |
| `database.view` | Shows database and backup/restore links plus database metadata availability |

No new permission, migration, seeder, menu entry or configuration key is introduced. Hiding a Dashboard section does not replace authorization on the target route or action.

## Data Safety Contract

Allowed Dashboard data:

- capability booleans;
- table/subsystem availability booleans;
- bounded workspace and warning counts;
- aggregate pending, reserved and failed job counts;
- configuration completeness counts;
- allowlisted `success` / `failed` backup state;
- sanitized timestamps;
- boolean Google OAuth configuration and stored-connection state.

The DTO and HTML must not contain:

- environment or raw configuration values;
- Google client ID, client secret, token, email or folder ID;
- backup filename, private path, persisted error message or raw file list;
- queue payload;
- database credentials;
- raw exception text or external payload.

## Workspace Navigation

The following routed Admin pages include the shared permission-aware `Quay về Dashboard` partial:

- current System tab workspace;
- general settings;
- environment/integration settings;
- Module manager;
- Artisan operations;
- script operations;
- database manager;
- database backup/restore.

The Dashboard follows `ADMIN_UI_STANDARD.md`: Admin shell-owned width, responsive grids, semantic sections, visible focus states, capability-aware links and readable empty/unavailable/error states.

## Compatibility Boundary

- `admin.system.index` is preserved with its current controller and behavior.
- Existing controllers, Livewire components, services, routes and permission names are not renamed.
- No public System service contract is modified.
- No business Module consumer is changed.
- No migration, config, seeder or storage ownership change is included.
- ClientPortal/PWA is untouched; Admin Blade, routes and authentication are not reused there.
- Rollback is limited to the Dashboard route/files, shared return-link includes and this handoff.

## Files

### Added

```text
Modules/System/Data/SystemDashboardData.php
Modules/System/Http/Controllers/SystemDashboardController.php
Modules/System/Services/SystemDashboardService.php
Modules/System/resources/views/pages/dashboard.blade.php
Modules/System/resources/views/partials/dashboard-return-link.blade.php
tests/Feature/System/SystemDashboardTest.php
docs/modules/System/COLLABORATION_HANDOFF.md
```

### Updated

```text
Modules/System/routes/web.php
Modules/System/resources/views/system.blade.php
Modules/System/resources/views/pages/settings/env.blade.php
Modules/System/resources/views/pages/settings/index.blade.php
Modules/System/resources/views/pages/settings/modules.blade.php
Modules/System/resources/views/pages/settings/artisan.blade.php
Modules/System/resources/views/pages/settings/scripts.blade.php
Modules/System/resources/views/pages/database.blade.php
Modules/System/resources/views/pages/database-backup-restore.blade.php
```

## Verification Gate

Required before opening the PR:

```text
Pint changed PHP files                         PENDING
Focused SystemDashboardTest                   PENDING
System module regression                      PENDING
Admin Feature regression                      PENDING
Route inspection                              PENDING
Frontend production build                     PENDING
Desktop UI acceptance                         PENDING
Mobile UI acceptance                          PENDING
Working tree clean                            PENDING
```

Verification remains limited to System and directly impacted Admin tests. Full-project regression is not required unless the implementation scope expands.

## Deferred Refactor Roadmap

The approved roadmap continues in separate PRs only:

1. Correct the identified Google Drive/backup P0 boundaries.
2. Consolidate settings ownership and preserve compatibility adapters.
3. Separate Module registry/runtime control responsibilities.
4. Improve queue and scheduler health contracts, locking and multi-server behavior.
5. Split database operations behind smaller explicit services.
6. Deprecate/remove legacy or duplicate components only after consumer and compatibility tests prove they are removable.

No deferred item is authorized for implementation by approval of this Dashboard phase.

## Deferred Existing Debt

- Google Drive and database-backup operations still require their dedicated P0 correction PR.
- Queue worker health still depends on explicit probe behavior in Queue Manager; Dashboard does not infer worker liveness.
- Scheduler runtime/distributed-lock health has no canonical persisted heartbeat contract.
- System/Admin settings ownership and duplicated presentation components remain unchanged.
- Module registry discovery and runtime mutation remain coupled in existing services.
- Database operations remain concentrated in the existing large service.
- Historical System analysis documents contain drift from already completed containment work and need a later documentation consolidation.

## PR and Merge Gate

1. Operator pulls the feature commit and runs the focused verification matrix.
2. Operator confirms desktop/mobile Dashboard UI acceptance.
3. Verification results and the final source checkpoint are recorded in this handoff.
4. PR is opened for manual user review.
5. The user reviews the PR link and merges manually.
6. A docs-only post-merge closeout is created if the merge checkpoint or deferred roadmap needs recording.
