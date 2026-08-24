<?php

namespace Modules\Request\Domain\Forms;

final class DefinitionCanonicalizer
{
    public function canonicalize(array $definition): string
    {
        $normalized = $this->normalize($definition);

        return json_encode($normalized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function checksum(array $definition): string
    {
        return hash('sha256', $this->canonicalize($definition));
    }

    private function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalize($item);
        }

        return $value;
    }
}
