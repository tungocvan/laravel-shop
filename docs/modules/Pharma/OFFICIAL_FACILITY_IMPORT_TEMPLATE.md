# Official Facility Import Excel Template

The admin workspace exposes `official-facility-import-template.xlsx` for test and operator onboarding.

Columns in the `Official Facilities` sheet:

- `external_id`
- `facility_name`
- `tax_code`
- `address`
- `phone`
- `email`

Canonical province, source province code and source date are supplied by the upload form rather than repeated per row.

The workbook contains a `README` sheet and only `TEST-*` example rows. Those rows are synthetic and must not be treated as official healthcare facilities.
