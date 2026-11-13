<?php
namespace Modules\ClientPortal\Applications\Partner\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Partner\Services\PartnerQueryService;
use Rap2hpoutre\FastExcel\FastExcel;
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
   'partner_type'=>['nullable',Rule::in([...array_keys(Partner::PARTNER_TYPES),'both'])],
   'status'=>['nullable',Rule::in(array_keys(Partner::STATUSES))],
   'per_page'=>['nullable',Rule::in([10,25,50,100])]]);
  return view('ClientPortal::applications.partners.index',array_merge($this->context('list',$registry,$settings),[
   'partners'=>$partners->paginate($filters,(int)($filters['per_page']??10)),'filters'=>$filters]));
 }
 public function businessLookup(ApplicationRegistry $registry, ClientPortalSettingsService $settings)
 {
  return view('ClientPortal::applications.partners.business-lookup', $this->context('lookup',$registry,$settings));
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


 public function import(Request $request, PartnerService $partners)
 {
  $request->validate(['import_file'=>['required','file','mimes:xlsx,csv,txt','max:5120']]);
  $upload=$request->file('import_file');
  $extension=strtolower($upload->getClientOriginalExtension());
  $temp=tempnam(sys_get_temp_dir(),'partner-import-');
  $path=$temp.'.'.$extension;
  @unlink($temp);
  try {
   if (!copy($upload->getRealPath(),$path)) throw new \RuntimeException('Không thể xử lý file import.');
   $rows=(new FastExcel)->import($path);
  } finally {
   @unlink($path);
  }
  if ($rows->count()>1000) return back()->withErrors(['import_file'=>'Tối đa 1000 dòng mỗi lần import.']);
  $normalized=[];
  foreach ($rows as $index=>$row) {
   $name=trim((string)($row['name']??$row['Tên đối tác']??''));
   $legal=trim((string)($row['legal_type']??$row['Loại pháp lý']??''));
   $types=collect(explode(',',(string)($row['partner_types']??($legal==='hospital'?'customer':'supplier'))))->map(fn($v)=>trim($v))->filter()->values()->all();
   $data=[
    'name'=>$name,'tax_code'=>trim((string)($row['tax_code']??$row['Mã số thuế']??'')),
    'legal_type'=>$legal,'partner_types'=>$types,
    'address'=>trim((string)($row['address']??'')),'province_code'=>trim((string)($row['province_code']??'')),
    'email'=>trim((string)($row['email']??'')),'phone'=>trim((string)($row['phone']??'')),
    'contact_person'=>trim((string)($row['contact_person']??'')),
    'source'=>in_array($row['source']??'',array_keys(Partner::SOURCES),true)?$row['source']:'import',
    'status'=>in_array($row['status']??'',array_keys(Partner::STATUSES),true)?$row['status']:'active',
    'note'=>trim((string)($row['note']??'')),
   ];
   $validator=\Illuminate\Support\Facades\Validator::make($data,[
    'name'=>['required','string','max:255'],
    'tax_code'=>['nullable','string','max:50'],
    'legal_type'=>['required',Rule::in(array_keys(Partner::LEGAL_TYPES))],
    'partner_types'=>['required','array','min:1'],
    'partner_types.*'=>[Rule::in(array_keys(Partner::PARTNER_TYPES))],
    'email'=>['nullable','email','max:255'],
    'phone'=>['nullable','string','max:50'],'contact_person'=>['nullable','string','max:255'],
    'address'=>['nullable','string','max:1000'],'province_code'=>['nullable','string','max:20'],
    'note'=>['nullable','string','max:2000'],
   ]);
   if($validator->fails()) return back()->withErrors(['import_file'=>'Dòng '.($index+2).': '.$validator->errors()->first()]);
   $normalized[]=$data;
  }
  \Illuminate\Support\Facades\DB::transaction(function () use ($normalized,$partners) {
   foreach($normalized as $data) {
    $existing=$data['tax_code']!==''?Partner::where('tax_code',$data['tax_code'])->first():Partner::where('name',$data['name'])->where('legal_type',$data['legal_type'])->first();
    if($existing) $partners->update($existing,$data);
    else $partners->create($data);
   }
  });
  return redirect()->route('client.partners.index')->with('success','Đã import '.count($normalized).' đối tác.');
 }
 public function export(Request $request, PartnerQueryService $queryService)
 {
  $data=$request->validate([
   'search'=>['nullable','string','max:255'],
   'legal_type'=>['nullable',Rule::in(array_keys(Partner::LEGAL_TYPES))],
   'partner_type'=>['nullable',Rule::in([...array_keys(Partner::PARTNER_TYPES),'both'])],
   'status'=>['nullable',Rule::in(array_keys(Partner::STATUSES))],
   'selected'=>['nullable','array','max:100'],
   'selected.*'=>['required','integer','distinct','min:1'],
  ]);
  $query=$queryService->query($data)->latest('id');
  if (!empty($data['selected'])) $query->whereIn('id',$data['selected']);
  $rows=$query->get()->map(fn (Partner $partner)=>[
   'tax_code'=>$partner->tax_code,'name'=>$partner->name,
   'legal_type'=>$partner->legal_type,'partner_types'=>implode(',',$partner->partner_types??[]),
   'address'=>$partner->address,'province_code'=>$partner->province_code,
   'email'=>$partner->email,'phone'=>$partner->phone,
   'contact_person'=>$partner->contact_person,'source'=>$partner->source,
   'status'=>$partner->status,'note'=>$partner->note,
  ]);
  $temp=tempnam(sys_get_temp_dir(),'partner-export-');
  $path=$temp.'.xlsx';
  @unlink($temp);
  try {
   (new FastExcel($rows))->export($path);
   return response()->download($path,'partners_'.now()->format('Ymd_His').'.xlsx')->deleteFileAfterSend(true);
  } catch (\Throwable $e) {
   @unlink($path);
   throw $e;
  }
 }
 public function template()
 {
  $temp=tempnam(sys_get_temp_dir(),'partner-template-');
  $path=$temp.'.xlsx';
  @unlink($temp);
  try {
   (new FastExcel(collect([[
    'tax_code'=>'','name'=>'','legal_type'=>'company','partner_types'=>'supplier,customer',
    'address'=>'','province_code'=>'','email'=>'','phone'=>'','contact_person'=>'',
    'source'=>'import','status'=>'active','note'=>'',
   ]])))->export($path);
   return response()->download($path,'partner_import_template.xlsx')->deleteFileAfterSend(true);
  } catch (\Throwable $e) {
   @unlink($path);
   throw $e;
  }
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
