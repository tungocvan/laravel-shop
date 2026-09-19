<?php

namespace Modules\System\Tests\Unit;

use PHPUnit\Framework\TestCase;

class GoogleDriveApplicationUploadContractTest extends TestCase
{
    public function test_google_drive_connection_exposes_scoped_application_upload(): void
    {
        $service = file_get_contents(dirname(__DIR__, 4).'/Modules/System/Services/Cloud/GoogleDriveConnectionService.php');

        $this->assertStringContainsString('public function uploadApplicationFile(', $service);
        $this->assertStringContainsString('$this->resolveRootFolder($token)', $service);
        $this->assertStringContainsString('$this->ensureChildFolder($token, $parentId, $folder)', $service);
        $this->assertStringContainsString("str_contains(\$folder, '/')", $service);
        $this->assertStringContainsString('?uploadType=media', $service);
        $this->assertStringContainsString('public function deleteApplicationFile(', $service);
        $this->assertStringContainsString('findChildFileIds', $service);
        $this->assertStringContainsString('deleteDriveFile', $service);
    }
}
