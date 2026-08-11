<?php

namespace Modules\Website\Livewire\Admin\Footer;

use Livewire\Component;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\FooterColumn;
use Modules\Website\Services\FooterService;

class FooterColumns extends Component
{
    use AuthorizesAdminPermissions;

    public $activeColumnId = null;
    public $col_title, $col_slug, $col_sort = 0;
    public $link_label, $link_url, $link_sort = 0;
    public $new_links = [];
    public $editingLinkId = null;
    public $edit_label;
    public $edit_url;
    public $editingColumnId = null;
    public $edit_col_title;
    public $edit_col_slug;

    public function render(FooterService $service)
    {
        return view('Website::livewire.admin.footer.footer-columns', [
            'columns' => $service->getColumnsForAdmin(),
        ]);
    }

    public function createColumn(FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $this->validate(['col_title' => 'required', 'col_slug' => 'required|unique:footer_columns,slug']);

        $service->createColumn([
            'title' => $this->col_title,
            'slug' => $this->col_slug,
            'sort_order' => (int) $this->col_sort,
        ]);

        $this->reset(['col_title', 'col_slug', 'col_sort']);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã thêm cột mới']);
    }

    public function deleteColumn($id, FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $deleted = $service->deleteColumn($id);

        $this->dispatch('show-toast', [
            'type' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Đã xóa cột thành công' : 'Cột không tồn tại hoặc đã bị xóa',
        ]);
    }

    public function addLink($columnId, FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $input = $this->new_links[$columnId] ?? [];

        if (empty($input['label'])) {
            $this->addError("new_links.$columnId.label", 'Vui lòng nhập tên link');
            return;
        }

        if (! FooterColumn::where('id', $columnId)->exists()) {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Cột không tồn tại. F5 lại trang!']);
            return;
        }

        $service->addLinkToColumn($columnId, [
            'label' => $input['label'],
            'url' => $input['url'] ?? '#',
            'sort_order' => (int) ($input['sort'] ?? 0),
            'is_active' => true,
        ]);

        unset($this->new_links[$columnId]);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã thêm link mới']);
    }

    public function deleteLink($linkId, FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $service->deleteLink($linkId);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã xóa link']);
    }

    public function editLink($id, $label, $url)
    {
        $this->editingLinkId = $id;
        $this->edit_label = $label;
        $this->edit_url = $url;
    }

    public function cancelEdit()
    {
        $this->reset(['editingLinkId', 'edit_label', 'edit_url']);
    }

    public function updateLink(FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $this->validate([
            'edit_label' => 'required',
            'edit_url' => 'required',
        ]);

        $service->updateLink($this->editingLinkId, [
            'label' => $this->edit_label,
            'url' => $this->edit_url,
        ]);

        $this->cancelEdit();
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật link']);
    }

    public function updateLinkOrder($orderedIds, FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $service->updateLinkOrder($orderedIds);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật thứ tự']);
    }

    public function updateColumnOrder($orderedIds, FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $service->updateColumnOrder($orderedIds);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật vị trí cột']);
    }

    public function toggleColumn($id, FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $service->toggleColumnStatus($id);
    }

    public function editColumn($id)
    {
        $col = FooterColumn::find($id);
        if ($col) {
            $this->editingColumnId = $id;
            $this->edit_col_title = $col->title;
            $this->edit_col_slug = $col->slug;
        }
    }

    public function cancelEditColumn()
    {
        $this->reset(['editingColumnId', 'edit_col_title', 'edit_col_slug']);
    }

    public function updateColumn(FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $this->validate([
            'edit_col_title' => 'required|string|max:255',
            'edit_col_slug' => 'required|string|max:255|unique:footer_columns,slug,' . $this->editingColumnId,
        ]);

        $service->updateColumn($this->editingColumnId, [
            'title' => $this->edit_col_title,
            'slug' => $this->edit_col_slug,
        ]);

        $this->cancelEditColumn();
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật thông tin cột']);
    }
}
