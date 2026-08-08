# Deployment Review Checklist

- `composer install --no-dev --optimize-autoloader` is supported.
- `npm run build` succeeds when assets change.
- Migrations are reversible or have a documented rollback plan.
- Queues required by the change are documented.
- Storage directories and disks are documented.
- Cache and config clear steps are documented when needed.
- New permissions are seeded or migration-safe.
- Scheduled cleanup tasks are documented.
- Environment variables are documented without values.
- No generated docs contain secrets or personal data.
- Verification commands and results are recorded.
