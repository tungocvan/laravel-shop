<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->string('phone')->nullable()->index();
                $table->string('avatar')->nullable();
                $table->string('account_type', 30)->default('customer')->index();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_login_at')->nullable();
                $table->string('google_id')->nullable()->unique();
                $table->text('google_token')->nullable();
                $table->string('google_refresh_token')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'phone')) {
                    $table->string('phone')->nullable()->after('email')->index();
                }
                if (! Schema::hasColumn('users', 'avatar')) {
                    $table->string('avatar')->nullable()->after('phone');
                }
                if (! Schema::hasColumn('users', 'account_type')) {
                    $table->string('account_type', 30)->default('customer')->after('avatar')->index();
                }
                if (! Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('account_type')->index();
                }
                if (! Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('is_active');
                }
                if (! Schema::hasColumn('users', 'google_id')) {
                    $table->string('google_id')->nullable()->unique()->after('last_login_at');
                }
                if (! Schema::hasColumn('users', 'google_token')) {
                    $table->text('google_token')->nullable()->after('google_id');
                }
                if (! Schema::hasColumn('users', 'google_refresh_token')) {
                    $table->string('google_refresh_token')->nullable()->after('google_token');
                }
                if (! Schema::hasColumn('users', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('employee_profiles')) {
            Schema::create('employee_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('employee_code')->nullable()->unique();
                $table->string('department')->nullable();
                $table->string('position')->nullable();
                $table->date('joined_date')->nullable();
                $table->string('work_phone')->nullable();
                $table->string('work_email')->nullable();
                $table->string('status', 30)->default('active');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['employee_code', 'status']);
            });
        }

        if (! Schema::hasTable('customer_profiles')) {
            Schema::create('customer_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('customer_code')->nullable()->unique();
                $table->string('gender', 20)->nullable();
                $table->date('birthday')->nullable();
                $table->string('address')->nullable();
                $table->string('province')->nullable();
                $table->string('district')->nullable();
                $table->string('ward')->nullable();
                $table->string('status', 30)->default('active');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['customer_code', 'status']);
                $table->index(['province', 'district', 'ward']);
            });
        }

        if (! Schema::hasTable('user_identity_profiles')) {
            Schema::create('user_identity_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('identity_type', 50)->nullable();
                $table->string('identity_number', 100)->nullable();
                $table->date('issued_date')->nullable();
                $table->string('issued_place')->nullable();
                $table->string('front_image')->nullable();
                $table->string('back_image')->nullable();
                $table->string('portrait_4x6_image')->nullable();
                $table->string('tax_code', 100)->nullable();
                $table->string('tax_registered_name')->nullable();
                $table->string('tax_address')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['identity_type', 'identity_number'], 'identity_type_number_index');
                $table->index('tax_code', 'identity_tax_code_index');
            });
        }

        if (! Schema::hasTable('user_metas')) {
            Schema::create('user_metas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('key');
                $table->text('value')->nullable();
                $table->string('group_name')->default('general');
                $table->string('type')->default('text');
                $table->string('label')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'key']);
                $table->index(['group_name', 'type']);
            });
        }

        if (! Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('district')->nullable();
                $table->string('ward')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->index(['user_id', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('user_metas');
        Schema::dropIfExists('user_identity_profiles');
        Schema::dropIfExists('customer_profiles');
        Schema::dropIfExists('employee_profiles');
        Schema::dropIfExists('password_reset_tokens');
    }
};
