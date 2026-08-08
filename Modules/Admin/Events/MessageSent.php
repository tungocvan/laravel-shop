<?php

namespace Modules\Admin\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * Payload gửi sang frontend
     */
    public array $message;

    /**
     * ID phòng chat
     */
    public int $roomId;

    /**
     * @param array $message  (message đã format sẵn cho UI)
     * @param int   $roomId
     */
    public function __construct(array $message, int $roomId)
    {
        $this->message = $message;
        $this->roomId  = $roomId;
    }

    /**
     * Channel broadcast
     */
    public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->roomId);
    }

    /**
     * Tên event cho Echo.listen()
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * Data gửi đi (Echo sẽ nhận object này)
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'room_id' => $this->roomId,
        ];
    }
}
