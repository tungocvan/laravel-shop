<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_synced_export_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name', 120);
            $table->boolean('is_default')->default(false)->index();
            $table->json('column_order');
            $table->json('selected_columns');
            $table->json('headers')->nullable();
            $table->json('alignments')->nullable();
            $table->json('widths')->nullable();
            $table->json('data_types')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name'], 'msc_export_profiles_user_name_unique');
        });

        if (! Schema::hasTable('muasamcong_synced_export_preferences')) {
            return;
        }

        DB::table('muasamcong_synced_export_preferences')
            ->orderBy('id')
            ->get()
            ->each(function (object $preference): void {
                DB::table('muasamcong_synced_export_profiles')->insert([
                    'user_id' => $preference->user_id,
                    'name' => 'Mặc định',
                    'is_default' => true,
                    'column_order' => $preference->column_order,
                    'selected_columns' => $preference->selected_columns,
                    'headers' => null,
                    'alignments' => $preference->alignments ?? null,
                    'widths' => $preference->widths ?? null,
                    'data_types' => $preference->data_types ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_synced_export_profiles');
    }
};
