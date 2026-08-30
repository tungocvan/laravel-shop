# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Address Legacy Ownership Cleanup**

Status: **IMPLEMENTED — FOCUSED VERIFICATION PASS / PR READY**

Branch/checkpoint: `refactor/admin-address-ownership-cleanup`

This approved slice removes independent UserAddress persistence/business ownership from Admin while preserving compatibility for historical class callers. It does not delete compatibility classes, change schema/migrations/data, or expand into historical controllers or other Admin legacy families.

## Ownership decision

Address ownership remains split consistently with the established Customer/Account boundary:

- `Modules/User` owns canonical `UserAddress` persistence and address mutation behavior;
- `Modules/Account` owns account/customer-profile workspace where applicable;
- `Modules/Admin` remains the authenticated shell and must not independently implement address persistence/business rules.

## Runtime changes

### Admin model compatibility

`Modules/Admin/Models/UserAddress.php` is now a deprecated compatibility alias extending `Modules\User\Models\UserAddress`.

It no longer declares its own table, fillable fields, casts, relationships, or address helper implementation. Dynamic/external callers using the historical Admin namespace can continue resolving the class while persistence ownership remains in User.

### Admin service compatibility

`Modules/Admin/Services/AddressService.php` is now a deprecated compatibility facade over `Modules\User\Services\UserAddressService`.

The historical public API is retained:

- `getUserAddresses($userId)`;
- `create($userId, array $data)`;
- `update($addressId, $userId, array $data)`;
- `delete($addressId, $userId)`;
- `setDefault($addressId, $userId)`.

The facade delegates mutations and queries to the canonical User service instead of duplicating default-address mutation logic inside Admin.

The historical `delete()` contract continues to return a boolean compatibility result after canonical deletion.

## Schema and data decision

No schema, migration, foreign-key or production-data change is authorized or included.

The existing `user_addresses` persistence contract remains User-owned. Runtime ownership cleanup does not authorize moving or renaming applied migrations or tables.

## Verification

Local focused verification reported:

```text
AdminAddressOwnershipContractTest: 4 passed, 28 assertions
AdminOwnershipBoundaryContractTest: 4 passed, 21 assertions
Total: 8 passed, 49 assertions
Working tree: clean
```

`tests/Feature/Admin/AdminAddressOwnershipContractTest.php` protects:

- canonical User model ownership;
- Admin model compatibility-only inheritance;
- Admin AddressService delegation to UserAddressService;
- preservation of the historical Admin service public API;
- canonical User ownership of address mutations.

## Acceptance criteria

- canonical UserAddress model owner: **User — VERIFIED**;
- canonical address mutation owner: **User — VERIFIED**;
- independent Admin address persistence/business logic: **REMOVED**;
- historical Admin class compatibility: **PRESERVED**;
- Admin Address compatibility classes deleted: **NO — caller proof remains incomplete**;
- Account/customer-profile boundary: **UNCHANGED**;
- schema/migration/data changes: **NONE**;
- Admin shell ownership guardrail: **PASS**;
- focused regression: **PASS — 8 tests / 49 assertions**;
- manual UI smoke: **NOT REQUIRED by this compatibility-only slice unless a historical caller is later proven reachable**.

## Remaining compatibility debt

This slice does not claim zero callers for the deprecated Admin Address namespaces. Repository code-search indexing has previously proven unreliable, so the compatibility classes remain until stronger dynamic/external caller proof authorizes deletion.

Other compatibility debt remains separately scoped:

- historical Admin controllers/scaffolds;
- Banner/Header compatibility adapters;
- Flash Sale compatibility adapters;
- Affiliate compatibility adapters and OrderDetailModal residue;
- Environment/System settings adapters;
- quarantined Admin database family.

Database P0 containment remains unchanged and must not be reopened as part of compatibility cleanup.

## Next phase

Open and merge this Address ownership cleanup as a focused PR. Do not begin another Admin legacy family until this branch is merged and the next scope is explicitly approved.

After merge, resume Compatibility Debt Audit from historical controllers / Order-Affiliate residue and choose exactly one coherent next implementation slice.
