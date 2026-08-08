# Module Roadmap Template

Use this template when creating or updating a module-specific roadmap section.

## Module

Record the module name, canonical owner, and current maturity.

## Current State

Summarize the routes, controllers, Livewire components, services, imports, exports, models, migrations, and database tables that define the module.

## Target State

Describe the desired architecture after planned work is complete.

## Constraints

List security, data integrity, compatibility, migration, UI, storage, queue, cache, and deployment constraints.

## Priorities

| Priority | Task | Risk | Impact | Verification |
|---|---|---:|---:|---|
| P0 | Security or data-loss fix | Critical | Critical | Tests and manual checks |
| P1 | Correctness, architecture, performance, or testability fix | Medium | High | Tests, review, docs |
| P2 | Cleanup or developer experience improvement | Low | Medium | Review and smoke check |

## Implementation Order

1. Stabilize dangerous behavior and authorization.
2. Add or update tests around current behavior.
3. Move duplicated logic to canonical services.
4. Improve validation, transactions, and storage handling.
5. Update UI and documentation.

## Acceptance Criteria

- All changed routes and Livewire actions are authorized.
- Inputs are validated at boundaries.
- Multi-record writes are transactional.
- Imports and exports are bounded and safe.
- Documentation matches code.
- Tests or documented verification cover the changed behavior.
