<?php
namespace Modules\Admin\Livewire\Affiliate;

use Livewire\Component;
use Modules\Website\Models\WpProduct;
use Modules\Admin\Models\AffiliateLevel;
use Modules\Admin\Models\AffiliateScheme;
use App\Models\User;

class CommissionMatrix extends Component
{
    public $productId;
    public $product;
    
    // Form State cho việc thêm User đặc biệt
    public $searchUser = '';
    public $selectedUserId = null;
    public $userResults = [];

    // Danh sách schemes hiện tại
    public $schemes = [];

    public function mount($productId)
    {
        $this->productId = $productId;
        $this->product = WpProduct::findOrFail($productId);
        $this->loadSchemes();
    }

    public function loadSchemes()
    {
        $this->schemes = AffiliateScheme::with(['level', 'user'])
            ->where('product_id', $this->productId)
            ->get();
    }

    // Tìm kiếm User để thêm cấu hình đặc biệt
    public function updatedSearchUser()
    {
        if (strlen($this->searchUser) < 2) {
            $this->userResults = [];
            return;
        }
        $this->userResults = User::where('name', 'like', '%' . $this->searchUser . '%')
            ->orWhere('email', 'like', '%' . $this->searchUser . '%')
            ->limit(5)->get();
    }

    public function addLevelScheme($levelId)
    {
        AffiliateScheme::updateOrCreate(
            ['product_id' => $this->productId, 'level_id' => $levelId, 'user_id' => null],
            ['commission_type' => 'percentage', 'percent_value' => 0]
        );
        $this->loadSchemes();
    }

    public function addUserScheme($userId)
    {
        AffiliateScheme::updateOrCreate(
            ['product_id' => $this->productId, 'user_id' => $userId, 'level_id' => null],
            ['commission_type' => 'hybrid', 'percent_value' => 0, 'fixed_value' => 0]
        );
        $this->searchUser = '';
        $this->userResults = [];
        $this->loadSchemes();
    }

    public function updateScheme($id, $field, $value)
    {
        $scheme = AffiliateScheme::find($id);
        if ($scheme) {
            $scheme->update([$field => $value]);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã cập nhật cấu hình']);
        }
    }

    public function deleteScheme($id)
    {
        AffiliateScheme::destroy($id);
        $this->loadSchemes();
    }

    public function render()
    {
        $levels = AffiliateLevel::all();
        return view('Admin::livewire.affiliate.commission-matrix', [
            'levels' => $levels
        ]);
    }
}