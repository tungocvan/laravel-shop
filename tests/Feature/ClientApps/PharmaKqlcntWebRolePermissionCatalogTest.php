<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Services\ApplicationPermissionService;
use Tests\TestCase;

final class PharmaKqlcntWebRolePermissionCatalogTest extends TestCase
{
    public function test_kqlcnt_actions_are_registered_in_the_web_role_permission_catalog(): void
    {
        config()->set('modules.registry.Pharma.enabled', true);

        $definitions = app(ApplicationPermissionService::class)->applicationDefinitions()
            ->keyBy('name');

        foreach ([
            'client.pharma.kqlcnt-awards.view-all' => 'Theo dõi thầu · Xem tất cả TBMT',
            'client.pharma.kqlcnt-awards.export' => 'Theo dõi thầu · Xuất Excel KQLCNT',
            'client.pharma.kqlcnt-awards.import' => 'Theo dõi thầu · Nhập Excel KQLCNT',
            'client.pharma.kqlcnt-awards.edit' => 'Theo dõi thầu · Sửa KQLCNT',
        ] as $permission => $label) {
            $this->assertTrue($definitions->has($permission), $permission.' must appear in the Web Role catalog');
            $this->assertSame($label, $definitions->get($permission)['label']);
            $this->assertSame('kqlcnt-awards', $definitions->get($permission)['feature']);
        }

        $this->assertTrue($definitions->has('client.pharma.bid-awards.allocate'));
        $this->assertTrue($definitions->has('client.pharma.bid-awards.commercial-policy'));
    }

    public function test_navigation_does_not_own_kqlcnt_permission_actions(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');

        $this->assertArrayNotHasKey('actions', $manifest['navigation']['kqlcnt-awards']);
        $this->assertArrayHasKey('view-all', $manifest['features']['kqlcnt-awards']['actions']);
        $this->assertSame(
            'client.pharma.kqlcnt-awards.view-all',
            $manifest['features']['kqlcnt-awards']['actions']['view-all']['permission']
        );
    }
}
