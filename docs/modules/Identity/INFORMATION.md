# Identity Information

Identity is the merged domain module for account and user identity management.

## Canonical Names

- Module: Identity
- Route prefix: /admin/identities
- Route names: dmin.identities.*
- Livewire namespace: Modules\Identity\Livewire\Identities
- View namespace: Identity::
- Permissions: *_identity

## Domain Rules

- A user is either employee or customer.
- A user has at most one employee profile, one customer profile, and one identity profile.
- Super Admin users cannot be deactivated or deleted through Identity workflows.
- Identity files and import/export workbooks must not be stored on public disks in future slices.
