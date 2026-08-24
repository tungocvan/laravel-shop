<?php

namespace Modules\User\Contracts;

use Modules\User\Data\UserMailMessage;

interface UserMailGateway
{
    public function sendToActive(int $userId, UserMailMessage $message): bool;
}
