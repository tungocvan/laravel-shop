# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Customer/address Legacy Ownership Cleanup**

Status: **VERIFIED — PR READY**

Branch/checkpoint: `refactor/admin-customer-address-ownership`

This slice was explicitly approved after the Post/content ownership cleanup merged. The work focused on proving the legacy Admin Customer runtime unreachable, preserving the canonical Account/User boundaries, and avoiding schema or identity-lifecycle changes.

## Ownership decision

The removed `Modules/Admin` Customer family was a dead/unreachable runtime copy and is now `CLEANED`.

Canonical responsibilities remain split rather than moving the old Customer implementation wholesale into one module:

- `Modules/Account` owns the active admin account workspace at `/admin/accounts`, including account identity editing and `CustomerProfile` behavior through the Account runtime/service boundary;
- `Modules/User` retains `UserAddress` and the existing `user_addresses` schema contract;
- Order history/aggregate behavior remains outside the Account form/index and was not reimplemented during this cleanup;
- Admin remains the authenticated shell and does not own customer identity/profile/address persistence.

No `/admin/customers*` compatibility route was introduced because the legacy Admin Customer controller/routes were not part of the active Admin route boundary and no live repo caller was proven to require them.

## Removed legacy Admin Customer runtime

The following proven obsolete files were removed:

- `Modules/Admin/Http/Controllers/CustomerController.php`
- `Modules/Admin/Livewire/Customers/CustomerCreate.php`
- `Modules/Admin/Livewire/Customers/CustomerDetail.php`
- `Modules/Admin/Livewire/Customers/CustomerTable.php`
- `Modules/Admin/resources/views/livewire/customers/customer-create.blade.php`
- `Modules/Admin/resources/views/livewire/customers/customer-detail.blade.php`
- `Modules/Admin/resources/views/livewire/customers/customer-table.blade.php`
- `Modules/Admin/resources/views/pages/customers/create.blade.php`
- `Modules/Admin/resources/views/pages/customers/index.blade.php`
- `Modules/Admin/resources/views/pages/customers/show.blade.php`

The removed implementation directly mutated the historical application User model, mixed profile/password/status/address/order behavior in Admin Livewire, and included an unbounded `all`/`9999` pagination path. It was not revived or migrated as a unit.

## Canonical runtime retained

The active Account routes remain:

- `/admin/accounts` → `admin.accounts.index`
- `/admin/accounts/create` → `admin.accounts.create`
- `/admin/accounts/{id}/edit` → `admin.accounts.edit`

All three resolve to `Modules\\Account\\Http\\Controllers\\AccountController` under `auth:admin`.

The Account form retains `account_type = customer`, customer-profile handling, and the `AccountService` boundary. `Modules/User/Models/UserAddress.php` and its existing migration remain untouched.

## Authorization / schema assessment

This cleanup does not reuse the legacy `view_customer/create_customer/edit_customer/delete_customer` controller boundary and does not add new customer mutation endpoints.

Authentication/authorization weakening: **NONE INTRODUCED**

Account route URL/name change: **NONE**

Account/User model behavior change: **NONE**

Customer/address schema or migration change: **NONE**

Production data mutation: **NONE**

Order history behavior change: **NONE**

P0 database administration quarantine: **UNCHANGED**

Historical external bookmarks to an old `/admin/customers*` surface cannot be proven from repository source alone; no compatibility redirect is added without a verified contract.

## UI / UX assessment

The removed legacy Customer table's unbounded `all` pagination does not survive this cleanup. The canonical `/admin/accounts` workspace remains the active UI and the user manually verified it as **UI PASS** after the cleanup.

No speculative Account UI redesign was added to this ownership slice because the canonical Account runtime was not changed.

## Verification completed

```text
AdminCustomerOwnershipCleanupContractTest: 7 passed, 44 assertions
AdminCustomerOwnershipCleanupContractTest + AdminOwnershipBoundaryContractTest: 11 passed, 65 assertions
admin.accounts route list: PASS — 3 canonical Modules\\Account\\Http\\Controllers\\AccountController routes
Manual /admin/accounts UI: PASS
```

The focused Customer ownership contract protects:

- absence of legacy Customer registration from the Admin route source;
- the three canonical Account routes/controller and `auth:admin` middleware;
- absence of all ten removed Admin Customer runtime files;
- Account ownership of account/customer-profile runtime through `AccountService`;
- User ownership of `UserAddress` and the existing `user_addresses` schema contract;
- non-reimplementation of Order history/aggregate behavior in Account form/index;
- continued P0 `DatabaseService` quarantine.

No full-project regression was required for this ownership-only slice.

## Acceptance criteria

- legacy Admin Customer runtime reachability: **PROVEN OBSOLETE / CLEANED**;
- ten legacy Admin Customer artifacts absent: **VERIFIED**;
- canonical Account routes preserved: **VERIFIED — 3 routes**;
- Account customer-profile service boundary preserved: **VERIFIED**;
- UserAddress ownership/schema preserved: **VERIFIED**;
- Order history not absorbed into Account: **VERIFIED**;
- schema/migration/data changes: **NONE**;
- Account UI after cleanup: **UI PASS**;
- P0 database quarantine: **UNCHANGED**;
- focused + Admin boundary regression: **PASS**;
- PR readiness: **READY**.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` remains quarantined and must stay unreachable.

### Remaining Admin legacy families

Roles/staff, marketing/public-site, Affiliate/promotion and system/environment remain separate ownership/reachability candidates.

Production migration-ledger/table ownership for unrelated Admin legacy families remains unresolved and out of scope.

## Next phase

Customer/address ownership cleanup is closed out and PR-ready. Do not select or implement the next Admin legacy family until this branch is merged and the user explicitly authorizes the next scope.
