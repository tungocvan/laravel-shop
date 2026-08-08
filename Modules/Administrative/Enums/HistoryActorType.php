<?php

namespace Modules\Administrative\Enums;

enum HistoryActorType: string
{
    case PublicUser = 'public';
    case Admin = 'admin';
    case System = 'system';
}
