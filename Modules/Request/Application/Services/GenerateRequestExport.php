<?php

namespace Modules\Request\Application\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Support\RequestPrivateExportStorage;
use Modules\Request\Support\SpreadsheetCellSanitizer;
use Rap2hpoutre\FastExcel\FastExcel;
use RuntimeException;
use Throwable;

final readonly class GenerateRequestExport
{
    public function __construct(
        private RequestExportQuery $query,
        private RequestPrivateExportStorage $storage,
        private SpreadsheetCellSanitizer $sanitizer,
    ) {}

    public function handle(RequestExportJob $export): RequestExportJob
    {
        if ($export->status === ExportStatus::Ready) {
            return $export;
        }

        if (! in_array($export->format, ['csv', 'xlsx', 'pdf'], true)) {
            throw new RuntimeException('REQUEST_EXPORT_FORMAT_NOT_SUPPORTED');
        }

        $export->forceFill([
            'status' => ExportStatus::Processing,
            'attempt_count' => $export->attempt_count + 1,
            'last_error_code' => null,
        ])->save();

        try {
            $disk = $this->storage->disk();
            $path = $this->storage->pathFor($export->public_id, $export->format);

            if ($export->format === 'pdf') {
                $this->writePdf($disk, $path, $export);
            } else {
                $rows = $this->rows($export);
                if ($export->format === 'csv') {
                    $this->writeCsv($disk, $path, $rows);
                } else {
                    $this->writeXlsx($disk, $path, $rows);
                }
            }

            $checksum = hash('sha256', Storage::disk($disk)->get($path));

            $export->forceFill([
                'status' => ExportStatus::Ready,
                'storage_disk' => $disk,
                'storage_path' => $path,
                'checksum' => $checksum,
                'expires_at' => now()->addHours(max(1, (int) config('request.exports.expiry_hours', 24))),
            ])->save();

            return $export->refresh();
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => ExportStatus::Failed,
                'last_error_code' => 'REQUEST_EXPORT_GENERATION_FAILED',
            ])->save();

            throw $exception;
        }
    }

    private function rows(RequestExportJob $export): iterable
    {
        $fields = $export->field_snapshot_json;
        $count = 0;

        foreach ($this->query->queryForAuthorizationScope($export->authorization_scope_json, $export->filter_snapshot_json)->lazyById(200, column: 'request_instances.id') as $request) {
            $count++;

            if ($count > (int) config('request.exports.max_rows', 100000)) {
                throw new RuntimeException('REQUEST_EXPORT_MAX_ROWS_EXCEEDED');
            }

            yield $this->sanitizer->sanitizeRow($this->mapRow($request, $fields));
        }

        $export->forceFill(['row_count' => $count])->save();
    }

    private function mapRow($request, array $fields): array
    {
        $available = [
            'request_number' => $request->request_number,
            'type_code' => $request->type?->code,
            'type_name' => $request->type?->name,
            'status' => $request->status->value,
            'title' => $request->title_snapshot,
            'requester_id' => $request->requester_id,
            'submitted_at' => $request->submitted_at?->toIso8601String(),
            'created_at' => $request->created_at?->toIso8601String(),
            'updated_at' => $request->updated_at?->toIso8601String(),
        ];

        return array_intersect_key($available, array_flip($fields));
    }

    private function writeCsv(string $disk, string $path, iterable $rows): void
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('REQUEST_EXPORT_STREAM_OPEN_FAILED');
        }

        $headerWritten = false;

        foreach ($rows as $row) {
            if (! $headerWritten) {
                fputcsv($stream, array_keys($row));
                $headerWritten = true;
            }

            fputcsv($stream, array_values($row));
        }

        rewind($stream);
        Storage::disk($disk)->put($path, $stream);
        fclose($stream);
    }

    private function writeXlsx(string $disk, string $path, iterable $rows): void
    {
        $temporary = tempnam(sys_get_temp_dir(), 'request-export-');

        if ($temporary === false) {
            throw new RuntimeException('REQUEST_EXPORT_TEMP_FILE_FAILED');
        }

        $xlsxPath = $temporary.'.xlsx';
        rename($temporary, $xlsxPath);

        try {
            (new FastExcel($rows))->export($xlsxPath);
            Storage::disk($disk)->put($path, fopen($xlsxPath, 'rb'));
        } finally {
            @unlink($xlsxPath);
        }
    }

    private function writePdf(string $disk, string $path, RequestExportJob $export): void
    {
        $request = $this->query
            ->queryForAuthorizationScope($export->authorization_scope_json, $export->filter_snapshot_json)
            ->first();

        if ($request === null || $export->row_count !== 1) {
            throw new RuntimeException('REQUEST_PDF_SCOPE_INVALID');
        }

        $data = $this->mapRow($request, $export->field_snapshot_json);
        $pdf = Pdf::loadView('Request::exports.single-request-pdf', ['data' => $data])
            ->setOption('isRemoteEnabled', false)
            ->setOption('isPhpEnabled', false);

        Storage::disk($disk)->put($path, $pdf->output());
    }
}
