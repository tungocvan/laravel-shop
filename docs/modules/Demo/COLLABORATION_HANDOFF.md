# Demo — Collaboration Handoff

Branch: `refactor/demo-module-rental-room-20261029`.

Scope: Phase 0 bootstrap and Phase 01 Rental Room only. Shared sidebar and other demos stay in place.

Checkpoint: focused tests, impacted regression and UI review are pending. Do not merge or deploy.

## Asset Route batch (2026-10-29)

- Rental Room CSS/JS canonical source: `Modules/Demo/resources/assets/projects/rental-room/`.
- New allowlisted GET `/demo-assets/quan-ly-phong-tro/{asset}` preserves existing URLs and sends CSS/JS MIME, nosniff, short cache headers.
- Remove only 3 tracked Rental Room files from `public/demo-assets/`. Other 11 demos remain untouched.
- Existing shared sidebar endpoints remain outside module. No database, localStorage or JSON snapshot migration.
- Prior test/UI PASS predates this batch: rerun focused tests, shared menu regression and rendered UI smoke. Never claim PASS before new results.
- Deployment caveat: verify web-server fallback routes missing static files to Laravel; stale public files on servers can bypass PHP route. No production changes authorized.
