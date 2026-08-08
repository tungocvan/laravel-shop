# Admission Module README

## 1. Module Overview

Admission is a Laravel 12 domain module for managing student admission applications. It provides registration forms, admin review, import/export, document generation, public lookup, location data, and admission catalogs.

## 2. Installation / Registration

The module is discovered by `Modules/ModuleServiceProvider.php` from `Modules/Admission`.

Current manifest:

- File: `Modules/Admission/config/module.php`
- Type: `domain`
- Enabled: `true`

The module is enabled through module auto-discovery. Admission no longer registers `/admin` as `admin.dashboard`; the module dashboard is `admin.admission.dashboard` at `/admin/admission/dashboard`.

## 3. Routes

Route files:

- `Modules/Admission/routes/web.php`
- `Modules/Admission/routes/api.php`

Main named routes:

- `admission.search`
- `admin.dashboard`
- `admin.admission.index`
- `admin.admission.create`
- `admin.admission.edit`
- `admin.admission.export-pdf`
- `admin.admission.export`
- `admin.admission.import`
- `admin.admission.dvhc`
- `admin.admission.list-class`
- `admission.register`
- `admission.download-pdf`
- `admission.download-word`
- `admission.download`
- `admission.receipt`

## 4. Permissions

Declared permissions:

- `view_admission`
- `create_admission`
- `edit_admission`
- `delete_admission`
- `import_admission`
- `export_admission`
- `approve_admission`
- `reject_admission`
- `download_admission_documents`
- `manage_admission_locations`

Admin routes enforce permission middleware. Livewire admin mutation methods also enforce server-side permission checks for approve, reject, delete, export, and location updates.

The project permission seeder reads Admission permissions from the lowercase `Modules/Admission/config/module.php` manifest.

## 5. Features

- Register an admission application.
- Edit application data.
- Approve or reject pending applications.
- Assign class, homeroom teacher, and nanny.
- Generate application DOCX/PDF files.
- Generate receipt DOCX with QR code.
- Search approved admission results.
- Import applications from Excel.
- Export applications to Excel.
- Maintain location data.
- Show dashboard statistics.

## 6. UI Pages

- Admin dashboard: `Modules/Admission/resources/views/pages/dashboard.blade.php`
- Admin list: `Modules/Admission/resources/views/pages/admin/index.blade.php`
- Admin create/edit host: `Modules/Admission/resources/views/pages/admin/create.blade.php`
- Location admin: `Modules/Admission/resources/views/pages/admin/dvhc.blade.php`
- Public registration host: `Modules/Admission/resources/views/pages/public/register.blade.php`
- Search host: `Modules/Admission/resources/views/pages/public/search.blade.php`

## 7. Livewire Components

- `admission.public.registration-form`
- `admission.admin.applications.index`
- `admission.admin.dashboard.stats-overview`
- `admission.search`
- `admission.dvhc`

## 8. Import

Current controller import path:

```text
AdmissionController@import
-> App\Services\Data\Import\GenericImport
-> App\Services\Data\DataTransformer
-> AdmissionApplication
```

Dedicated module import class also exists:

- `Modules/Admission/Imports/ApplicationsImport.php`

Needs confirmation before coding: choose the canonical import implementation.

## 9. Export

Current export:

- `Modules/Admission/Exports/ApplicationsExport.php`

It filters by search, status, and class, then exports dynamic database columns except excluded fields. Large exports should be refactored to query/chunk or queue mode.

## 10. Configuration

- `Modules/Admission/config/module.php`
- `Modules/Admission/config/form.php`

Important flags:

- `enabled`
- `enable_pdf_convert`

## 11. Events

No standalone event classes are defined. `AdmissionApplication` uses model lifecycle hooks to reset status, dispatch PDF generation, and delete files.

## 12. Jobs

- `Modules/Admission/Jobs/GenerateAdmissionPdfJob.php`

The job generates DOCX and optionally PDF after an application is approved.

## 13. Developer Notes

- Do not rely on UI hiding for security. Add server-side permission checks.
- Keep stored dates in database-safe format (`Y-m-d`) and format only for display/templates.
- Avoid storing lookup credentials in URLs.
- Verify `libreoffice` availability before enabling PDF conversion.
- Add schema support before writing approval metadata.
- Avoid `perPage = all` and collection-based large exports for production data.
- API `/api/admission` currently returns an intentional 501 JSON response until a real API contract is designed.

## 14. Future Improvements

- Add Admission policies and permission middleware.
- Add complete form/request validation.
- Convert import/export to shared chunked/queued foundation.
- Add JSON columns for multi-value health/skill fields or normalize them.
- Add tests for routes, Livewire actions, import/export, jobs, and authorization.
- Add dashboard/catalog/location caching with invalidation.
