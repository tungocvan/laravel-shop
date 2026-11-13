<?php
namespace Tests\Feature\ClientApps;
use Tests\TestCase;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Illuminate\Support\Facades\Route;
final class PartnerPwaContractTest extends TestCase
{
 public function test_partner_manifest_declares_source_permissions_and_managed_presentation(): void
 {
  $manifest=require base_path('Modules/ClientPortal/Applications/Partner/manifest.php');
  $this->assertSame('Partner',$manifest['source_module']);
  $this->assertSame('client.partners.access',$manifest['permission']);
  $this->assertSame('client.partners.index',$manifest['route']);
  foreach (['list','create','edit'] as $key) {
   $this->assertArrayHasKey($key,$manifest['features']);
   foreach (['eyebrow','page_title','page_description'] as $field) {
    $this->assertNotEmpty($manifest['features'][$key][$field]);
   }
  }
  $this->assertSame('client.partners.delete',$manifest['features']['list']['actions']['delete']['permission']);
 }
 public function test_partner_routes_use_web_auth_and_separate_mutation_permissions(): void
 {
  $routes=file_get_contents(base_path('Modules/ClientPortal/Applications/Partner/routes.php'));
  $this->assertStringContainsString("'auth:web'",$routes);
  $this->assertStringContainsString("'client.application:partners'",$routes);
  foreach (['client.partners.create','client.partners.edit','client.partners.delete'] as $permission) {
   $this->assertStringContainsString('permission:'.$permission.',web',$routes);
  }
  $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Partner/Http/Controllers/PartnerApplicationController.php'));
  $this->assertStringContainsString('PartnerService',$controller);
  $this->assertStringContainsString("featurePresentation('partners'",$controller);
  $this->assertStringNotContainsString("auth('admin')",$controller);
 }
 public function test_partner_pwa_views_keep_local_navigation_and_csrf(): void
 {
  $index=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/partners/index.blade.php'));
  $form=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/partners/form.blade.php'));
  $this->assertStringContainsString("hide-mobile-navigation",$index);
  $this->assertStringContainsString("setTimeout(submitFilters, 400)",$index);
  $this->assertStringContainsString("select.addEventListener('change'",$index);
  $this->assertStringContainsString("reset.hidden = !hasChanges()",$index);
  $this->assertStringNotContainsString('>Lọc</button>',$index);
  $this->assertStringContainsString("client.apps.index",$index);
  $this->assertStringContainsString("@csrf",$form);
  $this->assertStringContainsString("client.partners.update",$form);
 }
}
