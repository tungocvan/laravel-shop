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
