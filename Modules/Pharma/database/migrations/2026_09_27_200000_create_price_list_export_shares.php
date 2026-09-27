<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_price_list_export_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id');
            $table->foreignId('created_by');
            $table->foreignId('export_profile_id')->nullable();
            $table->string('storage_path', 500);
            $table->string('download_name', 255);
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('price_list_id', 'pl_export_share_list_fk')->references('id')->on('pharma_price_lists')->cascadeOnDelete();
            $table->foreign('created_by', 'pl_export_share_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('export_profile_id', 'pl_export_share_profile_fk')->references('id')->on('pharma_price_list_export_profiles')->nullOnDelete();
            $table->index(['price_list_id', 'created_at'], 'pl_export_share_list_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_price_list_export_shares');
    }
};
