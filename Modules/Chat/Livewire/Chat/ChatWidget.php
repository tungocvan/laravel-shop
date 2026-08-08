<?php

namespace Modules\Chat\Livewire\Chat;

use Livewire\Component;
use Modules\Chat\Services\ChatService;
use Modules\Admin\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatWidget extends Component
{
    public $isOpen = false;

    public $step = 'auth';

    public $message = '';

    public $sessionToken;

    public $sessionId = null;

    public $activeSessionId = null;

    public $messages = [];

    protected $listeners = [

        'refresh-widget' => '$refresh',

        'refresh-chat' => '$refresh',

    ];

    public function mount()
    {
        logger('CHAT WIDGET MOUNT');

        /**
         * USER LOGIN
         */
        if (Auth::check()) {

            $this->sessionToken =
                'user_' . Auth::id();

            $session =
                ChatSession::where(
                    'session_token',
                    $this->sessionToken
                )
                ->first();

            if ($session) {

                $this->activeSessionId =
                    $session->id;

                $this->step =
                    'chat';

                $this->loadMessages();
            }

            return;
        }

        /**
         * GUEST
         */
        $this->sessionToken =
            session()->get(
                'chat_token'
            );

        if (!$this->sessionToken) {

            $this->sessionToken =
                Str::random(32);

            session([
                'chat_token' =>
                $this->sessionToken
            ]);
        }

        $session =
            ChatSession::where(
                'session_token',
                $this->sessionToken
            )
            ->first();

        if ($session) {

            $this->activeSessionId =
                $session->id;

            $this->step =
                'chat';

            $this->loadMessages();
        }

        logger([
            'token' =>
            $this->sessionToken,

            'session' =>
            $this->activeSessionId
        ]);
    }

    /**
     * LOAD MESSAGES
     */
    public function loadMessages()
    {
        $session =
            ChatSession::where(
                'session_token',
                $this->sessionToken
            )
            ->with([
                'messages' => fn($q)
                => $q->orderBy(
                    'created_at',
                    'asc'
                )
            ])
            ->first();

        $this->messages =
            $session
            ? $session->messages->toArray()
            : [];

        $this->dispatch(
            'scroll-bottom'
        );
    }

    /**
     * START CHAT
     */
    public function startChat(
        ChatService $chatService
    ) {
        logger(
            'START CHAT'
        );

        $session =
            $chatService
            ->getOrCreateSession(
                $this->sessionToken
            );

        logger([

            'session' =>
            $session->id,

            'token' =>
            $this->sessionToken

        ]);

        $this->activeSessionId =
            $session->id;

        $this->step =
            'chat';

        $this->loadMessages();

        /**
         * BROWSER EVENT
         */
        $this->dispatch(
            'chat-session-selected',
            sessionId: $session->id
        );

        $this->dispatch(
            'scroll-bottom'
        );
    }

    /**
     * SOCKET REALTIME
     */
    public function appendMessage($newMessage)
    {
        logger([
            'appendMessage' => $newMessage
        ]);

        /**
         * tránh trùng
         */
        foreach ($this->messages as $msg) {

            $id = is_array($msg)
                ? $msg['id']
                : $msg->id;

            if (
                $id == $newMessage['id']
            ) {
                return;
            }
        }

        $this->messages[] = [
            'id' => $newMessage['id'],
            'sender_type' => $newMessage['sender_type'],
            'message' => $newMessage['message'],
            'created_at' => now()
                ->format('Y-m-d H:i:s')
        ];

        $this->dispatch(
            'scroll-bottom'
        );
    }

    /**
     * SEND MESSAGE
     */
    public function send(
        ChatService $chatService
    ) {
        if (
            !trim(
                $this->message
            )
        ) {

            return;
        }

        $session =
            $chatService
            ->getOrCreateSession(
                $this->sessionToken
            );

        if (
            !$this->activeSessionId
        ) {

            $this->activeSessionId =
                $session->id;
        }

        $payload = [

            'session_id' =>
            $session->id,

            'sender_id' =>
            Auth::id(),

            'sender_type' =>
            Auth::check()
                ? 'user'
                : 'guest',

            'message' =>
            trim(
                $this->message
            ),

        ];

        logger([
            'send' =>
            $payload
        ]);

        $chatService
            ->sendMessage(
                $payload
            );

        $this->message = '';

        $this->dispatch(
            'scroll-bottom'
        );
    }

    public function render()
    {
        if (
            empty($this->messages)
            &&
            $this->step === 'chat'
        ) {

            $session =
                ChatSession::where(
                    'session_token',
                    $this->sessionToken
                )
                ->with([
                    'messages' =>
                    fn($q)
                    => $q->orderBy(
                        'created_at'
                    )
                ])
                ->first();

            $this->messages =
                $session
                ? $session
                ->messages
                ->toArray()
                : [];
        }

        return view(
            'Chat::livewire.chat.chat-widget'
        );
    }
}
