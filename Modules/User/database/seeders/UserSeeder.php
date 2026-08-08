<?php

namespace Modules\User\database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Hoặc đường dẫn tới Model User của bạn
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN'); // Sử dụng dữ liệu tiếng Việt

        // 1. Tạo 1 khách hàng mẫu để test login
        if (!User::where('email', 'khachhang@gmail.com')->exists()) {
            User::create([
                'name' => 'Nguyễn Văn Khách',
                'email' => 'khachhang@gmail.com',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]);
        }

        // 2. Tạo 10 khách hàng ngẫu nhiên
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('12345678'), // Password chung
                'email_verified_at' => now(),
            ]);
        }
    }
}
