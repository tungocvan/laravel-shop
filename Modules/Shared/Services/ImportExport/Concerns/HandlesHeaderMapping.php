<?php

namespace Modules\Shared\Services\ImportExport\Concerns;

use Illuminate\Support\Str;

trait HandlesHeaderMapping
{
    protected array $headerAliases = [];

    protected function normalizeHeader(string $header): string
    {
        $header = trim(mb_strtolower($header));

        $header = str_replace(
            ['đ', 'Đ'],
            ['d', 'D'],
            $header
        );

        return Str::snake($header);
    }

    protected function resolveHeader(string $header): string
    {
        $normalized = $this->normalizeHeader($header);

        foreach ($this->headerAliases as $field => $aliases) {
            $aliases = array_map(
                fn ($alias) => $this->normalizeHeader((string) $alias),
                $aliases
            );

            if (in_array($normalized, $aliases, true)) {
                return $field;
            }
        }

        return $normalized;
    }

    protected function normalizeRowHeaders(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[$this->resolveHeader((string) $key)] = $value;
        }

        return $normalized;
    }
}
