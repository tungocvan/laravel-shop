# Identity Module Analysis

Generated: 2026-06-24

Source modules: Account + User -> Identity

## Purpose

Identity becomes the canonical owner for user login records, employee/customer account profiles, identity and tax profile data, user metadata, user addresses, and admin identity CRUD.

## Source Summary

Account is the richer domain module. It owns admin account listing, create/edit pages, employee profiles, customer profiles, identity profiles, metadata, imports, exports, and account permissions. Its main risks were unenforced permissions, a duplicate user model, unsafe imports, public identity files, unbounded exports, and mismatched import/export contracts.

User is mostly a shell. It provides a duplicate admin CRUD route set, a simple Users model, base users/password reset/user address migrations, and a global user_meta table. It does not provide the richer profile workflow.

## Overlap Mapping

| Account/User concept | Identity decision |
|---|---|
| Account user model + User Users model | Redesign as Modules\Identity\Models\User on users |
| Account routes /admin/accounts + User routes /admin/user | Merge to /admin/identities |
| Account permissions + User permissions | Merge to *_identity permissions |
| user_metas + user_meta | Keep user_metas; retire global user_meta |
| Employee/customer profiles | Keep Account tables and normalize under Identity |
| User addresses | Keep User user_addresses table |
| Password reset tokens | Keep User password_reset_tokens table |
| Account import/export | Redesign; do not copy unsafe importer/exporter |
| Account empty Account model | Retire |

## Conflict Decisions

- Canonical model: use one User model in Identity and align Spatie roles through the normal HasRoles relationship.
- Identity cardinality: keep one identity profile per user, matching the existing unique user_id schema and form behavior.
- Account type: keep only employee and customer in the first Identity rebuild. The old importer's collaborator value is not backed by profile schema.
- Routes: secure web routes with named permissions. API route requires Sanctum and iew_identity.
- Deletion: soft-delete users and detach roles/permissions; do not force-delete users by default.
- Activation: explicit activate/deactivate commands replace toggle semantics.
- Import/export: document a future shared, versioned contract instead of copying the current mismatched implementation.

## Tables

- users
- password_reset_tokens
- employee_profiles
- customer_profiles
- user_identity_profiles
- user_metas
- user_addresses

## Permissions

- iew_identity
- create_identity
- edit_identity
- delete_identity
- import_identity
- export_identity

## Remaining Risks

- Existing application auth may still use App\Models\User; full cutover requires auth-provider and Spatie morph audit.
- Legacy Account/User modules remain in the repository until callers are migrated.
- Import/export implementation is intentionally deferred to the shared import/export contract called out in the roadmap.
