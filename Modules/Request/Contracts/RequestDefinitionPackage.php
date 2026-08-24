<?php

namespace Modules\Request\Contracts;

use Modules\Request\Models\RequestTypeVersion;

interface RequestDefinitionPackage
{
    public function export(RequestTypeVersion $version): array;

    public function encode(array $package): string;

    public function decode(string $json): array;

    public function validate(array $package): array;
}
