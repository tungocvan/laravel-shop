<div class="row g-3">
    <div class="col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>{{ $documentId ? 'Sửa tài liệu' : 'Tạo tài liệu' }}</strong></div>
            <div class="card-body">
                @if (session()->has('ebook_document_success'))
                    <div class="alert alert-success">{{ session('ebook_document_success') }}</div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Thư mục</label>
                    <select class="form-select" wire:model="folderId">
                        <option value="">-- Chọn thư mục --</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                    @error('folderId') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" class="form-control" wire:model="title">
                    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" wire:model="slug" placeholder="Tự sinh từ tiêu đề nếu để trống">
                    @error('slug') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control" rows="2" wire:model="description"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Markdown</label>
                    <textarea class="form-control font-monospace" rows="12" wire:model="content"></textarea>
                    @error('content') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Thứ tự</label>
                        <input type="number" min="0" class="form-control" wire:model="sortOrder">
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model="isActive" id="ebook-document-active">
                            <label class="form-check-label" for="ebook-document-active">Kích hoạt</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="button" wire:click="save" wire:loading.attr="disabled">Lưu</button>
                    @if ($documentId)
                        <button class="btn btn-outline-secondary" type="button" wire:click="resetForm">Hủy</button>
                    @endif
                </div>

                <hr>

                <div class="mb-2"><strong>Upload Markdown</strong></div>
                <div class="input-group">
                    <input class="form-control" type="file" wire:model="upload" accept=".md,text/markdown,text/plain">
                    <button class="btn btn-outline-primary" type="button" wire:click="uploadMarkdown" wire:loading.attr="disabled">Upload</button>
                </div>
                @error('upload') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Tài liệu Markdown</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Tiêu đề</th><th>Thư mục</th><th>File</th><th class="text-end">Thao tác</th></tr></thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td>{{ $document->title }}</td>
                                    <td>{{ $document->folder?->name }}</td>
                                    <td><code>{{ $document->file_name }}</code></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $document->id }})">Sửa</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $document->id }})" wire:confirm="Xóa tài liệu này?">Xóa</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Chưa có tài liệu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($documents->hasPages())
                <div class="card-footer">{{ $documents->links() }}</div>
            @endif
        </div>
    </div>
</div>
