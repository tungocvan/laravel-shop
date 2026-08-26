<?php

namespace Modules\Request\Domain\Forms;

final class FormDefaultValueResolver
{
    public function __construct(private readonly FormPayloadNormalizer $normalizer) {}

    public function values(array $schema): array
    {
        $values = [];

        foreach ((array) ($schema['sections'] ?? []) as $section) {
            foreach ((array) ($section['fields'] ?? []) as $field) {
                if (! is_array($field) || ! is_string($field['key'] ?? null) || ! array_key_exists('default', $field)) {
                    continue;
                }

                $value = $this->value($field);
                if ($value !== null && $value !== '' && $value !== []) {
                    $values[$field['key']] = $value;
                }
            }
        }

        return $values;
    }

    public function value(array $field): mixed
    {
        $type = (string) ($field['type'] ?? '');
        $default = $field['default'] ?? null;

        if ($type === 'date' && $default === 'today') {
            $default = now((string) config('app.timezone', 'UTC'))->toDateString();
        }

        return $this->normalizer->normalizeValue($type, $default, $field);
    }
}
