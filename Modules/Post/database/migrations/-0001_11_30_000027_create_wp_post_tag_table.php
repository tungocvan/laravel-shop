<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wp_post_tag')) {
            Schema::table('wp_post_tag', function (Blueprint $table) {
                if (! $this->hasForeignKey('wp_post_tag_post_id_foreign')) {
                    $table->foreign('post_id')->references('id')->on('wp_posts')->cascadeOnDelete();
                }

                if (! $this->hasForeignKey('wp_post_tag_tag_id_foreign')) {
                    $table->foreign('tag_id')->references('id')->on('wp_tags')->cascadeOnDelete();
                }
            });

            return;
        }

        Schema::create('wp_post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('wp_posts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('wp_tags')->cascadeOnDelete();

            $table->primary(['post_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_post_tag');
    }

    private function hasForeignKey(string $constraintName): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'wp_post_tag')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->where('CONSTRAINT_NAME', $constraintName)
            ->exists();
    }
};
