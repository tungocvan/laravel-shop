# Admission Livewire Analysis — Search

## Executive Summary

Target component:

`Modules/Admission/Livewire/Search.php`

Livewire alias:

`admission.search`

Primary view:

`Modules/Admission/resources/views/livewire/search.blade.php`

Parent page:

`Modules/Admission/resources/views/pages/public/search.blade.php`

Public route:

`GET /admission/search/{ma_dinh_danh?}/{password?}` → `admission.search`

Purpose: allow lookup of an admission result using a 12-digit student identity number plus a birth-date-derived password, then show approved class assignment information.

Recommendation: **Focused Refactor before any feature expansion.** The component is small and does not need a rebuild, but the current lookup flow has a P0 sensitive-data exposure risk, weak/public credential handling, no rate limiting, several correctness defects, and no focused runtime tests.

Application source code was not modified during this analysis.

## Component Purpose

Observed behavior:

1. Accept optional `ma_dinh_danh` and `password` route/component parameters.
2. Copy them into public Livewire state.
3. Validate identifier/password format manually.
4. Query an `AdmissionApplication` by `ma_dinh_danh`.
5. Derive the expected password from `ngay_sinh` using `dmY` (`DDMMYYYY`).
6. If the password matches and status is `approved`, open a modal showing admission/class information.
7. Otherwise show an error message.

The component performs no writes, uploads, downloads, pagination, sorting, or mutations.

## Dependency Flow

```text
GET /admission/search/{ma_dinh_danh?}/{password?}
    -> AdmissionController::search()
    -> pages.public.search
    -> admission.search
    -> AdmissionApplication
    -> admission_applications

admission.search::render()
    -> SchoolSettingService::all()
```

The route is intentionally public (`web` middleware only). There is no `auth:admin` or permission middleware on the lookup route.

## Livewire PHP Analysis

### Public state

The component exposes:

- `MaDinhDanh`
- `password`
- `app`
- `showModal`
- `message`

`MaDinhDanh` and `password` are expected input state. `app` is problematic because it can contain the complete application row, including data that the Blade does not display.

### Lifecycle: mount()

`mount($ma_dinh_danh = null, $password = null)` assigns:

```php
$this->MaDinhDanh = $ma_dinh_danh ?? '';
$this->password = $password ?? '';
```

but then checks:

```php
if (empty($this->ma_dinh_danh) || empty($this->password)) {
    return;
}
```

The declared property is `MaDinhDanh`, not `ma_dinh_danh`.

**P1 correctness:** the mismatched property name means the intended auto-login path from route parameters is not reliable and is likely short-circuited before `login()`.

### login()

The action:

1. clears `message` and `showModal`;
2. manually validates identifier/password shape;
3. loads the application using `where('ma_dinh_danh', ...)->firstOrFail()`;
4. converts the entire model to an array and assigns it to public `$app`;
5. derives password from `ngay_sinh`;
6. compares password;
7. checks `status === approved`;
8. opens the modal.

This ordering is the primary security issue.

### closeModal()

Only sets `showModal = false`.

It does not clear `$app`, so successful application data remains in component state after closing the modal.

### render()

Calls `SchoolSettingService::all()` on every render and passes settings to Blade.

The service is cached, so DB cost is likely low after warm cache, but a render-time service lookup remains coupled to every Livewire request.

## Validation / Contract Consistency

Validation is manual via `preg_match` rather than Livewire validation rules.

Identifier rule:

- exactly 12 digits.

Password code rule:

- exactly 8 digits.

However the user-facing messages are inconsistent:

- one message says `6 chữ số (ddmmyy)`;
- the input label says `ddmmyyyy`;
- the generated password uses Carbon `format('dmY')`, which is `DDMMYYYY` (8 digits);
- the wrong-password message again says `ddmmyy`.

**P1 correctness/UX:** password format contract is internally inconsistent even though the runtime comparison expects 8-digit `DDMMYYYY`.

## P0 — Sensitive Application Data Loaded Before Credential Verification

The source executes:

```php
$this->app = AdmissionApplication::where('ma_dinh_danh', $this->MaDinhDanh)
    ->firstOrFail()
    ->toArray();
```

before comparing the supplied password with the birth-date-derived password.

`$app` is a public Livewire property.

The `AdmissionApplication` model contains sensitive student/family fields including:

- date of birth;
- student identity number;
- addresses;
- phone numbers;
- parent/guardian CCCDs;
- health notes;
- parent/guardian information;
- admission status and class assignment;
- document paths/review metadata where present in model attributes.

The Blade only displays a subset, but the public component state is broader than the rendered subset.

**Inference based on Livewire public-state semantics:** assigning the full record to public state before credential verification can expose more application data to the client than intended on a failed login request.

**Direction:** never place the full model/row in public Livewire state before credentials are verified. Query only what is needed for verification first; after successful verification, expose only an explicit safe result DTO/array containing fields actually required by the modal.

## P0/P1 — Public Credential Design

The public route accepts both credentials in the URL path:

```text
/admission/search/{ma_dinh_danh?}/{password?}
```

This means the identity number and birth-date password can appear in:

- browser history;
- server/proxy access logs;
- copied/shared URLs;
- analytics or monitoring systems depending on deployment;
- referrer contexts depending on browser/navigation behavior.

**P0/P1 security/privacy:** secrets/PII should not be transported in reusable GET path segments for the normal lookup workflow.

The component also uses date of birth itself as the password (`DDMMYYYY`). This is predictable personal information rather than a strong secret.

Changing the credential model is a product/security decision and may be more than a pure refactor. A focused refactor can at minimum stop placing credentials in generated URLs and harden the existing contract; replacing DOB authentication with a stronger lookup token/PIN should be handled as an explicit feature/security change if desired.

## P0/P1 — No Rate Limiting / Brute-force Protection

The public search route has only `web` middleware. No throttle middleware or component/service-level attempt limiter is present.

Because the identifier space and DOB-derived password are guessable personal data, repeated lookup attempts are possible without an observed rate limit.

**Direction:** add repository-consistent rate limiting keyed appropriately (IP plus normalized identifier, or equivalent), while avoiding user enumeration in responses.

## Error Handling / Enumeration

### firstOrFail() on unknown identifier

Unknown `ma_dinh_danh` triggers `ModelNotFoundException`/404 before the intended code:

```php
if (!$this->app) {
    $this->message = 'Không tìm thấy hồ sơ.';
}
```

That branch is unreachable after `firstOrFail()`.

This causes inconsistent behavior between:

- invalid/missing identifier;
- unknown identifier;
- wrong password;
- pending/not-approved application.

It can also make account/application enumeration easier because callers may distinguish unknown identifier by HTTP behavior from wrong password.

**P1 security/correctness:** use a controlled lookup result and generic authentication failure message where appropriate.

## Data Integrity / Identifier Uniqueness

The lookup uses:

```php
where('ma_dinh_danh', ...)->firstOrFail()
```

The migration adds a normal index on `ma_dinh_danh`, not a unique index.

Therefore multiple applications with the same identity number are technically allowed at the database level, and `firstOrFail()` selects an arbitrary first matching record according to DB query order.

Whether multiple applications per student are valid is an unresolved business rule.

**P1 correctness:** the lookup contract needs an explicit duplicate policy before adding a unique constraint. If duplicates are valid across admission years, lookup may need another discriminator such as year/application code.

## Authorization

There is no authenticated user authorization because this is a public lookup feature.

The security boundary is therefore credential verification + rate limiting + minimal disclosed data.

No model policy or permission is observed in this flow.

This makes correct credential handling substantially more important than in admin-only components.

## Service / Model Boundary

The Livewire component directly queries `AdmissionApplication` and owns:

- credential verification;
- date-of-birth password derivation;
- status eligibility logic;
- result data shaping.

These are domain/security concerns and should live in a dedicated Admission service rather than in Livewire.

Suggested direction:

```text
Search Livewire
    -> AdmissionLookupService
       -> validate/normalize lookup input
       -> rate-limit / lookup
       -> verify DOB credential
       -> enforce approved-result policy
       -> return safe result DTO/array
```

Livewire should own UI state/messages/modal visibility only.

No transaction is required because the flow is read-only unless attempt/audit persistence is later introduced.

## Performance

### Query pattern

One application query per login attempt.

`ma_dinh_danh` is indexed, so lookup should be efficient for normal table sizes.

No N+1 query pattern exists.

### Result payload

The current use of `toArray()` loads all model attributes even though the modal displays only:

- student name;
- date of birth;
- identity number;
- application code;
- class;
- registration class type;
- teacher;
- caregiver/class assistant.

Selecting/returning only required columns would reduce exposure and Livewire payload size.

### School settings

`SchoolSettingService::all()` is called on render. The service uses cache, so this is not a major performance issue, but it can be injected/resolved once into safer presentation state if desired.

## Livewire Blade Analysis

### Form

The Blade contains inputs for identity number and password and submits to `login()`.

Strengths:

- identity input restricts client input to numeric characters;
- password uses `type=password`;
- button has visible `wire:loading` text;
- modal is responsive and displays a limited subset of fields.

Issues:

- submit button is not disabled during loading, so repeated submits remain possible;
- no `wire:target="login"` scopes the loading state;
- validation is shown as a single manual message rather than field-level validation;
- close button has no explicit `type="button"` (it is outside the form in current markup, so this is low risk);
- image `alt` text is missing for modal logo/decorative images;
- inline `style` gradient remains despite Tailwind usage (P2 only).

## Parent Page Analysis

`pages/public/search.blade.php` now uses `SchoolSettingService` and the static Admission logo fallback after the recent runtime fix.

It still resolves a service directly inside page Blade:

```php
$schoolSettings = app(SchoolSettingService::class)->all();
```

Repository standard prefers Page Blade as a thin shell without service/model queries.

This is a P2 architecture cleanup candidate, but secondary to search security.

## Security / Privacy Summary

Sensitive data handled by this flow includes identity number and date of birth, and the underlying application contains extensive child/family PII.

Primary observed risks:

1. full application public state populated before password verification;
2. credentials accepted in URL path;
3. predictable DOB-derived password;
4. no observed rate limiting;
5. distinguishable failure modes / `firstOrFail()` enumeration behavior;
6. application state remains populated after modal close;
7. direct domain/security logic in Livewire.

No file/path traversal, uploads, arbitrary download, or write-side transaction issues exist in this component.

## Test Coverage

Current Admission tests include:

- `AdmissionApplicationsIndexRefactorTest.php`;
- `AdmissionLocationImportExportTest.php`;
- `AdmissionRegistrationFormRefactorTest.php`;
- `AdmissionRouteConfigurationTest.php`.

No focused Search component/runtime test was found.

Missing critical coverage:

- valid identifier + valid DOB password + approved application returns only safe fields;
- wrong password never exposes application data;
- unknown identifier returns controlled generic failure rather than 404;
- pending/rejected status does not expose result;
- password contract is exactly `DDMMYYYY` if current behavior is retained;
- route parameters do not cause property-name bug;
- rate limiting / repeated attempts;
- duplicate `ma_dinh_danh` policy;
- public state clears sensitive result on failure/close;
- no parent CCCD/health/address/document paths appear in Livewire result payload/state;
- loading/disabled UI behavior.

## Issue List

### P0 — Full application is assigned to public state before password verification

**Evidence:** `AdmissionApplication -> firstOrFail() -> toArray()` is assigned to `$this->app` before DOB password comparison.

**Impact:** potential disclosure of child/family PII on failed authentication through Livewire public state.

**Direction:** verify credentials server-side first, then return a strict safe result shape only.

### P0/P1 — Credentials can be carried in public URL path

**Evidence:** route includes optional `{ma_dinh_danh}` and `{password}` segments.

**Impact:** identifier/DOB secret can be persisted in history/logs/shared URLs.

**Direction:** normal form lookup should use Livewire POST/state without credentials in URL; route compatibility decision must be explicit.

### P0/P1 — No observed rate limiting on public lookup

**Impact:** brute-force/enumeration risk against predictable DOB-based credentials.

**Direction:** add rate limiting to the lookup boundary.

### P1 — `mount()` references wrong property name

`$this->ma_dinh_danh` vs declared `$this->MaDinhDanh`.

**Impact:** route-parameter auto lookup is unreliable/broken.

### P1 — Unknown application triggers `firstOrFail()` before friendly error

**Impact:** inconsistent UX and identifier enumeration signal.

### P1 — Password wording conflicts with implementation

Runtime expects 8-digit `DDMMYYYY`; messages mention six digits / `ddmmyy` in places.

### P1 — Identifier is indexed but not unique

`firstOrFail()` has ambiguous semantics if duplicate identity numbers exist.

Business rule must be confirmed before schema changes.

### P1 — Domain/security logic lives in Livewire

Credential verification, query, DOB derivation, status eligibility and result shaping should move into an Admission-owned lookup service.

### P2 — Public result remains in state after modal close

Clear safe result state on close/new failed attempt.

### P2 — Submit button lacks disabled/targeted loading state

Add `wire:loading.attr="disabled"` and `wire:target="login"`.

### P2 — Page Blade resolves SchoolSettingService directly

Move page presentation data to controller/view model/service handoff when touching the parent page, without expanding scope unnecessarily.

## Recommended Direction

Recommendation: **Focused Refactor**, not rebuild.

Suggested implementation order:

1. Fix P0 state/data exposure: never hydrate full application into public state before verification.
2. Extract `AdmissionLookupService` (or equivalent) for lookup, credential verification, eligibility and safe result shaping.
3. Add rate limiting and generic failure behavior.
4. Resolve URL credential contract. Removing password from the URL is strongly recommended; if route compatibility must be preserved, do not silently break it in a pure refactor.
5. Fix mount/property-name and password-message inconsistencies.
6. Define/confirm duplicate identity-number business rule before DB uniqueness changes.
7. Add focused Search tests.
8. Improve loading/disabled/error/accessibility UX.

## Refactor vs Feature/Security Change Boundary

The following are safe refactor/hardening work:

- move lookup logic into service;
- verify before exposing data;
- return only safe fields;
- generic controlled errors;
- clear state;
- fix property/message defects;
- add rate limiting if repository policy treats this as security hardening;
- add tests/loading states.

Potential product/security changes requiring explicit approval if they alter public behavior:

- replacing DOB-derived password with OTP/PIN/token;
- removing existing credential-bearing URL compatibility;
- changing whether pending/rejected applications can be searched;
- enforcing unique `ma_dinh_danh` in DB;
- adding admission-year/application-code discriminator.

## Open Questions / Unknowns

1. Is `/admission/search/{id}/{password}` actively shared externally (QR/link) or can credentials be removed from URLs without compatibility impact?
2. Is `DDMMYYYY` intentionally the permanent lookup password, or is a stronger PIN/token desired?
3. Can one `ma_dinh_danh` legitimately have multiple applications across years/campaigns?
4. Should rejected applications return the same generic message as unknown/wrong-password attempts?
5. Is lookup attempt auditing required for operational/security review?

Until these are explicitly decided, a refactor should preserve approved-result semantics while hardening data exposure and lookup behavior.
