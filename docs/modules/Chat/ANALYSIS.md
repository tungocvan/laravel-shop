# Chat Module Analysis

Analysis date: 2026-06-18

Scope: `Modules/Chat` only, with one noted external schema dependency for `internal_messages`.

## 1. Module Purpose

The `Chat` module provides two realtime messaging surfaces:

- Customer support chat between guests/users and admins.
- Internal direct chat between admins/users shown as online.

Current implementation uses Livewire components, browser-side Socket.io/Alpine scripts, and a NodeJS bridge endpoint configured by `NODEJS_SERVER_URL` / `BRIDGE_SECRET_KEY`.

## 2. Route List

Flow: Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Service -> Model -> Migration.

| Method | URI | Name | Middleware | Controller | Page Blade | Livewire |
|---|---|---|---|---|---|---|
| GET | `/admin/chat/internal-chat` | `admin.chat.index` | `web`, `auth:admin` | `Modules/Chat/Http/Controllers/ChatController.php::internalChat` | `Modules/Chat/resources/views/pages/chat/index.blade.php` | `chat.chat.internal-chat-manager` |
| GET | `/admin/chat` | `admin.chat.cskh` | `web`, `auth:admin` | `Modules/Chat/Http/Controllers/ChatController.php::chat` | `Modules/Chat/resources/views/chat.blade.php` | `chat.chat.chat-manager` |
| GET | `/api/chat` or module API prefix equivalent | unnamed | none in module file | `Modules/Chat/Http/Controllers/Api/ChatController.php::index` | none | none |

Issues:

- P0: `Modules/Chat/routes/api.php` exposes a public unauthenticated API group and maps `GET /chat` to `index`, but `Modules/Chat/Http/Controllers/Api/ChatController.php` has no `index` method.
- P1: `Modules/Chat/routes/web.php` protects admin pages only with `auth:admin`; it does not enforce the declared permissions from `Modules/Chat/config/module.php`.
- P2: `Modules/Chat/routes/web.php` uses `/admin/chat/internal-chat`; the route name `admin.chat.index` does not clearly distinguish internal chat from customer support chat.

## 3. Controllers

`Modules/Chat/Http/Controllers/ChatController.php`

- `internalChat()` returns `Chat::pages.chat.index`.
- `chat()` returns `Chat::chat`.
- Thin controller behavior is aligned with the target architecture.

`Modules/Chat/Http/Controllers/Api/ChatController.php`

- Empty placeholder controller.
- Has an unused `Illuminate\Http\Request` import.
- Does not implement the route target `index`.

Recommendations:

- P0: Remove, protect, or implement the public API route in `Modules/Chat/routes/api.php` and `Modules/Chat/Http/Controllers/Api/ChatController.php`.
- P1: Add named permission checks for `view_chat` or equivalent at the route/controller/Livewire boundary.
- P2: Remove unused imports from `Modules/Chat/Http/Controllers/Api/ChatController.php`.

## 4. Page Blade Files

`Modules/Chat/resources/views/chat.blade.php`

- Extends `Admin::layouts.master`.
- Mounts `@livewire('chat.chat.chat-manager')`.
- Contains inline page JavaScript for scrolling.

`Modules/Chat/resources/views/pages/chat/index.blade.php`

- Extends `Admin::layouts.master`.
- Comments out `chat.chat.chat-manager`.
- Mounts `@livewire('chat.chat.internal-chat-manager')`.
- Contains duplicate inline page JavaScript from `chat.blade.php`.

Issues:

- P1: Both page blades duplicate the same page header and scroll script in `Modules/Chat/resources/views/chat.blade.php` and `Modules/Chat/resources/views/pages/chat/index.blade.php`.
- P1: Both page blades include inline JavaScript instead of isolating shared behavior in a reusable asset/component.
- P2: `Modules/Chat/resources/views/chat.blade.php` sits at the module view root while the other page follows `resources/views/pages/chat/index.blade.php`, creating inconsistent page layout organization.

## 5. Livewire PHP Classes

`Modules/Chat/Livewire/Chat/ChatManager.php`

- Admin customer-support chat manager.
- Public state: `activeSessionId`, `message`, `messages`.
- Public methods: `selectSession`, `loadMessages`, `send`, `appendMessage`, computed `sessions`, computed `activeSession`, `render`.
- Directly queries `ChatSession` and `ChatMessage`.
- Imports `Modules\Admin\Models\ChatSession` and `Modules\Admin\Models\ChatMessage` instead of the module-local models.

`Modules/Chat/Livewire/Chat/ChatWidget.php`

- Floating public chat widget used by `resources/views/welcome.blade.php`.
- Public state: open/auth/chat state, session token/id, message list.
- Public methods: `mount`, `loadMessages`, `startChat`, `appendMessage`, `send`, `render`.
- Directly queries sessions and messages through relationships.
- Imports `Modules\Admin\Models\ChatSession` instead of `Modules\Chat\Models\ChatSession`.

`Modules/Chat/Livewire/Chat/InternalChatManager.php`

- Internal direct-message UI.
- Public state: selected user, message, messages, online users.
- Public methods: `selectUser`, `send`, `appendMessage`, `setOnlineUsers`, `roomName`, `render`.
- Directly queries `App\Models\User` in `selectUser` and `render`.
- Uses `InternalChatService` for message retrieval and sending.

Issues:

- P0: `Modules/Chat/Livewire/Chat/ChatManager.php` allows any authenticated admin to select any chat session ID and claim it by setting `admin_id`; there is no permission, assignment, ownership, or status check.
- P0: `Modules/Chat/Livewire/Chat/ChatWidget.php` trusts `session_token` from browser/session state and does not verify ownership when loading or sending chat messages.
- P0: `Modules/Chat/Livewire/Chat/InternalChatManager.php` allows selecting any online user ID supplied to the Livewire action without a permission or allowed-recipient check.
- P1: `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, and `Modules/Chat/Livewire/Chat/InternalChatManager.php` bypass the Service layer for reads.
- P1: `Modules/Chat/Livewire/Chat/ChatManager.php` and `Modules/Chat/Livewire/Chat/ChatWidget.php` depend on duplicate Admin-module chat models.
- P1: Message input validation is only manual `trim()` checks; there are no Livewire rules for length, required content, or allowed payload shape.
- P1: `Modules/Chat/Livewire/Chat/ChatManager.php::appendMessage` indexes `$message['chat_session_id']` and `$message['id']` without validating the decoded payload shape.
- P1: `Modules/Chat/Livewire/Chat/ChatWidget.php::appendMessage` indexes `$newMessage['id']`, `sender_type`, and `message` without payload validation.
- P1: `Modules/Chat/Livewire/Chat/InternalChatManager.php::appendMessage` indexes `$message['id']` without validating required keys.
- P2: `Modules/Chat/Livewire/Chat/ChatWidget.php` and `Modules/Chat/Services/InternalChatService.php` contain verbose debug logging that may be noisy in production.

## 6. Livewire Blade Views

`Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php`

- Renders session sidebar, active chat panel, message list, message composer, and browser Socket.io/Alpine behavior.
- Uses `wire:model.live="message"`.
- Iterates over `$this->sessions`, `$messages`, and `$this->activeSession`.

`Modules/Chat/resources/views/livewire/chat/chat-widget.blade.php`

- Renders a floating website chat widget.
- Uses Alpine state for open/close and Socket.io event handling.
- Uses `wire:click="startChat"` and `wire:model.live="message"`.

`Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php`

- Renders online user list, direct-message panel, and browser Socket.io behavior.
- Uses `wire:model="message"` rather than the default `wire:model.live`.
- Uses users passed by the Livewire render method.

Issues:

- P1: `Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php` pulls computed properties from Livewire that run direct model queries in `Modules/Chat/Livewire/Chat/ChatManager.php`.
- P1: `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php` uses `wire:model="message"` instead of the project default `wire:model.live`.
- P1: Socket scripts and UI behavior are duplicated across `Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php`, `Modules/Chat/resources/views/livewire/chat/chat-widget.blade.php`, and `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php`.
- P2: The Livewire blades are large, script-heavy templates; extracting reusable Blade components or assets would improve maintainability after the security/service boundaries are fixed.

## 7. Services And Public Methods

`Modules/Chat/Services/ChatService.php`

- `getOrCreateSession(string $token, array $guestData = []): ChatSession`
- `sendMessage(array $data): ChatMessage`
- `deleteMessage($messageId): bool`
- Protected `broadcastToNodeJS(array $payload): void`

Concerns:

- P0: Uses `Modules\Admin\Models\ChatSession` and `Modules\Admin\Models\ChatMessage`, not the canonical `Modules/Chat/Models` classes.
- P0: `sendMessage()` accepts caller-provided `sender_id`, `sender_type`, and session ID without enforcing that the caller may write to that session.
- P0: `deleteMessage()` deletes by message ID without authorization, ownership, or permission checks.
- P1: `sendMessage()` opens a database transaction and performs an outbound HTTP request to NodeJS inside the transaction, increasing lock time and coupling database success to network latency.
- P1: `getOrCreateSession()` writes session records without a transaction even though token collision/race behavior matters for chat creation.
- P1: `broadcastToNodeJS()` reads `env()` directly instead of relying only on config, and logs raw bridge response body.

`Modules/Chat/Services/InternalChatService.php`

- `getMessages(int $userId)`
- `sendMessage(int $toUserId, string $message)`
- `makeRoom($a, $b): string`
- Protected `broadcast(array $payload): void`
- Protected unused `broadcastToNodeJS(array $payload): void`

Concerns:

- P0: `sendMessage()` accepts any `$toUserId` and does not confirm the current admin may message that recipient.
- P1: `sendMessage()` writes a message and broadcasts without a transaction or post-commit dispatch boundary.
- P1: `getMessages()` returns an unpaginated full conversation.
- P1: `broadcast()` logs full payloads and NodeJS response bodies, which can expose message content.
- P2: `broadcastToNodeJS()` appears unused because `sendMessage()` calls `broadcast()`.

## 8. Models And Database Tables

`Modules/Chat/Models/ChatSession.php`

- Table by convention: `chat_sessions`.
- Fillable: `session_token`, `user_id`, `admin_id`, guest fields, `status`, `last_message_at`.
- Casts: `last_message_at` datetime.
- Relationships: `messages`, `latestMessage`, `user`, `admin`.
- Uses `App\Models\User` for both user and admin.

`Modules/Chat/Models/ChatMessage.php`

- Table by convention: `chat_messages`.
- Fillable: `chat_session_id`, `sender_id`, `sender_type`, `message`, `is_read`, `metadata`.
- Casts: `is_read` boolean, `metadata` array.
- Relationship: `session`.

`Modules/Chat/Models/InternalMessage.php`

- Table by convention: `internal_messages`.
- Fillable: `from_id`, `to_id`, `message`, `seen_at`.
- Casts: `seen_at` datetime.
- Relationships: `fromUser`, `toUser`.

Tables:

- `chat_sessions`: created by `Modules/Chat/database/migrations/-0001_11_30_000041_create_chat_sessions_table.php`.
- `chat_messages`: created by `Modules/Chat/database/migrations/-0001_11_30_000042_create_chat_messages_table.php`.
- `internal_messages`: used by `Modules/Chat/Models/InternalMessage.php` but created outside the module in `database/migrations/2026_05_10_151034_internal_messages.php`.

Issues:

- P0: Duplicate canonical ownership exists: `Modules/Admin/Models/ChatSession.php` and `Modules/Admin/Models/ChatMessage.php` duplicate `Modules/Chat/Models/ChatSession.php` and `Modules/Chat/Models/ChatMessage.php`.
- P1: `Modules/Chat/Models/InternalMessage.php` depends on a root migration outside `Modules/Chat`, which breaks module ownership.
- P1: `Modules/Chat/Models/ChatSession.php::getDisplayNameAttribute` may trigger N+1 queries if `admin` or `user` are not eager loaded.
- P1: `Modules/Chat/Models/InternalMessage.php` uses `App\Models\User`, while the project has a User module; canonical user/admin ownership should be confirmed before refactor.
- P2: The model `$table` names are implicit; explicit `$table` declarations would make duplicated model ownership easier to audit.

## 9. Import/Export Classes

No import/export classes or `Modules/Chat/Services/ImportExport.php` exist in `Modules/Chat`.

Current module behavior does not expose import/export UI.

Recommendation:

- P2: Do not add import/export unless there is a confirmed business requirement. If added later, follow the shared `Modules/Shared/Services/ImportExport` foundation and pass `serviceClass` to the shared panel.

## 10. Authorization And Security Risks

- P0: `Modules/Chat/routes/api.php` exposes an unauthenticated public API route to an unimplemented controller action.
- P0: `Modules/Chat/routes/web.php` relies only on `auth:admin`; it does not enforce `view_chat`, `create_chat`, `edit_chat`, or `delete_chat` from `Modules/Chat/config/module.php`.
- P0: `Modules/Chat/Livewire/Chat/ChatManager.php` mutates `admin_id` in `selectSession()` without authorization or assignment rules.
- P0: `Modules/Chat/Services/ChatService.php::sendMessage` trusts caller-provided `sender_type` and `sender_id`.
- P0: `Modules/Chat/Services/ChatService.php::deleteMessage` can delete any message by ID if called.
- P0: `Modules/Chat/Livewire/Chat/ChatWidget.php` relies on a client/session token for access to messages.
- P0: `Modules/Chat/Livewire/Chat/InternalChatManager.php` and `Modules/Chat/Services/InternalChatService.php` do not restrict who can message whom.
- P1: `Modules/Chat/Services/ChatService.php` and `Modules/Chat/Services/InternalChatService.php` log bridge failures/responses and payloads that may contain chat content.
- P1: Bridge secret is pulled with `env('BRIDGE_SECRET_KEY')` in service code; fail-closed behavior is not visible when the secret is missing.

## 11. Validation Problems

- P1: `Modules/Chat/Livewire/Chat/ChatManager.php::send` validates message only with `trim()` and no max length.
- P1: `Modules/Chat/Livewire/Chat/ChatWidget.php::send` validates message only with `trim()` and no max length.
- P1: `Modules/Chat/Livewire/Chat/InternalChatManager.php::send` validates message only with `trim()` and no max length.
- P1: `Modules/Chat/Services/ChatService.php::sendMessage` does not validate required keys, allowed `sender_type`, message length, or session ownership.
- P1: `Modules/Chat/Services/InternalChatService.php::sendMessage` does not validate `$toUserId` existence/eligibility or message length.
- P1: Realtime payload handlers in all three Livewire classes do not validate decoded event structure before array access.
- P2: Guest fields in `Modules/Chat/Services/ChatService.php::getOrCreateSession` are mass-merged from `$guestData` without service-level field validation.

## 12. Transaction Risks

- P1: `Modules/Chat/Services/ChatService.php::sendMessage` performs an HTTP broadcast inside a `DB::transaction`, which can hold database locks during network failure/latency.
- P1: `Modules/Chat/Services/ChatService.php::deleteMessage` performs an HTTP broadcast inside a `DB::transaction`.
- P1: `Modules/Chat/Services/InternalChatService.php::sendMessage` creates a message and broadcasts without transaction/post-commit separation.
- P1: `Modules/Chat/Livewire/Chat/ChatManager.php::selectSession` updates `admin_id` outside a service transaction and may race when multiple admins select the same session.
- P1: `Modules/Chat/Services/ChatService.php::getOrCreateSession` can race on `session_token` under concurrent requests.

## 13. N+1 And Query Performance Risks

- P1: `Modules/Chat/Livewire/Chat/ChatManager.php::getSessionsProperty` loads every chat session with `get()` and no pagination or cap.
- P1: `Modules/Chat/Livewire/Chat/ChatManager.php::getActiveSessionProperty` performs `ChatSession::find()` separately from the sessions query.
- P1: `Modules/Chat/Livewire/Chat/ChatWidget.php::loadMessages` loads all messages for a session.
- P1: `Modules/Chat/Livewire/Chat/ChatWidget.php::render` may reload all messages when the in-memory message array is empty.
- P1: `Modules/Chat/Services/InternalChatService.php::getMessages` loads full direct-message history with `get()`.
- P1: `Modules/Chat/Livewire/Chat/InternalChatManager.php::render` queries users on every render and uses the full `onlineUsers` client-fed array in `whereIn`.
- P1: `Modules/Chat/Models/ChatSession.php::getDisplayNameAttribute` can lazy-load `admin`/`user` when not eager loaded.
- P2: `Modules/Chat/database/migrations/-0001_11_30_000042_create_chat_messages_table.php` has no composite index for common session chronology query `(chat_session_id, created_at)` or `(chat_session_id, id)`.
- P2: `database/migrations/2026_05_10_151034_internal_messages.php` has `from_id,to_id` index but not the reverse or a room-normalized index for bidirectional message lookups.

## 14. Duplicate Logic

- P0: `Modules/Chat/Models/ChatSession.php` and `Modules/Admin/Models/ChatSession.php` are duplicate model definitions for the same table.
- P0: `Modules/Chat/Models/ChatMessage.php` and `Modules/Admin/Models/ChatMessage.php` are duplicate model definitions for the same table.
- P1: `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, and `Modules/Chat/Services/ChatService.php` duplicate session lookup and message loading responsibilities.
- P1: `Modules/Chat/resources/views/chat.blade.php` and `Modules/Chat/resources/views/pages/chat/index.blade.php` duplicate page header and scroll script.
- P1: NodeJS bridge logic is duplicated in `Modules/Chat/Services/ChatService.php` and `Modules/Chat/Services/InternalChatService.php`.
- P2: `Modules/Chat/Services/InternalChatService.php` contains both `broadcast()` and an apparently unused `broadcastToNodeJS()` method.

## 15. Files That Look Unused

- P0: `Modules/Chat/Http/Controllers/Api/ChatController.php` is empty while `Modules/Chat/routes/api.php` routes to a missing `index` method.
- P2: `Modules/Chat/resources/views/components/placeholder.blade.php` is not referenced by the module routes/views found during analysis.
- P2: `Modules/Chat/Services/InternalChatService.php::broadcastToNodeJS` appears unused.
- P2: `Modules/Chat/structure.md` appears to be generated documentation; it references copy files that are not present in the current filesystem and may be stale.
- P2: `Modules/Chat/Livewire/Chat/ChatWidget.php` is not mounted by a page inside `Modules/Chat`, but it is referenced by root `resources/views/welcome.blade.php`; confirm ownership before removal.

## 16. Socket / Realtime Layer

Files analyzed:

- `socket/echo.js`
- `socket/server.js`
- `socket/events/chat.js`
- `socket/events/internal-chat.js`

Current flow:

- Browser initializes a global Socket.io client in `socket/echo.js`.
- Node server starts in `socket/server.js`.
- Support chat room behavior lives in `socket/events/chat.js`.
- Internal direct-message room and online-admin behavior lives in `socket/events/internal-chat.js`.
- Laravel services call Node `POST /broadcast` with `X-Bridge-Secret`; Node emits the event to a room or globally.

Issues:

- P0: `socket/server.js` bridge auth can fail open when `process.env.BRIDGE_SECRET_KEY` is missing because a missing request header and missing env value both compare as `undefined`.
- P0: `socket/events/chat.js` allows any connected socket to emit `join-session` with any `sessionId`, so room membership is controlled by browser-supplied session IDs.
- P0: `socket/events/internal-chat.js` allows any connected socket to emit `admin-online` with any `user_id`, so online presence can be spoofed from the browser.
- P0: `socket/events/internal-chat.js` allows any connected socket to emit `join-dm-room` with any room string, so internal DM room membership is controlled by browser-supplied data.
- P0: `socket/server.js` accepts arbitrary `event`, `channel`, and `data` from `/broadcast` after bridge auth; there is no event/channel allowlist or payload shape validation.
- P1: `socket/server.js` uses `origin: process.env.APP_URL || "*"`, which permits all origins when `APP_URL` is not configured.
- P1: `socket/server.js` returns `err.message` from `/broadcast` failures, which can leak internal error details.
- P1: `socket/echo.js`, `socket/server.js`, `socket/events/chat.js`, and `socket/events/internal-chat.js` contain verbose debug logging of events, rooms, socket IDs, and payloads.
- P1: `socket/echo.js` registers `socket.onAny` twice, creating duplicate debug listeners.
- P1: `socket/events/chat.js` typing and message-delivered events trust client-provided `session_id` and do not verify that the socket is authorized for the room.
- P1: `socket/events/internal-chat.js` stores online admins in an in-memory `Map`, so presence is lost on server restart and is not multi-process safe.
- P1: `socket/server.js` has no request rate limiting on `/broadcast`, and socket event handlers have no per-socket throttling beyond a local typing timeout.
- P2: `socket/echo.js` exports global functions/state on `window` (`window.socket`, `window.currentSessionId`, `window.joinSession`, `window.leaveSession`, `window.debugSocket`) without a clear lifecycle cleanup strategy.
- P2: `socket/events/chat.js` receives `bridgeAuth` and `app` from `socket/server.js` but does not use them.

Recommendations:

- P0: Make bridge auth fail closed in `socket/server.js` when `BRIDGE_SECRET_KEY` is absent or blank.
- P0: Add server-side socket authentication or signed room tokens before allowing `join-session`, `admin-online`, or `join-dm-room`.
- P0: Add an allowlist for bridge events and channel patterns in `socket/server.js`.
- P1: Require explicit CORS origin configuration in `socket/server.js`; do not default to `"*"` in production.
- P1: Redact and gate debug logging in all socket files.
- P1: Validate payload shape for every socket event and bridge event before emit/join.
- P1: Add rate limits/throttles for `/broadcast`, `join-session`, `join-dm-room`, `typing`, and `admin-online`.
- P2: Remove duplicate `onAny` listener and unused injected parameters.

## 17. Refactor Plan

### P0 Critical

- P0: Secure or remove the public API route in `Modules/Chat/routes/api.php` and fix `Modules/Chat/Http/Controllers/Api/ChatController.php` so no route points to a missing public action.
- P0: Enforce named chat permissions in `Modules/Chat/routes/web.php`, `Modules/Chat/Http/Controllers/ChatController.php`, and all mutating Livewire actions in `Modules/Chat/Livewire/Chat`.
- P0: Choose `Modules/Chat/Models/ChatSession.php` and `Modules/Chat/Models/ChatMessage.php` as the canonical chat models, then migrate `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Livewire/Chat/ChatManager.php`, and `Modules/Chat/Livewire/Chat/ChatWidget.php` away from `Modules\Admin\Models`.
- P0: Add server-side ownership checks for chat session reads/writes in `Modules/Chat/Services/ChatService.php` and Livewire callers.
- P0: Prevent arbitrary message deletion in `Modules/Chat/Services/ChatService.php::deleteMessage` by requiring permission and session ownership.
- P0: Add recipient authorization for internal messaging in `Modules/Chat/Livewire/Chat/InternalChatManager.php` and `Modules/Chat/Services/InternalChatService.php`.
- P0: Stop logging full message payloads and bridge response bodies in `Modules/Chat/Services/ChatService.php` and `Modules/Chat/Services/InternalChatService.php`.
- P0: Harden socket bridge and room authorization in `socket/server.js`, `socket/events/chat.js`, and `socket/events/internal-chat.js`.

### P1 Important

- P1: Move all query logic from `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, and `Modules/Chat/Livewire/Chat/InternalChatManager.php` into `Modules/Chat/Services/ChatService.php` or `Modules/Chat/Services/InternalChatService.php`.
- P1: Add Livewire validation rules for message content, selected session ID, and selected recipient ID in all three Livewire classes.
- P1: Validate service-level invariants in `Modules/Chat/Services/ChatService.php` and `Modules/Chat/Services/InternalChatService.php`, including allowed statuses, sender types, recipient existence, and token/session ownership.
- P1: Move HTTP broadcasting out of database transactions in `Modules/Chat/Services/ChatService.php`; dispatch after commit or use a queue/event boundary.
- P1: Add transaction/race handling for session creation and admin assignment in `Modules/Chat/Services/ChatService.php`.
- P1: Paginate or cap session and message queries in `Modules/Chat/Services/ChatService.php` and `Modules/Chat/Services/InternalChatService.php`.
- P1: Move `internal_messages` migration ownership from `database/migrations/2026_05_10_151034_internal_messages.php` into the Chat module through a planned migration hygiene task.
- P1: Replace duplicate page scripts in `Modules/Chat/resources/views/chat.blade.php` and `Modules/Chat/resources/views/pages/chat/index.blade.php` with a shared asset or component.
- P1: Add feature/Livewire tests for auth denial, permission denial, session ownership, message send, delete denial, and internal chat recipient restrictions.
- P1: Add socket payload validation, CORS hardening, rate limiting, and redacted logging for `socket/echo.js`, `socket/server.js`, `socket/events/chat.js`, and `socket/events/internal-chat.js`.

### P2 Nice To Have

- P2: Normalize page view placement so customer support chat uses `Modules/Chat/resources/views/pages/chat/...` consistently.
- P2: Remove or repurpose `Modules/Chat/resources/views/components/placeholder.blade.php` after confirming it is unused.
- P2: Remove stale `Modules/Chat/structure.md` or regenerate it as part of a documented architecture catalog.
- P2: Remove unused `Modules/Chat/Services/InternalChatService.php::broadcastToNodeJS`.
- P2: Add explicit `$table` declarations to `Modules/Chat/Models/ChatSession.php`, `Modules/Chat/Models/ChatMessage.php`, and `Modules/Chat/Models/InternalMessage.php` for audit clarity.
- P2: Add query-supporting indexes for chat chronology after service query patterns are finalized.
- P2: Extract large script-heavy Livewire Blade sections into maintainable assets/components once behavior and permissions are stabilized.
- P2: Remove duplicate socket debug listeners, unused socket module parameters, and unmanaged global debug helpers after socket authorization is stable.
