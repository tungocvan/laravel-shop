# Human Resources standalone demo — work in progress

Branch: feat/hr-standalone-blade-20261028

Source: user-supplied Quản lý nhân sự.html. Imported original HTML DOM, embedded example dataset, CSS and JS to independent Blade route `/demo/quan-ly-nhan-su`. This is a migration foundation, NOT a finished/accepted demo.

## Outstanding blockers before checkpoint
- Replace original save-to-HTML behavior with safe localStorage and JSON import/export.
- Implement secured JSON dataset library with server-side schema validation and authorization.
- Implement four themes with independent persistence.
- Integrate shared sidebar menu builder via compatible adapter and protected API.
- Audit original JS event/DOM compatibility and security; add focused tests.
- Run focused tests, impacted regression and UI acceptance.

No tests or UI acceptance have been reported as PASS. No production operations, migrations or .env changes.
