<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Pharma\Models\OfficialFacilityImportBatch;
use Modules\Pharma\Models\OfficialFacilityImportRow;
use RuntimeException;
use Throwable;

class OfficialFacilityImportService
{
    public function __construct(
        private readonly OfficialFacilityParser $parser,
        private readonly OfficialFacilityNormalizer $normalizer,
        private readonly OfficialFacilityValidator $validator,
        private readonly OfficialFacilityMatcher $matcher,
        private readonly OfficialFacilityImportSummary $summary,
    ) {}

    public function stage(UploadedFile $file, array $context, ?int $userId = null): OfficialFacilityImportBatch
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw new RuntimeException('Chỉ hỗ trợ tệp XLSX hoặc CSV.');
        }

        $sha256 = hash_file('sha256', $file->getRealPath());
        $duplicate = OfficialFacilityImportBatch::query()->where('sha256', $sha256)->latest('id')->first();
        $storedPath = $file->store('pharma/official-facility-imports');

        $batch = OfficialFacilityImportBatch::query()->create([
            'source' => Str::lower(trim((string) $context['source'])),
            'source_date' => $context['source_date'] ?? null,
            'province_code' => trim((string) $context['province_code']),
            'source_province_code' => filled($context['source_province_code'] ?? null) ? trim((string) $context['source_province_code']) : null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'sha256' => $sha256,
            'status' => 'PROCESSING',
            'uploaded_by' => $userId,
            'started_at' => now(),
            'summary' => $duplicate ? ['duplicate_file_warning' => ['batch_id' => $duplicate->id]] : null,
        ]);

        try {
            $rows = $this->parser->parse(Storage::path($storedPath));
            $seen = [];

            foreach ($rows as $offset => $raw) {
                $normalized = $this->mapRow($raw, $batch);
                $normalized['validation_errors'] = $this->validator->validate($normalized);

                $duplicateKey = $this->duplicateKey($normalized);
                $isDuplicate = $duplicateKey !== null && isset($seen[$duplicateKey]);
                if ($duplicateKey !== null) {
                    $seen[$duplicateKey] = true;
                }

                $match = $this->matcher->match($normalized, $batch->source);

                OfficialFacilityImportRow::query()->create([
                    'batch_id' => $batch->id,
                    'row_number' => $offset + 2,
                    'external_id' => $normalized['external_id'],
                    'facility_name' => $normalized['facility_name'],
                    'normalized_name' => $normalized['normalized_name'],
                    'tax_code' => $normalized['tax_code'],
                    'address' => $normalized['address'],
                    'normalized_address' => $normalized['normalized_address'],
                    'province_code' => $normalized['province_code'],
                    'source_province_code' => $normalized['source_province_code'],
                    'phone' => $normalized['phone'],
                    'email' => $normalized['email'],
                    'raw_payload' => $raw,
                    'classification' => $match['classification'],
                    'match_method' => $match['method'] ?? null,
                    'matched_partner_id' => $match['partner_id'] ?? null,
                    'validation_errors' => $normalized['validation_errors'],
                    'match_context' => Arr::except($match, ['classification', 'partner_id', 'method']),
                    'import_status' => $isDuplicate ? 'SKIPPED_DUPLICATE' : null,
                ]);
            }

            $batch->update(['status' => 'READY', 'completed_at' => now()]);
            return $this->summary->refresh($batch);
        } catch (Throwable $exception) {
            $batch->update(['status' => 'FAILED', 'error_message' => $exception->getMessage(), 'completed_at' => now()]);
            throw $exception;
        }
    }

    private function mapRow(array $raw, OfficialFacilityImportBatch $batch): array
    {
        $lookup = collect($raw)->mapWithKeys(fn ($value, $key) => [$this->normalizer->identity((string) $key) => $value]);
        $pick = function (array $aliases) use ($lookup) {
            foreach ($aliases as $alias) {
                $key = $this->normalizer->identity($alias);
                if ($lookup->has($key) && filled($lookup->get($key))) {
                    return $lookup->get($key);
                }
            }
            return null;
        };

        $name = $this->normalizer->text((string) ($pick(['facility_name', 'ten_co_so', 'ten co so', 'tên cơ sở', 'ten_benh_vien', 'tên bệnh viện', 'name']) ?? ''));
        $address = $this->normalizer->text($pick(['address', 'dia_chi', 'địa chỉ', 'dia chi']));

        return [
            'external_id' => $this->normalizer->text($pick(['external_id', 'ma_co_so', 'mã cơ sở', 'ma cs', 'facility_code', 'code'])),
            'facility_name' => $name,
            'normalized_name' => $this->normalizer->identity($name),
            'tax_code' => $this->normalizer->taxCode($pick(['tax_code', 'ma_so_thue', 'mã số thuế', 'mst'])),
            'address' => $address,
            'normalized_address' => $this->normalizer->identity($address),
            'province_code' => $batch->province_code,
            'source_province_code' => $batch->source_province_code,
            'phone' => $this->normalizer->text($pick(['phone', 'dien_thoai', 'điện thoại', 'so dien thoai'])),
            'email' => $this->normalizer->text($pick(['email', 'e-mail'])),
        ];
    }

    private function duplicateKey(array $row): ?string
    {
        if (filled($row['external_id'] ?? null)) {
            return 'external:'.$row['external_id'];
        }

        if (filled($row['normalized_name'] ?? null) && filled($row['normalized_address'] ?? null)) {
            return 'name-address:'.$row['normalized_name'].'|'.$row['normalized_address'];
        }

        return null;
    }
}
