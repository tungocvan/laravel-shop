<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();

            $table->string('tax_code')->nullable()->unique();
            $table->string('name');
            $table->string('legal_type')->default('company');
            $table->json('partner_types')->nullable();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();

            $table->text('address')->nullable();
            $table->string('source')->default('manual');
            $table->string('status')->default('active');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('legal_type');
            $table->index('status');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
