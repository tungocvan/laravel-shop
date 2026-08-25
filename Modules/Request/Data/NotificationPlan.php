<?php

namespace Modules\Request\Data;

final readonly class NotificationPlan
{
    public function __construct(
        public int $recipientId,
        public string $templateKey,
        public string $requestPublicId,
        public string $requestNumber,
        public string $requestTitle,
        public string $status,
        public array $channels = ['database', 'email'],
    ) {}
}
