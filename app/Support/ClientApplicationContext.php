<?php

namespace App\Support;

class ClientApplicationContext
{
    private ?array $application = null;

    public function set(array $application): void
    {
        $this->application = $application;
    }

    public function current(): ?array
    {
        return $this->application;
    }

    public function key(): ?string
    {
        return $this->application['key'] ?? null;
    }

    public function clear(): void
    {
        $this->application = null;
    }
}
