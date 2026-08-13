<?php

namespace Modules\Admission\Jobs;

use App\Services\DocumentConverterService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Services\AdmissionService;

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

    public function handle(
        AdmissionService $service,
        DocumentConverterService $converter,
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $app = AdmissionApplication::find($this->id);

        if (! $app) {
            \Log::warning('JOB SKIP: not found', ['id' => $this->id]);
            return;
        }

        if ($app->status !== 'approved') {
            \Log::warning('JOB SKIP: status changed', [
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
            $data = $service->getDataForTemplate($this->id);
            $name = 'Don_' . \Str::slug($data['HoVaTenHocSinh'] ?? 'unknown', '_');

            $relativeDir = 'admission/';
            $fullDir = storage_path('app/' . $relativeDir);

            if (! is_dir($fullDir)) {
                mkdir($fullDir, 0775, true);
            }

            $wordRelative = $relativeDir . $name . '.docx';
            $pdfRelative = $relativeDir . $name . '.pdf';
            $wordFull = $fullDir . $name . '.docx';
            $pdfFull = $fullDir . $name . '.pdf';

            // PDF conversion needs a DOCX source, so create it when either output requires it.
            if (($this->generateDocx || $pdfEnabled) && ! file_exists($wordFull)) {
                $template = storage_path('app/templates/application.docx');
                $converter->generate($template, $data, $wordFull);

                if (! file_exists($wordFull)) {
                    throw new \RuntimeException('DOCX không được tạo');
                }
            }

            if ($pdfEnabled && ! file_exists($pdfFull)) {
                $convertedPdf = $converter->toPdf($wordFull, $fullDir);

                if (! $convertedPdf || ! file_exists($convertedPdf)) {
                    throw new \RuntimeException('Convert xong nhưng không thấy PDF');
                }
            }

            $updates = [];

            if ($this->generateDocx && file_exists($wordFull)) {
                $updates['word_path'] = $wordRelative;
            }

            if ($pdfEnabled && file_exists($pdfFull)) {
                $updates['pdf_path'] = $pdfRelative;
            }

            if ($updates !== []) {
                $app->updateQuietly($updates);
            }

            \Log::info('Admission document job done.', [
                'id' => $this->id,
                'docx_requested' => $this->generateDocx,
                'pdf_requested' => $pdfEnabled,
                'docx_exists' => file_exists($wordFull),
                'pdf_exists' => file_exists($pdfFull),
                'batch_id' => $this->batchId,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Generate Admission document lỗi', [
                'id' => $this->id,
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
