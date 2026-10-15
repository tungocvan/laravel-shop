# Admission — Collaboration Handoff

## Current objective

Fix intermittent HTTP 403 when mobile QR scanners open signed Admission receipt search links with appended tracking query parameters.

Branch: `fix/admission-receipt-qr-tracking-signature`

Status: implementation committed; awaiting local focused tests, Admission regression and manual UI acceptance. No merge approval.

## Root cause evidence (Production ntd, 2026-10-12, 16:45–17:05 Asia/Ho_Chi_Minh)

- Same `/admission/search/receipt/272` URL signature fingerprint returned HTTP 200 without extra parameters, but HTTP 403 when scanners added `zarsrc`, `utm_source`, `utm_medium`, `utm_campaign`, `gidzl`.
- The signature value itself was unchanged. Raw signatures and student identifiers are not reproduced here.
- `signed` middleware validates the signed URL's query string; adding tracking parameters invalidates it.

## Changes

- Scoped exception to the existing `admission.search.receipt` route: `signed:zarsrc,utm_source,utm_medium,utm_campaign,gidzl`.
- The `signature` parameter is **not** ignored. No global signature changes, QR generation changes, controller changes, schema changes or permission changes.
- Added `tests/Feature/Admission/AdmissionReceiptQrTrackingSignatureTest.php`: checks real receipt middleware contract using an isolated probe route; original and tracked signed URL succeed, unknown parameter, modified signature and missing signature are rejected.
- Existing approved-application status check and student-prefill behavior remain unchanged.

## Verification gate

Not yet executed in user environment. Run focused test first, then Admission module regression. UI acceptance must confirm that scanning the existing QR on iPhone and Android works when tracking parameters are appended, while invalid/unsigned links remain denied. Full project regression: NOT APPLICABLE — module-scoped middleware change.

Do not merge until test PASS + UI PASS and explicit user approval.

## Historical context

Prior handoff described public branding/logo alignment on branch `fix/admission-public-branding-logo` (manual UI PASS on 2026-09-03). This QR fix does not change branding, registration, school settings, Admission persistence or legacy route behavior.
