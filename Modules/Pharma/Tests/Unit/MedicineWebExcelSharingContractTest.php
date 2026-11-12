<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineWebExcelSharingContractTest extends TestCase
{
    #[Test]
    public function web_sharing_is_registered_as_a_separate_managed_permission(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $module = require base_path('Modules/Pharma/config/module.php');

        $this->assertSame(
            'client.pharma.products.excel-share',
            $manifest['navigation']['products']['actions']['excel-share']['permission']
        );
        $this->assertContains('client.pharma.products.excel-share', $module['permissions_by_guard']['web']);

        $definitions = app(\Modules\ClientPortal\Services\ApplicationPermissionService::class)
            ->definitions()->pluck('name')->all();
        $this->assertContains('client.pharma.products.excel-share', $definitions);
    }

    #[Test]
    public function web_publication_and_revocation_are_scoped_to_the_current_owner(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelProfileService.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));

        $this->assertStringContainsString('function setSharedByWeb(int $userId, int $profileId, bool $shared)', $service);
        $this->assertStringContainsString("->where('owner_guard', 'web')", $service);
        $this->assertStringContainsString("->where('user_id', \$userId)", $service);
        $this->assertStringContainsString('->findOrFail($profileId)', $service);
        $this->assertStringContainsString('function canShareProfile(): bool', $component);
        $this->assertStringContainsString("client.pharma.products.excel-share", $component);
        $this->assertStringContainsString('abort_unless($this->canShareProfile(), 403)', $component);
        $this->assertStringContainsString('setSharedByWeb($ownerId, $this->profileId', $component);
        $this->assertStringContainsString('@if($canShare)', $view);
    }

    #[Test]
    public function shared_templates_are_readable_but_not_editable_in_the_web_designer(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelProfileService.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));

        $this->assertStringContainsString("->where('is_shared', true)", $service);
        $this->assertStringContainsString('function listOwnedForUser(', $service);
        $this->assertStringContainsString('function forOwner(', $service);
        $this->assertStringContainsString('->listOwnedForUser($this->assertOwner()', $component);
        $this->assertStringContainsString('->forOwner($this->assertOwner()', $component);
        $this->assertStringContainsString("->forUser((int) \$user->id", file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelController.php')));
        $this->assertStringContainsString("->where('user_id', \$userId)->findOrFail(\$id)", $service);
    }
}
