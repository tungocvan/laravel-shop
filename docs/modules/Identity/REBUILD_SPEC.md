# Identity Rebuild Spec

## Final Architecture

Route -> Controller -> Page Blade -> Livewire -> Service -> Model -> Database

- Routes: Modules/Identity/routes/web.php, Modules/Identity/routes/api.php
- Controllers: IdentityController, Api\IdentityController
- Livewire: Identities\Index, Identities\Form
- Service: IdentityService
- Models: User, EmployeeProfile, CustomerProfile, UserIdentityProfile, UserMeta, UserAddress

## Database Design

The module owns one canonical identity schema:

- users: login, profile summary, account type, activation, OAuth fields, soft deletes.
- employee_profiles: one employee profile per user.
- customer_profiles: one customer profile per user.
- user_identity_profiles: one identity/tax profile per user.
- user_metas: scoped key/value metadata per user.
- user_addresses: delivery/contact addresses per user.
- password_reset_tokens: Laravel password reset support.

The migration is defensive: it creates tables on fresh install and adds missing users columns when users already exists.

## Service Design

IdentityService owns:

- filtered admin pagination capped at 100 rows per page,
- create/update inside transactions,
- profile synchronization by account type,
- single identity-profile synchronization,
- explicit activate/deactivate commands,
- soft deletion with Super Admin protection.

## Livewire Design

- Identities\Index handles search, account type filtering, active-status filtering, bounded pagination, activation, deactivation, and deletion.
- Identities\Form handles create/update with validation for user, profile, identity, and tax fields.

## Permission Design

Web routes require uth:admin and named permissions. Mutating Livewire actions call authorization checks before service calls.

## Import/Export Design

Identity should use Modules/Shared/Services/ImportExport in a later implementation slice. The versioned workbook should contain:

- users
- employee_profiles
- customer_profiles
- user_identity_profiles
- user_addresses
- user_roles

Rules:

- never create missing privileged roles during import,
- never update Super Admin password/status/roles through import,
- store temporary files outside the public disk,
- delete temporary files after processing,
- validate all sheets before writing.

## Migration Strategy

1. Deploy Identity with routes disabled from navigation.
2. Run schema migration after verifying existing Account/User migrations.
3. Seed *_identity permissions.
4. Migrate callers from Account/User routes to Identity routes.
5. Audit auth provider and Spatie morph records before retiring old models.
6. Remove or disable Account/User routes after route tests pass.

## Data Migration Plan

- users remains the canonical table.
- Account profile tables are reused by name and model ownership moves to Identity.
- User user_addresses records are kept.
- User user_meta rows should be copied into user_metas only after a user ownership rule is chosen; current user_meta lacks user_id and cannot be merged automatically.
