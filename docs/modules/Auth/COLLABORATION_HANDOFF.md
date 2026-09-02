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

They should remain in place in the primary MR unless safe relocation/deletion is proven. A second MR is created only if persistence ownership cleanup requires an independently migration-safe change. This avoids unnecessary pull/test cycles without coupling risky schema work to authentication behavior.

## Baseline findings

- `AuthController` is the canonical web/admin password-session adapter and should remain.
- Client/PWA Google authentication uses `GoogleWebAuthService`, which has materially stronger conflict, verification, inactive/deleted-account, and linking policy.
- Admin Google authentication currently uses legacy `AuthService`, which can restore a soft-deleted user and provision an admin-guard role during authentication.
- Those behaviors violate the approved Auth ownership/security target and must not remain canonical authentication policy.
- `GoogleController` and `ClientGoogleController` should remain guard/transport adapters but converge on one identity policy.
- Auth owns `UserEmailVerification` persistence.
- Generic cache/jobs/session schema is not Auth business ownership.
- The active API `/auth` surface appears to be a stub and remains quarantined until caller proof.
- Generic `view_auth/create_auth/edit_auth/delete_auth` module permissions remain deferred pending caller proof.

## Security invariants

Authentication must not silently reactivate deleted accounts or silently acquire ownership of role/permission provisioning. Inactive/deleted identities and Google identity conflicts must be handled consistently across entry points. OAuth state validation and session-regeneration protections must be preserved.

## Delivery checkpoints

No intermediate user pull/UI test is required for internal implementation batches. The intended user checkpoint is once at the end of the primary MR after focused automated regression is complete.

If a database/security blocker requires a product or ownership decision that cannot be proven from the repository, stop and request that decision rather than guessing.

## Current status

- Read-only module audit: COMPLETE.
- User approval for consolidated implementation: COMPLETE.
- Primary branch creation: COMPLETE.
- `docs/modules/Auth/MODULE.md`: CREATED.
- Handoff initialization: COMPLETE.
- Runtime refactor implementation: NEXT.
- Persistence relocation: NOT AUTHORIZED without schema/ledger proof.
