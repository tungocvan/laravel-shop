<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dossier_template_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('dossier_templates')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('dossier_template_items')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('allows_upload')->default(true);
            $table->boolean('allows_multiple_files')->default(true);
            $table->json('metadata_schema')->nullable();
            $table->timestamps();
            $table->unique(['template_id', 'code']);
        });

        Schema::create('dossiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('dossier_templates')->restrictOnDelete();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('version', 50)->default('1');
            $table->string('status', 50)->default('draft');
            $table->boolean('is_current')->default(true);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('dossier_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->foreignId('template_item_id')->nullable()->constrained('dossier_template_items')->nullOnDelete();
            $table->string('code');
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['dossier_id', 'code']);
        });

        Schema::create('dossier_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('dossier_items')->cascadeOnDelete();
            $table->string('kind', 30)->default('item');
            $table->string('disk', 50)->default('local');
            $table->string('path', 2000);
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('sync_status', 30)->default('local_only');
            $table->string('remote_path', 2000)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_attachments');
        Schema::dropIfExists('dossier_items');
        Schema::dropIfExists('dossiers');
        Schema::dropIfExists('dossier_template_items');
        Schema::dropIfExists('dossier_templates');
    }
};
