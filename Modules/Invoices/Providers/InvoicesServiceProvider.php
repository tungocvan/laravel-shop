<?php

namespace Modules\Invoices\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Console\Commands\BackupInvoiceFilesCommand;

class InvoicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/invoices.php', 'invoices');
    }

    public function boot(): void
    {
        $modulePath = dirname(__DIR__);

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackupInvoiceFilesCommand::class,
            ]);

            $this->publishes([
                $modulePath.'/config/invoices.php' => config_path('invoices.php'),
            ], 'invoices-config');

            $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
                if (! (bool) config('invoices.backup.automatic_enabled', false)) {
                    return;
                }

                $day = max(1, min(28, (int) config('invoices.backup.schedule_day', 1)));
                $time = (string) config('invoices.backup.schedule_time', '00:15');

                $schedule->command('invoices:backup-files')
                    ->monthlyOn($day, $time)
                    ->withoutOverlapping(120)
                    ->onOneServer();
            });
        }
    }
}
