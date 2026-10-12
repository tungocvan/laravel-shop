@extends('admin.layouts.app')

@section('title', 'Danh mục thuốc Pharma')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-2xl font-semibold">Danh mục thuốc Pharma</h1><p class="text-sm text-gray-500">Danh mục độc lập, không ảnh hưởng danh mục sản phẩm website.</p></div>
    </div>
    @if(session('success')) <div class="rounded border border-green-200 p-3">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="rounded border border-red-200 p-3">{{ $errors->first() }}</div> @endif
    @can('create_pharma')
    <form method="POST" action="{{ route('admin.pharma.medicine-categories.store') }}" class="rounded-xl border p-4 grid gap-3 md:grid-cols-3">
        @csrf
        <input name="name" required maxlength="190" placeholder="Tên danh mục" class="rounded border p-2">
        <input name="slug" placeholder="Slug (để trống để tự tạo)" class="rounded border p-2">
        <select name="parent_id" class="rounded border p-2"><option value="">Danh mục gốc</option>@foreach(\Modules\Pharma\Models\MedicineCategory::query()->orderBy('name')->get() as $parent)<option value="{{ $parent->id }}">{{ $parent->name }}</option>@endforeach</select>
        <input name="description" placeholder="Mô tả" class="rounded border p-2">
        <input type="number" name="sort_order" min="0" value="0" required class="rounded border p-2">
        <select name="is_active" class="rounded border p-2"><option value="1">Hiển thị</option><option value="0">Ẩn</option></select>
        <button class="rounded bg-indigo-600 text-white px-4 py-2">Thêm danh mục</button>
    </form>
    @endcan
    <div class="rounded-xl border overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="bg-gray-50"><th class="p-3 text-left">Danh mục</th><th class="p-3 text-left">Danh mục cha</th><th class="p-3">Thuốc</th><th class="p-3">Trạng thái</th><th class="p-3">Thao tác</th></tr></thead>
        <tbody>@forelse($categories as $category)
            <tr class="border-t">
                <td class="p-3"><strong>{{ $category->name }}</strong><div class="text-gray-500">{{ $category->slug }}</div></td>
                <td class="p-3">{{ $category->parent?->name ?? 'Root' }}</td>
                <td class="p-3 text-center">{{ $category->medicines_count }}</td>
                <td class="p-3 text-center">{{ $category->is_active ? 'Hiện' : 'Ẩn' }}</td>
                <td class="p-3">
                    @can('edit_pharma')
                    <details><summary class="cursor-pointer">Sửa</summary>
                        <form method="POST" action="{{ route('admin.pharma.medicine-categories.update', $category) }}" class="grid gap-2 min-w-60 py-2">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $category->name }}" required class="border rounded p-2">
                            <input name="slug" value="{{ $category->slug }}" required class="border rounded p-2">
                            <select name="parent_id" class="border rounded p-2"><option value="">Root</option>@foreach(\Modules\Pharma\Models\MedicineCategory::query()->whereKeyNot($category->id)->orderBy('name')->get() as $parent)<option value="{{ $parent->id }}" @selected($category->parent_id === $parent->id)>{{ $parent->name }}</option>@endforeach</select>
                            <input name="description" value="{{ $category->description }}" class="border rounded p-2">
                            <input type="number" name="sort_order" value="{{ $category->sort_order }}" required class="border rounded p-2">
                            <select name="is_active" class="border rounded p-2"><option value="1" @selected($category->is_active)>Hiện</option><option value="0" @selected(!$category->is_active)>Ẩn</option></select>
                            <button class="bg-indigo-600 text-white rounded p-2">Lưu</button>
                        </form>
                    </details>
                    @endcan
                    @can('delete_pharma')
                    <form method="POST" action="{{ route('admin.pharma.medicine-categories.destroy', $category) }}" onsubmit="return confirm('Xóa danh mục thuốc này?')" class="mt-2">@csrf @method('DELETE')<button class="text-red-600">Xóa</button></form>
                    @endcan
                </td>
            </tr>
        @empty <tr><td colspan="5" class="p-5 text-center">Chưa có danh mục thuốc Pharma.</td></tr> @endforelse</tbody>
    </table></div>
    {{ $categories->links() }}
</div>
@endsection
