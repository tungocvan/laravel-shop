<?php

namespace Modules\Admission\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Services\AdmissionDocumentService;

class GenerateAdmissionPdfJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    public $tries = 3;

    public function __construct(
        public int $id,
        public bool $generateDocx = true,
        public ?bool $generatePdf = null,
    ) {
        $this->onQueue('admission-documents');
    }

    public function handle(AdmissionDocumentService $documents): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $app = AdmissionApplication::find($this->id);

        if (! $app) {
            \Log::warning('Admission document job skipped: application not found.', ['id' => $this->id]);
            return;
        }

        if ($app->status !== 'approved') {
            \Log::warning('Admission document job skipped: application status changed.', [
                'id' => $this->id,
                'status' => $app->status,
            ]);
            return;
        }

        $pdfEnabled = $this->generatePdf ?? (bool) config('admission.module.enable_pdf_convert', false);

        if (! $this->generateDocx && ! $pdfEnabled) {
            return;
        }

        try {
            $documents->generate($app, $this->generateDocx, $pdfEnabled);

            \Log::info('Admission document job done.', [
                'id' => $this->id,
                'docx_requested' => $this->generateDocx,
                'pdf_requested' => $pdfEnabled,
                'batch_id' => $this->batchId,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Generate Admission document failed.', [
                'id' => $this->id,
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
