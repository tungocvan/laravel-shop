# Admission Livewire Refactor Plan — Public/RegistrationForm

## Scope

Target component:

`Modules/Admission/Livewire/Public/RegistrationForm.php`

Livewire alias:

`admission.public.registration-form`

Primary view:

`Modules/Admission/resources/views/livewire/admission/registration-form.blade.php`

Direct view partials:

- `partials/stepper.blade.php`
- `partials/step-1-student.blade.php`
- `partials/step-2-address.blade.php`
- `partials/step-3-extra.blade.php`
- `partials/step-4-parent.blade.php`
- `partials/step-5-confirm.blade.php`
- `partials/actions.blade.php`
- `partials/error-summary.blade.php`

Direct service/model dependencies:

- `Modules/Admission/Services/AdmissionService.php`
- `Modules/Admission/Services/SchoolSettingService.php`
- `Modules/Admission/Models/AdmissionApplication.php`
- `Modules/Admission/Models/AdmissionLocation.php`
- `Modules/Admission/Models/AdmissionCatalog.php`

This is a focused refactor. It does not change the Admission product model, route contract, Livewire alias, five-step user flow, public/admin naming, or introduce a true unauthenticated public registration route.

## Refactor Goal

Make RegistrationForm production-safe and repository-compliant while preserving current user-visible behavior as far as possible.

Primary goals:

1. Enforce create/edit authorization at the Livewire boundary.
2. Remove implicit approval/status mutation from edit save.
3. Make DB -> Form -> DB mapping lossless for existing fields.
4. Establish canonical create status server-side.
5. Expand validation safely, including step-aware validation.
6. Reduce direct database/query responsibilities in Livewire.
7. Add visible failure/loading behavior and prevent repeated save submission.
8. Add focused regression tests for security and data integrity.

## Public Contract to Preserve

Unless an implementation issue makes preservation impossible, retain:

- route names and URLs;
- `admission.public.registration-form` alias;
- component mount parameter `id` for admin edit mode;
- five-step form structure;
- PascalCase form keys used by existing Blade/service contracts;
- registration class configuration from `SchoolSettingService`;
- address copy behavior;
- success event `show-success-modal`;
- success redirect behavior:
  - create -> `admission.register`;
  - edit -> `admin.admission.index`;
- existing `AdmissionApplication` model hooks and PDF-generation behavior;
- existing database schema and migration history.

Explicitly NOT changed in this refactor:

- `/admission/register` remains admin-authenticated under the current route contract;
- no new public registration endpoint;
- no new rejection reason workflow;
- no new approval UI in RegistrationForm;
- no new statuses;
- no uniqueness constraint on `ma_dinh_danh` without a separate business-rule decision;
- no module-wide UI redesign.

## P0 — Authorization and Workflow Integrity

### 1. Authorize create/edit at Livewire boundary

`save()` must explicitly use the `admin` guard and enforce:

- create mode -> `create_admission`;
- edit mode -> `edit_admission`.

Edit initialization with an application ID must also enforce `edit_admission` before loading sensitive application data.

Implementation direction:

- add small internal authorization helpers similar to the already-refactored `Admin/Applications/Index` pattern;
- use `Auth::guard('admin')->user()` explicitly;
- return 403 for unauthorized component calls.

### 2. Remove implicit approval from edit save

Current behavior:

`Lop + Gvcn present -> Status = approved`

must be removed.

RegistrationForm edit will update profile/application/class-assignment fields but must not transition `status` to `approved` or `rejected`.

Approval/rejection remains owned by the explicit review workflow (`Admin/Applications/Index` + `AdmissionApplicationAdminService`) where `approve_admission` / `reject_admission` are enforced.

Expected behavior:

- editing a pending application keeps it pending;
- editing an approved/rejected application follows the existing model lifecycle semantics unless the service intentionally prevents/reset behavior according to existing model hooks;
- RegistrationForm never grants approval capability implicitly.

If current model hooks force an approved/rejected record back to pending after ordinary edit, preserve that existing model contract unless a separate approved change plan explicitly changes it.

## P1 — Data Mapping Integrity

### 3. Centralize DB -> Form mapping

Move the large edit mapping out of `mount()` into an owning module service/helper method, preferably `AdmissionService`, because it already owns form-data normalization and application persistence.

Suggested API:

```php
public function toRegistrationForm(AdmissionApplication $application): array
```

or equivalent repository-consistent naming.

The mapping must preserve all currently supported form fields and correct known defects:

- `ChucVuCha` <- `chuc_vu_cha`;
- `ChucVuMe` <- `chuc_vu_me`;
- `QuanHeGiamHo` <- `quan_he_giam_ho`;
- `NgayLamDon` <- stored application date;
- all five commitment booleans retain true AND false correctly;
- `NoiSinhChiTiet` <- `noi_sinh_chi_tiet`;
- existing registration class value is retained even if configuration changed.

The refactor should remove obsolete duplicate/commented form-mapping blocks from the component where safe.

### 4. Correct Form -> DB mapping

In `AdmissionService::prepareData()`:

- `noi_sinh_chi_tiet` must use `NoiSinhChiTiet` rather than silently reusing `NoiSinh`;
- use safe null/default access for optional keys;
- preserve current array casts for `KhaNangHocSinh` and `SucKhoeCanLuuY`;
- avoid changing unrelated persisted fields during an edit when the corresponding form value was not intentionally changed.

### 5. Canonical initial status

`createRegistration()` must assign initial status server-side as:

`pending`

rather than trusting a client-supplied `Status` key or defaulting to an empty string.

The form must not be able to choose its own lifecycle status on create.

## P1 — Validation

### 6. Introduce step-aware validation

Preserve five steps but define validation rules by step.

Minimum safe validation plan:

#### Step 1

- student name required, reasonable length;
- identity number required, exactly 12 digits;
- date of birth valid date when present;
- gender limited to supported values when present;
- student phone format/length when present;
- configured/catalog-backed values validated where practical without over-constraining legacy data.

#### Step 2

- validate address text lengths;
- selected province/ward values must be strings and bounded;
- do not require fields unless current business behavior clearly requires them.

#### Step 3

- `ConThu`, `TSAnhChiEm` nullable integers with sensible non-negative bounds;
- health/ability arrays validated as arrays of strings;
- caregiver relationship text bounded.

#### Step 4

- parent/guardian names bounded;
- birth years numeric/plausible when supplied;
- phone fields validated conservatively;
- CCCD fields validated as numeric-length values when supplied, without adding new mandatory-field policy.

#### Step 5

- registration class required and must be one of configured classes plus the existing legacy value in edit mode;
- submitter name validated/bounded;
- commitment flags boolean;
- admin assignment fields bounded strings.

`nextStep()` should validate only the current step before advancing.

Final `save()` should validate the full form using the combined rules.

Do not invent new required business fields beyond current explicit requirements unless necessary for data type safety.

### 7. Clamp step state

`setStep($step)` must normalize/clamp to valid range `1..5` and should not allow navigation around required validation if direct step navigation remains clickable.

If the stepper currently permits arbitrary jumping, choose the least disruptive approach:

- either validate skipped/current step before forward jumps;
- or make future-step navigation non-actionable until reached.

Do not redesign the stepper visually.

## P1/P2 — Service / Query Boundary

### 8. Move location/catalog reads behind service methods

RegistrationForm currently directly queries:

- provinces;
- wards by province;
- ethnicity catalog;
- religion catalog;
- application record in edit mode.

Move these reads to Admission-owned services so Livewire focuses on state/UI orchestration.

Preferred approach:

- reuse `AdmissionService` for application/form mapping;
- introduce a small Admission form/options service only if keeping all catalog/location concerns in `AdmissionService` would make it materially less cohesive;
- do not create generic shared infrastructure for this component alone.

Candidate methods:

```text
provinces()
wardsForProvince(string $province)
ethnicities()
religions()
findForEdit(int $id)
```

Stable lists may use repository-consistent cache if already supported, but caching is optional and secondary to correctness.

### 9. Avoid unnecessary repeated ward queries

Keep dynamic ward loading behavior but centralize the lookup and clear dependent ward/state values appropriately when a province changes.

During edit mount, load only required ward lists for existing selected provinces.

## P2 — Blade / UX

### 10. Correct permission semantics in Step 5

Remove `@can('delete_admission')` from the class-assignment section.

Because RegistrationForm no longer owns approval, class-assignment visibility should align with edit capability/current route context, not delete capability.

Recommended behavior:

- admin edit mode + `edit_admission` can see/edit class assignment fields;
- create mode should not expose admin-only assignment fields unless current intended behavior explicitly requires it;
- no field visibility should imply approval authority.

Do not add approval/rejection buttons here.

### 11. Loading / repeated submit protection

Add:

- `wire:loading.attr="disabled"` for save/next actions where appropriate;
- `wire:target="save"` on final submit;
- visible submitting text/spinner using existing Tailwind patterns;
- prevent double-submit.

### 12. Visible save error

Current catch block logs errors without user feedback.

Refactor should:

- keep detailed technical errors in server logs;
- show a generic session/Livewire error message to the user;
- preserve the existing `error-summary` partial and validation error flow.

Do not expose exception messages or sensitive form data.

### 13. Validation UI

Add field-local validation messages for newly validated high-value fields where missing, prioritizing:

- student identity/name/date;
- phone/CCCD fields;
- registration class;
- submitter.

Avoid a full visual rewrite of every partial.

### 14. Parent page cleanup within direct scope

`pages/admin/create.blade.php` currently gates `+ Thêm đơn mới` with `view_admin`.

Within this refactor it may be changed to `create_admission` because it is the direct parent page and the capability is unambiguous.

Do NOT refactor `pages/public/register.blade.php` site-logo lookup in this component implementation unless required for tests/runtime, because that is a separate page architecture issue and not a RegistrationForm behavior dependency.

## Transaction / Concurrency

### 15. Create

Keep `createRegistration()` transactional.

Retain current registration-code generation behavior unless tests reveal a defect directly caused by this refactor.

### 16. Edit

Use an explicit service workflow for updating an existing application.

Recommended direction:

- service loads the current record server-side;
- apply mapped/validated form data;
- do not accept lifecycle status from Livewire state;
- use a transaction when updating data that must remain atomic;
- preserve model event behavior.

No new optimistic-version column or migration is planned in this component refactor.

## Security / Sensitive Data

- Never trust `applicationId`, `isEdit`, `form.Status`, `Lop`, `Gvcn`, or other public state as authorization evidence.
- Resolve the application ID server-side and authorize edit capability before reading/updating.
- Do not log full form payloads, CCCDs, health data, addresses, or identity numbers.
- Validation failure should not expose internal implementation details.
- Preserve admin authentication requirement from current routes.

## Test Plan

Create focused tests, likely under:

`tests/Feature/Admission/AdmissionRegistrationFormRefactorTest.php`

Required coverage:

### Authorization

- unauthenticated Livewire create call is denied;
- admin without `create_admission` cannot create;
- admin with `create_admission` can create;
- admin without `edit_admission` cannot initialize/update edit mode;
- admin with `edit_admission` can update;
- edit-only admin cannot cause approval through class/teacher state;
- RegistrationForm does not require/use `approve_admission` for ordinary edit because it no longer changes approval status.

### Lifecycle

- create stores `status = pending`;
- ordinary edit does not explicitly set `approved`;
- existing model auto-reset semantics are preserved and documented by test where applicable;
- RegistrationForm save does not trigger approval PDF generation unless status was legitimately changed by the canonical approval workflow outside this component.

### Mapping round-trip

- `ChucVuCha` preserved;
- `ChucVuMe` preserved;
- `QuanHeGiamHo` preserved;
- `NgayLamDon` preserved;
- commitment false values remain false;
- `NoiSinhChiTiet` preserved;
- configured/legacy class value preserved in edit form.

### Validation

- student name required/min length;
- identity exactly 12 digits;
- invalid date/phone/CCCD cases rejected according to approved rules;
- invalid `LoaiLopDangKy` rejected;
- next step blocks when current-step required fields are invalid;
- valid current step can advance;
- `setStep()` cannot escape 1..5.

### UI/state

- address copy behavior remains functional;
- success event still contains name and redirect URL;
- save failure produces user-visible generic error state;
- final save button has loading/disabled protection (Blade assertion where practical).

Run at minimum:

```bash
php artisan test tests/Feature/Admission/AdmissionRegistrationFormRefactorTest.php
php artisan test tests/Feature/Admission/AdmissionRouteConfigurationTest.php
php artisan test tests/Feature/Admission
```

## Files Expected to Change

Primary implementation scope:

- `Modules/Admission/Livewire/Public/RegistrationForm.php`
- `Modules/Admission/Services/AdmissionService.php`
- `Modules/Admission/resources/views/livewire/admission/registration-form.blade.php`
- `Modules/Admission/resources/views/livewire/admission/partials/actions.blade.php`
- selected step partials only where validation/permission/loading feedback requires changes
- `Modules/Admission/resources/views/pages/admin/create.blade.php`
- `tests/Feature/Admission/AdmissionRegistrationFormRefactorTest.php`

Optional direct-scope new file only if service cohesion requires it:

- `Modules/Admission/Services/AdmissionRegistrationOptionsService.php` or equivalent focused name.

No migration is planned.

No route file change is planned.

No change to `Admin/Applications/Index` is planned unless a regression test reveals a direct compatibility defect; such a discovery would require updating this plan before implementation if scope materially expands.

## Rollback / Recovery

The refactor should remain reversible because it changes PHP/Blade/service/tests only.

Rollback strategy:

1. revert RegistrationForm and AdmissionService changes;
2. revert Blade capability/loading/error-state changes;
3. remove/revert any focused options service added by this refactor;
4. revert the new focused tests;
5. no database rollback required because no schema migration is planned.

Before deployment, preserve existing route names, Livewire alias and form keys so rollback does not require data migration.

## Acceptance Criteria

Implementation is accepted only when all are true:

- create action explicitly enforces `create_admission` with the admin guard;
- edit initialization/save explicitly enforces `edit_admission`;
- RegistrationForm cannot approve/reject an application;
- create always receives canonical `pending` status server-side;
- DB->Form->DB round-trip no longer loses/corrupts the identified fields;
- `NoiSinhChiTiet` maps consistently;
- step-aware validation exists and final save validates the full form;
- public step/application state cannot bypass authorization;
- direct application/catalog/location query responsibility is reduced/centralized behind Admission services;
- current route and Livewire alias remain unchanged;
- five-step UX and success modal remain functional;
- save/next actions expose suitable loading/disabled behavior;
- server failures provide safe visible feedback;
- no sensitive payload is added to logs;
- focused RegistrationForm tests pass;
- Admission route tests pass;
- complete Admission feature test suite passes.

## Explicit Approval Gate

This file is the planning artifact only.

Do not implement this refactor until the user explicitly approves this plan.