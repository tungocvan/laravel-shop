<?php

namespace Modules\Request\Domain\Enums;

enum IdempotencyStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case FailedRetryable = 'failed_retryable';
}
