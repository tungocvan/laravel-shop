<?php

namespace Modules\Facebook\Exceptions;

use Exception;
use Modules\Facebook\DTO\FacebookApiErrorData;

class FacebookApiException extends Exception
{
    public function __construct(public readonly FacebookApiErrorData $error)
    {
        parent::__construct($error->message, (int) ($error->code ?? 0));
    }

    public function retryable(): bool
    {
        return $this->error->retryable;
    }
}
