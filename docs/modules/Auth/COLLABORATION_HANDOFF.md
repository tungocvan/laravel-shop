# Auth Collaboration Handoff

## Current objective

Follow-up feature after the merged Auth architecture/security refactor: **Auth Login Theme & Branding Manager V1**.

Current branch:

`feat/auth-login-theme-branding-manager`

The previous Auth architecture/security MR was merged after `188 passed (1075 assertions)`, route verification, Vite build PASS and UI PASS.

## Approved scope

The approved follow-up keeps authentication security behavior unchanged while adding configurable presentation for login surfaces.

V1 scope:

- System settings manages login appearance configuration;
- Auth owns presentation semantics and rendering through a canonical presentation service;
- four presets: `classic-card`, `split-brand`, `hero-overlay`, `minimal`;
- independent Admin and Client/PWA settings;
- configurable login logo, background image, title lines, description, primary color, overlay opacity, Google-button visibility and footer text;
- live preview in System settings;
- presentation engine designed for `/admin/login` and reusable by `/login`;
- normalize the legacy logo contract so the Auth view consumes a resolved URL instead of treating it as a storage-relative path;
- no changes to guards, credentials, authorization, OAuth identity resolution, callback policy or session security.

## Ownership boundary

Auth owns:

- `LoginPresentationService`;
- supported login theme vocabulary;
- presentation normalization/defaults;
- login rendering contract.

System owns:

- the administration UI for configuration;
- generic settings persistence and permission enforcement;
- managed upload lifecycle for login branding assets.

The System settings UI consumes Auth's presentation contract but does not own authentication behavior.

## Implementation status

Implemented on the branch:

- `Modules/Auth/Services/LoginPresentationService.php` as canonical presentation normalization boundary;
- independent Admin and Client/PWA presentation keys;
- System `Giao diện đăng nhập` settings tab;
- four theme choices with live preview;
- editable title lines, description, primary color, overlay opacity, footer and Google-button visibility;
- dedicated login logo/background uploads with replacement cleanup limited to `login-branding/` managed paths;
- default Auth login view rendered from presentation config;
- guard-aware Google route selection in the shared default login view;
- existing Admission/site branding retained as fallback when dedicated login branding has not been configured;
- focused presentation regression test added at `tests/Feature/Auth/LoginThemePresentationTest.php`;
- `docs/modules/Auth/MODULE.md` updated with presentation ownership and security invariants.

## Safety notes

- Theme settings are presentation-only and must not mutate auth policy.
- Uploaded replacement assets are written first; old managed assets are removed only after settings persistence succeeds.
- On persistence failure, newly uploaded files are removed.
- Asset deletion is restricted to paths under `login-branding/`; fallback/global site assets are never deleted by the login-theme manager.
- Invalid theme/color/opacity values are normalized to safe presentation defaults at read time.

## Validation checkpoint

User-executed checkpoint results:

- Auth/System impacted regression: `188 passed (1075 assertions)` in `12.74s`;
- Vite production build: PASS, `34 modules transformed`, completed in `3.99s`;
- initial Pint checkpoint found two formatting-only issues in `LoginForm.php` and `LoginTheme.php`;
- those Pint issues were corrected on the branch with formatting-only commits;
- UI smoke for Login Theme & Branding Manager V1: **PASS**.

The user confirmed the configurable login UI works after the implementation and formatting correction.

## Deferred / unchanged boundaries

From the earlier Auth refactor, the following remain unchanged:

- `GoogleWebAuthService`: deprecated compatibility adapter / `QUARANTINE`;
- API Auth stub: `QUARANTINE`;
- generic Auth CRUD permissions: `DEFER/REVIEW`;
- cache/jobs/session infrastructure migrations: `QUARANTINE` pending persistence ownership proof.

## Current status

- Previous Auth security refactor: MERGED.
- Follow-up branch creation: COMPLETE.
- Presentation contract: COMPLETE.
- System settings manager: COMPLETE.
- Four-theme live preview: COMPLETE.
- Auth login rendering integration: COMPLETE.
- Focused regression coverage: PASS (`188 passed`, `1075 assertions`).
- Vite production build: PASS.
- Pint formatting issues: FIXED; final re-run should be confirmed before PR if desired.
- UI smoke checkpoint: PASS.
- Follow-up MR readiness: READY FOR FINAL PR REVIEW after final Pint confirmation.
