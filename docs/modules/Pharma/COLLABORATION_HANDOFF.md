# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Objective: **Drug Award Allocation & Hospital Contract Management**
- Branch: `feat/pharma-drug-award-allocation-contracts`
- PR: `#165`
- Status: **PR READY — executable checks and UI acceptance PASS**
- Date: 2026-09-05
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- Consolidation: **one implementation branch / one PR**

## Canonical ownership

The merged Multi-source Drug Intelligence architecture remains unchanged:

1. `Muasamcong` owns procurement canonical data.
2. Pharma `DrugBidAward` owns the Drug Award business canonical projection/snapshot.
3. `Partner` is reused as the organization master for receiving hospitals; phase 1 selects active partners with `legal_type = hospital`.
4. Pharma owns allocation and hospital-contract data below the award.

A receiving hospital is **never** inferred to be the TBMT investor. Allocation/contract mutations do not write procurement source snapshots or Muasamcong data.

## Implemented domain foundation

### Allocation

`pharma_drug_bid_award_allocations` links one Drug Award to one canonical Partner hospital and stores allocated quantity, effective dates, lifecycle state, notes and audit/cancellation metadata.

- `(drug_bid_award_id, partner_id)` is unique.
- cancelled allocations can be reactivated/reused as the same canonical row.
- active allocation total cannot exceed `DrugBidAward.quantity` on user mutation.
- allocation cannot be reduced below committed contract quantity.
- no hard-delete workflow is exposed.

### Contract

`pharma_drug_bid_award_contracts` is separate from allocation and supports many contracts per allocation.

- `contract_quantity` is independent from `allocated_quantity`.
- committed statuses are `signed`, `in_progress`, `completed`.
- committed contract total cannot exceed allocation quantity.
- completed contracts cannot be directly cancelled in phase 1.

### Quantity state

Award quantity is normalized to `decimal(20,4)` to match model/business precision.

Derived allocation state:

- `UNALLOCATED`
- `PARTIALLY_ALLOCATED`
- `FULLY_ALLOCATED`
- `OVER_ALLOCATED` diagnostic only

`remaining_quantity = award.quantity - total_active_allocated_quantity`; negative remaining is intentionally preserved for inconsistency detection.

## Concurrency / integrity

Allocation writes use a database transaction and `lockForUpdate()` on the parent `DrugBidAward`, then recalculate active allocation SUM before writing.

Contract writes use a database transaction and `lockForUpdate()` on the parent allocation, then recalculate committed contract SUM before writing.

This makes Livewire validation advisory rather than the only integrity barrier.

## Authorization boundary

The implementation introduces independent permissions:

- `view_pharma_allocations`
- `manage_pharma_allocations`
- `cancel_pharma_allocations`
- `view_pharma_contracts`
- `manage_pharma_contracts`
- `cancel_pharma_contracts`

`edit_pharma` alone does not grant allocation/contract mutation rights. Permission records are created but are not automatically granted to every existing role.

## UI / export foundation

- Drug Award rows expose **Phân bổ** only to users with allocation view permission.
- Workspace route: `/admin/pharma/drug-bid-awards/{id}/allocations`.
- Workspace keeps procurement context read-only and labels the original party explicitly as **Chủ đầu tư TBMT**.
- KPI: winning, allocated, remaining, hospital count and derived allocation state.
- bounded pagination: `10/25/50/100`; no unbounded `All`.
- checkbox selection is page-scoped.
- allocation and contract CSV exports follow: selected rows when selection exists; otherwise all rows matching active filters.
- controlled pause/cancel actions preserve the domain audit boundary.
- Drug Award filter workspace follows the established `admin/muasamcong/kqlcnt-awards` interaction pattern: searchable dropdowns for TBMT, investor, medicine/HSSP and contractor, with bounded option lists and shared `<x-select-search>`/TomSelect behavior.

The existing Drug Award HSSP enrichment/provenance indicators (`Bổ sung từ HSSP`, source lineage) are retained.

## Canonical hospital management UX

Hospital master data remains owned by `Partner`; Pharma does not introduce a duplicate Hospital model/table.

- **Quản lý bệnh viện** opens the canonical Partner list scoped to `legal_type = hospital`.
- **+ Thêm bệnh viện** opens the canonical Partner create form prefilled with `legal_type = hospital`.
- Partner import accepts `name` + `legal_type` as the minimum hospital identity, keeps optional profile fields, avoids null-tax-code collisions, and retains bounded pagination/export semantics.
- Pharma allocations reference the canonical Partner through `partner_id`.
- Procurement investor remains separate from receiving-hospital allocation.

## Final verification evidence

Current closeout evidence:

- full `tests/Feature/Pharma` regression: **57 tests / 324 assertions PASS**;
- focused Pint on changed Pharma/Partner implementation and regression files: **10 files PASS**;
- frontend build: **PASS**, Vite **34 modules transformed**; subsequent commits were PHP/test/document formatting only and did not modify frontend assets;
- manual UI acceptance: **PASS**, including allocation/contract workspace, canonical hospital entry points, Partner hospital UX and KQLCNT-style Drug Award searchable filters;
- branch comparison against `main`: branch is ahead with no base drift at closeout;
- GitHub reported no commit status checks/workflow runs for this PR head during closeout, so no CI PASS is claimed.

Whole-module Pint still reports legacy style debt in unrelated pre-existing Pharma files. Those files are outside this objective and were intentionally not reformatted to avoid scope creep.

## Deferred scope

Not implemented in this objective:

- delivery / goods receipt;
- inventory;
- invoice reconciliation;
- automatic consumption;
- allocation Excel import;
- AI/fuzzy hospital matching;
- contract amendment engine.

Schema/domain boundaries intentionally leave room for future delivery and amendment entities.

## Merge handoff

PR #165 is ready for manual merge once GitHub reports a mergeable state. No additional application changes are required from the accepted implementation unless GitHub exposes a repository-level merge gate.

## Prior checkpoint

The Multi-source Drug Intelligence objective was merged to `main` via PR #164 before this branch began. Its canonical ownership, HSSP enrichment and source-provenance behavior remain baseline contracts for this implementation.
