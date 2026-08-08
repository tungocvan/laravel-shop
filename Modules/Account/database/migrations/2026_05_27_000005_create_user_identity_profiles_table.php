<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_identity_profiles', function (Blueprint $table) {
            $table->id()->comment('ID hồ sơ định danh người dùng');

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Liên kết với tài khoản người dùng');

            $table->string('identity_type', 50)
                ->nullable()
                ->comment('Loại định danh: citizen_id, tax_code, passport, other');

            $table->string('identity_number', 100)
                ->nullable()
                ->comment('Số định danh: CCCD, mã số thuế, hộ chiếu hoặc mã khác');

            $table->date('issued_date')
                ->nullable()
                ->comment('Ngày cấp giấy tờ định danh');

            $table->string('issued_place')
                ->nullable()
                ->comment('Nơi cấp giấy tờ định danh');

            $table->string('front_image')
                ->nullable()
                ->comment('Ảnh mặt trước giấy tờ định danh');

            $table->string('back_image')
                ->nullable()
                ->comment('Ảnh mặt sau giấy tờ định danh');

            $table->string('portrait_4x6_image')
                ->nullable()
                ->comment('Ảnh chân dung hồ sơ 4x6');

            $table->string('tax_code', 100)
                ->nullable()
                ->comment('Mã số thuế cá nhân hoặc mã số thuế liên quan');

            $table->string('tax_registered_name')
                ->nullable()
                ->comment('Tên đăng ký thuế');

            $table->string('tax_address')
                ->nullable()
                ->comment('Địa chỉ đăng ký thuế');

            $table->text('note')
                ->nullable()
                ->comment('Ghi chú hồ sơ định danh');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['identity_type', 'identity_number'], 'user_identity_type_number_index');
            $table->index('tax_code', 'user_identity_tax_code_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_identity_profiles');
    }
};
