<?php

namespace Modules\Request\Domain\Enums;

enum AttachmentClassification: string
{
    case Internal = 'internal';
    case Confidential = 'confidential';
}
