# Request Module — Collaboration Handoff

Last updated: 2026-08-26  
Repository: `tungocvan/laravel-shop`  
Base branch: `main`  
Working branch: `feat/clientportal-request-requester`  
Pull request: `#41 MR-3: ClientPortal requester flow for Request`  
PR state: **open draft; not merged**  
Code checkpoint before this handoff: `fc321b46`  

## Current objective

MR-3 exposes the Request **requester** workflow inside ClientPortal/PWA using guard `web`, while preserving the existing admin Request workspace and reusing the existing Request domain services.

This MR intentionally does **not** open approver decision capabilities on `web`. Approver operations such as `request.task.decide` remain out of scope for MR-3.

## Implemented in MR-3

### Authorization foundation

- `RequestAuthorizationContext` supports `admin` and `web` channels.
- Request policies resolve permissions through the active Request authorization guard instead of assuming `admin`.
- `MyRequestsQuery`, collaboration visibility and attachment visibility are guard-aware.
- `ModulePermissionManager` provisions permissions by guard while preserving the existing admin permission discovery semantics.
- Request module declares `permissions_by_guard.web` for ClientPortal access and requester operations.

Requester operational permissions currently provisioned for `web` include:

- `request.instance.view-own`
- `request.instance.create`
- `request.instance.update-own`
- `request.instance.submit`
- `request.instance.cancel-own`
- `request.comment.create`
- `request.attachment.upload`
- `request.attachment.download`

ClientPortal feature permissions include access/overview/create/mine plus the existing future inbox/processed feature permissions. MR-3 does not add `request.task.decide` to `web`.

### ClientPortal requester flow

Routes under `/apps/request` now include:

- `client.request.dashboard`
- `client.request.catalog`
- `client.request.create`
- `client.request.mine`
- `client.request.show`
- `client.request.attachments.download`

All requester routes are under:

- `web`
- `auth:web`
- `client.application:request`
- `UseRequestAuthorizationGuard:web`
- the matching ClientPortal feature permission middleware

### Livewire channel reuse

The existing Request requester Livewire components are reused for both admin and ClientPortal.

`InteractsWithRequestAuthorization` stores the requester channel in a locked Livewire property and re-establishes the Request authorization context on subsequent Livewire requests. The requester components no longer hard-code `auth('admin')`.

Updated requester components:

- `Catalog`
- `CreateDraft`
- `MyRequests`
- `RequestDetail`
- `CommentComposer`
- `AttachmentManager`

Admin continues to use existing `request.*` routes and the existing admin detail Blade. ClientPortal uses `client.request.*` routes and a dedicated requester-focused PWA detail view.

### Requester mutations reused from the domain layer

ClientPortal requester UI calls the existing Request application services directly through the Livewire components:

- create draft
- save draft
- submit
- resubmit returned request
- cancel own draft/returned request
- add comment
- upload attachment
- download attachment

No second requester domain workflow was introduced.

### ClientPortal UI

Added ClientPortal screens for:

- catalog
- create draft entry
- my requests
- request detail

The Request mobile navigation now exposes:

- Tổng quan
- Tạo đề nghị
- Của tôi

`config/livewire.php` has `inject_assets => false`, so the four Request ClientPortal screens explicitly load Livewire styles/scripts. This is scoped to Request and does not modify the shared ClientPortal layout in MR-3.

## Important review findings already corrected

- Requester Livewire components originally hard-coded `auth('admin')`; converted to channel-aware actor resolution.
- Collaboration and attachment queries originally checked permissions using guard `admin`; converted to the active Request guard.
- Attachment download controller originally preferred an admin session; it now selects the user from the active Request guard.
- ClientPortal Request screens initially lacked Livewire assets while global auto-injection is disabled; assets were added explicitly.
- `AttachmentManager::mount()` temporarily had a required dependency after an optional parameter; parameter order was corrected.

## Verification evidence available from GitHub

- Branch is ahead of `main` and behind by `0` at the MR-3 review point.
- GitHub code search returned zero `auth('admin')` matches under `Modules/Request/Livewire/Requester`; GitHub marked the code-search index incomplete, so this is supporting evidence only.
- `RequestWebPermissionConfigurationTest` covers web requester permissions and verifies that admin-only permissions such as `request.dashboard.view` are not copied into the web set.
- Draft PR #41 is open and not merged.
- Repository currently has no `.github/workflows` directory; therefore PR #41 does not receive GitHub Actions CI automatically.

## Validation still required before merge

The current ChatGPT environment cannot run the repository test suite because the GitHub connector exposes repository data but the execution container cannot resolve `github.com` to clone the project. Therefore **runtime tests are not claimed as passed**.

Run the accepted two-stage Request validation from a repository checkout:

```bash
git pull --ff-only
php artisan test tests/Feature/Request/Authorization/RequestWebPermissionConfigurationTest.php
php artisan test tests/Feature/Request
```

Rule: run the full `tests/Feature/Request` regression only if the focused authorization test passes. If Test 1 fails, stop and return the raw failure.

Recommended manual ClientPortal smoke after tests:

1. Sign in through guard `web` with a user that has Request ClientPortal feature permissions plus requester operational permissions.
2. Open `/apps/request`.
3. Open **Tạo đề nghị**, choose an eligible request type and create a draft.
4. Save draft values, upload an attachment and add a comment.
5. Submit the draft and verify the request becomes pending.
6. Open **Đề nghị của tôi** and verify only the actor's requests are visible.
7. Open the submitted request and download a clean attachment.
8. Verify another web user without ownership cannot access the request/detail/download URL.
9. Verify admin Request requester pages still use the existing admin views/routes without regression.

## Permission synchronization

After deploying permission changes, synchronize module permissions through the existing Role lifecycle:

```bash
php artisan db:seed --class='Modules\Role\database\seeders\RolesAndPermissionsSeeder'
```

Do not introduce a separate ClientPortal Request permission seeder.

## Merge boundary

- PR #41 must remain unmerged until focused + Request regression tests and the requester smoke path pass.
- This handoff does not authorize enabling the Request module in production.
- Production enablement remains governed by the Request implementation/release runbooks.

## Prior Request baseline

The earlier Request UX Phase 2 work established requester, approver and administration workspaces; definition/version management; SLA/recovery UX; reports/exports; demo/runtime safety; audience authorization UX; governed duplication/cleanup; and the focused Request regression workflow. MR-3 builds ClientPortal requester delivery on top of that accepted Request domain baseline rather than replacing it.
