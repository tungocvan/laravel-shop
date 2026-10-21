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

## Draft voucher workflow (checkpoint 5A)

FinanceWorkflowService provides a backend-only first slice for creating VND cash/bank accounts, resolving source total/paid/remaining, and creating a draft receipt/payment voucher with one source allocation. The direction comes from the provider snapshot, never client input. Posted allocations determine paid amounts; draft allocations do not consume the outstanding balance. VoucherPostingService remains the sole posting/reversal owner. No HTTP routes or UI were added in this slice. This is not a production-ready accounting workflow; multi-source selection, UI authorization, cancellation of drafts and operational account management remain future work.

## Admin workspace checkpoint 5B

Route `GET /admin/finance` (`admin.finance.index`) uses `auth:admin` and `finance.view` permission. Livewire component `finance.workspace` selects posted Pharma receipt/issue by code, displays Finance outstanding balances, creates cash/bank accounts and draft vouchers, and delegates posting/reversal to Finance services. Granular permissions are checked in component actions. Mobile-first Blade uses the canonical `Admin::layouts.master`. This UI is not yet manually accepted; no production rollout is authorized. Draft voucher cancellation, ledger reports, multi-source allocations and account editing are not implemented.

## Manual cash movements — checkpoint 6A

Finance owns manual receipt/payment vouchers independently of Pharma and Request. finance_vouchers.origin defaults to source for existing records; manual vouchers use origin=manual and optional manual_category, counterparty, description and reference_number metadata. A manual draft requires direction, category, counterparty, reason, valid date, positive VND amount and active account. No finance_allocations rows are created for manual vouchers. VoucherPostingService allows zero allocations **only** for origin=manual, validates manual details and writes the same signed ledger/reversal entries; origin=source continues to require live Provider validation and source allocations. Manual advances/deposits represent cash movements only, not automatic settlement or expense recognition. No UI, reports or print view yet; those belong to checkpoints 6B–6D.

## Workspace navigation and manual categories (checkpoint 6B.2)

The workspace defaults to compact receipt/payment actions, with Pharma under automatic document sources. Finance account management is accessible from the top toolbar, and account creation is shown only when requested. Manual transaction categories are Finance-owned data in finance_manual_categories (direction, code, name, is_active). Migration 000004 seeds default categories and allows additional direction-specific categories without hardcoded UI options. ManualCategoryService validates active category selection and creates unique custom codes. The UI's add-category action requires finance.voucher.create. No Request integration, category deletion, dashboard or printing in this checkpoint.
