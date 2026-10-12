<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('categories'));
        $hasGlobal = $indexes->contains(fn (array $index) => $index['unique'] && $index['columns'] === ['slug']);
        $hasScoped = $indexes->contains(fn (array $index) => $index['unique'] && $index['columns'] === ['type', 'slug']);

        Schema::table('categories', function (Blueprint $table) use ($hasGlobal, $hasScoped): void {
            if ($hasGlobal) {
                $table->dropUnique(['slug']);
            }
            if (! $hasScoped) {
                $table->unique(['type', 'slug'], 'categories_type_slug_unique');
            }
        });
    }

    public function down(): void
    {
        // A global slug unique index cannot safely be restored after cross-type
        // duplicate slugs exist. Do not destructively rewrite category slugs.
    }
};
