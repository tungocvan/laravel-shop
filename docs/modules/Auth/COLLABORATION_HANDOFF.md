# Auth Collaboration Handoff

## Current objective

Refactor `Modules/Auth` under `docs/GITHUB_COLLABORATION_WORKFLOW.md` in **Refactor Module** mode.

Primary delivery branch:

`refactor/auth-architecture-security-boundaries`

The user approved consolidation of coherent work into one primary MR to minimize repeated pull/test cycles.

## Approved primary MR scope

The primary MR covers, where caller and compatibility proof permits:

- establish the missing Auth module contract;
- normalize authentication ownership and security boundaries;
- consolidate Google identity policy shared by admin and client/PWA adapters;
- remove Auth ownership of authorization-role provisioning from authentication behavior;
- review/quarantine legacy API and generic CRUD permission surfaces;
- preserve and strengthen registration/email-verification/OTP behavior;
- retain guard-specific redirects while making identity policy consistent;
- add focused Auth and directly impacted integration regression coverage;
- close out documentation and ownership classifications.

## Persistence exception

Infrastructure migrations currently under Auth for cache, cache locks, jobs, job batches, failed jobs, and generic sessions are classified `QUARANTINE` pending migration-ledger/schema ownership proof.

They remain in place in the primary MR unless safe relocation/deletion is proven. A second MR is created only if persistence ownership cleanup requires an independently migration-safe change.

## Runtime refactor completed

Google OAuth identity resolution now converges on `GoogleIdentityService` for both admin and client/PWA entry points.

The canonical service preserves verified-email, identity-conflict, inactive/deleted-account, OTP-proven auto-link, and explicit-link checks. Client/PWA may create a new account for a verified non-conflicting identity, while administrator Google login uses `resolveExisting()` and therefore cannot create a new administrator account as a login side effect.

`GoogleController` remains the admin guard/transport adapter and retains the System `AdminLoginRedirectService` integration. `ClientGoogleController` remains the web/client adapter.

The legacy admin Google `AuthService` has been removed from the branch after its runtime replacement. Its previous behaviors that restored soft-deleted users and provisioned an admin role during login are no longer part of the approved Google authentication path.

The superseded `GoogleWebAuthService` is retained as a deprecated compatibility adapter only. It contains no independent identity policy and delegates `resolve`, `resolveExisting`, and `link` to `GoogleIdentityService`.

## Deferred / quarantined boundaries

- `GoogleWebAuthService`: compatibility adapter, `QUARANTINE` pending complete caller proof.
- API Auth stub: `QUARANTINE` pending stronger caller proof.
- Generic Auth CRUD permissions: `DEFER/REVIEW` pending authorization-contract proof.
- Cache/jobs/session infrastructure migrations: `QUARANTINE` pending schema/migration-ledger and canonical-owner proof.

## Validation checkpoint

User-executed final regression checkpoint on the approved branch passed:

- focused/impacted test run: `188 passed (1075 assertions)` in `12.93s`;
- canonical Auth routes verified for admin login/logout, client logout, admin Google OAuth, client/PWA Google OAuth callback, and explicit Google linking;
- Vite production build passed: `34 modules transformed`, build completed in `3.81s`;
- UI smoke acceptance: **PASS**.

## Follow-up approved after this MR

A separate follow-up feature is approved for the administrator login presentation rather than being mixed into this security refactor: **Auth Login Theme & Branding Manager V1**.

Planned direction:

- configuration managed from System settings;
- Auth remains owner of login rendering/authentication presentation;
- four initial login-theme presets (`classic-card`, `split-brand`, `hero-overlay`, `minimal`);
- configurable logo, background, text/branding, colors and presentation options;
- live preview in administration;
- presentation changes must not modify authentication guards, credentials, OAuth, session, or identity-security policy;
- normalize the current logo value/view contract while implementing the presentation boundary;
- design the theme engine for later reuse by client `/login`, while applying V1 first to `/admin/login`.

This follow-up must start from merged `main` on its own branch after the present MR is merged.

## Current status

- Read-only module audit: COMPLETE.
- Module contract: COMPLETE.
- Canonical Google identity service: COMPLETE.
- Admin/client Google adapters migration: COMPLETE.
- Legacy role-provisioning Google `AuthService`: CLEANED.
- Focused security regression coverage: COMPLETE.
- Automated regression/routes/build checkpoint: PASS.
- UI smoke checkpoint: PASS.
- Persistence ownership relocation: DEFERRED / NOT INCLUDED.
- Primary MR: READY FOR PR REVIEW/MERGE.
