# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Task: `/analyze Pharma`
- Branch: `docs/pharma-analyze-refresh`
- Status: **ANALYSIS COMPLETE — DOCUMENTATION ONLY**
- Date: 2026-08-30
- Application source modified: **NO**

## Analysis deliverables

Updated and verified against current repository source:

- `docs/modules/Pharma/ANALYSIS.md`
- `docs/modules/Pharma/INFORMATION.md`
- `docs/modules/Pharma/README.md`

Repository task governance was also aligned so future `/analyze` runs can satisfy the canonical collaboration workflow without treating handoff metadata as an analysis deliverable:

- `.codex/tasks/analyze-module.md`

## Final recommendation

**Major Refactor**

The current module has reusable domain structure and services, so a full rebuild is not justified by the observed evidence. Refactoring should be planned separately and must address high-risk boundaries before broader cleanup.

## Material findings to carry forward

### P0

1. Capability-specific authorization is not consistently enforced at Pharma route/action mutation boundaries.
2. PriceList keeps workbook analysis/product data in public Livewire state, creating an avoidable client-serialization/data-exposure boundary.
3. Pharma business import/export output still uses public-disk storage in shared export paths.

### P1 highlights

- Shared import/export panel still exposes a mutable service selector even though it now validates the service base class.
- Some forms expose raw exception messages.
- DrugBidAward supports effectively unbounded `All` behavior and SupplierTracking can materialize all matching IDs.
- Medicine option lists are loaded without bounded/searchable selection in relevant flows.
- Collection-oriented import/export remains a scalability risk.
- Active public Pharma API route targets an API controller without the required `index` implementation.
- SupplierTracking business-key integrity is not enforced at database level.
- PriceList output-path override requires hardening if exposed beyond trusted callers.

See `ANALYSIS.md` for evidence, file paths, impact and detailed recommendations.

## Documentation drift resolved in this analysis

The refreshed documentation now reflects that:

- the tracked Pharma manifest currently has `enabled => false`;
- current observable Pharma tests are limited compared with older documentation claims;
- Shared import/export service selection has gained base-class validation, narrowing the previous security finding rather than eliminating the mutable-state concern.

## Verification status

Because this task changes documentation/governance only:

- Focused application tests: **NOT APPLICABLE — documentation-only**
- Pharma regression: **NOT APPLICABLE — documentation-only**
- Manual UI smoke: **NOT APPLICABLE — documentation-only**
- Application source/config/schema/runtime changes: **NONE**

Required gate before PR: verify final branch diff remains documentation/governance-only.

## Next phase

**NOT AUTHORIZED**

Do not start Pharma refactor/rebuild implementation from this handoff alone.

After this documentation-only analysis is merged, the next step is to propose a separate Pharma Major Refactor plan, prioritizing P0 findings and coherent MR boundaries. Implementation requires explicit user approval before creating or modifying application source for that phase.
