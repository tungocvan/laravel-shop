<?php

namespace Modules\User\Contracts;

use Illuminate\Notifications\Notification;

interface UserNotifier
{
    public function notify(int $userId, Notification $notification): bool;
}
