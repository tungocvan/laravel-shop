<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\File;
use Modules\Muasamcong\Services\MuasamcongConfigService;
use RuntimeException;
use Tests\TestCase;

class MuasamcongEnvironmentDoctorTest extends TestCase
{
    public function test_environment_doctor_reports_missing_keys_and_copy_ready_defaults(): void
    {
        $path = base_path('.env');
        $originalContainer = getenv('container');
        putenv('container=');

        File::shouldReceive('isFile')->once()->with($path)->andReturn(true);
        File::shouldReceive('isReadable')->once()->with($path)->andReturn(true);
        File::shouldReceive('isWritable')->once()->with($path)->andReturn(true);
        File::shouldReceive('get')->once()->with($path)->andReturn(
            "MUASAMCONG_ORIGIN=https://muasamcong.mpi.gov.vn\n".
            "MUASAMCONG_SMART_TOKEN=existing-token\n"
        );
        File::shouldReceive('exists')->once()->with('/.dockerenv')->andReturn(false);

        try {
            $status = app(MuasamcongConfigService::class)->inspectEnvironment();
        } finally {
            $originalContainer === false
                ? putenv('container')
                : putenv('container='.$originalContainer);
        }

        $this->assertFalse($status['docker']);
        $this->assertFalse($status['complete']);
        $this->assertSame(2, $status['present']);
        $this->assertArrayNotHasKey('MUASAMCONG_ORIGIN', $status['missing']);
        $this->assertArrayNotHasKey('MUASAMCONG_SMART_TOKEN', $status['missing']);
        $this->assertArrayHasKey('MUASAMCONG_TIMEOUT', $status['missing']);
        $this->assertStringContainsString('MUASAMCONG_TIMEOUT=20', $status['snippet']);
        $this->assertStringNotContainsString('existing-token', $status['snippet']);
    }

    public function test_docker_runtime_refuses_to_write_env(): void
    {
        File::shouldReceive('exists')->once()->with('/.dockerenv')->andReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không cập nhật .env bên trong Docker container');

        app(MuasamcongConfigService::class)->update([
            'MUASAMCONG_TIMEOUT' => '30',
        ]);
    }
}
