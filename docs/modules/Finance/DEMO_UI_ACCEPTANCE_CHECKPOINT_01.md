# Finance Demo WSL — UI Acceptance Checkpoint 01

**Status: CODE COMMITTED / GIT PULL + TEST PENDING.** No assistant-side browser or PHPUnit execution.

## Concrete UI changes

1. `/admin/finance` link now goes to `/admin/finance/debts/documents`, the **working** manual-debt screen, instead of the unfinished dashboard.
2. Debt sidebar labels six unfinished sections `(Đang xây dựng)`; their placeholder panel has a direct action to the manual-debt workflow. No misleading claim that dashboard/partners/ledger/summary/aging/settings are implemented.
3. Finance overview and ledger report totals, rows and Excel links now use the **applied** date range (the dates selected by clicking `Áp dụng`), consistent with the existing `Đang xem` label and ledger filters. Editing a date without applying must not silently change reports.

## Manual UI acceptance on isolated Demo WSL only

- Log in as an admin with `finance.view` and appropriate voucher/debt permissions. Navigate `/admin/finance`.
- On **Tổng quan**, change `Từ ngày`/`Đến ngày` without pressing `Áp dụng`. Confirm `Đang xem` and displayed totals still reflect the previous applied range. Press `Áp dụng` and confirm totals update. Use a known test voucher on a date inside/outside the ranges.
- Open **Sổ chứng từ**; verify table and Excel export match the same applied date range; verify status/direction/category filters and reset.
- From main Finance header click **Công nợ thủ công**. Confirm navigation to `/admin/finance/debts/documents` with a real list/create/confirm/adjust form (depending on role).
- Click **Tổng quan**, **Đối tác**, **Chi tiết công nợ**, **Tổng hợp**, **Tuổi nợ**, **Cài đặt** in debt sidebar: each clearly displays `Đang xây dựng` and provides a link back to the working manual-debt page. Do not attempt financial mutations on placeholder sections.
- Verify user without mutation permissions cannot create/confirm/adjust debt. Existing backend tests still apply.

## Automated regression

Run `php artisan test Modules/Finance/Tests` using the existing isolated test MySQL environment. UI interactions require a separate browser acceptance pass. Report PASS/FAIL and screenshots/error messages.

## Not in scope

No new settlement UI, no public settlement activation, no production migrations, no merge/deploy. This checkpoint is intentionally UI-first and narrowly fixes an observed applied-date mismatch and navigation confusion.
