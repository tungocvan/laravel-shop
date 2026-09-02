# User — Module Contract

## 1. Identity

- Module: `User`
- Type: `shell`
- Status: `active`
- Manifest: `Modules/User/config/module.php`
- Routes: `Modules/User/routes/web.php`, `Modules/User/routes/api.php`
- Last architecture review: `2026-09-02`

## 2. Purpose

`User` is the canonical shell module for shared user/account directory capabilities used across the application. It owns admin staff account management, user-facing directory/profile integration boundaries, account state, safe assignment of existing roles to accounts, and User import/export behavior.

The module must not become the canonical owner of the Role/Permission catalog or authentication workflow merely because it consumes those capabilities.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Admin staff account list/create/edit/delete | User | `admin.user.*` → `UserController` → `UserForm` / `UserTable` → `UserService` |
| User directory integration | User | `Contracts/UserDirectory` and its User-owned adapter |
| User mail integration boundary | User | `Contracts/UserMailGateway` and its User-owned adapter |
| User import/export | User | `Services/ImportExport`, using Shared import/export infrastructure |
| User profile/address services | User | User-owned services/persistence boundaries, subject to caller and schema proof |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Role and permission catalog | Role | User consumes roles for account assignment; it must not duplicate role/permission administration |
| Authentication workflows | Auth | Auth consumes User public integration contracts; User does not own login/password-flow orchestration |
| Generic import/export infrastructure and UI primitives | Shared | User consumes the canonical Shared infrastructure |
| Admin shell/layout/navigation | Admin | User renders inside the canonical Admin shell |

## 5. Dependencies

### Direct dependencies

| Module | Reason | Required |
|---|---|---|
| Shared | Canonical shared infrastructure, including import/export | Yes |

Direct dependencies must remain synchronized with `Modules/User/config/module.php`.

### Integration dependencies

- `Role`: User reads assignable admin roles and synchronizes role assignments, without taking ownership of the catalog.
- `Auth`: Auth may consume User directory/mail contracts; dependency direction must remain contract-driven and must not duplicate User directory logic.
- `Admin`: User Admin screens use the canonical Admin layout/UI standards.

## 6. Consumers

| Consumer | Capability |
|---|---|
| Auth | User directory and user mail integration contracts |
| Admin operators | Staff account administration and User import/export |
| Other modules | User identity/directory data only through proven public boundaries or established model relations |

## 7. Canonical Routes

- `admin/user` route group, names `admin.user.*`, protected by `auth:admin` and User permissions.
- User API routes remain compatibility-sensitive until caller/reachability proof is complete.

Ownership audits must trace `Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

## 8. Canonical Runtime Components

### Controllers

- `UserController` is the thin Admin page entry point.
- API controller boundaries remain subject to caller proof before removal or rehome.

### Livewire / UI Components

- `UserTable` owns list UI state: search, role filter, bounded pagination, visible-page selection and bulk UI actions.
- `UserForm` owns create/edit UI state and validation orchestration; domain/account mutation belongs in services.

### Services

- `UserService` is the canonical application service for staff account queries/mutations and assignment safeguards.
- `ImportExport` owns User-specific import/export mapping and authorization while reusing Shared infrastructure.
- Directory/mail/profile/address services remain User-owned where runtime callers confirm the boundary.

### Models

- `App\Models\User` is a compatibility-sensitive shared runtime model. This refactor does not mechanically move it into `Modules/User`; any rehome requires repository-wide caller, schema and auth proof in a separately approved architecture change.

## 9. Persistence Ownership

User-related persistence is schema-sensitive. Existing user/account/profile/address tables and migrations must not be moved, renamed, deleted, or recreated merely to match directory structure. Exact table ownership must be reconciled with current migrations and cross-module callers before destructive or ownership-changing migration work.

`App\Models\User` and its backing user table are therefore a compatibility boundary during this refactor.

## 10. Integration Boundaries

### User ↔ Role

- Business owner of role/permission catalog: `Role`.
- Consumer: `User`.
- Allowed behavior: list allowed roles and assign existing valid admin roles to user accounts with authorization safeguards.
- Forbidden duplication: User must not create a parallel role/permission management catalog.

### Auth ↔ User

- Business owner of authentication flow: `Auth`.
- Business owner of shared user directory/mail capability: `User`.
- Integration should use `UserDirectory` / `UserMailGateway` contracts and User-owned adapters where applicable.

### User ↔ Shared Import/Export

- Shared owns reusable import/export infrastructure and UI primitives.
- User owns User-specific permissions, filters, row mapping, validation and export scope.

## 11. Compatibility / Deprecated Boundaries

No runtime artifact may be classified as removable solely because it appears duplicated or legacy. API controllers, profile/customer/address services, shared model placement and other uncertain boundaries require caller proof plus focused/impacted regression before `DELETE` or `REHOME`.

## 12. Quarantine

Until separately proven and approved, quarantine:

- destructive/re-homing changes to `App\Models\User`;
- user/account/profile/address schema ownership changes;
- removal of User API routes/controllers;
- deletion/rehome of customer/profile/address services whose consumers are not exhaustively proven.

Do not expand, beautify, rehome or delete quarantined boundaries as incidental cleanup.

## 13. Refactor Invariants

Every User refactor must preserve:

1. canonical `admin.user.*` routes and admin authentication/permission boundaries;
2. protection against deleting the currently authenticated account;
3. protection against unauthorized visibility or assignment of `Super Admin`;
4. bounded pagination with tampered page-size normalization;
5. header checkbox semantics as visible-page selection unless UI explicitly implements another scope;
6. export semantics: no selected IDs means export all records in the approved/filter scope; selected IDs means export only those selected records;
7. export must never silently mean current page when no rows are selected;
8. checkbox visibility must support selected export independently from delete permission when export permission permits it;
9. public integration contracts and compatibility boundaries until caller proof exists;
10. Shared remains owner of generic import/export infrastructure and Role remains owner of role/permission catalog.

## 14. Required Refactor Audit

Classify affected artifacts as `KEEP`, `REHOME`, `DELETE`, `QUARANTINE`, or `DEFER` only after route/runtime/caller proof. Similar names or directory placement are not deletion proof.

Approved target for the 2026-09-02 refactor:

- `KEEP / REFACTOR`: Admin routes, controller, `UserForm`, `UserTable`, `UserService`, User import/export.
- `KEEP`: public directory/mail contracts.
- `VERIFY / DEFER`: customer/profile/address services and API compatibility surfaces.
- `QUARANTINE`: shared User model placement and destructive schema ownership changes.
- `DELETE / REHOME`: only when caller proof and regression establish safety.

## 15. Required Regression Scope

Minimum gate for User architectural/UI work:

- Pint on changed PHP files;
- focused tests for User list/form/service/import-export behavior;
- User module regression;
- impacted Auth, Role and Admin regression where boundaries changed;
- route verification for `admin.user.*` and any affected User API routes;
- frontend build when Blade/UI assets are affected;
- manual Admin UI acceptance for desktop/mobile/tablet, especially input borders/focus states, bounded pagination, checkbox semantics, bulk confirmation, loading states and export selected/all behavior;
- clean Git state before PR/merge closeout.

Full-project regression is conditional, not automatic.

## 16. Architectural Change Rules

Update this contract in the same PR whenever changing User responsibility, ownership/non-ownership, direct dependencies, routes, integration boundaries, persistence ownership, compatibility/quarantine boundaries or refactor invariants.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| Exact persistence ownership of shared user/profile/address schema | User + current schema owners | Shared model/schema is compatibility-sensitive | migration/caller audit + approved architecture decision |
| User API compatibility surface | User | Reachability not yet proven exhaustively | caller proof + replacement contract + regression |
| Customer/profile/address service overlap | User / proven domain owner | Potential ownership overlap requires evidence | cross-module caller and persistence proof |
| Possible consolidation of module-scoped pagination into shared Admin paginator | Admin/Shared | Avoid premature global UI change | stable shared paginator proven across modules |

## 18. Architecture Decisions

### 2026-09-02 — Establish User as shared account-directory shell

**Decision:** Keep User as the canonical shell owner for shared account-directory administration and public user integration boundaries, while Role owns the role/permission catalog, Auth owns authentication workflows, and Shared owns generic import/export infrastructure.

**Reason:** Runtime config already identifies User as a shell module and current services/contracts show these integration directions. Explicit ownership prevents duplicated account, role and auth logic.

**Impact:** The approved refactor can modernize User UI, pagination, selection and export behavior in one coherent PR without destructive model/schema re-homing.

### 2026-09-02 — Canonical selected export semantics

**Decision:** When the User list has selected row IDs, export only those selected records. When selection is empty, export the full approved/filter scope, never only the current page.

**Reason:** This matches the repository Admin UI standard and removes ambiguity between visible-page selection and dataset export scope.

**Impact:** UserTable/ImportExport integration and tests must enforce the rule server-side.