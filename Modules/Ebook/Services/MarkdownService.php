<?php

namespace Modules\Ebook\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookDocument;

class MarkdownService
{
    public function render(EbookDocument $document, string $markdown): array
    {
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        [$html, $toc] = $this->decorateHeadings($html);
        $html = $this->rewriteRelativeImages($document, $html);

        return [
            'html' => $html,
            'toc' => $toc,
        ];
    }

    public function resolveAssetPath(EbookDocument $document, string $path): string
    {
        $path = rawurldecode($path);

        if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '/') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)) {
            throw ValidationException::withMessages(['asset' => 'Đường dẫn tài nguyên không hợp lệ.']);
        }

        $documentDirectory = dirname(str_replace('\\', '/', $document->file_path));
        $segments = explode('/', trim($documentDirectory.'/'.$path, '/'));
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($normalized === []) {
                    throw ValidationException::withMessages(['asset' => 'Đường dẫn tài nguyên vượt khỏi Ebook root.']);
                }
                array_pop($normalized);
                continue;
            }

            $normalized[] = $segment;
        }

        $resolved = implode('/', $normalized);
        $root = trim((string) config('ebook.ebook.root', 'ebooks'), '/');

        if ($resolved !== $root && ! str_starts_with($resolved, $root.'/')) {
            throw ValidationException::withMessages(['asset' => 'Đường dẫn tài nguyên vượt khỏi Ebook root.']);
        }

        return $resolved;
    }

    public function assetExists(EbookDocument $document, string $path): bool
    {
        return Storage::disk($this->disk())->exists($this->resolveAssetPath($document, $path));
    }

    private function decorateHeadings(string $html): array
    {
        $toc = [];
        $used = [];

        $html = preg_replace_callback('/<h([1-6])>(.*?)<\/h\1>/is', function (array $matches) use (&$toc, &$used): string {
            $level = (int) $matches[1];
            $inner = $matches[2];
            $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $base = Str::slug($text) ?: 'section';
            $used[$base] = ($used[$base] ?? 0) + 1;
            $id = $used[$base] === 1 ? $base : $base.'-'.$used[$base];

            $toc[] = [
                'level' => $level,
                'id' => $id,
                'title' => $text,
            ];

            return '<h'.$level.' id="'.e($id).'">'.$inner.'</h'.$level.'>';
        }, $html) ?? $html;

        return [$html, $toc];
    }

    private function rewriteRelativeImages(EbookDocument $document, string $html): string
    {
        return preg_replace_callback('/<img\b([^>]*?)\bsrc="([^"]+)"([^>]*)>/i', function (array $matches) use ($document): string {
            $src = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($src === '' || str_starts_with($src, '/') || str_starts_with($src, '#') || str_starts_with($src, '//') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $src)) {
                return $matches[0];
            }

            $url = route('admin.ebook.asset', [
                'document' => $document->id,
                'path' => $src,
            ]);

            return '<img'.$matches[1].'src="'.e($url).'"'.$matches[3].'>';
        }, $html) ?? $html;
    }

    private function disk(): string
    {
        return (string) config('ebook.ebook.disk', 'local');
    }
}
