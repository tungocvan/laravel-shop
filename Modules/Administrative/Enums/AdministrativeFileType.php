<?php

namespace Modules\Administrative\Enums;

enum AdministrativeFileType: string
{
    case Submission = 'submission';
    case Result = 'result';
    case Supplement = 'supplement';
}
