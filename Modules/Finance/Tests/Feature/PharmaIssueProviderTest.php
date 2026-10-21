<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Integrations\Pharma\PharmaIssueProvider;
use Tests\TestCase;

final class PharmaIssueProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('pharma_inventory_issues', function (Blueprint $table): void {
            $table->id();
            $table->string('number');
            $table->string('status');
            $table->string('recipient_name')->nullable();
        });
        Schema::create('pharma_inventory_issue_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('issue_id');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 18, 4)->nullable();
        });
        config(['modules.registry.Pharma.enabled' => true]);
    }

    public function test_posted_issue_is_receivable_at_line_total(): void
    {
        $id = $this->issue('posted');
        DB::table('pharma_inventory_issue_items')->insert([
            'issue_id' => $id, 'quantity' => '2.000', 'unit_price' => '125.0000',
        ]);
        $snapshot = (new PharmaIssueProvider)->resolveForPosting((string) $id);
        $this->assertSame('Pharma:inventory_issue:'.$id, $snapshot->key());
        $this->assertSame('receivable', $snapshot->direction);
        $this->assertSame('VND', $snapshot->currency);
        $this->assertSame(250, (int) $snapshot->amount);
        $this->assertDatabaseHas('pharma_inventory_issues', ['id' => $id, 'status' => 'posted']);
    }

    public function test_draft_issue_is_rejected(): void
    {
        $id = $this->issue('draft');
        $this->expectException(ValidationException::class);
        (new PharmaIssueProvider)->resolveForPosting((string) $id);
    }

    public function test_missing_positive_price_is_rejected(): void
    {
        $id = $this->issue('posted');
        DB::table('pharma_inventory_issue_items')->insert([
            'issue_id' => $id, 'quantity' => '1.000', 'unit_price' => '0.0000',
        ]);
        $this->expectException(ValidationException::class);
        (new PharmaIssueProvider)->resolveForPosting((string) $id);
    }

    public function test_disabled_pharma_is_rejected(): void
    {
        config(['modules.registry.Pharma.enabled' => false]);
        $this->expectException(ValidationException::class);
        (new PharmaIssueProvider)->resolveForPosting('1');
    }

    private function issue(string $status): int
    {
        return DB::table('pharma_inventory_issues')->insertGetId([
            'number' => 'PX-'.uniqid(), 'status' => $status, 'recipient_name' => 'Customer',
        ]);
    }
}
