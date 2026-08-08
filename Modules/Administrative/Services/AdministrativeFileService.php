<?php

namespace Modules\Administrative\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Administrative\Enums\AdministrativeFileType;
use Modules\Administrative\Models\AdministrativeProcedure;
use Modules\Administrative\Models\AdministrativeSubmission;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdministrativeFileService
{
    private const MIME_TYPES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    public function validateUploads(AdministrativeProcedure $procedure, array $uploads): void
    {
        if ($uploads === [] || count($uploads) > $procedure->max_files) {
            throw ValidationException::withMessages(['files' => "Vui lòng tải từ 1 đến {$procedure->max_files} file."]);
        }

        foreach ($uploads as $index => $upload) {
            if (! $upload instanceof UploadedFile || ! $upload->isValid()) {
                throw ValidationException::withMessages(["files.{$index}" => 'File tải lên không hợp lệ.']);
            }

            $extension = strtolower($upload->getClientOriginalExtension());
            $mime = (string) $upload->getMimeType();
            $allowed = $procedure->allowed_extensions ?: config('administrative.administrative.allowed_extensions', []);

            if (! in_array($extension, $allowed, true) || ! in_array($mime, self::MIME_TYPES[$extension] ?? [], true)) {
                throw ValidationException::withMessages(["files.{$index}" => 'Định dạng hoặc nội dung file không được chấp nhận.']);
            }

            if ($upload->getSize() > $procedure->max_file_size_kb * 1024) {
                throw ValidationException::withMessages(["files.{$index}" => 'File vượt quá dung lượng cho phép.']);
            }
        }
    }

    public function storeTemporary(array $uploads): array
    {
        $disk = (string) config('administrative.administrative.storage_disk', 'local');

        return collect($uploads)->map(function (UploadedFile $upload) use ($disk): array {
            $extension = strtolower($upload->getClientOriginalExtension());
            $storedName = Str::uuid().'.'.$extension;
            $path = $upload->storeAs('administrative/submissions/'.now()->format('Y/m'), $storedName, $disk);

            if (! $path) {
                throw ValidationException::withMessages(['files' => 'Không thể lưu file. Vui lòng thử lại.']);
            }

            return [
                'disk' => $disk,
                'path' => $path,
                'original_name' => $upload->getClientOriginalName(),
                'stored_name' => $storedName,
                'mime_type' => (string) $upload->getMimeType(),
                'extension' => $extension,
                'size' => $upload->getSize(),
                'checksum' => hash_file('sha256', $upload->getRealPath()),
            ];
        })->all();
    }

    public function attach(AdministrativeSubmission $submission, array $storedFiles, AdministrativeFileType $type = AdministrativeFileType::Submission): void
    {
        foreach ($storedFiles as $file) {
            $submission->files()->create($file + ['file_type' => $type]);
        }
    }

    public function cleanup(array $storedFiles): void
    {
        foreach ($storedFiles as $file) {
            if (Str::startsWith($file['path'] ?? '', 'administrative/submissions/') && ! Str::contains($file['path'], ['..', '\\'])) {
                Storage::disk($file['disk'])->delete($file['path']);
            }
        }
    }

    public function downloadForAdmin(int $submissionId, int $fileId): StreamedResponse
    {
        $file = AdministrativeFile::query()
            ->whereKey($fileId)
            ->where('submission_id', $submissionId)
            ->firstOrFail();

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
