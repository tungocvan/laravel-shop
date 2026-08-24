<?php

namespace Modules\Request\Domain\Forms;

final class FormFieldRegistry
{
    private const TYPES = ['text', 'textarea', 'integer', 'decimal', 'currency', 'date', 'datetime', 'boolean', 'select', 'multiselect', 'user', 'role', 'attachment', 'computed_display'];

    public function supports(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public function keys(): array
    {
        return self::TYPES;
    }
}
