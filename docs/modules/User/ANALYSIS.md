# User Module Analysis

## Purpose

`User` is a legacy staff-account compatibility module for `/admin/user`. It lists, creates, edits, deletes, imports, and exports users that have Spatie roles.

The richer canonical direction is documented in `docs/modules/Identity`: `Identity` should become the long-term owner for user login records, profiles, identity data, metadata, addresses, and admin identity CRUD. Until that cutover is verified, `User` keeps the existing staff CRUD route surface.

## Current Routes

- `admin.user.index`: `GET admin/user`
- `admin.user.create`: `GET admin/user/create`
- `admin.user.edit`: `GET admin/user/{id}/edit`

Routes use `web`, `auth:admin`, and named permission middleware.

## Components

- Controller: `Modules\User\Http\Controllers\UserController`
- Livewire table: `Modules\User\Livewire\UserTable`
- Livewire form: `Modules\User\Livewire\UserForm`
- Service: `Modules\User\Services\UserService`
- Page views: `Modules/User/resources/views/pages/staff/*`
- Livewire views: `Modules/User/resources/views/livewire/*`

## Data

- Canonical runtime model: `App\Models\User`
- Legacy module model shell: `Modules\User\Models\Users`
- Tables touched: `users`, `roles`, `model_has_roles`
- Migrations in this module also create `password_reset_tokens`, `user_addresses`, and `user_meta`.

## Risks

- `User` overlaps with `Account` and `Identity` ownership of the `users` table.
- Import/export now uses the shared Excel foundation via `Modules\User\Services\ImportExport`.
- The module still depends on `App\Models\User` because the auth provider and Spatie morph strategy have not been fully cut over to `Identity`.
