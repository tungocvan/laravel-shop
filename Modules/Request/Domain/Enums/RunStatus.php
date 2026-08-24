<?php

namespace Modules\Request\Domain\Enums;

enum RunStatus: string
{
    case Active = 'active';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case FailedActivation = 'failed_activation';
}
