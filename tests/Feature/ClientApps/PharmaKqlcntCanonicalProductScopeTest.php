<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntCanonicalProductScope;
use Tests\TestCase;

class PharmaKqlcntCanonicalProductScopeTest extends TestCase
{
    private function line(int $id, string $contractor = 'C1'): object
    {
        return (object) ['id' => $id, 'notify_no' => 'TBMT-01', 'contractor_code' => $contractor];
    }

    private function link(int $id, int $sourceId, string $contractor = 'C1'): object
    {
        return (object) [
            'id' => $id, 'source_id' => $sourceId,
            'bidding_notice_code' => 'TBMT-01', 'contractor_code' => $contractor,
        ];
    }

    public function test_exact_canonical_group_resolves_only_its_product_ids(): void
    {
        $ids = app(KqlcntCanonicalProductScope::class)->resolve(
            collect([$this->line(10), $this->line(11)]),
            collect([$this->link(101, 10), $this->link(102, 11)]),
        );

        $this->assertSame([101, 102], $ids);
    }

    public function test_rejects_product_from_another_contractor_on_same_tbmt(): void
    {
        $this->expectException(ValidationException::class);

        app(KqlcntCanonicalProductScope::class)->resolve(
            collect([$this->line(10, 'C1')]),
            collect([$this->link(101, 10, 'C2')]),
        );
    }

    public function test_rejects_duplicate_source_links(): void
    {
        $this->expectException(ValidationException::class);

        app(KqlcntCanonicalProductScope::class)->resolve(
            collect([$this->line(10)]),
            collect([$this->link(101, 10), $this->link(102, 10)]),
        );
    }

    public function test_rejects_missing_product_link(): void
    {
        $this->expectException(ValidationException::class);

        app(KqlcntCanonicalProductScope::class)->resolve(
            collect([$this->line(10), $this->line(11)]),
            collect([$this->link(101, 10)]),
        );
    }

    public function test_rejects_unrelated_source_link(): void
    {
        $this->expectException(ValidationException::class);

        app(KqlcntCanonicalProductScope::class)->resolve(
            collect([$this->line(10)]),
            collect([$this->link(101, 10), $this->link(102, 11)]),
        );
    }

    public function test_rejects_empty_canonical_group(): void
    {
        $this->expectException(ValidationException::class);

        app(KqlcntCanonicalProductScope::class)->resolve(collect(), collect());
    }
}
