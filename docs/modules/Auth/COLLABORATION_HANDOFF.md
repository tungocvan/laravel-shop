# Auth Collaboration Handoff

## Current objective

**Login Theme Manager UI V1.1** — UI/UX refinement of the already merged Auth Login Theme & Branding Manager V1.

Current branch:

`refactor/auth-login-theme-admin-ui-v1-1`

V1 was merged after focused regression, production build, Pint and user UI acceptance.

## V1.1 approved scope

Refine only the System administration experience for login presentation settings:

- align ordinary inputs/textareas with `.codex/standards/ADMIN_UI_STANDARD.md`;
- improve workspace hierarchy and responsive layout;
- group configuration into clear task sections;
- replace browser-native visible file controls with managed upload cards;
- improve theme selection, color/overlay controls and Google visibility switch;
- keep Live Preview prominent and sticky on sufficiently wide screens;
- keep the primary save action visible through a sticky action area;
- preserve existing settings keys, Auth presentation contract, persistence behavior and security behavior.

## Ownership boundary

Unchanged from V1:

- Auth owns `LoginPresentationService`, supported theme vocabulary, presentation normalization/defaults and login rendering contract.
- System owns the administration UI, generic settings persistence/permission enforcement and managed login-branding upload lifecycle.

V1.1 does not move ownership between modules.

## Implementation status

Implemented on this branch in `Modules/System/resources/views/livewire/settings/partials/login-theme.blade.php`:

- professional sectioned workspace: Mẫu giao diện, Nội dung thương hiệu, Màu sắc & hiệu ứng, Hình ảnh, Tùy chọn đăng nhập;
- canonical visible-border Admin text controls with consistent padding, radius and indigo focus state;
- validation messages located next to editable fields;
- improved Admin / Client-PWA segmented target selector;
- richer theme cards with selected state;
- combined color swatch + HEX control;
- overlay slider with explicit percentage and light/dark context;
- managed logo/background upload cards with thumbnails and `Thay ảnh` / `Xóa` actions instead of exposed native file chrome;
- switch-style Google Workspace visibility control;
- sticky save action area with active target context;
- larger bordered Live Preview surface, sticky on wide desktop layouts;
- responsive single-column fallback before the wide workspace breakpoint.

## Safety / unchanged behavior

- No database/schema changes.
- No settings-key changes.
- No Auth guard, credential, authorization, OAuth, callback or session-security changes.
- No changes to `LoginPresentationService` behavior.
- No changes to upload persistence/deletion logic; this MR changes only its administration presentation.
- Live Preview continues to use unsaved Livewire state and does not persist until the explicit save action.

## Validation checkpoint

V1 baseline before this refinement:

- focused impacted regression: `188 passed (1075 assertions)`;
- Vite production build: PASS (`34 modules transformed`);
- Pint: PASS;
- V1 UI smoke: PASS.

V1.1 final checkpoint requested from the user:

- Pint for the affected System Livewire/view surface as applicable;
- focused Auth regression to prove presentation/security behavior remains stable;
- focused System regression for settings;
- Vite production build;
- UI smoke at System → Settings → Giao diện đăng nhập on desktop and a narrow/mobile viewport;
- verify all four theme cards, Admin/Client-PWA target switch, inputs, color/slider, upload cards, Google switch, sticky save action and Live Preview.

## Deferred / unchanged Auth refactor boundaries

- `GoogleWebAuthService`: deprecated compatibility adapter / `QUARANTINE`;
- API Auth stub: `QUARANTINE`;
- generic Auth CRUD permissions: `DEFER/REVIEW`;
- cache/jobs/session infrastructure migrations: `QUARANTINE` pending persistence ownership proof.

## Current status

- V1: MERGED.
- V1.1 branch: ACTIVE.
- V1.1 UI implementation: COMPLETE.
- Architecture/security scope: UNCHANGED.
- Final focused test/build/UI checkpoint: NEXT.
- PR: NOT YET CREATED; wait for final UI acceptance.
