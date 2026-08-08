# Rebuild Specification

## Module

Name the module and the rebuild target.

## Source Of Truth

List documents, files, workflows, database tables, and business rules used to define the rebuild.

## Target Architecture

Describe the intended routes, controllers, Livewire components, Blade views, services, jobs, policies, imports, exports, models, and migrations.

## Public Contracts

| Contract | Current | Target | Compatibility Requirement |
|---|---|---|---|

## Data Model

Describe tables, columns, relationships, indexes, casts, and migration strategy.

## Authorization And Validation

List permissions, policies, gates, request rules, Livewire rules, and service invariants.

## Import Export Contract

Describe file formats, headers, validation, duplicate handling, storage, reports, queues, and cleanup.

## UI Contract

Describe admin pages, Livewire states, empty states, loading states, errors, and permission-denied states.

## Implementation Plan

| Step | Files | Notes | Verification |
|---|---|---|---|

## Risk Register

| Risk | Severity | Mitigation |
|---|---:|---|

## Acceptance Criteria

- Routes and Livewire aliases are documented.
- Authorization and validation are enforced.
- Data writes are transactional where required.
- Imports and exports are bounded and safe.
- Tests or manual verification are complete.
