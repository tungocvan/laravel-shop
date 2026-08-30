# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Slice 4: Chat Legacy Compatibility Cleanup**

Status: **VERIFIED — READY FOR PR REVIEW**

Branch/checkpoint: `refactor/admin-chat-legacy-compatibility-cleanup`

This slice was explicitly approved after the Chat canonical ownership boundary PR was merged.

## Cleanup decision

`Modules/Chat` remains the canonical owner of Chat runtime behavior.

The dedicated cleanup removes only obsolete Admin Chat runtime copies after the canonical routes/controller/Livewire/service/models were already active and verified in the previous slice.

Preserved contracts:

- `admin.chat.index` -> `/admin/chat/internal-chat`
- `admin.chat.cskh` -> `/admin/chat`
- both routes resolve to `Modules\Chat\Http\Controllers\ChatController`
- both routes remain under `auth:admin`
- both routes require `permission:view_chat,admin`
- capability-specific Chat Livewire authorization remains in place
- canonical realtime behavior is unchanged

## Removed legacy Admin Chat runtime

The following files were removed:

- `Modules/Admin/Http/Controllers/ChatController.php`
- `Modules/Admin/Livewire/Chat/ChatManager.php`
- `Modules/Admin/Models/ChatSession.php`
- `Modules/Admin/Models/ChatMessage.php`
- `Modules/Admin/Services/ChatService.php`
- `Modules/Admin/resources/views/pages/chat/index.blade.php`
- `Modules/Admin/resources/views/livewire/chat/chat-manager.blade.php`

Canonical `Modules/Chat/*` source was not moved back into Admin.

## Website client corrective fix discovered during verification

Manual client verification exposed a pre-existing Website shell asset-loading defect: the storefront rendered the Website Livewire chat widget HTML, but Livewire JavaScript was not loaded, so `wire:click` actions could not hydrate or execute.

Corrective changes in this branch:

- `Modules/Website/resources/views/partials/layout/runtime-head.blade.php` now includes `@livewireStyles`;
- `Modules/Website/resources/views/partials/layout/runtime-scripts.blade.php` now includes `@livewireScripts` before `@stack('scripts')`;
- `Modules/Website/Livewire/Chat/ChatWidget.php` uses a server-backed `toggleChat()` action for deterministic open/close behavior;
- `Modules/Website/resources/views/livewire/chat/chat-widget.blade.php` renders the panel from Livewire state rather than relying on Alpine entanglement for the primary toggle;
- added `tests/Feature/Website/WebsiteLivewireRuntimeAssetContractTest.php` to protect Website Livewire runtime assets.

This corrective fix does not alter Chat routes, schema, migrations, or realtime protocol semantics.

## Guardrails

`tests/Feature/Admin/AdminChatOwnershipBoundaryContractTest.php` verifies:

- the seven legacy Admin Chat files remain absent;
- canonical Chat models/service/Livewire/views remain present;
- route URLs/names/controller ownership remain unchanged;
- `view_chat` remains on both admin Chat routes;
- canonical Chat runtime does not import Admin Chat models/service;
- canonical Chat service retains `deleteAllMessages()`;
- capability-specific Livewire permissions remain enforced.

`tests/Feature/Website/WebsiteLivewireRuntimeAssetContractTest.php` protects the Website shell Livewire bootstrap used by the storefront chat widget.

`docs/modules/Admin/OWNERSHIP_BASELINE.md` classifies Chat legacy runtime as `CLEANED`.

## Runtime / schema impact

Route URL/name change: **NONE**

Authentication guard change: **NONE**

Authorization change: **NONE IN THIS SLICE** — hardening from Slice 3 is preserved

Website Livewire runtime: **FIXED** — storefront now loads Livewire assets required by the Website chat widget

Realtime protocol redesign: **NONE**

Database/schema/migration change: **NONE**

Chat manifest dependency on Admin: **UNCHANGED** — Chat views still use the Admin presentation shell

P0 database administration quarantine: **UNCHANGED**

## Verification

Focused Website Livewire runtime contract: **PASS**.

Manual end-to-end client/admin verification: **PASS**.

Verified flow:

- storefront Chat widget opens successfully;
- `Bắt đầu Chat ngay` successfully creates/opens the client chat session;
- client can send a message;
- `/admin/chat` receives the client session/message successfully;
- no missing Livewire class/view/model/service error was observed in the verified flow.

Earlier Slice 3 baseline remained green before this cleanup:

```text
Tests: 11 passed (86 assertions)
Tests: 150 passed (1400 assertions)
```

The user reported the dedicated Website Livewire runtime test PASS after the corrective fix.

Full project regression was intentionally not run; verification remains focused on Admin, Chat, Website Livewire runtime, and the directly impacted client/admin chat flow.

## Acceptance criteria

- legacy Admin Chat runtime files absent: **CONFIRMED**;
- canonical Chat runtime files present: **CONFIRMED BY CONTRACT**;
- Chat route names/URLs unchanged: **CONFIRMED BY CONTRACT**;
- Chat route/controller ownership unchanged: **CONFIRMED BY CONTRACT**;
- Chat permissions unchanged from Slice 3: **CONFIRMED**;
- Website Livewire runtime asset contract: **PASS**;
- storefront widget open: **PASS**;
- client send message: **PASS**;
- Admin receive message: **PASS**;
- schema/migration changes: **NONE**;
- PR readiness: **READY**.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` remains quarantined and must stay unreachable.

### Remaining Admin legacy families

Product, Order, Post/content, customer/address, roles/staff, marketing/public-site and system/environment remain separate ownership/reachability candidates. This Chat cleanup does not authorize cleanup of any of those families.

Production migration-ledger/table ownership remains unresolved and out of scope.

## Next phase

Next Admin legacy-family slice: **NOT AUTHORIZED YET**.

After this checkpoint is merged, inspect the remaining candidates and propose exactly one next family before implementation.
