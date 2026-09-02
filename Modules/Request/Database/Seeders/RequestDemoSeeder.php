<?php

namespace Modules\Request\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Contracts\UserDirectory;

class RequestDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (config('request.settings.demo_seeders_enabled', false) !== true) {
            $this->command?->warn('RequestDemoSeeder skipped: demo seeders are disabled for this environment.');

            return;
        }

        if (app()->environment(['local', 'testing'])) {
            $this->bootstrapLocalTemplateUsers();
        }

        $this->command?->info('RequestDemoSeeder enabled: seeding Request starter templates and local workflow fixtures.');

        $this->call([
            RequestStarterTemplateSeeder::class,
            RequestOffboardingHandoverSeeder::class,
            RequestWorkflowDemoSeeder::class,
        ]);
    }

    private function bootstrapLocalTemplateUsers(): void
    {
        $directory = app(UserDirectory::class);
        $users = collect($directory->searchActive('@', 100))->values();

        if ($users->isEmpty()) {
            $this->command?->warn('RequestDemoSeeder skipped: local environment has no active user available for starter templates.');

            return;
        }

        $configuredActorId = (int) config('request.settings.starter_template_actor_id', 0);
        $actorId = $configuredActorId > 0 && $directory->findActive($configuredActorId) !== null
            ? $configuredActorId
            : (int) $users->first()->id;

        $configuredApproverId = (int) config('request.settings.starter_template_approver_id', 0);
        $approverId = $configuredApproverId > 0
            && $configuredApproverId !== $actorId
            && $directory->findActive($configuredApproverId) !== null
                ? $configuredApproverId
                : (int) ($users->first(fn ($identity): bool => (int) $identity->id !== $actorId)?->id ?? 0);

        config()->set('request.settings.starter_template_actor_id', $actorId);
        config()->set('request.settings.starter_template_approver_id', $approverId);

        $this->command?->info(sprintf(
            'RequestDemoSeeder local bootstrap: actor=%d, approver=%s.',
            $actorId,
            $approverId > 0 ? (string) $approverId : 'none',
        ));

        if ($approverId <= 0) {
            $this->command?->warn('Starter templates that require a distinct approver may be skipped because local has fewer than two active users.');
        }
    }
}
