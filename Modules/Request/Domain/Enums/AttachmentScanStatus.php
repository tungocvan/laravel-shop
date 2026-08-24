<?php

namespace Modules\Request\Domain\Enums;

enum AttachmentScanStatus: string
{
    case Clean = 'clean';
    case Pending = 'pending';
    case Quarantined = 'quarantined';
    case Rejected = 'rejected';
}
