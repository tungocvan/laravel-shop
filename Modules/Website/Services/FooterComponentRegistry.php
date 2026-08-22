<?php

namespace Modules\Website\Services;

use InvalidArgumentException;

class FooterComponentRegistry
{
    public function all(): array
    {
        return (array) config('website.footer.components', []);
    }

    public function get(string $type): ?array
    {
        $component = $this->all()[$type] ?? null;

        return is_array($component) ? $component : null;
    }

    public function resolve(string $type, string $slot): array
    {
        $component = $this->get($type);

        if ($component === null) {
            throw new InvalidArgumentException("Unknown footer component type: {$type}");
        }

        if (! in_array($slot, (array) ($component['allowed_slots'] ?? []), true)) {
            throw new InvalidArgumentException("Footer component {$type} is not allowed in slot {$slot}");
        }

        $view = $component['view'] ?? null;
        if (! is_string($view) || $view === '') {
            throw new InvalidArgumentException("Footer component {$type} has no renderer");
        }

        return $component;
    }
}
