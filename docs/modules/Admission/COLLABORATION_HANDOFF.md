# Admission — Collaboration Handoff

## Current objective

Admission public branding/logo alignment for the registration and search pages.

Branch: `fix/admission-public-branding-logo`

Status: implementation complete and manually accepted; ready for PR review.

## Completed baseline

The compact Admission major refactor and the subsequent admin-list UI/pagination refinement are already complete. Their architecture, persistence, authorization and pagination contracts remain unchanged by this follow-up.

## Public branding follow-up

The public routes `/admission/register` and `/admission/search` previously duplicated a hard-coded Admission logo path.

Implemented in this branch:

- added a shared Admission public branding-header partial used by both public pages;
- removed duplicated hard-coded logo markup from register and search;
- aligned both pages to the existing Website logo file contract at `storage/app/public/logo.png`;
- added a compatibility fallback to the existing Admission asset at `storage/app/public/admission/img/logo.png` when the Website logo file is absent;
- resolved the logo through the configured `public` filesystem disk before generating the `/storage/...` URL;
- preserved the registration form, search workflow, school-year heading and legacy-search warning behavior.

During manual verification, the initial assumption that `site_logo` was the canonical persisted setting was disproved by the runtime environment: `site_logo` was `null`, while the active Website logo existed at `storage/app/public/logo.png`. The branch was corrected to follow that existing file contract rather than introducing a new settings dependency.

## Safety

- No schema or migration changes.
- No route changes.
- No permission or authorization changes.
- No Admission registration/search business logic changes.
- No upload/storage migration or destructive file operation.
- Existing Admission compatibility/quarantined debt remains unchanged.

## Verification

Manual UI acceptance:

- user reported **UI PASS** on 2026-09-03 for both `/admission/register` and `/admission/search` after the corrected Website-logo resolution was applied.

This is a focused Blade/public-branding fix. No application-wide regression was requested or required for this follow-up, and no automated-test/Pint result is claimed here.

## Merge gate

Ready for PR review:

- both public Admission pages share one branding header;
- Website logo contract verified against the runtime filesystem;
- fallback uses the existing Admission logo asset;
- UI PASS;
- no schema, route, authz or business-workflow changes.

After merge, synchronize `main`. The API placeholder, shared `job_batches` migration ownership, legacy credential lookup compatibility route and DVHC ownership questions remain separately documented Admission debt and are outside this branding fix.
