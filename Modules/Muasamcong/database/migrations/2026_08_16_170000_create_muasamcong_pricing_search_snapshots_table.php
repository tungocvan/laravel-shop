<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_pricing_search_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('keyword', 200);
            $table->string('keyword_normalized', 200)->index();
            $table->char('keyword_hash', 64)->unique();
            $table->longText('result_payload');
            $table->unsignedInteger('source_total')->default(0);
            $table->unsignedInteger('loaded_total')->default(0);
            $table->boolean('source_partial')->default(false);
            $table->unsignedBigInteger('searched_by')->nullable()->index();
            $table->timestamp('searched_at')->useCurrent()->index();
            $table->timestamp('last_accessed_at')->nullable()->index();
            $table->unsignedInteger('access_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_pricing_search_snapshots');
    }
};
