<?php

namespace Modules\Ebook\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookDocument;

class EbookSearchService
{
    public function search(string $query, int $limit = 20): Collection
    {
        $term = trim(preg_replace('/\s+/', ' ', $query) ?? '');

        if (mb_strlen($term) < 2) {
            return collect();
        }

        if (mb_strlen($term) > 100) {
            throw ValidationException::withMessages(['search' => 'Từ khóa tìm kiếm tối đa 100 ký tự.']);
        }

        $maxDocuments = max(1, (int) config('ebook.ebook.search.max_documents', 500));
        $maxFileBytes = max(1, (int) config('ebook.ebook.search.max_file_kb', 512)) * 1024;
        $maxTotalBytes = max(1, (int) config('ebook.ebook.search.max_total_kb', 10240)) * 1024;
        $disk = Storage::disk((string) config('ebook.ebook.disk', 'local'));
        $totalBytes = 0;

        $documents = app(EbookAccessService::class)
            ->visibleDocuments(auth('admin')->user())
            ->with('folder:id,name,parent_id')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit($maxDocuments)
            ->get();

        $results = [];

        foreach ($documents as $document) {
            $score = 0;
            $matched = [];
            $snippet = null;

            if ($this->contains($document->title, $term)) {
                $score += 100;
                $matched[] = 'title';
            }

            if ($this->contains($document->file_name, $term)) {
                $score += 70;
                $matched[] = 'filename';
            }

            if ($this->contains((string) $document->description, $term)) {
                $score += 50;
                $matched[] = 'description';
                $snippet = $this->snippet((string) $document->description, $term);
            }

            if ($disk->exists($document->file_path)) {
                $size = (int) $disk->size($document->file_path);

                if ($size <= $maxFileBytes && ($totalBytes + $size) <= $maxTotalBytes) {
                    $totalBytes += $size;
                    $content = (string) $disk->get($document->file_path);

                    if ($this->contains($content, $term)) {
                        $score += 30;
                        $matched[] = 'content';
                        $snippet ??= $this->snippet($content, $term);
                    }
                }
            }

            if ($score === 0) {
                continue;
            }

            $results[] = [
                'id' => (int) $document->id,
                'title' => $document->title,
                'folder' => $document->folder?->name,
                'file_name' => $document->file_name,
                'snippet' => $snippet,
                'matched' => array_values(array_unique($matched)),
                'score' => $score,
                'is_favorite' => (bool) $document->is_favorite,
            ];
        }

        return collect($results)
            ->sortByDesc(fn (array $item): int => $item['score'])
            ->take(max(1, min($limit, 50)))
            ->values();
    }

    private function contains(string $haystack, string $needle): bool
    {
        return mb_stripos($haystack, $needle) !== false;
    }

    private function snippet(string $text, string $term): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        $position = mb_stripos($plain, $term);

        if ($position === false) {
            return mb_substr($plain, 0, 180);
        }

        $start = max(0, $position - 70);
        $snippet = mb_substr($plain, $start, 180);

        return ($start > 0 ? '…' : '').$snippet.(($start + 180) < mb_strlen($plain) ? '…' : '');
    }
}
