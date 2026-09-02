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
- Auth-specific identity-verification persistence;
- rendering and presentation contracts for Auth login surfaces.

System may own the administration of configurable login presentation values, but those settings must be consumed through an Auth presentation boundary and must not acquire ownership of authentication behavior.

## Explicit non-ownership

Auth does not own:

- canonical user/profile master data;
- business account/profile lifecycle outside authentication;
- role and permission provisioning/lifecycle;
- generic cache or cache-lock persistence;
- queue, job, job-batch, or failed-job persistence;
- generic session schema;
- admin dashboard, menu, module catalog, or module-management behavior;
- the generic System settings administration surface.

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
12. Login theme/branding settings may alter presentation only; they must not change guards, credentials, authorization, OAuth identity policy, callback behavior, or session security.

## Runtime boundaries

### Password / session authentication

HTTP or Livewire adapter
→ authentication/registration application boundary
→ identity policy
→ canonical user identity persistence
→ Laravel guard/session runtime

Guard-specific redirects and UX may differ between `web` and `admin`; identity/security policy must remain consistent.

### Login presentation

System settings administration
→ persisted presentation values
→ `LoginPresentationService`
→ `LoginForm` / Auth login view

`LoginPresentationService` is the canonical presentation-normalization boundary for configurable login themes. Admin and client login surfaces may use independent presentation settings while sharing the same supported theme vocabulary and safe normalization rules.

Initial supported presets are:

- `classic-card`;
- `split-brand`;
- `hero-overlay`;
- `minimal`.

Presentation settings may include logo, background image, title lines, description, primary color, overlay opacity, Google-button visibility and footer text. Asset paths must be converted to renderable URLs before entering the view contract; views must not reinterpret a resolved URL as a storage-relative path.

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
- System settings as the administration/persistence integration for configurable login presentation;
- ClientPortal route destinations;
- mail/notification infrastructure used by verification flows.

Role/permission provisioning is an external authorization/account-provisioning responsibility and must not be introduced as Auth business ownership.

## Persistence ownership

Auth owns the user-email-verification persistence required by its verification flows.

Login presentation settings are configuration records managed through System's generic settings infrastructure; Auth owns their presentation semantics, not the generic settings persistence implementation.

The cache, cache-lock, jobs, job-batches, failed-jobs, and generic sessions migrations currently located under `Modules/Auth/database/migrations` are ownership drift. They are classified `QUARANTINE` until migration-ledger, fresh-install, existing-schema, rollback, and canonical-owner proof is complete. Their physical relocation or deletion is not authorized merely by this contract.

## Legacy and compatibility boundaries

- Legacy Google/admin `AuthService` has been retired from the approved runtime because it restored soft-deleted identities and provisioned authorization roles during authentication.
- `GoogleWebAuthService` is retained only as a deprecated compatibility adapter that delegates to `GoogleIdentityService`. It is `QUARANTINE`, must not contain independent identity policy, and must not receive new callers. Remove it only when complete caller proof is available.
- The current API Auth stub is `QUARANTINE` pending route/caller proof.
- Generic module permissions (`view_auth`, `create_auth`, `edit_auth`, `delete_auth`) are `DEFER/REVIEW` pending caller and authorization-contract proof.

## Refactor classification baseline

- `AuthController`: KEEP.
- `LoginForm`: KEEP; consumes canonical presentation config.
- `LoginPresentationService`: KEEP / CANONICAL for login presentation normalization.
- `RegistrationForm`: KEEP.
- `VerifyEmailOtpForm`: KEEP.
- `RegistrationService`: KEEP / REFACTOR.
- `GoogleIdentityService`: KEEP / CANONICAL.
- `ClientGoogleController`: KEEP as client/PWA adapter.
- `GoogleController`: KEEP as admin adapter.
- legacy `AuthService`: CLEANED from runtime/source after replacement proof in the architecture/security refactor.
- `GoogleWebAuthService`: QUARANTINE compatibility adapter; delegates only to `GoogleIdentityService`.
- `UserEmailVerification` and its migrations: KEEP.
- cache/cache-lock migrations: QUARANTINE → REHOME candidate.
- jobs/job-batches/failed-jobs migrations: QUARANTINE → REHOME candidate.
- sessions migration: QUARANTINE → REHOME candidate.
- API Auth stub: QUARANTINE.
- generic Auth CRUD permissions: DEFER / REVIEW.

## Refactor delivery policy

The approved refactor should consolidate coherent authentication architecture/security changes into one primary MR where safe. Persistence relocation is separated only when schema/migration-ledger proof shows that an independent migration-safe change is required.

Login presentation features must remain independently testable from authentication/security policy. Theme or branding changes must not be used as a reason to bypass Auth regression coverage.

Deletion or rehoming requires caller/ownership proof; similarity or apparent duplication alone is not sufficient evidence.

Any future architectural change to these ownership or security boundaries must update this contract in the same pull request.
