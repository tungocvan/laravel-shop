<?php

namespace Modules\Admission\Jobs;

use App\Services\DocumentConverterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Services\AdmissionService;

class GenerateAdmissionPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id;
    public $timeout = 120;
    public $tries = 3;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function handle(
        AdmissionService $service,
        DocumentConverterService $converter,
    ): void {
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

            if (! file_exists($wordFull)) {
                $template = storage_path('app/templates/application.docx');
                $converter->generate($template, $data, $wordFull);

                if (! file_exists($wordFull)) {
                    throw new \RuntimeException('DOCX không được tạo');
                }
            }

            $pdfEnabled = (bool) config('admission.enable_pdf_convert', false);

            if ($pdfEnabled && ! file_exists($pdfFull)) {
                $convertedPdf = $converter->toPdf($wordFull, $fullDir);

                if (! $convertedPdf || ! file_exists($convertedPdf)) {
                    throw new \RuntimeException('Convert xong nhưng không thấy PDF');
                }
            }

            $app->updateQuietly([
                'word_path' => $wordRelative,
                'pdf_path' => $pdfEnabled && file_exists($pdfFull) ? $pdfRelative : null,
            ]);

            \Log::info('Admission document job done.', [
                'id' => $this->id,
                'word' => true,
                'pdf' => $pdfEnabled && file_exists($pdfFull),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Generate Admission document lỗi', [
                'id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
