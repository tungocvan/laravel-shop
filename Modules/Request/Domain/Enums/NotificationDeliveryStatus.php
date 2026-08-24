<?php

namespace Modules\Request\Domain\Enums;

enum NotificationDeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
