<?php

namespace Modules\Website\Livewire\Chat;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Chat\Services\ChatService;

class ChatWidget extends Component
{
    public $isOpen = false;

    public $step = 'auth'; // auth, chat

    public $message = '';

    public $sessionToken;

    public $chatSessionId = null;

    public function getListeners()
    {
        return [
            'refresh-widget' => '$refresh',
            'refresh-chat' => '$refresh',
        ];
    }

    public function mount(ChatService $chatService)
    {
        if (Auth::check()) {
            $this->step = 'chat';
            $this->sessionToken = 'user_'.Auth::id();
        } else {
            $this->sessionToken = session()->get('chat_token', Str::random(32));
            session(['chat_token' => $this->sessionToken]);

            $exists = $chatService->findSession($this->sessionToken) !== null;

            if ($exists) {
                $this->step = 'chat';
            }
        }

        $session = $chatService->findSession($this->sessionToken);

        $this->chatSessionId = $session?->id;
    }

    public function handleIncoming($data, ChatService $chatService)
    {
        // Chỉ refresh nếu tin nhắn thuộc về chính khách hàng này
        $session = $chatService->findSession($this->sessionToken);
        if ($session && isset($data['session_id']) && $data['session_id'] == $session->id) {
            $this->dispatch('refresh-widget');
            $this->dispatch('scroll-bottom');
        }
    }

    public function startChat(ChatService $chatService)
    {
        $session = $chatService->getOrCreateSession($this->sessionToken);

        $this->chatSessionId = $session->id;
        $this->step = 'chat';

        $this->dispatch(
            'chat-session-ready',
            sessionId: $session->id
        );

        $this->dispatch('scroll-bottom');
    }

    public function send(ChatService $chatService)
    {
        if (empty(trim($this->message))) {
            return;
        }

        $session = $chatService->getOrCreateSession($this->sessionToken);

        $chatService->sendMessage([
            'session_id' => $session->id,
            'sender_id' => Auth::id(),
            'sender_type' => Auth::check() ? 'user' : 'guest',
            'message' => $this->message,
        ]);

        $this->message = '';
        $this->dispatch('scroll-bottom');
    }

    public function render(ChatService $chatService)
    {
        $messages = [];
        if ($this->step === 'chat') {
            $session = $chatService->findSession($this->sessionToken, true);
            $messages = $session ? $session->messages : [];
        }

        return view('Website::livewire.chat.chat-widget', [
            'messages' => $messages,
        ]);
    }
}
