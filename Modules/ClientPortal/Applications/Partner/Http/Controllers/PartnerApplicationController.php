<?php
namespace Modules\ClientPortal\Applications\Partner\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

final class PartnerApplicationController extends Controller
{
 private function context(string $feature, ApplicationRegistry $registry, ClientPortalSettingsService $settings): array
 {
  $application = $registry->find('partners');
  abort_unless($application, 404);
  $definition = collect($application['features'])->firstWhere('key',$feature);
  return ['application'=>$application,'applicationPresentation'=>$settings->applicationPresentation($application),
   'pagePresentation'=>$settings->featurePresentation('partners',$definition)];
 }
 public function index(Request $request, PartnerService $partners, ApplicationRegistry $registry, ClientPortalSettingsService $settings)
 {
  $filters=$request->validate(['search'=>['nullable','string','max:255'],
   'legal_type'=>['nullable',Rule::in(array_keys(Partner::LEGAL_TYPES))],
   'partner_type'=>['nullable',Rule::in(array_keys(Partner::PARTNER_TYPES))],
   'status'=>['nullable',Rule::in(array_keys(Partner::STATUSES))],
   'per_page'=>['nullable',Rule::in([10,25,50,100])]]);
  return view('ClientPortal::applications.partners.index',array_merge($this->context('list',$registry,$settings),[
   'partners'=>$partners->paginate($filters,(int)($filters['per_page']??10)),'filters'=>$filters]));
 }
 public function create(ApplicationRegistry $registry, ClientPortalSettingsService $settings)
 {
  return view('ClientPortal::applications.partners.form',array_merge($this->context('create',$registry,$settings),['partner'=>null]));
 }
 public function edit(Partner $partner, ApplicationRegistry $registry, ClientPortalSettingsService $settings)
 {
  return view('ClientPortal::applications.partners.form',array_merge($this->context('edit',$registry,$settings),['partner'=>$partner]));
 }
 public function store(Request $request, PartnerService $partners)
 {
  $partners->create($this->validated($request));
  return redirect()->route('client.partners.index')->with('success','Đã thêm đối tác.');
 }
 public function update(Request $request, Partner $partner, PartnerService $partners)
 {
  $partners->update($partner,$this->validated($request,$partner));
  return redirect()->route('client.partners.index')->with('success','Đã cập nhật đối tác.');
 }
 public function destroy(Partner $partner, PartnerService $partners)
 {
  $partners->delete($partner);
  return redirect()->route('client.partners.index')->with('success','Đã xóa đối tác.');
 }
 private function validated(Request $request, ?Partner $partner=null): array
 {
  return $request->validate([
   'name'=>['required','string','max:255'],
   'tax_code'=>['nullable','string','max:50',Rule::unique('partners','tax_code')->ignore($partner?->id)],
   'legal_type'=>['required',Rule::in(array_keys(Partner::LEGAL_TYPES))],
   'partner_types'=>['required','array','min:1'],
   'partner_types.*'=>['required',Rule::in(array_keys(Partner::PARTNER_TYPES))],
   'source'=>['required',Rule::in(array_keys(Partner::SOURCES))],
   'status'=>['required',Rule::in(array_keys(Partner::STATUSES))],
   'phone'=>['nullable','string','max:50'],'email'=>['nullable','email','max:255'],
   'contact_person'=>['nullable','string','max:255'],'address'=>['nullable','string','max:1000'],
   'province_code'=>['nullable','string','max:20'],'note'=>['nullable','string','max:2000'],
  ]);
 }
}
