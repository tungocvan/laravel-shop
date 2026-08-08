<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_posts', function (Blueprint $table) {

        
                            $table->id();
                            $table->string('name'); // Tiêu đề bài viết
                            $table->string('slug')->unique(); // URL thân thiện
                            $table->text('summary')->nullable(); // Mô tả ngắn (Sapo)
                            $table->longText('content')->nullable(); // Nội dung chính (HTML)
                            $table->string('thumbnail')->nullable(); // Ảnh đại diện
        
                            $table->boolean('is_featured')->default(false); // Bài viết nổi bật (Hot)
                            $table->string('status')->default('published'); // published, draft, hidden
        
                            $table->integer('views')->default(0); // Đếm lượt xem
        
                            // Quan hệ với User (Ai là người viết)
                            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        
                            // Thời gian đăng (Cho phép hẹn giờ đăng bài)
                            $table->timestamp('published_at')->nullable();
        
                            // SEO
                            $table->string('meta_title')->nullable();
                            $table->string('meta_description')->nullable();
        
                            $table->timestamps();
                            $table->softDeletes(); // Thùng rác
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_posts');
    }
};