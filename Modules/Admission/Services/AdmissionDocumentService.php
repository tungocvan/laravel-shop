<?php

namespace Modules\Admission\Services;

use App\Services\DocumentConverterService;
use Illuminate\Support\Str;
use Modules\Admission\Models\AdmissionApplication;
use RuntimeException;

class AdmissionDocumentService
{
    public function __construct(
        private readonly AdmissionService $admissionService,
        private readonly DocumentConverterService $converter,
    ) {}

    public function generate(
        int|AdmissionApplication $application,
        bool $generateDocx = true,
        bool $generatePdf = false,
    ): array {
        if (! $generateDocx && ! $generatePdf) {
            throw new RuntimeException('Phải chọn ít nhất một định dạng tài liệu.');
        }

        $application = $application instanceof AdmissionApplication
            ? $application
            : AdmissionApplication::findOrFail($application);

        $data = $this->admissionService->getDataForTemplate($application->id);
        $name = 'Don_'.$application->id.'_'.Str::slug($data['HoVaTenHocSinh'] ?? 'unknown', '_');

        $relativeDir = 'admission/';
        $fullDir = storage_path('app/'.$relativeDir);

        if (! is_dir($fullDir) && ! mkdir($fullDir, 0775, true) && ! is_dir($fullDir)) {
            throw new RuntimeException('Không thể tạo thư mục tài liệu tuyển sinh.');
        }

        $wordRelative = $relativeDir.$name.'.docx';
        $pdfRelative = $relativeDir.$name.'.pdf';
        $wordFull = $fullDir.$name.'.docx';
        $pdfFull = $fullDir.$name.'.pdf';
        $wordExistedBefore = file_exists($wordFull);

        if (($generateDocx || $generatePdf) && ! $wordExistedBefore) {
            $template = storage_path('app/templates/application.docx');
            $this->converter->generate($template, $data, $wordFull);
        }

        if ($generatePdf && ! file_exists($pdfFull)) {
            $convertedPdf = $this->converter->toPdf($wordFull, $fullDir);

            if (! file_exists($convertedPdf)) {
                throw new RuntimeException('Không tạo được file PDF tuyển sinh.');
            }
        }

        $updates = [];

        if ($generateDocx && file_exists($wordFull)) {
            $updates['word_path'] = $wordRelative;
        }

        if ($generatePdf && file_exists($pdfFull)) {
            $updates['pdf_path'] = $pdfRelative;
        }

        if ($updates !== []) {
            $application->updateQuietly($updates);
        }

        if (! $generateDocx && ! $wordExistedBefore && file_exists($wordFull)) {
            @unlink($wordFull);
        }

        return [
            'word_path' => $generateDocx && file_exists($wordFull) ? $wordFull : null,
            'pdf_path' => $generatePdf && file_exists($pdfFull) ? $pdfFull : null,
            'download_name' => $name,
        ];
    }
}
