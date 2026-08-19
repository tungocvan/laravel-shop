<?php
namespace Modules\Muasamcong\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Modules\Muasamcong\Models\PriceListProfile;
class PriceListProfileController extends Controller
{
    public function index(): View
    {
        return view('Muasamcong::price-list-profiles.index',[
            'profiles'=>PriceListProfile::orderBy('sort_order')->orderBy('id')->get(),
            'availableColumns'=>PriceListProfile::availableColumns(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$this->validated($request);
        if($data['is_default']) PriceListProfile::query()->update(['is_default'=>false]);
        PriceListProfile::create($data);
        return back()->with('success','Đã tạo cấu hình Bảng Giá và '.($data['is_active']?'cho phép Client sử dụng.':'để ở trạng thái tạm tắt.'));
    }

    public function update(Request $request, PriceListProfile $profile): RedirectResponse
    {
        $data=$this->validated($request);
        if($data['is_default']) PriceListProfile::whereKeyNot($profile->getKey())->update(['is_default'=>false]);
        $profile->update($data);
        return back()->with('success','Đã cập nhật cấu hình Bảng Giá.');
    }

    public function toggle(PriceListProfile $profile): RedirectResponse
    {
        $profile->update(['is_active'=>!$profile->is_active]);
        return back()->with('success',$profile->fresh()->is_active?'Đã cho phép Client sử dụng cấu hình này.':'Đã tắt cấu hình khỏi Client.');
    }

    public function destroy(PriceListProfile $profile): RedirectResponse
    {
        $profile->delete();
        return back()->with('success','Đã xóa cấu hình Bảng Giá.');
    }

    private function validated(Request $request): array
    {
        $data=$request->validate([
            'name'=>'required|string|max:120',
            'title'=>'nullable|string|max:200',
            'sheet_name'=>'required|string|max:31',
            'file_prefix'=>'required|string|max:80',
            'columns'=>'required|array|min:1',
            'columns.*'=>'required|string|in:'.implode(',',array_keys(PriceListProfile::availableColumns())),
            'sort_order'=>'nullable|integer|min:0',
        ]);
        $data['is_active']=$request->boolean('is_active');
        $data['is_default']=$request->boolean('is_default');
        $data['sort_order']=$data['sort_order']??0;
        return $data;
    }
}
