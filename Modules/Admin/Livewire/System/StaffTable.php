<?php

namespace Modules\Admin\Livewire\System;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffTable extends Component
{
    use WithPagination, WithFileUploads;

    // Filter & Pagination
    public $search = '';
    public $perPage = 10;
    public $filterRole = '';

    // Selection
    public $selected = [];
    public $selectAll = false;

    // Import/Export
    public $showImportModal = false;
    public $importFile;

    // --- RESET LOGIC ---
    public function updatedSearch() { $this->resetPage(); $this->resetSelection(); }
    public function updatedFilterRole() { $this->resetPage(); $this->resetSelection(); }
    public function updatedPerPage() { $this->resetPage(); $this->resetSelection(); }
    
    public function resetSelection() 
    { 
        $this->selected = []; 
        $this->selectAll = false; 
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getQuery()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    // --- CRUD ACTIONS ---
    
    // 1. Xóa nhiều
    public function deleteSelected()
    {
        // Bảo vệ: Không cho xóa chính mình
        if (in_array(auth()->id(), $this->selected)) {
            $this->dispatch('notify', content: 'Bảo mật: Không thể xóa tài khoản đang đăng nhập!', type: 'error');
            return;
        }

        User::whereIn('id', $this->selected)->delete();
        $this->resetSelection();
        $this->dispatch('notify', content: 'Đã xóa các nhân viên đã chọn.', type: 'success');
    }

    // 2. Xóa lẻ
    public function delete($id)
    {
        if ($id == auth()->id()) {
            $this->dispatch('notify', content: 'Không thể xóa chính mình!', type: 'error');
            return;
        }
        User::find($id)->delete();
        $this->dispatch('notify', content: 'Đã xóa nhân viên.', type: 'success');
    }

    // --- IMPORT / EXPORT LOGIC ---

    // 3. Export JSON
    public function export()
    {
        $users = $this->getQuery()->get()->map(function($user) {
            return [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => (bool)$user->is_active,
                'roles' => $user->roles->pluck('name')->toArray(),
                'created_at' => $user->created_at->toDateTimeString(),
            ];
        });

        $fileName = 'staff_export_' . date('Y_m_d_His') . '.json';
        return response()->streamDownload(function () use ($users) {
            echo $users->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName);
    }

    // 4. Import JSON (Có kiểm tra trùng)
    public function import()
    {
        $this->validate(['importFile' => 'required|mimes:json,txt|max:2048']);

        try {
            $content = file_get_contents($this->importFile->getRealPath());
            $json = json_decode($content, true);
            
            if (!is_array($json)) {
                throw new \Exception("File không đúng định dạng JSON.");
            }

            $countSuccess = 0;
            $countSkip = 0;

            DB::transaction(function () use ($json, &$countSuccess, &$countSkip) {
                foreach ($json as $item) {
                    // CHECK TRÙNG: Nếu email đã có -> Bỏ qua ngay lập tức
                    if (User::where('email', $item['email'])->exists()) {
                        $countSkip++;
                        continue; 
                    }

                    // Tạo User
                    $user = User::create([
                        'name' => $item['name'],
                        'email' => $item['email'],
                        'password' => Hash::make('12345678'), // Mật khẩu mặc định
                        'phone' => $item['phone'] ?? null,
                        'is_active' => $item['is_active'] ?? true,
                    ]);

                    // Gán Role (Nếu có và Role đó tồn tại)
                    if (!empty($item['roles'])) {
                        foreach ($item['roles'] as $roleName) {
                            $role = Role::where('name', $roleName)->where('guard_name', 'admin')->first();
                            if ($role) {
                                $user->assignRole($role);
                            }
                        }
                    }
                    
                    $countSuccess++;
                }
            });

            $this->showImportModal = false;
            $this->importFile = null;
            
            $this->dispatch('notify', content: "Import xong: {$countSuccess} thành công, {$countSkip} bỏ qua (do trùng).", type: 'success');

        } catch (\Exception $e) {
            $this->addError('importFile', 'Lỗi: ' . $e->getMessage());
        }
    }

    // --- QUERY ---
    private function getQuery()
    {
        return User::query()
            ->with('roles')
            ->whereHas('roles') // Chỉ lấy user có role (Nhân viên)
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterRole, function($q) {
                $q->whereHas('roles', fn($r) => $r->where('id', $this->filterRole));
            })
            ->latest();
    }

    public function render()
    {
        return view('Admin::livewire.system.staff-table', [
            'users' => $this->getQuery()->paginate($this->perPage),
            'roles' => Role::all()
        ]);
    }
}