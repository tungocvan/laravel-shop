<?php

namespace Modules\Pharma\Exceptions;

use RuntimeException;

final class AmbiguousHeaderException extends RuntimeException
{
    public function __construct(public readonly array $duplicates)
    {
        parent::__construct('Có nhiều cột trùng tiêu đề sau khi chuẩn hóa: '.implode(', ', array_keys($duplicates)));
    }
}
