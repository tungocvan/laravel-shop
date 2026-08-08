# Chat Refactor Plan

Created: 2026-06-18

Source documents:

- `docs/modules/Chat/ANALYSIS.md`
- `docs/CODEX_BOOTSTRAP.md`
- `docs/AI_PROJECT_CONTEXT.md`
- `ROADMAP.md`

Scope: planning only. No implementation code is included in this document.

## 1. Executive Summary

The `Chat` module currently mixes customer support chat and internal direct messaging. The highest-risk problems are security and ownership gaps: a public unauthenticated API route points to a missing controller action, admin routes rely only on `auth:admin`, Livewire actions trust browser-supplied IDs/tokens, and message writes/deletes do not enforce record-level authorization.

Architecturally, the module also violates the Laravel 12 and Livewire 3 standards in the repository guidance. Livewire classes query models directly, business rules are split between Livewire and services, the Chat module uses duplicate `Modules\Admin\Models` for chat tables, and the `internal_messages` schema lives outside `Modules/Chat`.

The recommended approach is security-first:

1. Close public and mutating entry-point risks.
2. Establish `Modules/Chat` as the canonical owner of chat models and services.
3. Move query and business logic into services with validation, transactions, and bounded queries.
4. Clean up duplicate UI/scripts, stale files, and naming only after behavior is protected by tests.

## 2. P0 Critical Fixes

### P0-01: Public unauthenticated API route points to missing action

* Issue: `Modules/Chat/routes/api.php` exposes a public `GET /chat` route to `ChatController::index`, but `Modules/Chat/Http/Controllers/Api/ChatController.php` has no `index` method.
* Root Cause: Scaffolded API route was left enabled while the API controller remained a placeholder.
* Business Impact: Public users can hit a broken endpoint; depending on exception handling, this can expose stack traces, confuse integrations, and create avoidable attack surface.
* Technical Impact: Route boot or request handling can fail with a missing method error, and there is no authentication, throttling, or response contract.
* Proposed Solution: Decide whether Chat needs an API endpoint. If not, remove or disable the route in `Modules/Chat/routes/api.php`. If it is required, implement a thin authenticated controller action in `Modules/Chat/Http/Controllers/Api/ChatController.php` that delegates to `Modules/Chat/Services/ChatService.php`, with route middleware and tests.
* Files To Change: `Modules/Chat/routes/api.php`, `Modules/Chat/Http/Controllers/Api/ChatController.php`, `tests/Feature/Chat/ChatRouteConfigurationTest.php`.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: No public route points to a missing method; unauthenticated access is denied or the route is absent; route tests cover the chosen behavior.

### P0-02: Admin chat access lacks named permissions

* Issue: `Modules/Chat/routes/web.php` uses only `web` and `auth:admin`; it does not enforce `view_chat`, `create_chat`, `edit_chat`, or `delete_chat` from `Modules/Chat/config/module.php`.
* Root Cause: Authentication was treated as sufficient authorization.
* Business Impact: Any authenticated admin can view or interact with chat features, including customer conversations and internal messaging.
* Technical Impact: The module does not satisfy roadmap P0-01/P0-05 or the repository security boundary for privileged features.
* Proposed Solution: Add route/controller/Livewire permission gates using the project's existing permission convention. At minimum, require `view_chat` for page access and action-specific permissions for sends, claims, deletes, and internal messaging.
* Files To Change: `Modules/Chat/routes/web.php`, `Modules/Chat/Http/Controllers/ChatController.php`, `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/Livewire/Chat/InternalChatManager.php`, `Modules/Chat/config/module.php`, `tests/Feature/Chat/ChatAuthorizationTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Admin chat routes deny admins without named permissions; Livewire mutating actions deny unauthorized users; tests prove allowed and denied behavior.

### P0-03: Admins can claim arbitrary support sessions

* Issue: `Modules/Chat/Livewire/Chat/ChatManager.php::selectSession` accepts any session ID and updates `admin_id` without permission, ownership, status, or concurrency checks.
* Root Cause: Session assignment logic is implemented directly in Livewire and trusts browser-supplied IDs.
* Business Impact: One admin can take over another admin's customer conversation or view sessions they should not access.
* Technical Impact: Race conditions can overwrite `admin_id`; business rules are outside the service layer.
* Proposed Solution: Move session selection/claiming to `Modules/Chat/Services/ChatService.php`. Enforce `view_chat` and assignment permission, validate that the session exists and is claimable, use a transaction with row locking where needed, and return safe state for Livewire.
* Files To Change: `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Models/ChatSession.php`, `tests/Unit/Chat/ChatServiceTest.php`, `tests/Feature/Chat/ChatAuthorizationTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 to 1.5 days.
* Acceptance Criteria: A session can only be selected or claimed by an authorized admin; concurrent claims do not silently overwrite ownership; Livewire no longer updates `admin_id` directly.

### P0-04: Chat widget trusts browser/session token for message access

* Issue: `Modules/Chat/Livewire/Chat/ChatWidget.php` loads and sends messages based on `session_token` from session/client state without robust ownership checks.
* Root Cause: Token possession is used as the only access control.
* Business Impact: Token fixation or leakage can expose customer conversations.
* Technical Impact: Message reads and writes can be performed against sessions without service-level authorization.
* Proposed Solution: Centralize token/session resolution in `Modules/Chat/Services/ChatService.php`. Bind guest sessions to the server session where applicable, bind authenticated sessions to the authenticated user, and verify ownership before loading or sending messages.
* Files To Change: `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Models/ChatSession.php`, `tests/Unit/Chat/ChatServiceTest.php`, `tests/Feature/Chat/ChatWidgetSecurityTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 to 1.5 days.
* Acceptance Criteria: A user or guest can only read/write their own chat session; forged or mismatched tokens are rejected; tests cover guest and authenticated cases.

### P0-05: Message send and delete trust caller-provided identity

* Issue: `Modules/Chat/Services/ChatService.php::sendMessage` accepts `sender_id`, `sender_type`, and session ID from callers, and `deleteMessage` deletes by message ID without permission or ownership checks.
* Root Cause: The service persists caller-provided fields without deriving identity from the authenticated context or enforcing domain invariants.
* Business Impact: Callers can impersonate admins/users or delete messages they do not own.
* Technical Impact: Service is not a secure boundary for non-HTTP callers and cannot be safely reused.
* Proposed Solution: Make `Modules/Chat/Services/ChatService.php` derive sender identity from the current guard/context or explicit trusted service parameters. Add authorization checks for session access and deletion. Require delete confirmation and permission for admin delete flows.
* Files To Change: `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/Models/ChatMessage.php`, `tests/Unit/Chat/ChatServiceTest.php`, `tests/Feature/Chat/ChatAuthorizationTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1.5 days.
* Acceptance Criteria: Sender identity cannot be spoofed from Livewire payloads; unauthorized deletes fail; authorized sends/deletes are covered by positive and negative tests.

### P0-06: Internal chat recipient access is unrestricted

* Issue: `Modules/Chat/Livewire/Chat/InternalChatManager.php` accepts any selected online user ID, and `Modules/Chat/Services/InternalChatService.php::sendMessage` accepts any `$toUserId`.
* Root Cause: Recipient eligibility is checked in UI state only, not on the server.
* Business Impact: Admins may message users outside their allowed scope or expose internal communication channels.
* Technical Impact: Livewire action inputs are trusted, and service methods are not reusable outside the UI.
* Proposed Solution: Define recipient eligibility rules and enforce them in `Modules/Chat/Services/InternalChatService.php`. Livewire should pass validated scalar IDs, and the service should verify the recipient exists, is allowed, and is not the sender.
* Files To Change: `Modules/Chat/Livewire/Chat/InternalChatManager.php`, `Modules/Chat/Services/InternalChatService.php`, `Modules/Chat/Models/InternalMessage.php`, `tests/Unit/Chat/InternalChatServiceTest.php`, `tests/Feature/Chat/InternalChatAuthorizationTest.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Unauthorized recipient IDs are rejected server-side; users cannot message themselves unless explicitly allowed; tests cover invalid, unauthorized, and valid recipients.

### P0-07: Duplicate canonical ownership of chat models

* Issue: `Modules/Chat/Models/ChatSession.php` and `Modules/Chat/Models/ChatMessage.php` duplicate `Modules/Admin/Models/ChatSession.php` and `Modules/Admin/Models/ChatMessage.php`, while Chat services and Livewire classes import the Admin models.
* Root Cause: Admin presentation code was allowed to own domain models that belong in the Chat module.
* Business Impact: Changes to chat behavior can diverge between modules and cause inconsistent customer support behavior.
* Technical Impact: Violates module ownership, complicates authorization, and increases regression risk.
* Proposed Solution: Establish `Modules/Chat/Models/ChatSession.php` and `Modules/Chat/Models/ChatMessage.php` as canonical. Migrate Chat module imports first. Then plan Admin callers separately before deleting Admin duplicates.
* Files To Change: `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/Models/ChatSession.php`, `Modules/Chat/Models/ChatMessage.php`, `Modules/Admin/Models/ChatSession.php`, `Modules/Admin/Models/ChatMessage.php`, `tests/Feature/Chat/ChatModelOwnershipTest.php`.
* Risk Level: Critical.
* Complexity: High.
* Estimated Effort: 2 to 3 days for Chat migration; Admin duplicate removal should be a later cross-module task.
* Acceptance Criteria: All `Modules/Chat` code imports Chat models; duplicate Admin models are documented as pending removal or replaced only after Admin callers are migrated; architecture tests catch new Chat-to-Admin model dependencies.

### P0-08: Sensitive chat payloads and bridge responses are logged

* Issue: `Modules/Chat/Services/ChatService.php` and `Modules/Chat/Services/InternalChatService.php` log raw message payloads, bridge failures, and response bodies that may contain personal chat content.
* Root Cause: Debug logging remained in service code.
* Business Impact: Customer or internal chat messages can leak into logs.
* Technical Impact: Violates roadmap P0 secret/personal-data handling and makes logs unsafe to share.
* Proposed Solution: Replace raw payload logging with structured, redacted logs containing event type, session/message IDs, status codes, and correlation IDs only. Fail closed when bridge configuration is missing.
* Files To Change: `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `config/services.php`, `tests/Unit/Chat/ChatBridgeLoggingTest.php`.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.5 to 1 day.
* Acceptance Criteria: Logs do not contain message bodies, bridge secrets, or raw response bodies; missing bridge secret does not silently proceed in production-sensitive flows; tests or log assertions cover redaction.

### P0-09: Socket bridge can fail open when bridge secret is missing

* Issue: `socket/server.js` compares `req.headers["x-bridge-secret"]` to `process.env.BRIDGE_SECRET_KEY`; if both are missing, the comparison can pass.
* Root Cause: Bridge auth does not explicitly require a non-empty configured secret before accepting requests.
* Business Impact: A misconfigured production server can expose the broadcast endpoint to unauthenticated requests.
* Technical Impact: Any caller may emit realtime events into rooms or globally through `/broadcast` if the secret is absent.
* Proposed Solution: Make `socket/server.js` fail closed when `BRIDGE_SECRET_KEY` is absent or blank, and return a generic unauthorized response. Mirror the same fail-closed expectation in Laravel bridge config from `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, and `config/services.php`.
* Files To Change: `socket/server.js`, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `config/services.php`, `tests/Unit/Chat/ChatBridgeConfigurationTest.php`, socket integration test file Needs confirmation before coding.
* Risk Level: Critical.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: `/broadcast` rejects requests when the bridge secret is missing or mismatched; Laravel bridge services do not attempt unsafe broadcast when required config is absent.

### P0-10: Socket room membership trusts browser-supplied IDs and room strings

* Issue: `socket/events/chat.js` allows `join-session` with any `sessionId`, and `socket/events/internal-chat.js` allows `join-dm-room` with any room string.
* Root Cause: Socket event handlers treat client-provided room identifiers as authorization.
* Business Impact: A user with socket access can subscribe to another customer's support session or an internal DM room if they know or guess the identifier.
* Technical Impact: Laravel-side session ownership checks can be bypassed at the realtime subscription layer.
* Proposed Solution: Add server-side socket authentication and signed room-join tokens generated by Laravel services. `socket/events/chat.js` must verify the token before joining `session-*`; `socket/events/internal-chat.js` must verify the token before joining `dm-*`.
* Files To Change: `socket/events/chat.js`, `socket/events/internal-chat.js`, `socket/server.js`, `socket/echo.js`, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/Livewire/Chat/InternalChatManager.php`, socket integration test file Needs confirmation before coding.
* Risk Level: Critical.
* Complexity: High.
* Estimated Effort: 2 to 3 days.
* Acceptance Criteria: A socket cannot join a support or DM room without a valid server-issued token; forged session IDs and forged room names are rejected; reconnect flow rejoins only authorized rooms.

### P0-11: Internal online presence can be spoofed

* Issue: `socket/events/internal-chat.js` accepts `admin-online` with browser-supplied `user_id` and broadcasts the online admin list.
* Root Cause: Presence identity is not derived from authenticated socket state or a signed token.
* Business Impact: Users can impersonate other admins as online and influence recipient lists in `Modules/Chat/Livewire/Chat/InternalChatManager.php`.
* Technical Impact: Presence state cannot be trusted for authorization or UI decisions.
* Proposed Solution: Authenticate socket identity before accepting `admin-online`; derive user/admin ID from the verified socket context or signed presence token, not from raw payload.
* Files To Change: `socket/events/internal-chat.js`, `socket/server.js`, `socket/echo.js`, `Modules/Chat/Livewire/Chat/InternalChatManager.php`, `Modules/Chat/Services/InternalChatService.php`, socket integration test file Needs confirmation before coding.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 to 2 days.
* Acceptance Criteria: Browser-supplied `user_id` cannot spoof presence; online list contains only verified identities; internal chat recipient filtering remains server-authoritative.

### P0-12: Bridge endpoint accepts arbitrary events and channels

* Issue: `socket/server.js` accepts any `event`, `channel`, and `data` on `POST /broadcast` after bridge auth.
* Root Cause: There is no allowlist for event names, channel patterns, or payload shape.
* Business Impact: A compromised or buggy Laravel caller can emit unexpected realtime events or broadcast globally.
* Technical Impact: Frontend listeners may process malformed or malicious event payloads.
* Proposed Solution: Add explicit allowlists for supported events (`MessageSent`, `MessageDeleted`, `InternalMessageSent`, and confirmed typing/read events), channel patterns (`session-<id>`, `dm-<id>-<id>`), and required payload fields. Reject global broadcast for chat unless explicitly required.
* Files To Change: `socket/server.js`, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `tests/Unit/Chat/ChatBridgeServiceTest.php`, socket integration test file Needs confirmation before coding.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Unsupported events/channels are rejected; malformed payloads are rejected; global broadcast is not used by Chat unless explicitly approved.

## 3. P1 Important Refactors

### P1-01: Livewire classes bypass the service layer for reads

* Issue: `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, and `Modules/Chat/Livewire/Chat/InternalChatManager.php` query models directly.
* Root Cause: Read behavior was implemented in Livewire computed properties and lifecycle/actions instead of services.
* Business Impact: Authorization and filtering rules can drift between UI actions.
* Technical Impact: Violates Livewire 3 architecture in `docs/CODEX_BOOTSTRAP.md`; makes query optimization and tests harder.
* Proposed Solution: Add service read methods for sessions, active session, session messages, widget messages, online recipients, and internal messages. Livewire should own UI state and call services only.
* Files To Change: `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/Livewire/Chat/InternalChatManager.php`, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `tests/Unit/Chat/ChatServiceTest.php`, `tests/Unit/Chat/InternalChatServiceTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 2 days.
* Acceptance Criteria: No direct `ChatSession`, `ChatMessage`, `InternalMessage`, or `User` query remains in Chat Livewire classes; service tests cover read filters and authorization.

### P1-02: Livewire and service validation is too weak

* Issue: Message validation is only `trim()` checks, service methods do not validate required keys or allowed values, and realtime payload handlers access array keys without shape checks.
* Root Cause: UI convenience checks were treated as validation.
* Business Impact: Invalid, oversized, or malformed messages can degrade chat reliability or trigger runtime errors.
* Technical Impact: Services cannot protect non-Livewire callers; realtime payloads can throw notices/errors.
* Proposed Solution: Add Livewire `rules()` or equivalent validation for user input. Add service-level validation/invariants for sender type, session ownership, message length, recipient existence, statuses, and payload shape.
* Files To Change: `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/Livewire/Chat/InternalChatManager.php`, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `tests/Feature/Chat/ChatValidationTest.php`, `tests/Feature/Chat/InternalChatValidationTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1.5 days.
* Acceptance Criteria: Empty, oversized, invalid sender type, invalid session, invalid recipient, and malformed realtime payload cases are handled safely with tests.

### P1-03: HTTP broadcasting happens inside or too close to persistence

* Issue: `Modules/Chat/Services/ChatService.php::sendMessage` and `deleteMessage` perform HTTP broadcasts inside transactions, while `Modules/Chat/Services/InternalChatService.php::sendMessage` writes and broadcasts without a post-commit boundary.
* Root Cause: Persistence and bridge notification are coupled in the same synchronous flow.
* Business Impact: Slow or failed NodeJS bridge calls can delay chat saves or cause inconsistent user experience.
* Technical Impact: Database locks can be held during network calls; transaction rollback and broadcast order are hard to reason about.
* Proposed Solution: Persist in a transaction, then broadcast after commit through a dedicated bridge method/event/job. Keep failure logging redacted and do not roll back saved messages solely because realtime broadcast failed unless that is an explicit business rule.
* Files To Change: `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `tests/Unit/Chat/ChatServiceTest.php`, `tests/Unit/Chat/InternalChatServiceTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1.5 days.
* Acceptance Criteria: No outbound HTTP call runs inside a database transaction; saved messages remain consistent if broadcast fails; broadcast failures are logged safely.

### P1-04: Session creation and assignment are race-prone

* Issue: `Modules/Chat/Services/ChatService.php::getOrCreateSession` can race on `session_token`, and `Modules/Chat/Livewire/Chat/ChatManager.php::selectSession` can race when multiple admins claim a session.
* Root Cause: Read-then-write logic is not protected by transactions or atomic constraints beyond the unique token index.
* Business Impact: Users may see failed chat starts, and admins may accidentally overwrite assignments.
* Technical Impact: Duplicate key exceptions or last-write-wins assignment behavior can occur under concurrency.
* Proposed Solution: Use atomic session creation patterns around `session_token`, handle unique constraint retries, and move admin assignment into a service transaction that verifies current state before update.
* Files To Change: `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/database/migrations/-0001_11_30_000041_create_chat_sessions_table.php`, `tests/Unit/Chat/ChatServiceTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Concurrent session start does not create duplicate records or expose raw database errors; concurrent admin claims have deterministic accepted/denied outcomes.

### P1-05: Queries are unbounded and may cause N+1 behavior

* Issue: Session/message queries use `get()` without pagination or caps in `ChatManager`, `ChatWidget`, and `InternalChatService`; `ChatSession::getDisplayNameAttribute` can lazy-load relations.
* Root Cause: Realtime UI was built around full collections rather than bounded service queries.
* Business Impact: Large chat histories can slow admin pages and customer widgets.
* Technical Impact: Memory growth, N+1 queries, and repeated render queries can create production hotspots.
* Proposed Solution: Move queries into services, add pagination or fixed caps for sessions and messages, eager load required relations, and avoid accessors that trigger lazy loads in list contexts.
* Files To Change: `Modules/Chat/Livewire/Chat/ChatManager.php`, `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/Livewire/Chat/InternalChatManager.php`, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `Modules/Chat/Models/ChatSession.php`, `tests/Unit/Chat/ChatServiceTest.php`, `tests/Feature/Chat/ChatQueryPerformanceTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 2 days.
* Acceptance Criteria: Session lists and message histories are bounded; relations displayed by lists are eager loaded; tests or query-count assertions cover high-risk screens.

### P1-06: Internal messages migration is outside the Chat module

* Issue: `Modules/Chat/Models/InternalMessage.php` uses `internal_messages`, but the table is created by `database/migrations/2026_05_10_151034_internal_messages.php`.
* Root Cause: Schema ownership was created globally instead of under the owning module.
* Business Impact: Module installation or deployment can miss required schema.
* Technical Impact: Violates module boundaries and complicates fresh-install migration ordering.
* Proposed Solution: Plan a migration hygiene task to move ownership into `Modules/Chat/database/migrations` without breaking existing databases. Do not delete the root migration until migration history and deployment strategy are confirmed.
* Files To Change: `database/migrations/2026_05_10_151034_internal_messages.php`, `Modules/Chat/database/migrations/<new_internal_messages_migration>.php`, `Modules/Chat/Models/InternalMessage.php`, `tests/Feature/Chat/ChatMigrationTest.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 1 to 2 days plus deployment review.
* Acceptance Criteria: Fresh install creates `internal_messages` through Chat module ownership; existing migrated databases are not broken; migration smoke tests pass.

### P1-07: Internal chat depends directly on `App\Models\User`

* Issue: `Modules/Chat/Livewire/Chat/InternalChatManager.php` and `Modules/Chat/Models/InternalMessage.php` use `App\Models\User`, while the project also has a User module.
* Root Cause: Canonical user/admin model ownership is not confirmed for internal chat.
* Business Impact: Internal chat recipient rules may not align with the actual admin/user domain.
* Technical Impact: Cross-module dependencies are unclear and could conflict with future User module refactors.
* Proposed Solution: Confirm the canonical admin/user model for internal chat. Keep the current dependency only if it is the project standard; otherwise, migrate through an approved User module contract or service.
* Files To Change: `Modules/Chat/Livewire/Chat/InternalChatManager.php`, `Modules/Chat/Services/InternalChatService.php`, `Modules/Chat/Models/InternalMessage.php`, `Modules/User/Models/User.php` or `app/Models/User.php` depending on confirmed ownership, `tests/Unit/Chat/InternalChatServiceTest.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day after ownership decision.
* Acceptance Criteria: Internal chat uses one confirmed user/admin source; dependency is documented and covered by tests.

### P1-08: Bridge configuration uses `env()` and lacks fail-closed behavior

* Issue: `Modules/Chat/Services/ChatService.php::broadcastToNodeJS` and `Modules/Chat/Services/InternalChatService.php::broadcast` read `env()` directly and do not clearly fail closed when `BRIDGE_SECRET_KEY` is missing.
* Root Cause: Environment access was embedded in runtime services instead of configuration.
* Business Impact: Production bridge messages may be sent without expected secrets or fail silently.
* Technical Impact: Config caching can make direct `env()` access unreliable in Laravel.
* Proposed Solution: Move bridge URL and secret access to `config/services.php`; validate required config before sending; return/log safe failures.
* Files To Change: `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `config/services.php`, `tests/Unit/Chat/ChatBridgeConfigurationTest.php`.
* Risk Level: High.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Services use `config()` only; missing secret has deterministic behavior; tests cover configured and missing config cases.

### P1-09: Page Blade duplication and inline scripts

* Issue: `Modules/Chat/resources/views/chat.blade.php` and `Modules/Chat/resources/views/pages/chat/index.blade.php` duplicate the page header and scroll script.
* Root Cause: Separate pages were copied instead of extracting shared layout behavior.
* Business Impact: UI changes may drift between support chat and internal chat pages.
* Technical Impact: Inline scripts reduce maintainability and conflict with the target Admin UI standard.
* Proposed Solution: Extract shared page header/scroll behavior into a module Blade partial, shared component, or asset after security boundaries are stabilized.
* Files To Change: `Modules/Chat/resources/views/chat.blade.php`, `Modules/Chat/resources/views/pages/chat/index.blade.php`, `Modules/Chat/resources/views/components/<shared_header_or_script>.blade.php` or a module asset path if established, `tests/Feature/Chat/ChatPageRenderTest.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 to 1 day.
* Acceptance Criteria: Header and scroll behavior are defined once; both pages render correctly; no business logic is introduced into Blade.

### P1-10: Socket/browser behavior is duplicated across Livewire blades

* Issue: `Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php`, `Modules/Chat/resources/views/livewire/chat/chat-widget.blade.php`, and `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php` duplicate socket setup and UI scripts.
* Root Cause: Browser integration was embedded per template.
* Business Impact: Realtime behavior can diverge between chat surfaces.
* Technical Impact: Large script-heavy Livewire templates are harder to test and maintain.
* Proposed Solution: Extract shared socket helper behavior to a reusable asset or focused Blade partial while keeping Livewire components responsible for UI state only.
* Files To Change: `Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php`, `Modules/Chat/resources/views/livewire/chat/chat-widget.blade.php`, `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php`, module asset path confirmed by project conventions, `tests/Feature/Chat/ChatPageRenderTest.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 to 1.5 days.
* Acceptance Criteria: Socket behavior is centralized; Livewire views remain renderable; realtime event names remain backward compatible.

### P1-11: `wire:model` binding is inconsistent with project standard

* Issue: `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php` uses `wire:model="message"` instead of `wire:model.live`.
* Root Cause: Legacy/default Livewire binding style was used.
* Business Impact: Minor UX inconsistency with other Livewire 3 forms.
* Technical Impact: Violates repository Livewire binding standard.
* Proposed Solution: Change the internal chat message input to `wire:model.live` when validating the component flow.
* Files To Change: `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php`, `Modules/Chat/Livewire/Chat/InternalChatManager.php`, `tests/Feature/Chat/InternalChatValidationTest.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Internal chat input uses `wire:model.live`; validation and send behavior still work.

### P1-12: NodeJS bridge logic is duplicated

* Issue: Bridge HTTP calls exist separately in `Modules/Chat/Services/ChatService.php` and `Modules/Chat/Services/InternalChatService.php`.
* Root Cause: Shared infrastructure was copied into both services.
* Business Impact: Error handling and redaction can drift between support and internal chat.
* Technical Impact: Duplicate retry, timeout, secret, and logging logic increases maintenance cost.
* Proposed Solution: Extract a small Chat-owned bridge service if reuse is real, such as `Modules/Chat/Services/ChatBridgeService.php`, or keep one private method only if refactor scope stays narrow. Do not add broad abstractions outside Chat.
* Files To Change: `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `Modules/Chat/Services/ChatBridgeService.php`, `tests/Unit/Chat/ChatBridgeServiceTest.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Bridge URL, headers, timeouts, redacted logging, and failure handling are implemented once inside the Chat module.

### P1-13: Missing security and behavior tests

* Issue: Analysis found route, permission, ownership, validation, transaction, and query risks, but no Chat-specific tests are present.
* Root Cause: Feature grew without a regression suite.
* Business Impact: Security fixes may regress without detection.
* Technical Impact: Refactors to services, models, and Livewire components are risky.
* Proposed Solution: Add focused tests before or alongside each P0/P1 implementation slice. Prioritize route denial, permission denial, session ownership, send/delete authorization, validation, and query bounds.
* Files To Change: `tests/Feature/Chat/ChatRouteConfigurationTest.php`, `tests/Feature/Chat/ChatAuthorizationTest.php`, `tests/Feature/Chat/ChatWidgetSecurityTest.php`, `tests/Feature/Chat/InternalChatAuthorizationTest.php`, `tests/Feature/Chat/ChatValidationTest.php`, `tests/Unit/Chat/ChatServiceTest.php`, `tests/Unit/Chat/InternalChatServiceTest.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 2 days initially, then incremental.
* Acceptance Criteria: P0 fixes have negative tests; P1 service behavior has unit tests; route and Livewire entry points are covered.

### P1-14: Socket CORS, errors, logging, and throttling are unsafe for production

* Issue: `socket/server.js` defaults CORS origin to `"*"`, returns `err.message` from `/broadcast`, and lacks rate limits; `socket/echo.js`, `socket/server.js`, `socket/events/chat.js`, and `socket/events/internal-chat.js` contain verbose debug logging.
* Root Cause: Realtime server currently favors development visibility over production hardening.
* Business Impact: Chat activity and internal details can leak through logs or errors, and the realtime server can be abused with repeated events.
* Technical Impact: Inconsistent operational behavior and avoidable denial-of-service risk.
* Proposed Solution: Require explicit CORS origin in production, return generic error responses, gate debug logs by environment, redact payloads, and add rate limiting/throttling for `/broadcast`, joins, typing, and presence events.
* Files To Change: `socket/server.js`, `socket/echo.js`, `socket/events/chat.js`, `socket/events/internal-chat.js`, deployment env docs Needs confirmation before coding, socket integration test file Needs confirmation before coding.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1 to 1.5 days.
* Acceptance Criteria: Production mode has no wildcard CORS, no raw error messages, no raw payload logging, and rate limits on high-frequency endpoints/events.

### P1-15: Socket event payloads are not validated

* Issue: `socket/events/chat.js` trusts `session_id` in `typing` and `message-delivered`; `socket/events/internal-chat.js` trusts `room` and `user_id`; `socket/server.js` trusts broadcast payload structure.
* Root Cause: Event handlers do not validate schema or normalize types before use.
* Business Impact: Malformed realtime events can break UI behavior or leak events to unintended rooms.
* Technical Impact: Runtime errors and inconsistent room naming can occur from string/integer mismatches or invalid payloads.
* Proposed Solution: Add lightweight payload validators for each event and normalize IDs to integers/strings consistently before room construction.
* Files To Change: `socket/server.js`, `socket/events/chat.js`, `socket/events/internal-chat.js`, `socket/echo.js`, socket integration test file Needs confirmation before coding.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: Invalid payloads are rejected without throwing; valid payloads are normalized; tests cover missing, malformed, and valid event payloads.

### P1-16: Socket presence state is in-memory only

* Issue: `socket/events/internal-chat.js` stores online admins in a process-local `Map`.
* Root Cause: Presence was implemented for a single Node process without persistence or adapter support.
* Business Impact: Online status is lost on restart and inaccurate if multiple Node processes run.
* Technical Impact: Horizontal scaling breaks internal chat presence.
* Proposed Solution: Keep the in-memory map only for single-process deployments, or move presence to Redis/Socket.io adapter when scaling is required.
* Files To Change: `socket/events/internal-chat.js`, `socket/server.js`, deployment/socket config Needs confirmation before coding.
* Risk Level: Medium.
* Complexity: Medium to High depending on deployment.
* Estimated Effort: 0.5 day to document single-process assumption, or 2 to 3 days for Redis adapter.
* Acceptance Criteria: Deployment mode is explicit; presence behavior is accurate for the selected runtime model.

## 4. P2 Nice To Have Improvements

### P2-01: Admin route name is ambiguous

* Issue: `Modules/Chat/routes/web.php` names `/admin/chat/internal-chat` as `admin.chat.index`, which does not distinguish internal chat from customer support chat.
* Root Cause: Route naming was not aligned to feature slugs.
* Business Impact: Developers may link to the wrong chat page.
* Technical Impact: Future route refactors are harder to reason about.
* Proposed Solution: Rename routes to explicit names such as `admin.chat.internal.index` and `admin.chat.support.index` after checking all callers.
* Files To Change: `Modules/Chat/routes/web.php`, `Modules/Chat/Http/Controllers/ChatController.php`, any Chat/Admin Blade links found during implementation, `tests/Feature/Chat/ChatRouteConfigurationTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Route names clearly identify support vs internal chat; all links/tests use the new names.

### P2-02: Customer support page view is outside the page directory convention

* Issue: `Modules/Chat/resources/views/chat.blade.php` is at the module view root while internal chat uses `Modules/Chat/resources/views/pages/chat/index.blade.php`.
* Root Cause: Page views were added inconsistently.
* Business Impact: Low, but slows developer navigation.
* Technical Impact: Inconsistent with module page Blade conventions.
* Proposed Solution: Move or wrap customer support chat into a conventional page path after routes and references are tested.
* Files To Change: `Modules/Chat/resources/views/chat.blade.php`, `Modules/Chat/resources/views/pages/chat/support.blade.php`, `Modules/Chat/Http/Controllers/ChatController.php`, `tests/Feature/Chat/ChatPageRenderTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Customer support chat uses a conventional `pages/chat` view path; old path is removed only after references are updated.

### P2-03: Unused placeholder component

* Issue: `Modules/Chat/resources/views/components/placeholder.blade.php` appears unused.
* Root Cause: Scaffold placeholder remained after real chat views were added.
* Business Impact: None at runtime if unreferenced.
* Technical Impact: Adds clutter and can mislead developers.
* Proposed Solution: Confirm no references remain, then remove the file or repurpose it intentionally.
* Files To Change: `Modules/Chat/resources/views/components/placeholder.blade.php`, `tests/Feature/Chat/ChatPageRenderTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: File is removed only after reference search and page render tests pass.

### P2-04: Stale generated structure document

* Issue: `Modules/Chat/structure.md` appears generated and references copy files that are no longer present.
* Root Cause: Generated architecture snapshot was not maintained.
* Business Impact: Developers may trust stale documentation.
* Technical Impact: Documentation drift during refactors.
* Proposed Solution: Remove the file or regenerate it through a documented project architecture catalog process.
* Files To Change: `Modules/Chat/structure.md`, `docs/modules/Chat/ANALYSIS.md` if documentation references need updating.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Stale file no longer conflicts with actual filesystem, or it is regenerated accurately.

### P2-05: Unused internal bridge method

* Issue: `Modules/Chat/Services/InternalChatService.php::broadcastToNodeJS` appears unused because `sendMessage()` calls `broadcast()`.
* Root Cause: Older bridge method was retained after adding a second method.
* Business Impact: None directly.
* Technical Impact: Duplicate dead code confuses bridge refactoring.
* Proposed Solution: Remove the unused method after the P1 bridge service/refactor is complete.
* Files To Change: `Modules/Chat/Services/InternalChatService.php`, `tests/Unit/Chat/InternalChatServiceTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: No unused bridge method remains; internal chat send tests still pass.

### P2-06: Model table names are implicit

* Issue: `Modules/Chat/Models/ChatSession.php`, `Modules/Chat/Models/ChatMessage.php`, and `Modules/Chat/Models/InternalMessage.php` rely on implicit table naming.
* Root Cause: Laravel conventions were used, but duplicate model ownership makes explicitness useful.
* Business Impact: Low.
* Technical Impact: Auditing duplicate ownership is harder.
* Proposed Solution: Add explicit `$table` declarations once canonical model ownership is settled.
* Files To Change: `Modules/Chat/Models/ChatSession.php`, `Modules/Chat/Models/ChatMessage.php`, `Modules/Chat/Models/InternalMessage.php`, `tests/Unit/Chat/ChatModelTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Chat models explicitly declare their table names; model tests verify table, fillable, casts, and relationships.

### P2-07: Chat chronology indexes need review

* Issue: `Modules/Chat/database/migrations/-0001_11_30_000042_create_chat_messages_table.php` lacks a composite index for session chronology queries, and `database/migrations/2026_05_10_151034_internal_messages.php` lacks a reverse or room-normalized index for bidirectional lookups.
* Root Cause: Indexes were added for individual fields before final query patterns were defined.
* Business Impact: Large chat tables may slow down message loading.
* Technical Impact: Query plans may degrade as data grows.
* Proposed Solution: After service query patterns are finalized, add measured indexes for `(chat_session_id, created_at)` or `(chat_session_id, id)` and the chosen internal-message lookup pattern.
* Files To Change: `Modules/Chat/database/migrations/-0001_11_30_000042_create_chat_messages_table.php`, `database/migrations/2026_05_10_151034_internal_messages.php` or the future Chat-owned internal messages migration, `Modules/Chat/Services/ChatService.php`, `Modules/Chat/Services/InternalChatService.php`, `tests/Feature/Chat/ChatMigrationTest.php`.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 0.5 to 1 day.
* Acceptance Criteria: Indexes match actual service queries; migration tests pass; no redundant indexes are added blindly.

### P2-08: Import/export should not be added without a business requirement

* Issue: `Modules/Chat` has no import/export classes or `Modules/Chat/Services/ImportExport.php`, and analysis found no current import/export UI.
* Root Cause: Not applicable; absence is acceptable unless requirements change.
* Business Impact: Adding import/export prematurely could expose private chat data.
* Technical Impact: Unneeded code would increase security and maintenance surface.
* Proposed Solution: Do not add import/export in the Chat refactor. If business later requires export, create a separate analysis and use the shared import/export foundation with explicit sensitive-field exclusions.
* Files To Change: No file should change now. Future requirement would affect `Modules/Chat/Services/ImportExport.php`, `Modules/Chat/resources/views/pages/chat/import-export.blade.php`, and `tests/Feature/Chat/ChatImportExportTest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0 days now.
* Acceptance Criteria: Refactor does not introduce chat import/export; any future import/export starts with a confirmed data/security spec.

### P2-09: Chat widget ownership needs confirmation before cleanup

* Issue: `Modules/Chat/Livewire/Chat/ChatWidget.php` is not mounted by a page inside `Modules/Chat`, but root `resources/views/welcome.blade.php` references `@livewire('chat.chat.chat-widget')`.
* Root Cause: Website integration depends on a Chat module component from outside the module page flow.
* Business Impact: Removing or moving the widget could break the public website chat.
* Technical Impact: Cross-module/page dependency must be preserved during cleanup.
* Proposed Solution: Document `ChatWidget` as public website integration, or migrate mounting through the Website module only after a caller migration plan.
* Files To Change: `Modules/Chat/Livewire/Chat/ChatWidget.php`, `Modules/Chat/resources/views/livewire/chat/chat-widget.blade.php`, `resources/views/welcome.blade.php`, `Modules/Website/resources/views/partials/footer.blade.php`, `Modules/Website/Livewire/Chat/ChatWidget.php`, `tests/Feature/Chat/ChatWidgetRenderTest.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day for ownership decision and tests.
* Acceptance Criteria: Public widget ownership is documented; no cleanup breaks website chat rendering.

### P2-10: Socket debug globals and duplicate listeners should be cleaned up

* Issue: `socket/echo.js` registers `socket.onAny` twice and exposes `window.socket`, `window.currentSessionId`, `window.joinSession`, `window.leaveSession`, and `window.debugSocket`.
* Root Cause: Debug helpers and global lifecycle state remained in the production client script.
* Business Impact: Low direct impact after P0/P1 hardening, but browser console and globals can expose operational details.
* Technical Impact: Duplicate listeners create noisy logs and make socket lifecycle harder to reason about.
* Proposed Solution: Remove duplicate `onAny`, gate or remove debug helpers, and define a clear lifecycle for joining/leaving rooms after signed room authorization exists.
* Files To Change: `socket/echo.js`, Chat Livewire blade files that call global socket helpers, socket integration test file Needs confirmation before coding.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Only one debug listener exists in development; production has no global debug helper; join/leave lifecycle remains functional.

### P2-11: Unused socket module parameters should be removed

* Issue: `socket/server.js` calls `chatModule(socket, io, bridgeAuth, app)`, but `socket/events/chat.js` only accepts and uses `(socket, io)`.
* Root Cause: Older module wiring signature was left behind.
* Business Impact: None at runtime.
* Technical Impact: Misleads maintainers about where bridge auth is applied.
* Proposed Solution: Simplify the call signature after socket hardening is complete.
* Files To Change: `socket/server.js`, `socket/events/chat.js`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.25 day.
* Acceptance Criteria: Socket module signatures match actual usage; no behavior change.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. P0-01: Secure or remove the public API route in `Modules/Chat/routes/api.php`.
2. P0-02: Add named permission checks for Chat routes and mutating Livewire actions.
3. P0-08: Redact chat and bridge logging in both Chat services.
4. P0-05: Stop sender spoofing and unauthorized message deletion.
5. P0-03: Move admin session claiming into `Modules/Chat/Services/ChatService.php`.
6. P0-04: Enforce widget token/session ownership in `Modules/Chat/Services/ChatService.php`.
7. P0-06: Enforce internal chat recipient authorization.
8. P0-09: Make socket bridge auth fail closed.
9. P0-10: Require signed/authorized socket room joins.
10. P0-11: Derive online presence identity from verified socket context.
11. P0-12: Add event/channel allowlists to `/broadcast`.
12. P1-13: Add the first security regression tests around the P0 behavior.

### Phase 2: Correctness and Maintainability

1. P0-07: Migrate Chat module imports to canonical `Modules/Chat/Models`.
2. P1-01: Move Livewire read queries into services.
3. P1-02: Add Livewire and service validation.
4. P1-03: Move broadcasts after commit or behind a safe event/job boundary.
5. P1-04: Add transaction/race handling for session creation and admin assignment.
6. P1-08: Move bridge configuration to `config/services.php`.
7. P1-12: Consolidate bridge behavior inside Chat.
8. P1-14: Harden socket CORS, errors, logging, and throttling.
9. P1-15: Validate socket event payloads.
10. P1-07: Confirm User/Admin ownership for internal chat.
11. P1-06: Plan migration ownership for `internal_messages`.

### Phase 3: Performance and Cleanup

1. P1-05: Add bounded queries, pagination/caps, and eager loading.
2. P1-09: Extract duplicate page header/scroll behavior.
3. P1-10: Centralize duplicated Socket.io browser behavior.
4. P1-11: Standardize `wire:model.live` in internal chat.
5. P2-01 and P2-02: Normalize route and page naming.
6. P2-03, P2-04, and P2-05: Remove confirmed unused/stale files and methods.
7. P2-06: Add explicit model table names.
8. P2-07: Add measured query indexes after query patterns are finalized.
9. P2-10 and P2-11: Clean socket debug globals/listeners and unused module parameters.
10. P2-08 and P2-09: Keep import/export out of scope and confirm widget ownership.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/Chat/routes/api.php` | Modify or remove route | P0 | Public unauthenticated route points to missing action. |
| `Modules/Chat/Http/Controllers/Api/ChatController.php` | Modify or remove placeholder | P0 | Controller has no `index` method and unused import. |
| `Modules/Chat/routes/web.php` | Modify middleware/names | P0/P2 | Add named permissions; later clarify route names. |
| `Modules/Chat/Http/Controllers/ChatController.php` | Modify authorization/page mapping | P0/P2 | Ensure permission boundary and page path consistency. |
| `Modules/Chat/config/module.php` | Review permissions | P0 | Confirm action-level chat permissions. |
| `Modules/Chat/Livewire/Chat/ChatManager.php` | Refactor | P0/P1 | Remove direct queries, enforce session ownership, validation, and service calls. |
| `Modules/Chat/Livewire/Chat/ChatWidget.php` | Refactor | P0/P1/P2 | Enforce token ownership, remove direct queries/logging, confirm website ownership. |
| `Modules/Chat/Livewire/Chat/InternalChatManager.php` | Refactor | P0/P1 | Enforce recipient authorization, remove direct user queries, add validation. |
| `Modules/Chat/Services/ChatService.php` | Refactor | P0/P1 | Canonical model use, authorization, validation, transactions, bounded queries, safe bridge handling. |
| `Modules/Chat/Services/InternalChatService.php` | Refactor | P0/P1/P2 | Recipient authorization, validation, bounded queries, bridge consolidation, remove unused method. |
| `Modules/Chat/Services/ChatBridgeService.php` | Add optional service | P1 | Centralize NodeJS bridge behavior inside Chat if reuse is retained. |
| `Modules/Chat/Models/ChatSession.php` | Modify | P0/P1/P2 | Canonical model, avoid N+1 access patterns, explicit table declaration. |
| `Modules/Chat/Models/ChatMessage.php` | Modify | P0/P2 | Canonical model and explicit table declaration. |
| `Modules/Chat/Models/InternalMessage.php` | Modify | P1/P2 | Confirm user dependency, explicit table declaration. |
| `Modules/Admin/Models/ChatSession.php` | Later migrate/remove | P0 | Duplicate domain model; do not remove until Admin callers migrate. |
| `Modules/Admin/Models/ChatMessage.php` | Later migrate/remove | P0 | Duplicate domain model; do not remove until Admin callers migrate. |
| `Modules/Chat/database/migrations/-0001_11_30_000041_create_chat_sessions_table.php` | Review/add future migration | P1 | Race handling and schema/index review for session creation. |
| `Modules/Chat/database/migrations/-0001_11_30_000042_create_chat_messages_table.php` | Review/add future migration | P2 | Add measured chronology indexes after query patterns settle. |
| `database/migrations/2026_05_10_151034_internal_messages.php` | Migration hygiene | P1/P2 | Root migration owns Chat table; index review needed. |
| `Modules/Chat/database/migrations/<new_internal_messages_migration>.php` | Add future migration | P1 | Move internal message schema ownership into Chat safely. |
| `Modules/Chat/resources/views/chat.blade.php` | Refactor/move | P1/P2 | Remove duplicate header/script and normalize page path. |
| `Modules/Chat/resources/views/pages/chat/index.blade.php` | Refactor | P1/P2 | Remove duplicate header/script and keep page shell thin. |
| `Modules/Chat/resources/views/pages/chat/support.blade.php` | Add optional page | P2 | Conventional support chat page path if view is moved. |
| `Modules/Chat/resources/views/components/placeholder.blade.php` | Remove after confirmation | P2 | Appears unused scaffold file. |
| `Modules/Chat/resources/views/components/<shared_header_or_script>.blade.php` | Add optional component | P1 | Shared page header/scroll behavior if Blade extraction is chosen. |
| `Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php` | Refactor | P1/P2 | Reduce script duplication and keep view maintainable. |
| `Modules/Chat/resources/views/livewire/chat/chat-widget.blade.php` | Refactor | P1/P2 | Reduce script duplication and preserve widget behavior. |
| `Modules/Chat/resources/views/livewire/chat/internal-chat-manager.blade.php` | Refactor | P1/P2 | Use `wire:model.live`, reduce script duplication. |
| `Modules/Chat/structure.md` | Remove or regenerate | P2 | Stale generated documentation. |
| `resources/views/welcome.blade.php` | Review only before changes | P2 | External caller of Chat widget. |
| `Modules/Website/resources/views/partials/footer.blade.php` | Review only before changes | P2 | Website has a separate chat widget reference. |
| `Modules/Website/Livewire/Chat/ChatWidget.php` | Review only before changes | P2 | Potential duplicate/caller relevant to widget ownership. |
| `config/services.php` | Modify | P1 | Move bridge URL/secret config out of direct `env()` usage. |
| `socket/server.js` | Refactor | P0/P1/P2 | Fail-closed bridge auth, event/channel allowlist, CORS hardening, error redaction, rate limiting, module signature cleanup. |
| `socket/echo.js` | Refactor | P0/P1/P2 | Signed room join flow, remove duplicate debug listener, reduce globals, redacted/gated logging. |
| `socket/events/chat.js` | Refactor | P0/P1/P2 | Authorize `join-session`, validate support-chat socket payloads, throttle events, remove unused signature assumptions. |
| `socket/events/internal-chat.js` | Refactor | P0/P1 | Authorize DM room joins, verify presence identity, validate payloads, clarify presence storage strategy. |
| `tests/Feature/Chat/ChatRouteConfigurationTest.php` | Add | P0/P2 | Route boot, auth, and naming coverage. |
| `tests/Feature/Chat/ChatAuthorizationTest.php` | Add | P0 | Permission and ownership denial coverage. |
| `tests/Feature/Chat/ChatWidgetSecurityTest.php` | Add | P0 | Guest/user token ownership coverage. |
| `tests/Feature/Chat/InternalChatAuthorizationTest.php` | Add | P0 | Recipient authorization coverage. |
| `tests/Feature/Chat/ChatValidationTest.php` | Add | P1 | Livewire/service validation coverage. |
| `tests/Feature/Chat/InternalChatValidationTest.php` | Add | P1 | Internal chat validation and binding coverage. |
| `tests/Feature/Chat/ChatQueryPerformanceTest.php` | Add | P1 | Bounded query and N+1 regression coverage. |
| `tests/Feature/Chat/ChatMigrationTest.php` | Add | P1/P2 | Migration ownership and index coverage. |
| `tests/Feature/Chat/ChatPageRenderTest.php` | Add | P1/P2 | Page render coverage during Blade cleanup. |
| `tests/Feature/Chat/ChatWidgetRenderTest.php` | Add | P2 | Public widget render coverage. |
| `tests/Unit/Chat/ChatServiceTest.php` | Add | P0/P1 | Chat service authorization, validation, transactions, and reads. |
| `tests/Unit/Chat/InternalChatServiceTest.php` | Add | P0/P1 | Internal chat service authorization, validation, and reads. |
| `tests/Unit/Chat/ChatBridgeLoggingTest.php` | Add | P0 | Redacted logging coverage. |
| `tests/Unit/Chat/ChatBridgeConfigurationTest.php` | Add | P1 | Bridge config/fail-closed coverage. |
| `tests/Unit/Chat/ChatBridgeServiceTest.php` | Add optional | P1 | Shared bridge behavior coverage if service is extracted. |
| `tests/Unit/Chat/ChatModelTest.php` | Add | P2 | Model table/fillable/cast/relationship coverage. |
| `tests/Socket/ChatSocketSecurityTest.*` | Add after test harness confirmation | P0/P1 | Socket bridge auth, room authorization, payload validation, and presence spoofing coverage. |

## 7. Risk Control

Do not change these yet:

- Do not delete `Modules/Admin/Models/ChatSession.php` or `Modules/Admin/Models/ChatMessage.php` until all Admin callers are identified, migrated, and tested.
- Do not move `database/migrations/2026_05_10_151034_internal_messages.php` blindly. Migration history and deployed database state must be reviewed first.
- Do not rename chat routes until all Blade links, redirects, tests, and external callers are known.
- Do not remove `Modules/Chat/Livewire/Chat/ChatWidget.php` just because it is not mounted by a Chat page; `resources/views/welcome.blade.php` references it.
- Do not add Chat import/export in this refactor. Chat data is sensitive and requires a separate confirmed business/security spec.
- Do not introduce DTOs; use validated arrays/scalars between Livewire and services.
- Do not put model queries back into Livewire, controllers, or Blade while refactoring.
- Do not perform broad UI redesign while P0 authorization, ownership, and logging risks remain open.
- Do not add indexes before final service query patterns are known; measure and align indexes to actual queries.
- Do not log raw chat messages, bridge secrets, raw NodeJS responses, or stack traces in user-facing flows.
- Do not expose socket room joins based only on browser-supplied IDs, room names, or online-user state.
- Do not run `socket/server.js` in production with missing `BRIDGE_SECRET_KEY` or wildcard CORS.
