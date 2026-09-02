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
- perform Auth UI/UX consistency/accessibility cleanup where required;
- add focused Auth and directly impacted integration regression coverage;
- close out documentation and ownership classifications.

## Persistence exception

Infrastructure migrations currently under Auth for cache, cache locks, jobs, job batches, failed jobs, and generic sessions are classified `QUARANTINE` pending migration-ledger/schema ownership proof.

They remain in place in the primary MR unless safe relocation/deletion is proven. A second MR is created only if persistence ownership cleanup requires an independently migration-safe change. This avoids unnecessary pull/test cycles without coupling risky schema work to authentication behavior.

## Baseline findings

- `AuthController` is the canonical web/admin password-session adapter and remains in place.
- Auth owns `UserEmailVerification` persistence.
- Generic cache/jobs/session schema is not Auth business ownership.
- The active API `/auth` surface is a compatibility stub and remains quarantined until stronger caller proof exists.
- Generic `view_auth/create_auth/edit_auth/delete_auth` module permissions remain deferred pending caller proof.

## Runtime refactor completed on branch

Google OAuth identity resolution now converges on `GoogleIdentityService` for both admin and client/PWA entry points.

The canonical service preserves verified-email, identity-conflict, inactive/deleted-account, OTP-proven auto-link, and explicit-link checks. Client/PWA may create a new account for a verified non-conflicting identity, while administrator Google login uses `resolveExisting()` and therefore cannot create a new administrator account as a login side effect.

`GoogleController` remains the admin guard/transport adapter and retains the System `AdminLoginRedirectService` integration. `ClientGoogleController` remains the web/client adapter.

The legacy admin Google `AuthService` has been removed from the branch after its runtime replacement. Its previous behaviors that restored soft-deleted users and provisioned an admin role during login are no longer part of the approved Google authentication path.

The superseded `GoogleWebAuthService` is retained as a deprecated compatibility adapter only. It now contains no independent identity policy and delegates `resolve`, `resolveExisting`, and `link` to `GoogleIdentityService`. This preserves compatibility while preventing duplicated security rules from drifting again.

Focused regression coverage on the branch includes explicit admin cases for:

- existing Google-linked account login;
- no role provisioning during authentication;
- unknown Google identity not creating an administrator account;
- soft-deleted identity not being restored.

Client Google coverage exercises verified identity creation, OTP-proven linking, rejection without OTP provenance, unverified email rejection, identity conflicts, callback login, guard separation, and non-persistence of provider access/refresh tokens.

## Deferred / quarantined boundaries

`GoogleWebAuthService` remains `QUARANTINE` as a compatibility adapter until final caller proof permits removal. No new code should depend on it.

The API Auth stub and generic Auth CRUD permissions remain unchanged while caller evidence is insufficient for safe deletion.

Infrastructure persistence migrations remain unchanged and quarantined. No migration relocation is included in the primary MR without ledger/schema proof.

## Security invariants

Authentication must not silently reactivate deleted accounts or silently acquire ownership of role/permission provisioning. Inactive/deleted identities and Google identity conflicts must be handled consistently across entry points. OAuth state validation and session-regeneration protections must be preserved.

## Validation checkpoint

User-executed final regression checkpoint on the approved branch passed:

- focused/impacted test run: `188 passed (1075 assertions)` in `12.93s`;
- canonical Auth routes verified for admin login/logout, client logout, admin Google OAuth, client/PWA Google OAuth callback, and explicit Google linking;
- Vite production build passed: `34 modules transformed`, build completed in `3.81s`.

UI smoke remains a separate acceptance signal and should be recorded explicitly before merge if performed.

## Delivery checkpoints

No intermediate user pull/UI test was required for internal implementation batches. The consolidated automated regression and build checkpoint is COMPLETE.

If a database/security blocker requires a product or ownership decision that cannot be proven from the repository, stop and request that decision rather than guessing.

## Current status

- Read-only module audit: COMPLETE.
- User approval for consolidated implementation: COMPLETE.
- Primary branch creation: COMPLETE.
- `docs/modules/Auth/MODULE.md`: CREATED and aligned with runtime target.
- Canonical Google identity service: IMPLEMENTED.
- Admin/client Google adapters: MIGRATED to canonical identity service.
- Legacy role-provisioning Google `AuthService`: CLEANED from branch.
- Superseded `GoogleWebAuthService`: REDUCED to deprecated compatibility adapter.
- Google security regression coverage: ADDED/UPDATED.
- API/config compatibility surfaces: QUARANTINE / DEFER pending proof.
- Persistence relocation: NOT AUTHORIZED without schema/ledger proof.
- Automated regression/routes/build checkpoint: PASS.
- UI smoke checkpoint: PENDING USER CONFIRMATION.
- Primary MR readiness: BLOCKED ONLY ON UI ACCEPTANCE / FINAL REVIEW.
