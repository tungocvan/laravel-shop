<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Domain\Forms\RequestTypeDraftValidator;
use Modules\Request\Models\RequestTypeVersion;

final class ValidateTypeDraft
{
    public function __construct(private readonly RequestTypeDraftValidator $validator) {}

    public function handle(RequestTypeVersion $draft): array
    {
        return $this->validator->errors($draft->loadMissing(['audiences', 'stages']));
    }
}
