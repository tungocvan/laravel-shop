<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Services\WebsiteDesignThemeService;

class WebsiteDesignThemeSeeder extends Seeder
{
    public function run(): void
    {
        app(WebsiteDesignThemeService::class)->restoreDefaultThemes();

        $this->command?->info('✅ WebsiteDesignThemeSeeder: Đã tạo/khôi phục 03 Website design themes demo. Custom themes hiện có được giữ nguyên.');
    }
}
