# Role — Collaboration Handoff

## Current objective

Major/Clean Module Refactor for `Modules/Role`, consolidated into one implementation batch to reduce repeated pull/test cycles.

## Approved scope

- Establish missing durable `MODULE.md` contract.
- Preserve canonical `/admin/roles*` routes and legacy redirects.
- Refactor permission-catalog sync out of `RoleTable` into a Role service boundary.
- Standardize Admin list/form UI, especially visible input boundaries and bounded pagination.
- Enforce export semantics: selected IDs => selected only; no selection => all matching current filters, never current page only.
- Preserve Super Admin, in-use role, historical permission and admin-guard invariants.

## Architecture classification

- KEEP: canonical Role routes, RoleController, RoleService, RoleForm, RoleDirectory, ImportExport, Super Admin protections.
- REHOME: permission-catalog sync mechanics from Livewire to `RolePermissionCatalogService`.
- QUARANTINE: Spatie Permission persistence and historical assignments.
- DEFER: legacy redirect removal until caller proof exists.
- DELETE: none in this slice.

## Implementation status

Branch target: `refactor/role-architecture-ui-export-boundaries`

Implemented in the consolidated batch:

- Role architectural contract.
- Role permission-catalog service adapter.
- RoleTable pagination/selection/export filter state cleanup.
- Selected-vs-filtered-all export service contract.
- Shared ImportExport panel nested under RoleTable so reactive filters follow Role selection/search state.
- Admin input, table overflow, loading state and module-scoped pagination UI normalization.

## Final validation evidence

Validation completed on 2026-09-02:

- Pint changed-files gate: PASS — 4 files checked, 0 style issues.
- Role regression: PASS — 14 tests, 36 assertions.
- Export contract: PASS — selected IDs export selected roles only; no selection exports all roles matching current filter scope, not only the current page.
- Bounded pagination contract: PASS — unbounded/tampered page size is normalized.
- Canonical route verification: PASS — `admin.role.index`, `admin.role.create`, `admin.role.edit` are present under `/admin/roles*`.
- Frontend build: PASS — Vite production build completed successfully.
- Manual UI acceptance: PASS — user verified Role UI after refactor, including the Admin list/form experience, pagination/export flow, and protected-role behavior.

## Merge gate

PASS. Automated validation and required manual UI smoke are complete. The branch is ready for pull request review and manual merge.
