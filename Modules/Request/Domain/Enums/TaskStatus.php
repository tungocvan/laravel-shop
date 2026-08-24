<?php

namespace Modules\Request\Domain\Enums;

enum TaskStatus: string
{
    case Active = 'active';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';
    case Reassigned = 'reassigned';
}
