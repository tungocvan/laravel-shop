<?php

namespace Modules\Admission\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Services\AdmissionService;
use App\Services\DocumentConverterService;
//use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class GenerateAdmissionPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id;

    // 🔥 timeout + retry
    public $timeout = 120;
    public $tries = 3;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function handle(
        AdmissionService $service,
        DocumentConverterService $converter,
    ) {

        $app = AdmissionApplication::find($this->id);

        // =========================
        // 🔥 Guard
        // =========================
        if (!$app) {
            \Log::warning('JOB SKIP: not found', ['id' => $this->id]);
            return;
        }

        if ($app->status !== 'approved') {
            \Log::warning('JOB SKIP: status changed', [
                'id' => $this->id,
                'status' => $app->status
            ]);
            return;
        }

        try {

            // =========================
            // 🔥 DATA
            // =========================
            $data = $service->getDataForTemplate($this->id);

            $name = 'Don_' . \Str::slug($data['HoVaTenHocSinh'] ?? 'unknown', '_');

            $relativeDir = 'admission/';
            $fullDir = storage_path('app/' . $relativeDir);

            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0775, true);
            }

            // =========================
            // 📄 PATH
            // =========================
            $wordRelative = $relativeDir . $name . '.docx';
            $pdfRelative  = $relativeDir . $name . '.pdf';

            $wordFull = $fullDir . $name . '.docx';
            $pdfFull  = $fullDir . $name . '.pdf';

            // =========================
            // 🚀 Idempotent check
            // =========================
            if (file_exists($pdfFull)) {
                \Log::info('SKIP: PDF already exists', ['id' => $this->id]);

                $app->updateQuietly([
                    'pdf_path'  => $pdfRelative,
                    'word_path' => $wordRelative,
                ]);

                return;
            }

            // =========================
            // 📝 Generate DOCX
            // =========================
            $template = storage_path('app/templates/application.docx');

            $converter->generate($template, $data, $wordFull);

            if (!file_exists($wordFull)) {
                throw new \Exception('DOCX không được tạo');
            }

            // =========================
            // 📄 Convert PDF
            // =========================
            if (config('admission.enable_pdf_convert')) {

                $pdfFull = $converter->toPdf($wordFull, $fullDir);

                if (!file_exists($pdfFull)) {
                    throw new \Exception('Convert xong nhưng không thấy PDF');
                }
            } else {
                $pdfFull = null;
            }

            // =========================
            // 💾 Update DB
            // =========================
            $app->updateQuietly([
                'pdf_path'  => $pdfFull ? $pdfRelative : null,
                'word_path' => $wordRelative,
            ]);

            \Log::info('JOB DONE', ['id' => $this->id]);

        } catch (\Throwable $e) {

            \Log::error('Generate Admission PDF lỗi', [
                'id'    => $this->id,
                'error' => $e->getMessage(),
            ]);

            // ❗ để queue retry
            throw $e;
        }
    }
}