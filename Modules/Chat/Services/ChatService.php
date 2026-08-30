<?php

namespace Modules\Chat\Services;

use App\Services\RealtimeManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\ChatMessage;
use Modules\Chat\Models\ChatSession;

class ChatService
{
    public function findSession(string $token, bool $withMessages = false): ?ChatSession
    {
        return ChatSession::query()
            ->when($withMessages, fn ($query) => $query->with(['messages' => fn ($messages) => $messages->orderBy('created_at')]))
            ->where('session_token', $token)
            ->first();
    }

    public function getOrCreateSession(string $token, array $guestData = []): ChatSession
    {
        $session = ChatSession::where('session_token', $token)->first();

        if (! $session) {
            $session = ChatSession::create(array_merge([
                'session_token' => $token,
                'status' => 'open',
                'last_message_at' => now(),
                'user_id' => Auth::id(),
            ], $guestData));
        } elseif (! $session->user_id && Auth::check()) {
            $session->update(['user_id' => Auth::id()]);
        }

        return $session;
    }

    public function sendMessage(array $data): ChatMessage
    {
        return DB::transaction(function () use ($data) {
            $sessionId = $data['chat_session_id'] ?? $data['session_id'] ?? null;

            if (! $sessionId) {
                throw new \InvalidArgumentException('Missing session id');
            }

            $session = ChatSession::findOrFail($sessionId);

            $message = ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_id' => $data['sender_id'] ?? Auth::id(),
                'sender_type' => $data['sender_type'] ?? (Auth::check() ? 'user' : 'guest'),
                'message' => trim($data['message'] ?? ''),
                'metadata' => $data['metadata'] ?? null,
            ]);

            $session->update([
                'last_message_at' => now(),
                'status' => 'open',
            ]);

            $this->broadcastToNodeJS([
                'event' => 'MessageSent',
                'channel' => 'session-'.$session->id,
                'data' => [
                    'id' => (int) $message->id,
                    'chat_session_id' => (int) $session->id,
                    'session_id' => (int) $session->id,
                    'sender_id' => $message->sender_id ? (int) $message->sender_id : null,
                    'sender_type' => $message->sender_type,
                    'message' => $message->message,
                    'created_at' => $message->created_at->toISOString(),
                ],
            ]);

            return $message;
        });
    }

    public function deleteMessage(int $messageId): bool
    {
        return DB::transaction(function () use ($messageId) {
            $message = ChatMessage::find($messageId);

            if (! $message) {
                return false;
            }

            $sessionId = (int) $message->chat_session_id;
            $message->delete();

            $this->broadcastToNodeJS([
                'event' => 'MessageDeleted',
                'channel' => 'session-'.$sessionId,
                'data' => [
                    'message_id' => $messageId,
                    'session_id' => $sessionId,
                ],
            ]);

            return true;
        });
    }

    public function deleteAllMessages(int $sessionId): bool
    {
        return DB::transaction(function () use ($sessionId) {
            ChatSession::query()->findOrFail($sessionId);
            ChatMessage::query()->where('chat_session_id', $sessionId)->delete();

            $this->broadcastToNodeJS([
                'event' => 'AllMessagesDeleted',
                'channel' => 'session-'.$sessionId,
                'data' => [
                    'session_id' => $sessionId,
                ],
            ]);

            return true;
        });
    }

    protected function broadcastToNodeJS(array $payload): void
    {
        if (! app(RealtimeManager::class)->enabled()) {
            return;
        }

        try {
            $url = rtrim((string) config('services.nodejs.url'), '/').'/broadcast';

            $response = Http::withHeaders([
                'X-Bridge-Secret' => config('services.nodejs.bridge_secret'),
                'Content-Type' => 'application/json',
            ])
                ->timeout(3)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::warning('Node Bridge Response Failed', [
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Node Bridge Connection Failed', [
                'exception' => $exception::class,
            ]);
        }
    }
}
