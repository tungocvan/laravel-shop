# Auth Module Contract

## Purpose

`Auth` is a shell module responsible for authentication and identity-verification workflows used by the web client/PWA and the administration surface.

The module is intentionally an authentication boundary, not the canonical owner of user profiles, authorization-role lifecycle, or generic framework infrastructure.

## Canonical responsibilities

Auth owns:

- web/client login and logout;
- administrator login and logout;
- registration credential flow;
- email verification and OTP verification workflows;
- Google OAuth authentication and explicit account linking;
- authentication-session security behavior such as session regeneration and invalidation;
- Auth-specific identity-verification persistence.

## Explicit non-ownership

Auth does not own:

- canonical user/profile master data;
- business account/profile lifecycle outside authentication;
- role and permission provisioning/lifecycle;
- generic cache or cache-lock persistence;
- queue, job, job-batch, or failed-job persistence;
- generic session schema;
- admin dashboard, menu, module catalog, or module-management behavior.

Integration with those boundaries must remain explicit and must not silently transfer ownership into Auth.

## Security invariants

1. Authentication must never silently reactivate a soft-deleted account.
2. Authentication must never silently provision authorization roles or permissions as a side effect of login.
3. Inactive or deleted identities must be rejected consistently across password and OAuth entry points.
4. Google identity resolution must reject conflicting Google IDs and conflicting account ownership.
5. Automatic Google account linking requires a verified identity and must follow the same canonical identity policy for admin and client entry points.
6. Explicit Google linking must require an authenticated active account and a matching account email.
7. Administrator Google login is an existing-account flow; it must not create a new account or grant roles during authentication.
8. Client/PWA Google login may create a new active account when the verified Google identity has no conflicting local owner.
9. Login/link callbacks must preserve OAuth state validation and regenerate the session after authentication transitions.
10. Logout must invalidate the authenticated session and regenerate the CSRF token.
11. Authentication failures exposed to users must not leak provider tokens, secrets, or sensitive exception details.

## Runtime boundaries

### Password / session authentication

HTTP or Livewire adapter
→ authentication/registration application boundary
→ identity policy
→ canonical user identity persistence
→ Laravel guard/session runtime

Guard-specific redirects and UX may differ between `web` and `admin`; identity/security policy must remain consistent.

### Google OAuth

`GoogleController` (admin) and `ClientGoogleController` (client/PWA) are transport/guard adapters. `GoogleIdentityService` is the canonical Google identity policy boundary.

Both adapters converge on `GoogleIdentityService` for:

- verified-email requirements;
- active/deleted account policy;
- Google-ID conflicts;
- email ownership conflicts;
- automatic linking eligibility;
- explicit linking eligibility.

The adapters retain different guards, callback routes, redirects, and account-creation policy: admin uses the existing-account-only resolver, while client/PWA may use the create-capable resolver.

## Dependency contract

Allowed direct framework/infrastructure integrations include Laravel authentication/session facilities and Socialite.

Known application integrations include:

- canonical `App\Models\User` identity persistence;
- System's administrator-login redirect service;
- ClientPortal route destinations;
- mail/notification infrastructure used by verification flows.

Role/permission provisioning is an external authorization/account-provisioning responsibility and must not be introduced as Auth business ownership.

## Persistence ownership

Auth owns the user-email-verification persistence required by its verification flows.

The cache, cache-lock, jobs, job-batches, failed-jobs, and generic sessions migrations currently located under `Modules/Auth/database/migrations` are ownership drift. They are classified `QUARANTINE` until migration-ledger, fresh-install, existing-schema, rollback, and canonical-owner proof is complete. Their physical relocation or deletion is not authorized merely by this contract.

## Legacy and compatibility boundaries

- Legacy Google/admin `AuthService` has been retired from the approved runtime because it restored soft-deleted identities and provisioned authorization roles during authentication.
- `GoogleWebAuthService` is a superseded compatibility candidate after introduction of canonical `GoogleIdentityService`; remove it only with complete caller proof. Until then it is `QUARANTINE` and must not be used for new code.
- The current API Auth stub is `QUARANTINE` pending route/caller proof.
- Generic module permissions (`view_auth`, `create_auth`, `edit_auth`, `delete_auth`) are `DEFER/REVIEW` pending caller and authorization-contract proof.

## Refactor classification baseline

- `AuthController`: KEEP.
- `LoginForm`: KEEP.
- `RegistrationForm`: KEEP.
- `VerifyEmailOtpForm`: KEEP.
- `RegistrationService`: KEEP / REFACTOR.
- `GoogleIdentityService`: KEEP / CANONICAL.
- `ClientGoogleController`: KEEP as client/PWA adapter.
- `GoogleController`: KEEP as admin adapter.
- legacy `AuthService`: CLEANED from runtime/source after replacement proof in this refactor branch.
- `GoogleWebAuthService`: QUARANTINE pending final caller proof; no new callers allowed.
- `UserEmailVerification` and its migrations: KEEP.
- cache/cache-lock migrations: QUARANTINE → REHOME candidate.
- jobs/job-batches/failed-jobs migrations: QUARANTINE → REHOME candidate.
- sessions migration: QUARANTINE → REHOME candidate.
- API Auth stub: QUARANTINE.
- generic Auth CRUD permissions: DEFER / REVIEW.

## Refactor delivery policy

The approved refactor should consolidate coherent authentication architecture/security changes into one primary MR where safe. Persistence relocation is separated only when schema/migration-ledger proof shows that an independent migration-safe change is required.

Deletion or rehoming requires caller/ownership proof; similarity or apparent duplication alone is not sufficient evidence.

Any future architectural change to these ownership or security boundaries must update this contract in the same pull request.
