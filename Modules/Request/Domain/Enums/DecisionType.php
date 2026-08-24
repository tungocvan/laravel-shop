<?php

namespace Modules\Request\Domain\Enums;

enum DecisionType: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Return = 'return';
}
