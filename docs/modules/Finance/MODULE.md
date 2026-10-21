# Finance module

Finance owns financial accounts, payment vouchers, allocations and ledger. Pharma owns inventory documents. Request owns request workflows.

Finance depends on Admin, Auth, Role and Shared. Pharma and Request are optional sources.

FinancialDocumentProvider and FinancialDocumentRegistry provide a module-independent integration boundary. Source eligibility must be verified before any financial posting. A posted inventory document is not necessarily a receivable or payable.

Current implementation is a foundation only. No posting, UI, Pharma adapter, Request adapter or production migration has been implemented.

## Posting service

VoucherPostingService posts existing draft vouchers with source allocations only after the provider validates each source and the snapshot matches. It locks voucher, account, allocations and source rows, checks outstanding amount against posted allocations, currency and direction, then inserts a signed ledger entry and marks posted in one transaction. Reversal appends the opposite ledger entry and marks reversed. No UI or direct manual posting endpoint is exposed in this batch.

## Optional Pharma integration

When Finance and Pharma are enabled, FinanceServiceProvider registers PharmaReceiptProvider for `Pharma:inventory_receipt`. It resolves only posted inventory receipts with invoice number, invoice date and complete positive invoice unit prices. It calculates a VND payable snapshot with VAT from receipt items using SQL DECIMAL and does not change Pharma records. The invoice-backed rule is provisional and requires business/UI acceptance before financial use. Generic inventory issues are intentionally excluded; Request remains deferred. Provider resolution fails closed when Pharma is disabled.

## Pharma posted-document rule (approved 2026-10-21)

Both posted Pharma inventory receipts and posted inventory issues may be selected for Finance. Receipts are payable/payment sources, issues are receivable/receipt sources. No invoice number/date is required for posted receipt eligibility. Receipt value derives from item invoice unit price when provided, otherwise inventory unit price, including item VAT. Issue value derives from item quantity × unit_price; issue schema has no VAT column. Missing/nonpositive prices, zero amount and non-posted documents fail closed. VoucherPostingService revalidates the live source snapshot at posting; partial payments are bounded by the original source total. Source amounts are not user-entered independently. This supersedes the earlier invoice-required MVP restriction; business review of tax/rounding rules remains necessary before production activation.
