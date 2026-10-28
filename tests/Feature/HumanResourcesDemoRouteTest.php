<?php
namespace Tests\Feature;
use Tests\TestCase;
final class HumanResourcesDemoRouteTest extends TestCase {
 public function test_standalone_hr_demo_renders_original_dom(): void {
  $this->get('/demo/quan-ly-nhan-su')->assertOk()->assertSee('page-dashboard')->assertSee('page-employees')->assertSee('savedData');
 }
 public function test_assets_exist(): void {
  $this->assertFileExists(public_path('demo/quan-ly-nhan-su/js/app.js'));
  $this->assertFileExists(public_path('demo/quan-ly-nhan-su/css/app.css'));
 }
}
