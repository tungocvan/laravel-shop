# Demo — Module Contract

Module type: domain. Status: transitional.

The Demo module owns routes, controllers, views and source assets of migrated demo projects.
Phase 01 owns Rental Room. Eleven other demos remain unchanged.

Direct module dependencies: none. Shared sidebar endpoints stay in their existing application location during transition.

Canonical Rental Room URL and route names remain unchanged. Browser storage, private JSON snapshots and asset URLs remain unchanged. Rental Room CSS/JS are served by a strict allowlisted Laravel asset route from Modules/Demo/resources/assets; no Rental Room public asset copies are retained. No database migration.

REHOME: rental-room controllers, views, routes, source assets.
KEEP: shared menu, runtime data, public assets belonging to the other 11 demos, remaining demos.
DEFER: other demo migrations and shared-menu relocation.
QUARANTINE: runtime data and untracked files.

Regression: focused route, dataset, shared-menu and UI checks. Rollback through Git without deleting runtime data.

## Shared UI foundation — staged adoption

- Canonical module-owned shared menu editor CSS/JS: `Modules/Demo/resources/assets/shared/menu-editor.{css,js}`, served by allowlisted Rental Room Asset Route.
- Rental Room is first consumer; remaining 11 demos keep their existing implementations until individually migrated and regression-tested.
- Menu API still belongs to existing `SharedSidebarMenuController` during transition; no duplicate persistence implementation.
- Theme palettes remain demo-specific for now; shared theme selector API is a future opt-in contract, not a claim of 12-demo parity.

## Shared layout/sidebar/theme pilot (Phase 01)

- Module-owned `demo::layouts.app` defines shared HTML document shell with Blade stacks for demo-specific styles, data and scripts.
- Module-owned `demo::components.sidebar` renders configurable brand, nav, card and footer partials. Rental Room retains original DOM IDs and badge elements in its own partials.
- Module-owned `resources/assets/shared/theme-selector.js` owns the selector and five Rental Room palettes; other demos can opt in with their own theme configuration in later phases.
- Other 11 demos remain untouched; do not infer full shared layout adoption. No route/persistence/API contract changes.

## Phase 02 — Hotel Uniform

- Canonical Hotel Uniform UI, dataset API, Blade and CSS/JS now belong to `Modules/Demo`; the original `/demo/quan-ly-dong-phuc-khach-san` and dataset URLs remain unchanged.
- Shared module layout/sidebar and menu editor are reused; the five Hotel Uniform palettes remain in its demo-specific integration because their CSS variable contract differs from Rental Room. Shared theme-selector convergence is deferred pending visual regression, rather than silently changing the palette.
- Existing browser storage keys, private `demo-datasets/hotel-uniform` snapshots and shared menu API remain unchanged. No database migration.
- Tracked legacy controller, view and public asset copies are retired. Allowlisted module asset route retains existing browser asset URLs. Check web-server static fallback and stale untracked public files before deployment.
- Eleven other demo ownership boundaries are unchanged except Hotel Uniform, leaving ten legacy demos.
