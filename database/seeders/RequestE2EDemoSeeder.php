<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Request\Database\Seeders\RequestStarterTemplateSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RequestE2EDemoSeeder extends Seeder
{
    private const PASSWORD = '12345678';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('RequestE2EDemoSeeder chỉ được phép chạy ngoài production.');
        }

        $now = now('UTC');
        $superAdmin = User::query()->where('email', 'tungocvan@gmail.com')->firstOrFail();
        $users = [
            'employee' => $this->upsertUser('tungocvan1@gmail.com', 'Từ Ngọc Vân · Nhân viên E2E', $now),
            'approver' => $this->upsertUser('vhdtshop@gmail.com', 'VHDT Shop · Người duyệt E2E', $now),
            'finance' => $this->upsertUser('vansala78@gmail.com', 'Vân Sala · Tài chính E2E', $now),
            'auditor' => $this->upsertUser('hamadaqc01@gmail.com', 'Hamada QC · Kiểm toán E2E', $now),
        ];

        $guard = (string) (Permission::query()->where('name', 'request.dashboard.view')->value('guard_name') ?? 'admin');
        $roles = [
            'requester' => $this->syncRole('Request E2E · Nhân viên', $guard, ['admin.dashboard.view', 'request.dashboard.view', 'request.instance.view-own', 'request.instance.create', 'request.instance.update-own', 'request.instance.submit', 'request.instance.cancel-own', 'request.comment.create', 'request.attachment.upload', 'request.attachment.download']),
            'approver' => $this->syncRole('Request E2E · Người duyệt', $guard, ['admin.dashboard.view', 'request.dashboard.view', 'request.instance.view-participant', 'request.task.view', 'request.task.decide', 'request.task.reassign', 'request.comment.create', 'request.attachment.download', 'request.audit.view']),
            'finance' => $this->syncRole('Request E2E · Tài chính', $guard, ['admin.dashboard.view', 'request.dashboard.view', 'request.instance.view-participant', 'request.task.view', 'request.task.decide', 'request.comment.create', 'request.attachment.download', 'request.report.view', 'request.export']),
            'auditor' => $this->syncRole('Request E2E · Kiểm toán', $guard, ['admin.dashboard.view', 'request.dashboard.view', 'request.instance.view-all', 'request.audit.view', 'request.report.view', 'request.export', 'request.operation.view']),
        ];

        $users['employee']->syncRoles([$roles['requester']]);
        $users['approver']->syncRoles([$roles['approver']]);
        $users['finance']->syncRoles([$roles['finance']]);
        $users['auditor']->syncRoles([$roles['auditor']]);
        if ($legacyApprover = User::query()->where('email', 'demo@website.test')->first()) {
            $legacyApprover->syncRoles([$roles['approver']]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->call(RequestDemoSeeder::class);

        $typeId = DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->value('id');
        if ($typeId) {
            DB::table('request_types')->where('id', $typeId)->update(['available_from' => $now, 'available_until' => $now->copy()->addDays(90), 'updated_at' => $now]);
            $versionIds = DB::table('request_type_versions')->where('request_type_id', $typeId)->pluck('id');
            foreach ($versionIds as $versionId) {
                DB::table('request_type_audiences')->updateOrInsert(['request_type_version_id' => $versionId, 'actor_type' => 'user', 'actor_id' => $users['employee']->id, 'capability' => 'create'], ['created_at' => $now, 'updated_at' => $now]);
                DB::table('request_stage_definitions')->where('request_type_version_id', $versionId)->where('stage_key', 'manager_review')->update([
                    'resolver_key' => 'fixed_users', 'resolver_config_json' => json_encode(['user_ids' => [$users['approver']->id]], JSON_THROW_ON_ERROR),
                    'sla_minutes' => 1440, 'warning_minutes_before' => 240, 'grace_minutes' => 720, 'timeout_action' => 'suspend',
                    'email_on_assignment' => true, 'email_on_decision' => true, 'email_on_sla_warning' => true, 'updated_at' => $now,
                ]);
            }
        }

        config()->set('request.settings.starter_templates_enabled', true);
        config()->set('request.settings.starter_template_actor_id', $superAdmin->id);
        config()->set('request.settings.starter_template_approver_id', $users['approver']->id);
        $this->call(RequestStarterTemplateSeeder::class);

        $this->command?->newLine();
        $this->command?->info('Request E2E local pack đã sẵn sàng.');
        $this->command?->line('Super Admin: tungocvan@gmail.com (giữ nguyên mật khẩu hiện tại)');
        $this->command?->line('Nhân viên: tungocvan1@gmail.com / '.self::PASSWORD);
        $this->command?->line('Người duyệt: vhdtshop@gmail.com / '.self::PASSWORD);
        $this->command?->line('Tài chính: vansala78@gmail.com / '.self::PASSWORD);
        $this->command?->line('Kiểm toán: hamadaqc01@gmail.com / '.self::PASSWORD);
        $this->command?->line('SLA DEMO: 24 giờ; cảnh báo trước 4 giờ; grace 12 giờ; hết grace sẽ tạm dừng.');
        $this->command?->line('Hiệu lực DEMO: 90 ngày kể từ lần seed local gần nhất; timestamp persistence dùng UTC.');
    }

    private function upsertUser(string $email, string $name, mixed $now): User
    {
        return User::query()->updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make(self::PASSWORD), 'is_active' => true, 'email_verified_at' => $now]);
    }

    private function syncRole(string $name, string $guard, array $permissionNames): Role
    {
        $role = Role::query()->firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        $permissions = Permission::query()->where('guard_name', $guard)->whereIn('name', $permissionNames)->get();
        $role->syncPermissions($permissions);

        return $role;
    }
}
