# Admin Rebuild Decision

Generated: 2026-06-22

## Final Recommendation

Decision:
- Safe rebuild

Reason:

`Modules/Admin` should remain the admin shell, but the current source is not safe to keep as-is. It contains unauthenticated API exposure, active routes without named permissions, dangerous database backup/restore/truncate/drop behavior, raw exception leakage, shell command strings with database credentials, domain logic duplicated inside Admin, unbounded import/export paths, malformed migration filenames, namespace mismatches, and broken service method contracts.

A full rewrite is not recommended because stable shell pieces can be preserved: the Admin module manifest, layout, dashboard/menu/profile/header/theme route shape, thin shell controllers, sidebar/header partial concepts, and header menu models can be retained after hardening.

Risk level:
- High

Suggested next step:

Start with a P0 containment slice before any feature rebuild:

1. Remove or protect `Modules/Admin/routes/api.php`.
2. Disable database export/backup/restore/truncate/drop/download actions until named permissions, server-owned identifiers, audit logs, safe process execution, redacted errors, and tests exist.
3. Add named permissions to active Admin web routes and mutating Livewire actions.
4. Confirm canonical ownership for database administration, settings, menu/category data, user addresses, product import/export, affiliate, banner, flash sale, chat, role/staff, and Website-facing content.
5. After containment, rebuild Admin menu management around a shell-owned service and move domain behavior to canonical modules.

## Decision Notes

Keep:

- Admin as a `shell` module.
- Admin layout and shell partials.
- Dashboard/menu/profile/header/theme page composition.
- Thin active shell controllers.
- Header menu model/table concept if ownership is confirmed.

Refactor:

- Active routes and Livewire actions to use explicit named permissions.
- Menu Livewire components into service-backed workflows.
- Header/theme/profile components for authorization and service boundaries.
- Address handling only if Admin remains the confirmed owner.
- Reusable UI components after cross-module usage verification.

Safely rebuild:

- Menu import/export/restore and bulk actions.
- Admin authorization boundary.
- Module ownership boundaries.
- Migration compatibility plan.
- Any retained system/database operations.

Rewrite from scratch:

- Current database backup/restore/truncate/drop implementation.
- Full database restore flow.
- Admin-owned product import/export.
- Broken namespace/service-contract areas after ownership is decided.

Do not implement until verified:

- Exact permission names and seed location.
- Whether database administration belongs in Admin or System.
- Whether shell menus remain in `categories` or move to a dedicated admin menu table.
- Production migration history for negative-year migrations.
- Import/export formats and sample files.
- Test locations and first security regression slice.

