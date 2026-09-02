# Auth Collaboration Handoff

## Current objective

**Login Theme Manager UI V1.1** — UI/UX refinement of the already merged Auth Login Theme & Branding Manager V1.

Current branch:

`refactor/auth-login-theme-admin-ui-v1-1`

V1 was merged after focused regression, production build, Pint and user UI acceptance.

## V1.1 approved scope

Refine the System administration experience for login presentation settings while preserving Auth ownership and security boundaries:

- align controls with `.codex/standards/ADMIN_UI_STANDARD.md`;
- use a professional two-region configuration + Live Preview workspace;
- support Desktop / Mobile preview modes;
- improve theme, branding, color, overlay and Google visibility controls;
- isolate image upload from Livewire's file-upload lifecycle;
- preserve existing settings keys, Auth presentation contract and authentication/security behavior.

## Ownership boundary

Unchanged from V1:

- Auth owns `LoginPresentationService`, supported theme vocabulary, presentation normalization/defaults and login rendering contract.
- System owns the administration UI, generic settings persistence/permission enforcement and managed login-branding asset lifecycle.

V1.1 does not move ownership between modules.

## Delivered implementation

System now exposes Login Theme as a dedicated top-level settings workspace rather than a nested dynamic Livewire settings tab. The workspace provides:

- professional sections: Mẫu giao diện, Nội dung thương hiệu, Màu sắc & hiệu ứng, Hình ảnh, Tùy chọn đăng nhập;
- Admin / Client-PWA target selector;
- four theme cards with selected state;
- canonical Admin text controls, color + HEX input and overlay slider;
- sticky save action;
- larger sticky Live Preview with Desktop / Mobile modes;
- responsive single-column fallback;
- robust missing/broken image fallback.

### Image upload corrective architecture

During V1.1 UI verification, Livewire file selection could blank the administration UI, including the cancel-file-picker path. The image lifecycle was therefore isolated from Livewire:

- `LoginTheme` no longer uses `WithFileUploads` for logo/background assets;
- logo/background uploads use standard Laravel multipart HTTP POST endpoints;
- asset removal uses the dedicated HTTP asset endpoint;
- upload validation, `system.settings.update` authorization, settings persistence and managed old-file deletion remain enforced by System;
- newly stored files are cleaned up if persistence fails;
- the native file picker is launched outside the Livewire-managed DOM so both file selection and Cancel leave the Livewire workspace stable;
- the previous hard-coded `/storage/img/logo.png` fallback was removed so an unavailable fallback asset does not generate a broken 403 image request.

This correction changes only presentation/settings asset transport. It does not change login credentials, guards, sessions, OAuth behavior or database schema.

## Safety / unchanged behavior

- No database/schema changes.
- No settings-key changes.
- No Auth guard, credential, authorization, OAuth, callback or session-security changes.
- Auth remains the canonical login presentation owner.
- System remains the canonical administration/settings owner.
- Live Preview uses unsaved Livewire settings state and persists only through the explicit save action.

## Final validation checkpoint

User UI acceptance: **PASS**.

Verified UI flows include the Login Theme workspace, Admin / Client-PWA configuration, Desktop / Mobile preview, image selection/upload, and cancelling the native image picker without leaving the UI blank.

Focused Auth regression:

```text
PASS  Tests\Feature\Auth\LoginThemePresentationTest
3 passed (17 assertions)
```

Route inspection:

```text
GET|HEAD  admin/system/settings/login-theme
POST      admin/system/settings/login-theme/assets/{type}
DELETE    admin/system/settings/login-theme/assets/{type}
```

Vite production build:

```text
PASS — 34 modules transformed
```

### Pint status

The repository-wide command `vendor/bin/pint --test` reports **435 pre-existing style issues across 1658 files**. The failures span many unrelated modules and legacy application/test files, so repository-wide Pint is not a valid V1.1 acceptance gate and must not be auto-fixed as part of this focused Auth/System change.

No repository-wide formatting cleanup is included in V1.1.

## Deferred / unchanged Auth refactor boundaries

- `GoogleWebAuthService`: deprecated compatibility adapter / `QUARANTINE`;
- API Auth stub: `QUARANTINE`;
- generic Auth CRUD permissions: `DEFER/REVIEW`;
- cache/jobs/session infrastructure migrations: `QUARANTINE` pending persistence ownership proof.

## Current status

- V1: **MERGED**.
- V1.1 implementation: **COMPLETE**.
- V1.1 UI smoke: **PASS**.
- Focused Auth test: **PASS — 3 tests, 17 assertions**.
- Login Theme route contract: **PASS — 3 routes**.
- Production asset build: **PASS**.
- Repository-wide Pint: **BLOCKED BY PRE-EXISTING REPOSITORY STYLE DEBT — 435 issues**; no unrelated cleanup authorized.
- Architecture/security scope: **PRESERVED**.
- Next step: final branch diff/review and PR preparation; do not merge automatically.
