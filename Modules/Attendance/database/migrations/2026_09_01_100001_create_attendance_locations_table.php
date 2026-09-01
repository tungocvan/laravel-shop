<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(150);
            $table->unsignedInteger('maximum_accuracy_meters')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('check_in_enabled')->default(true);
            $table->boolean('check_out_enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'check_in_enabled']);
            $table->index(['is_active', 'check_out_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_locations');
    }
};
