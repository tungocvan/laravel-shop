<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharma_commission_export_artifacts',function(Blueprint $table){
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('disk',32)->default('local');
            $table->string('storage_path');
            $table->string('download_name');
            $table->json('filters')->nullable();
            $table->json('issue_ids')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->index(['created_by','generated_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('pharma_commission_export_artifacts'); }
};
