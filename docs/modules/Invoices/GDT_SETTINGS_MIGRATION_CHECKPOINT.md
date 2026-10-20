# GDT settings database migration — staged rollout

Branch: `feat/invoices-gdt-db-settings-20261020`.

## Scope

- Seven Admin-managed GDT keys are stored in `invoice_gdt_settings`, owned by Invoices.
- Password is encrypted using Laravel Crypt; do not expose it in logs, UI or CLI.
- Runtime config overrides remain under `invoices.gdt.*`, preserving existing service and job consumers.
- Four advanced tuning keys retain their current config defaults and are outside this Admin settings migration.
- Admin save no longer writes `.env` or clears application configuration.
- The legacy import command is idempotent and offers `--dry-run`; it never prints setting values.

## Rollout gates

1. Pull feature branch and run focused tests before any database operation.
2. Run module and impacted regression only after focused tests pass.
3. Review the migration and back up the relevant database before any approved migration.
4. In a separately approved deployment window, run migration and `php artisan invoices:gdt-settings-import --dry-run`, then import without dry-run after checking the reported key count.
5. Confirm DB-backed settings, Admin permission, captcha/authentication, invoice sync, PDF and queue recovery with real UI smoke.
6. Remove legacy `GDT_*` lines from the local development `.env` only after explicitly approved verification and a rollback plan. Never remove production values in this change.

## Rollback

Retain existing legacy environment values until DB-backed settings have been validated. Code rollback restores the original config behavior; do not drop the settings table or delete encrypted values during rollback.

## Acceptance

Pending local focused tests, module/impacted regression, manual UI PASS and explicit merge approval. No production change is authorized.
