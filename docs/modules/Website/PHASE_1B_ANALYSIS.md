# Website Phase 1B — Admin Authorization Analysis

## Status

- Phase: `1B — Admin Authorization`
- Analysis: `COMPLETE`
- Implementation: `NOT STARTED`
- Tests: `NOT STARTED`
- Approval to implement: `PENDING`
- Previous phase: `1A — APPROVED / CLOSED`

## Goal

Add capability-level authorization to Website Admin without changing domain ownership, database architecture, or admin UI design.

Phase 1B must protect both page access and Livewire mutation methods. Hiding a menu/button is not considered authorization.

## Current Authentication Baseline

Website Admin routes are grouped under:

```php
Route::middleware(['web', 'auth:admin'])
```

The `admin` guard uses the same `users` provider as the `web` guard. The provider resolves to `App\Models\User`.

`App\Models\User` already uses Spatie `HasRoles`, so the current project has the technical foundation required for permission-based authorization without introducing a second admin identity model in Phase 1B.

Important boundary:

- `auth:admin` proves an authenticated session exists on the admin guard.
- It does **not** prove the user may edit homepage, delete customers, modify coupons, or manage affiliate configuration.

## Website Admin Surface

Current Website Admin routes/pages include:

```text
/admin/homepage-settings
/admin/header-settings
/admin/footer-settings
/admin/banners
/admin/flash-sales
/admin/coupons
/admin/coupons/create
/admin/coupons/{id}/edit
/admin/customers
/admin/customers/create
/admin/customers/{id}
/admin/affiliate
```

Current Livewire Admin areas include:

```text
Home
Header
Footer
Banner
FlashSale
Coupon
Customers
Affiliate
```

## Confirmed Authorization Risk

### F1B-01 — Routes only require authenticated admin session

Current Website Admin routes do not add capability-specific middleware.

Result: any account able to authenticate through the `admin` guard can potentially reach every Website Admin page unless deeper checks happen elsewhere.

### F1B-02 — Homepage settings mutation has no authorization check

`Modules/Website/Livewire/Admin/Home/HomeSettings.php` exposes `save()` and state-changing helper methods without capability checks.

`save()` directly persists homepage settings and uploads promo images.

This is a privileged mutation and must be protected at the Livewire method boundary even if the page route also receives permission middleware.

### F1B-03 — Customer administration is cross-domain and high risk

Website currently contains Customer create/detail/table administration. Customer ownership will be corrected in Phase 2, but Phase 1B cannot leave destructive customer actions unprotected while waiting for ownership migration.

Temporary Phase 1B rule: protect existing customer actions using customer-scoped permissions; do not move the code yet.

### F1B-04 — Marketing/admin concepts are mixed into Website

Coupon and Flash Sale are likely future non-Website owners, but they remain operational inside Website today.

Temporary Phase 1B rule: authorize them using marketing-scoped permission names so later Phase 2 ownership movement does not require renaming Website-specific permissions.

### F1B-05 — Affiliate should not be hidden behind generic Website permission

Affiliate administration changes commission/business configuration. It requires its own capability rather than inheriting broad `website.settings.manage`.

## Permission Naming Strategy

Use stable business-capability names rather than route names or Livewire class names.

### Website-owned capabilities

```text
website.view
website.home.manage
website.menu.manage
website.footer.manage
website.banner.manage
website.settings.manage
```

Notes:

- `website.view`: general entry/read capability for Website administration where needed.
- `website.home.manage`: homepage settings/builder mutations.
- `website.menu.manage`: header/navigation/menu mutations.
- `website.footer.manage`: footer columns/links/social mutations.
- `website.banner.manage`: banner CRUD/status/order mutations.
- `website.settings.manage`: reserve for global Website settings; do not use it as a universal bypass for every feature.

### Marketing capabilities

```text
marketing.coupon.view
marketing.coupon.manage
marketing.flash-sale.view
marketing.flash-sale.manage
```

### Customer capabilities

```text
customer.view
customer.create
customer.update
customer.delete
```

### Affiliate capabilities

```text
affiliate.view
affiliate.manage
```

## Why View and Manage Are Separated

Read-only administration is useful for support, operations, and audit roles.

Examples:

```text
Content Editor
- website.view
- website.home.manage
- website.menu.manage
- website.footer.manage
- website.banner.manage

Marketing Staff
- marketing.coupon.view
- marketing.coupon.manage
- marketing.flash-sale.view
- marketing.flash-sale.manage

Customer Support
- customer.view
- customer.update

Affiliate Manager
- affiliate.view
- affiliate.manage
```

Destructive rights such as `customer.delete` must remain explicit.

## Route Authorization Matrix

Target page-level checks:

| Admin route | Required permission |
|---|---|
| `/admin/homepage-settings` | `website.home.manage` |
| `/admin/header-settings` | `website.menu.manage` |
| `/admin/footer-settings` | `website.footer.manage` |
| `/admin/banners` | `website.banner.manage` |
| `/admin/flash-sales` | `marketing.flash-sale.view` |
| `/admin/coupons` | `marketing.coupon.view` |
| `/admin/coupons/create` | `marketing.coupon.manage` |
| `/admin/coupons/{id}/edit` | `marketing.coupon.manage` |
| `/admin/customers` | `customer.view` |
| `/admin/customers/create` | `customer.create` |
| `/admin/customers/{id}` | `customer.view` |
| `/admin/affiliate` | `affiliate.view` |

Page-level middleware is the first layer only.

## Livewire Mutation Authorization Matrix

Every method that changes persistent state must authorize again.

### Homepage

Permission: `website.home.manage`

Protect at minimum:

```text
save
```

UI-only transient methods such as changing the active tab do not need DB capability enforcement by themselves, but any method that uploads, saves, deletes, reorders, enables/disables, or changes persisted content does.

### Header / Menu

Permission: `website.menu.manage`

Protect all:

```text
create
save
update
delete
reorder
toggle active/status
```

or repository-equivalent method names.

### Footer

Permission: `website.footer.manage`

Protect all persisted column/link/social mutations.

### Banner

Permission: `website.banner.manage`

Protect create/update/delete, upload replacement, ordering, enable/disable, and bulk actions.

### Coupon

Read methods/pages: `marketing.coupon.view`.

Mutations: `marketing.coupon.manage`.

Protect create/update/delete/toggle/bulk-delete or equivalent actions.

### Flash Sale

Read methods/pages: `marketing.flash-sale.view`.

Mutations: `marketing.flash-sale.manage`.

Protect create/update/delete/product assignment/status/reorder where present.

### Customers

```text
read/list/detail -> customer.view
create           -> customer.create
edit/update      -> customer.update
delete           -> customer.delete
bulk delete      -> customer.delete
```

Do not infer `customer.update` from `customer.view`.

### Affiliate

Read: `affiliate.view`.

All commission/config mutations: `affiliate.manage`.

## Enforcement Strategy

Use defense in depth:

```text
Admin request
    -> auth:admin
    -> route permission middleware
    -> page/controller
    -> Livewire component
    -> method-level permission check before mutation
    -> persistence
```

Recommended implementation mechanics within the current stack:

1. Keep `auth:admin` on the group.
2. Add Spatie `permission:<permission-name>` middleware to individual routes or route subgroups.
3. Add a small reusable Website Admin authorization helper/trait only if it genuinely reduces repetition.
4. In sensitive Livewire mutation methods, use the authenticated admin-guard user and reject unauthorized calls before persistence.
5. Do not rely on Blade `@can` or hidden buttons as the security boundary.

## Guard Consideration

Both `web` and `admin` guards currently use the same User provider. Phase 1B must therefore test permissions specifically through the `admin` guard.

Do not create a new Admin model/table/guard architecture in this phase. Authentication redesign is outside Phase 1B scope.

## Super Admin Behavior

Preserve a privileged administrator path, but do not scatter hard-coded role-name checks throughout Website components.

Preferred direction:

- central Spatie role/permission assignment;
- `Super Admin` receives all Website/marketing/customer/affiliate permissions through seeding/synchronization;
- components check capabilities, not `if role == Super Admin` repeatedly.

If the project already has a global Gate/Spatie before-hook for Super Admin, reuse it instead of duplicating bypass logic.

## Permission Provisioning

Phase 1B implementation must establish a repeatable way for required permissions to exist.

Preferred options, in order:

1. Extend the repository's canonical permission seeder/synchronizer if one is present and active.
2. Otherwise create a focused Website permission seeder under an appropriate owning module and document how it is invoked.

Do not create permissions opportunistically during web requests.

Do not delete or rename unrelated existing permissions in this phase.

## Test Plan

### Route tests

For each protected surface verify:

- unauthenticated request is denied/redirected;
- authenticated `admin` guard user without permission receives 403;
- authenticated `admin` guard user with permission can access;
- one permission does not accidentally grant unrelated areas.

### Livewire mutation tests

At minimum test representative mutations:

```text
Homepage save
Banner mutation
Coupon mutation
Customer destructive mutation
Affiliate mutation
```

For each representative mutation:

- admin without permission: denied before database change;
- admin with correct permission: allowed;
- database remains unchanged after denied mutation.

### Permission separation tests

Examples:

- `website.home.manage` must not authorize customer deletion.
- `customer.view` must not authorize customer delete.
- `marketing.coupon.view` must not authorize coupon mutation.
- `affiliate.view` must not authorize affiliate configuration mutation.

### Regression

- Existing Website checkout tests remain green.
- Existing Website route configuration tests remain green, updated only where permission middleware is now intentionally expected.
- Current Super Admin runtime still accesses all Website Admin screens after permissions are provisioned.
- Phase 0 frontend behavior remains unchanged.

## Implementation Checklist

### Permission catalog

- [ ] Confirm canonical permission provisioning mechanism.
- [ ] Add Website permission catalog.
- [ ] Add marketing coupon/flash-sale permissions required by current Website-hosted UI.
- [ ] Add customer permissions required by current Website-hosted UI.
- [ ] Add affiliate permissions.
- [ ] Ensure Super Admin receives required permissions.

### Routes

- [ ] Homepage route permission.
- [ ] Header route permission.
- [ ] Footer route permission.
- [ ] Banner route permission.
- [ ] Flash Sale route permission.
- [ ] Coupon route permissions split view/manage.
- [ ] Customer route permissions split view/create.
- [ ] Affiliate route permission.

### Livewire

- [ ] Homepage persisted mutations authorized.
- [ ] Header/menu persisted mutations authorized.
- [ ] Footer persisted mutations authorized.
- [ ] Banner persisted mutations authorized.
- [ ] Coupon persisted mutations authorized.
- [ ] Flash Sale persisted mutations authorized.
- [ ] Customer create/update/delete mutations authorized separately.
- [ ] Affiliate persisted mutations authorized.

### Tests

- [ ] Route allow/deny coverage.
- [ ] Representative Livewire allow/deny coverage.
- [ ] Denied mutation leaves database unchanged.
- [ ] Permission separation coverage.
- [ ] Super Admin compatibility verification.
- [ ] Phase 1A checkout tests remain green.

## Compatibility Boundaries

Phase 1B must **not**:

- move Customers out of Website yet;
- move Coupon/FlashSale to another module yet;
- redesign Admin authentication provider/guard;
- redesign Website database;
- rebuild admin UI;
- rename routes merely for authorization;
- introduce business-feature changes.

Those belong to later phases.

## Phase 1B Exit Gate

Phase 1B may be marked `TESTED / APPROVED` only when:

1. every Website Admin route has an explicit capability decision;
2. every persistent Livewire mutation has a method-level authorization decision;
3. denied route/mutation tests pass;
4. permitted route/mutation tests pass;
5. Super Admin retains operational access;
6. Phase 1A checkout regression tests remain green;
7. manual Website Admin smoke passes for the user's administrator;
8. user explicitly approves Phase 1B.

## Decision

**Recommended: IMPLEMENT Phase 1B using this permission matrix.**

The critical rule is defense in depth: `auth:admin` + route permission + method-level authorization for mutations. Domain ownership remains unchanged until Phase 2.
