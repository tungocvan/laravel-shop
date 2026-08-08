<?php

namespace Modules\Chat\Livewire\Chat;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Services\InternalChatService;

class InternalChatManager extends Component
{
    public ?int $selectedUserId = null;

    public ?User $selectedUser = null;

    public string $message = '';

    public array $messages = [];

    public array $onlineUsers = [];

    protected $listeners = [
        'appendMessage',
        'setOnlineUsers',
    ];

    /**
     * =====================================================
     * SELECT USER
     * =====================================================
     */
    public function selectUser(
        int $userId,
        InternalChatService $service
    ): void {

        $this->selectedUserId = $userId;

        /**
         * Selected user
         */
        $this->selectedUser = User::query()
            ->select([
                'id',
                'name',
                'email',
            ])
            ->find($userId);

        /**
         * Messages
         */
        $this->messages = $service
            ->getMessages($userId)
            ->toArray();

        /**
         * Join socket room
         */
        $this->dispatch(
            'join-room',
            room: $this->roomName()
        );

        /**
         * Scroll bottom
         */
        $this->dispatch('scroll-bottom');
    }

    /**
     * =====================================================
     * SEND MESSAGE
     * =====================================================
     */
    public function send(
        InternalChatService $service
    ): void {

        if (!$this->selectedUserId) {
            return;
        }

        if (!trim($this->message)) {
            return;
        }

        /**
         * Send
         */
        $service->sendMessage(
            $this->selectedUserId,
            trim($this->message)
        );

        /**
         * Clear input
         */
        $this->reset('message');

        /**
         * Reload messages
         */
        $this->messages = $service
            ->getMessages($this->selectedUserId)
            ->toArray();

        /**
         * Scroll
         */
        $this->dispatch('scroll-bottom');
    }

    /**
     * =====================================================
     * REALTIME APPEND MESSAGE
     * =====================================================
     */
    public function appendMessage(
        $message
    ): void {

        if (is_string($message)) {

            $message = json_decode(
                $message,
                true
            );
        }

        if (!is_array($message)) {
            return;
        }


        /**
         * Duplicate prevent
         */
        $exists = collect($this->messages)
            ->contains(
                fn($msg)
                    =>
                    $msg['id']
                    ==
                    $message['id']
            );

        if ($exists) {
            return;
        }

        /**
         * Append realtime
         */
        $this->messages[] = $message;

        /**
         * Scroll
         */
        $this->dispatch('scroll-bottom');
    }

    /**
     * =====================================================
     * ONLINE USERS
     * =====================================================
     */
    public function setOnlineUsers($users): void
    {
        $this->onlineUsers = $users;
    }

    /**
     * =====================================================
     * ROOM NAME
     * =====================================================
     */
    public function roomName(): ?string
    {
        if (!$this->selectedUserId) {
            return null;
        }

        $ids = [
            Auth::guard('admin')->id(),
            $this->selectedUserId,
        ];

        sort($ids);

        return "dm-{$ids[0]}-{$ids[1]}";
    }

    /**
     * =====================================================
     * RENDER
     * =====================================================
     */
    public function render()
    {
        return view(
            'Chat::livewire.chat.internal-chat-manager',
            [

                'users' => User::query()
                    ->select([
                        'id',
                        'name',
                        'email',
                    ])
                    ->whereIn(
                        'id',
                        $this->onlineUsers
                    )
                    ->where(
                        'id',
                        '!=',
                        Auth::guard('admin')->id()
                    )
                    ->orderBy('name')
                    ->get(),

            ]
        );
    }
}
