<?php

namespace Modules\Finance\Tests\Unit;

use InvalidArgumentException;
use Modules\Finance\Contracts\FinancialDocumentProvider;
use Modules\Finance\Data\FinancialDocumentSnapshot;
use Modules\Finance\Services\FinancialDocumentRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FinancialDocumentRegistryTest extends TestCase
{
    public function test_provider_registration_and_resolution(): void
    {
        $registry = new FinancialDocumentRegistry;
        $provider = new class implements FinancialDocumentProvider {
            public function module(): string { return 'Example'; }
            public function documentType(): string { return 'invoice'; }
            public function resolveForPosting(string $documentId): FinancialDocumentSnapshot
            {
                return new FinancialDocumentSnapshot('Example', 'invoice', $documentId, 'INV-1', 'receivable', 'VND', '100.00');
            }
        };

        $registry->register($provider);
        self::assertSame(['Example:invoice'], $registry->available());
        self::assertSame('Example:invoice:1', $registry->resolve('Example', 'invoice')->resolveForPosting('1')->key());

        $this->expectException(InvalidArgumentException::class);
        $registry->register($provider);
    }

    public function test_missing_provider_fails_closed(): void
    {
        $this->expectException(RuntimeException::class);
        (new FinancialDocumentRegistry)->resolve('Request', 'advance');
    }

    public function test_negative_amount_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FinancialDocumentSnapshot('Pharma', 'issue', '1', 'PX-1', 'receivable', 'VND', '-1.00');
    }
}
