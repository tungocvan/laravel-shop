<?php

namespace App\Services;

use Symfony\Component\Process\Process;
// use PhpOffice\PhpWord\TemplateProcessor;

class DocumentConverterService
{
    /**
     * Convert Word/Excel -> PDF
     */
    public function toPdf(string $inputPath, string $outputDir): string
    {
        $this->validateInput($inputPath);
        $this->ensureDirectory($outputDir);

        $process = $this->buildProcess($inputPath, $outputDir);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception(
                'Convert PDF lỗi: ' . $process->getErrorOutput()
            );
        }

        $pdfPath = $this->getOutputPdfPath($inputPath, $outputDir);

        if (!file_exists($pdfPath)) {
            throw new \Exception('Không tạo được file PDF');
        }

        chmod($pdfPath, 0664);

        return $pdfPath;
    }

    // =========================
    // 🔒 INTERNAL METHODS
    // =========================

    protected function validateInput(string $inputPath): void
    {
        if (!file_exists($inputPath)) {
            throw new \Exception("File không tồn tại: {$inputPath}");
        }

        $ext = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));

        if (!in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ods'])) {
            throw new \Exception("Format không hỗ trợ: {$ext}");
        }
    }

    protected function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        chmod($dir, 0775);
    }

    protected function buildProcess(string $inputPath, string $outputDir): Process
    {
        $process = new Process([
            'libreoffice',
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $outputDir,
            $inputPath
        ]);

        $process->setTimeout(120);

        // 🔥 tránh lỗi permission khi chạy queue
        $process->setEnv([
            'HOME' => $outputDir
        ]);

        return $process;
    }

    protected function getOutputPdfPath(string $inputPath, string $outputDir): string
    {
        $filename = pathinfo($inputPath, PATHINFO_FILENAME);

        return rtrim($outputDir, '/') . '/' . $filename . '.pdf';
    }
    public function generate(string $templatePath, array $data, string $outputPath): string
    {
        
        if (!file_exists($templatePath)) {
            throw new \Exception('Template không tồn tại: ' . $templatePath);
        }

        try {
            $tp = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

            foreach ($data as $key => $value) {
                $tp->setValue($key, $value ?? '');
            }

            $tp->saveAs($outputPath);
        } catch (\Throwable $e) {
            \Log::error('PHPWord ERROR', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        // 🔥 Check ngay sau khi save
        if (!file_exists($outputPath)) {
            \Log::error('FILE NOT CREATED', [
                'path' => $outputPath,
                'dir_exists' => is_dir(dirname($outputPath)),
                'dir_writable' => is_writable(dirname($outputPath)),
            ]);

            throw new \Exception('Không tạo được file DOCX');
        }

        chmod($outputPath, 0664);

    

        return $outputPath;
    }
}
