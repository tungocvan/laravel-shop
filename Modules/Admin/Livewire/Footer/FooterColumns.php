<?php

namespace Modules\Admin\Livewire\Footer;

use Livewire\Component;
use Modules\Website\Services\FooterService;
use Modules\Website\Models\FooterColumn;

class FooterColumns extends Component
{
    public $activeColumnId = null; // Để biết đang xem/sửa link của cột nào

    // Form Column
    public $col_title, $col_slug, $col_sort = 0;

    // Form Link
    public $link_label, $link_url, $link_sort = 0;

    public $new_links = [];
    public $editingLinkId = null;
    public $edit_label;
    public $edit_url;

    public $editingColumnId = null;
    public $edit_col_title; // Biến tạm để lưu title đang sửa
    public $edit_col_slug;  // Biến tạm để lưu slug đang sửa

    public function render(FooterService $service)
    {
        // Khi render, Service sẽ check cache.
        // Vì các hàm delete/create đã xóa cache, nên ở đây sẽ lấy dữ liệu mới nhất.
        return view('Admin::livewire.footer.footer-columns', [
            'columns' => $service->getColumnsForAdmin()
        ]);
    }

    // --- COLUMN ACTIONS ---
    public function createColumn(FooterService $service)
    {
        $this->validate(['col_title' => 'required', 'col_slug' => 'required|unique:footer_columns,slug']);

        $service->createColumn([
            'title' => $this->col_title,
            'slug' => $this->col_slug,
            'sort_order' => (int)$this->col_sort
        ]);

        $this->reset(['col_title', 'col_slug', 'col_sort']);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã thêm cột mới']);
    }

    public function deleteColumn($id, FooterService $service)
    {
        // ✅ GỌI SERVICE ĐỂ XÓA (Service sẽ lo vụ Cache)
        $deleted = $service->deleteColumn($id);

        if ($deleted) {
            $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã xóa cột thành công']);
        } else {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Cột không tồn tại hoặc đã bị xóa']);
        }
    }

    // --- LINK ACTIONS ---
    // --- LINK ACTIONS ---
    public function addLink($columnId, FooterService $service)
    {
        // 1. Lấy dữ liệu từ mảng theo ID cột
        $input = $this->new_links[$columnId] ?? [];

        // 2. Validate thủ công dữ liệu trong mảng
        if (empty($input['label'])) {
            // Bắn lỗi cụ thể cho cột này
            $this->addError("new_links.$columnId.label", 'Vui lòng nhập tên link');
            return;
        }

        // 3. Check Cột tồn tại
        if (!FooterColumn::where('id', $columnId)->exists()) {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Cột không tồn tại. F5 lại trang!']);
            return;
        }

        // 4. Gọi Service thêm Link
        $service->addLinkToColumn($columnId, [
            'label' => $input['label'],
            'url' => $input['url'] ?? '#', // Mặc định # nếu rỗng
            'sort_order' => (int)($input['sort'] ?? 0),
            'is_active' => true
        ]);

        // 5. Reset input của RIÊNG cột này
        unset($this->new_links[$columnId]);

        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã thêm link mới']);
    }
    public function deleteLink($linkId, FooterService $service)
    {
        $service->deleteLink($linkId);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã xóa link']);
    }

    // 1. Kích hoạt chế độ sửa
    public function editLink($id, $label, $url)
    {
        $this->editingLinkId = $id;
        $this->edit_label = $label;
        $this->edit_url = $url;
    }

    // 2. Hủy sửa
    public function cancelEdit()
    {
        $this->reset(['editingLinkId', 'edit_label', 'edit_url']);
    }

    // 3. Lưu thay đổi
    public function updateLink(FooterService $service)
    {
        $this->validate([
            'edit_label' => 'required',
            'edit_url' => 'required'
        ]);

        $service->updateLink($this->editingLinkId, [
            'label' => $this->edit_label,
            'url' => $this->edit_url
        ]);

        $this->cancelEdit(); // Thoát chế độ sửa
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật link']);
    }

    // --- SORT ACTIONS (Được gọi từ JS) ---
    public function updateLinkOrder($orderedIds, FooterService $service)
    {
        $service->updateLinkOrder($orderedIds);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật thứ tự']);
    }
    // --- COLUMN ACTIONS (Bổ sung) ---

    // Xử lý kéo thả cột (Gọi từ JS)
    public function updateColumnOrder($orderedIds, FooterService $service)
    {
        $service->updateColumnOrder($orderedIds);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật vị trí cột']);
    }

    // Xử lý Ẩn/Hiện
    public function toggleColumn($id, FooterService $service)
    {
        $service->toggleColumnStatus($id);
        // Không cần thông báo toast nếu muốn thao tác nhanh, hoặc alert nhẹ
    }
    // 1. Kích hoạt chế độ sửa cột
    public function editColumn($id)
    {
        $col = FooterColumn::find($id);
        if ($col) {
            $this->editingColumnId = $id;
            $this->edit_col_title = $col->title;
            $this->edit_col_slug = $col->slug;
        }
    }

    // 2. Hủy sửa
    public function cancelEditColumn()
    {
        $this->reset(['editingColumnId', 'edit_col_title', 'edit_col_slug']);
    }

    // 3. Lưu thay đổi cột
    public function updateColumn(FooterService $service)
    {
        $this->validate([
            'edit_col_title' => 'required|string|max:255',
            // Validate unique slug nhưng trừ ID hiện tại ra
            'edit_col_slug' => 'required|string|max:255|unique:footer_columns,slug,' . $this->editingColumnId
        ]);

        $service->updateColumn($this->editingColumnId, [
            'title' => $this->edit_col_title,
            'slug' => $this->edit_col_slug
        ]);

        $this->cancelEditColumn();
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật thông tin cột']);
    }
}
