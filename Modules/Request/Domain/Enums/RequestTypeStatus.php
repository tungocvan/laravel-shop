<?php

namespace Modules\Request\Domain\Enums;

enum RequestTypeStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';
}
