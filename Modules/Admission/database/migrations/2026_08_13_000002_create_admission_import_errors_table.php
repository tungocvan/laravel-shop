<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_run_id')
                ->constrained('admission_import_runs')
                ->cascadeOnDelete();
            $table->unsignedInteger('row_number')->index();
            $table->string('error_code', 64)->index();
            $table->string('field')->nullable();
            $table->text('error_message');
            $table->string('ma_dinh_danh', 32)->nullable();
            $table->string('mhs', 100)->nullable();
            $table->string('student_name')->nullable();
            $table->json('row_snapshot')->nullable();
            $table->timestamps();

            $table->index(['import_run_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_import_errors');
    }
};
