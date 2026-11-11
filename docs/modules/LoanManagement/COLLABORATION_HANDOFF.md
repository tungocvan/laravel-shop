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

## Batch 8 — UX refinement and draft lifecycle checkpoint (awaiting local test)
- User approved UI auto-filter and strict lifecycle gate: draft deletion / active-or-cancelled to draft only when **no** disbursements, repayment schedules, payments or insurance policies exist. Closed contracts cannot return to draft. This is a business safety policy, not a blanket state reset.
- Domain `LoanContractService::deleteDraft/restoreDraft` owns checks inside transactions with `lockForUpdate`; referential FKs remain restrict-on-delete. ClientPortal routes DELETE contract and PATCH restore-draft use existing `client.loans.contracts.edit` permission on web guard; no direct state mutation in Blade.
- UI hides destructive actions for contracts with dependencies; server always rechecks to prevent stale UI/races. Confirmation required before delete/restore; draft edit form remains available.
- Filter UX: compact search/type/status/insurance row on desktop; select changes auto-submit GET; search debounced 550ms; reset appears only when filters active; mobile responsive grid. Native GET remains as non-JS fallback via Enter. No new shared PWA assets, migrations, or data modifications.
- Added isolated SQLite integration tests for draft delete and restore safety; updated focused static contract tests. **No tests executed by assistant**; user must run focused then LoanManagement regression. UI acceptance on desktop/tablet/iPhone remains pending.
- Note: filtered set is still limited to the latest 100 contracts loaded by the existing domain dashboard; server-side pagination/global search is future scope. No PR, merge, production action.

## Batch 9 — Full loan profile and actual payment history (checkpoint)
- User reported Batch 8 focused and regression PASS; screenshot showed contract detail only committed/disbursed/outstanding, no history, and individual loans exposed insurance creation.
- User approved read-only full contract detail + three separate histories, and no new insurance for individual lenders.
- New domain-owned `LoanContractHistoryService` fetches actual disbursements, scheduled installments and payments for **visible filtered contract IDs only**, grouping by contract, with BCMath totals for principal/interest/fees. No N+1 per-contract queries, no fictitious accrued interest, no mapping forecast installments to actual payments.
- Dashboard controller passes `loanHistory`; Blade partial `applications/loans/partials/contract-history.blade.php` shows contract terms, principal repaid/outstanding and actual history in compact responsive sections. Existing create-action forms remain intact.
- Insurance creation is forbidden for `loan_lenders.type=individual` at `LoanInsuranceService::createPolicy` (transactional server validation) and hidden on the contract Blade card. Historical insurance policies (including demo 03) are **not deleted or rewritten**; existing policies remain readable, and premium operations for legacy policies are unchanged.
- Added focused SQLite integration coverage for insurance guard and history totals, plus static UI contract checks. **No tests executed by assistant; no TEST PASS/UI PASS claimed for Batch 9.** Local focused tests -> module regression -> real UI desktop/iPhone required.
- Existing dashboard limit of 100 contracts and insurance overview limit of 100 remain unchanged. No migration, production operation, PR or merge.

## Batch 10 — Guided loan setup (checkpoint, local tests pending)
- User approved a guided loan setup for all three lender categories (individual/company/credit institution), including optional auto-generated forecast. This is an incremental enhancement to the existing `/apps/loans` hub, not a new route or schema change.
- Existing `loan_lenders` remain the canonical lender records; wizard filters the lender selector by type. Create lender separately if missing. New loan stays **draft**. No automatic disbursement or actual payment; actual cash movements must be posted explicitly.
- Optional `repayment_mode`: `interest_monthly` (simple interest on committed principal, monthly installments, all principal at maturity), `all_at_maturity` (one principal+interest installment), or `manual` (no generated schedule). Uses the existing `LoanInterestCalculator` with actual days and contract day-count basis 360/365/366, rounded whole VND per installment. Not amortization or compounding. If disbursed in stages or principal changes, generated forecast must be reviewed rather than assumed to be correct.
- New domain `LoanScheduleGenerator` validates draft state, prevents duplicate schedule, limits to 120 monthly periods, creates forecast rows in transaction under contract lock. `LoanContractService::createContractWithOptionalSchedule` wraps draft + forecast in one atomic transaction. ClientPortal controller validates optional mode and delegates; Blade provides simple loan setup and preview with JS progressive enhancement.
- Important business limitation: existing `LoanContractService::updateContract` does not regenerate a previously created schedule; the generated forecast is immutable via wizard, and may need a dedicated reschedule/reconciliation feature later. Existing UI/manual schedule remains unchanged.
- Added SQLite integration tests for all three lender types, no actual cash movement, maturity mode and invalid-mode rollback; static UI contract coverage. **Not executed by assistant; TEST PASS and UI PASS pending.**
- No seeder, production, database migration, merge or deploy.

## Batch 11 — Installment payment allocation & compact UI (checkpoint, TEST/UI pending)
- User approved per-installment payment actions and backend allocation, partial/full settlement statuses, and collapsing secondary forms.
- New additive migration `2026_11_10_000002_create_loan_payment_allocations_table.php`: immutable relationship between actual `loan_payments` and forecast `loan_repayment_schedules`, component amounts principal/interest/fees, restrictive FKs, unique payment+schedule. Existing payment/schedule records are **not migrated, overwritten, guessed, or deleted**. Existing unallocated historical payments remain visible separately, with explicit notice; no automatic reconciliation/backfill.
- `LoanInstallmentPaymentService` locks contract and validates schedule ownership, active contract, positive payment, per-component remaining amount, then invokes existing `LoanTransactionService::pay` within one DB transaction and writes allocation. `pay` now returns inserted payment ID (backward-compatible to void callers). Existing total principal outstanding cap remains authoritative. Repeated/over-amount allocations are rejected by server; race serialized by contract lock.
- PWA route `client.loans.installments.pay` enforces existing `client.loans.payments.create` permission; input validation remains controller-owned, allocation rules domain-owned.
- Dashboard bulk loads allocation totals; read-only per-installment status is based **only on explicit allocations**, not on date/amount coincidences. UI displays remaining principal/interest/fees, partial status, overdue/due/upcoming, payment form prefilled with remaining amounts, confirmation before posting, and collapses old action forms under "Thao tác khác". Payment form supports partial values; history of actual payments remains visible.
- Added isolated SQLite integration tests for allocation partial/full, excess rejection, legacy unallocated payments, and static UI contract. Test setup creates both migrations in memory. **Assistant did not run PHP tests.** Focused then module regression and desktop/iPhone UI PASS still required.
- **Migration gate:** New additive migration must be reviewed and run ONLY in verified local development environment after user approval; never production. Until migrated, new UI query against `loan_payment_allocations` will fail; do not refresh /apps/loans until local migration succeeds. Take local backup if appropriate. No production mutation, no PR, no main merge.
- Limitation: old payments have no allocations; explicit reconciliation UI for old transactions is future work. No automated interest recalculation, no automatic contract closure, no payment reversal/undo. Multi-installment payment is handled by separate explicit transactions, not a hidden automatic allocation.

## Batch 12 — Opt-in repayment email reminders (checkpoint; TEST/UI pending)
- Approved: prefill per-installment actual payment date from that installment's due date (editable, never automatically posts a payment), and per-contract email reminder settings for 7/5/2/0 days before due date.
- Added additive migration `2026_11_10_000003_create_loan_reminders_tables.php`: `loan_reminder_settings` (one per contract, enabled/email/JSON offsets) and `loan_reminder_deliveries` (unique schedule + offset + due-date snapshot; status, timestamps, error). No existing financial tables altered.
- Domain-owned `LoanReminderService` validates offsets and contract, persists opt-in settings, checks active contracts, computes outstanding scheduled principal/interest/fees using explicit payment allocations, skips fully settled periods, and reserves reminders idempotently. `SendLoanReminder` queued job rechecks opt-in, active status, recipient, due-date snapshot, and outstanding before sending; logs sent/error. Existing Laravel Mail and Queue reused. Existing historical payments not automatically reconciled with installments.
- ClientPortal remains thin: `LoanReminderController` uses `client.loans.contracts.edit`, PWA form has per-contract on/off, recipient email and offset checkboxes; `LoanApplicationController` bulk loads settings. `<x-pwa-date>` receives installment due date as default `paid_at` and remains user-editable.
- `loans:dispatch-reminders` command registered through `bootstrap/app.php`, scheduled daily 08:00 only when module enabled and `config('loan_reminders.dispatch_enabled')` is true. `config/loan_reminders.php` defaults `LOAN_REMINDERS_DISPATCH_ENABLED=false`. **Do not enable sending, change .env, run queue/scheduler or dispatch email without separate user approval and environment verification.** No actual email sent by assistant.
- Added SQLite in-memory integration tests for 7/5/2/0 offsets, dedup, partial/full settlement and UI contract. **Assistant has not executed PHP tests; focused/regression and UI PASS pending.**
- Important limitation for follow-up: one email is currently generated per due installment, not aggregated across multiple same-day installments; retry after queue reservation/worker crash requires operational reconciliation, and external email delivery is not exactly-once. Must review before enabling outbound email in production.
- Next checkpoint: pull branch; focused integration/UI tests; module regression. After PASS, user-approved additive migration in verified local DB, desktop/iPhone UI smoke. No merge, no production deploy.

## Batch 13 — Multiple email recipients and overdue suppression (Git Pull/Test checkpoint)
- User explicitly approved option A: **stop sending after due date**, no overdue follow-up email; keep overdue status visible in UI.
- Read-only audit confirmed `main` and feature branch relationship (branch ahead 114 commits, behind 0 at audit), existing Batch 12 single `recipient_email`, delivery unique key without recipient, and worker lacking strict current-date gate. No unrelated module ownership changes.
- Additive `2026_11_10_000004_add_loan_reminder_recipients.php`: new `loan_reminder_recipients` table, copy existing primary recipients, replace delivery unique key with schedule+offset+due-date+recipient; preserves existing delivery rows and historical payment records. Migration rollback is intentionally blocked to prevent silent loss/collapse of multiple recipient audit records; requires reviewed data-preserving rollback if needed.
- `LoanReminderService` owns normalized deduplicated email list (max 10), transactional replacement and per-recipient idempotent reservations. Legacy `loan_reminder_settings.recipient_email` remains synchronized with first recipient for compatibility; recipient table is canonical.
- PWA thin controller validates recipient array, Blade uses Alpine for add/remove and iPhone-responsive inputs, controller loads recipients in bulk. No new route or permission; retains `client.loans.contracts.edit`.
- `SendLoanReminder` checks recipient membership, enabled status, offset, due-date snapshot, contract status, **exact intended send date**, and outstanding at execution; stale/overdue/disabled/settled deliveries marked `skipped`. Does not call `queue:clear`, delete other modules' jobs or purge ledger. Global outbound email remains **disabled by default**.
- Focused SQLite in-memory tests cover multiple recipients, deduplication, existing opt-in offset scenarios, and overdue job skip with Mail fake. UI static contract updated. **No PHP tests executed by assistant; user must run focused tests before regression and report actual output.**
- Production caution: Do not enable outbound Mail/Queue/Scheduler without separate approval. Exactly-once external email delivery and crashed-worker recovery remain operational limitations. Batch 12 UI PASS not explicitly confirmed; Batch 13 UI desktop/iPhone acceptance pending.
- Checkpoint: git pull --ff-only, focused integration/UI tests, then module regression. Additive migration on verified local `db_laravel_shop` requires separate approval; do not migrate production, merge main or deploy.

## Batch 13 MySQL migration 1553 follow-up — pending local retest
- User reported two-layer PHP tests PASS, then local MySQL 8.0.46 `php artisan migrate` failed dropping `loan_reminder_delivery_once_uq`: index required by schedule FK. User supplied read-only `SHOW CREATE TABLE`: recipient table exists with 0 rows, delivery table has 0 rows and old unique index; new unique index absent.
- Fixed migration 000004 to guard existing recipient table, preserve insertOrIgnore backfill, **add new composite unique index first** (retains schedule FK support), then drop old unique index; both index steps check `Schema::getIndexes` for interrupted-DDL resume.
- Added focused SQLite regression verifying idempotent `up()`. MySQL-specific index/FK behavior must be verified on user's local MySQL; assistant did not execute tests or migration. Avoid migrate:fresh, manual FK drop, or destructive rollback.
- Next checkpoint: fetch/pull, focused test, then MySQL local migration after user approval, inspect migration status/index metadata. If any failure, stop for diagnosis. UI PASS/merge/deploy remain pending.

## Batch 14 — Reminder status timeline, checkpoint pending
- User approved a read-only per-contract reminder status UI to distinguish saved settings, planned dates, actual reservation, transport submission, failure and skipped overdue jobs.
- Read-only audit: feature branch ahead of main, not behind at time of audit; domain service creates delivery rows only at due offset, command requires `loan_reminders.dispatch_enabled`; `reserved` alone cannot prove a queue job remains; `sent` cannot prove inbox delivery.
- New `LoanReminderHistoryService` (domain-owned) returns up to 20 recent deliveries per visible contract and 5 upcoming unpaid/unreserved reminder dates, filtered by contract IDs. No new schema, route, job or write operation.
- ClientPortal controller loads history in bulk; Blade renders compact collapsed `Lịch sử nhắc hẹn Email` per contract behind existing `client.loans.contracts.edit` gate. Explicit copy distinguishes planned vs reserved and transport sent vs actual inbox delivery.
- Tests: focused UI contract + SQLite in-memory read-only scoped service test. **Assistant has not executed PHP tests; local focused + module regression and UI desktop/iPhone confirmation pending.**
- No Mail, queue worker, scheduler, migration, main merge or production deploy invoked. Earlier Batch 13 migration MySQL 1553 fix still requires independent local `migrate:status` verification if not already done.

## Batch 15 — Dispatch configuration warning and disable/send serialization (checkpoint pending)
- User verified local MySQL migration 000004 DONE, recipient index present, SMTP test received at approved inbox, database queue worker and schedule:work running, and CLI schedule:list shows loans:dispatch-reminders at 08:00 after local-only LOAN_REMINDERS_DISPATCH_ENABLED=true. These are user-supplied environment checks, not proof of first scheduled run.
- Missing LOAN_REMINDERS_DISPATCH_ENABLED remains fail-closed false. ClientPortal reminder form now displays explicit configuration warning when global dispatch is disabled/missing; UI never edits .env. Global gate remains an operator-controlled deployment setting, not a per-contract toggle.
- SendLoanReminder locks the canonical loan_contracts row in a database transaction through final eligibility check and Mail::raw. LoanReminderService::save locks that same row before changing enabled status, serializing disable with in-flight sends: after disable save returns, a subsequent worker cannot send based on stale enabled state. If a send was already in progress, disable waits for that send to finish. SMTP remains an external side effect; a crash after SMTP acceptance and before DB commit can still cause duplicate delivery on retry. Failure status is recorded outside rolled-back transaction.
- Added focused SQLite integration test for a reserved delivery followed by disabled setting, verifying skipped status and no Mail, plus UI static contract for missing global gate and row-locking. No tests executed by assistant; local focused tests, module regression and desktop/iPhone UI acceptance PENDING.
- No production operations, queue restart, .env write, migration, merge or deploy performed by assistant. No additional database schema change. Checkpoint: pull feature branch, focused tests, module regression only after focused PASS, then manual UI PASS.

## Batch 16 — Priority loan actions before repayment schedule (awaiting local tests/UI)
- Moved existing disbursement and draft-edit forms into contract history immediately after loan profile and before repayment schedule. Responsive two-column layout; original route, permission, status guards, field names and CSRF preserved.
- Secondary accordion retains outside-schedule payment, manual schedule and insurance; no domain/schema changes.
- Added focused source-order contract test. Tests NOT RUN; PHP 8.4 focused/module regression and real UI acceptance required before PR/merge.

## Batch 17A/17B — Financial display and lender category overview (checkpoint pending)
- Read-only user query confirmed existing active HDCN-001 (#5) stores annual_rate_percent=7.99999, basis=365. The display reflects persisted value; no evidence yet that an entered 8 was mutated by the application. No existing contract or repayment schedule was modified; do not silently round persisted 7.99999 to 8.
- Contract service continues to persist rate strings verbatim; SQLite-isolated integration regression added for exact 8.00000 create/update. No changes to financial calculator or database schema.
- Installment form now displays whole VND with thousands separators; on submit, browser validates safe nonnegative integers against remaining amount, confirms, and strips grouping separators for the existing server validation. Defaults are floored (never rounded up) for fractional VND balances; existing server decimal precision remains available for other workflows. Confirm partial payments, zero/over-limit rejection, and mobile keyboard in real UI.
- Domain LoanContractService aggregates count and principal outstanding by individual/company/credit_institution over ALL contracts using grouped subqueries (avoids multiplicative joins), and overall outstanding uses the same full portfolio. Existing latest-100 listing remains unchanged. Dashboard shows category cards and links to existing type filters.
- Focused UI source contract and SQLite-isolated business integration coverage added. Tests NOT EXECUTED by assistant; first php8.4 artisan view:cache, focused tests, then module regression and real desktop/iPhone UI. Existing active contract rate correction requires a separately authorized, auditable financial adjustment workflow. No PR/merge/deploy authorized.

## Batch 17C — Edit future unpaid installments (awaiting checkpoint)
- Source LoanManagement owns LoanScheduleAdjustmentService; ClientPortal exposes authorized PUT /apps/loans/contracts/{contract}/schedules/{schedule}, with inline mobile-friendly Blade edit form for eligible future installments.
- Domain service locks contract and schedule, validates draft/active, original and new due dates strictly future, maturity window, increasing installment date order, no allocation records (including zero-valued), nonnegative amounts from request validation, and total scheduled principal <= committed amount. Updates are transactional; no actual payment changes.
- New migration 2026_11_10_000005 creates loan_schedule_adjustments with before/after JSON, actor and timestamp. Old reserved/failed reminder snapshots are marked skipped on date correction; existing queued jobs also revalidate snapshot against current due date. Already-sent notifications remain immutable historical records.
- Added isolated SQLite integration test for successful correction, audit, chronological rejection and allocated-payment rejection; updated SQLite migration bootstrap and UI source contract.
- Do NOT run migrations on production. Local database migration requires explicit separate approval after focused SQLite tests pass. No test or UI PASS claimed. Check php8.4 artisan view:cache, focused tests, impacted regression; desktop/iPhone UI only after migration authorized. No PR/merge/deploy.


## Batch 17D — controlled demo reset (awaiting local tests)
- Branch: `feat/loan-demo-reset-20261111` based on `feat/loan-management-pwa-20261110`, which is ahead of main and contains the unmerged LoanManagement feature. Do not merge independently of the parent feature.
- New opt-in CLI command `php8.4 artisan loans:demo-seed` seeds eight fixed illustrative scenarios without replacing existing contracts; `--delete` previews related record counts, requires an interactive `RESET-DEMO` confirmation, and atomically deletes/reseeds only the eight exact canonical `DEMO-LOAN-20261110-01..08` contract identifiers.
- Reset deletes dependent allocations, reminder deliveries/settings/recipients, schedule adjustment audit rows, insurance premiums/policies, actual payments, schedules and disbursements in FK-safe order. Shared lenders remain if referenced by any unrelated contract. Cross-contract payment allocations block reset entirely.
- Reset is restricted to local/testing by runtime environment guard; this is not a production cleanup mechanism. No migrations, web routes, UI, configuration or real contract data are intentionally changed. The command is discovered by the existing module console registration when LoanManagement is enabled.
- Added isolated in-memory SQLite integration coverage for reset, foreign keys, shared lender protection, cross-contract guard and non-interactive refusal. **Tests not executed by assistant; no TEST PASS or UI PASS claimed for this batch.**
- User checkpoint: pull this child branch, run focused `LoanManagementBusinessIntegrationTest.php`, then LoanManagement module regression only after focused PASS; verify tracked working tree. Do NOT execute `--delete` against MySQL until the user separately approves an actual demo data reset.
- Prior Batch 17C: user confirmed focused tests 36 passed / 353 assertions, module regression and UI PASS, and migration 000005 DONE on local MySQL.


## Batch 17D correction — --delete means delete only (pending tests)
- User clarified `loans:demo-seed --delete` must permanently remove the eight canonical demo contracts and their related rows **without reseeding**. Prior behavior (`resetAndSeed`) was incorrect and is superseded.
- CLI now prompts for `DELETE-DEMO`; normal `loans:demo-seed` remains the only opt-in create path. `LoanDemoResetService::deleteDemo()` deletes in a transaction and preserves unrelated contracts and shared lenders. Repeated delete is idempotent.
- Existing pre-correction tests and real MySQL reset PASS do **not** verify this corrected behavior. Run focused SQLite integration and module regression on updated commit, then explicitly approve and run the destructive local MySQL command. Expected post-delete demo contract count: zero; reminder settings/recipients/deliveries for those demo contracts: zero.
- No merge, production operation or CLI execution performed by assistant.


## Batch 17E — full LoanManagement data clean (PENDING local tests)
- User explicitly authorized a command to delete **all** LoanManagement-owned business records, including actual individual, organization, and credit institution lender details, contracts, payments, insurance and reminders. This is distinct from canonical demo-only deletion.
- New `loans:data-clean --all` command and `LoanDataCleanService`: local/testing only, prints per-table counts, refuses noninteractive execution, requires exact `DELETE-ALL-LOANS` confirmation, deletes 12 LoanManagement-owned tables in FK-safe order within one DB transaction. Does not reseed, drop schema, disable FK checks or touch shared users/organizations outside the module.
- Unexpected external FK dependencies should fail the transaction rather than being force-deleted. No job queue purge or recall of already-sent email; pending loan reminder jobs must be assessed separately before running a live cleanup.
- Tests added for all-record deletion (including non-demo), repeated deletion and missing confirmation. Tests **NOT run by assistant**; user must run focused and module regression before destructive local execution. UI PASS not implied. Do not merge/deploy without approval.

 
## Batch UI-1 — Workspace navigation foundation (PENDING TEST/UI)
- Approved Plan A: Overview Hub + five capability browse entries: lenders, contracts, payments, reminders, insurance.
- Branch: `feat/loan-workspace-navigation-20261111` created from `feat/loan-demo-reset-20261111` (parent feature remains unmerged into main at audit). No worktree.
- Added manifest-managed capability metadata/navigation, dedicated GET browse routes and authorization-checked `LoanCapabilityController`, focused Blade shell with local return link, Overview discovery links. Transition shells intentionally link to existing functional dashboard anchors; full business workspace migration belongs to UI-2 through UI-4.
- Preserved all existing mutation endpoints, domain services, database/migrations and legacy dashboard forms. Updated MODULE.md to resolve stale status/ownership description. Added `LoanManagementCapabilityNavigationContractTest`.
- Focused tests NOT RUN by assistant. Suggested: `php8.4 artisan test tests/Feature/ClientApps/LoanManagementCapabilityNavigationContractTest.php tests/Feature/ClientApps/LoanManagementUiWorkspaceContractTest.php tests/Feature/ClientApps/LoanManagementFoundationContractTest.php`. Only after PASS run LoanManagement module regression and impacted ClientPortal tests.
- UI acceptance required for desktop/tablet/iPhone, capability access/403, Overview return, legacy form continuity. No UI PASS claim. No PR, merge, production change or data cleanup performed.

 
## Batch UI-2 — Lender workspace + contract creation wizard (PENDING TEST/UI)
- User confirmed UI-1 TEST two layers + UI PASS, approved UI-2 through Git Pull/Test.
- Branch `feat/loan-workspace-lenders-wizard-20261111` created from UI-1 feature branch; parent LoanManagement branches not yet merged into main.
- Lenders: dedicated list, client-side search/type filter, responsive modal, existing POST service and permission; JSON response for inline wizard lender creation while legacy HTML redirect remains.
- Contracts: dedicated GET `/apps/loans/contracts/create` guarded by create permission, three-step wizard with native validation, summary and existing preview script shared with Overview. Inline lender modal preserves entered contract values. Existing contract store endpoint, finance domain logic, migrations and mutation routes retained.
- Redirect to relevant focused workspace only for explicitly sourced forms. Removed redundant Overview capability navigation (shared shell sidebar remains). Contract list remains a UI-1 transition shell; UI-3 owns full list/detail migration.
- Focused tests added `LoanManagementWorkspaceCreationContractTest`; updated UI-1 navigation contract for removed duplication. No tests run by assistant; no UI PASS claimed.
- Checkpoint: git fetch/switch/pull, focused `LoanManagementWorkspaceCreationContractTest.php`, `LoanManagementCapabilityNavigationContractTest.php`, `LoanManagementUiWorkspaceContractTest.php`; then module regression and impacted ClientPortal regression if PASS. UI check desktop/tablet/iPhone, create lender modal and wizard, server validation, permission 403, legacy forms, no console errors. Do not merge/deploy without approval.

## UI-2 lender modal regression — pending local verification
- User confirmed two test layers PASS but UI FAIL: modal visible, clicks not functional. Browser inspection: input/close are topmost click targets, Alpine `window.Alpine` absent and `_x_dataStack` undefined. Source `resources/js/tailwind.js` does not load Alpine; npm package.json does not declare Alpine.
- Replaced Alpine-dependent lender modal/search/filter with scoped native JS; retained POST, validation, focus return, Escape/Tab and outside close. Added scoped hidden-display rule and updated contract assertions. No changes to finance data, routes or schema.
- **Remaining risk**: contract-create wizard also uses Alpine directives and must be audited/fixed before UI-2 overall PASS; do not certify wizard from lender-only retest.
- Focused tests not rerun by assistant; request local focused then module regression and manual UI retest.
