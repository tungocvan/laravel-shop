# GDT Authentication Troubleshooting

## Purpose

Runbook for diagnosing authentication changes at `https://hoadondientu.gdt.gov.vn` used by `Modules\\Invoices`.

This document records verified project evidence separately from community observations so a future GDT frontend/API change can be diagnosed quickly without guessing, copying browser secrets, or changing unrelated modules.

## Current integration boundary

- GDT access belongs to `Modules\\Invoices`.
- Inventory must never call GDT directly.
- Base API currently uses `https://hoadondientu.gdt.gov.vn/api`.
- Captcha endpoint: `/captcha`.
- Authenticate endpoint: `/security-taxpayer/authenticate`.
- Captcha and authenticate must share the same upstream cookie session.
- Captcha is read and entered manually by the operator.
- Successful authentication token is cached by Invoices.

## Incident — 18/09/2026: authenticate HTTP 403

### Symptom

The official GDT web frontend could authenticate successfully, while Laravel received HTTP 403 from authenticate with the upstream message that an invalid behavior/request had been detected and blocked.

Captcha initialization still returned HTTP 200.

### What was ruled out

Diagnostics established that this was not a Livewire/modal-only failure:

1. The local CLI diagnostic reproduced authenticate HTTP 403 independently of the UI.
2. Captcha and authenticate reached the same GDT IP observed in the successful frontend path.
3. Transport used HTTP/2 and SSL verification succeeded.
4. Captcha returned HTTP 200 and produced three session cookies.
5. Laravel persisted/restored the captcha cookie jar for authenticate.
6. `Origin`, `Referer`, `Action` and `End-Point` had already been aligned without resolving the 403.

Do not reopen Nginx/PHP-FPM investigation for this exact signature unless new evidence points there.

### Root-cause evidence

The material request-contract difference was `request-id`.

Controlled comparison:

```text
authenticate without request-id  -> HTTP 403
authenticate with fresh request-id -> HTTP 200
```

The successful response included the expected login action and the token was cached. The real `/admin/invoices/hoadon` flow was then manually verified: UI PASS.

Implementation rule: generate a fresh UUID for the authenticate request. Never copy or replay a browser request-id. Diagnostic logs record only that a request ID was generated; they never record its value.

## Incident — 18/09/2026: invoice list/detail HTTP 403 after successful login

### Symptom and misleading secondary errors

Authentication could return HTTP 200 and the token could be present in the database cache, but the first invoice-list query still returned HTTP 403 with the upstream blocked-request message.

The old code treated both HTTP 401 and HTTP 403 as an expired session and deleted the cached token. That produced misleading follow-up errors such as `Không có token GDT trong cache` or `Phiên đăng nhập GDT đã hết hạn hoặc chưa được tạo`. Those messages were consequences of deleting the token after a 403, not proof that cache, queue or token TTL was the original cause.

The detail endpoint had the same 401/403 conflation. A rejected detail request could delete the token and make every later detail request fail before reaching GDT.

### Verified diagnosis

Fresh-login evidence showed:

```text
authenticate                         -> HTTP 200
database cache put                   -> success
token present immediately after put -> true
invoice list query with Bearer token but old request context -> HTTP 403
```

Safe rejection diagnostics then recorded the list-query status as HTTP 403 and the upstream blocked-request message without logging the token.

After aligning the invoice-list request context with the successful authentication pattern, the same date range returned all 20/20 invoice headers. The failure then moved to the detail endpoint, whose code still used the old request context and still merged 401/403.

### Implemented correction

For authenticate, invoice-list queries and invoice-detail queries:

- generate a fresh UUID `request-id` for each outbound request;
- send the application-level `Accept`, `Origin`, `Referer`, `Action` and `End-Point` context used by this integration;
- retain Bearer authentication where required;
- never copy/replay browser request IDs, cookies or browser fingerprint headers.

For list/detail rejection handling:

- HTTP 401 means the authentication token is rejected; clear the cached token and require a fresh login;
- HTTP 403 means GDT rejected the request; do **not** delete the cached token merely because of 403;
- log a redacted rejection diagnostic containing endpoint/action context, HTTP status, upstream message/response keys and a marker that Bearer/request-id were present, never their values.

### Runtime acceptance

After worker reload and a fresh manual-captcha login:

```text
RAW detail recovery: 14 candidates -> 14 fetched, 0 errors
invoice list query: 20/20 received
header persistence: 0 created, 0 updated, 20 unchanged
detail pass: 19 reused, 1 fetched, 0 errors
Excel export: created successfully
job: completed
```

This is the end-to-end acceptance evidence for the 18/09/2026 request-context correction.

### Error signatures and recovery guide

| Signature | Meaning / first check | Correct recovery |
| --- | --- | --- |
| Captcha HTTP 200, authenticate HTTP 403 blocked-request | Authentication request contract rejected by GDT | Verify fresh per-request `request-id` and safe application headers. Do not repeatedly submit captcha/login or copy browser secrets. |
| Authenticate HTTP 200, token cache put/present true, list query HTTP 403 | Login/cache is working; list request contract is being rejected | Inspect `GDT invoice query rejected.`. Keep token on 403; verify list request context before investigating cache/queue. |
| List query succeeds but detail returns HTTP 403 | Detail request contract is being rejected | Inspect `GDT invoice detail rejected.`. Keep token on 403; verify detail request context. |
| HTTP 401 on list/detail | GDT rejected the Bearer session/token | Clear the cached token, perform a fresh manual-captcha login, then retry once. |
| `Không có token GDT trong cache` immediately after an earlier 403 | May be a secondary symptom from legacy 403 handling | Inspect the preceding rejection status first. Do not diagnose cache failure from this message alone. |
| Fresh source pulled but runtime still shows old generic 401/403 message | Long-lived queue worker may still have old PHP code loaded | Run `php artisan queue:restart`; if PM2 owns the worker, confirm/restart the correct queue process, then fresh-login before one controlled retry. |
| Google Drive token refresh warning while GDT processing continues | Separate storage/export verification concern | Diagnose Google Drive independently; do not treat it as evidence of GDT authentication failure. |
| HTTP 429 on detail | GDT rate limit | Respect `Retry-After`/configured conservative backoff. Do not increase request frequency. |
| Connection/timeout error | Transport failure, not automatically authentication failure | Check network/DNS/TLS diagnostics and retry conservatively; do not delete token solely for a connection exception. |

When a 401/403 occurs, always inspect the **first** rejection in the job. Later missing-token errors can be secondary effects and are less useful for root-cause diagnosis.

### Queue-worker rule after code changes

`queue:work` is long-lived. Pulling new PHP source does not guarantee an already-running worker has loaded it. After changing GDT request/diagnostic code, reload the worker before runtime verification. Then perform a fresh manual-captcha login and one controlled sync. Do not use repeated retries as a diagnostic technique.



## Diagnostic note — GDT API count can differ from canonical date coverage

Do not assume the count returned by the GDT list endpoint must equal `InvoiceSourceCoverageService` coverage.

Verified case on 18/09/2026 for a requested sold-invoice range of 10/09/2026 through 17/09/2026:

```text
GDT API list returned: 20 invoices
canonical local coverage in requested issued_date range: 14/14
out-of-range invoices returned by GDT: 6
```

The six extra records were invoice numbers 463 through 468. Their persisted `issued_date` was 09/09/2026, so they were correctly excluded from local coverage for 10/09–17/09. Invoice numbers 469 through 482 were inside the requested local `issued_date` range.

This was **not** a timezone/database-date defect: direct raw database inspection confirmed the stored dates. It was also not missing canonical data. The upstream GDT response simply contained records whose persisted invoice issue date fell outside the requested local date boundary.

Recovery rule: when API count and RAW canonical coverage differ, first compare the returned records' normalized `issued_date` against the requested range. Do not broaden `InvoiceSourceCoverageService`, delete records, re-fetch detail, or diagnose missing data solely from the count mismatch.

The sync log therefore reports two separate concepts: total records returned by the GDT API, and how many of those records have normalized issue dates inside/outside the operator's requested range. RAW canonical coverage continues to use the requested local `issued_date` range.


## External reference history

These references are supporting observations, not an official GDT API contract.

- Giải Pháp Excel discussion: `https://www.giaiphapexcel.com/diendan/threads/t%E1%BA%A3i-h%C3%B3a-%C4%91%C6%A1n-%C4%91i%E1%BB%87n-t%E1%BB%AD-https-hoadondientu-gdt-gov-vn-excel-vba.171723/`
- The thread records a 10/05/2026 update after the GDT site redesign and a 16/09/2026 update explicitly described as adding `request-id`.
- Page 7 records the May 2026 endpoint migration from the older port-based URLs to `/api/captcha` and `/api/security-taxpayer/authenticate`.
- Page 9 records September 2026 community reports that requests missing `request-id` receive HTTP 403 and that adding a newly generated request ID restored operation for multiple users.

Because this is community evidence, re-verify against current GDT behavior whenever the portal changes. Do not assume a historical workaround remains the current contract.

## Safe diagnostic procedure for future changes

When GDT login breaks again, diagnose in this order:

1. Confirm the official GDT website itself can log in manually. Do not expose credentials in logs or chat.
2. Run the focused GDT authentication contract test.
3. Run `php artisan invoices:gdt-auth-diagnose` in the local environment, open the generated captcha SVG, and enter the captcha manually.
4. Inspect redacted GDT logs for captcha/authenticate HTTP status, cookie count/metadata, network context, and safe response headers.
5. Compare the application-level request contract with the current official frontend: endpoint paths, payload field names, `Origin`, `Referer`, `Action`, `End-Point`, and documented/verified correlation headers such as `request-id`.
6. Search for recent GDT portal changes and community reports, but treat community reports as hypotheses until reproduced locally.
7. Change one contract dimension at a time and repeat the CLI diagnostic before touching the UI.
8. After CLI PASS, verify `/admin/invoices/hoadon` manually and record UI PASS.

## Security guardrails

Never:

- log or paste GDT password, token, captcha value, cookie values, or actual request-id;
- copy/replay browser cookies, JWT/token, session IDs, or browser request IDs into Laravel;
- emulate `sec-ch-ua`, `sec-fetch-*`, browser TLS fingerprints, or stealth-browser behavior;
- rotate proxies or use automatic repeated login attempts;
- automate captcha solving;
- blindly retry authenticate POST.

If GDT publishes a supported integration/API contract, prefer it over reverse-engineering frontend behavior.

## Current implementation locations

Primary files to inspect first:

```text
Modules/Invoices/Services/GdtApiService.php
Modules/Invoices/Services/GdtInvoiceService.php
Modules/Invoices/Services/GdtPdfService.php
Modules/Invoices/Console/Commands/DiagnoseGdtAuthenticationCommand.php
Modules/Invoices/Providers/InvoicesServiceProvider.php
Modules/Invoices/config/invoices.php
tests/Feature/Invoices/GdtAuthenticationSafetyContractTest.php
docs/modules/Invoices/COLLABORATION_HANDOFF.md
```

## Known regression baseline at this closeout

After the request-context fixes:

- focused GDT authentication contract test: **7 passed / 73 assertions**;
- CLI GDT authentication: PASS;
- `/admin/invoices/hoadon`: UI PASS;
- invoice list runtime: **20/20 received**;
- RAW detail runtime: **14/14 recovery fetched, then 19 reused + 1 fetched, 0 errors**;
- Excel export/job completion: PASS;
- Invoices module regression: 49 passed / 4 failed / 459 assertions.

The four failures are existing Inventory/bulk-intake/handoff contract drift and are outside this GDT authentication scope. Do not modify those boundaries merely to make this authentication branch green.

## Maintenance rule

Whenever GDT changes authentication again, append a dated incident section containing:

- symptom and upstream HTTP status;
- what still works;
- transport/session evidence;
- exact application-level contract difference found;
- external references and whether they are official or community sources;
- controlled before/after result;
- focused test result and UI acceptance.

Keep historical incidents. They are useful evidence of how the GDT portal contract has changed over time.
