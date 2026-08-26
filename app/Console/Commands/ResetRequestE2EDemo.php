<?php

namespace App\Console\Commands;

use Database\Seeders\RequestE2EDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResetRequestE2EDemo extends Command
{
    protected $signature = 'request:e2e-reset
        {--seed : Seed lại E2E pack sau khi reset runtime của REQUEST_UI_DEMO}
        {--rebuild : Xóa toàn bộ dữ liệu các bảng Request rồi dựng lại E2E pack đầy đủ}';

    protected $description = 'Reset dữ liệu Request DEMO/E2E ngoài production';

    private const REQUEST_TABLES = [
        'request_notification_deliveries',
        'request_export_jobs',
        'request_attachments',
        'request_comments',
        'request_decisions',
        'request_task_candidates',
        'request_tasks',
        'request_audit_events',
        'request_outbox_messages',
        'request_idempotency_keys',
        'request_runs',
        'request_payload_revisions',
        'request_instances',
        'request_stage_definitions',
        'request_type_audiences',
        'request_type_versions',
        'request_types',
        'request_groups',
    ];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('request:e2e-reset không được phép chạy ở production.');

            return self::FAILURE;
        }

        if ($this->option('rebuild')) {
            if (! app()->environment(['local', 'testing'])) {
                $this->error('Tùy chọn --rebuild chỉ được phép chạy trong môi trường local/testing.');

                return self::FAILURE;
            }

            $this->deleteStoredRequestFiles();
            $this->deleteAllRequestRows();
            $this->info('Đã xóa toàn bộ dữ liệu trong các bảng Request.');

            return $this->seedE2EPack();
        }

        $typeId = DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->value('id');
        if (! $typeId) {
            $this->warn('Không tìm thấy Request type REQUEST_UI_DEMO.');

            return $this->seedIfRequested();
        }

        $instanceIds = DB::table('request_instances')->where('request_type_id', $typeId)->pluck('id');
        if ($instanceIds->isNotEmpty()) {
            DB::transaction(function () use ($instanceIds): void {
                $instancePublicIds = DB::table('request_instances')->whereIn('id', $instanceIds)->pluck('public_id');
                $runIds = DB::table('request_runs')->whereIn('request_instance_id', $instanceIds)->pluck('id');
                $runPublicIds = DB::table('request_runs')->whereIn('id', $runIds)->pluck('public_id');
                $taskIds = DB::table('request_tasks')->whereIn('request_run_id', $runIds)->pluck('id');
                $taskPublicIds = DB::table('request_tasks')->whereIn('id', $taskIds)->pluck('public_id');
                $commentIds = DB::table('request_comments')->whereIn('request_instance_id', $instanceIds)->pluck('id');

                $aggregatePublicIds = $instancePublicIds->concat($runPublicIds)->concat($taskPublicIds)->unique()->values();
                $outboxPublicIds = DB::table('request_outbox_messages')
                    ->whereIn('aggregate_public_id', $aggregatePublicIds)
                    ->pluck('public_id');

                DB::table('request_notification_deliveries')->whereIn('outbox_public_id', $outboxPublicIds)->delete();
                DB::table('request_attachments')->whereIn('request_instance_id', $instanceIds)->delete();
                DB::table('request_comments')->whereIn('id', $commentIds)->delete();
                DB::table('request_decisions')->whereIn('request_instance_id', $instanceIds)->delete();
                DB::table('request_task_candidates')->whereIn('request_task_id', $taskIds)->delete();

                if ($taskIds->isNotEmpty()) {
                    DB::table('request_tasks')->whereIn('id', $taskIds)->update([
                        'replaces_task_id' => null,
                        'replaced_by_task_id' => null,
                    ]);
                    DB::table('request_tasks')->whereIn('id', $taskIds)->delete();
                }

                DB::table('request_audit_events')->whereIn('request_instance_id', $instanceIds)->delete();
                DB::table('request_outbox_messages')->whereIn('aggregate_public_id', $aggregatePublicIds)->delete();
                DB::table('request_idempotency_keys')->whereIn('aggregate_public_id', $aggregatePublicIds)->delete();

                DB::table('request_instances')->whereIn('id', $instanceIds)->update([
                    'current_payload_revision_id' => null,
                    'current_run_id' => null,
                ]);

                DB::table('request_runs')->whereIn('id', $runIds)->delete();
                DB::table('request_payload_revisions')->whereIn('request_instance_id', $instanceIds)->delete();
                DB::table('request_instances')->whereIn('id', $instanceIds)->delete();
            }, 3);
        }

        $this->info('Đã xóa runtime dữ liệu REQUEST_UI_DEMO.');

        return $this->seedIfRequested();
    }

    private function seedIfRequested(): int
    {
        if (! $this->option('seed')) {
            return self::SUCCESS;
        }

        return $this->seedE2EPack();
    }

    private function seedE2EPack(): int
    {
        $exitCode = Artisan::call('db:seed', [
            '--class' => RequestE2EDemoSeeder::class,
            '--force' => true,
        ]);

        $this->output->write(Artisan::output());

        if ($exitCode === self::SUCCESS) {
            $this->normalizeLocalRequestStoragePermissions();
        }

        return $exitCode;
    }

    private function normalizeLocalRequestStoragePermissions(): void
    {
        if ((string) config('request.files.disk', 'local') !== 'local') {
            return;
        }

        $root = Storage::disk('local')->path('request');
        if (! is_dir($root) && ! mkdir($root, 02770, true) && ! is_dir($root)) {
            $this->warn('Không thể tạo thư mục storage riêng của Request: '.$root);

            return;
        }

        if (! function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            if (! is_writable($root)) {
                $this->warn('Storage Request chưa ghi được bởi tiến trình hiện tại: '.$root);
            }

            return;
        }

        $owner = (string) config('request.files.local_owner', 'www-data');
        $group = (string) config('request.files.local_group', 'www-data');
        if (! function_exists('posix_getpwnam') || ! function_exists('posix_getgrnam') || posix_getpwnam($owner) === false || posix_getgrnam($group) === false) {
            $this->warn("Không tìm thấy user/group {$owner}:{$group}; chưa thể chuẩn hóa quyền storage Request.");

            return;
        }

        $paths = [$root];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            if (! $item->isLink()) {
                $paths[] = $item->getPathname();
            }
        }

        $failed = false;
        foreach ($paths as $path) {
            $failed = ! @chown($path, $owner) || ! @chgrp($path, $group) || ! @chmod($path, is_dir($path) ? 02770 : 0660) || $failed;
        }

        if ($failed) {
            $this->warn('Một hoặc nhiều tệp Request chưa được chuẩn hóa quyền đầy đủ.');

            return;
        }

        $this->info("Đã chuẩn hóa quyền storage Request cho {$owner}:{$group}.");
    }

    private function deleteStoredRequestFiles(): void
    {
        foreach (['request_attachments', 'request_export_jobs'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)
                ->whereNotNull('storage_disk')
                ->whereNotNull('storage_path')
                ->get(['storage_disk', 'storage_path'])
                ->each(function (object $file): void {
                    try {
                        Storage::disk((string) $file->storage_disk)->delete((string) $file->storage_path);
                    } catch (\Throwable $exception) {
                        $this->warn('Không thể xóa file Request '.$file->storage_path.': '.$exception->getMessage());
                    }
                });
        }
    }

    private function deleteAllRequestRows(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasTable('request_tasks')) {
                DB::table('request_tasks')->update(['replaces_task_id' => null, 'replaced_by_task_id' => null]);
            }
            if (Schema::hasTable('request_instances')) {
                DB::table('request_instances')->update(['current_payload_revision_id' => null, 'current_run_id' => null]);
            }
            if (Schema::hasTable('request_types')) {
                DB::table('request_types')->update(['current_published_version_id' => null, 'active_draft_version_id' => null]);
            }
            if (Schema::hasTable('request_type_versions')) {
                DB::table('request_type_versions')->update(['created_from_version_id' => null]);
            }

            foreach (self::REQUEST_TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
