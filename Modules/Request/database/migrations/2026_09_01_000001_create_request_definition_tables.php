<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_groups', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('icon_key', 80)->nullable();
            $table->string('color_key', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
            $table->index('archived_at');
        });

        Schema::create('request_types', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_group_id')->constrained('request_groups')->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name', 180);
            $table->text('summary')->nullable();
            $table->string('status', 24)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->unsignedBigInteger('retired_by')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();
            $table->index(['request_group_id', 'status', 'sort_order']);
            $table->index(['status', 'available_from', 'available_until']);
        });

        Schema::create('request_type_versions', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_type_id')->constrained('request_types')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status', 24)->default('draft');
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->text('requester_guidance')->nullable();
            $table->json('form_schema_json');
            $table->json('policy_json');
            $table->json('presentation_json');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->char('canonical_checksum', 64)->nullable();
            $table->foreignId('created_from_version_id')->nullable()->constrained('request_type_versions')->restrictOnDelete();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('retired_by')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->unique(['request_type_id', 'version_number']);
            $table->index(['request_type_id', 'status', 'published_at']);
        });

        Schema::create('request_type_audiences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_type_version_id')->constrained('request_type_versions')->cascadeOnDelete();
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id');
            $table->string('capability', 16);
            $table->timestamps();
            $table->unique(['request_type_version_id', 'actor_type', 'actor_id', 'capability'], 'request_audience_unique');
            $table->index(['actor_type', 'actor_id', 'capability']);
        });

        Schema::create('request_stage_definitions', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_type_version_id')->constrained('request_type_versions')->cascadeOnDelete();
            $table->string('stage_key', 80);
            $table->string('name', 160);
            $table->unsignedSmallInteger('position');
            $table->string('mode', 24);
            $table->string('resolver_key', 40);
            $table->json('resolver_config_json');
            $table->text('instructions')->nullable();
            $table->boolean('allow_reassignment')->default(false);
            $table->timestamps();
            $table->unique(['request_type_version_id', 'stage_key']);
            $table->unique(['request_type_version_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_stage_definitions');
        Schema::dropIfExists('request_type_audiences');
        Schema::dropIfExists('request_type_versions');
        Schema::dropIfExists('request_types');
        Schema::dropIfExists('request_groups');
    }
};
