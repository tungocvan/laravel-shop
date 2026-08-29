<?php

namespace Modules\System\Services;

use App\Services\RealtimeManager;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemRealtimeControlService
{
    public function toggle(RealtimeManager $realtime, bool $currentlyEnabled, ?int $actorId = null): bool
    {
        $target = ! $currentlyEnabled;

        Log::notice('System realtime toggle started.', [
            'actor_id' => $actorId,
            'target_enabled' => $target,
        ]);

        try {
            $realtime->setEnabled($target);

            Log::notice('System realtime toggle completed.', [
                'actor_id' => $actorId,
                'target_enabled' => $target,
            ]);

            return $target;
        } catch (Throwable $e) {
            Log::error('System realtime toggle failed.', [
                'actor_id' => $actorId,
                'target_enabled' => $target,
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }
}
