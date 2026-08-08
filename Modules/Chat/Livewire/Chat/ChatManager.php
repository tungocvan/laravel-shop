<?php

namespace Modules\Chat\Livewire\Chat;

use Livewire\Component;
use Modules\Admin\Models\ChatSession;
use Modules\Admin\Models\ChatMessage;
use Modules\Chat\Services\ChatService;
use Illuminate\Support\Facades\Auth;

class ChatManager extends Component
{
    public ?int $activeSessionId = null;
    public string $message = '';
    public array $messages = [];

    // Lắng nghe sự kiện từ NodeJS gửi về thông qua trình duyệt
    protected $listeners = [
        'echo-refresh' => '$refresh',
        'appendMessage' => 'appendMessage',
    ];

    public function selectSession(int $sessionId): void
    {
        $this->activeSessionId = $sessionId;
        $session = ChatSession::find($sessionId);

        if (!$session) return;

        // Gán admin quản lý session nếu chưa có ai
        if (!$session->admin_id) {
            $session->update(['admin_id' => Auth::id()]);
        }

        // Load 50 tin nhắn mới nhất
        $this->loadMessages();

        // Dispatch sự kiện để AlpineJS Join Room bên Socket.io
        $this->dispatch('chat-session-selected', sessionId: $sessionId);
    }

    public function loadMessages()
    {
        if (!$this->activeSessionId) return;

        $this->messages = ChatMessage::query()
            ->where('chat_session_id', $this->activeSessionId)
            ->oldest() // Lấy từ cũ đến mới để hiển thị đúng thứ tự
            ->limit(100)
            ->get()
            ->toArray();

        $this->dispatch('scroll-bottom');
    }

    public function send(ChatService $chatService): void
    {
        if (!$this->activeSessionId) return;

        $messageText = trim($this->message);
        if (!$messageText) return;

        $sentMessage = $chatService->sendMessage([
            'chat_session_id' => $this->activeSessionId,
            'sender_id'       => Auth::id(),
            'sender_type'     => 'admin',
            'message'         => $messageText,
        ]);

        // Hiển thị ngay trong chính request Livewire. Socket.IO vẫn phát
        // cho các client khác; appendMessage() sẽ loại bản sao theo ID.
        $this->appendMessage($sentMessage->toArray());
        $this->reset('message');
    }

    // public function appendMessage($message): void 
    // {
    //     // Chống trùng lặp tin nhắn
    //     $exists = collect($this->messages)->contains('id', $message['id']);
    //     if ($exists) return;

    //     if ($message['chat_session_id'] == $this->activeSessionId) {
    //         $this->messages[] = $message;
    //         $this->dispatch('scroll-bottom');
    //     }
    // }



    public function appendMessage($message): void
    {
        // Chuyển đổi nếu message là JSON string
        if (is_string($message)) {
            $message = json_decode($message, true);
        }

        // Kiểm tra xem tin nhắn có thuộc session đang mở không
        if ((int)$message['chat_session_id'] !== $this->activeSessionId) {
            return;
        }

        // Chống trùng (Duplicate prevention)
        $exists = collect($this->messages)->contains('id', $message['id']);
        if ($exists) return;

        // Đẩy vào mảng và Livewire sẽ tự động render lại HTML
        $this->messages[] = $message;

        // Cuộn xuống cuối
        $this->dispatch('scroll-bottom');
    }

    public function getSessionsProperty()
    {
        return ChatSession::query()
            ->with(['user', 'latestMessage'])
            ->latest('last_message_at')
            ->get();
    }

    public function getActiveSessionProperty()
    {
        return $this->activeSessionId ? ChatSession::find($this->activeSessionId) : null;
    }

    public function render()
    {
        return view('Chat::livewire.chat.chat-manager');
    }
}
