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
Domain services, CRUD endpoints, disbursement and repayment workflows, insurance,
reminder settings/history, local demo seed/clean commands and feature/contract tests
exist on the current LoanManagement feature branch. They are not evidence of
production deployment or UI acceptance for new refactor batches.

## PWA workspace contract (UI-1 transition)
ClientPortal owns the `/apps/loans` Overview Hub and dedicated browse entry
routes `/apps/loans/{lenders|contracts|payments|reminders|insurance}`.
Each capability declares manifest navigation, permission and presentation defaults.
The Hub remains the canonical discovery surface; focused browse screens hide the
global app header/mobile navigation and provide a local return path.

During UI-1 the dedicated pages are transition shells linking back to the
existing functional dashboard anchors. Existing POST/PUT/PATCH/DELETE endpoints
and financial services remain canonical. Later UI batches may move forms and
lists into focused workspaces without changing financial calculations or
persistence ownership. Do not duplicate business rules in ClientPortal.

## Dependencies and non-ownership
Direct dependencies: Auth, Role, Shared (per module config).
ClientPortal is a consumer/integration adapter, not a domain dependency.
Finance ledgers and unrelated shared user/organization records are not owned
by LoanManagement. UI refactor does not change database schema or domain
data-clean semantics.
