<?php

namespace Modules\Request\Domain\Forms;

final class VisibilityRuleEvaluator
{
    private const OPERATORS = ['equals', 'not_equals', 'present', 'absent', 'in', 'not_in', 'greater_than', 'less_than'];

    public function valid(mixed $rule): bool
    {
        if ($rule === null || $rule === []) {
            return true;
        }
        if (! is_array($rule)) {
            return false;
        }
        if ($this->hasExactKeys($rule, ['all']) || $this->hasExactKeys($rule, ['any'])) {
            $children = $rule[array_key_first($rule)];

            return is_array($children) && $children !== [] && collect($children)->every(fn (mixed $child): bool => $this->valid($child));
        }
        if ($this->hasExactKeys($rule, ['not'])) {
            return $this->valid($rule['not']);
        }

        $allowedKeys = in_array($rule['operator'] ?? null, ['present', 'absent'], true) ? ['field', 'operator'] : ['field', 'operator', 'value'];

        return $this->hasExactKeys($rule, $allowedKeys)
            && is_string($rule['field'] ?? null)
            && preg_match('/^[a-z][a-z0-9_]{0,79}$/', $rule['field']) === 1
            && in_array($rule['operator'] ?? null, self::OPERATORS, true);
    }

    public function visible(mixed $rule, array $payload): bool
    {
        if ($rule === null || $rule === []) {
            return true;
        }

        if (! $this->valid($rule)) {
            return false;
        }

        if (isset($rule['all']) && is_array($rule['all'])) {
            return collect($rule['all'])->every(fn (mixed $child): bool => $this->visible($child, $payload));
        }

        if (isset($rule['any']) && is_array($rule['any'])) {
            return collect($rule['any'])->contains(fn (mixed $child): bool => $this->visible($child, $payload));
        }

        if (array_key_exists('not', $rule)) {
            return ! $this->visible($rule['not'], $payload);
        }

        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? null;
        if (! is_string($field) || ! is_string($operator)) {
            return false;
        }

        $exists = array_key_exists($field, $payload);
        $actual = $payload[$field] ?? null;
        $expected = $rule['value'] ?? null;

        return match ($operator) {
            'equals' => $exists && $actual === $expected,
            'not_equals' => ! $exists || $actual !== $expected,
            'present' => $exists && $actual !== null && $actual !== '' && $actual !== [],
            'absent' => ! $exists || $actual === null || $actual === '' || $actual === [],
            'in' => is_array($expected) && in_array($actual, $expected, true),
            'not_in' => is_array($expected) && ! in_array($actual, $expected, true),
            'greater_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'less_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            default => false,
        };
    }

    private function hasExactKeys(array $value, array $expected): bool
    {
        $keys = array_keys($value);
        sort($keys);
        sort($expected);

        return $keys === $expected;
    }
}
