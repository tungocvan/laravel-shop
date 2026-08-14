<?php

namespace App\Modules;

interface ModuleStateRepository
{
    public function has(string $module): bool;

    public function get(string $module): ?bool;

    /** @return array<string, bool> */
    public function all(): array;

    public function set(string $module, bool $enabled): void;

    public function forget(string $module): void;
}
