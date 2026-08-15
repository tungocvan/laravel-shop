<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20)->default('automatic');
            $table->string('status', 20)->index();
            $table->string('recipient');
            $table->unsignedInteger('files_count')->default(0);
            $table->unsignedInteger('emails_sent')->default(0);
            $table->unsignedBigInteger('bytes_total')->default(0);
            $table->json('files')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_backup_runs');
    }
};
