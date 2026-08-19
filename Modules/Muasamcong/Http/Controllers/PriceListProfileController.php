<?php
namespace Modules\Muasamcong\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Modules\Muasamcong\Models\PriceListProfile;
class PriceListProfileController extends Controller
{
    public function index(): View { return view('Muasamcong::price-list-profiles.index',['profiles'=>PriceListProfile::orderBy('sort_order')->get(),'availableColumns'=>PriceListProfile::availableColumns()]); }
    public function store(Request $request): RedirectResponse
    {
        $data=$this->validated($request); if($data['is_default']??false) PriceListProfile::query()->update(['is_default'=>false]); PriceListProfile::create($data);
        return back()->with('success','Đã tạo cấu hình Bảng Giá.');
    }
    public function update(Request $request, PriceListProfile $profile): RedirectResponse
    {
        $data=$this->validated($request); if($data['is_default']??false) PriceListProfile::whereKeyNot($profile->getKey())->update(['is_default'=>false]); $profile->update($data);
        return back()->with('success','Đã cập nhật cấu hình Bảng Giá.');
    }
    public function destroy(PriceListProfile $profile): RedirectResponse { $profile->delete(); return back()->with('success','Đã xóa cấu hình Bảng Giá.'); }
    private function validated(Request $request): array
    {
        $data=$request->validate(['name'=>'required|string|max:120','title'=>'nullable|string|max:200','sheet_name'=>'required|string|max:31','file_prefix'=>'required|string|max:80','columns'=>'required|array|min:1','columns.*'=>'required|string|in:'.implode(',',array_keys(PriceListProfile::availableColumns())),'sort_order'=>'nullable|integer|min:0']);
        $data['is_active']=$request->boolean('is_active'); $data['is_default']=$request->boolean('is_default'); return $data;
    }
}
