<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('admission_applications', 'school_campus_name')) {
                $table->string('school_campus_name')->nullable()->after('bao_mau');
            }

            if (! Schema::hasColumn('admission_applications', 'school_campus_address')) {
                $table->string('school_campus_address', 500)->nullable()->after('school_campus_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('admission_applications', 'school_campus_name') ? 'school_campus_name' : null,
                Schema::hasColumn('admission_applications', 'school_campus_address') ? 'school_campus_address' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
