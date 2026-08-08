# Admission Module Information

## 1. Purpose

The Admission module manages student admission applications, admin review, document generation, public lookup, class assignment, location data, and admission catalogs.

## 2. Features

- Multi-step admission registration form.
- Admin application list with search, filters, pagination, selection, approve, reject, delete, import, and export.
- Dashboard statistics.
- DOCX/PDF generation for applications.
- Receipt generation with QR code.
- Public search for approved application status.
- Location administration for provinces and wards.
- Catalog data for form options.

## 3. Routes

- `Modules/Admission/routes/web.php`
- `Modules/Admission/routes/api.php`

Important route groups:

- Public-like search: `/admission/search/{ma_dinh_danh?}/{password?}`
- Admin dashboard: `/admin`
- Admin admission: `/admin/admission/*`
- Authenticated admission document/register routes: `/admission/*`
- API: `/api/admission`

## 4. Permissions

Declared in `Modules/Admission/config/module.php`:

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

Refactor update 2026-06-23: Admission admin routes now enforce permission middleware, and admin Livewire mutation methods for approve, reject, delete, export, and DVHC updates enforce server-side permission checks.

## 5. Controllers

- `Modules/Admission/Http/Controllers/AdmissionController.php`
  - Returns Admission pages.
  - Handles import, download, DOCX/PDF, and receipt actions.
- `Modules/Admission/Http/Controllers/Api/AdmissionController.php`
  - Stub controller. `index()` now returns an intentional 501 JSON response until a real API contract is designed.

## 6. Livewire Components

- `Modules/Admission/Livewire/Public/RegistrationForm.php`
  - Multi-step create/edit form.
- `Modules/Admission/Livewire/Admin/Applications/Index.php`
  - Admin listing and actions.
- `Modules/Admission/Livewire/Admin/Dashboard/StatsOverview.php`
  - Dashboard counts.
- `Modules/Admission/Livewire/Search.php`
  - Application lookup.
- `Modules/Admission/Livewire/Dvhc.php`
  - Location maintenance.

## 7. Blade Views

Page views:

- `Modules/Admission/resources/views/pages/dashboard.blade.php`
- `Modules/Admission/resources/views/pages/admin/index.blade.php`
- `Modules/Admission/resources/views/pages/admin/create.blade.php`
- `Modules/Admission/resources/views/pages/admin/edit.blade.php`
- `Modules/Admission/resources/views/pages/admin/dvhc.blade.php`
- `Modules/Admission/resources/views/pages/public/register.blade.php`
- `Modules/Admission/resources/views/pages/public/search.blade.php`
- `Modules/Admission/resources/views/pages/public/list-class.blade.php`

Livewire views:

- `Modules/Admission/resources/views/livewire/admission/registration-form.blade.php`
- `Modules/Admission/resources/views/livewire/admission/partials/*.blade.php`
- `Modules/Admission/resources/views/livewire/admin/applications/index.blade.php`
- `Modules/Admission/resources/views/livewire/admin/dashboard/stats-overview.blade.php`
- `Modules/Admission/resources/views/livewire/search.blade.php`
- `Modules/Admission/resources/views/livewire/dvhc.blade.php`

PDF/template view:

- `Modules/Admission/resources/views/pdf/registration.blade.php`

## 8. Services

- `Modules/Admission/Services/AdmissionService.php`
  - Creates and updates applications.
  - Normalizes form data.
  - Builds document template data.
  - Generates receipt DOCX.
  - Provides list/delete helpers.

Shared services:

- `App/Services/Data/Import/GenericImport.php`
- `App/Services/Data/DataTransformer.php`
- `App/Services/DocumentConverterService.php`

## 9. Import Classes

- `Modules/Admission/Imports/ApplicationsImport.php`
- `App/Services/Data/Import/GenericImport.php` is used by `AdmissionController@import`.

## 10. Export Classes

- `Modules/Admission/Exports/ApplicationsExport.php`

## 11. Models

- `Modules/Admission/Models/AdmissionApplication.php`
- `Modules/Admission/Models/AdmissionCatalog.php`
- `Modules/Admission/Models/AdmissionLocation.php`
- `Modules/Admission/Models/Admission.php` placeholder/unused candidate.

## 12. Database Tables

- `admission_applications`
- `admission_catalogs`
- `admission_locations`

## 13. Relationships

No Eloquent relationships were found in the Admission models.

## 14. Shared Dependencies

- Laravel admin auth guard.
- Spatie permissions package, declared but not enforced in this module.
- Maatwebsite Excel.
- PhpOffice PhpWord.
- Endroid QR Code.
- Symfony Process through document conversion.
- Shared Blade component `x-select-search`.
- Shared app services under `app/Services/Data` and `app/Services/DocumentConverterService.php`.

## 15. Events

No explicit Laravel event classes were found.

Model lifecycle events in `AdmissionApplication`:

- `updating`: resets approved/rejected records to pending when data changes without status change.
- `updated`: dispatches `GenerateAdmissionPdfJob` after commit when status becomes approved.
- `deleting`: deletes stored PDF/Word files.

## 16. Jobs and Queues

- `Modules/Admission/Jobs/GenerateAdmissionPdfJob.php`
  - Queue job with timeout 120 seconds and 3 tries.
  - Generates DOCX and optionally PDF.
  - Uses `DocumentConverterService`.

## 17. Configuration

- `Modules/Admission/config/module.php`
  - `enabled => true`
  - `enable_pdf_convert => false`
  - Lists permissions and tables.
- `Modules/Admission/config/form.php`
  - Partial form-step configuration.

## 18. Environment Variables

No Admission-specific environment variables were found.

Operational dependency:

- `libreoffice` executable is required when PDF conversion is enabled or synchronous conversion is used.

## 19. Known Limitations

- Import/export are not chunked or queued.
- Registration validation is incomplete.
- Public search uses URL parameters for sensitive lookup credentials.
- Multi-value fields still need a dedicated text-vs-JSON persistence decision before production data migration.

Refactor update 2026-06-23:

- Module manifest is enabled.
- API route returns an intentional 501 JSON response instead of targeting a missing method.
- Approval metadata migration was added.
- Permission seeder now supports lowercase `config/module.php`, so Admission permissions are discoverable.
- Focused Admission route configuration tests were added.

## 20. Maintenance Notes

- Confirm whether public registration should require `auth:admin`.
- Confirm whether `ApplicationsImport` or `GenericImport` is the canonical import path.
- Confirm whether approval metadata should be stored on the application table or an audit table.
- Confirm whether array-like fields should become JSON columns.
- Keep document template paths under `storage/app/templates` available to web and queue workers.
