<?php

namespace Modules\Chat\Services;

use App\Services\RealtimeManager;
use Modules\Chat\Models\InternalMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InternalChatService
{
    public function getMessages(int $userId)
    {
        $authId = Auth::guard('admin')->id();

        return InternalMessage::query()
            ->where(function ($q) use ($authId, $userId) {
                $q->where('from_id', $authId)
                    ->where('to_id', $userId);
            })
            ->orWhere(function ($q) use ($authId, $userId) {
                $q->where('from_id', $userId)
                    ->where('to_id', $authId);
            })
            ->orderBy('id')
            ->get();
    }

    public function sendMessage(int $toUserId, string $message)
    {
        $authId = Auth::guard('admin')->id();

        $chat = InternalMessage::create([
            'from_id' => $authId,
            'to_id' => $toUserId,
            'message' => $message,
        ]);

        $payload = [
            'id' => $chat->id,
            'from_id' => $chat->from_id,
            'to_id' => $chat->to_id,
            'message' => $chat->message,
            'created_at' => $chat->created_at->toISOString(),
        ];

        $this->broadcast([
            'event' => 'InternalMessageSent',
            'channel' => $this->makeRoom($authId, $toUserId),
            'data' => $payload,
        ]);

        return $chat;
    }

    public function makeRoom($a, $b): string
    {
        $ids = [$a, $b];

        sort($ids);

        return 'dm-' . $ids[0] . '-' . $ids[1];
    }

    protected function broadcast(array $payload): void
    {
        if (! app(RealtimeManager::class)->enabled()) {
            return;
        }

        try {

            $url = rtrim((string) config('services.nodejs.url'), '/') . '/broadcast';

            $response = Http::withHeaders([
                'X-Bridge-Secret' => config('services.nodejs.bridge_secret'),
                'Content-Type' => 'application/json',
            ])
                ->timeout(5)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::warning('Internal chat bridge request failed', ['status' => $response->status()]);
            }
        } catch (\Throwable $e) {

            Log::error('Internal chat bridge failed', ['message' => $e->getMessage()]);
        }
    }
    protected function broadcastToNodeJS(array $payload): void
    {
        if (! app(RealtimeManager::class)->enabled()) {
            return;
        }

        try {

            $url = rtrim((string) config('services.nodejs.url'), '/') . '/broadcast';

            $response = Http::withHeaders([
                'X-Bridge-Secret' => config('services.nodejs.bridge_secret'),
                'Content-Type' => 'application/json',
            ])
                ->timeout(5)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::warning('Node bridge request failed', ['status' => $response->status()]);
            }
        } catch (\Throwable $e) {

            Log::error('Node bridge failed', ['message' => $e->getMessage()]);
        }
    }
}
