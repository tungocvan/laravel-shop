# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Objective: **Drug Award Allocation & Hospital Contract Management**
- Branch: `feat/pharma-drug-award-allocation-contracts`
- Status: **IMPLEMENTATION IN PROGRESS — awaiting executable test/UI acceptance**
- Date: 2026-09-05
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- Consolidation: **one implementation branch / one PR preferred**

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
- dangerous cancellation requires a reason and confirmation.

The existing Drug Award HSSP enrichment/provenance indicators (`Bổ sung từ HSSP`, source lineage) are retained.

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

## Verification still required before PR-ready closeout

Do not mark this checkpoint PASS until executable evidence is available for:

- migrations;
- focused Pint;
- new allocation/contract focused test;
- full `tests/Feature/Pharma` regression;
- directly impacted authorization/Partner tests if needed;
- route list;
- frontend build;
- manual UI acceptance for Drug Award list + allocation workspace + contract/cancel/export behavior.

## Prior checkpoint

The Multi-source Drug Intelligence objective was merged to `main` via PR #164 before this branch began. Its canonical ownership, HSSP enrichment and source-provenance behavior remain baseline contracts for this implementation.
