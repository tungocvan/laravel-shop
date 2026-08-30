# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Slice 3: Chat Canonical Ownership & Authorization Boundary**

Status: **IMPLEMENTED — awaiting local focused verification**

Branch/checkpoint: `refactor/admin-chat-canonical-ownership-boundary`

This slice was explicitly approved after the Category cleanup PR was merged.

## Canonical ownership decision

`Modules/Chat` is the canonical owner of Chat runtime behavior.

Admin remains the authenticated presentation shell for Chat admin pages, but Chat owns its controller, Livewire components, services, models, realtime payload handling and Chat permissions.

Preserved route contracts:

- `admin.chat.index` -> `/admin/chat/internal-chat`
- `admin.chat.cskh` -> `/admin/chat`

Both routes continue to resolve to `Modules\Chat\Http\Controllers\ChatController`.

## Changes in this slice

### Authorization boundary

`Modules/Chat/routes/web.php` now requires:

- `web`
- `auth:admin`
- `permission:view_chat,admin`

for both admin Chat workspaces.

Canonical Chat Livewire components also authorize operations at the component/action layer so Livewire update requests do not rely only on the initial page route:

- read/render/load: `view_chat`
- send message: `create_chat`
- assign/select customer session: `edit_chat`
- delete message / clear session messages: `delete_chat`
- internal-chat send: `create_chat`

### Canonical Chat models/services

The canonical Chat runtime no longer imports Admin-owned Chat models/services:

- `Modules/Chat/Services/ChatService.php` uses `Modules\Chat\Models\ChatSession` and `ChatMessage`;
- `Modules/Chat/Livewire/Chat/ChatManager.php` uses Chat-owned models and service;
- `Modules/Chat/Livewire/Chat/ChatWidget.php` uses Chat-owned `ChatSession`;
- `Modules/Chat/Livewire/Chat/InternalChatManager.php` has explicit Chat permission enforcement.

`ChatService::deleteAllMessages()` was retained in the canonical service so switching away from the legacy Admin service does not drop the existing clear-session behavior.

Realtime event/channel semantics remain based on the existing canonical Chat implementation.

### Compatibility / deletion boundary

Legacy Admin Chat files are **not deleted yet** in this slice.

Examples still physically present include legacy Admin Chat controller, Livewire manager, models, service and view. They are no longer canonical ownership, but deletion requires repository-wide caller/alias proof that is not yet complete.

This deliberately avoids repeating a bulk-delete assumption.

The Chat manifest still declares an `Admin` dependency because Chat pages extend `Admin::layouts.master`. Removing that presentation dependency is not authorized by this checkpoint.

## Guardrail added

Added `tests/Feature/Admin/AdminChatOwnershipBoundaryContractTest.php` to verify:

- Chat admin URLs and route names remain unchanged;
- both admin Chat routes resolve to the Chat controller;
- both routes require `auth:admin` and `permission:view_chat,admin`;
- canonical Chat service/Livewire code does not import Admin Chat models/service;
- canonical Chat models/services/components remain present;
- canonical Chat service retains `deleteAllMessages()`;
- Chat Livewire components contain capability-specific authorization checks.

Updated `docs/modules/Admin/OWNERSHIP_BASELINE.md` to classify Chat as `BOUNDARY MOVED` rather than fully `CLEANED` until legacy caller proof is complete.

## Runtime / schema impact

Route URL/name change: **NONE**

Authentication guard change: **NONE**

Authorization: **HARDENED** — `view_chat` now protects both Chat admin routes and Livewire actions enforce capability-specific permissions.

Chat canonical model/service ownership: **MOVED OUT OF ADMIN DEPENDENCY**

Realtime protocol redesign: **NONE**

Database/schema/migration change: **NONE**

P0 database administration quarantine: **UNCHANGED**

## Required local verification

Sync the branch and run focused contracts:

```bash
php artisan test \
  tests/Feature/Admin/AdminChatOwnershipBoundaryContractTest.php \
  tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php \
  tests/Feature/Admin/AdminDatabaseIsolationContractTest.php
```

Then run impacted regressions only:

```bash
php artisan test tests/Feature/Admin tests/Feature/Chat
```

If `tests/Feature/Chat` does not exist in the local tree, run:

```bash
php artisan test tests/Feature/Admin
```

Do not run the full project suite for this checkpoint.

Manual UI smoke should verify with an admin account that has `view_chat`:

- `/admin/chat`
- `/admin/chat/internal-chat`
- opening/selecting a session still works where local data exists;
- sending a message works only with the corresponding Chat capability;
- no Livewire component-resolution or model-class errors appear.

Also verify an admin without `view_chat` receives 403 for both routes if a suitable local account is available.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` remains quarantined and must stay unreachable.

### P1 — Chat compatibility cleanup

Legacy Admin Chat files remain physically present until caller proof is complete. Do not delete them solely because the canonical Chat routes no longer use them.

Production migration-ledger/table ownership remains unresolved and is intentionally out of scope.

## Acceptance criteria

Before PR readiness:

- Chat ownership boundary contract: PASS;
- existing Admin ownership/P0 guardrails: PASS;
- focused Admin + Chat impacted regression: PASS;
- `/admin/chat` UI smoke: PASS;
- `/admin/chat/internal-chat` UI smoke: PASS;
- route URLs/names unchanged;
- `view_chat` route authorization confirmed;
- canonical Chat runtime has no Admin Chat model/service imports;
- no schema/migration changes.

## Next phase

Next Chat cleanup or another Admin legacy-family slice: **NOT AUTHORIZED YET**.

After this checkpoint is verified and merged, first decide whether remaining legacy Admin Chat copies have sufficient repository-wide caller proof for deletion. Do not combine that decision with unrelated Product/Order/Post cleanup.
