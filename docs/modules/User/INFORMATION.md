# User Module Information

`User` provides legacy admin staff account management at `/admin/user`.

## User-Facing Screens

- Staff list with search, role filter, pagination, Excel import/export, and bulk delete.
- Staff create form with name, email, password, active status, and roles.
- Staff edit form with the same fields except password is optional.

## Main Business Rules

- Staff records are users with at least one Spatie role.
- Non-Super Admin actors cannot see, assign, or import `Super Admin`.
- The current signed-in admin cannot delete their own account through this module.
- Excel import uses `email` as the unique key.
- Excel import default mode is `update_or_create`.
- `created_at` is export-only and is ignored during import.
- Empty roles default to `user`.
- Roles imported from Excel are created on the `admin` guard when missing.

## Ownership Note

This module is compatibility-only. Long-term canonical ownership should move to `Identity`.
