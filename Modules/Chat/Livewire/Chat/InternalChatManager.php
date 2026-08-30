<?php

namespace Modules\Chat\Livewire\Chat;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
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

    public function mount(): void
    {
        $this->authorizePermission('view_chat');
    }

    public function selectUser(int $userId, InternalChatService $service): void
    {
        $this->authorizePermission('view_chat');

        $this->selectedUserId = $userId;
        $this->selectedUser = User::query()
            ->select(['id', 'name', 'email'])
            ->find($userId);

        $this->messages = $service->getMessages($userId)->toArray();
        $this->dispatch('join-room', room: $this->roomName());
        $this->dispatch('scroll-bottom');
    }

    public function send(InternalChatService $service): void
    {
        $this->authorizePermission('create_chat');

        if (! $this->selectedUserId || trim($this->message) === '') {
            return;
        }

        $service->sendMessage($this->selectedUserId, trim($this->message));
        $this->reset('message');
        $this->messages = $service->getMessages($this->selectedUserId)->toArray();
        $this->dispatch('scroll-bottom');
    }

    public function appendMessage($message): void
    {
        $this->authorizePermission('view_chat');

        if (is_string($message)) {
            $message = json_decode($message, true);
        }

        if (! is_array($message) || ! isset($message['id'])) {
            return;
        }

        if (collect($this->messages)->contains(fn ($item) => ($item['id'] ?? null) == $message['id'])) {
            return;
        }

        $this->messages[] = $message;
        $this->dispatch('scroll-bottom');
    }

    public function setOnlineUsers($users): void
    {
        $this->authorizePermission('view_chat');
        $this->onlineUsers = is_array($users) ? $users : [];
    }

    public function roomName(): ?string
    {
        if (! $this->selectedUserId) {
            return null;
        }

        $ids = [
            Auth::guard('admin')->id(),
            $this->selectedUserId,
        ];

        sort($ids);

        return "dm-{$ids[0]}-{$ids[1]}";
    }

    public function render()
    {
        $this->authorizePermission('view_chat');

        return view('Chat::livewire.chat.internal-chat-manager', [
            'users' => User::query()
                ->select(['id', 'name', 'email'])
                ->whereIn('id', $this->onlineUsers)
                ->where('id', '!=', Auth::guard('admin')->id())
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $admin = Auth::guard('admin')->user();

        abort_unless($admin, 403);
        Gate::forUser($admin)->authorize($permission);
    }
}
