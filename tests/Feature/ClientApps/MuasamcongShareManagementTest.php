<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\ClientPortal\Models\PublicShare;
use Tests\TestCase;

class MuasamcongShareManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('client_portal_public_shares')) {
            Schema::create('client_portal_public_shares', function (Blueprint $table): void {
                $table->id(); $table->string('token',64)->unique(); $table->unsignedBigInteger('created_by');
                $table->string('application_key'); $table->string('feature_key'); $table->uuid('source_id');
                $table->string('title'); $table->json('payload'); $table->unsignedInteger('views_count')->default(0);
                $table->timestamp('last_viewed_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamp('revoked_at')->nullable(); $table->timestamps();
            });
        }
    }

    public function test_share_management_routes_stay_inside_client_drug_pricing_feature(): void
    {
        foreach (['client.muasamcong.shares','client.muasamcong.shares.expiry','client.muasamcong.shares.revoke'] as $name) {
            $route=Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $middleware=$route->gatherMiddleware();
            $this->assertContains('auth:web',$middleware);
            $this->assertContains('client.application:muasamcong',$middleware);
            $this->assertContains('client.feature:muasamcong,drug-pricing',$middleware);
        }
    }

    public function test_public_share_model_reports_revoked_and_expired_links_unavailable(): void
    {
        $active=new PublicShare(['expires_at'=>now()->addDay()]);
        $this->assertTrue($active->isAvailable());
        $revoked=new PublicShare(['revoked_at'=>now()]);
        $this->assertFalse($revoked->isAvailable());
        $expired=new PublicShare(['expires_at'=>now()->subDay()]);
        $this->assertFalse($expired->isAvailable());
    }
}
