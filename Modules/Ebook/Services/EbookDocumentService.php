<?php

namespace Modules\Ebook\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookFolder;

class EbookDocumentService
{
    public function find(int $id): EbookDocument
    {
        return EbookDocument::query()->with('folder')->findOrFail($id);
    }

    public function create(array $data): EbookDocument
    {
        $payload = $this->normalizePayload($data);
        $content = (string) ($data['content'] ?? '');
        $this->assertUniqueDocument($payload['folder_id'], $payload['slug'], $payload['file_path']);

        $disk = Storage::disk($this->disk());
        if ($disk->exists($payload['file_path'])) {
            throw ValidationException::withMessages(['file_name' => 'File Markdown đã tồn tại.']);
        }

        if (! $disk->put($payload['file_path'], $content)) {
            throw ValidationException::withMessages(['content' => 'Không thể ghi file Markdown.']);
        }

        $payload['content_hash'] = hash('sha256', $content);
        $payload['file_mtime'] = $disk->lastModified($payload['file_path']);

        try {
            return DB::transaction(fn (): EbookDocument => EbookDocument::query()->create($payload));
        } catch (\Throwable $e) {
            $disk->delete($payload['file_path']);
            throw $e;
        }
    }

    public function upload(int $folderId, UploadedFile $file, array $metadata = []): EbookDocument
    {
        $extension = Str::lower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, config('ebook.ebook.allowed_extensions', ['md']), true)) {
            throw ValidationException::withMessages(['file' => 'Chỉ cho phép upload file Markdown (.md).']);
        }

        $maxKb = (int) config('ebook.ebook.upload_max_kb', 2048);
        if (($file->getSize() ?? 0) > ($maxKb * 1024)) {
            throw ValidationException::withMessages(['file' => "File vượt quá {$maxKb} KB."]);
        }

        $content = (string) file_get_contents($file->getRealPath());
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $title = trim((string) ($metadata['title'] ?? $this->extractTitle($content) ?? Str::headline($baseName)));

        return $this->create(array_merge($metadata, [
            'folder_id' => $folderId,
            'title' => $title,
            'slug' => $metadata['slug'] ?? Str::slug($baseName),
            'file_name' => Str::slug($baseName).'.md',
            'content' => $content,
        ]));
    }

    public function update(int $id, array $data): EbookDocument
    {
        $document = $this->find($id);
        $disk = Storage::disk($this->disk());
        $oldPath = $document->file_path;

        if (! $disk->exists($oldPath)) {
            throw ValidationException::withMessages(['content' => 'File Markdown hiện không tồn tại trên filesystem.']);
        }

        $currentContent = (string) $disk->get($oldPath);
        $currentHash = hash('sha256', $currentContent);
        $expectedHash = (string) ($data['expected_hash'] ?? $document->content_hash ?? '');
        if ($expectedHash !== '' && ! hash_equals($expectedHash, $currentHash)) {
            throw ValidationException::withMessages(['content' => 'File đã được thay đổi bên ngoài. Hãy tải lại trước khi lưu.']);
        }

        $payload = $this->normalizePayload(array_merge($document->toArray(), $data));
        $this->assertUniqueDocument($payload['folder_id'], $payload['slug'], $payload['file_path'], $document->id);
        $newContent = array_key_exists('content', $data) ? (string) $data['content'] : $currentContent;
        $newPath = $payload['file_path'];

        if ($oldPath !== $newPath && $disk->exists($newPath)) {
            throw ValidationException::withMessages(['file_name' => 'File Markdown đích đã tồn tại.']);
        }

        $backupPath = $oldPath.'.ebook-backup-'.Str::uuid();
        if (! $disk->copy($oldPath, $backupPath)) {
            throw ValidationException::withMessages(['content' => 'Không thể tạo bản sao an toàn trước khi lưu.']);
        }

        try {
            if ($oldPath !== $newPath && ! $disk->move($oldPath, $newPath)) {
                throw ValidationException::withMessages(['file_name' => 'Không thể di chuyển file Markdown.']);
            }

            if (! $disk->put($newPath, $newContent)) {
                throw ValidationException::withMessages(['content' => 'Không thể cập nhật file Markdown.']);
            }

            $payload['content_hash'] = hash('sha256', $newContent);
            $payload['file_mtime'] = $disk->lastModified($newPath);

            DB::transaction(fn () => $document->update($payload));
            $disk->delete($backupPath);
        } catch (\Throwable $e) {
            if ($disk->exists($newPath)) {
                $disk->delete($newPath);
            }
            if ($disk->exists($backupPath)) {
                $disk->move($backupPath, $oldPath);
            }
            throw $e;
        }

        return $document->fresh(['folder']);
    }

    public function delete(int $id): void
    {
        $document = $this->find($id);
        $disk = Storage::disk($this->disk());
        $path = $document->file_path;
        $backupPath = null;

        if ($disk->exists($path)) {
            $backupPath = $path.'.ebook-delete-'.Str::uuid();
            if (! $disk->move($path, $backupPath)) {
                throw ValidationException::withMessages(['document' => 'Không thể chuẩn bị xóa file Markdown.']);
            }
        }

        try {
            DB::transaction(fn () => $document->delete());
            if ($backupPath !== null) {
                $disk->delete($backupPath);
            }
        } catch (\Throwable $e) {
            if ($backupPath !== null && $disk->exists($backupPath)) {
                $disk->move($backupPath, $path);
            }
            throw $e;
        }
    }

    public function content(EbookDocument|int $document): string
    {
        $document = is_int($document) ? $this->find($document) : $document;
        $disk = Storage::disk($this->disk());

        if (! $disk->exists($document->file_path)) {
            throw ValidationException::withMessages(['document' => 'File Markdown không tồn tại.']);
        }

        return (string) $disk->get($document->file_path);
    }

    private function normalizePayload(array $data): array
    {
        $folderId = (int) ($data['folder_id'] ?? 0);
        $folder = EbookFolder::query()->findOrFail($folderId);
        $title = trim((string) ($data['title'] ?? ''));
        $slug = Str::slug((string) ($data['slug'] ?? $title));
        if ($title === '' || $slug === '') {
            throw ValidationException::withMessages(['title' => 'Tiêu đề tài liệu không hợp lệ.']);
        }

        $fileName = trim((string) ($data['file_name'] ?? ($slug.'.md')));
        $fileName = Str::slug(pathinfo($fileName, PATHINFO_FILENAME)).'.md';
        $folderPath = $this->folderPath($folder);
        $filePath = trim($folderPath.'/'.$fileName, '/');

        return [
            'folder_id' => $folderId,
            'title' => $title,
            'slug' => $slug,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'source_type' => 'file',
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_favorite' => (bool) ($data['is_favorite'] ?? false),
        ];
    }

    private function assertUniqueDocument(int $folderId, string $slug, string $filePath, ?int $ignoreId = null): void
    {
        $query = EbookDocument::query()->where(function ($query) use ($folderId, $slug, $filePath): void {
            $query->where(function ($q) use ($folderId, $slug): void {
                $q->where('folder_id', $folderId)->where('slug', $slug);
            })->orWhere('file_path', $filePath);
        });

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'Tài liệu hoặc đường dẫn đã tồn tại.']);
        }
    }

    private function folderPath(EbookFolder $folder): string
    {
        $segments = [$folder->slug];
        $parent = $folder->parent()->first();
        while ($parent !== null) {
            array_unshift($segments, $parent->slug);
            $parent = $parent->parent()->first();
        }

        return trim($this->root().'/'.implode('/', $segments), '/');
    }

    private function extractTitle(string $content): ?string
    {
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            if (preg_match('/^#\s+(.+)$/', trim($line), $matches) === 1) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function disk(): string
    {
        return (string) config('ebook.ebook.disk', 'local');
    }

    private function root(): string
    {
        return trim((string) config('ebook.ebook.root', 'ebooks'), '/');
    }
}
