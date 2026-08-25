<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Request\Database\Seeders\RequestE2EDemoSeeder;

class ResetRequestE2EDemo extends Command
{
    protected $signature = 'request:e2e-reset {--seed : Seed lại E2E pack sau khi reset}';

    protected $description = 'Xóa runtime dữ liệu Request DEMO ngoài production và tùy chọn seed lại E2E pack';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('request:e2e-reset không được phép chạy ở production.');

            return self::FAILURE;
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

        $exitCode = Artisan::call('db:seed', [
            '--class' => RequestE2EDemoSeeder::class,
            '--force' => true,
        ]);

        $this->output->write(Artisan::output());

        return $exitCode;
    }
}
