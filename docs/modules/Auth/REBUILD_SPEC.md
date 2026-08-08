# Auth Module Rebuild Specification

## Document Status

This document is the implementation source of truth for rebuilding `Modules/Auth`.

Source documents:

- `docs/modules/Auth/ANALYSIS.md`
- `docs/modules/Auth/REFACTOR_PLAN.md`
- `ROADMAP.md`

Target platform:

- Laravel 12
- Livewire 3
- PHP 8.3
- Laravel Socialite 5
- Spatie Laravel Permission 6

No implementation code is included in this document.

## Mandatory Architecture Decisions

1. `Modules/Auth` is a `support` module, not an Admin presentation shell.
2. `App/Models/User.php` remains the canonical human identity model.
3. Guards authenticate sessions; guards do not grant administrative authority.
4. Every admin session principal must also have the `admin.access` permission.
5. The `admin` guard may continue using the shared `users` provider, but no admin route may rely on `auth:admin` alone.
6. Google OAuth never creates a new active user automatically.
7. Google OAuth may only authenticate an existing, active user who has `admin.access` and satisfies the configured Google domain policy.
8. Google access tokens and refresh tokens are not persisted.
9. External provider identity is stored separately from the `users` table.
10. Password and Google login use one shared eligibility policy.
11. Authentication writes are atomic and session creation occurs only after database commit.
12. Import and export are out of scope for Auth.
13. Cache, queue and session tables are platform infrastructure, not Auth-owned schema.
14. Self-registration is not supported by the admin Auth module.
15. `/admin/login` is the only canonical admin login route.

## Target Module Structure

The implementation should converge on this ownership:

```text
Modules/Auth/
├── config/module.php
├── config/security.php
├── Data/
│   ├── GoogleIdentityData.php
│   ├── LoginBrandingData.php
│   └── LoginResult.php
├── Enums/
│   ├── AuthProvider.php
│   └── LoginFailureReason.php
├── Events/
│   ├── LoginFailed.php
│   ├── LoginSucceeded.php
│   └── LoginThrottled.php
├── Exceptions/
│   ├── AuthenticationFailed.php
│   ├── IdentityConflict.php
│   └── LoginThrottled.php
├── Http/Controllers/
│   ├── AuthController.php
│   └── GoogleController.php
├── Livewire/
│   └── LoginForm.php
├── Models/
│   └── ExternalIdentity.php
├── Policies/
│   └── AdminLoginPolicy.php
├── Services/
│   ├── AdminAuthenticationService.php
│   ├── ExternalIdentityService.php
│   ├── LoginRateLimiter.php
│   └── LoginBrandingService.php
├── resources/views/
│   ├── layouts/auth.blade.php
│   ├── livewire/login-form.blade.php
│   └── pages/auth/login.blade.php
└── routes/web.php
```

The following current artifacts must not exist in the target state:

- `Modules/Admin/Services/AuthService.php`
- `Modules/Auth/Models/Auth.php`
- `Modules/Auth/Http/Controllers/Api/AuthController.php`
- Placeholder endpoint in `Modules/Auth/routes/api.php`
- Duplicate `Modules/Auth/Livewire/Auth/LoginForm.php` namespace layer

## 1. Database Design

### 1.1 Ownership

| Schema | Owner | Decision |
|---|---|---|
| `users` | User domain | Canonical user identity remains in `App/Models/User.php`. |
| `external_identities` | Auth support module | Stores provider-to-user links without provider tokens. |
| Roles and permissions | Role module | Spatie tables remain canonical authorization storage. |
| `sessions` | Platform/database infrastructure | Must not depend on Auth module enablement. |
| `cache`, `cache_locks` | Platform/database infrastructure | Move out of Auth migration ownership. |
| `jobs`, `job_batches`, `failed_jobs` | Platform/database infrastructure | Move out of Auth migration ownership. |
| `settings` | Admin/settings owner | Auth reads branding through a contract, not the model directly. |

### 1.2 `users` Table

Source model:

- `App/Models/User.php`

Historical migrations:

- `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php`
- `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php`

Required Auth-facing columns:

| Column | Type | Constraint | Purpose |
|---|---|---|---|
| `id` | bigint unsigned | Primary key | User identity. |
| `email` | varchar | Unique, not null | Canonical normalized login identifier. |
| `password` | varchar | Nullable only when business rules require provider-only accounts | Password credential hash. |
| `is_active` | boolean | Not null, default true | Global account enablement. |
| `last_login_at` | timestamp | Nullable | Last successful authentication time. |
| `deleted_at` | timestamp | Nullable | Soft-delete eligibility check. |

Target changes:

- `google_id` is migrated to `external_identities.provider_user_id`, then removed from `users`.
- `google_token` is removed.
- `google_refresh_token` is removed.
- Historical migrations already applied in production must not be edited or renamed.
- Schema changes require new forward-only migrations under the owning module.

Planned migration paths:

- `Modules/Auth/database/migrations/2026_06_15_000000_create_external_identities_table.php`
- `Modules/User/database/migrations/2026_06_15_000001_migrate_google_identity_data.php`
- `Modules/User/database/migrations/2026_06_15_000002_remove_google_columns_from_users_table.php`

Exact timestamps may change to avoid repository collision, but names and ownership must remain semantically equivalent.

### 1.3 `external_identities` Table

Owner:

- `Modules/Auth/Models/ExternalIdentity.php`

Schema:

| Column | Type | Constraint | Purpose |
|---|---|---|---|
| `id` | bigint unsigned | Primary key | Internal identity link ID. |
| `user_id` | bigint unsigned | Foreign key to `users.id`, cascade delete | Owner user. |
| `provider` | varchar(32) | Not null | Provider enum value, initially `google`. |
| `provider_user_id` | varchar(191) | Not null | Stable provider subject/user ID. |
| `provider_email` | varchar(254) | Not null | Normalized provider email observed at link time. |
| `linked_at` | timestamp | Not null | Initial successful link time. |
| `last_used_at` | timestamp | Nullable | Last successful provider login. |
| `created_at` | timestamp | Not null | Laravel timestamp. |
| `updated_at` | timestamp | Not null | Laravel timestamp. |

Required constraints:

- Unique index on `provider, provider_user_id`.
- Unique index on `user_id, provider`.
- Index on `provider_email`.
- Foreign key from `user_id` to `users.id` with cascade delete.
- No access token, refresh token or raw provider payload column.
- Provider email is reference/audit data, not the primary linking key after initial link.

### 1.4 External Identity Linking Rules

1. Existing `provider + provider_user_id` link is authoritative.
2. If no provider link exists, an existing local user may be linked only when all conditions pass:
   - Google reports a verified email.
   - Normalized Google email exactly matches `users.email`.
   - User is active and not soft deleted.
   - User has `admin.access`.
   - Email domain passes configured allowlist.
   - User has no conflicting Google identity.
3. No new `users` record is created by OAuth.
4. A provider identity already linked to another user produces `IdentityConflict`.
5. A user already linked to another Google subject produces `IdentityConflict`.
6. Identity conflict is never auto-repaired in a login request.

### 1.5 Email Normalization

Canonical normalization:

- Trim surrounding whitespace.
- Convert to lowercase using a locale-independent operation.
- Do not remove dots, plus tags or otherwise rewrite provider-specific mailbox semantics.
- Store and query the normalized result.

The same normalized value must be used for:

- Validation output.
- Password authentication lookup.
- Rate limiter key.
- Google email comparison.
- Structured security event correlation.

### 1.6 Permission Storage

Canonical capability:

- `admin.access`

Permission guard:

- `admin.access` uses Spatie `guard_name = admin`.
- `AdminLoginPolicy` checks the capability explicitly in the `admin` permission context.
- Implementation must not depend on the default guard inferred from an unrelated request.

The generic permissions below are invalid and must be removed:

- `view_auth`
- `create_auth`
- `edit_auth`
- `delete_auth`

Role names do not substitute for capability checks. Roles may aggregate `admin.access`, but policies and middleware must authorize the permission.

Relevant paths:

- `Modules/Auth/config/module.php`
- `App/Models/User.php`
- Spatie permission schema under `Modules/Role/database/migrations/`
- Permission synchronization at `Modules/Role/database/seeders/PermissionSeeder.php`

### 1.7 Platform Infrastructure Migrations

The following current migrations must be removed from Auth ownership through a documented migration baseline, not by rewriting production history:

- `Modules/Auth/database/migrations/-0001_11_30_000000_create_cache_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000001_create_cache_locks_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000002_create_jobs_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000003_create_job_batches_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000004_create_failed_jobs_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000008_create_sessions_table.php`

Target ownership:

- Root `database/migrations/`.

Migration rules:

- Never rename a migration already recorded in production.
- Establish the transition in `docs/database/MIGRATION_BASELINE.md`.
- Fresh install must create infrastructure tables even when Auth is disabled.
- Upgrade install must not create duplicate tables.
- `sessions.user_id` remains indexed without a foreign key in the initial rebuild. This preserves session cleanup independence and avoids delete coupling. The decision must be documented.

### 1.8 Data Retention and Sensitive Data

- Password hashes remain Laravel-managed.
- OAuth tokens must not be stored.
- Raw Socialite payloads must not be stored.
- Provider email is retained while the identity link exists.
- Deleting a user cascades deletion of external identities.
- Authentication logs must follow the central logging retention policy and must not contain passwords or tokens.

## 2. Model Design

### 2.1 `App\Models\User`

Path:

- `App/Models/User.php`

Responsibilities:

- Canonical authenticatable identity.
- Own active/deleted state.
- Own password credential.
- Expose Spatie roles and permissions.
- Expose external identity relationship.

Required relationships:

- `externalIdentities(): HasMany`

Required model behavior:

- `is_active` cast to boolean.
- `last_login_at` uses Laravel `immutable_datetime` cast.
- Password uses Laravel `hashed` cast.
- Password, remember token and any legacy OAuth token columns remain hidden during transition.
- The model must not contain provider-specific login orchestration.
- The model must not decide route redirects or create sessions.

Authorization rule:

- Administrative eligibility requires `is_active`, not soft deleted and `hasPermissionTo('admin.access', 'admin')`.
- This invariant is evaluated by `Modules/Auth/Policies/AdminLoginPolicy.php`, not duplicated in model/controller/Livewire code.

### 2.2 `Modules\Auth\Models\ExternalIdentity`

Path:

- `Modules/Auth/Models/ExternalIdentity.php`

Responsibilities:

- Represent one external provider link.
- Enforce provider value casting.
- Expose the owning user relationship.
- Contain no OAuth token.

Required casts:

- `provider` -> `Modules/Auth/Enums/AuthProvider.php`
- `linked_at` -> immutable datetime
- `last_used_at` -> immutable datetime

Required relationship:

- `user(): BelongsTo`

Mass assignment:

- Prefer explicit guarded/fillable policy consistent with repository standards.
- `user_id`, `provider`, `provider_user_id` and `provider_email` may only be written by `ExternalIdentityService`.

### 2.3 Enums and DTOs

`Modules/Auth/Enums/AuthProvider.php`:

- Initial value: `google`.
- No free-form provider string outside persistence mapping.

`Modules/Auth/Enums/LoginFailureReason.php`:

- `INVALID_CREDENTIALS`
- `INACTIVE`
- `NOT_AUTHORIZED`
- `DOMAIN_NOT_ALLOWED`
- `EMAIL_NOT_VERIFIED`
- `IDENTITY_CONFLICT`
- `THROTTLED`
- `PROVIDER_FAILURE`

`Modules/Auth/Data/GoogleIdentityData.php`:

- Provider user ID.
- Normalized email.
- Email verified flag.
- Hosted domain when available.
- Display name only if needed for logging/UI; it must not be used to create a user.
- No token fields.

`Modules/Auth/Data/LoginResult.php`:

- Authenticated user ID.
- Authentication provider.
- Redirect route name.
- No Eloquent model serialization requirement.

`Modules/Auth/Data/LoginBrandingData.php`:

- Logo URL/path.
- Organization name line 1.
- Organization name line 2.
- Login description.
- Immutable after construction.

### 2.4 Removed Model

`Modules/Auth/Models/Auth.php` must be deleted. There is no `auths` table or Auth aggregate represented by that class.

## 3. Service Design

### 3.1 Service Ownership Rules

- Services under `Modules/Auth/Services/` own authentication application logic.
- `Modules/Admin` may call Auth services but Auth must not depend on Admin services.
- Controllers and Livewire components do not perform direct authentication queries or role provisioning.
- Services accept validated DTO/scalar input and enforce business invariants again.
- Services return typed results or throw Auth domain exceptions.

### 3.2 `AdminAuthenticationService`

Path:

- `Modules/Auth/Services/AdminAuthenticationService.php`

Public methods:

| Method | Input | Output | Responsibility |
|---|---|---|---|
| `authenticatePassword` | Normalized email, password, remember flag, request context | `LoginResult` | Rate-limit, authenticate, authorize admin access, create session and record success/failure. |
| `authenticateGoogle` | `GoogleIdentityData`, request context | `LoginResult` | Validate domain/verified email, resolve/link identity transactionally, authorize, create session and record success/failure. |
| `logoutAdmin` | Current request/session context | void | Logout admin guard, invalidate intended session state and regenerate CSRF token. |

Mandatory behavior:

- Generic user-facing failure message for invalid credentials and unauthorized account.
- Specific internal failure reason for logs/tests.
- Password and OAuth both call `AdminLoginPolicy`.
- Session regeneration occurs after successful login.
- `last_login_at` updates on successful login only.
- No raw exception reaches the browser.
- No provider token is persisted or logged.

### 3.3 `ExternalIdentityService`

Path:

- `Modules/Auth/Services/ExternalIdentityService.php`

Public methods:

| Method | Input | Output | Responsibility |
|---|---|---|---|
| `resolveGoogleIdentity` | `GoogleIdentityData` | `User` | Resolve an existing provider link or safely link an eligible existing user. |
| `linkGoogleIdentity` | Eligible `User`, `GoogleIdentityData` | `ExternalIdentity` | Create a provider link under transaction/unique constraints. |
| `touchLastUsed` | `ExternalIdentity` | void | Update provider identity usage after successful authentication. |

Mandatory behavior:

- Never create a user.
- Never assign roles or permissions.
- Never create a session.
- Never persist provider tokens.
- Treat unique database constraints as authoritative.
- Convert link conflicts into `IdentityConflict`.

### 3.4 `AdminLoginPolicy`

Path:

- `Modules/Auth/Policies/AdminLoginPolicy.php`

Required decision:

`canLogin(User $user): bool` is true only when:

- User exists.
- User is not soft deleted.
- `is_active` is true.
- User has `admin.access`.

Google-specific domain and verified-email checks occur before this policy. The policy remains provider-neutral.

### 3.5 `LoginRateLimiter`

Path:

- `Modules/Auth/Services/LoginRateLimiter.php`

Required contract:

- Key: hash of normalized email plus request IP.
- Maximum: 5 failed attempts.
- Decay: 60 seconds for the initial implementation.
- Successful login clears the key.
- Throttled attempts do not invoke password verification.
- Failure messages do not reveal account existence.
- Configuration values must be read from `Modules/Auth/config/security.php`, merged as `auth.security`, and must not be hard-coded in Blade/Livewire.

The implementation may increase backoff later, but tests must lock the configured behavior.

### 3.6 `LoginBrandingService`

Path:

- `Modules/Auth/Services/LoginBrandingService.php`

Responsibility:

- Return all login branding values in one read operation.
- Abstract the current `Modules/Admin/Models/Setting.php` dependency.
- Depend on `Modules/Shared/Contracts/SettingsReader.php`.
- The settings reader uses one `whereIn` query for the required keys.
- Cache only when write-side invalidation exists.

Returned values:

- Logo URL/path.
- Name line 1.
- Name line 2.
- Login description.

Fallbacks remain centralized in this service, not in Livewire.

Related paths:

- `Modules/Admin/Models/Setting.php`
- `Modules/Admin/Services/SettingsService.php`
- `Modules/Shared/Contracts/SettingsReader.php`

### 3.7 Events and Logging

Domain/application events:

- `Modules/Auth/Events/LoginSucceeded.php`
- `Modules/Auth/Events/LoginFailed.php`
- `Modules/Auth/Events/LoginThrottled.php`

Structured log fields:

- Event name.
- Correlation/request ID.
- Provider.
- User ID when known.
- Hashed normalized-email correlation value.
- IP address according to central privacy policy.
- Failure reason enum.
- Timestamp.

Forbidden log fields:

- Password.
- Access token.
- Refresh token.
- Raw Socialite payload.
- Full exception response shown to the user.

### 3.8 Exception Mapping

Paths:

- `Modules/Auth/Exceptions/AuthenticationFailed.php`
- `Modules/Auth/Exceptions/IdentityConflict.php`
- `Modules/Auth/Exceptions/LoginThrottled.php`
- `bootstrap/app.php`

Rules:

- Browser receives stable Vietnamese user messages.
- Logs retain internal reason and exception chain.
- Identity conflict is not reported as “account not found”; it uses a generic support-oriented message without exposing the linked account.
- Provider/network errors return a generic retry-later message.

## 4. Livewire Design

### 4.1 Component

Target path:

- `Modules/Auth/Livewire/LoginForm.php`

Target alias:

- `auth.login-form`

View:

- `Modules/Auth/resources/views/livewire/login-form.blade.php`

Responsibilities:

- Own form state.
- Perform input-shape validation.
- Normalize email.
- Call `AdminAuthenticationService::authenticatePassword`.
- Map safe domain failures to validation/UI messages.
- Redirect using `LoginResult`.

Non-responsibilities:

- Direct `Auth::attempt`.
- Direct Eloquent queries.
- Permission/role checks.
- Settings queries.
- OAuth callback handling.
- Transaction management.

### 4.2 Public State

| Property | Type | Default | Validation |
|---|---|---|---|
| `email` | string | `''` | required, string, email, max 254 |
| `password` | string | `''` | required, string, max 1024 |
| `remember` | bool | false | boolean |

`LoginBrandingService` returns `LoginBrandingData`; `mount()` maps it to typed public string properties used by the Blade view. The DTO itself is not exposed as mutable Livewire state.

Sensitive-state rules:

- Password is never placed in query strings, browser events or logs.
- Password is cleared after failed authentication unless UX testing proves this materially harms usability.
- Validation and authentication errors use the same generic credential message where account enumeration is possible.

### 4.3 Lifecycle

`mount()`:

- Redirect authenticated admin with `admin.access` to `admin.dashboard`.
- Load branding using one service call.
- Must not perform four independent settings queries.

`login()`:

1. Validate state.
2. Normalize email.
3. Invoke service.
4. On success, redirect to the route from `LoginResult`.
5. On known failure, add safe error.
6. Do not catch and display raw exception text.

`render()`:

- Return only `Auth::livewire.login-form`.
- Must not query database.

### 4.4 Livewire Request and UX Rules

- Form uses `wire:submit="login"`.
- Submit button uses `wire:loading.attr="disabled"` and `wire:target="login"`.
- Loading text is scoped to `login`.
- Email input uses `autocomplete="username"`.
- Password input uses `autocomplete="current-password"`.
- Inputs have explicit labels and accessible error association.
- Google login is a normal link to `admin.google.redirect`; it is not a Livewire action.
- Double submission must be harmless server-side even with UI protection.

### 4.5 Route Contract

Target routes in `Modules/Auth/routes/web.php`:

| Method | URI | Name | Middleware | Handler |
|---|---|---|---|---|
| GET | `/admin/login` | `admin.login` | `web`, `guest:admin` | `AuthController@login` |
| GET | `/admin/auth/google` | `admin.google.redirect` | `web`, `guest:admin`, OAuth redirect throttle | `GoogleController@redirect` |
| GET | `/admin/auth/google/callback` | `admin.google.callback` | `web`, `guest:admin`, OAuth callback throttle | `GoogleController@callback` |
| POST | `/admin/logout` | `admin.logout` | `web`, `auth:admin` | `AuthController@logout` |

Removed routes:

- `/login`
- `/register`
- `/auth/google`
- `/auth/google/callback`
- `/api/auth`

Post-login authorization:

- `admin.dashboard` and every Admin route require both `auth:admin` and `permission:admin.access,admin`.

### 4.6 Controller Contract

`Modules/Auth/Http/Controllers/AuthController.php`:

- `login()` renders the login page only.
- `logout()` delegates to `AdminAuthenticationService`.
- No `register()` method.

`Modules/Auth/Http/Controllers/GoogleController.php`:

- `redirect()` configures Socialite redirect.
- `callback()` maps Socialite user to `GoogleIdentityData`, calls `AdminAuthenticationService` and performs safe redirect/error handling.
- No Eloquent, role or session business logic.

## 5. Import Design

### 5.1 Scope Decision

Auth has no import capability.

The following are explicitly forbidden in Auth:

- Bulk user import.
- Password import.
- Role/permission import.
- External identity import.
- OAuth token import.

Rationale:

- User lifecycle belongs to User/Account ownership.
- Role assignment belongs to Role ownership.
- Authentication identity linking must occur through verified login or a separately approved administrative workflow, never spreadsheet import.

### 5.2 Future Change Rule

Any future import request must:

- Be specified in the owning domain module.
- Use `Modules/Shared/Services/ImportExport`.
- Require explicit authorization.
- Reject password hashes, access tokens and refresh tokens.
- Not write `external_identities` directly.

No files under `Modules/Auth/Imports/` should be created for the current rebuild.

## 6. Export Design

### 6.1 Scope Decision

Auth has no user-facing export capability.

The following must never be exportable:

- Password hashes.
- Remember tokens.
- OAuth access/refresh tokens.
- Session payloads.
- Raw authentication provider payloads.
- Rate limiter state.

### 6.2 Security Audit Data

If central audit export is introduced later:

- It belongs to System/Shared observability ownership, not Auth UI.
- It requires a dedicated capability such as `security.audit.export`.
- It exports redacted structured events only.
- It is queued, private, time-limited and audited.

No files under `Modules/Auth/Exports/` should be created for the current rebuild.

## 7. Permissions

### 7.1 Capability Matrix

| Capability | Purpose | Required by |
|---|---|---|
| `admin.access` | Enter and use the administrative application | Every Admin route and successful Auth admin login |

Auth does not own domain action permissions such as settings management, user management or database operations. Those capabilities belong to their domain modules.

### 7.2 Authorization Layers

1. `guest:admin` prevents authenticated admin users from reopening login/OAuth entry routes.
2. `auth:admin` requires an authenticated admin guard session.
3. `permission:admin.access,admin` proves administrative authority.
4. Domain routes add their own capability after `admin.access`.
5. Hidden UI controls never substitute for server authorization.

### 7.3 Google Login Eligibility

Google login is allowed only when:

- Google provider response is valid.
- Email is present and verified.
- Email domain is in `config('services.google.allowed_domains')`.
- Existing local user is active and not soft deleted.
- Existing local user has `admin.access`.
- External identity has no conflict.

Configuration path:

- `config/services.php`

Required configuration:

- `client_id`
- `client_secret`
- `redirect`
- `allowed_domains` as a normalized array

Missing required Google configuration must fail closed. The Google button may be hidden when the provider is intentionally disabled, but server routes must still deny operation.

### 7.4 Permission Provisioning

- `Modules/Auth/config/module.php` declares `admin.access`.
- `Modules/Role/database/seeders/PermissionSeeder.php` synchronizes it.
- Existing approved admin roles receive `admin.access` through an explicit data migration/seeder reviewed by the business owner.
- New OAuth users are never automatically assigned roles.
- `Super Admin` bypass behavior in `Modules/ModuleServiceProvider.php` remains subject to a separate architecture/security review, but tests must prove it does not allow an unauthenticated principal.

## 8. Transactions

### 8.1 Password Login

Password login does not require a transaction for credential verification.

Required sequence:

1. Check rate limiter.
2. Retrieve the user and verify the password without creating a session.
3. Evaluate `AdminLoginPolicy`.
4. Update `last_login_at` inside a short database transaction.
5. Commit the database transaction.
6. Create the `admin` guard session with the requested remember behavior.
7. Regenerate session ID.
8. Clear limiter.
9. Emit success event.

If the `last_login_at` transaction fails, no session is created and authentication fails closed.

### 8.2 Google Identity Resolution

The database transaction includes:

- Resolve user by existing external identity, or resolve eligible user by normalized email.
- Recheck user active/deleted state.
- Recheck `admin.access`.
- Create external identity when first linking.
- Update `external_identities.last_used_at`.
- Update `users.last_login_at`.

The transaction excludes:

- Socialite network call.
- Browser redirect.
- Session creation.
- Logging to external transports.

Session creation occurs only after commit.

### 8.3 Isolation and Concurrency

- Unique constraints on `provider, provider_user_id` and `user_id, provider` are authoritative.
- Duplicate-key exceptions are translated to `IdentityConflict` unless a bounded retry can prove the winning row represents the same user/provider relationship.
- Retry count is at most one.
- Do not use an unbounded retry loop.
- Do not use `firstOrCreate` as the only concurrency control.

### 8.4 Role and Permission Writes

Login requests do not create roles, permissions or role assignments.

All role provisioning occurs through:

- Reviewed seeders/migrations.
- Authorized Role/User administrative workflows.

This removes role writes from the authentication transaction.

### 8.5 Failure Atomicity

The following must leave no partial database change:

- Inactive user OAuth attempt.
- User without `admin.access`.
- Unverified or disallowed Google email.
- Conflicting provider identity.
- Failed first-time identity link.

No provider identity fields may be updated before eligibility passes.

## 9. UI Components

### 9.1 Page and Layout

Page:

- `Modules/Auth/resources/views/pages/auth/login.blade.php`

Layout:

- `Modules/Auth/resources/views/layouts/auth.blade.php`

Composition decision:

- Use Blade template inheritance with `@extends` and `@yield`.
- Remove `$slot` support from the Auth layout.

Layout responsibilities:

- Document metadata.
- Vite assets.
- Livewire styles/scripts.
- Auth page content.

Layout must not:

- Read `env()` directly.
- Publish chat configuration.
- Load Admin dashboard-only assets.
- Contain business logic or database queries.

### 9.2 Login Form

View:

- `Modules/Auth/resources/views/livewire/login-form.blade.php`

Required UI elements:

- Configurable logo.
- Organization name line 1.
- Organization name line 2.
- Login description.
- Email input.
- Password input.
- Remember checkbox.
- Submit button with loading/disabled state.
- Google Workspace button only when Google login is enabled.
- Safe validation/authentication error area.

Required semantics:

- Correct labels and autocomplete.
- Visible keyboard focus.
- Disabled state during submit.
- No account-existence disclosure.
- No raw exception text.
- Responsive width using valid Tailwind utilities.

### 9.3 Styling

- Auth UI uses the existing Tailwind 4 Vite entry points:
  - `resources/css/tailwind.css`
  - `resources/js/tailwind.js`
  - `vite.config.js`
- `w-128` must not be used unless explicitly defined.
- Prefer standard utility or a documented arbitrary value.
- Auth UI must not introduce Bootstrap/AdminLTE dependencies.

This is intentionally isolated from the broader Bootstrap/AdminLTE reconciliation in `ROADMAP.md`.

### 9.4 Branding Query Contract

- One service call per page mount.
- Maximum one database query when cache is cold.
- Zero settings queries when valid cache is warm.
- Cache requires explicit invalidation from:
  - `Modules/Admin/Services/SettingsService.php`
  - The settings write workflow.

### 9.5 UI Error Messages

Recommended stable messages:

| Condition | User-facing behavior |
|---|---|
| Invalid password/email | Generic incorrect-login message |
| Inactive or unauthorized user | Generic access-denied login message |
| Throttled | Retry-after message without account disclosure |
| Google provider unavailable | Generic temporary provider error |
| Identity conflict | Contact-administrator message |

Internal reason remains available only in logs/tests.

## 10. Test Strategy

### 10.1 Test Ownership

Target test paths:

```text
tests/
├── Architecture/
│   ├── ModuleDependencyTest.php
│   └── UnusedModuleArtifactsTest.php
├── Feature/
│   ├── Architecture/
│   │   └── FreshMigrationTest.php
│   └── Modules/Auth/
│       ├── AdminGuardAuthorizationTest.php
│       ├── AuthRoutesTest.php
│       ├── GoogleIdentityLinkingTest.php
│       ├── GoogleLoginErrorHandlingTest.php
│       ├── GoogleLoginQueryTest.php
│       ├── GoogleLoginTest.php
│       ├── GoogleProvisioningTransactionTest.php
│       ├── LoginFormViewTest.php
│       ├── LoginPageQueryTest.php
│       ├── LoginPageTest.php
│       ├── LoginRateLimitTest.php
│       ├── LogoutTest.php
│       ├── PasswordLoginTest.php
│       └── PasswordLoginValidationTest.php
└── Unit/
    └── Modules/Auth/
        ├── AdminLoginPolicyTest.php
        ├── EmailNormalizationTest.php
        └── LoginRateLimiterTest.php
```

### 10.2 Route Tests

Must prove:

- `GET /admin/login` is available to guests.
- Authenticated eligible admin is redirected away from login.
- `/login` does not exist.
- `/register` does not exist.
- `/api/auth` does not exist.
- Google routes use canonical admin-prefixed names.
- `POST /admin/logout` rejects guests.
- Admin routes reject authenticated users without `admin.access`.
- Route handlers/classes resolve successfully.

### 10.3 Password Login Tests

Must cover:

- Valid active admin succeeds.
- Session ID regenerates.
- Remember flag is respected.
- `last_login_at` updates only on success.
- Wrong password fails with generic message.
- Unknown email and wrong password produce equivalent user-facing behavior.
- Inactive user fails.
- Soft-deleted user fails.
- Active user without `admin.access` fails.
- Email trimming/lowercase normalization works.
- Oversized/invalid input fails validation.
- Password never appears in logs.

### 10.4 Rate Limiter Tests

Must cover:

- Five configured failures are allowed/recorded according to limiter semantics.
- Next attempt is throttled.
- Key includes normalized email and IP.
- Different IP or normalized email produces the expected independent key.
- Success clears limiter.
- Throttled request does not perform password verification.
- Retry-after behavior is deterministic under a frozen clock.

### 10.5 Google OAuth Tests

Socialite must be faked; tests must not call Google.

Must cover:

- Existing linked eligible admin succeeds.
- Existing eligible admin with matching verified email links once and succeeds.
- External domain fails.
- Unverified email fails.
- Missing email fails.
- Inactive user fails.
- User without `admin.access` fails.
- Unknown email does not create a user.
- Provider identity linked to another user fails.
- User linked to another Google subject fails.
- Repeated callback is idempotent.
- Concurrent link conflict resolves safely.
- Access/refresh tokens are not persisted.
- Raw provider exception is not shown to user.

### 10.6 Transaction Tests

Must prove rollback for:

- Identity link insert followed by a simulated failure.
- `last_login_at` failure.
- Identity conflict.
- Permission failure after user resolution.

Must prove:

- Session is not created before commit.
- Role/permission rows are not created during login.
- Retry is bounded.

### 10.7 Livewire Tests

Must cover:

- Component alias `auth.login-form` resolves.
- Typed state defaults are correct.
- Validation rules are enforced.
- Successful action redirects to `admin.dashboard`.
- Failure adds safe field/general error.
- Submit view contains loading disable attributes.
- Inputs contain required autocomplete attributes.
- `render()` performs no database query beyond data prepared in mount.

### 10.8 Query Budgets

Initial budgets:

| Flow | Budget |
|---|---|
| Login page branding, cold cache | Maximum 1 settings query |
| Login page branding, warm cache | 0 settings queries |
| Invalid password login | No relationship N+1; fixed bounded queries |
| Existing linked Google login | Fixed bounded queries; no loop-driven queries |

Tests should assert query counts for stable local flows. Production-like profiling must validate the `provider_user_id` and email lookup execution plans.

### 10.9 Migration Tests

Must cover:

- Fresh migration creates platform tables independently of Auth enablement.
- `external_identities` constraints exist.
- Legacy Google IDs migrate without duplication.
- Token columns are removed only after data/link migration.
- Upgrade migration is idempotent at deployment level.
- MySQL production path passes.
- SQLite test limitations are documented rather than silently ignored.

### 10.10 Architecture Tests

Must enforce:

- `Modules/Auth` does not import `Modules/Admin/Services`.
- Controllers do not query Eloquent directly.
- Livewire component does not call `Auth::attempt` directly.
- `Modules/Auth/Models/Auth.php` does not exist.
- No Auth import/export classes exist.
- No Auth migration creates cache/queue/session tables in the target baseline.
- Auth manifest type is `support`.

### 10.11 Security Logging Tests

Must prove:

- Success/failure/throttle events are emitted.
- Logs contain correlation ID and failure reason.
- Logs do not contain password, access token, refresh token or raw Socialite payload.
- Browser responses do not contain exception messages.

### 10.12 CI Gates

Required gates before merge:

- Auth feature/unit tests.
- Route/module boot tests.
- Fresh migration smoke test.
- Static analysis when repository tooling is established.
- Laravel Pint.
- Frontend production build.
- No P0 denied-path test may be skipped.

Relevant files:

- `phpunit.xml`
- `.github/workflows/tests.yml`
- `composer.json`
- `package.json`

## Implementation Sequence

### Stage 1 - Security Contract

1. Add tests for denied and allowed admin login.
2. Add `admin.access`.
3. Enforce eligibility for password and Google.
4. Add rate limiting.
5. Stop storing Google tokens.
6. Stop exposing raw exceptions.

### Stage 2 - Architecture and Data

1. Create Auth-owned services, DTOs, enums, policy and exceptions.
2. Create `external_identities`.
3. Migrate legacy Google ID data.
4. Remove legacy Google token/identity columns.
5. Remove `Modules/Admin/Services/AuthService.php`.
6. Normalize routes and Livewire alias.
7. Establish platform migration baseline.

### Stage 3 - UI, Performance and Cleanup

1. Replace four settings reads with one branding service call.
2. Simplify Auth layout.
3. Complete Livewire loading/accessibility behavior.
4. Remove placeholder model/API artifacts.
5. Add query budgets, architecture tests and build gates.

## Definition of Done

The Auth rebuild is complete only when:

- Google cannot create a user or grant admin authority implicitly.
- Every successful admin login principal has `admin.access`.
- Inactive, deleted and unauthorized users fail for every provider.
- Password login is rate limited.
- OAuth tokens are absent from persistent storage and logs.
- External identity linking is atomic and conflict-safe.
- Session creation occurs only after successful authorization and transaction commit.
- Auth no longer depends on `Modules/Admin/Services/AuthService.php`.
- `/admin/login` is the sole admin login route.
- Placeholder Auth API/model artifacts are removed.
- Cache/queue/session schema no longer depends on Auth module enablement.
- Import/export remain absent from Auth.
- All required security, transaction, route, Livewire, query and migration tests pass.
