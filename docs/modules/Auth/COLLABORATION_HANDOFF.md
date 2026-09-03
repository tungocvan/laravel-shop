# Auth Collaboration Handoff

## Current objective

**Login Theme Manager V1.1 corrective closeout** remains the merged Auth baseline. The current User branch additionally contains a narrow cross-module Google identity contract change for one-time existing-account auto-link approval.

Current cross-module branch:

`feat/user-google-auto-link-approval`

## Existing corrective baseline

### Login branding assets

- Logo/background uploads remain standard Laravel multipart HTTP requests outside Livewire file-upload lifecycle.
- Browser-side limits prevent normal oversized uploads before submission: logo 3 MB, background 6 MB.
- Server-side validation mirrors the supported image types and limits and surfaces Vietnamese validation errors.
- Temporary native picker lifecycle is resilient to Windows/Chrome focus-before-change ordering.
- Login logo previews use a neutral contrast surface so transparent/light logos remain visible.

### Admin entrypoint and redirect

- `/admin` is the dynamic Admin entrypoint (`admin.entry`).
- `/admin/dashboard` is the canonical built-in Admin dashboard (`admin.dashboard`).
- The configured Admin destination is stored under `admin_login_redirect_route`.
- `/admin` resolves through `AdminLoginRedirectService` and redirects to the configured valid Admin route.
- Successful Admin authentication uses the same resolver.
- Invalid/missing configured routes fall back to `/admin/dashboard`.

## Cross-module Google identity note

`feat/user-google-auto-link-approval` changes `Modules/Auth/Services/GoogleIdentityService.php` only at the identity-link boundary:

- User administration owns the one-time account approval flag `google_auto_link_enabled`.
- Auth remains the sole owner allowed to persist `google_id`.
- Google must provide a verified email and that normalized email must exactly match the existing account.
- Existing active/non-deleted and Google-ID collision safeguards remain enforced.
- When an existing email owner has explicit one-time approval, Auth may bypass only the previous local OTP prerequisite.
- Without approval, the existing OTP/password-linking safeguards remain unchanged.
- Successful approved linking sets `google_id`, ensures `email_verified_at`, and consumes the approval by setting `google_auto_link_enabled = false`.
- No fake OTP verification record is created.

## Ownership boundary

- Auth owns authentication, OAuth identity verification, Google collision rules and final identity linking.
- User owns shared account administration and the explicit one-time approval state/UI.
- Role owns role/permission catalog data.
- System/Admin ownership from the Login Theme/entrypoint work is unchanged.

## Validation checkpoint

User-reported acceptance on the current cross-module branch:

- Google auto-link approval UI: **PASS**.
- Focused User contract suite after final User list addition: **11 passed (44 assertions)**.
- Vite production build: **PASS — 34 modules transformed**.
- Earlier focused Auth/User validation on this branch covered approved Google auto-link, approval consumption, unapproved existing-email blocking, unknown-account behavior and soft-deleted-account safeguards.

Repository-wide Pint style debt outside the changed scope remains unrelated.

## Current status

- Login Theme V1/V1.1 corrective baseline: **MERGED/COMPLETE**.
- One-time Google auto-link Auth boundary: **IMPLEMENTED**.
- User UI acceptance: **PASS**.
- Architecture/security boundary: **PRESERVED**.
- Next step: include this narrow Auth change in the single PR from `feat/user-google-auto-link-approval` to `main`.
