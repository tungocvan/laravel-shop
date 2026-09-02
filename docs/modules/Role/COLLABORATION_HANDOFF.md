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

## Required validation before PR

1. Pint changed PHP files.
2. Focused Role tests, including export scope and bounded pagination/selection behavior.
3. Full `tests/Feature/Role` regression.
4. Route verification for canonical and compatibility routes.
5. `npm run build` because Admin Blade/UI changed.
6. Manual UI smoke desktop + mobile:
   - visible search/name input borders and focus;
   - page size 10/25/50/100 and indigo/white pagination states;
   - responsive table overflow;
   - create/edit permissions;
   - select rows then export selected only;
   - clear selection then export all matching search scope;
   - bulk delete and Super Admin protections.

## Merge gate

Do not create/merge PR until automated validation and required manual UI smoke are reported PASS. Refresh this handoff with final test/UI evidence before PR/merge.
