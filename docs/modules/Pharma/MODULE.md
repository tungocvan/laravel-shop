# Pharma Module Contract

Last reviewed: 2026-09-02

## Purpose

`Pharma` is the domain owner for pharmaceutical product records and the Pharma Admin workspaces used to manage medicine/HSSP records, drug bid awards, supplier tracking, and PriceList generation.

## Canonical ownership

Pharma owns:

- Medicine / HSSP domain records and Admin CRUD workspace.
- Drug Bid Award records, including stable external-source identity metadata used by the sync-ready boundary.
- Supplier Tracking records and their accepted domain-integrity rules.
- Pharma PriceList workbook analysis, validation and generation pipeline.
- Pharma Admin dashboard and routes under `/admin/pharma/**`.
- Pharma-specific import/export mappings and business filters while reusing the Shared Import/Export engine.

## Dependencies

- Direct module dependency: `Shared`.
- Pharma must reuse Shared cross-module infrastructure rather than introduce competing generic import/export or Admin infrastructure.
- Future Muasamcong synchronization must integrate through an explicit boundary; Pharma does not own Muasamcong source data.

## Persistence ownership

Canonical Pharma persistence includes:

- `pharma_medicines`.
- `pharma_drug_bid_awards`.
- `pharma_supplier_trackings`.

Schema ownership must remain inside Pharma migrations. Cross-module writes must use an explicit service/integration boundary rather than writing Pharma tables directly.

## Authorization boundary

- Pharma Admin routes require `web` + `auth:admin` and the appropriate Pharma capability.
- Canonical capabilities are `view_pharma`, `create_pharma`, `edit_pharma`, and `delete_pharma`.
- Livewire mutations must authorize server-side.
- Row selection is a generic workspace capability: users allowed to export selected rows must be able to select rows even when they do not have delete permission.
- Destructive actions remain independently guarded by `delete_pharma`.

## Admin workspace contract

- Canonical entry point: `/admin/pharma`.
- Production list workspaces use bounded page sizes `10/25/50/100`; there is no `All` pagination mode.
- Filtering, pagination, selection, loading, empty and error states must follow `.codex/standards/ADMIN_UI_STANDARD.md`.
- Inputs must have visible boundaries in default/empty state and clear focus, disabled, read-only and validation states.
- Pagination must use the canonical Admin pagination treatment and remain usable on desktop and mobile widths.
- Selection is page-scoped when using the page header checkbox; changing page or filter clears page selection unless a feature explicitly documents otherwise.

## Export contract

For Pharma list workspaces using row selection:

- If one or more row checkboxes are selected, Export exports exactly the selected records.
- If no row checkbox is selected, Export exports the complete dataset matching the active export filters, not only the visible page.
- `selected_ids` takes precedence over ordinary list filters for determining the exported record set, while authorization and domain ownership are still enforced server-side.
- Export selection must not depend on `delete_pharma`; export-capable users can select rows without receiving destructive permissions.
- Generated export files remain private/temporary and use the existing authorized Shared download behavior.

## Accepted domain invariants

- Medicine import/update preserves the accepted unique/business mapping contract.
- Drug Bid Award source projection remains idempotent and uses stable source identity; fuzzy Medicine auto-matching is not part of this contract.
- Supplier Tracking retains the accepted Medicine + normalized supplier + working-date business key for non-null working dates; multiple null-working-date rows remain allowed by design.
- Medicine -> Supplier Tracking cascade-delete behavior is intentionally retained until separately approved.
- PriceList generation remains service-controlled, server-side and private; request/component input must not choose arbitrary production output paths.

## Public/API boundary

Pharma currently exposes no public API contract. `routes/api.php` must not gain public Pharma endpoints without a separately approved objective and authorization design.

## Deferred / quarantined capabilities

The following are intentionally outside this refactor contract and require separate approval:

- Muasamcong -> Pharma production synchronization/wiring.
- Automated fuzzy Medicine matching.
- Pharma production/runtime enablement.
- PriceList database entity/table or queue/background generation.
- User replacement/upload of the canonical PriceList source workbook.
- Changing Medicine -> Supplier Tracking cascade-delete policy.

## Refactor rule

Refactoring Pharma must preserve the domain and persistence boundaries above. Prefer reuse/extraction only where multiple workspaces share the same behavioral contract; do not introduce a generic mega-component that obscures Medicine, Drug Bid Award, Supplier Tracking, or PriceList domain behavior.
