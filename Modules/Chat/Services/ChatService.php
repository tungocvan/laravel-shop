<?php

namespace Modules\Chat\Services;

use App\Services\RealtimeManager;
use Modules\Admin\Models\ChatSession;
use Modules\Admin\Models\ChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ChatService
{
    /**
     * Lấy hoặc tạo Session mới
     * Cải tiến: Đảm bảo gán user_id nếu khách hàng đăng nhập sau khi đã chat với tư cách Guest
     */
    public function getOrCreateSession(string $token, array $guestData = []): ChatSession
    {
        $session = ChatSession::where('session_token', $token)->first();

        if (!$session) {
            $session = ChatSession::create(array_merge([
                'session_token'   => $token,
                'status'          => 'open',
                'last_message_at' => now(),
                'user_id'         => Auth::id(), // null nếu chưa login
            ], $guestData));
        } else {
            // Nếu session đã tồn tại nhưng chưa có user_id mà nay User đã login -> Cập nhật ngay
            if (!$session->user_id && Auth::check()) {
                $session->update(['user_id' => Auth::id()]);
            }
        }

        return $session;
    }

    /**
     * Gửi tin nhắn
     * Cải tiến: Chuẩn hóa payload gửi sang NodeJS và xử lý lỗi chặt chẽ hơn
     */
    public function sendMessage(array $data): ChatMessage
    {
        return DB::transaction(function () use ($data) {
            // 1. Xác định Session ID
            $sessionId = $data['chat_session_id'] ?? $data['session_id'] ?? null;

            if (!$sessionId) {
                throw new \Exception('Missing session id');
            }

            $session = ChatSession::findOrFail($sessionId);

            // 2. Tạo tin nhắn trong Database
            $message = ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_id'       => $data['sender_id'] ?? Auth::id(), // null nếu là guest
                'sender_type'     => $data['sender_type'] ?? (Auth::check() ? 'user' : 'guest'),
                'message'         => trim($data['message'] ?? ''),
                'metadata'        => $data['metadata'] ?? null,
            ]);

            // 3. Cập nhật trạng thái Session
            $session->update([
                'last_message_at' => now(),
                'status'          => 'open',
            ]);

            // 4. Chuẩn bị Payload cho NodeJS (Khớp với logic trong chat.js)
            $payload = [
                'event'   => 'MessageSent',
                'channel' => 'session-' . $session->id, // Phát vào phòng riêng
                'data'    => [
                    'id'              => (int) $message->id,
                    'chat_session_id' => (int) $session->id,
                    'session_id'      => (int) $session->id, // Gửi cả 2 key cho chắc
                    'sender_id'       => $message->sender_id ? (int) $message->sender_id : null,
                    'sender_type'     => $message->sender_type,
                    'message'         => $message->message,
                    'created_at'      => $message->created_at->toISOString(),
                ],
            ];

            // 5. Broadcast realtime qua Bridge
            $this->broadcastToNodeJS($payload);

            return $message;
        });
    }

    /**
     * Gửi yêu cầu sang NodeJS Server
     * Cải tiến: Tăng khả năng chịu lỗi và log chi tiết
     */
    protected function broadcastToNodeJS(array $payload): void
    {
        if (! app(RealtimeManager::class)->enabled()) {
            return;
        }

        try {
            $url = rtrim((string) config('services.nodejs.url'), '/') . '/broadcast';

            $response = Http::withHeaders([
                'X-Bridge-Secret' => config('services.nodejs.bridge_secret'),
                'Content-Type'    => 'application/json',
            ])
            ->timeout(3)
            ->post($url, $payload);

            if ($response->failed()) {
                Log::warning("⚠️ Node Bridge Response Failed: " . $response->body());
            }

        } catch (\Throwable $e) {
            Log::error("❌ Node Bridge Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * Xóa tin nhắn (Realtime)
     */
    public function deleteMessage($messageId): bool
    {
        return DB::transaction(function () use ($messageId) {
            $message = ChatMessage::find($messageId);
            if (!$message) return false;

            $sessionId = $message->chat_session_id;
            $message->delete();

            $this->broadcastToNodeJS([
                'event'   => 'MessageDeleted',
                'channel' => 'session-' . $sessionId,
                'data'    => [
                    'message_id' => (int) $messageId,
                    'session_id' => (int) $sessionId,
                ]
            ]);

            return true;
        });
    }
}
