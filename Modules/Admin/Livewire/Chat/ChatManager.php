<?php

namespace Modules\Admin\Livewire\Chat;

use Livewire\Component;
use Modules\Admin\Models\ChatSession;
use Modules\Admin\Services\ChatService;
use Illuminate\Support\Facades\Auth;

class ChatManager extends Component
{
    public $activeSessionId = null;
    public $message = '';

    /**
     * Lắng nghe sự kiện Realtime từ Echo
     */
    public function getListeners()
    {
        return [
            "refresh-chat" => '$refresh',
            'refresh-widget' => '$refresh'
        ];
    }

    /**
     * Xử lý khi có tin nhắn mới bay về
     */
    public function handleIncomingMessage($data)
    {
        // Tự động refresh UI để hiển thị tin nhắn mới hoặc cập nhật Sidebar
        $this->dispatch('refresh-chat');

        // Nếu tin nhắn thuộc session đang mở, yêu cầu cuộn xuống cuối
        if (isset($data['session_id']) && $data['session_id'] == $this->activeSessionId) {
            $this->dispatch('scroll-chat-to-bottom');
        }
    }

    public function selectSession($id)
    {
        $this->activeSessionId = $id;

        // Gán Admin tiếp nhận phiên chat nếu chưa có ai trực
        $session = ChatSession::find($id);
        if ($session && !$session->admin_id) {
            $session->update(['admin_id' => Auth::id()]);
        }

        $this->dispatch('scroll-chat-to-bottom');
    }

    public function send(ChatService $chatService)
    {
        if (!$this->activeSessionId || empty(trim($this->message))) return;

        // Gọi Service xử lý (Lưu DB + Bridge sang NodeJS)
        $chatService->sendMessage([
            'session_id'  => $this->activeSessionId,
            'sender_id'    => Auth::id(),
            'sender_type'  => 'admin',
            'message'      => $this->message,
        ]);

        $this->message = '';
        $this->dispatch('scroll-chat-to-bottom');
    }

    public function render()
    {
        return view('Admin::livewire.chat.chat-manager', [
            'sessions' => ChatSession::with(['user', 'latestMessage'])
                ->orderBy('last_message_at', 'desc') // Sắp xếp theo tương tác mới nhất
                ->limit(30)
                ->get(),
            'activeSession' => $this->activeSessionId
                ? ChatSession::with(['messages' => fn($q) => $q->orderBy('created_at', 'asc')])
                    ->find($this->activeSessionId)
                : null,
        ]);
    }
    public function delete($id, ChatService $service)
    {
        $service->deleteMessage($id);
        $this->dispatch('refresh-chat');
    }
    public function clearSessionMessages($sessionId, ChatService $service)
    {
        $service->deleteAllMessages($sessionId);

        if ($this->activeSessionId == $sessionId) {
            $this->dispatch('refresh-chat'); // Làm mới vùng chat hiện tại của Admin
        }
    }
}
