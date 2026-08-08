<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menus', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('url', 500)->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('can')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('admin_menus')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['parent_id', 'is_active', 'sort_order']);
        });

        $this->copyLegacyCategoryMenus();
    }

    private function copyLegacyCategoryMenus(): void
    {
        if (! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'type')) {
            return;
        }

        $columns = ['id', 'name', 'slug', 'url', 'icon', 'can', 'parent_id', 'sort_order', 'is_active', 'created_at', 'updated_at'];
        $available = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('categories', $column)));

        foreach (DB::table('categories')->where('type', 'menu')->orderBy('id')->get($available) as $menu) {
            $row = (array) $menu;
            $row['slug'] = $row['slug'] ?: 'menu-' . $row['id'];
            $row['created_at'] ??= now();
            $row['updated_at'] ??= now();
            DB::table('admin_menus')->insert($row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menus');
    }
};
