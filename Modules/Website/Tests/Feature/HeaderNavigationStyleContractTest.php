<?php
namespace Modules\Website\Tests\Feature;

use Modules\Website\Services\HeaderNavigationStyleService;
use Tests\TestCase;

class HeaderNavigationStyleContractTest extends TestCase
{
    public function test_invalid_style_values_are_rejected_and_sizes_bounded(): void
    {
        $service=new HeaderNavigationStyleService();
        $value=$service->resolve(['font_family'=>'evil','font_size'=>999,'padding_x'=>-10,'style'=>'evil','text_color'=>'red;evil','active_color'=>'#AABBCC','font_weight'=>999]);
        $this->assertSame('system',$value['font_family']);
        $this->assertSame(24,$value['font_size']);
        $this->assertSame(0,$value['padding_x']);
        $this->assertSame('classic',$value['style']);
        $this->assertSame('#334155',$value['text_color']);
        $this->assertSame('#aabbcc',$value['active_color']);
        $this->assertSame(700,$value['font_weight']);
        $this->assertSame(600,$service->resolve(['font_weight'=>'invalid'])['font_weight']);
        $this->assertStringNotContainsString('evil',$service->css($value));
    }
}