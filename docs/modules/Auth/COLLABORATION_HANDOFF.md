# Auth Collaboration Handoff

## Current objective

**Login Theme Manager V1.1 corrective closeout** — image-upload validation/stability plus canonical Admin entrypoint/redirect behavior.

Current branch:

`fix/auth-login-theme-upload-validation-preview`

V1 and V1.1 are already merged. This branch contains only corrective follow-up discovered during UI verification.

## Corrective scope delivered

### Login branding assets

- Logo/background uploads remain standard Laravel multipart HTTP requests outside Livewire file-upload lifecycle.
- Browser-side limits prevent normal oversized uploads before submission: logo 3 MB, background 6 MB.
- Server-side validation mirrors the supported image types and limits and surfaces Vietnamese validation errors.
- Temporary native picker lifecycle is resilient to Windows/Chrome focus-before-change ordering.
- Login logo previews use a neutral contrast surface so transparent/light logos remain visible.

### Admin entrypoint and redirect

The intended contract is now explicit:

- `/admin` is the dynamic Admin entrypoint (`admin.entry`).
- `/admin/dashboard` is the canonical built-in Admin dashboard (`admin.dashboard`).
- The configured Admin destination is stored under `admin_login_redirect_route`.
- `/admin` resolves through `AdminLoginRedirectService` and redirects to the configured valid Admin route.
- Successful Admin authentication uses the same resolver.
- If the configured route is missing/invalid, fallback is the canonical `/admin/dashboard` route.
- `admin.dashboard` is excluded as a selectable redirect target when needed to avoid entrypoint recursion semantics.
- The System settings screen uses a dedicated top-level Laravel form/POST persistence flow for this setting instead of a nested dynamic Livewire save path.

Example accepted flow:

`/admin` → configured `admin.admission.dashboard` → `/admin/admission/dashboard`.

## Ownership boundary

- Auth continues to own authentication/login presentation behavior.
- System owns persisted settings, Admin configuration UI, redirect resolver and managed branding assets.
- Admin owns the canonical Admin entrypoint/dashboard routes and delegates dynamic entry resolution to the System resolver.
- Admission remains independent; only its existing named dashboard route can be selected as a destination.

## Safety / unchanged behavior

- No database/schema changes.
- No credential, guard, OAuth identity, callback security or session-security changes.
- No Admission implementation/ownership changes.
- No repository-wide formatting cleanup.

## Validation checkpoint

User UI acceptance: **PASS**.

Verified manually in the accepted flow:

- Login Theme corrective UI/upload behavior;
- Admin redirect configuration;
- `/admin` redirects to the configured `/admin/admission/dashboard` destination.

Earlier V1.1 focused validation remains recorded as:

```text
PASS  Tests\Feature\Auth\LoginThemePresentationTest
3 passed (17 assertions)

Vite production build: PASS — 34 modules transformed
```

Repository-wide Pint remains blocked by pre-existing repository style debt and is not part of this corrective scope.

## Current status

- V1: **MERGED**.
- V1.1: **MERGED**.
- Corrective implementation: **COMPLETE**.
- Corrective UI smoke / Admin entrypoint flow: **PASS (user accepted)**.
- Branch vs `main`: corrective branch is ahead with no behind commits at PR-preparation checkpoint.
- Architecture/security boundaries: **PRESERVED**.
- Next step: open corrective PR to `main` for user review and manual merge.
