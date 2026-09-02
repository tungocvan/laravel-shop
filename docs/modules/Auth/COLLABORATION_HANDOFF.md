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

## Validation plan

Final user checkpoint should remain consolidated into one pull/test cycle.

Recommended focused validation:

- Pint on changed PHP files;
- `php artisan test tests/Feature/Auth`;
- focused System settings tests or directly impacted System regression;
- route verification for `/admin/login`, `/login`, Google routes and logout routes;
- Vite production build;
- UI smoke of System → Settings → Giao diện đăng nhập;
- preview each of the four themes;
- save/reload Admin settings;
- `/admin/login` renders saved branding;
- Client/PWA target can hold independent settings;
- image upload/remove behavior;
- mobile/tablet/desktop responsive check.

## Deferred / unchanged boundaries

From the earlier Auth refactor, the following remain unchanged:

- `GoogleWebAuthService`: deprecated compatibility adapter / `QUARANTINE`;
- API Auth stub: `QUARANTINE`;
- generic Auth CRUD permissions: `DEFER/REVIEW`;
- cache/jobs/session infrastructure migrations: `QUARANTINE` pending persistence ownership proof.

## Current status

- Previous Auth security refactor: MERGED.
- Follow-up branch creation: COMPLETE.
- Presentation contract: IMPLEMENTED.
- System settings manager: IMPLEMENTED.
- Four-theme live preview: IMPLEMENTED.
- Auth login rendering integration: IMPLEMENTED.
- Focused regression coverage: ADDED, NOT YET USER-EXECUTED.
- Final consolidated test/build/UI checkpoint: NEXT.
