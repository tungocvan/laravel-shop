# Admission Livewire Analysis — Public/RegistrationForm

## Executive Summary

Target component:

`Modules/Admission/Livewire/Public/RegistrationForm.php`

Livewire alias:

`admission.public.registration-form`

View:

`Modules/Admission/resources/views/livewire/admission/registration-form.blade.php`

Primary parent pages:

- `Modules/Admission/resources/views/pages/public/register.blade.php`
- `Modules/Admission/resources/views/pages/admin/create.blade.php`

Primary routes:

- `GET /admission/register` → `admission.register` → `create_admission`
- `GET /admin/admission/create` → `admin.admission.create` → `create_admission`
- `GET /admin/admission/edit/{id}` → `admin.admission.edit` → `edit_admission`

Despite the `Public` namespace and public-facing copy, the current registration route is protected by `auth:admin` and `create_admission`. The same Livewire component is reused for create and edit.

The component is functionally substantial and already delegates persistence to `AdmissionService`, but it has critical authorization/status-transition issues and several DB↔Form mapping defects that can silently mutate data during edit. A focused refactor is recommended before adding new admission behavior.

Application source code was not modified during this analysis.

## Component Purpose

The component owns the five-step admission form:

1. student identity and birthplace;
2. permanent/current address;
3. household, preschool, abilities and health notes;
4. parent/guardian information;
5. registration class, submitter and admin class-assignment fields.

It also supports admin edit mode when mounted with an application ID.

## Dependency Flow

```text
/admission/register
  -> AdmissionController::index()
  -> pages.public.register
  -> admission.public.registration-form

/admin/admission/create
  -> AdmissionController::adminCreate()
  -> pages.admin.create
  -> admission.public.registration-form

/admin/admission/edit/{id}
  -> AdmissionController::adminEdit($id)
  -> pages.admin.create(id)
  -> admission.public.registration-form(id)

RegistrationForm
  -> AdmissionService
  -> AdmissionApplication
  -> admission_applications

RegistrationForm
  -> AdmissionLocation / AdmissionCatalog
  -> SchoolSettingService
```

## Livewire PHP Analysis

### Public state

The component exposes a large client-synchronized state surface:

- `currentStep`, `totalSteps`
- province/ward/catalog collections
- `registrationClasses`
- address-copy toggles
- `applicationId`, `isEdit`
- the complete `form` array, including student identity, parent CCCDs/phones, admin class assignment and an edit-mode `Status` key.

The public form state is expected for input fields, but status-related/admin-only values must never be trusted as an authorization boundary.

### Lifecycle

`mount($id = null)`:

- loads all distinct provinces;
- loads ethnicity and religion catalogs;
- loads registration class configuration through `SchoolSettingService`;
- sets application date;
- when `$id` is supplied, loads `AdmissionApplication::findOrFail($id)` directly and maps DB fields into the form;
- then manually invokes `updated()` five times to populate ward collections.

There is no component-level authorization in `mount()` for loading an application in edit mode.

### Dynamic location queries

`updated($field)` runs a separate `AdmissionLocation` query whenever one of five province fields changes. This is understandable UI behavior but places query responsibilities directly in Livewire and duplicates the same query pattern.

### Step navigation

`setStep($step)` directly assigns the supplied value and does not clamp it to `1..5`.

`nextStep()` and `prevStep()` enforce bounds, but neither performs step-specific validation.

### Validation

Current rules validate only:

```text
form.HoVaTenHocSinh -> required|min:5
form.MaDinhDanh     -> required|digits:12
form.LoaiLopDangKy  -> required|string|max:255
```

The component collects many other high-value fields without validation, including date of birth, phones, parent identity numbers, address selections, guardian data, numeric sibling/order fields, commitment flags and admin assignment fields.

`nextStep()` performs no `validateOnly()` / step validation, so users can move through all steps with invalid or incomplete data and only encounter the three global rules on final submit.

### Save action

`save(AdmissionService $service)` validates the three rules, normalizes two array fields, converts the `other` caregiver choice, then branches into edit/create.

Create calls `AdmissionService::createRegistration()`.

Edit sets status itself:

```text
Lop != '' AND Gvcn != '' -> approved
otherwise                 -> pending
```

then calls `AdmissionService::updateRegistration()`.

The method catches every `Exception`, logs only the exception message, and does not provide a user-visible error state in the active code path.

## Authorization

### Route boundaries

The repository currently protects:

- create routes with `create_admission`;
- edit route with `edit_admission`.

This is necessary but not sufficient for Livewire mutation actions under the repository standard.

### P0 — save() has no action-boundary authorization

There is no explicit check inside `save()` for:

- `create_admission` when creating;
- `edit_admission` when editing.

There is also no authorization in `mount($id)` before loading a record for edit.

Sensitive create/update operations should enforce the correct admin capability inside the Livewire action/context rather than relying solely on the page route that initially rendered the component.

### P0 — edit can transition application status without approve permission

In edit mode, status is derived from public form fields `Lop` and `Gvcn` and written through `AdmissionService::updateRegistration()`.

This means a user with `edit_admission` can cause an application to become `approved` without the component checking `approve_admission`. Because the form state is client-controlled, hiding class-assignment inputs in Blade is not a security boundary.

This also duplicates the approval workflow now owned by the refactored admin application review service/component.

Recommended direction: editing profile/application data must not implicitly approve/reject. Status transitions should stay in the explicit approve/reject workflow with capability-specific authorization.

## DB → Form Mapping Integrity

### P1 — parent position keys are inconsistent

The initial form and service use:

```text
ChucVuCha
ChucVuMe
```

but edit-mode mapping writes:

```text
ChuvuCha
ChuvuMe
```

Therefore unchanged existing `chuc_vu_cha` / `chuc_vu_me` values are not reliably round-tripped and may be replaced by null/empty values on save.

### P1 — guardian relationship is omitted in edit mapping

The initial form defines `QuanHeGiamHo` and `AdmissionService` persists it, but edit mapping does not restore `quan_he_giam_ho` into `form.QuanHeGiamHo`.

Saving an edited record can therefore clear this field without the user intentionally changing it.

### P1 — application date is omitted in edit mapping

Edit mapping does not restore `NgayLamDon`. `AdmissionService::prepareData()` substitutes today's date when this key is empty/missing.

Therefore editing an existing application can overwrite its original application date with the edit date.

### P1 — false commitment values are coerced to true on edit

Edit mapping uses patterns such as:

```php
$app->ck_goc_hoc_tap ? (bool) $app->ck_goc_hoc_tap : true
```

A stored `false` therefore becomes `true` in the form. The same pattern is used for all five commitment fields. Saving after edit can silently change false commitments to true.

### P1 — birthplace detail persistence mapping is inconsistent

The form has both `NoiSinh` and `NoiSinhChiTiet`, and edit mode loads `noi_sinh_chi_tiet` into `NoiSinhChiTiet`.

However `AdmissionService::prepareData()` persists:

```text
noi_sinh_chi_tiet <- form.NoiSinh
```

rather than `form.NoiSinhChiTiet`.

This means the dedicated detail key is not round-tripped according to its declared contract.

### P1 — create status defaults to empty string

`AdmissionService::createRegistration()` assigns:

```text
status = formData['Status'] ?? ''
```

Create-mode form does not define `Status`, so newly created applications receive an empty string rather than the semantically expected `pending` value used by the admin workflow.

The database column is nullable and has no default enforcing a lifecycle state.

This is a domain-state consistency issue and should be resolved in the service, not by trusting Livewire input.

## Service / Model Boundary

### Strengths

Persistence is already delegated to `AdmissionService` rather than directly inserting/updating from `save()`.

`createRegistration()` uses a database transaction.

### Issues

The Livewire component still owns domain decisions that belong in service/workflow code:

- implicit approval based on class assignment;
- data-shape normalization before save;
- DB→Form mapping of the full application;
- direct `AdmissionApplication` read in edit mode;
- repeated location queries.

`AdmissionService::updateRegistration()` performs a direct find/update without an explicit transaction or concurrency strategy. The `AdmissionApplication` model also has an updating hook that resets approved/rejected records to `pending` when non-status data changes, which further couples edit behavior to status lifecycle.

Any refactor must account for these model side effects and avoid accidentally dispatching approval PDF generation from ordinary edits.

## Identifier / Duplicate Data Integrity

`MaDinhDanh` is validated as 12 digits but there is no uniqueness rule in the component. The base migration defines the column as nullable string; a later migration adds a non-unique index.

Therefore duplicate student identity numbers are not prevented by this component/database contract.

Whether duplicates are ever valid is a business-rule question; it should be confirmed before adding a unique constraint. At minimum, the current behavior should be tested/documented.

## Performance

### Mount queries

Create mode performs queries for:

- distinct provinces;
- ethnicities;
- religions;
- registration classes.

Edit mode additionally loads one application and then runs up to five ward-list queries.

These are bounded by catalog/location table sizes but repeat on each component mount. Stable catalogs and province lists are candidates for service/cache reuse if profiling shows value.

### Dynamic ward collections

Each province change uses `get()->toArray()` for all wards in that province. This is bounded by administrative geography and does not look like a production-scale unbounded application query, but the repeated query logic should be centralized for maintainability.

### N+1

No relationship-driven N+1 issue was observed in this component.

## Livewire Blade / UI Analysis

### Structure

The view is split into dedicated step partials plus shared actions/error summary/stepper. This is a good maintainability direction and should be preserved.

### Navigation and validation UX

The Next button calls `nextStep()` with no loading/disabled state and no validation gate. The final submit button also lacks `wire:loading`/disabled behavior, so repeated submits are possible.

The root view contains a success modal, but active `save()` error handling only logs the error; it does not flash an error or dispatch a failure event. Users can receive no visible feedback after a server failure.

### Admin-only class assignment gate is incorrect

Step 5 wraps class assignment in `@can('delete_admission')`, although assigning class/editing an application is unrelated to deletion capability.

It also only shows the class-assignment fields when current `Status === approved`, while `save()` uses the presence of class/teacher to set the record to approved. This creates a circular/inconsistent UI/workflow contract.

The UI gate does not protect status mutation because public Livewire form state can be modified independently of visible Blade fields.

### Parent page issues

The `pages.admin.create` page uses `@can('view_admin')` around a `+ Thêm đơn mới` link instead of `create_admission`.

The `pages.public.register` page executes `Modules\Admin\Models\Setting::getValue('site_logo')` directly in Blade. This is a cross-module query/business lookup in the page view and conflicts with the preferred thin-page-Blade pattern, though it is secondary to the RegistrationForm refactor.

### Namespace/runtime ambiguity

The component and page are named `Public`, and UI copy calls it an online parent registration portal, but `/admission/register` is currently protected by `auth:admin` and `create_admission` and the page extends `Admin::layouts.master`.

Evidence supports the current runtime as admin-authenticated only. Whether true public parent registration is intended is Unknown and should not be changed as part of a refactor without explicit feature approval.

## Security / Sensitive Data

The form contains sensitive student/family data including:

- student identity number;
- date of birth;
- phones;
- parent/guardian identity numbers;
- address details;
- health notes.

The main security concern in this component is authorization/status integrity rather than upload/path traversal; it does not perform file uploads or arbitrary path operations.

Exception messages are logged. Current save logging records only the exception text, not the full form payload, which avoids directly dumping all sensitive form data into logs.

## Events

On successful create/edit, the component dispatches `show-success-modal` with student name and redirect URL.

This public event contract should be preserved during refactor unless the UI is intentionally redesigned.

## Test Coverage

Current Admission feature tests include:

- `AdmissionApplicationsIndexRefactorTest.php`
- `AdmissionLocationImportExportTest.php`
- `AdmissionRouteConfigurationTest.php`

No focused runtime test for `Public/RegistrationForm` was found.

Critical missing coverage:

- create requires `create_admission` at the Livewire action boundary;
- edit/load requires `edit_admission`;
- edit user cannot approve without `approve_admission`;
- ordinary edit does not change status unexpectedly;
- create starts in the canonical pending status;
- DB→Form→DB round-trip preserves `ChucVuCha`, `ChucVuMe`, `QuanHeGiamHo`, `NgayLamDon`;
- false commitment flags remain false after edit/save;
- birthplace detail round-trip is correct;
- registration-class validation accepts only configured/allowed classes as intended;
- 12-digit identity validation;
- duplicate identity behavior;
- step validation behavior;
- address-copy behavior;
- success and failure UX;
- repeated submit protection/idempotency behavior.

## Issue List

### P0 — create/edit mutations lack Livewire action authorization

**Evidence:** `save()` performs create/update without checking `create_admission` or `edit_admission`; `mount($id)` loads an editable record without a component-level edit check.

**Impact:** capability enforcement depends on the initial route rather than the mutation/read boundary.

**Direction:** enforce explicit admin-guard authorization in component action/edit initialization.

### P0 — edit path can approve application without `approve_admission`

**Evidence:** edit `save()` sets `Status=approved` whenever `Lop` and `Gvcn` are non-empty; those fields are client-controlled state; no approval permission is checked.

**Impact:** edit capability can cross into approval workflow and trigger approval side effects/PDF generation.

**Direction:** remove implicit status transition from RegistrationForm; keep approval in explicit review service/action.

### P1 — edit round-trip can silently corrupt fields

Includes wrong parent-position keys, omitted guardian relationship, omitted application date, and false→true commitment coercion.

**Direction:** centralize explicit DB↔form mapping and add round-trip regression tests.

### P1 — create lifecycle status is empty rather than canonical pending

**Evidence:** create service uses `Status ?? ''`; create form has no Status key.

**Direction:** owning service should assign canonical initial state server-side.

### P1 — birthplace detail mapping is inconsistent

**Direction:** define one canonical form key/database mapping and test it.

### P1 — validation is materially incomplete

Only three fields are validated across a large five-step form with identity, contact and enrollment data.

**Direction:** implement step-aware validation and server-side final validation based on explicit business rules.

### P1 — no focused component tests

High-risk create/edit/status/data-round-trip behavior currently lacks regression coverage.

### P2 — repeated catalog/location queries live in component

Centralize behind module service/cache where useful; do not over-engineer before measuring.

### P2 — submit/navigation UX lacks loading/error handling

Add disabled/loading state and visible save failure feedback.

### P2 — permission gate for class assignment uses `delete_admission`

Capability is semantically incorrect and should be aligned with the approved workflow after status/class-assignment ownership is decided.

### P2 — Public naming/runtime contract is ambiguous

Current source says parent/public portal but actual route is admin-authenticated. Treat as an open product contract, not a silent refactor target.

## Recommended Direction

Recommendation: **Focused Refactor**, not rebuild.

Suggested order:

1. P0 authorization and removal of implicit approval from edit save.
2. Fix DB↔Form round-trip/data-integrity defects.
3. Establish canonical initial status in service.
4. Add focused Livewire/service regression tests.
5. Add step-aware validation based on confirmed business requirements.
6. Improve submit/error/loading UX.
7. Optionally centralize/cache stable catalog/location reads after core correctness is secured.

Preserve:

- Livewire alias;
- five-step UI structure;
- PascalCase form contract unless a separately approved compatibility plan changes it;
- route names/URIs;
- SchoolSetting-based registration class source;
- `show-success-modal` event behavior;
- create/edit page integration.

Do not silently make the registration portal public as part of this refactor; that is a feature/security-contract change requiring explicit approval.

## Open Questions / Unknowns

1. Should parents eventually access `/admission/register` without an admin account, or is the `Public` naming legacy only?
2. Is `ma_dinh_danh` required to be unique across all applications, or can a student submit more than once?
3. Which fields are truly required at each of the five steps? Current source only proves three required rules.
4. Is class assignment a separate workflow after approval, or is assignment intended to cause approval? Current implementation is internally contradictory.
5. Should editing an approved/rejected application automatically return it to pending (model behavior), or only for specific material fields?
6. Are the five commitment fields required to be true for submission, or informational choices? Current defaults are true but server validation does not enforce them.
