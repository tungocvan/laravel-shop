<?php

namespace Modules\Partner\Contracts;

interface BusinessRegistryProvider
{
    public function source(): string;

    public function label(): string;

    public function search(string $query): array;

    public function fetchDetail(array $candidate): array;
}
