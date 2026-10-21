<?php

namespace Modules\Finance\Services;

use InvalidArgumentException;
use Modules\Finance\Contracts\FinancialDocumentProvider;
use RuntimeException;

final class FinancialDocumentRegistry
{
    private array $providers = [];

    public function register(FinancialDocumentProvider $provider): void
    {
        $key = $provider->module().':'.$provider->documentType();

        if (isset($this->providers[$key])) {
            throw new InvalidArgumentException('Duplicate financial provider.');
        }

        $this->providers[$key] = $provider;
    }

    public function resolve(string $module, string $type): FinancialDocumentProvider
    {
        return $this->providers[$module.':'.$type]
            ?? throw new RuntimeException('Financial document provider unavailable.');
    }

    public function available(): array
    {
        return array_keys($this->providers);
    }
}
