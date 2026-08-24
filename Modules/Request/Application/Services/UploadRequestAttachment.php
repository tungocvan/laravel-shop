<?php

namespace Modules\Request\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Contracts\PrivateRequestFileStore;
use Modules\Request\Domain\Enums\AttachmentClassification;
use Modules\Request\Domain\Enums\AttachmentScanStatus;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAttachment;
use ZipArchive;

final class UploadRequestAttachment
{
    private const EXTENSION_MIMES = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];

    public function __construct(private readonly PrivateRequestFileStore $files, private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(InternalRequest $request, UploadedFile $file, int $actorId, int $expectedVersion, string $idempotencyKey, ?string $fieldKey = null): RequestAttachment
    {
        $metadata = $this->validateFile($file);
        $disk = (string) config('request.files.disk', 'local');
        $storedPath = null;
        try {
            $response = DB::transaction(function () use ($request, $file, $metadata, $actorId, $expectedVersion, $idempotencyKey, $fieldKey, $disk, &$storedPath): array {
                $locked = InternalRequest::query()->with('typeVersion:id,form_schema_json')->lockForUpdate()->findOrFail($request->id);

                return $this->idempotency->execute($actorId, 'request.attachment.upload', $locked->public_id, $idempotencyKey, ['checksum' => $metadata['checksum'], 'size' => $metadata['size'], 'field_key' => $fieldKey, 'expected_version' => $expectedVersion], function (string $correlationId, string $keyHash) use ($locked, $file, $metadata, $actorId, $expectedVersion, $fieldKey, $disk, &$storedPath): array {
                    if ($locked->archived_at || in_array($locked->status, [RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Cancelled], true)) {
                        throw ValidationException::withMessages(['request' => ['attachments_not_allowed']]);
                    }
                    if ($locked->lock_version !== $expectedVersion) {
                        throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                    }
                    [$classification, $fieldLimit] = $this->fieldPolicy((array) $locked->typeVersion->form_schema_json, $fieldKey);
                    $active = $locked->attachments()->whereNull('removed_at');
                    if ((clone $active)->count() >= (int) config('request.files.max_count', 20) || ((int) (clone $active)->sum('size_bytes')) + $metadata['size'] > (int) config('request.files.max_bytes_per_request', 52428800)) {
                        throw ValidationException::withMessages(['attachment' => ['request_attachment_limit_exceeded']]);
                    }
                    if ($fieldKey !== null && (clone $active)->where('payload_field_key', $fieldKey)->count() >= $fieldLimit) {
                        throw ValidationException::withMessages(['attachment' => ['field_attachment_limit_exceeded']]);
                    }
                    $generated = (string) Str::uuid().'.'.$metadata['extension'];
                    $storedPath = $this->files->put($file, 'request/attachments/'.$locked->public_id, $generated);
                    $scanDriver = (string) config('request.files.scan_driver', 'none');
                    $scanStatus = $scanDriver === 'none' ? AttachmentScanStatus::Clean : AttachmentScanStatus::Pending;
                    $attachment = RequestAttachment::query()->create(['request_instance_id' => $locked->id, 'payload_field_key' => $fieldKey, 'uploaded_by' => $actorId, 'storage_disk' => $disk, 'storage_path' => $storedPath, 'original_filename' => $metadata['original_name'], 'generated_filename' => $generated, 'mime_type' => $metadata['mime'], 'extension' => $metadata['extension'], 'size_bytes' => $metadata['size'], 'checksum' => $metadata['checksum'], 'classification' => $classification, 'scan_status' => $scanStatus, 'scan_metadata_json' => ['driver' => $scanDriver, 'validation' => 'signature_and_package'], 'created_at' => now('UTC')]);
                    $locked->update(['lock_version' => $locked->lock_version + 1]);
                    $this->audit->append('request_instance', $locked->public_id, 'request.attachment.created.v1', $actorId, $correlationId, ['attachment_public_id' => $attachment->public_id, 'mime_type' => $attachment->mime_type, 'size_bytes' => $attachment->size_bytes, 'classification' => $attachment->classification->value], $keyHash, $locked->id);
                    $this->outbox->append('request.attachment.created.v1', 'request_instance', $locked->public_id, $correlationId, ['attachment_public_id' => $attachment->public_id]);

                    return ['attachment_public_id' => $attachment->public_id];
                });
            });
        } catch (\Throwable $exception) {
            if ($storedPath !== null) {
                $this->files->delete($disk, $storedPath);
            }

            throw $exception;
        }

        return RequestAttachment::query()->where('public_id', $response['attachment_public_id'])->firstOrFail();
    }

    private function validateFile(UploadedFile $file): array
    {
        if (! $file->isValid() || $file->getSize() <= 0 || $file->getSize() > (int) config('request.files.max_bytes', 10485760)) {
            throw ValidationException::withMessages(['attachment' => ['attachment_invalid_or_too_large']]);
        }
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        if (! isset(self::EXTENSION_MIMES[$extension]) || ! in_array($mime, self::EXTENSION_MIMES[$extension], true) || ! in_array($mime, (array) config('request.files.allowed_mimes', []), true) && $mime !== 'application/zip') {
            throw ValidationException::withMessages(['attachment' => ['attachment_type_mismatch']]);
        }
        $path = $file->getRealPath();
        $prefix = is_string($path) ? file_get_contents($path, false, null, 0, 12) : false;
        $validSignature = match ($extension) {
            'pdf' => is_string($prefix) && str_starts_with($prefix, '%PDF-'),
            'png' => is_string($prefix) && str_starts_with($prefix, "\x89PNG\r\n\x1a\n"),
            'jpg', 'jpeg' => is_string($prefix) && str_starts_with($prefix, "\xff\xd8\xff"),
            'docx', 'xlsx' => $this->validOfficePackage((string) $path, $extension),
            default => false,
        };
        if (! $validSignature) {
            throw ValidationException::withMessages(['attachment' => ['attachment_signature_invalid']]);
        }
        $original = preg_replace('/[\x00-\x1F\x7F]+/u', '', basename(str_replace('\\', '/', $file->getClientOriginalName()))) ?: 'attachment.'.$extension;

        return ['extension' => $extension, 'mime' => $extension === 'docx' ? self::EXTENSION_MIMES['docx'][0] : ($extension === 'xlsx' ? self::EXTENSION_MIMES['xlsx'][0] : $mime), 'size' => (int) $file->getSize(), 'checksum' => hash_file('sha256', (string) $path), 'original_name' => mb_substr($original, 0, 255)];
    }

    private function validOfficePackage(string $path, string $extension): bool
    {
        $zip = new ZipArchive;
        if ($path === '' || $zip->open($path) !== true || $zip->numFiles > 2000) {
            return false;
        }
        $required = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
        $found = false;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            if ($name === $required) {
                $found = true;
            }
            $lower = strtolower($name);
            if ($name === '' || str_starts_with($name, '/') || str_contains($name, '..') || str_contains($name, '\\') || str_contains($lower, 'vbaproject.bin') || preg_match('/\.(?:exe|com|bat|cmd|js|html?|svg|zip|rar|7z)$/', $lower)) {
                $zip->close();

                return false;
            }
        }
        $zip->close();

        return $found;
    }

    private function fieldPolicy(array $schema, ?string $fieldKey): array
    {
        if ($fieldKey === null) {
            return [AttachmentClassification::Internal, (int) config('request.files.max_count_per_field', 5)];
        }
        $field = collect((array) ($schema['sections'] ?? []))->flatMap(fn (array $section): array => (array) ($section['fields'] ?? []))->firstWhere('key', $fieldKey);
        if (! is_array($field) || ($field['type'] ?? null) !== 'attachment') {
            throw ValidationException::withMessages(['field_key' => ['attachment_field_invalid']]);
        }
        $classification = ($field['classification'] ?? 'internal') === 'confidential' ? AttachmentClassification::Confidential : AttachmentClassification::Internal;
        $limit = min((int) config('request.files.max_count_per_field', 5), max(1, (int) ($field['validation']['max_count'] ?? config('request.files.max_count_per_field', 5))));

        return [$classification, $limit];
    }
}
