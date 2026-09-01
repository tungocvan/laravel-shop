<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('work_date');
            $table->foreignId('shift_id')->nullable()->constrained('attendance_shifts')->nullOnDelete();
            $table->string('session_key')->unique();
            $table->string('status', 30);

            $table->string('shift_code_snapshot');
            $table->string('shift_name_snapshot');
            $table->time('shift_start_time_snapshot');
            $table->time('shift_end_time_snapshot');
            $table->unsignedInteger('late_grace_minutes_snapshot');
            $table->unsignedInteger('early_leave_grace_minutes_snapshot');

            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('check_in_location_id')->nullable()->constrained('attendance_locations')->nullOnDelete();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy_meters', 8, 2)->nullable();
            $table->decimal('check_in_distance_meters', 10, 2)->nullable();
            $table->timestamp('check_in_captured_at')->nullable();
            $table->string('check_in_verification_result', 40)->nullable();

            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('check_out_location_id')->nullable()->constrained('attendance_locations')->nullOnDelete();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy_meters', 8, 2)->nullable();
            $table->decimal('check_out_distance_meters', 10, 2)->nullable();
            $table->timestamp('check_out_captured_at')->nullable();
            $table->string('check_out_verification_result', 40)->nullable();

            $table->unsignedInteger('worked_minutes')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);

            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamp('adjusted_at')->nullable();
            $table->timestamps();

            $table->index(['employee_profile_id', 'work_date']);
            $table->index(['work_date', 'status']);
            $table->index('checked_in_at');
            $table->index('checked_out_at');
            $table->index('check_in_location_id');
            $table->index('check_out_location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
