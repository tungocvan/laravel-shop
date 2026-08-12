<?php

namespace Modules\Website\database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WebsiteDemoUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Nguyễn Minh Anh', 'email' => 'demo@website.test', 'phone' => '0901000001'],
            ['name' => 'Trần Hoàng Nam', 'email' => 'affiliate@website.test', 'phone' => '0901000002'],
            ['name' => 'Từ Ngọc Vân', 'email' => 'tungocvan@gmail.com', 'phone' => '0903971494'],
            ['name' => 'Lê Thu Hà', 'email' => 'customer2@website.test', 'phone' => '0901000003'],
        ] as $data) {
            User::withTrashed()->updateOrCreate(['email' => $data['email']], $data + [
                'password' => Hash::make('Demo@123456'),
                'email_verified_at' => now(),
                'is_active' => true,
                'deleted_at' => null,
            ]);
        }

        $this->command?->info('Tài khoản demo: demo@website.test / Demo@123456');
    }
}
