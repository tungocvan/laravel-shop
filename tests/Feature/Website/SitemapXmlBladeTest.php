<?php

namespace Tests\Feature\Website;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SitemapXmlBladeTest extends TestCase
{
    public function test_sitemap_xml_declaration_compiles_to_php_instead_of_raw_blade(): void
    {
        $source = file_get_contents(base_path('Modules/Website/resources/views/sitemap.blade.php'));

        $this->assertIsString($source);

        $compiled = Blade::compileString($source);

        $this->assertStringNotContainsString('{!!', $compiled);
        $this->assertStringContainsString('echo', $compiled);
        $this->assertStringNotContainsString('<?xml', $compiled);
    }

    public function test_sitemap_view_renders_valid_xml_declaration_and_escaped_urls(): void
    {
        $xml = view('Website::sitemap', [
            'urls' => collect([
                ['loc' => 'https://example.test/products?one=1&two=2', 'lastmod' => '2026-11-12T00:00:00+00:00'],
                ['loc' => 'https://example.test/blog', 'lastmod' => null],
            ]),
        ])->render();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('one=1&amp;two=2', $xml);
        $this->assertStringContainsString('<lastmod>2026-11-12T00:00:00+00:00</lastmod>', $xml);
        $this->assertSame(2, substr_count($xml, '<url>'));
    }
}
