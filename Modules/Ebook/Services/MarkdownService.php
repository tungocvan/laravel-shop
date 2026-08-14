<?php

namespace Modules\Ebook\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookDocument;

class MarkdownService
{
    public function render(EbookDocument $document, string $markdown): array
    {
        return $this->renderMarkdown($markdown, $document);
    }

    public function renderPreview(string $markdown, ?EbookDocument $document = null): array
    {
        return $this->renderMarkdown($markdown, $document);
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

    private function renderMarkdown(string $markdown, ?EbookDocument $document): array
    {
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        [$html, $toc] = $this->decorateHeadings($html);

        if ($document !== null && $document->exists) {
            $html = $this->rewriteRelativeImages($document, $html);
        }

        $html = $this->decorateCodeBlocks($html);
        $html = $this->decorateLinks($html);
        $html = $this->decorateImages($html);

        return [
            'html' => $html,
            'toc' => $toc,
        ];
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

    private function decorateCodeBlocks(string $html): string
    {
        return preg_replace_callback('/<pre><code(?: class="language-([^"]+)")?>(.*?)<\/code><\/pre>/is', function (array $matches): string {
            $language = trim((string) ($matches[1] ?? ''));
            $label = $language !== '' ? strtoupper($language) : 'CODE';
            $class = $language !== '' ? ' class="language-'.e($language).'"' : '';

            return '<div class="ebook-code-block relative my-5 overflow-hidden rounded-xl border border-slate-700 bg-slate-950 shadow-sm" x-data="{ copied: false }">'
                .'<div class="flex items-center justify-between border-b border-slate-800 px-3 py-2 text-xs font-semibold text-slate-400">'
                .'<span>'.e($label).'</span>'
                .'<button type="button" class="rounded-md border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-indigo-500 hover:text-white" '
                .'@click="navigator.clipboard.writeText($refs.code.innerText).then(() => { copied = true; setTimeout(() => copied = false, 1600) })">'
                .'<span x-show="!copied">⧉ Sao chép</span><span x-cloak x-show="copied">✓ Đã sao chép</span></button></div>'
                .'<pre class="m-0 overflow-x-auto rounded-none border-0 bg-slate-950 p-4"><code x-ref="code"'.$class.'>'.$matches[2].'</code></pre>'
                .'</div>';
        }, $html) ?? $html;
    }

    private function decorateLinks(string $html): string
    {
        return preg_replace_callback('/<a\s+href="([^"]+)"([^>]*)>(.*?)<\/a>/is', function (array $matches): string {
            $href = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $attributes = $matches[2];
            $label = $matches[3];

            if (! preg_match('/^https?:\/\//i', $href)) {
                return $matches[0];
            }

            return '<a href="'.e($href).'"'.$attributes
                .' target="_blank" rel="noopener noreferrer" class="ebook-external-link inline-flex items-baseline gap-1 font-medium text-indigo-600 underline decoration-indigo-300 underline-offset-2 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">'
                .$label.'<span class="text-[0.75em] no-underline" aria-hidden="true">↗</span><span class="sr-only"> (mở trong tab mới)</span></a>';
        }, $html) ?? $html;
    }

    private function decorateImages(string $html): string
    {
        return preg_replace_callback('/<img\b([^>]*)>/i', function (array $matches): string {
            $attributes = $matches[1];

            if (! preg_match('/\bsrc="([^"]+)"/i', $attributes, $srcMatch)) {
                return $matches[0];
            }

            $src = $srcMatch[1];
            $alt = '';
            if (preg_match('/\balt="([^"]*)"/i', $attributes, $altMatch)) {
                $alt = html_entity_decode($altMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $image = '<img'.$attributes.' loading="lazy" class="mx-auto max-h-[70vh] max-w-full cursor-zoom-in rounded-xl border border-slate-200 object-contain shadow-sm dark:border-slate-700" />';
            $caption = $alt !== ''
                ? '<span class="mt-2 block text-center text-xs leading-5 text-slate-500 dark:text-slate-400">'.e($alt).'</span>'
                : '';

            return '<span class="my-5 block" x-data="{ open: false }">'
                .'<button type="button" class="block w-full cursor-zoom-in" @click="open = true" aria-label="Phóng to hình ảnh">'.$image.'</button>'
                .$caption
                .'<span x-cloak x-show="open" x-transition.opacity @keydown.escape.window="open = false" @click.self="open = false" '
                .'class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/90 p-4 sm:p-8" role="dialog" aria-modal="true">'
                .'<button type="button" @click="open = false" class="absolute right-4 top-4 rounded-lg border border-white/20 bg-slate-900/80 px-3 py-2 text-sm font-semibold text-white shadow-lg">✕ Đóng</button>'
                .'<img src="'.e(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8')).'" alt="'.e($alt).'" class="max-h-[90vh] max-w-[94vw] rounded-xl object-contain shadow-2xl" />'
                .'</span></span>';
        }, $html) ?? $html;
    }

    private function disk(): string
    {
        return (string) config('ebook.ebook.disk', 'local');
    }
}
