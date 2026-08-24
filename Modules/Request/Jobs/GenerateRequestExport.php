<?php

namespace Modules\Request\Jobs;

use App\Modules\ModuleStateRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Request\Application\Services\GenerateRequestExport as Generator;
use Modules\Request\Models\RequestExportJob;

class GenerateRequestExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $exportPublicId)
    {
        $this->onQueue((string) config('request.exports.queue', 'request-exports'));
    }

    public function handle(Generator $generator, ModuleStateRepository $states): void
    {
        if ($states->get('Request') !== true) {
            return;
        }

        $export = RequestExportJob::query()->where('public_id', $this->exportPublicId)->first();

        if ($export === null) {
            return;
        }

        $generator->handle($export);
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
