# Identity Refactor Plan

## Phase 1 - Adopt Canonical Ownership

- Update auth/provider configuration and module callers to use one canonical user model.
- Add route and Livewire tests for Identity permissions.
- Move navigation from Account/User to Identity.

## Phase 2 - Import/Export

- Implement a shared import/export adapter using Modules/Shared/Services/ImportExport.
- Add fixture-based validation for all workbook sheets.
- Store import/export artifacts privately and clean them up.

## Phase 3 - Retire Duplicates

- Disable Account/User routes after parity tests pass.
- Remove Account empty scaffold model and User shell CRUD.
- Add architecture tests to prevent new duplicate user/account modules.

## Phase 4 - Hardening

- Add policy classes or gates for every mutating action.
- Add migration smoke tests for fresh install and existing-database upgrades.
- Add query-count tests for identity listing.
