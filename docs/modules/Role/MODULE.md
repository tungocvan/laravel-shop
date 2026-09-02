# Role — Module Contract

## 1. Identity
- Module: `Role`
- Type: `shell`
- Status: `active`
- Manifest: `Modules/Role/config/module.php`
- Routes: `Modules/Role/routes/web.php`
- Last architecture review: `2026-09-02`

## 2. Purpose
Role owns administration of admin-guard roles and assignment of approved permissions to those roles. It exposes a stable read-only role directory for consumers and an Admin workspace for role maintenance, import/export and permission-catalog synchronization operations.

## 3. Canonical Ownership
| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Admin role list/create/edit/delete | Role | `admin.role.*` / `RoleController` / Role Livewire |
| Role persistence operations | Role | `RoleService` |
| Stable read-only role directory | Role | `RoleDirectory` → `SpatieRoleDirectory` |
| Role import/export | Role | `Modules\\Role\\Services\\ImportExport` |
| Role UI selection/export scope | Role | `RoleTable` + Shared ImportExport panel |

## 4. Explicit Non-Ownership
| Capability | Canonical owner | Relationship |
|---|---|---|
| Module permission declarations/catalog discovery | System/application module catalog | Role consumes through `ModulePermissionManager` |
| User identity/profile lifecycle | User | Role depends on User membership data |
| Shared import/export transport/UI infrastructure | Shared | Role integrates through shared service/panel contracts |
| Spatie package implementation | framework/package boundary | Role adapts; package tables are persistence-sensitive |

## 5. Dependencies
### Direct dependencies
| Module | Reason | Required |
|---|---|---|
| User | Role membership and user-role relationships | Yes |

This must remain synchronized with `Modules/Role/config/module.php`.

### Integration dependencies
- Shared ImportExport infrastructure provides reusable upload/export UI and transport.
- `App\\Modules\\ModulePermissionManager` provides the active permission catalog; Role does not invent permissions outside that catalog.

## 6. Consumers
- Other modules may consume `Modules\\Role\\Contracts\\RoleDirectory` for bounded read-only lookup.
- Admin shell links to the canonical Role management workspace.

## 7. Canonical Routes
- `GET /admin/roles` → `admin.role.index`
- `GET /admin/roles/create` → `admin.role.create`
- `GET /admin/roles/{id}/edit` → `admin.role.edit`

Legacy `/admin/role*` paths are compatibility redirects and are not canonical ownership entries.

## 8. Canonical Runtime Components
- `RoleController`: thin page-shell routing only.
- `RoleTable`: list/filter/pagination/selection UI state and delegation.
- `RoleForm`: create/edit form state and permission selection.
- `RoleService`: role mutations and protection invariants.
- `RolePermissionCatalogService`: Role-facing adapter for permission-catalog sync operations.
- `ImportExport`: role-specific import/export policy and scope.
- `SpatieRoleDirectory`: stable read-only directory implementation.

## 9. Persistence Ownership
Role operates against Spatie Permission persistence (`roles`, `permissions`, role/permission/user pivots). These tables are persistence-sensitive shared/package contracts. This refactor does not rehome, delete or migrate them.

## 10. Integration Boundaries
- Role may consume the active permission catalog but must never generate arbitrary permissions not declared by active modules.
- Consumers use `RoleDirectory` rather than querying Role implementation internals when the stable directory contract fits.
- Shared ImportExport receives Role-owned filters. **selected IDs => selected only**; no selected IDs => all records in the approved filtered scope, never only the current page.

## 11. Compatibility / Deprecated Boundaries
| Artifact | Canonical replacement | Status | Removal condition |
|---|---|---|---|
| `/admin/role*` redirects | `/admin/roles*` | compatibility | caller proof + regression + explicit approval |

## 12. Quarantine
- Spatie Permission tables, pivots and historical permission assignments are QUARANTINE from destructive cleanup.
- Historical permissions attached to an existing role are preserved unless an approved migration/removal plan proves safe removal.

## 13. Refactor Invariants
1. Only `admin` guard roles are administered by this module.
2. `Super Admin` cannot be edited or deleted through ordinary Role management.
3. A role in use cannot be deleted.
4. Permission assignment remains bounded by approved active catalog plus preserved historical assignments for existing roles.
5. Export selection semantics are selected-only when IDs exist, otherwise all matching approved filters.
6. Pagination is bounded and cannot be used as implicit export scope.
7. Checkbox availability for export must not depend on delete permission; protected roles may still be selected for export while destructive services retain protection.
8. Canonical routes and authorization middleware remain stable.

## 14. Required Refactor Audit
Audit route → controller → Livewire/view → service → persistence → callers/dependencies and classify changes as KEEP / REHOME / DELETE / QUARANTINE / DEFER before architectural changes.

## 15. Required Regression Scope
- Focused Role service/Livewire/import-export tests.
- Full `tests/Feature/Role` regression.
- User/Shared/System regression only when their boundary is changed or evidence shows direct impact.
- Route verification for `admin.role.*` and compatibility redirects.
- Pint changed PHP files.
- Frontend build and manual Admin desktop/mobile smoke when UI changes.

## 16. Architectural Change Rules
Update this contract in the same PR whenever Role responsibility, dependency, route ownership, integration, persistence, compatibility, quarantine or invariants change.

## 17. Deferred Debt
| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| Remove legacy `/admin/role*` redirects | Role | caller proof not yet established | no runtime callers + approved compatibility removal |
| Broader Spatie persistence cleanup | system/package migration scope | data-sensitive and unrelated to this refactor | dedicated migration/recovery plan |

## 18. Architecture Decisions
### 2026-09-02 — Role management and export boundary
**Decision:** Keep Role as canonical admin-role shell, consume permission catalog through a Role-facing service boundary, and bind checkbox selection directly to export scope.

**Reason:** Prevent Livewire from owning permission persistence/catalog mechanics, preserve authorization/data invariants, and make export behavior explicit and testable.

**Impact:** Role UI/service boundaries become clearer; no schema migration or canonical route break is introduced.
