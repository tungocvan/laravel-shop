<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <strong>Cây thư mục</strong>
            </div>
            <div class="card-body">
                @if (session()->has('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @forelse ($tree as $folder)
                    @include('Ebook::livewire.folder.partials.folder-node', ['folder' => $folder, 'level' => 0])
                @empty
                    <div class="text-muted">Chưa có thư mục Ebook.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ $folderId ? 'Sửa thư mục' : 'Tạo thư mục' }}</strong>
                @if ($folderId)
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetForm">Tạo mới</button>
                @endif
            </div>
            <div class="card-body">
                <form wire:submit="save">
                    <div class="mb-3">
                        <label class="form-label">Thư mục cha</label>
                        <select class="form-select @error('parentId') is-invalid @enderror" wire:model="parentId">
                            <option value="">-- Thư mục gốc --</option>
                            @foreach ($parentOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                        @error('parentId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tên thư mục</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug <span class="text-muted">(để trống để tự tạo)</span></label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" wire:model="slug">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" rows="3" wire:model="description"></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Thứ tự</label>
                            <input type="number" min="0" class="form-control @error('sortOrder') is-invalid @enderror" wire:model="sortOrder">
                            @error('sortOrder') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="ebook-folder-active" wire:model="isActive">
                                <label class="form-check-label" for="ebook-folder-active">Đang hoạt động</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $folderId ? 'Cập nhật' : 'Tạo thư mục' }}</span>
                            <span wire:loading wire:target="save">Đang lưu...</span>
                        </button>
                        @if ($folderId)
                            <button type="button" class="btn btn-outline-danger" wire:click="delete({{ $folderId }})" wire:confirm="Xóa thư mục này?" wire:loading.attr="disabled">
                                Xóa
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
