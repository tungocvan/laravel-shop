<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_levels', function (Blueprint $table) {

        
                    $table->id();
                    $table->string('name'); // Vàng, Kim Cương...
                    $table->string('slug')->unique();
                    $table->decimal('min_revenue_required', 15, 2)->default(0); // Doanh số tối thiểu để đạt cấp
                    $table->boolean('is_default')->default(false); // Cấp độ mặc định cho user mới
                    $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_levels');
    }
};