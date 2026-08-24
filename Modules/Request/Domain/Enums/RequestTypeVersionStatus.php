<?php

namespace Modules\Request\Domain\Enums;

enum RequestTypeVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Superseded = 'superseded';
    case Retired = 'retired';
}
