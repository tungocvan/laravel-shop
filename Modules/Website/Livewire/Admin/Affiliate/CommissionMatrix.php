<?php

namespace Modules\Website\Livewire\Admin\Affiliate;

use App\Models\User;
use Livewire\Component;
use Modules\Order\Models\AffiliateLevel;
use Modules\Order\Models\AffiliateScheme;
use Modules\Product\Models\Product;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;

class CommissionMatrix extends Component
{
    use AuthorizesAdminPermissions;

    public $productId;

    public $product;

    public $searchUser = '';

    public $selectedUserId = null;

    public $userResults = [];

    public $schemes = [];

    public function mount($productId)
    {
        $this->productId = $productId;
        $this->product = Product::findOrFail($productId);
        $this->loadSchemes();
    }

    public function loadSchemes()
    {
        $this->schemes = AffiliateScheme::with(['level', 'user'])
            ->where('product_id', $this->productId)
            ->get();
    }

    public function updatedSearchUser()
    {
        if (strlen($this->searchUser) < 2) {
            $this->userResults = [];

            return;
        }

        $this->userResults = User::where('name', 'like', '%'.$this->searchUser.'%')
            ->orWhere('email', 'like', '%'.$this->searchUser.'%')
            ->limit(5)
            ->get();
    }

    public function addLevelScheme($levelId)
    {
        $this->authorizeAdminPermission('affiliate.manage');

        AffiliateScheme::updateOrCreate(
            ['product_id' => $this->productId, 'level_id' => $levelId, 'user_id' => null],
            ['commission_type' => 'percentage', 'percent_value' => 0]
        );
        $this->loadSchemes();
    }

    public function addUserScheme($userId)
    {
        $this->authorizeAdminPermission('affiliate.manage');

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
        $this->authorizeAdminPermission('affiliate.manage');

        $scheme = AffiliateScheme::find($id);
        if ($scheme) {
            $scheme->update([$field => $value]);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã cập nhật cấu hình']);
        }
    }

    public function deleteScheme($id)
    {
        $this->authorizeAdminPermission('affiliate.manage');
        AffiliateScheme::destroy($id);
        $this->loadSchemes();
    }

    public function render()
    {
        return view('Website::livewire.admin.affiliate.commission-matrix', [
            'levels' => AffiliateLevel::all(),
        ]);
    }
}
