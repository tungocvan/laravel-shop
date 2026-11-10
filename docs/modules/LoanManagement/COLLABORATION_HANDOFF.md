# LoanManagement Collaboration Handoff

Branch: `feat/loan-management-pwa-20261110`

## Database checkpoint — user verified
- Local MySQL `db_laravel_shop`: seven LoanManagement tables present, all zero rows at time of audit.
- Original migration failed because MySQL foreign-key identifier exceeded 64 characters; migration fixed in feature branch using `loan_ins_premium_policy_fk`.
- User verified foreign key/index and recorded migration ledger ID 276, batch 110; `migrate:status` reports Ran.
- User verified Module Manager enabled LoanManagement and route `GET apps/loans` present.
- Foundation tests user reported PASS: 2 tests / 9 assertions.

## Batch 2 — pending local test
- Added LoanContractService (domain-owned lender/contract creation, draft-only editing and outstanding principal summary).
- Added ClientPortal PWA dashboard for lender creation, draft contract creation/editing, and contract overview.
- Added application-protected POST/PUT routes and per-action `web` user permission checks.
- Added focused workspace contract tests. **Not run yet; no TEST PASS claim for Batch 2.**
- Existing data is not seeded or modified by this GitHub batch. No new migrations.
- UI acceptance **PENDING**. No claim of desktop/iPhone PASS.

## Outstanding
- Business feature tests with isolated test database, demo seeder, disbursement, repayments, schedules, insurance, reporting and reminders.
- Improve dashboard scaling/pagination and task-screen navigation.
- UI permission checks and actual rendered PWA acceptance.
- Focused + relevant regression gates, tracked-tree verification, PR review.
- No main merge, production migration, or deployment authorized.

## Batch 3 — transaction checkpoint (PENDING local tests)
- Domain LoanTransactionService: transactional disbursement capped by commitment, draft-to-active on first disbursement; manual repayment schedule capped by committed principal; actual payments capped by disbursed outstanding principal. Locks contract rows to serialize concurrent writes.
- No automatic close or interest accrual: schedules are forecasts, payments are actuals. Interest amounts are explicitly entered, not automatically calculated.
- ClientPortal web-guard endpoints enforce per-action permissions and validate request data.
- PWA overview has collapsible action forms and subtle slate-200 borders on text/number/select fields; desktop/iPhone UI NOT YET VERIFIED.
- Added LoanManagementTransactionsContractTest (static contract; business DB tests still required).
- No new migration; no production action. Do not merge without user UI PASS and regression.

## Batch 4 — insurance + demo seed checkpoint (pending tests)
- Domain `LoanInsuranceService`: policy creation, premium schedule, partial/full premium payments, status by due date, expiry warning within 30 days. Never assumes insurance is mandatory: `requirement` is explicit contractual/voluntary/unverified.
- PWA controller routes enforce `client.loans.insurance.manage`; read UI guarded by `client.loans.insurance.view`. Uses ClientPortal existing application and presentation.
- Insurance panels and forms use thin `border-slate-200` input borders. Real desktop/iPhone UI PASS still pending.
- Opt-in `LoanManagementDemoSeeder` runs only in local/testing; deterministic contract number, no overwrites on rerun. **Do not run until after tests and user confirms demo data may be inserted into local DB.**
- Focused `LoanManagementInsuranceContractTest` added; no tests executed by assistant. Tests are static contracts, NOT integration/financial DB test coverage. Business DB integration tests remain a mandatory gate before merge.
- No schema changes or production actions; no PR or merge.

## Batch 5 — UI workspace refinement (PENDING local tests/UI)
- User screenshot of `/apps/loans` showed dark card borders and a form-first layout. Shared shell is left untouched; styling is scoped under `.loan-workspace` and injected via the existing `application-head` stack.
- Introduced lighter `#e2e8f0` 1px borders, soft focus, subtle shadows, accent buttons, hero hierarchy, anchor navigation, expanded max width and mobile responsive spacing.
- Preserved all lender/contract/disbursement/schedule/payment/insurance forms, actions, permissions and shared PWA date components; no database/schema changes.
- New focused `LoanManagementUiWorkspaceContractTest`. Static source contract only; **no automated tests run by assistant**. User must verify rendered desktop/tablet/iPhone UI and confirm UI PASS before PR/merge.
- Future improvement: move task forms to dedicated focused routes once app navigation/permission contract is reviewed; current anchor sections remain on the single hub.

## Batch 6 — isolated business integration checkpoint (pending local test)
- Added `tests/Feature/ClientApps/LoanManagementBusinessIntegrationTest.php`: creates seven tables from the actual module migration in a dedicated **in-memory SQLite connection**; tests commitment cap, actual-principal cap, duplicate/excess schedules, insurance partial/full premium payments and demo seeder rerun.
- The test explicitly checks SQLite `:memory:` and restores the prior DB connection in teardown. No production/local MySQL business tables should be mutated by these tests.
- Tests NOT run by assistant. If local PHP lacks `pdo_sqlite`, stop and report; do NOT switch tests to MySQL application database.
- Demo seeder remains opt-in and must only run after focused/regression PASS, explicit user consent and verified `APP_ENV=local`; never on production. Demo is illustrative, not accounting evidence.
- Existing Batch 5 UI remains pending real desktop/iPhone acceptance. No PR/merge/deploy.

## Batch 6 debug — 2026-11-10
- User ran 5 SQLite integration tests: all FAIL, 4 because PHP CLI BCMath functions `bccomp/bcadd` are unavailable; one because seeder PSR-4 directory case did not match `Modules\\LoanManagement\\Database\\Seeders`.
- Fixed seeder path from `database/seeders` to `Database/Seeders` (case-sensitive PSR-4). No seeder execution, no business data mutation.
- BCMath is a **required PHP CLI extension** for exact financial arithmetic; do not replace with floating-point operations. Operator must inspect CLI `extension_loaded('bcmath')`, and enable/install extension in the appropriate local PHP runtime before rerunning tests. Do not mutate production PHP/container services without separate approval.
- User reported 11 static contract tests PASS (77 assertions); integration tests remain FAIL until rerun after BCMath is available.

## Batch 6 user checkpoint — PASS
- User installed PHP 8.4 BCMath locally; `BCMATH=YES`.
- User confirmed SQLite business integration: 5 tests PASS (25 assertions); after PSR-4 path contract correction, user confirmed focused and LoanManagement regression PASS (two tiers). No independent CI execution claimed.
- Local `APP_ENV=local` and configured DB `db_laravel_shop` observed, but database safety/permission to insert sample records has NOT been confirmed. Do not run seeder without explicit approval for that DB.

## Batch 7 — full demo seeder (awaiting user Git Pull / Test)
- User approved eight illustrative scenarios: 3 individual lenders, 2 company lenders, 3 credit-institution lenders; four contracts with insurance and four without.
- Seeder remains opt-in, local/testing only; stable per-contract `DEMO-LOAN-20261110-01..08` identifiers, skips existing contracts, never truncates/deletes/overwrites. One DB transaction per run. No new migration, route, permission, PWA UI or production changes.
- Scenario coverage: draft/no disbursement; active/partial and fully disbursed; multiple repayment installments; overdue schedules; fully repaid/closed example; insurance types loan/life/collateral, requirement contractual/voluntary/unverified, premium unpaid/partial/paid, expiring insurance.
- Fixed calendar dates are for reproducibility; actual overdue/expiring badges depend on current date. Status closed is illustrative and NOT proof of accounting reconciliation or automatic closure logic.
- Extended isolated SQLite integration tests for 8-contract matrix, counts, financial bounds, insurance variants, idempotency and protection of unrelated records. Existing insurance contract regression remains in scope.
- **Tests not executed by assistant.** Focused test first; module regression only after PASS; UI PASS still pending. No seeding, PR, merge or deploy authorized.

## Batch 8 — compact contract workspace (awaiting local test and UI)
- User approved professional UI refactor. First controlled UI batch replaces long always-expanded contract cards with accessible native collapsible `details`, plus Alpine client-side search and filters by lender type, status and insurance. Every loaded contract can be found regardless of its original position.
- Controller supplies insurance contract IDs read-only for UI filtering; existing LoanManagement services remain the business source. No routes, migrations, permission changes or financial logic changes.
- Existing transaction/insurance forms, web permissions and shared `x-pwa-date` remain inside the corresponding contract detail; scoped thin borders remain.
- Added focused static UI contract assertions. **Not run by assistant**. Run focused UI test then module contract/integration regression. Real desktop/tablet/iPhone UI PASS pending.
- Known follow-up: server-side pagination for >100 contracts, summary alerts/KPI and separate focused task routes require subsequent controlled batches. Current UI filters only the latest 100 contracts loaded by existing dashboard service; do not misrepresent them as global filters.
- No seeder, production changes, PR or merge performed.

## Batch 8 filter hotfix / simplified UX (awaiting local verification)
- User screenshot shows no contract cards after selecting `Đang vay`; root cause not conclusively established from screenshot. Removed fragile Alpine `x-show` / `x-cloak` dependency and implemented explicit GET server-side filtering of the loaded 100-contract dashboard collection. Search, lender type, status, insurance all validated and applied in controller; reset uses canonical dashboard route.
- Insurance policy premium details now collapsed by default to reduce vertical scrolling, preserving nested payment/create actions.
- Contract cards remain native collapsible details; business forms and permissions preserved. No DB schema/data changes.
- Focused UI contract test updated. **No tests run by assistant; local TEST/UI PASS pending.** If filter or Blade render fails, stop and debug before further UI refactor.
