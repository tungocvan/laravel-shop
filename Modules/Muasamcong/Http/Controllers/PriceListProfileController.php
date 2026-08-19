<?php
namespace Modules\Muasamcong\Http\Controllers;
use App\Http\Controllers\Controller; use Illuminate\Contracts\View\View; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Modules\Muasamcong\Models\PriceListProfile;
class PriceListProfileController extends Controller
{
 public function index(): View{return view('Muasamcong::price-list-profiles.index',['profiles'=>PriceListProfile::orderBy('sort_order')->get(),'availableColumns'=>PriceListProfile::availableColumns()]);}
 public function store(Request $r): RedirectResponse{$d=$this->validated($r);if($d['is_default'])PriceListProfile::query()->update(['is_default'=>false]);PriceListProfile::create($d);return back()->with('success','Đã tạo cấu hình Bảng Giá.');}
 public function update(Request $r,PriceListProfile $profile): RedirectResponse{$d=$this->validated($r);if($d['is_default'])PriceListProfile::where('id','!=',$profile->getKey())->update(['is_default'=>false]);$profile->update($d);return back()->with('success','Đã cập nhật cấu hình Bảng Giá.');}
 public function destroy(PriceListProfile $profile): RedirectResponse{$profile->delete();return back()->with('success','Đã xóa cấu hình Bảng Giá.');}
 private function validated(Request $r): array{$d=$r->validate(['name'=>'required|string|max:120','title'=>'nullable|string|max:200','sheet_name'=>'required|string|max:31','file_prefix'=>'required|string|max:80','columns'=>'required|array|min:1','columns.*'=>'required|string|in:'.implode(',',array_keys(PriceListProfile::availableColumns())),'sort_order'=>'nullable|integer|min:0']);$d['sort_order']=$d['sort_order']??0;$d['is_active']=$r->boolean('is_active');$d['is_default']=$r->boolean('is_default');return $d;}
}
