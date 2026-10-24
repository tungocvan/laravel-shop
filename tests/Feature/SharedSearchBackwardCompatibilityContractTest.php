<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Blade;

final class SharedSearchBackwardCompatibilityContractTest extends TestCase
{
    public function test_default_search_still_renders_original_input_and_autocomplete_hooks(): void
    {
        $html = Blade::render('<x-search placeholder="Tìm chứng từ" input-class="custom-search-class" />');

        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('placeholder="Tìm chứng từ"', $html);
        self::assertStringContainsString('custom-search-class', $html);
        self::assertStringNotContainsString('aria-haspopup="listbox"', $html);
    }

    public function test_select_variant_renders_alpine_picker_without_changing_default_variant(): void
    {
        $html = Blade::render('<x-search variant="select" placeholder="Chọn đối tác" search-placeholder="Tìm theo tên" :options="$options" selected="17" />', [
            'options' => [['id' => '17', 'label' => 'Khách hàng mẫu']],
        ]);

        self::assertStringContainsString('Khách hàng mẫu', $html);
        self::assertStringContainsString('aria-haspopup="listbox"', $html);
        self::assertStringContainsString('Tìm theo tên', $html);
        self::assertStringNotContainsString('data-search-autocomplete-menu', $html);
    }
}
