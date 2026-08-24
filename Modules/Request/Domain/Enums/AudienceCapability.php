<?php

namespace Modules\Request\Domain\Enums;

enum AudienceCapability: string
{
    case Discover = 'discover';
    case Create = 'create';
}
