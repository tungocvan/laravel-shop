# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Slice 4: Chat Legacy Compatibility Cleanup**

Status: **IMPLEMENTED — awaiting local focused verification**

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

## Guardrails

`tests/Feature/Admin/AdminChatOwnershipBoundaryContractTest.php` now also verifies:

- the seven legacy Admin Chat files remain absent;
- canonical Chat models/service/Livewire/views remain present;
- route URLs/names/controller ownership remain unchanged;
- `view_chat` remains on both admin Chat routes;
- canonical Chat runtime does not import Admin Chat models/service;
- canonical Chat service retains `deleteAllMessages()`;
- capability-specific Livewire permissions remain enforced.

`docs/modules/Admin/OWNERSHIP_BASELINE.md` now classifies Chat legacy runtime as `CLEANED`.

## Runtime / schema impact

Route URL/name change: **NONE**

Authentication guard change: **NONE**

Authorization change: **NONE IN THIS SLICE** — hardening from Slice 3 is preserved

Realtime protocol redesign: **NONE**

Database/schema/migration change: **NONE**

Chat manifest dependency on Admin: **UNCHANGED** — Chat views still use the Admin presentation shell

P0 database administration quarantine: **UNCHANGED**

## Required local verification

Sync the branch and run focused ownership/guardrail contracts:

```bash
php artisan test \
  tests/Feature/Admin/AdminChatOwnershipBoundaryContractTest.php \
  tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php \
  tests/Feature/Admin/AdminDatabaseIsolationContractTest.php
```

Then run impacted Admin + Chat regression only:

```bash
php artisan test tests/Feature/Admin tests/Feature/Chat
```

If `tests/Feature/Chat` does not exist, run:

```bash
php artisan test tests/Feature/Admin
```

Do not run the full project suite for this checkpoint.

Manual UI smoke:

- `/admin/chat`
- `/admin/chat/internal-chat`
- open/select a customer session where local data exists
- send a message with appropriate permission
- confirm no missing Livewire class/view/model/service errors

## Acceptance criteria

Before PR readiness:

- legacy Admin Chat runtime files absent;
- canonical Chat runtime files present;
- Chat route names/URLs unchanged;
- Chat route/controller ownership unchanged;
- Chat permissions unchanged from Slice 3;
- focused Chat/Admin ownership tests PASS;
- impacted Admin + Chat regression PASS;
- manual Chat UI smoke PASS;
- no schema/migration changes.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` remains quarantined and must stay unreachable.

### Remaining Admin legacy families

Product, Order, Post/content, customer/address, roles/staff, marketing/public-site and system/environment remain separate ownership/reachability candidates. This Chat cleanup does not authorize cleanup of any of those families.

Production migration-ledger/table ownership remains unresolved and out of scope.

## Next phase

Next Admin legacy-family slice: **NOT AUTHORIZED YET**.

After this checkpoint is locally verified and merged, inspect the remaining candidates and propose exactly one next family before implementation.
