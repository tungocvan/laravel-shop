# Website Phase 2D — User / Account / Address Ownership

## Status

- Slice: `2D — User/account/address ownership`
- Implementation: `COMPLETE`
- Automated tests: `PASS`
- Manual account/customer smoke: `PASS — user verified`
- Approval: `APPROVED`
- Decision: `CLOSED`

## Ownership Decision

- Runtime identity remains `App/Models/User`, shared by the current web/admin guards.
- `user_addresses` is canonically owned by `Modules/User`, matching the existing User migration that creates the table.
- Orders related from User now resolve to canonical `Modules/Order/Models/Order`.

## Implemented

- Added canonical `Modules/User/Models/UserAddress` with the existing table, fillable fields, boolean cast, User relation and full-address accessor.
- Added transactional `Modules/User/Services/UserAddressService`.
- Every lookup/mutation is scoped by `user_id`; update/delete/default changes lock the addressed row.
- Preserved the single-default behavior when creating, updating, deleting or selecting a default address.
- Migrated Website account address Livewire to the User-owned service.
- Migrated customer-admin address reads to the canonical model and mutations to the canonical service.
- Preserved Phase 1B `customer.update` authorization at each admin mutation method.
- Updated `App/Models/User` address and order relationships to canonical models.
- No database migration or route/UI redesign was introduced.

## Remaining Boundary

- The old Website UserAddress model and AddressService remain temporarily for Slice 2E zero-caller verification/removal.
- ProfileService stays under Website until the broader account/profile ownership contract is evaluated during cleanup; this slice changes address ownership only.

## Manual Test Required

- Customer account: list, create, edit and delete an address.
- First address becomes default automatically.
- Change default address and verify only one remains default.
- Delete the default address and verify another address becomes default.
- Admin customer detail: open/edit/create/delete address.
- Verify an address ID belonging to another user cannot be modified.
- Confirm customer update permissions still deny lower-privilege accounts.
