<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\UserCommissionWorkspace;
use ReflectionClass;
use Tests\TestCase;

final class PharmaCommissionsCapabilityTest extends TestCase
{
    public function test_commissions_route_is_read_only_and_feature_guarded(): void
    {
        if(! (bool)config('modules.registry.Pharma.enabled',false)) $this->markTestSkipped('Pharma source module is disabled.');

        $route=Route::getRoutes()->getByName('client.pharma.commissions');
        $this->assertNotNull($route);
        $this->assertSame('GET',$route->methods()[0]);
        $this->assertSame('apps/pharma/commissions',$route->uri());
        $this->assertContains('auth:web',$route->gatherMiddleware());
        $this->assertContains('client.application:pharma',$route->gatherMiddleware());
        $this->assertContains('client.feature:pharma,commissions',$route->gatherMiddleware());
        $this->assertNotContains('auth:admin',$route->gatherMiddleware());

        $show=Route::getRoutes()->getByName('client.pharma.commissions.show');
        $this->assertNotNull($show);
        $this->assertSame('GET',$show->methods()[0]);
        $this->assertSame('apps/pharma/commissions/{issue}',$show->uri());
        $this->assertContains('client.feature:pharma,commissions',$show->gatherMiddleware());

        $export=Route::getRoutes()->getByName('client.pharma.commissions.export');
        $this->assertNotNull($export);
        $this->assertSame('POST',$export->methods()[0]);
        $this->assertSame('apps/pharma/commissions/exports',$export->uri());
        $this->assertContains('auth:web',$export->gatherMiddleware());
        $this->assertContains('client.feature:pharma,commissions',$export->gatherMiddleware());

        $destroy=Route::getRoutes()->getByName('client.pharma.commissions.exports.destroy');
        $this->assertNotNull($destroy);
        $this->assertSame('DELETE',$destroy->methods()[0]);
        $this->assertSame('apps/pharma/commissions/exports/{artifact}',$destroy->uri());
        $this->assertContains('auth:web',$destroy->gatherMiddleware());
    }

    public function test_user_workspace_starts_from_canonical_user_scope_and_never_admin_scope(): void
    {
        $service=file_get_contents(base_path('Modules/Pharma/Services/UserCommissionWorkspace.php'));

        $this->assertStringContainsString('CommissionQueryService $commissions',$service);
        $this->assertStringContainsString('->userQuery($userId',$service);
        $this->assertStringContainsString('if(!$canViewTeam)',$service);
        $this->assertStringContainsString('return $this->commissions->userQuery($userId,$filters);',$service);
        $this->assertStringContainsString('return $this->commissions->adminQuery($filters);',$service);
        $this->assertStringContainsString("if(\$managerUserId!==null)",$service);
        $this->assertStringContainsString("if(\$partnerId!==null)",$service);
        $this->assertStringContainsString("where('partner_id',\$partnerId)",$service);
        $this->assertStringContainsString('public function commissionPartners(',$service);
        $this->assertStringContainsString('public function commissionMedicines(',$service);
        $this->assertStringContainsString("where('medicine_id',\$medicineId)",$service);
        $this->assertStringContainsString("SUM(revenue_amount)",$service);
        $this->assertStringContainsString("SUM(commission_amount)",$service);
        $this->assertStringContainsString("groupBy('issue_id')",$service);
        $this->assertStringContainsString("public function detail(",$service);
        $this->assertStringContainsString("->where('issue_id',\$issueId)",$service);

        $reflection=new ReflectionClass(UserCommissionWorkspace::class);
        $this->assertTrue($reflection->hasMethod('browse'));
        $this->assertTrue($reflection->hasMethod('summary'));
        $this->assertTrue($reflection->hasMethod('exportRows'));
        $this->assertTrue($reflection->hasMethod('commissionMedicines'));
    }

    public function test_client_surface_uses_authenticated_user_scope_managed_copy_and_native_load_more(): void
    {
        $controller=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $manifest=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $view=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/commissions.blade.php'));
        $detailView=file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/commission-show.blade.php'));
        $pwaSelectSearch=file_get_contents(base_path('resources/views/components/pwa-select-search.blade.php'));
        $nativeInteractions=file_get_contents(base_path('resources/js/clientportal/native-interactions.js'));

        $this->assertStringContainsString('UserCommissionWorkspace $workspace',$controller);
        $this->assertStringContainsString("'client.pharma.commissions.view'",$controller);
        $this->assertStringContainsString('$workspace->browse((int)$user->id',$controller);
        $this->assertStringContainsString('$workspace->summary((int)$user->id',$controller);
        $this->assertStringContainsString('CommissionExcelExportService $exporter',$controller);
        $this->assertStringContainsString('$workspace->exportRows((int)$user->id',$controller);
        $this->assertStringContainsString('$artifact=$exporter->generate($rows,$from,$to',$controller);
        $this->assertStringContainsString("featurePresentation(\$application['key'],\$feature)",$controller);

        $this->assertStringContainsString("'route' => 'client.pharma.commissions'",$manifest);
        $this->assertStringContainsString("'page_title' => 'Hoa hồng của tôi'",$manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.commissions.view'",$manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.commissions.view-team'",$manifest);
        $this->assertStringContainsString("\$registry->userCan(\$user,'client.pharma.commissions.view-team')",$controller);
        $this->assertStringContainsString("'manager_user_id'=>['nullable','integer','min:1']",$controller);
        $this->assertStringContainsString("'partner_id'=>['nullable','integer','min:1']",$controller);
        $this->assertStringContainsString("'medicine_id'=>['nullable','integer','min:1']",$controller);
        $this->assertStringContainsString('$workspace->commissionMedicines((int)$user->id',$controller);
        $this->assertStringContainsString('$workspace->commissionPartners((int)$user->id',$controller);
        $this->assertStringContainsString("\$canViewTeam ? \$workspace->commissionUsers() : collect()",$controller);

        $this->assertStringContainsString("@section('hide-application-header', true)",$view);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)",$view);
        $this->assertStringContainsString("\$featurePresentation['eyebrow']",$view);
        $this->assertStringContainsString("\$featurePresentation['page_title']",$view);
        $this->assertStringContainsString("\$featurePresentation['page_description']",$view);
        $this->assertStringContainsString('Doanh số ghi nhận',$view);
        $this->assertStringContainsString('Ngày xuất',$view);
        $this->assertStringContainsString('Khách hàng',$view);
        $this->assertStringContainsString('Tổng giá trị',$view);
        $this->assertStringContainsString('Tổng hoa hồng',$view);
        $this->assertStringContainsString("route('client.pharma.commissions.show'",$view);
        $this->assertStringContainsString('Chi tiết phiếu xuất',$detailView);
        $this->assertStringContainsString('SL thực xuất',$detailView);
        $this->assertStringContainsString('Chính sách',$detailView);
        $this->assertStringContainsString("@section('hide-application-header', true)",$detailView);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)",$detailView);
        $this->assertSame(1,substr_count($detailView,"@section('hide-application-header', true)"));
        $this->assertStringContainsString('Hoa hồng ròng',$view);
        $this->assertStringContainsString('Chưa xác định',$view);
        $this->assertStringContainsString('Xóa bộ lọc',$view);
        $this->assertStringContainsString('$hasCommissionFilters',$view);
        $this->assertStringContainsString('md:grid-cols-2',$view);
        $this->assertStringNotContainsString('>Áp dụng</button>',$view);
        $this->assertStringNotContainsString('data-commission-date-trigger=', $view);
        $this->assertStringNotContainsString('<script>',$view);
        $this->assertStringContainsString('const bindCommissionWorkspace',$nativeInteractions);
        $this->assertStringNotContainsString("typeof input.showPicker === 'function'",$nativeInteractions);
        $this->assertStringNotContainsString("window.location.assign(url.toString());", $nativeInteractions);
        $this->assertStringContainsString('aria-label="Từ ngày"', $view);
        $this->assertStringContainsString('aria-label="Đến ngày"', $view);
        $this->assertStringNotContainsString('md:text-transparent', $view);
        $this->assertStringNotContainsString('pointer-events-none absolute h-px w-px opacity-0', $view);
        $this->assertStringContainsString('id="commission-partner"',$view);
        $this->assertStringContainsString('search-placeholder="Tìm khách hàng..."',$view);
        $this->assertStringContainsString('id="commission-medicine"',$view);
        $this->assertStringContainsString('search-placeholder="Tìm thuốc hoặc mã thuốc..."',$view);
        $this->assertStringContainsString("'medicine_id'=>\$medicineId",$controller);
        $this->assertStringContainsString('id="commission-mobile-list"',$view);
        $this->assertStringContainsString('hidden overflow-hidden rounded-3xl',$view);
        $this->assertStringContainsString('table-fixed',$view);
        $this->assertStringContainsString('Người phụ trách',$view);
        $this->assertStringContainsString('<x-pwa-select-search',$view);
        $this->assertStringContainsString('search-placeholder="Tìm tên hoặc email..."',$view);
        $this->assertStringContainsString('data-pwa-select-search-option',$view);
        $this->assertStringContainsString('data-pwa-select-search-input',$pwaSelectSearch);
        $this->assertStringContainsString('data-pwa-select-search-panel',$pwaSelectSearch);
        $this->assertStringContainsString("\$attributes->merge(['class' => 'relative min-w-0'])",$pwaSelectSearch);
        $this->assertStringContainsString("@push('application-scripts')",$pwaSelectSearch);
        $this->assertStringContainsString("document.addEventListener('DOMContentLoaded'",$pwaSelectSearch);
        $this->assertStringContainsString('const bindPwaSelectSearch',$nativeInteractions);
        $this->assertStringContainsString('bindCommissionWorkspace(root);',$nativeInteractions);
        $this->assertStringContainsString('window.ClientPortalNativeInteractions = {bind: bindNativeInteractions};',$nativeInteractions);
        $this->assertStringContainsString('<div data-commission-workspace',$view);
        $this->assertStringNotContainsString('id="commission-selection-actions"',$view);
        $this->assertStringNotContainsString('id="commission-select-all"',$view);
        $this->assertStringContainsString("workspace.dataset.pwaCommissionBound = '1'",$nativeInteractions);
        $this->assertStringContainsString('desktopSelectAll.indeterminate = ids.length > 0 && !desktopSelectAll.checked',$nativeInteractions);
        $this->assertStringContainsString("new Set(rowCheckboxes().filter((box) => box.checked).map((box) => box.value))",$nativeInteractions);
        $this->assertStringContainsString("credentials: 'same-origin'",$nativeInteractions);
        $this->assertStringContainsString("toLocaleLowerCase('vi')",$nativeInteractions);
        $this->assertStringContainsString('@if($canViewTeam)',$view);
        $this->assertStringContainsString('type="date" name="from"',$view);
        $this->assertStringContainsString('type="date" name="to"',$view);
        $this->assertStringContainsString('mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-2 font-normal', $view);
        $this->assertStringNotContainsString('data-commission-date-display=', $view);
        $this->assertStringNotContainsString('data-commission-date-picker=', $view);
        $this->assertStringNotContainsString('const bindCommissionDates', $nativeInteractions);
        $this->assertStringNotContainsString('data-commission-date-label=', $view);
        $this->assertStringContainsString("format('d/m/Y')", $view);
        $this->assertStringContainsString("display.textContent = day + '/' + month + '/' + year;", $nativeInteractions);
        $this->assertStringNotContainsString('submitForm(input.form);', $nativeInteractions);
        $this->assertStringContainsString('data-commission-date-apply', $view);
        $this->assertStringContainsString('grid-cols-2 items-end', $view);
        $this->assertStringContainsString('md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]', $view);
        $this->assertStringContainsString('col-span-2 inline-flex h-[46px] w-full', $view);
        $this->assertStringContainsString('md:col-span-1 md:w-auto md:shrink-0', $view);
        $this->assertStringContainsString('Áp dụng', $view);
        $this->assertStringNotContainsString('Áp dụng ngày', $view);
        $this->assertStringContainsString("format('d/m/Y')",$view);
        $this->assertStringContainsString('rounded-2xl border border-slate-200 bg-white',$pwaSelectSearch);
        $this->assertStringNotContainsString('id="commission-search-input"',$view);
        $this->assertStringNotContainsString('data-pwa-debounced-search="800"',$view);
        $this->assertStringContainsString('data-commission-item',$view);
        $this->assertStringContainsString('active:scale-[0.985]',$view);
        $this->assertStringContainsString('motion-reduce:transform-none',$view);
        $this->assertStringContainsString("route('client.pharma.commissions.export'",$view);
        $this->assertStringContainsString('Xuất Excel',$view);
        $this->assertStringContainsString('navigator.canShare(payload)',$nativeInteractions);
        $this->assertStringContainsString('navigator.share(payload)',$nativeInteractions);
        $this->assertStringContainsString('window.location.assign(button.dataset.commissionShareUrl)',$nativeInteractions);
        $this->assertStringContainsString('File đã xuất',$view);
        $this->assertStringContainsString('data-commission-export-toggle',$view);
        $this->assertStringContainsString('data-commission-export-content',$view);
        $this->assertStringContainsString('aria-expanded="false"',$view);
        $this->assertStringContainsString('class="hidden border-t border-slate-100 p-4 pt-3"',$view);
        $this->assertStringContainsString('>Nguồn:</span>',$view);
        $this->assertStringContainsString('{{ $exportSourceLabel }}',$view);
        $this->assertStringContainsString("'Trúng thầu'",$view);
        $this->assertStringContainsString("'Bảng giá'",$view);
        $this->assertStringContainsString('>Nguồn</th>',$view);
        $this->assertSame(2,substr_count($view,"\$sourceLabel=\$row->commission_source_type==='bid'"),'Mobile and desktop rows must each resolve commission source independently.');
        $workspace=file_get_contents(base_path('Modules/Pharma/Services/UserCommissionWorkspace.php'));
        $this->assertStringContainsString("orderByDesc('id')",$workspace);
        $this->assertStringContainsString("->groupBy('issue_id')",$workspace);
        $this->assertStringContainsString("\$first=\$entries->first()",$workspace);
        $this->assertStringContainsString("setAttribute('commission_source_type',\$first->source_type)",$workspace);
        $this->assertStringNotContainsString('COUNT(DISTINCT source_type)',file_get_contents(base_path('Modules/Pharma/Services/UserCommissionWorkspace.php')));
        $this->assertStringContainsString('>Tải</a>',$view);
        $this->assertStringContainsString('>In</a>',$view);
        $this->assertStringContainsString('>Chia sẻ</button>',$view);
        $this->assertStringContainsString('>Xóa</button>',$view);
        $this->assertStringContainsString("route('client.pharma.commissions.exports.destroy'",$view);
        $this->assertStringContainsString('public function deleteCommissionExport',$controller);
        $this->assertStringContainsString("Storage::disk(\$artifact->disk)->delete(\$artifact->storage_path)",$controller);
        $this->assertStringNotContainsString('id="commission-select-all"',$view);
        $this->assertStringContainsString('commission-row-checkbox',$view);
        $this->assertStringContainsString('data-commission-select-all-desktop',$view);
        $this->assertStringContainsString('<span>Chọn</span>',$view);
        $this->assertStringNotContainsString('absolute left-3 top-3',$view);
        $this->assertStringContainsString("input.name = 'ids[]'",$nativeInteractions);
        $this->assertStringContainsString("'ids'=>['nullable','array','max:500']",$controller);
        $this->assertStringContainsString("whereIn('issue_id',\$ids)",$controller);
        $this->assertStringNotContainsString('Admin::',$view);
        $this->assertStringContainsString('method="POST"',$view);
        $this->assertStringContainsString('@csrf',$view);
        $this->assertStringNotContainsString('wire:',$view);
    }
}
