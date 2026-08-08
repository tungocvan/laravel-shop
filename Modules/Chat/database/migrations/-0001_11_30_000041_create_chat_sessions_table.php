<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {

        
                            $table->id();
                            $table->string('session_token')->unique()->index();
                            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
        
                            // Thông tin định danh Guest (Enterprise Grade)
                            $table->string('guest_name')->nullable();
                            $table->string('guest_phone')->nullable();
                            $table->string('guest_email')->nullable();
        
                            $table->enum('status', ['open', 'closed', 'pending'])->default('open')->index();
                            $table->timestamp('last_message_at')->nullable()->index(); // Dùng để sắp xếp Sidebar nhanh
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};