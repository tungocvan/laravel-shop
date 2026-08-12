<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\User\Services\CustomerService;
use Modules\User\Services\UserProfileService;
use RuntimeException;
use Tests\TestCase;

class UserProfileCustomerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('wp_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wp_orders');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_profile_information_and_avatar_are_updated_and_old_avatar_is_removed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/avatars/old.jpg', 'old-avatar');
        $user = $this->user(['avatar' => 'uploads/avatars/old.jpg']);

        $updated = app(UserProfileService::class)->updateInfo($user, [
            'name' => 'Tên mới',
            'phone' => '0909123456',
        ], UploadedFile::fake()->image('avatar.png'));

        $this->assertSame('Tên mới', $updated->name);
        $this->assertSame('0909123456', $updated->phone);
        $this->assertNotSame('uploads/avatars/old.jpg', $updated->avatar);
        Storage::disk('public')->assertExists($updated->avatar);
        Storage::disk('public')->assertMissing('uploads/avatars/old.jpg');
    }

    public function test_password_requires_the_current_password_and_persists_the_new_hash(): void
    {
        $user = $this->user(['password' => Hash::make('mat-khau-cu')]);
        $service = app(UserProfileService::class);

        try {
            $service->updatePassword($user, 'sai-mat-khau', 'mat-khau-moi');
            $this->fail('Mật khẩu hiện tại sai phải bị từ chối.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Mật khẩu hiện tại không chính xác.', $exception->getMessage());
        }

        $this->assertTrue(Hash::check('mat-khau-cu', $user->fresh()->password));

        $service->updatePassword($user->fresh(), 'mat-khau-cu', 'mat-khau-moi');

        $this->assertTrue(Hash::check('mat-khau-moi', $user->fresh()->password));
    }

    public function test_customer_create_update_and_status_toggle_are_persisted(): void
    {
        $service = app(CustomerService::class);
        $customer = $service->create([
            'name' => 'Khách hàng mới',
            'email' => 'customer@example.test',
            'password' => 'secret123',
            'phone' => '0909000000',
            'is_active' => true,
        ]);

        $this->assertTrue(Hash::check('secret123', $customer->password));

        $updated = $service->update($customer->id, [
            'name' => 'Khách hàng cập nhật',
            'email' => 'updated@example.test',
            'phone' => '0911000000',
            'is_active' => true,
            'password' => 'new-secret',
        ]);

        $this->assertSame('Khách hàng cập nhật', $updated->name);
        $this->assertTrue(Hash::check('new-secret', $updated->password));

        $service->toggleStatus($customer->id);
        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_customer_search_status_filter_and_deletes_are_scoped_correctly(): void
    {
        $service = app(CustomerService::class);
        $active = $this->user(['name' => 'Nguyễn An', 'email' => 'an@example.test', 'is_active' => true]);
        $inactive = $this->user(['name' => 'Trần Bình', 'email' => 'binh@example.test', 'is_active' => false]);
        $other = $this->user(['name' => 'Lê Cường', 'email' => 'cuong@example.test', 'is_active' => true]);

        $this->assertSame([$active->id], $service->query([
            'search' => 'Nguyễn',
            'status' => 'active',
        ])->pluck('id')->all());
        $this->assertSame([$inactive->id], $service->query([
            'status' => 'inactive',
        ])->pluck('id')->all());

        $this->assertSame(2, $service->deleteMany([$active->id, $inactive->id, $active->id]));
        $this->assertSoftDeleted('users', ['id' => $active->id]);
        $this->assertSoftDeleted('users', ['id' => $inactive->id]);

        $service->delete($other->id);
        $this->assertSoftDeleted('users', ['id' => $other->id]);
    }

    private function user(array $attributes = []): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create(array_merge([
            'name' => 'Test User '.$sequence,
            'email' => "user{$sequence}@example.test",
            'password' => Hash::make('password'),
            'phone' => null,
            'avatar' => null,
            'is_active' => true,
        ], $attributes));
    }
}
