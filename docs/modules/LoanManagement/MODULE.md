# LoanManagement — Module Contract

## Ownership
Owns company borrowing contracts, lenders, disbursements, repayment obligations,
payments, insurance policies and insurance premium schedules. Does not own lending
to other parties or Finance ledgers. No dependency on Finance.

## ClientPortal boundary
ClientPortal owns the web-guard PWA adapter at /apps/loans and /my-apps registration.
LoanManagement owns canonical financial rules and persistence. Domain code must not
depend on ClientPortal.

## Safety
Never auto-seed production. Do not calculate interest on undisbursed commitments.
Do not treat a scheduled payment as settled. Insurance purchase is not presumed
mandatory; store contractual/voluntary/unverified requirement separately.

## Implementation status
Initial schema, interest calculation and manifest foundation only. CRUD, accounting
transactions, authorization, reminders, PWA screens, demo seeder and tests remain
pending and must not be represented as completed.
