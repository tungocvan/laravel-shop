# Official Facility Import — Acceptance Checklist

- [ ] Upload XLSX/CSV creates staging only.
- [ ] Entire file is classified before Partner import.
- [ ] `NEW`, `EXACT`, `LIKELY_MATCH`, `CONFLICT`, `INVALID` behavior verified.
- [ ] Unresolved likely/conflict cannot import.
- [ ] Checkbox selection is page-scoped and persisted across pages.
- [ ] No selection means no Partner write.
- [ ] Re-import same source identity does not duplicate Partner.
- [ ] Manual Partner name/phone/email/contact person remain unchanged.
- [ ] Canonical `province_code` is separate from source province code.
- [ ] Company/supplier Partner records may keep `province_code = NULL`.
- [ ] Dashboard entry and permissions verified.
- [ ] Focused tests PASS.
- [ ] Pharma + Partner impacted regression PASS.
- [ ] Focused Pint PASS.
- [ ] Routes PASS.
- [ ] Build PASS.
- [ ] Manual UI PASS.
