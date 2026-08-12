<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Post\Models\Post;
use Modules\Product\Models\Product;

class SitemapController extends Controller
{
    private const CACHE_KEY = 'website.sitemap.xml';

    public function __invoke(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, now()->addHour(), function (): string {
            $urls = collect([
                ['loc' => route('home'), 'lastmod' => null],
                ['loc' => route('product.list'), 'lastmod' => null],
                ['loc' => route('blog.index'), 'lastmod' => null],
            ]);

            Product::query()
                ->where('is_active', true)
                ->select(['slug', 'updated_at'])
                ->orderBy('id')
                ->limit(45000)
                ->get()
                ->each(fn (Product $product) => $urls->push([
                    'loc' => route('product.detail', $product->slug),
                    'lastmod' => $product->updated_at?->toAtomString(),
                ]));

            Post::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->select(['slug', 'updated_at'])
                ->orderBy('id')
                ->limit(max(0, 49950 - $urls->count()))
                ->get()
                ->each(fn (Post $post) => $urls->push([
                    'loc' => route('blog.detail', $post->slug),
                    'lastmod' => $post->updated_at?->toAtomString(),
                ]));

            return view('Website::sitemap', compact('urls'))->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
