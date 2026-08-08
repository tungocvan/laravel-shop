<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {


                            $table->id();
                            $table->string('key')->unique(); // VD: 'site_name'
                            $table->text('value')->nullable(); // VD: 'FlexBiz Store'
                            $table->string('group_name')->default('general'); // VD: 'general', 'seo', 'social'
                            $table->string('type')->default('text'); // text, image, textarea
                            $table->string('label')->nullable();
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
