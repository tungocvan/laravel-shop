<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\AffiliateLevel;
// php artisan db:seed --class="Modules\Website\database\Seeders\AffiliateLevelSeeder"
class AffiliateLevelSeeder extends Seeder
{
    public function run()
    {
        $levels = [
            ['name' => 'Bronze', 'slug' => 'bronze', 'min_revenue_required' => 0, 'is_default' => true],
            ['name' => 'Silver', 'slug' => 'silver', 'min_revenue_required' => 10000000, 'is_default' => false],
            ['name' => 'Gold', 'slug' => 'gold', 'min_revenue_required' => 50000000, 'is_default' => false],
            ['name' => 'Diamond', 'slug' => 'diamond', 'min_revenue_required' => 200000000, 'is_default' => false],
        ];

        foreach ($levels as $lv) {
            AffiliateLevel::create($lv);
        }
        
        $this->command->info('✅ Đã khởi tạo 4 cấp độ đối tác thành công!');
    }
}