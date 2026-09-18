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
Modules/Invoices/Console/Commands/DiagnoseGdtAuthenticationCommand.php
Modules/Invoices/Providers/InvoicesServiceProvider.php
Modules/Invoices/config/invoices.php
tests/Feature/Invoices/GdtAuthenticationSafetyContractTest.php
docs/modules/Invoices/COLLABORATION_HANDOFF.md
```

## Known regression baseline at this closeout

After the request-id fix:

- focused GDT authentication contract test: PASS;
- CLI GDT authentication: PASS;
- `/admin/invoices/hoadon`: UI PASS;
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
