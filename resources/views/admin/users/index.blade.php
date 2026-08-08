@extends('layouts.admin', ['title' => 'Users'])

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Danh sách người dùng</h5>

        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm mới
        </a>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Quyền</th>
                <th width="120">Hành động</th>
            </tr>
            </thead>

            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ implode(', ', $user->getRoleNames()->toArray()) }}</td>
                    <td>
                        <a href="#" class="btn btn-sm btn-info">Sửa</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{ $users->links() }}
    </div>
</div>
@endsection
