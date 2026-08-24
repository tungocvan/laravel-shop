<?php

namespace Modules\Request\Domain\Enums;

enum PayloadSource: string
{
    case ServerDraft = 'server_draft';
    case Submit = 'submit';
    case Resubmit = 'resubmit';
}
