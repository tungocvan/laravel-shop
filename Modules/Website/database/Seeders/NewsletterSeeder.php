<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsletterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 20) as $number) {
            DB::table('newsletters')->insert([
                'email' => "subscriber{$number}@website.test",
                'is_subscribed' => $number % 7 !== 0,
                'created_at' => now()->subDays($number),
                'updated_at' => now()->subDays($number),
            ]);
        }
    }
}
