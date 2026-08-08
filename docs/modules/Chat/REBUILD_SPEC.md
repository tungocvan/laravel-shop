# Chat Rebuild Specification

Created: 2026-06-18

Sources:

- `ROADMAP.md`
- `docs/AI_PROJECT_CONTEXT.md`
- `docs/CODEX_BOOTSTRAP.md`
- `docs/modules/Chat/ANALYSIS.md`
- `docs/modules/Chat/REFACTOR_PLAN.md`

Note: The user prompt mentioned "Category module" once, but `<Module_Name>` is `Chat`; this specification is for `Modules/Chat`.

## 1. Goal

The rebuilt/refactored Chat module must provide secure, bounded, maintainable realtime messaging for:

- Customer support chat between guests/users and admins.
- Internal direct messaging between authorized admins/users.

Design decisions:

- Make `Modules/Chat` the canonical owner of chat domain models and services. Reference: `ANALYSIS.md` issues in sections 7, 8, and 14; `REFACTOR_PLAN.md` P0-07.
- Close all public and mutating authorization gaps before UI cleanup. Reference: `ANALYSIS.md` sections 10 and 12; `REFACTOR_PLAN.md` P0-01 through P0-06.
- Move all database query and business behavior out of Livewire into services. Reference: `ANALYSIS.md` sections 5, 6, 13, and 14; `REFACTOR_PLAN.md` P1-01.
- Redact logs and centralize bridge configuration. Reference: `ANALYSIS.md` sections 7 and 10; `REFACTOR_PLAN.md` P0-08 and P1-08.
- Keep import/export out of scope unless a business requirement is confirmed. Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P2-08.

## 2. Target Architecture

Required flow:

```text
Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import
→ Export
→ Model
→ Migration
```

Target request flows:

- Support admin page: `Modules/Chat/routes/web.php` -> `Modules/Chat/Http/Controllers/ChatController.php::support` -> `Modules/Chat/resources/views/pages/chat/support.blade.php` -> `Modules/Chat/Livewire/Chat/ChatManager.php` -> `Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php` -> optional shared socket/header components -> `Modules/Chat/Services/ChatService.php` -> `Modules/Chat/Models/ChatSession.php` / `Modules/Chat/Models/ChatMessage.php` -> Chat migrations.
- Internal chat admin page: `Modules/Chat/routes/web.php` -> `Modules/Chat/Http/Controllers/ChatController.php::internal` -> `Modules/Chat/resources/views/pages/chat/internal.blade.php` or existing `index.blade.php` -> `Modules/Chat/Livewire/Chat/InternalChatManager.php` -> `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php` -> optional shared socket/header components -> `Modules/Chat/Services/InternalChatService.php` -> `Modules/Chat/Models/InternalMessage.php` -> Chat-owned internal message migration.
- Public widget: existing external caller -> `Modules/Chat/Livewire/Chat/ChatWidget.php` -> `Modules/Chat/resources/views/livewire/chat/chat-widget.blade.php` -> `Modules/Chat/Services/ChatService.php` -> Chat models/migrations.
- Realtime support socket: Livewire Blade / `socket/echo.js` -> `socket/events/chat.js` -> authorized `session-*` room -> Laravel service emits through `socket/server.js` `/broadcast` -> allowlisted event to room.
- Realtime internal socket: Livewire Blade / `socket/echo.js` -> `socket/events/internal-chat.js` -> authorized `dm-*` room and verified presence -> Laravel service emits through `socket/server.js` `/broadcast` -> allowlisted event to room.

Design decisions:

- Routes define only URL, name, middleware, and controller action. Reference: `ANALYSIS.md` section 2; `REFACTOR_PLAN.md` P0-01, P0-02, P2-01.
- Controllers stay thin and return page blades only. Reference: `ANALYSIS.md` section 3.
- Livewire owns state, validation, UI events, and service calls only. Reference: `ANALYSIS.md` sections 5 and 6; `REFACTOR_PLAN.md` P1-01 and P1-02.
- Services own queries, authorization-supporting invariants, transactions, bridge calls, and persistence. Reference: `ANALYSIS.md` sections 7, 12, 13; `REFACTOR_PLAN.md` P1-01, P1-03, P1-04.
- Shared components/assets may be added only for real duplication: page header/scroll behavior and socket behavior. Reference: `ANALYSIS.md` sections 4, 6, 14; `REFACTOR_PLAN.md` P1-09 and P1-10.
- Import/export layer remains absent by default. Reference: `REFACTOR_PLAN.md` P2-08.
- Socket room membership must be authorized with server-issued signed room tokens, not raw browser IDs or room strings. Reference: `ANALYSIS.md` section 16; `REFACTOR_PLAN.md` P0-10 and P0-11.
- Node `/broadcast` must fail closed, validate bridge secret, and allow only known chat events/channels. Reference: `ANALYSIS.md` section 16; `REFACTOR_PLAN.md` P0-09 and P0-12.

Needs confirmation before coding:

- Whether the public API route in `Modules/Chat/routes/api.php` should be removed entirely or rebuilt as an authenticated API. Reference: `REFACTOR_PLAN.md` P0-01.
- Whether route names should be changed now or after caller discovery. Reference: `REFACTOR_PLAN.md` P2-01.
- Exact signed socket token format, TTL, and signing secret source. Reference: `REFACTOR_PLAN.md` P0-10.
- Whether socket tests should use Jest, Node test runner, or an existing project test harness. Reference: `REFACTOR_PLAN.md` P0-09 through P1-15.

## 3. Database Design

### Tables

`chat_sessions`

- Current migration: `Modules/Chat/database/migrations/-0001_11_30_000041_create_chat_sessions_table.php`.
- Target owner: `Modules/Chat`.
- Columns:
  - `id`: primary key.
  - `session_token`: unique opaque token.
  - `user_id`: nullable FK to confirmed user table.
  - `admin_id`: nullable FK to confirmed admin/user table.
  - `guest_name`: nullable string.
  - `guest_phone`: nullable string.
  - `guest_email`: nullable string.
  - `status`: enum or constrained string: `open`, `closed`, `pending`.
  - `last_message_at`: nullable timestamp.
  - `created_at`, `updated_at`.
- Indexes:
  - Unique index on `session_token`.
  - Index on `status`.
  - Index on `last_message_at`.
  - Consider compound index for admin inbox filtering after service queries are finalized.
- Foreign keys:
  - `user_id` -> confirmed user table, null on delete.
  - `admin_id` -> confirmed admin/user table, null on delete.
- Constraints:
  - `session_token` must be unique.
  - `status` must be validated at service level even if the DB enum remains.

References: `ANALYSIS.md` sections 8, 12, 13; `REFACTOR_PLAN.md` P1-04 and P2-07.

`chat_messages`

- Current migration: `Modules/Chat/database/migrations/-0001_11_30_000042_create_chat_messages_table.php`.
- Target owner: `Modules/Chat`.
- Columns:
  - `id`: primary key.
  - `chat_session_id`: FK to `chat_sessions`.
  - `sender_id`: nullable ID for authenticated user/admin senders.
  - `sender_type`: constrained value: `guest`, `user`, `admin`.
  - `message`: text.
  - `is_read`: boolean.
  - `metadata`: nullable JSON.
  - `created_at`, `updated_at`.
- Indexes:
  - Existing FK index on `chat_session_id`.
  - Existing indexes on `sender_id`, `sender_type`, `is_read`.
  - Add measured chronology index only after service query shape is finalized: likely `(chat_session_id, created_at)` or `(chat_session_id, id)`.
- Foreign keys:
  - `chat_session_id` -> `chat_sessions.id`, cascade on delete.
- Constraints:
  - `sender_type` validated by service.
  - Message length enforced by Livewire and service validation.

References: `ANALYSIS.md` sections 8, 11, 13; `REFACTOR_PLAN.md` P1-02 and P2-07.

`internal_messages`

- Current migration outside module: `database/migrations/2026_05_10_151034_internal_messages.php`.
- Target owner: `Modules/Chat/database/migrations/<new_internal_messages_migration>.php`.
- Columns:
  - `id`: primary key.
  - `from_id`: FK to confirmed user/admin table.
  - `to_id`: FK to confirmed user/admin table.
  - `message`: text.
  - `seen_at`: nullable timestamp.
  - `created_at`, `updated_at`.
- Indexes:
  - Existing `(from_id, to_id)`.
  - Consider reverse `(to_id, from_id)` or normalized room key only after service query strategy is confirmed.
- Foreign keys:
  - `from_id` -> confirmed user/admin table.
  - `to_id` -> confirmed user/admin table.
- Constraints:
  - `from_id` and `to_id` must not be the same unless business approves self-chat.
  - Recipient eligibility enforced by service.

References: `ANALYSIS.md` sections 8, 10, 13; `REFACTOR_PLAN.md` P0-06, P1-06, P1-07, P2-07.

### Migration Notes

- Do not rename malformed negative-year migrations as part of the first security pass unless migration hygiene is the selected task. Reference: `ROADMAP.md` P1-08 and `REFACTOR_PLAN.md` risk control.
- Do not move `database/migrations/2026_05_10_151034_internal_messages.php` blindly. Existing deployment history must be checked first. Reference: `REFACTOR_PLAN.md` P1-06 and Risk Control.
- Add new indexes through new migrations after final service query patterns are known. Reference: `REFACTOR_PLAN.md` P2-07.

Needs confirmation before coding:

- Canonical user/admin table/model for `user_id`, `admin_id`, `from_id`, and `to_id`. Reference: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P1-07.
- Whether `sender_type` should remain an enum or become a validated string for MySQL portability. Reference: `ANALYSIS.md` section 8.

## 4. Model Design

### `Modules/Chat/Models/ChatSession.php`

- Table: explicit `$table = 'chat_sessions'`.
- Fillable:
  - `session_token`
  - `user_id`
  - `admin_id`
  - `guest_name`
  - `guest_phone`
  - `guest_email`
  - `status`
  - `last_message_at`
- Casts:
  - `last_message_at` => `datetime`
- Relationships:
  - `messages()` -> has many `Modules\Chat\Models\ChatMessage`.
  - `latestMessage()` -> has one latest `Modules\Chat\Models\ChatMessage`.
  - `user()` -> belongs to confirmed user model.
  - `admin()` -> belongs to confirmed admin/user model.
- Scopes:
  - `open()` for open sessions.
  - `assignedTo(int $adminId)` for admin inbox.
  - `recent()` for `last_message_at` ordering.
- Accessors/mutators:
  - Keep display-name logic lightweight. Avoid lazy loading in `getDisplayNameAttribute`; prefer service-calculated display names from eager-loaded relations.

References: `ANALYSIS.md` sections 8 and 13; `REFACTOR_PLAN.md` P0-07, P1-05, P2-06.

### `Modules/Chat/Models/ChatMessage.php`

- Table: explicit `$table = 'chat_messages'`.
- Fillable:
  - `chat_session_id`
  - `sender_id`
  - `sender_type`
  - `message`
  - `is_read`
  - `metadata`
- Casts:
  - `is_read` => `boolean`
  - `metadata` => `array`
- Relationships:
  - `session()` -> belongs to `Modules\Chat\Models\ChatSession`.
- Scopes:
  - `forSession(int $sessionId)`.
  - `chronological()` for oldest-first message display.
  - `latestFirst()` for admin summaries if needed.
- Accessors/mutators:
  - No business logic in accessors.

References: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P0-05, P0-07, P2-06.

### `Modules/Chat/Models/InternalMessage.php`

- Table: explicit `$table = 'internal_messages'`.
- Fillable:
  - `from_id`
  - `to_id`
  - `message`
  - `seen_at`
- Casts:
  - `seen_at` => `datetime`
- Relationships:
  - `fromUser()` -> belongs to confirmed user/admin model.
  - `toUser()` -> belongs to confirmed user/admin model.
- Scopes:
  - `between(int $a, int $b)` for bidirectional conversation query.
  - `chronological()`.
  - `unseenFor(int $userId)` if read state is implemented.
- Accessors/mutators:
  - None required now.

References: `ANALYSIS.md` sections 8 and 13; `REFACTOR_PLAN.md` P0-06, P1-06, P1-07, P2-06.

Needs confirmation before coding:

- Whether `Modules/Admin/Models/ChatSession.php` and `Modules/Admin/Models/ChatMessage.php` are still used by Admin routes. Do not delete until caller migration is complete. Reference: `REFACTOR_PLAN.md` P0-07 and Risk Control.

## 5. Service Design

### `Modules/Chat/Services/ChatService.php`

Responsibilities:

- Own all support chat queries and persistence.
- Enforce session ownership and allowed sender identity.
- Validate service-level invariants.
- Control transaction boundaries for session creation, session claiming, sends, deletes, and read markers.
- Return arrays, models, paginators, or collections; no DTOs.

Public methods:

- `listSessions(array $filters = [], int|string $perPage = 25)`: bounded admin session list with eager-loaded user/admin/latest message.
- `getSessionForAdmin(int $sessionId, int $adminId): ChatSession`: authorized session fetch.
- `claimSession(int $sessionId, int $adminId): ChatSession`: atomic session assignment.
- `resolveWidgetSession(string $token, ?int $userId, array $guestData = []): ChatSession`: safe widget session resolution.
- `getMessagesForSession(int $sessionId, array $actor, int $limit = 50, ?int $beforeId = null)`: bounded messages.
- `sendSupportMessage(int $sessionId, array $actor, string $message, array $metadata = []): ChatMessage`: create support chat message.
- `deleteMessage(int $messageId, int $adminId): bool`: authorized delete with audit-compatible boundary.
- `markSessionRead(int $sessionId, array $actor): void`: optional read-state method.

Transaction boundaries:

- `claimSession`: transaction with conditional update / row lock.
- `resolveWidgetSession`: transaction or atomic first-or-create with retry on unique `session_token`.
- `sendSupportMessage`: transaction for message create and session update; broadcast after commit.
- `deleteMessage`: transaction for delete and session state update; broadcast after commit.

Business rules:

- Sender identity is derived from authenticated context or trusted actor array, not browser payload. Reference: `REFACTOR_PLAN.md` P0-05.
- Widget sessions must belong to the current server session or authenticated user. Reference: `REFACTOR_PLAN.md` P0-04.
- Admins can view/claim/delete only with named permissions and ownership/assignment checks. Reference: `REFACTOR_PLAN.md` P0-02 and P0-03.
- Message text must be non-empty after trim and within confirmed max length. Reference: `ANALYSIS.md` section 11; `REFACTOR_PLAN.md` P1-02.

### `Modules/Chat/Services/InternalChatService.php`

Responsibilities:

- Own internal direct-message query and persistence.
- Enforce recipient eligibility.
- Provide bounded conversation loading.
- Keep Livewire free of user queries.

Public methods:

- `listAvailableRecipients(int $actorId, array $onlineIds = [], int|string $perPage = 25)`: server-filtered recipient list.
- `getConversation(int $actorId, int $recipientId, int $limit = 50, ?int $beforeId = null)`: authorized bounded conversation.
- `sendMessage(int $actorId, int $recipientId, string $message): InternalMessage`: authorized send.
- `makeRoom(int $a, int $b): string`: deterministic room name.
- `markSeen(int $actorId, int $recipientId): void`: optional read-state update.

Transaction boundaries:

- `sendMessage`: transaction for persistence; broadcast after commit.
- `markSeen`: transaction if updating multiple rows.

Business rules:

- Reject unauthorized recipients and self-messaging unless approved. Reference: `REFACTOR_PLAN.md` P0-06.
- Do not trust `onlineUsers` from client state as authorization. Reference: `ANALYSIS.md` section 10.

### `Modules/Chat/Services/ChatBridgeService.php`

Status: optional but recommended if bridge behavior remains shared.

Responsibilities:

- Read bridge config through `config/services.php`.
- Send redacted event payloads to NodeJS bridge.
- Apply timeout and safe failure behavior.
- Log only event type, channel, IDs, status, and correlation data.

Public methods:

- `broadcast(string $event, string $channel, array $payload): void`.

References: `ANALYSIS.md` sections 7, 10, 14; `REFACTOR_PLAN.md` P0-08, P1-08, P1-12.

### Socket / Node realtime services

Files:

- `socket/server.js`
- `socket/echo.js`
- `socket/events/chat.js`
- `socket/events/internal-chat.js`

Responsibilities:

- `socket/server.js` owns Socket.io server boot, strict CORS, bridge auth, `/health`, and `/broadcast`.
- `socket/events/chat.js` owns support-chat socket events only: authorized `join-session`, `leave-session`, `typing`, and delivery events.
- `socket/events/internal-chat.js` owns internal DM socket events only: verified presence, authorized `join-dm-room`, room debug in development, and disconnect cleanup.
- `socket/echo.js` owns browser-side singleton connection and should expose only minimal production-safe helpers.

Public/event methods:

- `POST /broadcast`: accepts only allowlisted event names, allowlisted channel patterns, and validated payloads.
- `join-session`: accepts `{ session_id, token }` or equivalent signed payload. Needs confirmation before coding.
- `leave-session`: leaves only rooms the socket is already authorized for.
- `typing`: accepts validated support-chat typing payload and checks socket room authorization.
- `message-delivered`: accepts validated delivery payload and checks socket room authorization.
- `admin-online`: derives identity from verified socket context or signed token, not raw `user_id`.
- `join-dm-room`: accepts `{ room, token }` or equivalent signed payload. Needs confirmation before coding.

Transaction boundaries:

- Socket files do not open DB transactions.
- Laravel services persist messages in DB transactions and call socket bridge after commit. Reference: `REFACTOR_PLAN.md` P1-03.

Business rules:

- `BRIDGE_SECRET_KEY` must be non-empty or `/broadcast` rejects all requests. Reference: `REFACTOR_PLAN.md` P0-09.
- Socket room joins require server-issued authorization. Reference: `REFACTOR_PLAN.md` P0-10.
- Presence identity is verified server-side. Reference: `REFACTOR_PLAN.md` P0-11.
- Bridge events/channels are allowlisted. Reference: `REFACTOR_PLAN.md` P0-12.
- CORS must not default to `"*"` in production. Reference: `REFACTOR_PLAN.md` P1-14.
- Debug logs are gated by environment and redacted. Reference: `REFACTOR_PLAN.md` P1-14 and P2-10.

Needs confirmation before coding:

- Whether failed realtime broadcast should ever roll back saved messages. Default spec says no; save succeeds and broadcast failure is logged safely. Reference: `REFACTOR_PLAN.md` P1-03.
- Whether presence must support multiple Node processes. If yes, use Redis/Socket.io adapter instead of the in-memory `Map`. Reference: `REFACTOR_PLAN.md` P1-16.

## 6. Livewire Design

### `Modules/Chat/Livewire/Chat/ChatManager.php`

Purpose: Admin customer-support chat manager.

State properties:

- `?int $activeSessionId`
- `string $message`
- `array $messages`
- `string $search`
- `string $statusFilter`
- `int|string $perPage`
- `array $perPageOptions`
- pagination state via Livewire pagination if used

Validation rules:

- `activeSessionId`: required for send, integer, service-authorized.
- `message`: required, string, max length Needs confirmation before coding.
- `statusFilter`: nullable, in `open,pending,closed`.

Events:

- Listen for safe realtime append events after payload shape validation.
- Dispatch `chat-session-selected`, `scroll-bottom`, and safe error/notice events as needed.

Pagination/search/filter/sort:

- Sessions must be paginated or capped through `ChatService::listSessions`.
- Search/filter/sort inputs reset pagination.
- Message history loads in bounded chunks through `ChatService::getMessagesForSession`.

References: `ANALYSIS.md` sections 5, 6, 11, 13; `REFACTOR_PLAN.md` P0-03, P1-01, P1-02, P1-05.

### `Modules/Chat/Livewire/Chat/ChatWidget.php`

Purpose: Public/website support chat widget.

State properties:

- `bool $isOpen`
- `string $step`
- `string $message`
- `?string $sessionToken`
- `?int $activeSessionId`
- `array $messages`

Validation rules:

- `message`: required for send, string, max length Needs confirmation before coding.
- Guest contact fields if enabled: name/phone/email validation Needs confirmation before coding.

Events:

- Dispatch `chat-session-selected` only after service resolves an authorized session.
- Realtime append must validate payload shape and session ownership before appending.

Pagination/search/filter/sort:

- No search/filter/sort.
- Message history loads by bounded chunk, newest-first from service then displayed oldest-first.

References: `ANALYSIS.md` sections 5, 10, 11, 13; `REFACTOR_PLAN.md` P0-04, P1-01, P1-02, P2-09.

### `Modules/Chat/Livewire/Chat/InternalChatManager.php`

Purpose: Internal direct-message manager.

State properties:

- `?int $selectedUserId`
- `?array $selectedUser`
- `string $message`
- `array $messages`
- `array $onlineUsers`
- `string $search`
- `int|string $perPage`

Validation rules:

- `selectedUserId`: required for send, integer, service-authorized recipient.
- `message`: required, string, max length Needs confirmation before coding.

Events:

- Listen for `appendMessage` and `setOnlineUsers`, but treat both as untrusted.
- Dispatch `join-room` only after service authorizes the recipient.
- Dispatch `scroll-bottom` after message changes.

Pagination/search/filter/sort:

- Recipients loaded via `InternalChatService::listAvailableRecipients`.
- Conversation loaded via bounded chunks.
- Use `wire:model.live` in the Blade input.

References: `ANALYSIS.md` sections 5, 6, 10, 11, 13; `REFACTOR_PLAN.md` P0-06, P1-01, P1-02, P1-05, P1-11.

## 7. Blade/UI Design

### Page Blade files

- Target support page: `Modules/Chat/resources/views/pages/chat/support.blade.php`.
- Target internal page: `Modules/Chat/resources/views/pages/chat/internal.blade.php` or keep `Modules/Chat/resources/views/pages/chat/index.blade.php` until route rename is safe.
- Existing root page `Modules/Chat/resources/views/chat.blade.php` should be moved only after route/caller tests pass.

References: `ANALYSIS.md` section 4; `REFACTOR_PLAN.md` P1-09 and P2-02.

### Livewire Blade files

- `Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php`
- `Modules/Chat/resources/views/livewire/chat/chat-widget.blade.php`
- `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php`

Design decisions:

- Keep Blade as presentation only. Do not query models or call services from Blade. Reference: `ANALYSIS.md` sections 5 and 6; `REFACTOR_PLAN.md` P1-01.
- Extract duplicated socket setup and scrolling behavior after P0 security fixes. Reference: `REFACTOR_PLAN.md` P1-10.
- Use `wire:model.live` for message inputs. Reference: `ANALYSIS.md` section 6; `REFACTOR_PLAN.md` P1-11.
- Browser socket scripts must not join rooms with raw IDs. They must request/receive signed room join data from Livewire/service state before calling socket helpers. Reference: `ANALYSIS.md` section 16; `REFACTOR_PLAN.md` P0-10.
- Production Blade/socket output must not expose debug helpers such as `window.debugSocket`. Reference: `REFACTOR_PLAN.md` P2-10.

### Shared components

Potential components/assets:

- `Modules/Chat/resources/views/components/page-header.blade.php` for shared page title/status if duplication remains.
- `Modules/Chat/resources/views/components/socket-scripts.blade.php` or a module JS asset for shared socket behavior.

References: `ANALYSIS.md` sections 4, 6, 14; `REFACTOR_PLAN.md` P1-09 and P1-10.

Socket asset rules:

- `socket/echo.js` should keep one singleton connection and one optional development-only event listener.
- `socket/echo.js` must not keep duplicate `onAny` listeners.
- Room join helpers should accept signed authorization payloads, not only room IDs.
- Debug helpers must be disabled or absent in production.

References: `ANALYSIS.md` section 16; `REFACTOR_PLAN.md` P2-10.

### AdminLTE/Bootstrap layout rules

- Active project standard says new admin UI should use Tailwind CSS 4 and `Admin::layouts.master`, not Bootstrap or jQuery. Reference: `docs/AI_PROJECT_CONTEXT.md` Admin UI Standard and `docs/CODEX_BOOTSTRAP.md` Admin UI Rules.
- Existing repository inventory mentions Bootstrap 5/AdminLTE 4 RC as installed, but this is not the target standard. Reference: `ROADMAP.md` P1-03.
- Needs confirmation before coding: If these Chat pages must preserve legacy AdminLTE/Bootstrap compatibility, isolate compatibility classes and do not introduce new Bootstrap patterns.

### Table design

- Session list should be a responsive, bounded list/table with loading and empty states.
- Internal recipient list should be server-filtered and bounded.
- Use status badges for `open`, `pending`, and `closed`.

References: `ANALYSIS.md` section 13; `REFACTOR_PLAN.md` P1-05.

### Form design

- Message composer must show validation errors, disabled/loading state during send, and safe failure messages.
- Delete actions, if exposed, require confirmation and danger styling.

References: `ANALYSIS.md` sections 10 and 11; `REFACTOR_PLAN.md` P0-05 and P1-02.

## 8. Import Design

Current decision: no Chat import implementation.

Design decisions:

- Do not create `Modules/Chat/Services/ImportExport.php`, import classes, or import UI during this refactor. Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P2-08.
- Chat messages are sensitive. Any future import must start with a separate confirmed data/security spec.

If future import is approved:

- Import classes:
  - `Modules/Chat/Services/ImportExport.php`
  - Optional `Modules/Chat/Import/ChatMessageImport.php`
  - Optional row mapper/normalizer/validator classes only if complexity warrants.
- Header mapping:
  - Needs confirmation before coding.
- Column mapping:
  - Needs confirmation before coding.
- Row normalization:
  - Trim strings, normalize dates, reject empty required message text.
- Row validation:
  - Validate session ownership, sender type, message length, timestamps, and no formula-derived trusted values.
- Duplicate handling:
  - Needs confirmation before coding; do not use spreadsheet `id` as unique key by default.
- Error reporting:
  - Must follow shared import/export report shape from `docs/AI_PROJECT_CONTEXT.md`.

References: `docs/AI_PROJECT_CONTEXT.md` Import Export Standard; `REFACTOR_PLAN.md` P2-08.

## 9. Export Design

Current decision: no Chat export implementation.

Design decisions:

- Do not add export during this refactor. Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P2-08.
- If export is later required, it must exclude or strictly authorize sensitive customer/internal message content.

If future export is approved:

- Export classes:
  - `Modules/Chat/Services/ImportExport.php`
  - Optional `Modules/Chat/Export/ChatExport.php`
- Query design:
  - Filter by authorized sessions only.
  - Use chunking/lazy iteration for large message sets.
  - Never export all chats without explicit permission and audit trail.
- Export mapping:
  - Default to safe fields only; exclude `metadata` unless approved.
  - Include redacted participant identifiers if required.
- Template generation:
  - Needs confirmation before coding.
- Large export strategy:
  - Queue large exports, store through shared export storage, and apply retention/authorization.

References: `docs/AI_PROJECT_CONTEXT.md` Export architecture; `REFACTOR_PLAN.md` P2-08.

## 10. Permissions and Authorization

Required permissions:

- `view_chat`: access support/internal chat pages.
- `create_chat`: send support messages or start widget conversations where applicable.
- `edit_chat`: claim sessions, update status, mark read/seen.
- `delete_chat`: delete messages.
- Needs confirmation before coding: whether internal chat needs separate permissions such as `view_internal_chat` and `send_internal_chat`.
- Socket room authorization is not a replacement for permissions; it is an additional realtime subscription guard.

Policy/Gate checks:

- Routes must require `auth:admin` plus named permissions for admin pages.
- Livewire actions must call authorization before mutating state or dispatching service operations.
- Services must enforce ownership/eligibility invariants and not trust Livewire inputs.

Livewire action protection:

- `ChatManager::selectSession`: require view/claim permission and service authorization.
- `ChatManager::send`: require send permission and session assignment/ownership.
- `ChatWidget::startChat` and `send`: verify token/session ownership through service.
- `InternalChatManager::selectUser` and `send`: verify recipient eligibility through service.
- Delete action: require `delete_chat` and session/message authorization.
- Socket join payload generation: require the same session/recipient authorization as the Livewire action that selected the room.

Route middleware:

- `Modules/Chat/routes/web.php`: `web`, `auth:admin`, and named permission middleware.
- `Modules/Chat/routes/api.php`: remove route or add auth/throttle/permission middleware after API requirement confirmation.

Socket authorization:

- `socket/server.js`: `/broadcast` requires non-empty `BRIDGE_SECRET_KEY`, matching request header, event allowlist, channel allowlist, and payload validation.
- `socket/events/chat.js`: `join-session`, `typing`, and `message-delivered` require the socket to be authorized for the target session room.
- `socket/events/internal-chat.js`: `admin-online` and `join-dm-room` require verified identity/room authorization.

References: `ANALYSIS.md` sections 2, 10, 16; `REFACTOR_PLAN.md` P0-01 through P0-12.

## 11. Transactions and Data Integrity

Actions requiring DB transactions:

- Support session creation/resolution by token. Reference: `REFACTOR_PLAN.md` P1-04.
- Admin session claiming/assignment. Reference: `REFACTOR_PLAN.md` P0-03 and P1-04.
- Support message send: create message and update session `last_message_at`/`status`. Reference: `REFACTOR_PLAN.md` P0-05 and P1-03.
- Message delete: delete message and dispatch post-commit notification. Reference: `REFACTOR_PLAN.md` P0-05 and P1-03.
- Internal message send. Reference: `REFACTOR_PLAN.md` P0-06 and P1-03.
- Mark read/seen if multiple rows update.

Rollback conditions:

- Invalid or unauthorized session/recipient.
- Invalid sender identity or sender type.
- Missing required persisted fields.
- Database persistence failure.

Do not roll back solely because NodeJS bridge broadcast fails unless this business rule is explicitly approved. Reference: `REFACTOR_PLAN.md` P1-03.

Idempotency concerns:

- `resolveWidgetSession` must handle repeated calls with the same token/user safely.
- `sendSupportMessage` and internal `sendMessage` may need client-generated idempotency keys if duplicate message sends are observed. Needs confirmation before coding.
- Realtime append handlers must ignore duplicate message IDs. Reference: `ANALYSIS.md` section 5.
- Socket reconnect must rejoin only rooms with still-valid signed room authorization, not stale `window.currentSessionId` alone. Reference: `ANALYSIS.md` section 16; `REFACTOR_PLAN.md` P0-10.

## 12. Performance Strategy

Eager loading:

- Support session list loads `user`, `admin`, and `latestMessage` as needed.
- Avoid `ChatSession::getDisplayNameAttribute` causing lazy relation loads in loops.

Query optimization:

- Move all session/message/user queries to services.
- Select only columns needed for list views.
- Add measured chronology indexes after query shape is finalized.

Pagination:

- Support session list: server-side pagination with default 10 or 25 and guarded `All`.
- Support messages: bounded chunks, e.g. latest 50 with `beforeId` pagination.
- Internal recipients: bounded/paginated list.
- Internal messages: bounded chunks.

Caching:

- Do not cache message streams by default.
- Recipient availability may be cached only if online-user source and invalidation are confirmed. Needs confirmation before coding.
- Socket presence currently uses process memory; this is acceptable only for confirmed single-process deployment. Needs confirmation before coding. Reference: `REFACTOR_PLAN.md` P1-16.

Socket performance and resilience:

- Add rate limits for `/broadcast`, `join-session`, `join-dm-room`, `typing`, and `admin-online`. Reference: `REFACTOR_PLAN.md` P1-14.
- Validate payloads before joining/emitting to reduce malformed event load. Reference: `REFACTOR_PLAN.md` P1-15.
- If multi-process deployment is required, use Redis/Socket.io adapter for rooms and presence. Reference: `REFACTOR_PLAN.md` P1-16.

References: `ANALYSIS.md` sections 13 and 16; `REFACTOR_PLAN.md` P1-05, P1-14, P1-15, P1-16, and P2-07.

## 13. Test Strategy

Route tests:

- `tests/Feature/Chat/ChatRouteConfigurationTest.php`: web route auth/permission checks, API route absent or protected, route names.
- References: `REFACTOR_PLAN.md` P0-01, P0-02, P2-01.

Livewire tests:

- `tests/Feature/Chat/ChatAuthorizationTest.php`: admin session view/claim/send/delete allowed and denied.
- `tests/Feature/Chat/ChatWidgetSecurityTest.php`: guest/user token ownership.
- `tests/Feature/Chat/InternalChatAuthorizationTest.php`: recipient eligibility.
- `tests/Feature/Chat/ChatValidationTest.php`: support message validation and malformed realtime payloads.
- `tests/Feature/Chat/InternalChatValidationTest.php`: internal message validation and `wire:model.live`.
- References: `REFACTOR_PLAN.md` P0-02 through P0-06, P1-02, P1-11.

Service tests:

- `tests/Unit/Chat/ChatServiceTest.php`: canonical model use, session resolution, claim race rules, send/delete authorization, transactions, bounded queries.
- `tests/Unit/Chat/InternalChatServiceTest.php`: recipient validation, bounded conversation, room naming, send transaction.
- `tests/Unit/Chat/ChatBridgeLoggingTest.php`: redacted logs.
- `tests/Unit/Chat/ChatBridgeConfigurationTest.php`: config-only bridge settings and missing secret behavior.
- Optional `tests/Unit/Chat/ChatBridgeServiceTest.php` if bridge service is extracted.
- References: `REFACTOR_PLAN.md` P0-03 through P0-08, P1-01, P1-03, P1-08, P1-12.

Import tests:

- None for this refactor because import is intentionally out of scope.
- If future import is approved: `tests/Feature/Chat/ChatImportExportTest.php`.
- Reference: `REFACTOR_PLAN.md` P2-08.

Export tests:

- None for this refactor because export is intentionally out of scope.
- Future export tests must cover authorization, sensitive-field exclusion, filters, chunking, and retention.
- Reference: `REFACTOR_PLAN.md` P2-08.

Authorization tests:

- Denied by default for admin without permission.
- Denied for forged session IDs/tokens.
- Denied for unauthorized internal recipients.
- Denied for message delete without `delete_chat`.
- References: `ANALYSIS.md` section 10; `REFACTOR_PLAN.md` P0-02 through P0-06.

Socket tests:

- Add socket test harness after confirmation: `tests/Socket/ChatSocketSecurityTest.*` or equivalent.
- Cover `/broadcast` rejection when `BRIDGE_SECRET_KEY` is missing or mismatched.
- Cover bridge event/channel allowlist rejection.
- Cover `join-session` rejection without valid signed room token.
- Cover `join-dm-room` rejection without valid signed room token.
- Cover `admin-online` spoofing rejection.
- Cover CORS/config behavior and generic error responses.
- Cover invalid payload rejection for `typing`, `message-delivered`, and broadcast events.
- References: `ANALYSIS.md` section 16; `REFACTOR_PLAN.md` P0-09 through P0-12 and P1-14 through P1-16.

Migration/model tests:

- `tests/Feature/Chat/ChatMigrationTest.php`: migration ownership/index smoke tests after migration work.
- `tests/Unit/Chat/ChatModelTest.php`: explicit table names, fillable, casts, relationships.
- References: `REFACTOR_PLAN.md` P1-06, P2-06, P2-07.

## 14. Implementation Checklist

### P0

- [ ] Resolve public API route: remove or protect `Modules/Chat/routes/api.php`. Reference: `REFACTOR_PLAN.md` P0-01.
- [ ] Add named permission checks to `Modules/Chat/routes/web.php` and Livewire actions. Reference: `REFACTOR_PLAN.md` P0-02.
- [ ] Move admin support session claim logic into `Modules/Chat/Services/ChatService.php`. Reference: `REFACTOR_PLAN.md` P0-03.
- [ ] Enforce widget token/session ownership in `Modules/Chat/Services/ChatService.php`. Reference: `REFACTOR_PLAN.md` P0-04.
- [ ] Derive sender identity server-side and protect message delete. Reference: `REFACTOR_PLAN.md` P0-05.
- [ ] Enforce internal chat recipient authorization. Reference: `REFACTOR_PLAN.md` P0-06.
- [ ] Migrate Chat module imports to `Modules/Chat/Models/ChatSession.php` and `Modules/Chat/Models/ChatMessage.php`. Reference: `REFACTOR_PLAN.md` P0-07.
- [ ] Redact bridge/message logging and stop logging raw payloads. Reference: `REFACTOR_PLAN.md` P0-08.
- [ ] Make `socket/server.js` bridge auth fail closed when `BRIDGE_SECRET_KEY` is missing or mismatched. Reference: `REFACTOR_PLAN.md` P0-09.
- [ ] Require signed/authorized socket room joins for support chat and internal DM rooms. Reference: `REFACTOR_PLAN.md` P0-10.
- [ ] Derive internal presence identity from verified socket context, not browser `user_id`. Reference: `REFACTOR_PLAN.md` P0-11.
- [ ] Add bridge event/channel allowlists and payload validation in `socket/server.js`. Reference: `REFACTOR_PLAN.md` P0-12.
- [ ] Add P0 route, authorization, widget ownership, and service tests. Reference: `REFACTOR_PLAN.md` P1-13.

### P1

- [ ] Move all Livewire read queries into `ChatService` and `InternalChatService`. Reference: `REFACTOR_PLAN.md` P1-01.
- [ ] Add Livewire and service validation rules for messages, sessions, recipients, and realtime payloads. Reference: `REFACTOR_PLAN.md` P1-02.
- [ ] Move bridge HTTP calls outside DB transactions. Reference: `REFACTOR_PLAN.md` P1-03.
- [ ] Add transaction/race handling for session creation and admin assignment. Reference: `REFACTOR_PLAN.md` P1-04.
- [ ] Add bounded queries, pagination/caps, and eager loading. Reference: `REFACTOR_PLAN.md` P1-05.
- [ ] Plan safe ownership migration for `internal_messages`. Reference: `REFACTOR_PLAN.md` P1-06.
- [ ] Confirm and document canonical user/admin model dependency. Reference: `REFACTOR_PLAN.md` P1-07.
- [ ] Move bridge settings to `config/services.php`. Reference: `REFACTOR_PLAN.md` P1-08.
- [ ] Extract duplicate page header/scroll behavior. Reference: `REFACTOR_PLAN.md` P1-09.
- [ ] Centralize socket/browser behavior if duplication remains. Reference: `REFACTOR_PLAN.md` P1-10.
- [ ] Standardize internal chat input to `wire:model.live`. Reference: `REFACTOR_PLAN.md` P1-11.
- [ ] Extract `ChatBridgeService` if shared bridge behavior remains. Reference: `REFACTOR_PLAN.md` P1-12.
- [ ] Harden socket CORS, error responses, logging, and throttling. Reference: `REFACTOR_PLAN.md` P1-14.
- [ ] Validate all socket event payloads and normalize IDs/rooms. Reference: `REFACTOR_PLAN.md` P1-15.
- [ ] Confirm single-process vs multi-process presence strategy. Reference: `REFACTOR_PLAN.md` P1-16.
- [ ] Add service, validation, transaction, migration, and query performance tests. Reference: `REFACTOR_PLAN.md` P1-13.

### P2

- [ ] Rename ambiguous route names after caller discovery. Reference: `REFACTOR_PLAN.md` P2-01.
- [ ] Normalize support page path under `resources/views/pages/chat`. Reference: `REFACTOR_PLAN.md` P2-02.
- [ ] Remove confirmed unused placeholder component. Reference: `REFACTOR_PLAN.md` P2-03.
- [ ] Remove or regenerate stale `Modules/Chat/structure.md`. Reference: `REFACTOR_PLAN.md` P2-04.
- [ ] Remove unused `InternalChatService::broadcastToNodeJS`. Reference: `REFACTOR_PLAN.md` P2-05.
- [ ] Add explicit `$table` declarations to Chat models. Reference: `REFACTOR_PLAN.md` P2-06.
- [ ] Add measured chat chronology indexes after query patterns are finalized. Reference: `REFACTOR_PLAN.md` P2-07.
- [ ] Keep import/export out of scope unless separately approved. Reference: `REFACTOR_PLAN.md` P2-08.
- [ ] Confirm website/public widget ownership before moving or deleting widget files. Reference: `REFACTOR_PLAN.md` P2-09.
- [ ] Remove duplicate socket debug listeners and production debug globals. Reference: `REFACTOR_PLAN.md` P2-10.
- [ ] Remove unused socket module parameters. Reference: `REFACTOR_PLAN.md` P2-11.
