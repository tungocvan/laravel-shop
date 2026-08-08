<?php
namespace Modules\Admin\Services;

use App\Services\RealtimeManager;
use Modules\Admin\Models\ChatSession;
use Modules\Admin\Models\ChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatService
{
    /**
     * Gửi tin nhắn và đồng bộ sang NodeJS Realtime
     */
    public function sendMessage(array $data): ChatMessage
    {
        return DB::transaction(function () use ($data) {
            // 1. Lưu tin nhắn
            $message = ChatMessage::create([
                'chat_session_id' => $data['session_id'],
                'sender_id'       => in_array($data['sender_type'], ['user', 'admin']) ? $data['sender_id'] : null,
                'sender_type'     => $data['sender_type'],
                'message'         => $data['message'],
                'metadata'        => $data['metadata'] ?? null,
            ]);

            // 2. Cập nhật thời gian tương tác cuối cùng của Session để sort sidebar
            $message->session()->update([
                'last_message_at' => now(),
                'status' => 'open'
            ]);

            // 3. Bridge sang NodeJS Server với Security Header
            $this->broadcastToNodeJS([
                'channel' => 'chat',
                'event'   => 'MessageSent',
                'data'    => [
                    'session_id'  => $data['session_id'],
                    'message'     => $message->message,
                    'sender_type' => $message->sender_type,
                    'created_at'  => $message->created_at->format('H:i'),
                ]
            ]);

            return $message;
        });
    }

    /**
     * Khởi tạo hoặc tìm phiên chat (Dành cho Guest/User)
     */
    public function getOrCreateSession(string $token, array $guestData = []): ChatSession
    {
        return ChatSession::firstOrCreate(
            ['session_token' => $token],
            array_merge([
                'status' => 'open',
                'last_message_at' => now(),
            ], $guestData)
        );
    }

    /**
     * HTTP Bridge: Gửi dữ liệu sang NodeJS Socket Server
     */
    protected function broadcastToNodeJS(array $payload): void
    {
        if (! app(RealtimeManager::class)->enabled()) {
            return;
        }

        try {
            $url = rtrim((string) config('services.nodejs.url'), '/') . '/broadcast';

            Http::withHeaders([
                'X-Bridge-Secret' => config('services.nodejs.bridge_secret'),
            ])
            ->timeout(2)
            ->post($url, $payload);

        } catch (\Exception $e) {
            Log::error("Realtime Bridge Failure: " . $e->getMessage());
        }
    }

    public function deleteMessage($messageId): bool
    {
        return DB::transaction(function () use ($messageId) {
            $message = ChatMessage::find($messageId);
            if (!$message) return false;

            $sessionId = $message->chat_session_id;
            $message->delete(); // Xóa hẳn khỏi DB theo yêu cầu

            // Thông báo cho các bên để xóa tin nhắn trên UI
            $this->broadcastToNodeJS([
                'channel' => 'chat',
                'event'   => 'MessageDeleted',
                'data'    => [
                    'message_id' => $messageId,
                    'session_id' => $sessionId
                ]
            ]);

            return true;
        });
    }
    /**
 * Xóa sạch toàn bộ tin nhắn trong một phiên chat
 */
public function deleteAllMessages($sessionId): bool
{
    try {
        return \DB::transaction(function () use ($sessionId) {
            // 1. Xóa tất cả tin nhắn thuộc session_id này trong Database
            ChatMessage::where('chat_session_id', $sessionId)->delete();

            // 2. Bắn tín hiệu Realtime sang NodeJS để Client cập nhật UI ngay lập tức
            $this->broadcastToNodeJS([
                'channel' => 'chat',
                'event'   => 'AllMessagesDeleted',
                'data'    => [
                    'session_id' => $sessionId
                ]
            ]);

            return true;
        });
    } catch (\Exception $e) {
        \Log::error("❌ Lỗi xóa tin nhắn session {$sessionId}: " . $e->getMessage());
        return false;
    }
}

}
