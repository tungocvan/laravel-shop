# Finance module

Finance owns financial accounts, payment vouchers, allocations and ledger. Pharma owns inventory documents. Request owns request workflows.

Finance depends on Admin, Auth, Role and Shared. Pharma and Request are optional sources.

FinancialDocumentProvider and FinancialDocumentRegistry provide a module-independent integration boundary. Source eligibility must be verified before any financial posting. A posted inventory document is not necessarily a receivable or payable.

Current implementation is a foundation only. No posting, UI, Pharma adapter, Request adapter or production migration has been implemented.

## Posting service

VoucherPostingService posts existing draft vouchers with source allocations only after the provider validates each source and the snapshot matches. It locks voucher, account, allocations and source rows, checks outstanding amount against posted allocations, currency and direction, then inserts a signed ledger entry and marks posted in one transaction. Reversal appends the opposite ledger entry and marks reversed. No UI or direct manual posting endpoint is exposed in this batch.
