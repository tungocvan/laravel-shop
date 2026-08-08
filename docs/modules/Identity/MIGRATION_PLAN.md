# Identity Migration Plan

## Old Tables To New Tables

| Old source | New target | Rule |
|---|---|---|
| users | users | Keep table; add missing Identity columns |
| employee_profiles | employee_profiles | Keep rows; Identity becomes owner |
| customer_profiles | customer_profiles | Keep rows; Identity becomes owner |
| user_identity_profiles | user_identity_profiles | Keep rows; one row per user |
| user_metas | user_metas | Keep rows |
| user_addresses | user_addresses | Keep rows |
| password_reset_tokens | password_reset_tokens | Keep rows |
| user_meta | no direct automatic target | Manual audit; table has no user_id |

## Field Mapping

- users.name -> users.name
- users.email -> users.email
- users.phone -> users.phone
- users.avatar -> users.avatar
- users.account_type -> users.account_type
- users.is_active -> users.is_active
- employee_profiles.* -> employee_profiles.*
- customer_profiles.* -> customer_profiles.*
- user_identity_profiles.* -> user_identity_profiles.*
- user_addresses.* -> user_addresses.*
- user_metas.* -> user_metas.*

## Data Merge Logic

- Duplicate users are resolved by normalized email.
- If Account and User disagree on profile fields, Account wins for domain profile data because it has the richer workflow.
- Employee and customer profile uniqueness remains based on user_id plus profile code uniqueness.
- Identity records are merged by user_id; if more than one old record exists, keep the latest non-deleted record and archive the rest before adding strict uniqueness.
- Super Admin users must not be deactivated, deleted, or have roles overwritten.

## Rollback Plan

- Keep Account and User modules untouched until Identity routes, policies, and data checks pass.
- Take a database backup before running any production data migration.
- Roll back navigation and route registration first if Identity has issues.
- Do not drop old tables in the first release; remove duplicate modules only after read/write parity is verified.
