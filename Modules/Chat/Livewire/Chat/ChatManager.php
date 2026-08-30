<?php

namespace Modules\Chat\Livewire\Chat;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Chat\Models\ChatMessage;
use Modules\Chat\Models\ChatSession;
use Modules\Chat\Services\ChatService;

class ChatManager extends Component
{
    public ?int $activeSessionId = null;

    public string $message = '';

    public array $messages = [];

    protected $listeners = [
        'echo-refresh' => '$refresh',
        'appendMessage' => 'appendMessage',
    ];

    public function mount(): void
    {
        $this->authorizePermission('view_chat');
    }

    public function selectSession(int $sessionId): void
    {
        $this->authorizePermission('edit_chat');

        $session = ChatSession::query()->find($sessionId);

        if (! $session) {
            return;
        }

        $this->activeSessionId = $sessionId;

        if (! $session->admin_id) {
            $session->update(['admin_id' => Auth::guard('admin')->id()]);
        }

        $this->loadMessages();
        $this->dispatch('chat-session-selected', sessionId: $sessionId);
    }

    public function loadMessages(): void
    {
        $this->authorizePermission('view_chat');

        if (! $this->activeSessionId) {
            return;
        }

        $this->messages = ChatMessage::query()
            ->where('chat_session_id', $this->activeSessionId)
            ->oldest()
            ->limit(100)
            ->get()
            ->toArray();

        $this->dispatch('scroll-bottom');
    }

    public function send(ChatService $chatService): void
    {
        $this->authorizePermission('create_chat');

        if (! $this->activeSessionId) {
            return;
        }

        $messageText = trim($this->message);

        if ($messageText === '') {
            return;
        }

        $sentMessage = $chatService->sendMessage([
            'chat_session_id' => $this->activeSessionId,
            'sender_id' => Auth::guard('admin')->id(),
            'sender_type' => 'admin',
            'message' => $messageText,
        ]);

        $this->appendMessage($sentMessage->toArray());
        $this->reset('message');
    }

    public function appendMessage($message): void
    {
        $this->authorizePermission('view_chat');

        if (is_string($message)) {
            $message = json_decode($message, true);
        }

        if (! is_array($message) || ! isset($message['chat_session_id'], $message['id'])) {
            return;
        }

        if ((int) $message['chat_session_id'] !== $this->activeSessionId) {
            return;
        }

        if (collect($this->messages)->contains('id', $message['id'])) {
            return;
        }

        $this->messages[] = $message;
        $this->dispatch('scroll-bottom');
    }

    public function getSessionsProperty()
    {
        $this->authorizePermission('view_chat');

        return ChatSession::query()
            ->with(['user', 'latestMessage'])
            ->latest('last_message_at')
            ->get();
    }

    public function getActiveSessionProperty(): ?ChatSession
    {
        $this->authorizePermission('view_chat');

        return $this->activeSessionId
            ? ChatSession::query()->find($this->activeSessionId)
            : null;
    }

    public function delete(int $id, ChatService $chatService): void
    {
        $this->authorizePermission('delete_chat');
        $chatService->deleteMessage($id);
        $this->dispatch('echo-refresh');
    }

    public function clearSessionMessages(int $sessionId, ChatService $chatService): void
    {
        $this->authorizePermission('delete_chat');
        $chatService->deleteAllMessages($sessionId);

        if ($this->activeSessionId === $sessionId) {
            $this->loadMessages();
        }
    }

    public function render()
    {
        $this->authorizePermission('view_chat');

        return view('Chat::livewire.chat.chat-manager');
    }

    private function authorizePermission(string $permission): void
    {
        $admin = Auth::guard('admin')->user();

        abort_unless($admin, 403);
        Gate::forUser($admin)->authorize($permission);
    }
}
